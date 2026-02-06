<?php
if ( ! class_exists( 'WFACP_EveryPay' ) ) {
	/**
	 * EveryPay Payment Gateway for WooCommerce
	 * https://wordpress.org/plugins/everypay-woocommerce-addon/
	 *
	 */
	#[AllowDynamicProperties]
	class WFACP_EveryPay {
		public function __construct() {
			$this->template_found();

		}

		public function template_found() {
			if ( wp_doing_ajax() ||  !is_checkout() ) {
				return;
			}
			$is_global_checkout = WFACP_Core()->public->is_checkout_override();
			if ( $is_global_checkout === true ) {
				add_filter( 'pre_option_woocommerce_checkout_page_id', '__return_true' );

			} else {
				add_filter( 'pre_option_woocommerce_checkout_page_id', [ $this, 'set_checkout_page_id' ] );
			}

		}

		public function set_checkout_page_id() {
			return WFACP_Common::get_id();
		}
	}

	WFACP_Plugin_Compatibilities::register( new WFACP_EveryPay(), 'everypay' );
}