<?php

/**
 * Class WFACP_InnoSend
 *
 * Handles compatibility with the Innosend plugin for checkout forms.
 * This class ensures that the necessary hooks and actions are properly
 * reattached to maintain functionality when using the Innosend plugin.
 *
 * @link https://innosend.eu/
 */
class WFACP_InnoSend {
	public function __construct() {
		add_action( 'wfacp_internal_css', [ $this, 'reattach_hook' ] );
	}

	public function reattach_hook() {
		try {
			if ( class_exists( '\App\Client\Versions\APS_WcLegacy' ) ) {
				$instance = WFACP_Common::remove_actions( 'woocommerce_checkout_after_order_review', 'App\Client\Versions\APS_WcLegacy', 'add_hidden_inputs' );
				if ( $instance instanceof \App\Client\Versions\APS_WcLegacy ) {
					add_action( 'wfacp_after_checkout_form_fields', [ $instance, 'add_hidden_inputs' ], 10 );
				}
			}
		} catch ( \Throwable $error ) {
			wc_get_logger()->error( $error->getMessage(), [ 'source' => __CLASS__ ] );
		}
	}
}

new WFACP_InnoSend();