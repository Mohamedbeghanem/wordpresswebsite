<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Compatibility class for All in One SEO plugin
 *
 * Handles conflicts between WooFunnels AeroCheckout and AIOSEO plugin
 * by removing conflicting head actions on checkout pages.
 *
 * @package WFACP
 * @subpackage Compatibilities
 */

if ( ! class_exists( 'WFACP_Compatibility_With_AIOSEO' ) ) {

	#[AllowDynamicProperties]
	class WFACP_Compatibility_With_AIOSEO {

		/**
		 * Constructor - Initialize compatibility hooks
		 */
		public function __construct() {
			// Hook into template found action to remove conflicting AIOSEO actions
			add_action( 'wfacp_after_template_found', [ $this, 'remove_actions' ] );
		}

		/**
		 * Remove AIOSEO head actions that conflict with checkout templates
		 *
		 * @param object $template The checkout template object
		 */
		public function remove_actions( $template ) {
			$is_global_checkout = WFACP_Core()->public->is_checkout_override();

			// Handle AIOSEO Pro version conflicts
			if ( class_exists( 'AIOSEO\Plugin\Pro\Main\Head' ) ) {
				// Remove init action for embed form templates
				if ( 'embed_form' == $template->get_template_type() ) {
					WFACP_Common::remove_actions( 'wp_head', 'AIOSEO\Plugin\Pro\Main\Head', 'init' );
				}

				// Remove wpHead action for global checkout pages
				$this->remove_wp_head_action( 'AIOSEO\Plugin\Pro\Main\Head', $is_global_checkout );
			}

			// Handle AIOSEO free version conflicts
			if ( class_exists( 'AIOSEO\Plugin\Common\Main\Head' ) ) {
				$this->remove_wp_head_action( 'AIOSEO\Plugin\Common\Main\Head', $is_global_checkout );
			}
		}

		/**
		 * Remove wpHead action from AIOSEO class if conditions are met
		 *
		 * @param string $class_name The AIOSEO class name
		 * @param bool   $is_global_checkout Whether this is a global checkout page
		 */
		private function remove_wp_head_action( $class_name, $is_global_checkout ) {
			if ( $is_global_checkout && method_exists( $class_name, 'wpHead' ) ) {
				WFACP_Common::remove_actions( 'wp_head', $class_name, 'wpHead' );
			}
		}
	}

	// Register the compatibility class
	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_AIOSEO(), 'aioseo' );
}