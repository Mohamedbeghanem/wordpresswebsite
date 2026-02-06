<?php

if ( ! class_exists( 'WFACP_CartFlows_Compatibility' ) ) {


	#[AllowDynamicProperties]
	class WFACP_CartFlows_Compatibility {
		public function __construct() {
			$this->remove_template_redirect();
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'remove_render_cart_flows_inline_js' ] );
			add_filter( 'wfacp_skip_checkout_page_detection', [ $this, 'disable_aero_checkout_on_cart_flows_template' ] );

			add_action( 'wp', [ $this, 'check_global_setting' ], - 1 );

		}

		public function check_global_setting() {

			try {
				if ( ! class_exists( 'WFACP_Core' ) ) {
					return;
				}

				if ( ! class_exists( 'Cartflows_Helper' ) ) {
					return;
				}

				$checkout_page_id = WFACP_Common::get_checkout_page_id();
				if ( $checkout_page_id === 0 ) {
					return;
				}

				$setting = Cartflows_Helper::get_admin_settings_option( '_cartflows_common', false, false );
				if ( ! is_array( $setting ) || ! isset( $setting['override_global_checkout'] ) || 'enable' !== $setting['override_global_checkout'] ) {
					return;
				}
				remove_action( 'wp', [ Cartflows_Global_Checkout::get_instance(), 'override_global_checkout' ], 0 );
			} catch ( Error $e ) {

			}
		}

		public function remove_template_redirect() {
			WFACP_Common::remove_actions( 'template_redirect', 'Cartflows_Checkout_Markup', 'global_checkout_template_redirect' );
		}

		public function remove_render_cart_flows_inline_js() {
			try {


				if ( ! class_exists( 'Cartflows_Tracking' ) ) {
					return;
				}
				remove_action( 'wp_head', [ Cartflows_Tracking::get_instance(), 'add_tracking_code' ] );
			} catch ( Error $e ) {

			}
		}

		public function disable_aero_checkout_on_cart_flows_template( $status ) {
			global $post;
			if ( $post instanceof WP_Post && 'cartflows_step' === $post->post_type ) {
				return true;
			}

			return $status;
		}
	}

	return new WFACP_CartFlows_Compatibility();
}
