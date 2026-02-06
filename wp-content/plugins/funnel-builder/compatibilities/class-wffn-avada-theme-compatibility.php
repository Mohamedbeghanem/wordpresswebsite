<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Class WFFN_Compatibility_With_Avada_Theme
 */
if ( ! class_exists( 'WFFN_Compatibility_With_Avada_Theme' ) ) {
	class WFFN_Compatibility_With_Avada_Theme {

		public function __construct() {
			add_filter( 'elementor/frontend/builder_content_data', [ $this, 'remove_avada_parse_elementor_content' ], 9, 2 );
		}

		public function is_enable() {
			if ( class_exists( 'FusionBuilder' ) ) {
				return true;
			}

			return false;
		}

		/**
		 * @param $data
		 * @param $post_id
		 *
		 * @return mixed|void
		 */
		public function remove_avada_parse_elementor_content( $data, $post_id ) {

			if ( $post_id <= 0 ) {
				return $data;
			}
			$post = get_post( $post_id );

			if ( ! is_null( $post ) && in_array( $post->post_type, array(
					'wfacp_checkout',
					'wfocu_offer',
					'wffn_landing',
					'wffn_ty',
					'wffn_optin',
					'wffn_oty',

				), true ) ) {

				WFFN_Common::remove_actions( 'elementor/frontend/builder_content_data', 'FusionBuilder', 'parse_elementor_content' );
			}

			return $data;

		}
	}

	WFFN_Plugin_Compatibilities::register( new WFFN_Compatibility_With_Avada_Theme(), 'avada' );
}
