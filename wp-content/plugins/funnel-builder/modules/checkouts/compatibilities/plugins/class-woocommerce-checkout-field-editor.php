<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Plugin Name: WooCommerce Checkout Field Editor by WooCommerce (1.7.13)
 */
if ( ! class_exists( 'WFACP_Woocommerce_Checkout_Field_Editor' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Woocommerce_Checkout_Field_Editor {

		public function __construct() {
			/**
			 * Add Field for advanced field using filter hook
			 */
			add_filter( 'wfacp_advanced_order_fields', [ $this, 'add_advanced_fields' ] );

			add_filter( 'wfacp_detect_extra_fields', [ $this, 'detect_extra_fields' ], 9, 3 );
		}

		public function add_advanced_fields( $fields ) {
			if ( ! function_exists( 'wc_checkout_fields_modify_order_fields' ) ) {
				return $fields;
			}

			if ( isset( $fields['order'] ) || count( $fields['order'] ) == 0 ) {
				$temp = wc_checkout_fields_modify_order_fields( $fields );
				if ( isset( $temp['order'] ) ) {
					$fields['order'] = $temp['order'];
				}
			}

			return $fields;
		}

		public function detect_extra_fields( $fields, $others_fields, $temp ) {


			if ( is_array( $fields ) && count( $fields ) > 0 ) {
				foreach ( $fields as $key => $value ) {

					if ( false !== strpos( $value['id'], '_wc_custom_field' ) && ! in_array( $value['id'], $temp ) ) {
						if ( is_array( $others_fields ) && count( $others_fields ) > 0 ) {
							foreach ( $others_fields as $k => $v ) {
								if ( false !== strpos( $k, 'shipping_' ) ) {
									$v['class'][] = 'wfacp_shipping_field_hide';
									$v['class'][] = 'wfacp_shipping_fields';
								} else {
									$v['class'][] = 'wfacp_billing_field_hide';
									$v['class'][] = 'wfacp_billing_fields';
								}

								$fields[] = $v;
							}
						}
					}
				}
			}

			return $fields;
		}
	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Woocommerce_Checkout_Field_Editor(), 'woocommerce-checkout-field-editor' );
}