<?php

class BWFAN_API_Get_Contact_Orders extends BWFAN_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $total_count = 0;
	public $contact;

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/v3/contacts/(?P<contact_id>[\\d]+)/orders';
	}

	public function default_args_values() {
		return array(
			'contact_id' => 0,
		);
	}

	public function process_api_call() {
		/** checking if id or email present in params **/
		$contact_id = $this->get_sanitized_arg( 'contact_id' );

		/** contact id missing than return  */
		if ( empty( $contact_id ) ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Contact id is mandatory', 'wp-marketing-automations' ) );
		}

		$contact = new BWFCRM_Contact( $contact_id );

		if ( ! $contact->is_contact_exists() ) {
			$this->response_code = 404;

			/* translators: 1: Contact ID */

			return $this->error_response( sprintf( __( 'No contact found with given id #%1$d', 'wp-marketing-automations' ), $contact_id ) );
		}

		$limit     = $this->get_sanitized_arg( 'limit', 'text_field' );
		$offset    = $this->get_sanitized_arg( 'offset', 'text_field' );
		$limit     = ! empty( $limit ) ? $limit : 10;
		$offset    = ! empty( $offset ) ? $offset : 0;
		$order_ids = $contact->get_orders( $offset, $limit );
		$orders    = $this->get_contact_activity_records( $contact_id, $order_ids );
		if ( empty( $orders ) ) {
			$response            = [
				'orders' => [],
			];
			$this->response_code = 200;

			/* translators: 1: Contact ID */

			return $this->success_response( $response, sprintf( __( 'No contact order found related with contact id : %1$d', 'wp-marketing-automations' ), $contact_id ) );
		}

		$response = [
			'orders' => $orders,
		];

		$this->total_count   = $contact->get_orders_count();
		$this->response_code = 200;
		$success_message     = __( 'Got all contact orders', 'wp-marketing-automations' );

		return $this->success_response( $response, $success_message );
	}


	public function get_contact_activity_records( $cid, $order_ids, $funnel_id = '' ) {
		if ( ! is_array( $order_ids ) || count( $order_ids ) === 0 ) {
			return [];
		}

		$all_orders = [];
		$sales_data = [];
		foreach ( $order_ids as $order_id ) {
			$conv_order   = $tags = $categories = [];
			$product_data = $this->get_single_order_info( $order_id, $cid );

			$conv_data  = apply_filters( 'wffn_conversion_tracking_data_activity', [], $cid, $order_id );
			$conv_order = array_merge( $conv_order, $conv_data );

			$conv_order['products'] = $product_data['products'];

			/** Get Products Categories name */
			if ( empty( $product_data['cat_ids'] ) ) {
				$categories = array_map( function ( $term_id ) {
					$cat = get_term( absint( $term_id ) );

					return $cat instanceof WP_Term ? $cat->name : false;
				}, array_unique( $product_data['cat_ids'] ) );
				$categories = array_values( array_filter( $categories ) );
			}

			/** Get Products Tags name */
			if ( ! empty( $product_data['tag_ids'] ) ) {
				$tags = array_map( function ( $term_id ) {
					$tag = get_term( absint( $term_id ) );

					return $tag instanceof WP_Term ? $tag->name : false;
				}, array_unique( $product_data['tag_ids'] ) );
				$tags = array_values( array_filter( $tags ) );
			}

			$conv_order['tags']       = $tags;
			$conv_order['categories'] = $categories;
			$sales_data               = array_merge( $sales_data, $product_data['timeline_data'] );
			$funnel_ids               = array_unique( array_column( $conv_order['products'], 'fid' ) );
			$conv_order['order_id']   = $order_id;

			if ( isset( $conv_order['overview'] ) ) {
				unset( $conv_order['overview'] );
			}

			if ( isset( $conv_order['conversion'] ) ) {
				$conv_order['conversion']['funnel_link']  = '';
				$conv_order['conversion']['funnel_title'] = '';
				$s_funnel_id                              = ! empty( $funnel_id ) ? $funnel_id : ( ( is_array( $funnel_ids ) && count( $funnel_ids ) > 0 ) ? $funnel_ids[0] : 0 );
				$conv_order['conversion']['funnel_id']    = $s_funnel_id;

				$get_funnel = new WFFN_Funnel( $s_funnel_id );
				if ( $get_funnel instanceof WFFN_Funnel && 0 !== $get_funnel->get_id() ) {
					$funnel_link                              = ( $get_funnel->get_id() === WFFN_Common::get_store_checkout_id() ) ? admin_url( "admin.php?page=bwf&path=/store-checkout" ) : admin_url( "admin.php?page=bwf&path=/funnels/$s_funnel_id" );
					$conv_order['conversion']['funnel_link']  = $funnel_link;
					$conv_order['conversion']['funnel_title'] = $get_funnel->get_title();
				}
			}

			$conv_order['customer_info'] = array(
				'email'            => '',
				'phone'            => '',
				'billing_address'  => '',
				'shipping_address' => '',
			);
			$conv_order['coupons']       = [];

			if ( ! empty( $order_id ) && absint( $order_id ) > 0 && function_exists( 'wc_get_order' ) ) {
				$order_data = wc_get_order( $order_id );
				if ( $order_data instanceof WC_Order ) {
					$conv_order['coupons']       = $order_data->get_coupon_codes();
					$conv_order['currency']      = BWFAN_Automations::get_currency( $order_data->get_currency() );
					$conv_order['customer_info'] = [
						'email'            => $order_data->get_billing_email(),
						'phone'            => $order_data->get_billing_phone(),
						'billing_address'  => wp_kses_post( $order_data->get_formatted_billing_address() ),
						'shipping_address' => wp_kses_post( $order_data->get_formatted_shipping_address() ),
						'purchased_on'     => ! empty( $order_data->get_date_created() ) ? $order_data->get_date_created()->date( 'Y-m-d H:i:s' ) : $product_data['date_added'],
						'payment_method'   => $order_data->get_payment_method_title(),
					];
					$conv_order['status']        = [
						'label' => ( 'trash' === $order_data->get_status() ) ? __( 'Trashed', 'wp-marketing-automations' ) : ucwords( wc_get_order_status_name( $order_data->get_status() ) ),
						'value' => 'wc-' . $order_data->get_status()
					];
				}
			}

			$all_orders[] = $conv_order;
		}


		return $all_orders;
	}

	function get_currency( $currency ) {
		$currency        = ! is_null( $currency ) ? $currency : get_option( 'woocommerce_currency' );
		$currency_symbol = get_woocommerce_currency_symbol( $currency );

		return [
			'code'              => $currency,
			'precision'         => wc_get_price_decimals(),
			'symbol'            => html_entity_decode( $currency_symbol ),
			'symbolPosition'    => get_option( 'woocommerce_currency_pos' ),
			'decimalSeparator'  => wc_get_price_decimal_separator(),
			'thousandSeparator' => wc_get_price_thousand_separator(),
			'priceFormat'       => html_entity_decode( get_woocommerce_price_format() ),
		];
	}

	public function get_single_order_info( $order_id, $cid = '' ) {

		$timeline_data = [];
		$products      = [];
		$data          = [
			'products'      => $products,
			'date_added'    => '',
			'timeline_data' => $timeline_data,
			'tag_ids'       => [],
			'cat_ids'       => [],
		];

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return $data;
		}

		$items    = $order->get_items();
		$subtotal = 0;
		$i        = 0;
		foreach ( $items as $item ) {
			$product       = new stdClass();
			$key           = 'checkout';
			$product->date = '';

			/**
			 * create data for show timeline data
			 */
			if ( class_exists( 'WFACP_Contacts_Analytics' ) && ! empty( $cid ) && 0 === $i ) {
				/*
				 * show checkout in timeline only one time per order
				 */
				$aero_obj         = WFACP_Contacts_Analytics::get_instance();
				$checkout_records = $aero_obj->get_contacts_revenue_records( $cid, $order_id );
				if ( is_array( $checkout_records ) && ! isset( $checkout_records['db_error'] ) && isset( $checkout_records[0] ) ) {
					$data['date_added']      = $checkout_records[0]->date;
					$data['timeline_data'][] = $checkout_records[0];
				}

			}
			if ( 'yes' === $item->get_meta( '_upstroke_purchase' ) ) {
				$key = 'upsell';
				if ( class_exists( 'WFOCU_Contacts_Analytics' ) ) {
					$upsell_obj     = WFOCU_Contacts_Analytics::get_instance();
					$upsell_records = $upsell_obj->get_contacts_revenue_records( $cid, $order_id );

					if ( is_array( $upsell_records ) && ! isset( $upsell_records['db_error'] ) && isset( $upsell_records[0] ) ) {
						$data['date_added']      = empty( $data['date_added'] ) ? $upsell_records[0]->date : $data['date_added'];
						$data['timeline_data'][] = $upsell_records[0];

					}

				}
			}
			if ( 'yes' === $item->get_meta( '_bump_purchase' ) ) {
				$key = 'bump';
				if ( class_exists( 'WFOB_Contacts_Analytics' ) ) {
					$bump_obj     = WFOB_Contacts_Analytics::get_instance();
					$bump_records = $bump_obj->get_contacts_revenue_records( $cid, $order_id );

					if ( is_array( $bump_records ) && ! isset( $bump_records['db_error'] ) && isset( $bump_records[0] ) ) {
						$data['date_added']      = empty( $data['date_added'] ) ? $bump_records[0]->date : $data['date_added'];
						$data['timeline_data'][] = $bump_records[0];
					}
				}
			}

			$sub_total          = $item->get_subtotal();
			$product->name      = $item->get_name();
			$product->revenue   = $sub_total;
			$product->type      = $key;
			$data['products'][] = $product;
			$subtotal           += $sub_total;
			$i ++;

			/** Fetch tags and categories */
			$item_data       = $item->get_data();
			$wc_product      = intval( $item_data['product_id'] ) ? wc_get_product( $item_data['product_id'] ) : '';
			$tag_ids         = $wc_product instanceof WC_Product ? $wc_product->get_tag_ids() : [];
			$cat_ids         = $wc_product instanceof WC_Product ? $wc_product->get_category_ids() : [];
			$data['tag_ids'] = array_merge( $data['tag_ids'], $tag_ids );
			$data['cat_ids'] = array_merge( $data['cat_ids'], $cat_ids );
		}
		$order_total      = $order->get_total();
		$total_discount   = $order->get_total_discount();
		$remaining_amount = $order_total - ( $subtotal - $total_discount );
		if ( $remaining_amount > 0 ) {
			$shipping_tax          = new stdClass();
			$shipping_tax->name    = __( 'Including shipping and taxes ,other costs', 'wp-marketing-automations' );
			$shipping_tax->revenue = round( $remaining_amount, 2 );
			$shipping_tax->type    = 'shipping';
			$data['products'][]    = $shipping_tax;
		}
		if ( $order->get_total_discount() > 0 ) {
			$discount           = new stdClass();
			$discount->name     = __( 'Discount', 'wp-marketing-automations' );
			$discount->revenue  = $order->get_total_discount();
			$discount->type     = 'discount';
			$data['products'][] = $discount;
		}

		return $data;
	}

	public function get_result_total_count() {
		return $this->total_count;
	}
}

if ( class_exists( 'woocommerce' ) ) {
	BWFAN_API_Loader::register( 'BWFAN_API_Get_Contact_Orders' );
}
