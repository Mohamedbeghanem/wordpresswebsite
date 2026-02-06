<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * plugin Name: WPLoyalty by wployalty v.1.3.4
 * Plugin URI: https://wployalty.net
 *
 */
if ( ! class_exists( 'WFACP_Compatibility_With_Wployalty' ) ) {

	#[AllowDynamicProperties]
	class WFACP_Compatibility_With_Wployalty {
		public function __construct() {
				/* checkout page */
			add_action( 'after_setup_theme', [ $this, 'remove_actions' ] );
		}
		public function remove_actions() {
			if ( WFACP_Common::is_theme_builder() && class_exists( '\Wlr\App\Controllers\Site\DisplayMessage' ) ) {
				add_filter('wlr_is_checkout_earn_message_enabled','__return_false');
			}
		}
	}
	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_Wployalty(), 'wployalty' );
}
