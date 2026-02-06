<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Plugin Name: GLS, votre partenaire transport by Nukium v.3.6.2
 * Description: Donnez le choix à vos clients du mode de livraison qui leur convient.
 */

if ( ! class_exists( 'WFACP_Compatibility_With_Woocommerce_GLS' ) ) {

	/**
	 * Class WFACP_Compatibility_With_Woocommerce_GLS
	 *
	 * Provides compatibility with GLS WooCommerce plugin by handling script conflicts
	 * and ensuring proper integration with AeroCheckout templates.
	 */
	class WFACP_Compatibility_With_Woocommerce_GLS {

		/**
		 * Constructor
		 *
		 * Initializes the compatibility hooks.
		 */
		public function __construct() {
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'handle_gls_compatibility' ], 999 );
		}

		/**
		 * Handle GLS compatibility
		 *
		 * Checks if GLS plugin is active and handles script conflicts
		 * with AeroCheckout templates.
		 */
		public function handle_gls_compatibility() {
			try {
				// Check if wfacp_template function exists
				if ( ! function_exists( 'wfacp_template' ) ) {
					return;
				}

				$shipping_settings = get_option( 'woocommerce_gls_relais_settings' );

				

				if (( !empty( $shipping_settings ) && isset($shipping_settings['enabled']) && "yes"===$shipping_settings['enabled']) || get_option( 'gls_settings_gmaps_enable' ) === 'yes') {
					return;
				}


				$instance = wfacp_template();
				if ( ! $instance instanceof WFACP_Template_Common ) {
					return;
				}

				// Check if Google autocomplete is enabled
				if ( ! isset( $instance->page_settings['enable_google_autocomplete'] ) || false === wc_string_to_bool( $instance->page_settings['enable_google_autocomplete'] ) ) {
					return;
				}

				// Add hook to dequeue conflicting scripts
				add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_gls_scripts' ], 20 );

			} catch ( Exception $e ) {

			}
		}

		/**
		 * Dequeue GLS scripts that conflict with AeroCheckout
		 *
		 * Removes GLS frontend scripts that may interfere with
		 * AeroCheckout's Google Maps integration.
		 */
		public function dequeue_gls_scripts() {

			// Deregister and dequeue GLS frontend scripts
			wp_dequeue_script( 'gls-frontend-js' );
			wp_deregister_script( 'gls-frontend-js' );
			wp_dequeue_script( 'gls-googlemaps-js' );
			wp_deregister_script( 'gls-googlemaps-js' );
		}
	}

	// Register the compatibility class
	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_Woocommerce_GLS(), 'woocommerce-gls' );
}
