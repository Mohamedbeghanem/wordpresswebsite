<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * plugin Name: WooCommerce Pre-Orders by  WooCommerce (2.1.0)
 * Plugin URL: https://woocommerce.com/products/woocommerce-pre-orders/
 */
if ( ! class_exists( 'WFACP_Compatibility_With_Woocommerce_Pre_Orders' ) ) {

	#[AllowDynamicProperties]
	class WFACP_Compatibility_With_Woocommerce_Pre_Orders {
		public function __construct() {
			/* checkout page */
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'remove_actions' ], 999 );
			add_action( 'wfacp_before_process_checkout_template_loader', [ $this, 'remove_actions' ], 999 );
		}


		public function remove_actions() {
			if ( ! $this->is_enable() || ! class_exists( 'WC_Pre_Orders_Cart' ) ) {
				return;
			}
			if ( is_null( WC()->cart ) ) {
				return false;
			}

			$instance = wfacp_template();

			$status = WC_Pre_Orders_Cart::cart_contains_pre_order();
			if ( true === $status && ! is_null( $instance ) ) {
				remove_filter( 'woocommerce_order_button_text', [ $instance, 'change_place_order_button_text' ], 11 );
				remove_filter( 'woocommerce_order_button_text', [ $instance, 'replace_merge_tag' ], 12 );
			}

		}

		public function is_enable() {
			return class_exists( 'WC_Pre_Orders_Checkout' );
		}
	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_Woocommerce_Pre_Orders(), 'woocommerce-pre-orders' );

}
