<?php

/**
 * Image Optimizer – Optimize Images and Convert to WebP or AVIF
 * By Elementor
 * https://wordpress.org/plugins/image-optimization/
 */
if ( ! class_exists( 'BWFAN_Compatibility_With_Image_Optimization' ) ) {
	class BWFAN_Compatibility_With_Image_Optimization {

		public function __construct() {
			add_action( 'action_scheduler_init', [ $this, 'remove_image_optimization_hook' ], 9 );
		}

		/**
		 * Remove image optimisation hook
		 *
		 * @return void
		 */
		public function remove_image_optimization_hook() {
			$rest_route = filter_input( INPUT_GET, 'rest_route' );
			if ( empty( $rest_route ) ) {
				$rest_route = $_SERVER['REQUEST_URI'] ?? '';
			}
			if ( empty( $rest_route ) ) {
				return;
			}
			$rest_route = bwf_clean( $rest_route );

			if ( false !== strpos( $rest_route, '/woofunnels/v1/worker' ) || false !== strpos( $rest_route, '/autonami/v2/worker' ) || false !== strpos( $rest_route, '/autonami/v1/worker' ) ) {
				BWFAN_Common::remove_actions( 'action_scheduler_init', 'ImageOptimization\Modules\Optimization\Components\Actions_Cleanup', 'schedule_cleanup' );
			}
		}
	}

	new BWFAN_Compatibility_With_Image_Optimization();
}
