<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WFACP_Compatibility_With_Konte_Theme' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Compatibility_With_Konte_Theme {
		public function __construct() {
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'remove_actions' ] );
			add_action( 'wfacp_internal_css', [ $this, 'internal_css' ] );
		}

		public function remove_actions() {
			WFACP_Common::remove_actions( 'woocommerce_before_checkout_form', 'Konte_WooCommerce_Template_Checkout', 'checkout_login_form' );
			WFACP_Common::remove_actions( 'woocommerce_before_checkout_form', 'Konte_WooCommerce_Template_Checkout', 'checkout_coupon_form' );


		}

		public function internal_css() {
			?>
            <style>
                .woocommerce-info .svg-icon, .woocommerce-info .svg-icon {
                    display: none;
                }
            </style>

			<?php
		}
	}

	if ( ! class_exists( 'Konte_WooCommerce_Template_Checkout' ) ) {
		return;
	}
	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_Konte_Theme(), 'konte' );
}