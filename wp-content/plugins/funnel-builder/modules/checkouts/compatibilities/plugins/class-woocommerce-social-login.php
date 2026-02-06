<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * plugin Name: WooCommerce Social Login by SkyVerge v( 2.16.0)
 *
 */

if ( ! class_exists( 'WFACP_Compatibility_With_Woocommerce_Social_Login' ) ) {

	#[AllowDynamicProperties]
	class WFACP_Compatibility_With_Woocommerce_Social_Login {
		private $instance = null;

		public function __construct() {
			/* checkout page */
			add_action( 'wfacp_after_template_found', [ $this, 'remove_actions' ] );
		}


		public function remove_actions() {

			if ( ! class_exists( 'WC_Social_Login' ) ) {
				return;
			}

			$page_settings = WFACP_Common::get_page_settings( WFACP_Common::get_id() );

			$smart_login = isset( $page_settings['display_smart_login'] ) ? trim( $page_settings['display_smart_login'] ) : "false";


			if ( "false" === $smart_login ) {
				return;
			}
			$this->instance = WFACP_Common::remove_actions( 'woocommerce_add_notice', 'WC_Social_Login_Frontend', 'checkout_social_login_message' );


			add_filter( 'woocommerce_add_notice', [ $this, 'checkout_social_login_message' ], 9999 );

		}

		public function checkout_social_login_message( $message ) {
			if ( ! $this->instance instanceof WC_Social_Login_Frontend ) {
				return $message;
			}


			if ( is_checkout() && $this->instance->is_displayed_on( 'checkout' ) && strpos( $message, '<a href="#" class="showlogin">' ) !== false ) {
				try {


					$available_providers = wc_social_login()->get_available_providers();
					if ( count( $available_providers ) > 0 ) {
						$message .= '. <br/>' . get_option( 'wc_social_login_text' ) . ' <a href="#" class="wfacp_display_smart_login">' . esc_html__( 'Click here to login', 'woocommerce-social-login' ) . '</a>';
					}
				} catch ( Exception $e ) {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'WFACP Social Login providers error: ' . $e->getMessage() );
					}
				}
			}

			return $message;
		}
	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_Woocommerce_Social_Login(), 'wc-social-loginc' );

}