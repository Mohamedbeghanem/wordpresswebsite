<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'WFACP_Analytics_Pint' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Analytics_Pint extends WFACP_Analytics {
		private static $self = null;
		protected $slug = 'pint';

		protected function __construct() {
			parent::__construct();
		}

		public static function get_instance() {
			if ( is_null( self::$self ) ) {
				self::$self = new self;
			}

			return self::$self;
		}

		public function get_key() {

			$get_pixel_key = apply_filters( 'wfacp_pinterest_key', $this->admin_general_settings->get_option( 'pint_key' ) );

			return empty( $get_pixel_key ) ? '' : $get_pixel_key;
		}

		public function enable_custom_event() {
			return $this->admin_general_settings->get_option( 'is_pint_custom_events' );
		}

		/**
		 * @param $product_obj WC_Product
		 * @param $cart_item WC_Order_Item
		 *
		 * @return array
		 */
		public function get_item( $product_obj, $cart_item ) {
			if ( ! $product_obj instanceof WC_Product ) {
				return parent::get_item( $product_obj, $cart_item );
			}

			$product_id = $this->get_cart_item_id( $cart_item );

			if ( $cart_item['variation_id'] ) {
				$variation = wc_get_product( $cart_item['variation_id'] );
				if ( $variation->get_type() === 'variation' ) {
					$categories   = implode( ', ', $this->get_object_terms( 'product_cat', $variation->get_parent_id() ) );
					$product_tags = implode( ', ', $this->get_object_terms( 'product_tag', $variation->get_parent_id() ) );
				} else {
					$categories   = implode( ', ', $this->get_object_terms( 'product_cat', $product_id ) );
					$product_tags = implode( ', ', $this->get_object_terms( 'product_tag', $product_id ) );
				}
			} else {
				$categories   = implode( ', ', $this->get_object_terms( 'product_cat', $product_id ) );
				$product_tags = implode( ', ', $this->get_object_terms( 'product_tag', $product_id ) );
			}


			$item_id   = $this->get_cart_item_id( $cart_item );
			$item_id   = $this->get_product_content_id( $item_id );
			$sub_total = apply_filters( 'wfacp_add_to_cart_tracking_line_subtotal', $cart_item['line_subtotal'], 'pint', $this->admin_general_settings );

			if ( ! wc_string_to_bool( $this->exclude_tax ) ) {
				$sub_total += $cart_item['line_subtotal_tax'];
			}

			$sub_total = $this->number_format( $sub_total );
			$quantity  = ! empty( $cart_item['quantity'] ) ? absint( $cart_item['quantity'] ) : 1;
			$line_item = [
				'product_id'       => $item_id,
				'product_name'     => $product_obj->get_name(),
				'product_price'    => floatval( $sub_total ),
				'product_quantity' => $quantity,
				'product_category' => $categories,
			];

			if ( ! empty( $product_tags ) ) {
				$line_item['tags'] = $product_tags;
			}

			return $line_item;
		}


		public function get_product_item( $product_obj ) {

			if ( ! $product_obj instanceof WC_Product ) {
				return parent::get_product_item( $product_obj );
			}

			$product_id = $product_obj->get_id();

			$categories   = implode( ', ', $this->get_object_terms( 'product_cat', $product_id ) );
			$product_tags = implode( ', ', $this->get_object_terms( 'product_tags', $product_id ) );

			$item_id = $product_id;
			$item_id = $this->get_product_content_id( $item_id );

			$sub_total = apply_filters( 'wfacp_add_to_cart_tracking_price', $product_obj->get_price(), $product_obj, 1, 'pint', $this->admin_general_settings );

			$sub_total       = $this->number_format( $sub_total );
			$item_added_data = [
				'currency'   => get_woocommerce_currency(),
				'user_role'  => WFACP_Common::get_current_user_role(),
				'value'      => $sub_total,
				'line_items' => [
					[
						'product_category' => $categories,
						'product_id'       => $item_id,
						'product_name'     => $product_obj->get_name(),
						'product_price'    => $sub_total,
						'product_quantity' => 1,
						'tags'             => $product_tags,
					]
				]
			];

			return $item_added_data;
		}


		public function remove_item( $product_obj, $cart_item ) {
			return $this->get_item( $product_obj, $cart_item );
		}


		public function get_checkout_data() {
			return $this->prepare_tracking_data();
		}

		public function get_add_to_cart_data() {
			return $this->prepare_tracking_data();
		}

		public function prepare_tracking_data() {
			global $post;
			$output = [];
			if ( ! function_exists( 'WC' ) || is_null( WC()->cart ) ) {
				return $output;
			}
			if ( ! is_null( $post ) && $post instanceof WP_Post ) {
				$output['page_title'] = $post->post_title;
				$output['post_id']    = $post->ID;
			}

			$contents = WC()->cart->get_cart_contents();
			if ( empty( $contents ) ) {
				return $output;
			}

			$output    = [ 'line_items' => [] ];
			$num_items = 0;
			$price     = 0;
			foreach ( $contents as $item ) {
				if ( $item['data'] instanceof WC_Product ) {
					$add_to_cart = $this->get_item( $item['data'], $item );
					if ( empty( $add_to_cart ) ) {
						continue;
					}
					$output['line_items'][] = $add_to_cart;
					$num_items              += absint( $add_to_cart['product_quantity'] );
					$price                  += $add_to_cart['product_quantity'] * $add_to_cart['product_price'];
				}
			}

			$event_data = [
				'event_id'       => WFACP_Common::generate_transient_key(),
				'value'          => $price,
				'order_quantity' => $num_items,
				'currency'       => get_woocommerce_currency(),
				'content_type'   => 'product',
				'user_role'      => WFACP_Common::get_current_user_role(),
				'event_url'      => $this->getEventRequestUri(),
			];

			$output = array_merge( $output, $event_data );

			if ( ! empty( $_COOKIE['wffn_referrer'] ) ) {
				$output['referrer'] = bwf_clean( $_COOKIE['wffn_referrer'] );
			}

			return array( $output );
		}

		public function is_global_add_to_cart_enabled() {
			return wc_string_to_bool( $this->admin_general_settings->get_option( 'is_pint_add_to_cart_global' ) );
		}

		public function is_global_pageview_enabled() {
			return wc_string_to_bool( $this->admin_general_settings->get_option( 'is_pint_page_view_global' ) );
		}

	}
}
