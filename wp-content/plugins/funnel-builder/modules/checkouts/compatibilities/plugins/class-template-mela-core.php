<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 *  Plugin Name:  TemplateMela Core - Plugin for the TemplateMela Theme by TemplateMela v.3.5.2
 *  Plugin URI:   #
 *
 *  Compatibility Handler for TemplateMela Core Plugin
 *
 * This file provides compatibility adjustments for the TemplateMela Core plugin
 * when used with the Aero Checkout plugin. It is not a standalone plugin file,
 * but a compatibility class registered with the plugin compatibility system.
 */
if ( ! class_exists( 'WFACP_Compatibility_With_Template_Mela_Core' ) ) {

	#[AllowDynamicProperties]
	class WFACP_Compatibility_With_Template_Mela_Core {
		public function __construct() {
			/* checkout page */
			add_action( 'wfacp_template_load', [ $this, 'remove_actions' ] );
			add_action( 'wfacp_internal_css', [ $this, 'add_css' ] );
		}

		public function remove_actions() {
			// Check if TemplateMela Core class exists before removing actions
			if ( ! class_exists( 'TemplateMelaCore_WooCommerce' ) ) {
				return;
			}

			// Remove TemplateMela Core actions that interfere with Aero checkout
			WFACP_Common::remove_actions( 'woocommerce_cart_item_name', 'TemplateMelaCore_WooCommerce', 'review_product_name_html' );
			WFACP_Common::remove_actions( 'woocommerce_checkout_before_order_review', 'TemplateMelaCore_WooCommerce', 'open_checkout_order_review' );
			WFACP_Common::remove_actions( 'woocommerce_checkout_after_order_review', 'TemplateMelaCore_WooCommerce', 'close_checkout_order_review' );
			WFACP_Common::remove_actions( 'woocommerce_checkout_order_review', 'TemplateMelaCore_WooCommerce', 'add_before_order_review' );
		}

		public function add_css() {
			?>
			<style>
				body #wfacp-e-form .wfacp_main_form .wfacp-section .woocommerce-checkout-payment {
					margin: 0;
				}
			</style>
			<?php
		}
	}

	// Register the compatibility class
	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_Template_Mela_Core(), 'wfacp-templatemela-core' );

}
