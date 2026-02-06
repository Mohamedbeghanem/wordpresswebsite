<?php
if ( defined( 'ELEMENTOR_VERSION' ) && ! class_exists( 'WFACP_Compatibility_With_Elementor_Pro' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Compatibility_With_Elementor_Pro {
		public function __construct() {
			add_action('wfacp_outside_header',[$this,'un_hook']);
			add_action( 'woocommerce_checkout_update_order_review', [ $this, 'remove_actions' ], - 5 );
			add_action( 'woocommerce_before_calculate_totals', [ $this, 'remove_actions' ], - 5 );
		}
		public function un_hook() {
			if(class_exists('ElementorPro\Modules\Woocommerce\Widgets\Checkout')){
				WFACP_Common::remove_actions('woocommerce_checkout_before_customer_details','ElementorPro\Modules\Woocommerce\Widgets\Checkout','woocommerce_checkout_before_customer_details');
				WFACP_Common::remove_actions('woocommerce_checkout_after_customer_details','ElementorPro\Modules\Woocommerce\Widgets\Checkout','woocommerce_checkout_after_customer_details');
				WFACP_Common::remove_actions('woocommerce_checkout_before_order_review_heading','ElementorPro\Modules\Woocommerce\Widgets\Checkout','woocommerce_checkout_before_order_review_heading_1');
				WFACP_Common::remove_actions('woocommerce_checkout_before_order_review_heading','ElementorPro\Modules\Woocommerce\Widgets\Checkout','woocommerce_checkout_before_order_review_heading_2');
			}
		}

		public function remove_actions( $status ) {
			if ( ! isset( $_GET['wfacp_is_checkout_override'] ) ) {
				return $status;
			}

			if ( ! class_exists( '\ElementorPro\Modules\Woocommerce\Module' ) ) {
				return $status;
			}
			$instance = ElementorPro\Modules\Woocommerce\Module::instance();
			if ( is_null( $instance ) ) {
				return $status;
			}
			remove_action( 'woocommerce_checkout_update_order_review', [ $instance, 'load_widget_before_wc_ajax' ] );
			remove_action( 'woocommerce_before_calculate_totals', [ $instance, 'load_widget_before_wc_ajax' ] );

			return $status;

		}
	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_Elementor_Pro(), 'elementor_pro' );
}