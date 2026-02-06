<?php

/**
 * WPBisnis - WooCommerce Indo Ongkir
 *  https://www.wpbisnis.com/item/woocommerce-indo-ongkir
 * #[AllowDynamicProperties]
 * class WFACP_Compatibility_WPbisnis_ONGKIR
 */
if ( ! class_exists( 'WFACP_Compatibility_WPbisnis_ONGKIR' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Compatibility_WPbisnis_ONGKIR {

		private $billing_new_fields = [
			'billing_indo_ongkir_kota',
			'billing_indo_ongkir_kecamatan',
		];
		private $shipping_new_fields = [
			'shipping_indo_ongkir_kota',
			'shipping_indo_ongkir_kecamatan',
		];

		public function __construct() {
			if ( WFACP_Common::is_funnel_builder_3() ) {
				add_action( 'wffn_rest_checkout_form_actions', [ $this, 'setup_field' ] );
				add_action( 'wffn_rest_checkout_form_actions', [ $this, 'setup_fields_shipping' ] );
			} else {
				$this->setup_field();
			}
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'remove_action' ] );

			/* prevent third party fields and wrapper*/

			add_action( 'wfacp_add_billing_shipping_wrapper', '__return_false' );

			add_filter( 'wfacp_third_party_billing_fields', [ $this, 'disabled_third_party_billing_fields' ] );
			add_filter( 'wfacp_third_party_shipping_fields', [ $this, 'disabled_third_party_shipping_fields' ] );


		}

		public function setup_field() {


			if ( ! class_exists( 'WPBisnis_WC_Indo_Ongkir_Init' ) || ! class_exists( 'WFACP_Add_Address_Field' ) ) {
				return;
			}


			new WFACP_Add_Address_Field( 'indo_ongkir_kota', [
				'type'        => 'select',
				'options'     => array( '' => '' ),
				'label'       => esc_attr__( 'Kota / Kabupaten', 'wpbisnis-wc-indo-ongkir' ),
				'placeholder' => esc_attr__( 'Pilih Kota / Kabupaten...', 'wpbisnis-wc-indo-ongkir' ),
				'class'       => [ 'form-row-wide' ],
				'cssready'    => [ 'wfacp-col-full' ],
				'required'    => false,
			] );

			new WFACP_Add_Address_Field( 'indo_ongkir_kecamatan', [
				'type'        => 'select',
				'options'     => array( '' => '' ),
				'label'       => esc_attr__( 'Kecamatan', 'wpbisnis-wc-indo-ongkir' ),
				'placeholder' => esc_attr__( 'Pilih Kecamatan...', 'wpbisnis-wc-indo-ongkir' ),
				'class'       => [ 'form-row-wide' ],
				'cssready'    => [ 'wfacp-col-full' ],
				'required'    => false,
				'priority'    => 22,
			] );

			// For Shipping
			new WFACP_Add_Address_Field( 'indo_ongkir_kota', [
				'type'        => 'select',
				'options'     => array( '' => '' ),
				'label'       => esc_attr__( 'Kota / Kabupaten', 'wpbisnis-wc-indo-ongkir' ),
				'placeholder' => esc_attr__( 'Pilih Kota / Kabupaten...', 'wpbisnis-wc-indo-ongkir' ),
				'class'       => [ 'form-row-wide' ],
				'cssready'    => [ 'wfacp-col-full' ],
				'required'    => false,
			], 'shipping' );

			new WFACP_Add_Address_Field( 'indo_ongkir_kecamatan', [
				'type'        => 'select',
				'options'     => array( '' => '' ),
				'label'       => esc_attr__( 'Kecamatan', 'wpbisnis-wc-indo-ongkir' ),
				'placeholder' => esc_attr__( 'Pilih Kecamatan...', 'wpbisnis-wc-indo-ongkir' ),
				'class'       => [ 'form-row-wide' ],
				'cssready'    => [ 'wfacp-col-full' ],
				'required'    => false,
				'priority'    => 22,
			], 'shipping' );
		}

		public function remove_action() {
			$instance = WFACP_Common::remove_actions( 'init', 'WPBisnis_WC_Indo_Ongkir_Init', 'load_textdomain' );
			if ( $instance instanceof WPBisnis_WC_Indo_Ongkir_Init && method_exists( $instance, 'enqueue_scripts' ) ) {
				$instance->enqueue_scripts();
			}
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

		public function disabled_third_party_shipping_fields( $fields ) {
			if ( is_array( $this->shipping_new_fields ) && count( $this->shipping_new_fields ) ) {
				foreach ( $this->shipping_new_fields as $i => $key ) {
					if ( isset( $fields[ $key ] ) ) {
						unset( $fields[ $key ] );
					}
				}
			}

			return $fields;
		}
	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_WPbisnis_ONGKIR(), 'WPbisnis_ONGKIR' );
}