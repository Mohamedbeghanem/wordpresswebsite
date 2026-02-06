<?php
defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'WFACP_AJAX_Controller' ) ) {
	/**
	 * Class wfacp_AJAX_Controller
	 * Handles All the request came from front end or the backend
	 */
	#[AllowDynamicProperties]
	abstract class WFACP_AJAX_Controller {
		private static $bump_action_data = '';
		private static $output_resp = [];
		public static $posted_data = [];

		public static function init() {
			/**
			 * Backend AJAX actions
			 */
			self::handle_public_ajax();

		}


		public static function handle_public_ajax() {

			add_action( 'woocommerce_checkout_update_order_review', [ __CLASS__, 'check_actions' ], - 10 );
			add_action( 'bwf_global_save_settings_wfacp', [ __CLASS__, 'update_global_settings_fields' ] );

			$endpoints = self::get_available_public_endpoints();
			foreach ( $endpoints as $action => $function ) {
				if ( method_exists( __CLASS__, $function ) ) {
					add_action( 'wc_ajax_' . $action, [ __CLASS__, $function ] );
				} else {
					do_action( 'wfacp_wc_ajax_' . $action, $function );
				}
			}
		}

		public static function check_actions( $data ) {
			if ( empty( $data ) ) {
				return;
			}
			parse_str( $data, $post_data );
			if ( empty( $post_data ) || ! isset( $post_data['wfacp_input_hidden_data'] ) || empty( $post_data['wfacp_input_hidden_data'] ) ) {
				return;
			}

			$bump_action_data = json_decode( $post_data['wfacp_input_hidden_data'], true );

			if ( empty( $bump_action_data ) ) {
				return;
			}
			self::$posted_data = $post_data;
			$action            = $bump_action_data['action'];

			/* fetching available payment method before modifying bump */
			$input_data = [];
			if ( isset( $bump_action_data['data'] ) ) {
				$input_data = $bump_action_data['data'];
			}
			if ( 'apply_coupon_field' == $action || 'apply_coupon_main' == $action ) {
				self::$output_resp = self::apply_coupon( $bump_action_data );
			} else if ( 'remove_coupon_field' == $action || 'remove_coupon_main' == $action ) {
				self::$output_resp = self::remove_coupon( $bump_action_data );
			} elseif ( method_exists( __CLASS__, $action ) ) {
				self::$output_resp = self::$action( $input_data );
			}
			$bump_action_data['wfacp_id'] = $_REQUEST['wfacp_id'];

			self::$bump_action_data        = $action;
			self::$output_resp['wfacp_id'] = $bump_action_data['wfacp_id'];
			//JS callback ID
			self::$output_resp['callback_id'] = isset( $bump_action_data['callback_id'] ) ? $bump_action_data['callback_id'] : '';

			add_filter( 'woocommerce_update_order_review_fragments', [ __CLASS__, 'merge_fragments' ], 999 );

		}


		public static function merge_fragments( $fragments ) {

			$data                         = [];
			$data['action']               = self::$bump_action_data;
			$data['analytics_data']       = WFACP_Common::analytics_checkout_data();
			$extra_data                   = WFACP_Common::ajax_extra_frontend_data();
			$data                         = array_merge( $extra_data, $data );
			$fragments['wfacp_ajax_data'] = array_merge( $data, self::$output_resp );

			return $fragments;
		}

		public static function get_available_public_endpoints() {
			$endpoints = [
				'wfacp_get_divi_form_data' => 'get_divi_form_data',
				'wfacp_analytics'          => 'analytics'
			];

			return apply_filters( 'wfacp_public_endpoints', $endpoints );
		}

		public static function get_public_endpoints() {
			$endpoints        = [];
			$public_endpoints = self::get_available_public_endpoints();
			if ( count( $public_endpoints ) > 0 ) {
				foreach ( $public_endpoints as $key => $function ) {
					$endpoints[ $key ] = WC_AJAX::get_endpoint( $key );
				}
			}

			return $endpoints;
		}

		/*
		 * Send Response back to checkout page builder
		 * With nonce security keys
		 * also delete transient of particular checkout page it page is found in request
		 */

		public static function check_nonce() {
			$rsp = [
				'status' => 'false',
				'msg'    => 'Invalid Call',
			];
			if ( isset( $_POST['post_data'] ) ) {
				$post_data = [];
				parse_str( $_POST['post_data'], $post_data );
				if ( ! empty( $post_data ) ) {
					WFACP_Common::$post_data = $post_data;
				}
			}

			if ( ! isset( $_REQUEST['wfacp_nonce'] ) || ! wp_verify_nonce( $_REQUEST['wfacp_nonce'], 'wfacp_secure_key' ) ) {
				wp_send_json( $rsp );
			}
		}

		public static function send_resp( $data = array() ) {
			if ( ! is_array( $data ) ) {
				$data = [];
			}

			if ( function_exists( 'WC' ) && WC()->cart instanceof WC_Cart && ! is_null( WC()->cart ) ) {
				$data['cart_total'] = WC()->cart->get_total( 'edit' );

				if ( class_exists( 'WC_Subscriptions_Cart' ) ) {
					$data['cart_contains_subscription'] = WC_Subscriptions_Cart::cart_contains_subscription();
				}
				$data['cart_is_virtual'] = WFACP_Common::is_cart_is_virtual();
			}
			wp_send_json( $data );
		}


		public static function update_global_settings_fields( $options ) {

			$options = ( is_array( $options ) && count( $options ) > 0 ) ? wp_unslash( $options ) : 0;
			$resp    = [
				'status' => false,
				'msg'    => __( 'Changes saved', 'funnel-builder' ),
			];

			if ( ! is_array( $options ) || count( $options ) === 0 ) {
				return $resp;
			}

			$options['wfacp_checkout_global_css']    = isset( $options['wfacp_checkout_global_css'] ) ? stripslashes_deep( $options['wfacp_checkout_global_css'] ) : '';
			$options['wfacp_global_external_script'] = isset( $options['wfacp_global_external_script'] ) ? stripslashes_deep( $options['wfacp_global_external_script'] ) : '';

			update_option( '_wfacp_global_settings', $options, true );
			do_action( 'wfacp_global_settings_updated', $options );
			$resp['status'] = true;

			return $resp;
		}



		public static function update_cart_item_quantity( $post ) {

			$resp     = [
				'msg'    => '',
				'status' => false,
			];
			$quantity = floatval( $post['quantity'] );

			if ( $quantity <= 0 ) {
				$quantity = 0;
			}

			$cart_key = $post['cart_key'];

			if ( $quantity == 0 ) {

				$items       = WC()->cart->get_cart();
				$deletion_by = $post['by'];
				if ( 'mini_cart' !== $deletion_by && 1 == WFACP_Common::get_cart_count( $items ) && false == WFACP_Common::enable_cart_deletion() ) {
					$resp             = WFACP_Common::last_item_delete_message( $resp, $cart_key );
					$resp['qty']      = isset( $post['old_qty'] ) ? $post['old_qty'] : 1;
					$resp['cart_key'] = $cart_key;
					$resp['status']   = false;

					return $resp;
				}
				WC()->cart->remove_cart_item( $cart_key );
				$resp['status'] = true;

				return ( $resp );

			}
			WFACP_Common::disable_wcct_pricing();
			$cart_item = WC()->cart->get_cart_item( $cart_key );
			if ( empty( $cart_item ) ) {

				return ( $resp );
			}
			/**
			 * @var $product_obj WC_Product;
			 */
			$save_product_list = WC()->session->get( 'wfacp_product_data_' . WFACP_Common::get_id(), [] );

			$new_qty  = $quantity;
			$aero_key = '';
			if ( isset( $cart_item['_wfacp_product_key'] ) ) {
				$aero_key     = $cart_item['_wfacp_product_key'];
				$product_data = $save_product_list[ $aero_key ];
				if ( isset( $save_product_list[ $aero_key ] ) ) {
					$org_qty = ( $product_data['org_quantity'] );
					if ( $org_qty > 0 ) {
						$new_qty = $org_qty * $quantity;
					}
				}
			}
			$product_obj = $cart_item['data'];
			if ( is_null( $product_obj ) || ! $product_obj instanceof WC_Product ) {
				return $resp;
			}
			$stock_status = WFACP_Common::check_manage_stock( $product_obj, $new_qty );
			if ( false == $stock_status ) {

				/* Add the wc Notice */
				$current_session_order_id = isset( WC()->session->order_awaiting_payment ) ? absint( WC()->session->order_awaiting_payment ) : 0;
				$held_stock               = wc_get_held_stock_quantity( $product_obj, $current_session_order_id );
				$resp['error']            = sprintf( __( 'Sorry, we do not have enough "%1$s" in stock to fulfill your order (%2$s available). We apologize for any inconvenience caused.', 'woocommerce' ), $product_obj->get_name(), wc_format_stock_quantity_for_display( $product_obj->get_stock_quantity() - $held_stock, $product_obj ) );

				$resp['qty']      = $cart_item['quantity'];
				$resp['status']   = false;
				$resp['cart_key'] = $cart_key;

				return ( $resp );
			}

			$set = WC()->cart->set_quantity( $cart_key, $new_qty );

			if ( $set ) {
				if ( '' !== $aero_key ) {
					$save_product_list[ $aero_key ]['quantity'] = $quantity;
					WC()->session->set( 'wfacp_product_data_' . WFACP_Common::get_id(), $save_product_list );
				}
				$resp['qty']    = $quantity;
				$resp['status'] = true;
			}

			return ( $resp );
		}

		public static function update_cart_multiple_page( $post ) {
			$resp              = [
				'msg'    => '',
				'status' => false,
			];
			$success           = [];
			$switcher_products = $post['products'];
			$coupons           = $post['coupons'];
			$wfacp_id          = absint( $post['wfacp_id'] );
			if ( ! is_array( $switcher_products ) || empty( $switcher_products ) ) {
				return ( $resp );
			}
			do_action( 'wfacp_after_update_cart_multiple_page', $post, $success, $wfacp_id );
			WC()->cart->empty_cart();
			WFACP_Common::set_id( $wfacp_id );
			WFACP_Core()->public->get_page_data( $wfacp_id );


			$products = WFACP_Common::get_page_product( $wfacp_id );
			do_action( 'wfacp_before_add_to_cart', $products );
			$added_products = [];

			foreach ( $products as $index => $data ) {

				$product_id   = absint( $data['id'] );
				$quantity     = absint( $data['quantity'] );
				$variation_id = 0;
				if ( $data['parent_product_id'] && $data['parent_product_id'] > 0 ) {
					$product_id   = absint( $data['parent_product_id'] );
					$variation_id = absint( $data['id'] );
				}
				$product_obj = WFACP_Common::wc_get_product( ( $variation_id > 0 ? $variation_id : $product_id ), $index );
				if ( ! $product_obj instanceof WC_Product ) {
					continue;
				}

				if ( ! $product_obj->is_purchasable() ) {
					unset( $products[ $index ] );
					continue;
				}
				$stock_status = WFACP_Common::check_manage_stock( $product_obj, $quantity );

				if ( false == $stock_status ) {
					unset( $products[ $index ] );
					continue;
				}
				$products[ $index ] = $data;
				$product_obj->add_meta_data( 'wfacp_data', $data );
				$added_products[ $index ] = $product_obj;
			}

			if ( count( $added_products ) > 0 ) {
				add_filter( 'wp_redirect', '__return_false', 100 );
				foreach ( $added_products as $index => $product_obj ) {
					if ( ! isset( $switcher_products[ $index ] ) ) {
						continue;
					}
					$data         = $product_obj->get_meta( 'wfacp_data' );
					$product_id   = absint( $data['id'] );
					$quantity     = isset( $data['org_quantity'] ) ? absint( $data['org_quantity'] ) : absint( $data['quantity'] );
					$variation_id = 0;
					if ( $data['parent_product_id'] && $data['parent_product_id'] > 0 ) {
						$product_id   = absint( $data['parent_product_id'] );
						$variation_id = absint( $data['id'] );
					}
					try {
						$attributes  = [];
						$custom_data = [];
						if ( isset( $data['variable'] ) ) {
							$variation_id                             = $data['default_variation'];
							$attributes                               = $data['default_variation_attr'];
							$custom_data['wfacp_variable_attributes'] = $attributes;
						}
						$custom_data['_wfacp_product']     = true;
						$custom_data['_wfacp_product_key'] = $index;
						$current_quantity                  = 1;
						if ( $switcher_products[ $index ] ) {
							$current_quantity = absint( $switcher_products[ $index ] );
							if ( $current_quantity > 0 ) {
								$quantity = $current_quantity * $quantity;
							}
						}
						$products[ $index ]['quantity'] = $current_quantity;
						$custom_data['_wfacp_options']  = $data;
						$success[]                      = $cart_key = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $attributes, $custom_data );
						if ( is_string( $cart_key ) ) {

							$data['is_added_cart'] = $cart_key;
							$added_products[ $index ]->update_meta_data( 'wfacp_data', $data );

							$products[ $index ]['is_added_cart'] = $cart_key;

						} else {
							unset( $added_products[ $index ], $products[ $index ] );
						}
					} catch ( Exception $e ) {

					}
				}
			}
			do_action( 'wfacp_after_add_to_cart' );

			if ( count( $success ) > 0 ) {
				do_action( 'wfacp_before_update_cart_multiple_page', $post, $success, $wfacp_id );
				WC()->session->set( 'wfacp_id', WFACP_Common::get_id() );
				WC()->session->set( 'wfacp_cart_hash', md5( maybe_serialize( WC()->cart->get_cart_contents() ) ) );
				WC()->session->set( 'wfacp_product_objects_' . WFACP_Common::get_id(), $added_products );
				WC()->session->set( 'wfacp_product_data_' . WFACP_Common::get_id(), $products );
				if ( is_array( $coupons ) && ! empty( $coupons ) ) {
					remove_action( 'woocommerce_applied_coupon', [ WFACP_Core()->public, 'set_session_when_coupon_applied' ] );
					//	WC()->cart->add_discount( $coupon );
					foreach ( $coupons as $coupon_id ) {
						$coupon_id = trim( $coupon_id );
						WC()->cart->add_discount( $coupon_id );
					}

				}
				WC()->session->__unset( 'wfacp_woocommerce_applied_coupon_' . WFACP_Common::get_id() );
				wc_clear_notices();
				$resp = array(
					'success' => $success,
					'status'  => true
				);
			}
			do_action( 'wfacp_after_add_to_cart' );

			return ( $resp );
		}

		public static function apply_coupon( $bump_action_data ) {

			if ( isset( $bump_action_data['coupon_code'] ) ) {
				if ( isset( self::$posted_data['billing_email'] ) && is_email( self::$posted_data['billing_email'] ) ) {
					wc()->customer->set_billing_email( self::$posted_data['billing_email'] );
				}
				remove_all_filters( 'woocommerce_coupons_enabled' );
				do_action( 'wfacp_before_coupon_apply', $bump_action_data );
				$status = true;
				add_filter( 'woocommerce_coupon_message', function ( $msg, $msg_code ) {
					if ( 200 == $msg_code ) {
						return '';
					}

					return $msg;
				}, 10, 2 );
				if ( apply_filters( 'wfacp_apply_coupon_via_ajax', true, $bump_action_data ) ) {
					$status = WC()->cart->add_discount( sanitize_text_field( $bump_action_data['coupon_code'] ) );
				} else {
					do_action( 'wfacp_apply_coupon_via_ajax_placeholder', $bump_action_data );
				}

				WC()->cart->calculate_totals();


				$all_notices  = WC()->session->get( 'wc_notices', array() );
				$notice_types = apply_filters( 'woocommerce_notice_types', array( 'error', 'success', 'notice' ) );
				$message      = [];

				foreach ( $notice_types as $notice_type ) {
					if ( wc_notice_count( $notice_type ) > 0 ) {
						$message = array(
							$notice_type => $all_notices[ $notice_type ],
						);
					}
				}


				wc_clear_notices();

				$resp = array(
					'status'  => $status,
					'message' => $message,
				);
			} else {
				$resp = array(
					'status'  => false,
					'message' => array(
						'error' => [ 'Please provide a coupon code' ],
					),
				);
			}

			return $resp;
		}

		public static function remove_coupon( $bump_action_data ) {
			$coupon = isset( $bump_action_data['coupon_code'] ) ? wc_format_coupon_code( wp_unslash( $bump_action_data['coupon_code'] ) ) : false;
			wc_clear_notices();
			do_action( 'wfacp_before_coupon_removed', $bump_action_data );
			$status = true;
			if ( empty( $coupon ) ) {
				$message = __( 'Sorry there was a problem removing this coupon.', 'woocommerce' );
				$status  = false;
			} else {
				WC()->cart->remove_coupon( $coupon );
				$message = __( 'Coupon has been removed.', 'woocommerce' );
				do_action( 'wfacp_after_coupon_removed', $bump_action_data );

			}

			$resp = array(
				'status'  => $status,
				'message' => $message,
			);

			wc_clear_notices();

			return $resp;
		}

		public static function prep_fees() {
			$fees = [];

			foreach ( WC()->cart->get_fees() as $fee ) {
				$out         = (object) [];
				$out->name   = $fee->name;
				$out->amount = ( 'excl' == WFACP_Common::get_tax_display_mode() ) ? wc_price( $fee->total ) : wc_price( $fee->total + $fee->tax );
				$fees[]      = $out;
			}

			return $fees;
		}

		public static function remove_cart_item( $post ) {

			$resp = [
				'msg'    => '',
				'status' => false,
			];

			$wfacp_id = absint( $post['wfacp_id'] );
			if ( $wfacp_id == 0 ) {
				return ( $resp );
			}
			if ( isset( $post['cart_key'] ) && '' !== $post['cart_key'] ) {
				$cart_item_key = sanitize_text_field( wp_unslash( $post['cart_key'] ) );
				$cart_item     = WC()->cart->get_cart_item( $cart_item_key );
				if ( $cart_item ) {
					WFACP_Common::set_id( $wfacp_id );
					$status = WC()->cart->remove_cart_item( $cart_item_key );
					if ( $status ) {
						$product           = wc_get_product( $cart_item['product_id'] );
						$item_is_available = false;
						// Don't show undo link if removed item is out of stock.
						if ( $product && $product->is_in_stock() && $product->has_enough_stock( $cart_item['quantity'] ) ) {
							$item_is_available = true;
							$removed_notice    = "&nbsp;" . ' <a href="javascript:void(0)" class="wfacp_restore_cart_item" data-cart_key="' . $cart_item_key . '">' . __( 'Undo?', 'woocommerce' ) . '</a>';
						} else {
							$item_is_available = false;
							/* Translators: %s Product title. */
							$removed_notice = sprintf( __( '%s removed.', 'woocommerce' ), '' );
						}
						$resp['item_is_available'] = $item_is_available;
						$resp['status']            = true;
						$resp['remove_notice']     = $removed_notice;

						return ( $resp );
					}
				}
			}

			return ( $resp );
		}

		public static function undo_cart_item( $post ) {
			$resp = [
				'msg'    => '',
				'status' => false,
			];

			if ( isset( $post['cart_key'] ) && '' !== $post['cart_key'] ) {
				// Undo Cart Item.
				$cart_item_key = sanitize_text_field( wp_unslash( $post['cart_key'] ) );
				WC()->cart->restore_cart_item( $cart_item_key );
				do_action( 'wfacp_restore_cart_item', $cart_item_key, $post );
				$item                 = WC()->cart->get_cart_item( $cart_item_key );
				$resp['restore_item'] = [];
				if ( is_array( $item ) && $item['data'] instanceof WC_Product ) {
					$data                 = [ 'type' => $item['data']->get_type() ];
					$resp['restore_item'] = apply_filters( 'wfacp_restore_cart_item_data', $data, $item );
				}
				if ( isset( $item['_wfacp_product_key'] ) ) {
					$product_key       = $item['_wfacp_product_key'];
					$save_product_list = WC()->session->get( 'wfacp_product_data_' . WFACP_Common::get_id() );
					if ( isset( $save_product_list[ $product_key ] ) ) {
						$save_product_list[ $product_key ]['is_added_cart'] = $cart_item_key;
						WC()->session->set( 'wfacp_product_data_' . WFACP_Common::get_id(), $save_product_list );
					}
				}
				$resp['status'] = true;
			}

			return ( $resp );
		}


		public static function get_divi_form_data() {


			if ( isset( $_REQUEST['wfacp_id'] ) ) {
				$post_id = $_REQUEST['wfacp_id'];
				$post    = get_post( $post_id );
				if ( ! is_null( $post ) && $post->post_type == WFACP_Common::get_post_type_slug() ) {

					WFACP_Common::wc_ajax_get_refreshed_fragments();

				} else {
					return;
				}
			}

			$json = file_get_contents( 'php://input' );
			if ( '' !== $json ) {
				$json = json_decode( $json, true );
			} else {
				$json = [];
			}

			$template = wfacp_template();
			$id       = 'wfacp_divi_checkout_form';
			WFACP_Common::set_session( $id, $json );
			$template->set_form_data( $json );

			if ( isset( $_COOKIE['wfacp_divi_open_page'] ) && wp_doing_ajax() ) {
				$cookie = $_COOKIE['wfacp_divi_open_page'];
				$parts  = explode( '@', $cookie );
				$template->set_current_open_step( $parts[1] );
			}
			include $template->wfacp_get_form();

			exit( 0 );
		}

		public static function analytics() {
			self::check_nonce();
			$resp       = array( 'status' => false );
			$data       = $_POST['data'];
			$event_data = $data['event_data'];
			$source     = isset( $data['source'] ) ? $data['source'] : '';

			if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
				$source = $_SERVER['HTTP_REFERER'];
			}

			$pixel = WFACP_Analytics_Pixel::get_instance();

			$get_all_fb_pixel  = $pixel->get_key();
			$get_each_pixel_id = explode( ',', $get_all_fb_pixel );

			if ( ! is_array( $get_each_pixel_id ) || empty( $get_each_pixel_id ) ) {
				self::send_resp( $resp );
			}


			$user_data                      = WFACP_Common::pixel_advanced_matching_data();
			$user_data['client_ip_address'] = ! empty( WC_Geolocation::get_ip_address() ) ? WC_Geolocation::get_ip_address() : '127.0.0.1';
			$user_data['client_user_agent'] = wc_get_user_agent();
			if ( isset( $_COOKIE['_fbp'] ) && ! empty( $_COOKIE['_fbp'] ) ) {
				$user_data['_fbp'] = wc_clean( $_COOKIE['_fbp'] ); //phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
			}
			if ( isset( $_COOKIE['_fbc'] ) && ! empty( $_COOKIE['_fbc'] ) ) {
				$user_data['_fbc'] = wc_clean( $_COOKIE['_fbc'] ); //phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
			} elseif ( isset( $_COOKIE['wffn_fbclid'] ) && isset( $_COOKIE['wffn_flt'] ) && ! empty( $_COOKIE['wffn_fbclid'] ) ) {
				$user_data['_fbc'] = 'fb.1.' . strtotime( $_COOKIE['wffn_flt'] ) . '.' . $_COOKIE['wffn_fbclid'];
			}

			foreach ( $get_each_pixel_id as $key => $pixel_id ) {
				$access_token = $pixel->get_conversion_api_access_token();
				$access_token = explode( ',', $access_token );
				if ( is_array( $access_token ) && count( $access_token ) > 0 ) {
					if ( isset( $access_token[ $key ] ) ) {
						BWF_Facebook_Sdk_Factory::setup( trim( $pixel_id ), trim( $access_token[ $key ] ) );
					}
				}

				$get_test      = $pixel->get_conversion_api_test_event_code();
				$get_test      = explode( ',', $get_test );
				$is_test_event = $pixel->admin_general_settings->get_option( 'is_fb_conv_enable_test' );
				if ( is_array( $is_test_event ) && count( $is_test_event ) > 0 && $is_test_event[0] === 'yes' && is_array( $get_test ) && count( $get_test ) > 0 ) {
					if ( isset( $get_test[ $key ] ) && ! empty( $get_test[ $key ] ) ) {
						BWF_Facebook_Sdk_Factory::set_test( trim( $get_test[ $key ] ) );
					}
				}

				BWF_Facebook_Sdk_Factory::set_partner( 'woofunnels' );
				$instance = BWF_Facebook_Sdk_Factory::create();
				if ( is_null( $instance ) ) {
					self::send_resp( $resp );
				}

				if ( is_array( $event_data ) && count( $event_data ) > 0 ) {
					foreach ( $event_data as $single_item ) {
						$instance->set_event_id( $single_item['event_id'] );
						$instance->set_user_data( $user_data );
						$instance->set_event_source_url( $source );

						$fb_single_data = isset( $single_item['data'] ) ? $single_item['data'] : [];
						if ( isset( $fb_single_data['contents'] ) ) {
							foreach ( $fb_single_data['contents'] as $ckey => $a ) {
								unset( $fb_single_data['contents'][ $ckey ]['value'] );
							}
						}
						$instance->set_event_data( $single_item['event'], $fb_single_data );
						$response = $instance->execute();
						WFACP_Common::maybe_insert_log( '----Facebook conversion API-----------' . print_r( $response, true ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
					}

					$resp['status'] = true;
				}

			}


			self::send_resp( $resp );
		}

	}

	WFACP_AJAX_Controller::init();
}
