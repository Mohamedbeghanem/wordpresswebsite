<?php


if ( ! class_exists( 'WFACP_WooCommerce_MyParcel5' ) ) {
	class WFACP_WooCommerce_MyParcel5 {
		public function __construct() {
			add_filter( 'wfacp_advanced_fields', [ $this, 'add_field' ], 20 );
			add_action( 'wffn_rest_checkout_form_actions', [ $this, 'setup_fields' ] );
			add_action( 'wfacp_after_template_found', [ $this, 'setup_frontend' ] );

		}

		public function setup_frontend() {
			add_filter( 'woocommerce_form_field_args', [ $this, 'add_wrapper' ] );
			add_filter( 'mpwc_checkout_delivery_options_position', [ $this, 'replace_position_hook' ] );
			add_filter( 'wfacp_html_fields_wfacp_myparcel_delivery_options', '__return_false' );
			add_action( 'process_wfacp_html', [ $this, 'display_field' ], 999, 2 );
			add_action( 'wfacp_after_shipping_calculator_field', [ $this, 'print_delivery_options' ] );
		}

		public function print_delivery_options() {
			$fields = WC()->checkout()->get_checkout_fields();
			if ( ! ( ! empty( $fields ) && ( isset( $fields['wfacp_myparcel_delivery_options'] ) || isset( $fields['advanced']['wfacp_myparcel_delivery_options'] ) ) ) ) {
				$this->display_delivery_options();
			}
		}

		public function setup_fields() {

			if ( false == $this->is_enabled() ) {
				return;
			}
			try {
				$checkout_separated = MyParcelNL\Pdk\Facade\Settings::get( 'useSeparateAddressFields', 'checkout' );
				if ( ! wc_string_to_bool( $checkout_separated ) ) {
					return;
				}

				$this->separate_fields();
				$this->separate_fields( 'shipping' );
			} catch ( Exception|Error $error ) {

			}
		}

		private function separate_fields( $type = 'billing' ) {

			new WFACP_Add_Address_Field( 'street_name', array(
				'label'    => __( 'Street name', 'woocommerce-postnl' ),
				'cssready' => [ 'wfacp-col-left-third' ],
				'class'    => apply_filters( 'mpwc_checkout_field_street_class', array( 'form-row-third first', 'wfacp-col-full' ) ),
				'required' => false, // Only required for NL
				'priority' => 60,
			), $type );

			new WFACP_Add_Address_Field( 'house_number', array(
				'label'    => __( 'No.', 'woocommerce-postnl' ),
				'cssready' => [ 'wfacp-col-left-half' ],
				'class'    => apply_filters( 'mpwc_checkout_field_number_class', array( 'form-row-third', 'wfacp-col-left-half' ) ),
				'required' => false, // Only required for NL
				'type'     => 'number',
				'priority' => 61,
			), $type );

			new WFACP_Add_Address_Field( 'house_number_suffix', array(
				'label'     => __( 'Suffix', 'woocommerce-postnl' ),
				'cssready'  => [ 'wfacp-col-left-half' ],
				'class'     => apply_filters( 'mpwc_checkout_field_number_suffix_class', array( 'form-row-third last', 'wfacp-col-left-half' ) ),
				'required'  => false,
				'maxlength' => 4,
				'priority'  => 62,
			), $type );


		}


		public function add_wrapper( $args ) {
			if ( empty( $args ) ) {
				return $args;
			}
			if ( 'wfacp_divider_billing' === $args['id'] ) {
				$args['label_class'][] = 'woocommerce-billing-fields__field-wrapper';
			}
			if ( 'wfacp_divider_shipping' === $args['id'] ) {
				$args['label_class'][] = 'woocommerce-shipping-fields__field-wrapper';
			}


			return $args;
		}

		public function replace_position_hook( $position ) {
			return apply_filters( 'wfacp_myparcel_delivery_option_hook', 'wfacp_myparcel_delivery_options', $position, wfacp_template() );

		}

		public function is_enabled() {
			return class_exists( 'MyParcelNLWooCommerce' );
		}

		public function add_field( $fields ) {
			if ( false === $this->is_enabled() ) {
				return $fields;
			}
			$fields['wfacp_myparcel_delivery_options'] = [
				'type'       => 'wfacp_html',
				'class'      => [ 'wfacp-col-full', 'wfacp-form-control-wrapper', 'wfacp_myparcel_delivery_options' ],
				'id'         => 'wfacp_myparcel_delivery_options',
				'field_type' => 'wfacp_myparcel_delivery_options',
				'label'      => __( 'MyParcel Delivery Options', 'woofunnels-aero-checkout' ),
			];

			return $fields;
		}

		public function display_field( $field, $key ) {
			try {
				if ( $key === 'wfacp_myparcel_delivery_options' ) {
					$this->display_delivery_options();
				}
			} catch ( Exception|Error $error ) {

			}
		}

		public function display_delivery_options() {
			if ( did_action( 'wfacp_myparcel_delivery_options' ) > 0 ) {

				return;
			}
			echo "<div class='wfacp_myparcel_delivery_options'>";
			echo "<style>body #wfacp-e-form .wfacp_main_form.woocommerce input[name='deliveryMoment'] {
    position: relative;
    vertical-align: middle;
    margin-right: 5px !important;
}</style>";
			do_action( 'wfacp_myparcel_delivery_options' );
			echo "</div>";
		}

	}

	new WFACP_WooCommerce_MyParcel5();
}