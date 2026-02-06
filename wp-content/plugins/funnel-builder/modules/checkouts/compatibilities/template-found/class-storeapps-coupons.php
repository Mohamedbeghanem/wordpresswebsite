<?php
/**
 * WooCommerce Smart Coupons by StoreApps v.9.54.0
 */
if ( ! class_exists( 'WFACP_Storeapps_Coupons' ) ) {
	class WFACP_Storeapps_Coupons {
		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'wfacp_after_checkout_page_found', array( $this, 'handle_aero_coupons' ) );

			add_action( 'wc_sc_before_auto_apply_coupons', [ $this, 'handle_auto_apply' ], 10 );

			/**
			 * Remove wc_sc_before_auto_apply_coupons hook when version is greater than or equal to 9.54.0
			 */
			add_action( 'wfacp_template_load', array( $this, 'remove_handle_aero_coupons' ) );
		}

		/**
		 * Handle Aero coupons processing
		 */
		public function handle_aero_coupons() {
			// Check if direct coupon-code is present in URL
			if ( isset( $_REQUEST['coupon-code'] ) ) {
				$coupon_code = $_REQUEST['coupon-code'];
			} // Otherwise check for aero-coupons parameter
			elseif ( isset( $_REQUEST['aero-coupons'] ) ) {
				$coupon_code             = $_REQUEST['aero-coupons'];
				$_REQUEST['coupon-code'] = $coupon_code;
			} else {
				return;
			}

			// Process coupon if Smart Coupons plugin is active
			if ( class_exists( 'WC_SC_Coupon_Actions' ) && method_exists( 'WC_SC_Coupon_Actions', 'coupon_action' ) ) {
				WC_SC_Coupon_Actions::get_instance()->coupon_action( $coupon_code );
			}
		}

		public function handle_auto_apply() {
			if ( did_action( 'wc_ajax_checkout' ) || ! wp_doing_ajax() || ! did_action( 'wfacp_after_template_found' ) ) {
				return;
			}
			add_filter( 'woocommerce_notice_types', '__return_empty_array' );
		}

		/**
		 * Remove handle auto coupons hook for newer versions
		 */
		public function remove_handle_aero_coupons() {
			if ( ! defined( 'WC_SC_PLUGIN_FILE' ) ) {
				return;
			}

			$plugin_data = get_plugin_data( WC_SC_PLUGIN_FILE );
			$current_wsc_version = $plugin_data['Version'];

			// Remove hook when version is >= 9.54.0
			if ( version_compare( $current_wsc_version, '9.54.0', '>=' ) ) {
				WFACP_Common::remove_actions( 'wc_sc_before_auto_apply_coupons', 'WFACP_Storeapps_Coupons', 'handle_auto_apply' );
			}
		}
	}

	new WFACP_Storeapps_Coupons();
}