<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * plugin Name: Complianz | GDPR/CCPA Cookie Consent by complianz-gdpr (6.5.6)
 *
 */
if ( ! class_exists( 'WFACP_Compatibility_With_Complianz_GDPR_Cookie_Consent' ) ) {

	#[AllowDynamicProperties]
	class WFACP_Compatibility_With_Complianz_GDPR_Cookie_Consent {
		public function __construct() {
			/* checkout page */
			add_action( 'wfacp_after_template_found', [ $this, 'remove_actions' ] );
		}


		public function remove_actions() {
			remove_filter( 'cmplz_known_script_tags', 'cmplz_acf_script' );
		}
	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_Complianz_GDPR_Cookie_Consent(), 'complianz-gdpr' );


}