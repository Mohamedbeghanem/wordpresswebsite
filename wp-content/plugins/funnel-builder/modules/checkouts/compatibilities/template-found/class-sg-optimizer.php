<?php
/*
 * Plugin Name: Speed Optimizer by SiteGround v.7.4.6
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'WFACP_Compatibility_With_Sg_optimizer' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Compatibility_With_Sg_optimizer {

		public function __construct() {
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'remove_sg_optiomizer_hook' ] );
		}

		public function remove_sg_optiomizer_hook() {
			if ( class_exists( 'SiteGround_Optimizer\Combinator\Combinator' ) ) {
				WFACP_Common::remove_actions( 'wp_print_styles', 'SiteGround_Optimizer\Combinator\Combinator', 'pre_combine_header_styles' );
			}

			add_filter( 'sgo_js_minify_exclude', array( $this, 'exclude_javascript' ), 999 );
			add_filter( 'sgo_javascript_combine_exclude', array( $this, 'exclude_javascript' ), 999 );
			add_filter( 'sgo_javascript_combine_excluded_inline_content', array( $this, 'exclude_javascript' ), 999 );
			add_filter( 'sgo_js_async_exclude', array( $this, 'exclude_js_async_exclude' ), 999 );
		}

		public function exclude_javascript( $excluded_handles ) {
			$excluded_handles[] = 'wfacp_checkout_js';

			return $excluded_handles;
		}

		public function exclude_js_async_exclude( $excluded_handles ) {
			$excluded_handles[] = 'wfacp_checkout_js';
			$excluded_handles[] = 'wfacp-intlTelInput-js';
			$excluded_handles[] = 'jquery-core';
			$excluded_handles[] = 'jquery-migrate';

			return $excluded_handles;
		}
	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_Sg_optimizer(), 'sg_optimizer' );
}
