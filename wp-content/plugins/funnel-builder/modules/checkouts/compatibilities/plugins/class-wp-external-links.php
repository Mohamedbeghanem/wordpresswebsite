<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Plugin Name: WP External Links by WebFactory Ltd
 * Version:        2.63
 * Author:         WebFactory Ltd
 * Plugin URI:     https://getwplinks.com/
 */
if ( ! class_exists( 'WFACP_Compatibility_With_WP_External_Links' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Compatibility_With_WP_External_Links {
		public function __construct() {
			add_action( 'wfacp_template_load', [ $this, 'remove_action' ] );
		}

		public function remove_action() {
			if ( !$this->is_enable() || ! wp_doing_ajax() ) {
				return;
			}
			add_filter( 'wpel_apply_settings', '__return_false' );
		}

		public function is_enable() {
			return class_exists( 'WPEL_Front' );
		}
	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_WP_External_Links(), 'wp-external-links' );
}