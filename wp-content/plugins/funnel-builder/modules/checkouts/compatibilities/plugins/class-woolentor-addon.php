<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * plugin Name: ShopLentor – WooCommerce Builder for Elementor & Gutenberg by HasThemes
 * Plugin URI:  https://woolentor.com/
 *
 */

if ( ! class_exists( 'WFACP_Woolentor_Addon' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Woolentor_Addon{
		public function __construct() {
			add_action( 'wfacp_checkout_page_found', [ $this, 'action' ], 15 );
		}
		public function action() {
			if(class_exists('Woolentor_Woo_Custom_Template_Layout_Pro')  ){
				WFACP_Common::remove_actions('template_include','Woolentor_Woo_Custom_Template_Layout_Pro','change_page_template');
			}
		}
	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Woolentor_Addon(), 'woolentor' );
}

