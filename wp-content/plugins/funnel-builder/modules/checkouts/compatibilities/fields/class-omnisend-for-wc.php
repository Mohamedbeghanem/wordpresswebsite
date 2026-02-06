<?php

/**
 * Omnisend for Woocommerce by Omnisend
 * Plugin URI: https://www.omnisend.com
 */
if ( ! class_exists( 'WFACP_Omnisend_For_WC' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Omnisend_For_WC {
		public $instance = null;

		public function __construct() {


			/* Register Add field */
			add_filter( 'wfacp_advanced_fields', [ $this, 'add_field' ], 20 );
			add_filter( 'wfacp_html_fields_wfacp_omnisend_wc', '__return_false' );

			add_action( 'process_wfacp_html', [ $this, 'display_field' ], 999, 2 );


			/* default classes */
			add_filter( 'woocommerce_form_field_args', [ $this, 'add_default_wfacp_styling' ], 10, 2 );

			/* prevent third party fields and wrapper*/

			add_action( 'wfacp_add_billing_shipping_wrapper', '__return_false' );

			add_action( 'wfacp_after_checkout_page_found', [ $this, 'action' ], 10 );
		}

		public function add_field( $fields ) {

			$fields['wfacp_omnisend_wc'] = [
				'type'       => 'wfacp_html',
				'class'      => [ 'wfacp-col-full', 'wfacp-form-control-wrapper', 'wfacp_omnisend_wc' ],
				'id'         => 'wfacp_omnisend_wc',
				'field_type' => 'wfacp_omnisend_wc',
				'label'      => __( 'Omnisend WC', 'woofunnels-aero-checkout' ),

			];

			return $fields;
		}

		public function display_field( $field, $key ) {


			if ( ! $this->is_enable() || empty( $key ) || 'wfacp_omnisend_wc' !== $key || ! function_exists( 'omnisend_checkbox_custom_checkout_field' ) ) {
				return '';
			}


			?>
            <div class="wfacp_omnisend_wc" id="wfacp_omnisend_wc">
				<?php
				omnisend_checkbox_custom_checkout_field( WC()->checkout() );
				?>
            </div>
			<?php

		}

		public function is_enable() {
			$omnisend_settings = get_option( 'omnisend_checkout_opt_in_text' );

			if ( ! function_exists( 'omnisend_checkbox_custom_checkout_field' ) || empty( $omnisend_settings ) ) {
				return false;
			}

			return true;
		}

		public function add_default_wfacp_styling( $args, $key ) {

			if ( ! $this->is_enable() || 'omnisend_newsletter_checkbox' !== $key ) {
				return $args;
			}


			if ( isset( $args['type'] ) && 'checkbox' !== $args['type'] ) {

				$args['input_class'] = array_merge( [ 'wfacp-form-control' ], $args['input_class'] );
				$args['label_class'] = array_merge( [ 'wfacp-form-control-label' ], $args['label_class'] );
				$args['class']       = array_merge( [ 'wfacp-form-control-wrapper wfacp-col-full' ], $args['class'] );
				$args['cssready']    = [ 'wfacp-col-full' ];

			} else {
				$args['class']    = array_merge( [ 'wfacp-form-control-wrapper wfacp-col-full ' ], $args['class'] );
				$args['cssready'] = [ 'wfacp-col-full' ];
			}


			return $args;
		}

		public function action() {
			if ( ! class_exists( 'Omnisend_Settings' ) ) {
				return;
			}
			if ( Omnisend_Settings::get_checkout_opt_in_status() === Omnisend_Settings::STATUS_ENABLED && Omnisend_Settings::get_checkout_opt_in_text() ) {
				remove_action( 'woocommerce_after_checkout_billing_form', 'omnisend_checkbox_custom_checkout_field' );
			}
		}


	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Omnisend_For_WC(), 'wfacp-ominisend-wc' );
}
