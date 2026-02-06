<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * plugin Name: Borlabs Cookie - Cookie Opt-in v.2.2.67 by Borlabs GmbH and Borlabs Cookie v.3.2.2 by Borlabs GmbH
 *
 */
if ( ! class_exists( 'WFACP_Borlabs_Cookie_Opt_In' ) ) {

	#[AllowDynamicProperties]
	class WFACP_Borlabs_Cookie_Opt_In {
		public function __construct() {

			/**
			 * Template Redirect conflict issue resolved
			 */
			add_action( 'wfacp_checkout_page_found', [ $this, 'remove_action' ] );
		}


		public function remove_action() {
			if ( class_exists( 'BorlabsCookie\Cookie\Frontend\Buffer' ) ) {
				remove_action( 'template_redirect', [ BorlabsCookie\Cookie\Frontend\Buffer::getInstance(), 'handleBuffering' ], 19021987 );
			}
			if ( class_exists( 'Borlabs\Cookie\System\WordPressFrontendDriver\OutputBufferManager' ) ) {
				WFACP_Common::remove_actions( 'template_redirect', 'Borlabs\Cookie\System\WordPressFrontendDriver\OutputBufferManager', 'startBuffering' );
			}
		}
	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Borlabs_Cookie_Opt_In(), 'borlabs_cookie' );


}