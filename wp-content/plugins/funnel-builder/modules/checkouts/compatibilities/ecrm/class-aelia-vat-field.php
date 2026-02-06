<?php
/*
 * plugin name: WooCommerce EU VAT Assistant by Aelia v.2.1.8.240109
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use Aelia\WC\EU_VAT_Assistant\Settings as Settings;
use Aelia\WC\EU_VAT_Assistant\WC_Aelia_EU_VAT_Assistant as EU_VAT_ASSISTANT;

if ( ! class_exists( 'WFACP_Compatibility_With_Aliea_vat' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Compatibility_With_Aliea_vat {

		private $new_fields = [];
		private $new_field_keys = [];

		public function __construct() {

			/* Register Add field */
			if ( WFACP_Common::is_funnel_builder_3() ) {
				add_action( 'wffn_rest_checkout_form_actions', [ $this, 'setup_fields_billing' ] );
			} else {
				add_action( 'init', [ $this, 'setup_fields_billing' ], 20 );
			}

			/* Disable to render backend register field */
			add_filter( 'wfacp_html_fields_billing_wfacp_vat_fields', '__return_false' );
			add_filter( 'wfacp_html_fields_vat_number', '__return_false' );

			/* Get All field on the Funnel checkout hook  */
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'action' ] );

			/* Process Billing Field  */
			add_action( 'process_wfacp_html', [ $this, 'billing_fields' ], 50, 2 );

			/* Display Global Dependency message show  */
			add_filter( 'wfacp_global_dependency_messages', [ $this, 'add_dependency_messages' ] );

			/* Internal Css Printe */
			add_action( 'wfacp_internal_css', [ $this, 'internal_css' ], 10 );

			add_filter( 'woocommerce_form_field_args', [ $this, 'add_default_wfacp_styling' ], 10, 2 );

			/* prevent third party fields and wrapper*/

			add_action( 'wfacp_add_billing_shipping_wrapper', '__return_false' );
			add_filter( 'wfacp_third_party_billing_fields', [ $this, 'disabled_third_party_fields' ] );


		}

		public function setup_fields_billing() {
			new WFACP_Add_Address_Field( 'wfacp_vat_fields', array(
				'type'        => 'wfacp_html',
				'label'       => __( 'vat Fields', 'woocommerce-aero-checkout' ),
				'placeholder' => __( 'vat Fields', 'woocommerce-fakturownia' ),
				'cssready'    => [ 'wfacp-col-left-third' ],
				'class'       => array( 'form-row-third first', 'wfacp-col-full' ),
				'required'    => false,
				'priority'    => 60,
			) );
		}

		public function action() {
			add_action( 'woocommerce_checkout_fields', [ $this, 'checkout_fields' ], 100 );


		}

		public function checkout_fields( $fields ) {

			if ( ! is_array( $fields ) || count( $fields ) == 0 ) {
				return $fields;
			}

			$aero_fields = WFACP_Common::get_aero_registered_checkout_fields();


			foreach ( $fields as $index => $field ) {

				if ( $index !== 'billing' && $index !== 'shipping' && $index !== 'advanced' ) {

					continue;
				}


				foreach ( $fields[ $index ] as $key => $field_val ) {

					if ( in_array( $key, $aero_fields ) || $key == 'billing_wfacp_vat_fields' || ! isset( $fields[ $index ][ $key ] ) ) {
						continue;
					}
					if ( false === strpos( $key, 'vat' ) ) {
						continue;
					}

					$this->new_fields[ $index ][ $key ] = $fields[ $index ][ $key ];
					$this->new_field_keys[]             = $key;
				}


			}


			return $fields;
		}

		public function billing_fields( $field, $key ) {

			if ( empty( $key ) || 'billing_wfacp_vat_fields' !== $key || 0 === count( $this->new_fields ) ) {
				return;
			}


			foreach ( $this->new_fields['billing'] as $field_key => $field_val ) {

				woocommerce_form_field( $field_key, $field_val );

			}


		}

		public function add_dependency_messages( $messages ) {
			if ( ! class_exists( 'Aelia\WC\EU_VAT_Assistant\WC_Aelia_EU_VAT_Assistant' ) ) {
				return $messages;
			}
			$messages[] = [
				'message'     => __( 'EU VAT field requires Billing Address field to present in checkout. Please drag Billing Address to place it in form.', 'woofunnels-aero-checkout' ),
				'id'          => 'address',
				'show'        => 'yes',
				'dismissible' => false,
				'is_global'   => false,
				'type'        => 'wfacp_error',
			];

			return $messages;
		}

		public function add_default_wfacp_styling( $args, $key ) {


			if ( 0 === count( $this->new_field_keys ) || ! in_array( $key, $this->new_field_keys ) ) {
				return $args;
			}


			if ( isset( $args['type'] ) && ( 'checkbox' !== $args['type'] && 'radio' !== $args['type'] && 'wfacp_radio' !== $args['type'] ) ) {
				$args['input_class'] = array_merge( [ 'wfacp-form-control' ], $args['input_class'] );
				$args['label_class'] = array_merge( [ 'wfacp-form-control-label' ], $args['label_class'] );
				$args['class']       = array_merge( [ 'wfacp-form-control-wrapper wfacp-col-full ' ], $args['class'] );
				$args['cssready']    = [ 'wfacp-col-left-half' ];


			} else {
				$args['class']    = array_merge( [ 'wfacp-form-control-wrapper wfacp-col-full ' ], $args['class'] );
				$args['cssready'] = [ 'wfacp-col-full' ];
			}


			return $args;
		}

		public function internal_css() {

			$instance = wfacp_template();
			$px       = $instance->get_template_type_px() . "px";
			if ( 'pre_built' !== $instance->get_template_type() ) {
				$px = "7px";
			}

			?>
            <style>
                @media (min-width: 768px) {
                    body .wfacp_main_form .aelia_wc_eu_vat_assistant.wfacp-col-full #vat_number-description {
                        position: absolute;
                        bottom: -22px;
                    }

                    body .wfacp_main_form .aelia_wc_eu_vat_assistant.wfacp-col-full #vat_number-description {
                        left: <?php echo $px; ?>;
                    }
                }

                body .wfacp_main_form #customer_location_self_certified_field {
                    padding: 0<?php echo $px; ?>;
                }

                #wfacp-e-form .wfacp_main_form #vat_number-description {
                    color: #777777;
                }

                #wfacp-e-form .wfacp_main_form.woocommerce p.aelia_wc_eu_vat_assistant label.wfacp-form-control-label {
                    bottom: auto;
                    top: 24px;
                    line-height: 26px;
                }


            </style>
			<?php

		}

		public function disabled_third_party_fields( $fields ) {

			if ( is_array( $this->new_fields['billing'] ) && count( $this->new_fields['billing'] ) > 0 ) {
				foreach ( $this->new_fields['billing'] as $i => $field_array ) {
					if ( isset( $fields[ $i ] ) ) {
						unset( $fields[ $i ] );
					}

				}
			}

			return $fields;
		}


	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_Aliea_vat(), 'aelia_vat' );


}