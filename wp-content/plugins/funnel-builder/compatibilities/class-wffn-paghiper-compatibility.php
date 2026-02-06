<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WFFN_Compatibility_With_Paghiper
 * Handles compatibility with PagHiper payment gateway
 */
if ( ! class_exists( 'WFFN_Compatibility_With_Paghiper' ) ) {
	class WFFN_Compatibility_With_Paghiper {

		public function __construct() {
			if ( ! $this->is_enable() ) {
				return;
			}

			add_filter( 'wffn_show_wc_order_details_before_table', '__return_true', 10, 1 );
		}


		public function is_enable() {
			return class_exists( 'WC_Paghiper' );
		}


	}

	WFFN_Plugin_Compatibilities::register( new WFFN_Compatibility_With_Paghiper(), 'paghiper' );
}