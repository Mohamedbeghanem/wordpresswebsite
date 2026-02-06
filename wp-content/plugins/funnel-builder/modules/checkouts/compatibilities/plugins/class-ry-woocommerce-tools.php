<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Compatibility class for RY Tools for WooCommerce plugin
 *
 * Plugin Name: RY Tools for WooCommerce
 * Version: 3.5.10
 * Author URI: https://richer.tw/
 * Author: Richer Yang
 *
 * This class handles compatibility with RY Tools for WooCommerce plugin
 * by properly managing the checkout fragment updates for ECPay shipping.
 */

if ( ! class_exists( 'WFACP_RY_WooCommerce_tools' ) ) {
	#[AllowDynamicProperties]
	class WFACP_RY_WooCommerce_tools {

		/**
		 * Constructor - initializes the compatibility hooks
		 */
		public function __construct() {
			add_action( 'wfacp_template_load', [ $this, 'action' ] );
			add_filter( 'wfacp_show_shipping_options', '__return_true' );
		}

		/**
		 * Main compatibility action
		 *
		 * Removes the original action and re-adds it with proper priority
		 * to ensure compatibility with AeroCheckout templates
		 */
		public function action() {
			// Remove the original action and get the instance
			$instance = WFACP_Common::remove_actions( 'woocommerce_update_order_review_fragments', 'RY_WT_WC_ECPay_Shipping', 'checkout_choose_cvs_info' );

			// Only proceed if we have a valid instance and the method exists
			if ( $instance && method_exists( $instance, 'checkout_choose_cvs_info' ) ) {
				// Re-add the action with higher priority to ensure it runs after AeroCheckout
				add_action( 'woocommerce_update_order_review_fragments', [ $instance, 'checkout_choose_cvs_info' ], 9999 );
			}
		}
	}

	// Register the compatibility class
	WFACP_Plugin_Compatibilities::register( new WFACP_RY_WooCommerce_tools(), 'ry-woocommerce-tools' );
}
