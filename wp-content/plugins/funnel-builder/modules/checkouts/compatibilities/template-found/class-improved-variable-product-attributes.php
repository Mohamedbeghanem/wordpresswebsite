<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WFACP_Plugin_Compatibilities_improved_variable_product_attributes' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Plugin_Compatibilities_improved_variable_product_attributes {

		public function __construct() {
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'attach_action_in_footer' ] );
		}

		public function attach_action_in_footer() {
			global $ivpa_global;
			$ivpa_global['init'] = 1;
		}
	}


	WFACP_Plugin_Compatibilities::register( new WFACP_Plugin_Compatibilities_improved_variable_product_attributes(), 'ivpa' );

}


