<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sg Woocommerce Checkout Location Picker by Sevengits (v.1.1.12)
 * Plugin Path : https://sevengits.com/
 */
if ( ! class_exists( 'WFACP_Sg_Checkout_Location_Picker_For_WC' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Sg_Checkout_Location_Picker_For_WC {
		/**
		 * @var Sg_Checkout_Location_Picker
		 */

		private $instance = null;


		public function __construct() {


			/* Register Add field */

			add_action( 'init', [ $this, 'setup_fields_billing' ], 20 );
			add_action( 'init', [ $this, 'setup_fields_shipping' ], 20 );


			add_filter( 'wfacp_html_fields_billing_sg_checkout_location_picker', '__return_false' );
			add_filter( 'wfacp_html_fields_shipping_sg_checkout_location_picker', '__return_false' );

			/* Assign Object */
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'action' ] );

			/* Display Fields */
			add_action( 'process_wfacp_html', [ $this, 'display_field' ], 10, 3 );

			/* Internal css  */
			add_action( 'wfacp_internal_css', [ $this, 'internal_css' ] );

			/* opening Div of customer_details   */
			add_action( 'wfacp_before_form', [ $this, 'add_div' ], 99 );
			/* closing Div of customer_details   */
			add_action( 'wfacp_after_form', [ $this, 'close_div' ], 100 );

			/* prevent third party fields and wrapper*/

			add_action( 'wfacp_add_billing_shipping_wrapper', '__return_false' );

		}

		public function action() {
			if ( ! $this->is_enable() ) {
				return;
			}
			$this->instance = WFACP_Common::remove_actions( 'woocommerce_after_checkout_billing_form', 'Sgitsclp_Checkout_Location_Picker_Public', 'sgitsclp_billingmap_display' );

			WFACP_Common::remove_actions( 'woocommerce_after_checkout_shipping_form', 'Sgitsclp_Checkout_Location_Picker_Public', 'sgitsclp_shippingmap_display' );

		}

		public function is_enable() {


			if ( get_option( 'sg_enable_picker' ) == 'enable' && class_exists( 'Sgitsclp_Checkout_Location_Picker_Public' ) ) {
				return true;
			}

			return false;
		}

		public function setup_fields_billing() {
			if ( ! $this->is_enable() ) {
				return;
			}

			new WFACP_Add_Address_Field( 'sg_checkout_location_picker', array(
				'type'     => 'wfacp_html',
				'label'    => __( 'SG Checkout Location Picker', 'woofunnels-aero-checkout' ),
				'cssready' => [ 'wfacp-col-left-third' ],
				'class'    => array( 'form-row-third first', 'wfacp-col-full' ),
				'required' => false,
				'priority' => 60,
			) );


		}

		public function setup_fields_shipping() {
			if ( ! $this->is_enable() ) {
				return;
			}

			new WFACP_Add_Address_Field( 'sg_checkout_location_picker', array(
				'type'  => 'wfacp_html',
				'label' => __( 'SG Checkout Location Picker', 'woofunnels-aero-checkout' ),

				'cssready' => [ 'wfacp-col-left-third' ],
				'class'    => array( 'form-row-third first', 'wfacp-col-full' ),
				'required' => false,
				'priority' => 60,
			), 'shipping' );


		}

		public function display_field( $field, $key, $args ) {


			if ( ! $this->is_enable() || empty( $key ) || ! $this->instance instanceof Sgitsclp_Checkout_Location_Picker_Public || ! isset( $args['id'] ) ) {
				return '';
			}


			echo '<div class="' . implode( ' ', $args['class'] ) . '" id="' . $args['id'] . '">';
			if ( 'billing_sg_checkout_location_picker' === $key ) {
				$this->instance->sgitsclp_billingmap_display();
			} else if ( 'shipping_sg_checkout_location_picker' === $key ) {
				$this->instance->sgitsclp_shippingmap_display();
			}

			echo '</div>';


		}

		public function add_div() {

			echo '<div id="customer_details" class="pt-2">';
		}

		public function close_div() {

			echo '</div>';
		}

		public function internal_css() {

			if ( ! $this->is_enable() ) {
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


			$cssHtml = "<style>";
			$cssHtml .= $bodyClass . "#billing_sg_checkout_location_picker{width:100%;}";
			$cssHtml .= $bodyClass . "#billing_sg_checkout_location_picker input[type=text]{padding:12px 10px;}";
			$cssHtml .= $bodyClass . "#shipping_sg_checkout_location_picker{width:100%;}";
			$cssHtml .= $bodyClass . "#shipping_sg_checkout_location_picker input[type=text]{padding:12px 10px;}";

			$cssHtml .= "</style>";
			echo $cssHtml;
		}


	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Sg_Checkout_Location_Picker_For_WC(), 'wfacp-sg-checkout-location-picker-for-wc' );
}
