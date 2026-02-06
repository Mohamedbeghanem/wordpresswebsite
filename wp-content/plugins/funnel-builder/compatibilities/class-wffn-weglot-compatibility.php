<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Weglot compatibility for return thankyou page with language code
 * Class WFFN_Compatibility_With_Weglot
 */
if ( ! class_exists( 'WFFN_Compatibility_With_Weglot' ) ) {

	class WFFN_Compatibility_With_Weglot {

		public function __construct() {
			add_filter( 'wfty_woocommerce_get_checkout_order_received_url', array( $this, 'weglot_comptibility_function' ) );
		}

		public function is_enable() {
			return true;
		}

		public function weglot_comptibility_function( $urlRedirect ) {
			if ( ! function_exists( 'weglot_get_service' ) ) {
				return $urlRedirect;
			}
			$url_service = weglot_get_service( 'Request_Url_Service_Weglot' );

			if ( empty( $url_service ) ) {
				return $urlRedirect;
			}

			$url = $url_service->create_url_object( $urlRedirect );

			return $url->getForLanguage( $url_service->get_current_language() );
		}

	}

	WFFN_Plugin_Compatibilities::register( new WFFN_Compatibility_With_Weglot(), 'weglot' );
}