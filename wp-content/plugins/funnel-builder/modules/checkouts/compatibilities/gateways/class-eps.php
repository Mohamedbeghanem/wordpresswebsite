<?php

/**
 * Eps Überweisung By PSA Gmbh Version: 2.1.2.
 *
 * #[AllowDynamicProperties]
 * class WFACP_Compatibility_With_Woocommce_EPS
 */
if ( ! class_exists( 'WFACP_Compatibility_With_Woocommce_EPS' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Compatibility_With_Woocommce_EPS {

		public function __construct() {
			add_filter( 'wfacp_skip_checkout_page_detection', [ $this, 'skip_detection' ] );
		}

		public function skip_detection( $status ) {


			if ( is_null( WC()->cart ) || is_null( WC()->session ) || WC()->cart->is_empty() ) {


				return $status;
			}

			$current_gateway = WC()->session->get( 'chosen_payment_method', '' );


			if ( 'eps' == $current_gateway ) {
				return true;
			}

			return $status;
		}
	}


	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_Woocommce_EPS(), 'woocommerce-eps' );
}