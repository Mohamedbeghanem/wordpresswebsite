<?php
if ( ! class_exists( 'WFACP_Compatibility_wfirma_wc' ) ) {
	/**
	 * WooCommerce wFirma By WP Desk
	 * Plugin URI: https://www.wpdesk.pl/sklep/wfirma-woocommerce/
	 * Version: 2.2.6
	 */
	#[AllowDynamicProperties]
	class WFACP_Compatibility_wfirma_wc {
		private $billing_new_fields = [
			'billing_nip',
		];

		public function __construct() {


			/* Register Add field */

			if ( WFACP_Common::is_funnel_builder_3() ) {
				add_action( 'wffn_rest_checkout_form_actions', [ $this, 'setup_fields_billing' ] );
			} else {
				$this->setup_fields_billing();
			}

			/* prevent third party fields and wrapper*/

			add_action( 'wfacp_add_billing_shipping_wrapper', '__return_false' );
			add_filter( 'wfacp_third_party_billing_fields', [ $this, 'disabled_third_party_billing_fields' ] );

		}

		public function setup_fields_billing() {
			new WFACP_Add_Address_Field( 'nip', array(
				'type'        => 'text',
				'label'       => __( 'NIP', 'woocommerce-wfirma' ),
				'placeholder' => __( 'NIP', 'woocommerce-wfirma' ),
				'cssready'    => [ 'wfacp-col-full' ],
				'class'       => array( 'form-row-third first', 'wfacp-col-full' ),
				'required'    => false,
				'priority'    => 60,
			) );
		}

		public function disabled_third_party_billing_fields( $fields ) {
			if ( is_array( $this->billing_new_fields ) && count( $this->billing_new_fields ) ) {
				foreach ( $this->billing_new_fields as $i => $key ) {
					if ( isset( $fields[ $key ] ) ) {
						unset( $fields[ $key ] );
					}
				}
			}

			return $fields;
		}
	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_wfirma_wc(), 'woocommerce-wfirma' );
}



