<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WFACP_Compatibility_With_Legenda' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Compatibility_With_Legenda {

		public function __construct() {

			add_action( 'wfacp_checkout_page_found', [ $this, 'remove_styling' ] );

		}

		public function remove_styling() {
			if ( function_exists( 'etheme_init' ) ) {
				remove_action( 'wp_enqueue_scripts', 'etheme_init', 999 );
			}
		}

	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_Legenda(), 'legenda' );
}