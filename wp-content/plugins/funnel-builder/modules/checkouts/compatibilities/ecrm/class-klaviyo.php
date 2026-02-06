<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * plugin name Klaviyo by Klaviyo, Inc. (3.3.5)
 * Plugin URI: https://wordpress.org/plugins/klaviyo/
 */
if ( ! class_exists( 'WFACP_Compatibility_With_Klaviyo' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Compatibility_With_Klaviyo {

		private $billing_new_fields = [
			'kl_newsletter_checkbox',
			'billing_kl_newsletter_checkbox',
			'billing_kl_sms_consent_checkbox',
			'kl_sms_consent_checkbox',

		];

		private $sms_consent_text = false;

		private $klavio_settings = [];


		public function __construct() {

			if ( WFACP_Common::is_funnel_builder_3() ) {
				add_action( 'wffn_rest_checkout_form_actions', [ $this, 'add_field' ] );
			} else {
				add_action( 'init', [ $this, 'add_field' ], 20 );
			}


			add_filter( 'wfacp_advanced_fields', [ $this, 'add_fields' ] );
			add_action( 'wfacp_after_template_found', [ $this, 'setup' ] );
			add_filter( 'wfacp_checkout_data', [ $this, 'prepare_checkout_data' ], 10, 2 );


			/**
			 * Prevent to display registered field
			 */
			add_filter( 'wfacp_html_fields_billing_kl_newsletter_checkbox', '__return_false' );
			add_filter( 'wfacp_html_fields_billing_kl_sms_consent_checkbox', '__return_false' );

			/**
			 * Process the field to display klavio field
			 */

			add_action( 'process_wfacp_html', [ $this, 'display_field' ], 10, 3 );

			/* prevent third party fields and wrapper*/
			add_filter( 'wfacp_third_party_billing_fields', [ $this, 'disabled_third_party_billing_fields' ], 9999 );


			add_action( 'wfacp_after_checkout_page_found', [ $this, 'remove_other_action' ] );
			add_action( 'wfacp_after_kl_sms_consent_checkbox_field', [ $this, 'kl_sms_consent' ] );
		}

		public function add_field() {

			if ( ! version_compare( WooCommerceKlaviyo::getVersion(), '2.4.0', '>' ) ) {
				return;
			}


			$klaviyo_settings = get_option( 'klaviyo_settings' );
			new WFACP_Add_Address_Field( 'kl_newsletter_checkbox', array(
				'label'    => __( 'Klaviyo Newsletter', 'woocommerce-klaviyo' ),
				'type'     => 'wfacp_html',
				'cssready' => [ 'wfacp-col-left-half', 'kl_newsletter_checkbox_field' ],
				'required' => false,
			) );

			if ( isset( $klaviyo_settings['klaviyo_sms_subscribe_checkbox'] ) && $klaviyo_settings['klaviyo_sms_subscribe_checkbox'] && ! empty( $klaviyo_settings['klaviyo_sms_list_id'] ) ) {
				new WFACP_Add_Address_Field( 'kl_sms_consent_checkbox', array(
					'label'    => __( 'Klaviyo SMS Consent', 'woocommerce-klaviyo' ),
					'type'     => 'wfacp_html',
					'cssready' => [ 'wfacp-col-left-half', 'kl_sms_consent_checkbox_field' ],
					'required' => false,
				) );
			}

		}


		public function add_fields( $fields ) {


			$fields['kl_newsletter_checkbox'] = [
				'type'          => 'checkbox',
				'default'       => 0,
				'label'         => __( 'Klaviyo', 'woocommerce-klaviyo' ),
				'validate'      => [],
				'id'            => 'kl_newsletter_checkbox',
				'required'      => false,
				'wrapper_class' => [],
				'class'         => [ 'kl_newsletter_checkbox_field' ],
			];


			$settings = get_option( 'klaviyo_settings' );
			if ( ! ( isset( $settings['klaviyo_sms_subscribe_checkbox'] ) && wc_string_to_bool( $settings['klaviyo_sms_subscribe_checkbox'] ) ) ) {
				return $fields;
			}
			$fields['kl_sms_consent_checkbox'] = [
				'type'          => 'checkbox',
				'default'       => 0,
				'label'         => __( 'Subscribe to SMS updates', 'woocommerce-klaviyo' ),
				'validate'      => [],
				'id'            => 'kl_sms_consent_checkbox',
				'required'      => false,
				'wrapper_class' => [],
				'class'         => [ 'kl_sms_consent_checkbox_field' ],
			];


			return $fields;
		}

		public function setup() {
			add_filter( 'woocommerce_form_field_args', [ $this, 'add_default_wfacp_styling' ], 10, 2 );
			add_action( 'wfacp_internal_css', [ $this, 'js_event' ], 100 );
			$this->remove_actions();
		}

		public function remove_actions() {
			if ( ! function_exists( 'checkout_additional_checkboxes' ) ) {
				return;
			}
			$klaviyo_settings = get_option( 'klaviyo_settings' );

			if ( ! empty( $klaviyo_settings['klaviyo_newsletter_list_id'] ) ) {
				remove_action( 'woocommerce_checkout_before_terms_and_conditions', 'checkout_additional_checkboxes' );
			}

		}

		public function add_default_wfacp_styling( $args, $key ) {
			if ( ! in_array( $key, [ 'kl_newsletter_checkbox', 'kl_sms_consent_checkbox' ] ) ) {
				return $args;
			}

			$klaviyo_settings = get_option( 'klaviyo_settings' );
			if ( $key == 'kl_newsletter_checkbox' && ! empty( $klaviyo_settings['klaviyo_newsletter_text'] ) ) {
				$args['label'] = $klaviyo_settings['klaviyo_newsletter_text'];
			}

			if ( $key == 'kl_sms_consent_checkbox' ) {
				$args['label'] = ! empty( $klaviyo_settings['klaviyo_sms_consent_text'] ) ? $klaviyo_settings['klaviyo_sms_consent_text'] : $args['label'];
				if ( isset( $args['description'] ) ) {
					$args['description']='';
				}
			}


			return $args;
		}

		/**
		 * @param $checkout_data
		 * @param $cart WC_Cart;
		 *
		 * @return mixed
		 */
		public function prepare_checkout_data( $checkout_data, $cart ) {
			$items = $cart->get_cart_contents();
			if ( empty( $items ) ) {
				return $checkout_data;
			}
			$event_data = array(
				'$service' => 'woocommerce',
				'$value'   => $cart->total,
				'$extra'   => array(
					'Items'         => array(),
					'SubTotal'      => $cart->subtotal,
					'ShippingTotal' => $cart->shipping_total,
					'TaxTotal'      => $cart->tax_total,
					'GrandTotal'    => $cart->total,
				),
			);

			foreach ( $cart->get_cart() as $cart_item_key => $values ) {
				/**
				 * @var $product WC_Product;
				 */
				$product = $values['data'];

				$event_data['$extra']['Items'] [] = array(
					'Quantity'     => $values['quantity'],
					'ProductID'    => $product->get_id(),
					'Name'         => $product->get_title(),
					'URL'          => $product->get_permalink(),
					'Images'       => [
						[
							'URL' => wp_get_attachment_url( get_post_thumbnail_id( $product->get_id() ) ),
						],
					],
					'Categories'   => wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) ),
					'Description'  => $product->get_description(),
					'Variation'    => $values['variation'],
					'SubTotal'     => $values['line_subtotal'],
					'Total'        => $values['line_subtotal_tax'],
					'LineTotal'    => $values['line_total'],
					'Tax'          => $values['line_tax'],
					'TotalWithTax' => $values['line_total'] + $values['line_tax'],
				);
			}
			$checkout_data['klaviyo'] = $event_data;

			return $checkout_data;

		}

		public function display_field( $field, $key, $args ) {


			if ( empty( $key ) && in_array( $key, [ 'billing_kl_sms_consent_checkbox' ] ) ) {
				return '';
			}

			if ( ! is_array( $this->klavio_settings ) || count( $this->klavio_settings ) == 0 ) {
				return '';
			}

			$kl_fields = [];


			if ( $key == 'billing_kl_newsletter_checkbox' && function_exists( 'kl_checkbox_custom_checkout_field' ) ) {
				$tmpfield = kl_checkbox_custom_checkout_field( [] );

				if ( isset( $tmpfield['billing']['kl_newsletter_checkbox'] ) ) {
					$kl_fields['kl_newsletter_checkbox'] = $tmpfield['billing']['kl_newsletter_checkbox'];
				}


			} elseif ( $key == 'billing_kl_sms_consent_checkbox' && function_exists( 'kl_sms_consent_checkout_field' ) ) {
				$tmpfield = kl_sms_consent_checkout_field( [] );
				if ( isset( $tmpfield['billing']['kl_sms_consent_checkbox'] ) ) {
					$kl_fields['kl_sms_consent_checkbox'] = $tmpfield['billing']['kl_sms_consent_checkbox'];
				}
			}
			$template = wfacp_template();
			if ( ! $template instanceof WFACP_Template_Common ) {
				return $field;
			}

			$checkout = WC()->checkout();
			if ( count( $kl_fields ) > 0 ) {
				$kl_sms_consent_checkbox = false;
				foreach ( $kl_fields as $i => $kl_field ) {
					if ( ! isset( $template->already_printed_fields[ $i ] ) ) {

						if ( $i === 'kl_sms_consent_checkbox' ) {
							$kl_sms_consent_checkbox = true;
						}
						$field_value = $checkout->get_value( $i );
						wfacp_form_field( $i, $kl_field, $field_value );
						$template->already_printed_fields[ $i ] = 'yes';
					}


				}
				if ( $kl_sms_consent_checkbox ) {
					add_action( 'wfacp_divider_billing_end', [ $this, 'kl_sms_consent' ], 99999 );
				}
			}


		}

		public function remove_other_action() {

			$this->klavio_settings = get_option( 'klaviyo_settings' );

			remove_filter( 'woocommerce_after_checkout_billing_form', 'kl_sms_compliance_text' );
		}

		public function kl_sms_consent() {
			if ( ! function_exists( 'kl_sms_compliance_text' ) || ! is_array( $this->klavio_settings ) || count( $this->klavio_settings ) <= 0 ) {
				return;
			}
			if($this->sms_consent_text==true){
				return;
			}


			if ( isset( $this->klavio_settings['klaviyo_sms_subscribe_checkbox'] ) && ! empty( $this->klavio_settings['klaviyo_sms_subscribe_checkbox'] ) && isset( $this->klavio_settings['klaviyo_sms_list_id'] ) && ! empty( $this->klavio_settings['klaviyo_sms_list_id'] ) ) {
				$this->sms_consent_text=true;
				echo '<p class="kl_sms_compliance_text form-row wfacp-form-control-wrapper wfacp-col-full kl_sms_consent_checkbox_field ">';
				kl_sms_compliance_text();
				echo '</p>';
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


		public function js_event() {
			?>
            <style>
                #kl_sms_consent_checkbox-description {
                    display: block !important;
                }
            </style>
            <script>
                window.addEventListener('bwf_checkout_load', function () {
                    try {
                        (function ($) {

                            let _learnq = window.klaviyo || window._learnq;
                            if (typeof _learnq == "undefined") {
                                return;
                            }

                            $(document.body).on('change', '#billing_email', function () {
                                if (typeof wfacp_storage.klaviyo != 'undefined') {
                                    _learnq.push(["track", "$started_checkout", wfacp_storage.klaviyo])
                                }
                            });
                            $(document.body).on('wfacp_checkout_data', function (e, v) {
                                if (typeof v !== "object") {
                                    return;
                                }
                                if (!v.hasOwnProperty('checkout')) {
                                    return;
                                }

                                if (!v.checkout.hasOwnProperty('klaviyo')) {
                                    return;
                                }
                                wfacp_storage.klaviyo = v.checkout.klaviyo;
                                _learnq.push(["track", "$started_checkout", v.checkout.klaviyo])
                            });
                        })(jQuery);
                    } catch (e) {
                    }
                });
            </script>
			<?php

		}
	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_Klaviyo(), 'klaviyo' );
}