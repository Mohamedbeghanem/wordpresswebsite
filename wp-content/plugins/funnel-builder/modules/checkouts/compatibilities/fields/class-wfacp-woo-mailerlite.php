<?php

/**
 * Plugin : MailerLite - WooCommerce integration by MailerLite (v.2.1.29)
 * Plugin URI:  https://wordpress.org/plugins/woo-mailerlite/
 * WFACP_Compatibility_WC_MailerLite
 */
if ( ! class_exists( 'WFACP_Compatibility_WC_MailerLite' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Compatibility_WC_MailerLite {
		private $position = null;
		private $position_show = [ 'review_order_before_submit' ];

		private $display_dragable_field = true;
		private $remove_fields = [ 'woo_ml_subscribe' ];


		public function __construct() {
			/*
			 *  Register Add Custom Fields for Mailer Lite
			 */
			add_filter( 'wfacp_advanced_fields', [ $this, 'add_field' ], 20 );
			/*
			 *  Prevent to display custom field
			 */
			add_filter( 'wfacp_html_fields_woo_ml_subscribe_html', '__return_false' );
			/*
			 *  Remove Plugin's Hook on the funnelkit checkout page Except review_order_before_submit Hook
			 */
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'actions' ] );

			/*
			 *  Process Custom field According to plugin functionality
			 */
			add_action( 'process_wfacp_html', [ $this, 'call_fields_hook' ], 50, 3 );
			/*
			 *  Add the custom css for Mail lite field on the Funnelkit Checkout
			 */
			add_action( 'wfacp_internal_css', [ $this, 'internal_css' ] );

			/* prevent third party fields and wrapper*/

			add_action( 'wfacp_add_billing_shipping_wrapper', '__return_false' );

			/**
			 * Unset Billing Extra Fields to prevent Duplicate fields
			 */
			add_filter( 'wfacp_detect_extra_fields', [ $this, 'detect_extra_fields' ], 99, 3 );

		}

		public function add_field( $fields ) {
			$fields['woo_ml_subscribe_html'] = [
				'type'       => 'wfacp_html',
				'class'      => [ 'woo_ml_subscribe' ],
				'id'         => 'woo_ml_subscribe_html',
				'field_type' => 'advanced',
				'label'      => __( 'Woo MailerLite', 'woofunnels-aero-checkout' ),
			];

			return $fields;
		}

		public function actions() {
			try {


				if ( class_exists( 'WooMailerLitePluginController' ) ) {
					add_action( 'wfacp_woocommerce_review_order_before_submit', [ WooMailerLitePluginController::instance(), 'addMlSubscribeCheckbox' ], PHP_INT_MAX );

					if ( ! function_exists( 'woo_ml_get_option' ) ) {
						return;
					}
					$checkout_position = woo_ml_get_option( 'checkout_position', 'checkout_billing' );

					/* default classes */
					add_filter( 'woocommerce_form_field_args', [ $this, 'add_default_wfacp_styling' ], 10, 2 );

					if ( in_array( $checkout_position, $this->position_show ) ) {
						$this->display_dragable_field = false;

						return;
					}

					$this->position = $checkout_position;
					remove_action( 'woocommerce_' . $checkout_position, 'woo_ml_checkout_label', 20 );
				}
			} catch( Exception|Error $e ) {

			}
		}

		public function call_fields_hook( $field, $key, $args ) {

			if ( ( ! empty( $key ) && ( 'woo_ml_subscribe_html' === $key ) ) ) {

				if ( ! in_array( $this->position, $this->position_show ) ) {
					$this->mailer_lite_html();
				}

			}
		}

		public function mailer_lite_html() {

			if ( false == $this->display_dragable_field ) {
				return;
			}
			if (function_exists('woo_ml_checkout_label')) {
				woo_ml_checkout_label();
			}

		}

		public function internal_css() {

			if ( ! function_exists( 'wfacp_template' ) ) {
				return;
			}


			$instance = wfacp_template();
			if ( ! $instance instanceof WFACP_Template_Common ) {
				return;
			}

			$bodyClass = "body ";
			if ( 'pre_built' !== $instance->get_template_type() ) {
				$bodyClass = "body #wfacp-e-form ";
			}

			echo "<style>";
			echo $bodyClass . ".wfacp_main_form.woocommerce #woo_ml_subscribe_field{text-align:left;}";
			echo $bodyClass . ".wfacp_main_form.woocommerce #billing_email_field .woocommerce-input-wrapper {position: relative;}";
			echo $bodyClass . ".wfacp_main_form.woocommerce #billing_email_field #woo_ml_subscribe { top: auto; left: 0; margin: 10px 0 0  !important;}";
			echo $bodyClass . ".wfacp_main_form.woocommerce #billing_email_field #woo_ml_subscribe + label{     padding-left: 26px !important;position: relative;display: inline-block !important;padding-top: 10px;}";
			echo "</style>";

		}

		public function add_default_wfacp_styling( $args, $key ) {

			if ( 'woo_ml_subscribe' !== $key ) {
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

		/**
		 * Detects and filters out extra fields based on removal criteria
		 *
		 * @param array $fields The fields to check
		 * @param array $others_fields Other fields for context
		 * @param mixed $temp Temporary data
		 *
		 * @return array Filtered fields array
		 */
		public function detect_extra_fields( $fields, $others_fields, $temp ) {
			try {
				// Check if we have fields to process
				if ( !is_array( $fields ) || count( $fields ) === 0 ) {
					return $fields;
				}

				// Check if we have fields to remove
				if ( !is_array( $this->remove_fields ) || count( $this->remove_fields ) === 0 ) {
					return $fields;
				}

				// Create a copy of the array to avoid modification during iteration
				$filtered_fields = $fields;

				foreach ( $fields as $key => $value ) {
					// Check if field has an ID and if it should be removed
					if ( isset( $value['id'] ) &&
					     in_array( $value['id'], $this->remove_fields ) &&
					     isset( $filtered_fields[ $value['id'] ] ) ) {
						unset( $filtered_fields[ $value['id'] ] );
					}
				}

				return $filtered_fields;

			} catch ( Exception $e ) {
				return $fields; // Return original fields on error
			}
		}
	}


	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_WC_MailerLite(), 'wfacp-woo-mailerlite' );
}
