<?php
if ( ! class_exists( 'WFACP_WeGlot' ) ) {
	/**
	 * WeGlot Translation
	 */
	#[AllowDynamicProperties]
	class WFACP_WeGlot {
		public function __construct() {
			add_action( 'wfacp_after_template_found', [ $this, 'action' ] );
		}

		public function action() {
			add_filter( 'woocommerce_update_order_review_fragments', [ $this, 'unset_fragments' ], 998 );
		}

		public function unset_fragments( $fragments ) {
			if ( isset( $fragments['place_order_text'] ) ) {
				unset( $fragments['place_order_text'] );
			}

			return $fragments;
		}
	}

	new WFACP_WeGlot();
}