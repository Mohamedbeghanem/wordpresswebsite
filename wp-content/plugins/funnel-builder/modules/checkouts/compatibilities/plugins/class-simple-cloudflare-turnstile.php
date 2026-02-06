<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Plugin Name: Simple Cloudflare Turnstile by Elliot Sowersby, RelyWP
 * Version: 1.32.2.
 * Author URI: https://www.relywp.com
 *
 */

if ( ! class_exists( 'WFACP_Simple_Cloudflare_Turnstile' ) ) {
	#[AllowDynamicProperties]

	class WFACP_Simple_Cloudflare_Turnstile {
		public function __construct() {
			add_action( 'wfacp_template_load', [ $this, 'actions' ] );
			add_action( 'wfacp_internal_css', [ $this, 'add_css' ] );
		}

		public function actions() {
			try {
				// Only proceed if the plugin is configured to show before submit and Check if the function exists before trying to use it
				if ( get_option( 'cfturnstile_woo_checkout_pos' ) !== 'beforesubmit' || ! function_exists( 'cfturnstile_field_checkout' )) {
					return;
				}
				// Remove the original hook and add it to our custom hook
				remove_action( 'woocommerce_review_order_before_submit', 'cfturnstile_field_checkout', 10 );
				add_action( 'wfacp_woocommerce_review_order_before_submit', 'cfturnstile_field_checkout', 10 );
			} catch ( Exception $e ) {
				// Log the error for debugging purposes
				error_log( 'WFACP_Simple_Cloudflare_Turnstile::__construct - ' . $e->getMessage() );
			}
		}
		public function add_css() {
			echo '<style>#wfacp-sec-wrapper #cf-turnstile-woo-checkout {text-align: left;}</style>';
		}
	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Simple_Cloudflare_Turnstile(), 'simple-cloudflare-turnstile' );
}

