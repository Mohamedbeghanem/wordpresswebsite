<?php

/**
 * ATUM WooCommerce Inventory Management and Stock Tracking
 * By Stock Management Labs
 * https://wordpress.org/plugins/atum-stock-manager-for-woocommerce/
 */
if ( ! class_exists( 'BWFAN_Compatibility_With_Atom_Stock_Manager' ) ) {
	class BWFAN_Compatibility_With_Atom_Stock_Manager {

		public function __construct() {
			add_action( 'action_scheduler_failed_action', [ $this, 'unhook_atom_stock_manager' ], 9, 2 );
		}

		/**
		 * Remove atom stock manager hook
		 *
		 * @param $action_id
		 * @param $timeout
		 *
		 * @return void
		 */
		public function unhook_atom_stock_manager( $action_id, $timeout ) {
			$rest_route = filter_input( INPUT_GET, 'rest_route' );
			if ( empty( $rest_route ) ) {
				$rest_route = $_SERVER['REQUEST_URI'] ?? '';
			}
			if ( empty( $rest_route ) ) {
				return;
			}
			$rest_route = bwf_clean( $rest_route );

			if ( false !== strpos( $rest_route, '/woofunnels/v1/worker' ) || false !== strpos( $rest_route, '/autonami/v2/worker' ) || false !== strpos( $rest_route, '/autonami/v1/worker' ) ) {
				BWFAN_Common::remove_actions( 'action_scheduler_failed_action', 'Atum\Api\AtumApi', 'maybe_retry_full_export_action' );
			}
		}
	}

	new BWFAN_Compatibility_With_Atom_Stock_Manager();
}
