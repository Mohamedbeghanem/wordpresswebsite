<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WFFN_Compatibility_With_BreakDance_Builder
 */
if ( ! class_exists( 'WFFN_Compatibility_With_BreakDance_Builder' ) ) {
	class WFFN_Compatibility_With_BreakDance_Builder {

		public function __construct() {
			add_filter( 'body_class', array( $this, 'add_break_dance_class' ), 10, 1 );
		}

		public function is_enable() {
			if ( defined( 'BREAKDANCE_WOO_DIR' ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Add breakdown class in body html
		 *
		 * @param $body_class
		 *
		 * @return mixed
		 */
		public function add_break_dance_class( $body_class ) {
			global $post;
			if ( ! is_null( $post ) && in_array( $post->post_type, array(
					'wfacp_checkout',
					'wfocu_offer',
					'wffn_landing',
					'wffn_ty',
					'wffn_optin',
					'wffn_oty',

				), true ) ) {

				if ( is_array( $body_class ) && count( $body_class ) > 0 ) {
					if ( ! in_array( 'breakdance', $body_class, true ) ) {
						$body_class[] = 'breakdance';
					}
				}
			}

			return $body_class;
		}

	}

	WFFN_Plugin_Compatibilities::register( new WFFN_Compatibility_With_BreakDance_Builder(), 'bd_builder' );
}

