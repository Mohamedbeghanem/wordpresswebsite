<?php
if ( ! class_exists( 'WFACP_Compatibility_Fattura_24' ) ) {
	/**
	 * WooCommerce Fattura24 by Fattura24.com (Version 7.1.0)
	 * Plugin Path: http://www.fattura24.com
	 */
	#[AllowDynamicProperties]
	class WFACP_Compatibility_Fattura_24 {

		private $add_fields = [
			'billing_checkbox',
			'billing_fiscalcode',
			'billing_vatcode',
			'billing_recipientcode',
			'billing_pecaddress',
		];
		private $new_fields = [];

		public function __construct() {
			/* Register Add field */
			if ( WFACP_Common::is_funnel_builder_3() ) {
				add_action( 'wffn_rest_checkout_form_actions', [ $this, 'setup_fields_billing' ] );
			} else {
				add_action( 'init', [ $this, 'setup_fields_billing' ], 20 );
			}
			add_filter( 'wfacp_html_fields_billing_fattura_24', '__return_false' );
			/* Process Html */
			add_action( 'process_wfacp_html', [ $this, 'call_fields_hook' ], 50, 2 );

			/* Get Billing Checkout fields */
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'action' ] );

			/* Add Default Styling  */
			add_filter( 'woocommerce_form_field_args', [ $this, 'add_default_wfacp_styling' ], 10, 2 );

			/* Add Internal Css for plugin */
			add_filter( 'wfacp_internal_css', [ $this, 'wfacp_internal_css' ] );


			/* prevent third party fields and wrapper*/

			add_action( 'wfacp_add_billing_shipping_wrapper', '__return_false' );
			add_filter( 'wfacp_third_party_billing_fields', [ $this, 'disabled_third_party_fields' ] );


		}


		public function setup_fields_billing() {
			new WFACP_Add_Address_Field( 'fattura_24', [
				'type'        => 'wfacp_html',
				'label'       => __( 'Fattura 24', 'woofunnels-aero-checkout' ),
				'placeholder' => __( 'Fattura 24', 'woofunnels-aero-checkout' ),
				'cssready'    => [ 'wfacp-col-left-third' ],
				'class'       => array( 'form-row-third first', 'wfacp-col-full' ),
				'required'    => false,
				'priority'    => 60,
			] );
		}

		public function checkout_fields( $fields ) {

			if ( ! is_array( $fields['billing'] ) || count( $fields['billing'] ) == 0 ) {
				return $fields;
			}

			foreach ( $this->add_fields as $field_key ) {
				if ( isset( $fields['billing'][ $field_key ] ) ) {
					$this->new_fields[ $field_key ] = $fields['billing'][ $field_key ];
				}
			}

			return $fields;
		}

		public function call_fields_hook( $field, $key ) {

			if ( empty( $key ) || 'billing_fattura_24' !== $key || 0 === count( $this->new_fields ) ) {
				return;
			}


			foreach ( $this->new_fields as $field_key => $field_val ) {
				woocommerce_form_field( $field_key, $field_val );
			}


		}

		public function action() {
			add_action( 'woocommerce_checkout_fields', [ $this, 'checkout_fields' ], 100 );
		}

		public function add_default_wfacp_styling( $args, $key ) {

			if ( 0 === count( $this->new_fields ) || ! array_key_exists( $key, $this->new_fields ) ) {
				return $args;
			}


			if ( isset( $args['type'] ) && 'checkbox' !== $args['type'] ) {

				$args['input_class'] = array_merge( [ 'wfacp-form-control' ], $args['input_class'] );
				$args['label_class'] = array_merge( [ 'wfacp-form-control-label' ], $args['label_class'] );
				$args['class']       = array_merge( [ 'wfacp-form-control-wrapper wfacp-col-left-half ' ], $args['class'] );
				$args['cssready']    = [ 'wfacp-col-left-half' ];


			} else {
				$args['class']    = array_merge( [ 'wfacp-form-control-wrapper wfacp-col-full ' ], $args['class'] );
				$args['cssready'] = [ 'wfacp-col-full' ];
			}


			return $args;
		}

		public function wfacp_internal_css() {

			$instance = wfacp_template();
			if ( ! $instance instanceof WFACP_Template_Common ) {
				return;
			}

			$bodyClass = "body #wfacp-sec-wrapper ";
			$cssHtml   = "<style>";
			$cssHtml   .= $bodyClass . " .wfacp-form:not(.wfacp-top) p.wfacp-form-control-wrapper.wfacp-anim-wrap label.wfacp-form-control-label {top: 6px!important;font-size: 12px!important;background: 0 0!important;bottom: auto;right: 8px;margin-top: 0;line-height: 1.3;}";
			$cssHtml   .= "";

			$cssHtml .= "</style>";
			echo $cssHtml;

		}

		public function disabled_third_party_fields( $fields ) {
			if ( is_array( $this->new_fields ) && count( $this->new_fields ) ) {
				foreach ( $this->new_fields as $k => $field ) {
					if ( isset( $fields[ $k ] ) ) {
						unset( $fields[ $k ] );
					}
				}
			}

			return $fields;
		}
	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_Fattura_24(), 'wfacp-_Fattura-24' );
}
