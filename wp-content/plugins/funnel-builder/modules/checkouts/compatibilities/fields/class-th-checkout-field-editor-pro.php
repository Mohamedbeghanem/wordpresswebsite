<?php

/**
 * Checkout Field Editor for WooCommerce (Pro) by theme high Version 3.6.1
 *
 */


if ( ! class_exists( 'WFACP_TH_Checkout_Field_Editor_pro_ThemeHigh' ) ) {
	#[AllowDynamicProperties]
	class WFACP_TH_Checkout_Field_Editor_pro_ThemeHigh {

		private $wc_checkout_fields = [];
		private $frontend_fields = [];
		private $register_checkout_fields = [];
		private $other_fields = [];
		private $th_checkout_fields = [];
		private $thewcfe_listed_fields = [];


		public function __construct() {


			add_action( 'wfacp_after_checkout_page_found', [ $this, 'action' ] );

			/*
			 * Register Billing and Shipping fields
			 */
			add_action( 'after_setup_theme', [ $this, 'setup_theme' ], 9999 );

			/*
			 * Register Advanced Fields
			 */
			add_filter( 'wfacp_advanced_fields', [ $this, 'register_advance_fields' ], 20 );


			/**
			 * Billing and Shipping Fields Values
			 */
			add_action( 'woocommerce_billing_fields', [ $this, 'billing_checkout_fields' ], 999999, 2 );
			add_action( 'woocommerce_shipping_fields', [ $this, 'shipping_checkout_fields' ], 999999, 2 );

			/**
			 * Display Fields
			 */
			add_filter( 'wfacp_forms_field', [ $this, 'check_fields' ], 50, 22, 2 );

			add_filter( 'woocommerce_form_field_args', [ $this, 'add_default_wfacp_styling' ], 99999, 2 );


			/**
			 * Internal Css For Checkout Field Editor
			 */
			add_action( 'wfacp_internal_css', [ $this, 'internal_css' ], 11 );

			/* prevent third party fields and wrapper*/

			add_action( 'wfacp_add_billing_shipping_wrapper', '__return_false' );

			add_filter( 'wfacp_get_checkout_fields', [ $this, 'check_insert_after_field_exist' ], 9999 );

			add_filter( 'wfacp_third_party_billing_fields', [ $this, 'disabled_third_party_billing_fields' ] );
			add_action( 'wfacp_update_posted_data_vice_versa_keys', [ $this, 'update_posted_data_vice_versa_keys' ] );
		}


		public function action() {
			if ( class_exists( 'THWCFE_Utils' ) ) {
				$this->th_checkout_fields = THWCFE_Utils::get_checkout_fields_full( true );
			}


			$thewcfe_instance        = WFACP_Common::remove_actions( 'woocommerce_checkout_fields', 'THWCFE_Public_Checkout', 'woo_checkout_fields' );
			$thewcfe_advance_fields  = $thewcfe_instance->woo_checkout_fields( (array) WC()->checkout() );
			$thewcfe_billing_fields  = $thewcfe_instance->woo_billing_fields( (array) WC()->checkout(), wc()->customer->get_billing_country() );
			$thewcfe_shipping_fields = $thewcfe_instance->woo_shipping_fields( (array) WC()->checkout(), wc()->customer->get_shipping_country() );

			$tmp = array_merge( $thewcfe_billing_fields, $thewcfe_shipping_fields );

			if ( isset( $thewcfe_advance_fields['order'] ) && is_array( $thewcfe_advance_fields['order'] ) && count( $thewcfe_advance_fields['order'] ) > 0 ) {
				$this->thewcfe_listed_fields = array_merge( $tmp, $thewcfe_advance_fields['order'] );
			}


		}


		/**
		 * @return void
		 * Setup Billing and Shipping Fields
		 */
		public function setup_theme() {
			if ( ! $this->is_enabled() || ! class_exists( 'WFACP_Add_Address_Field' ) ) {
				return;
			}
			$this->register_checkout_fields = WFACP_Common::get_aero_registered_checkout_fields();
			$this->get_th_custom_fields();
		}

		public function get_th_custom_fields() {


			$wcfe_fields = get_option( 'thwcfe_sections', [] );
			if ( ! is_array( $wcfe_fields ) || count( $wcfe_fields ) == 0 ) {
				return;
			}


			$this->wcfe_fields = $wcfe_fields;


			foreach ( $wcfe_fields as $key => $value ) {

				foreach ( $value->fields as $field_key => $field_value ) {
					$this->other_fields[ $field_key ] = $field_value;



					if ( in_array( $field_key, $this->register_checkout_fields ) ) {

						continue;
					}

					if(!$field_value instanceof WCFE_Checkout_Field){
						continue;
					}



					$this->wc_checkout_fields[ $key ][ $field_key ]                   = (array) $field_value->property_set;
					$this->wc_checkout_fields[ $key ][ $field_key ]['wfacp_th_field'] = true;


				}


			}



			if ( isset( $this->wc_checkout_fields['billing'] ) ) {
				$this->register_function( $this->wc_checkout_fields['billing'] );
			}
			if ( isset( $this->wc_checkout_fields['shipping'] ) ) {
				$this->register_function( $this->wc_checkout_fields['shipping'], 'shipping' );
			}


		}


		/**
		 * @return void
		 * Register Advanced Fields
		 */
		public function register_advance_fields( $fields ) {


			if ( ! $this->is_enabled() || empty( $this->wc_checkout_fields['additional'] ) ) {
				return $fields;
			}

			foreach ( $this->wc_checkout_fields['additional'] as $key => $field ) {

				if ( $field['type'] == 'label' ) {
					$field['type'] = 'textarea';

					$field['class'][] = 'wfacp-thwcfe-label-field';


					if ( isset( $field['label'] ) ) {
						$field['default'] = $field['label'];
						if ( ! isset( $field['placeholder'] ) ) {
							$field['placeholder'] = $field['label'];
						}
					}

				}
				$field['field_type']                = 'advanced';
				$field['class'][]                   = 'wfacp-col-full';
				$field['cssready'][]                = 'wfacp-col-full';
				$field['wfacp_th_advanced_field'][] = true;

				$fields[ $key ] = $field;
			}

			return $fields;

		}

		public function check_fields( $field, $key ) {

			if ( ! $this->is_enabled() ) {
				return $field;
			}


			$field_key = 'additional';
			if ( false !== strpos( $key, 'billing_' ) ) {
				$field_key = 'billing';
			} elseif ( false !== strpos( $key, 'shipping_' ) ) {
				$field_key = 'shipping';
			}

			if ( ! isset( $field['custom_btn_file_upload'] ) ) {


				$th_field                = [];
				$tmp_args                = [];
				$tmp_args['class']       = [];
				$tmp_args['cssready']    = [];
				$tmp_args['input_class'] = [];
				$tmp_args['label_class'] = [];
				$tmp_args['label']       = '';
				$tmp_args['placeholder'] = '';


				if ( isset( $field['class'] ) ) {

					$tmp_args['class'] = $field['class'];
				}
				if ( isset( $field['cssready'] ) ) {
					$tmp_args['cssready'] = $field['cssready'];
				}
				if ( isset( $field['input_class'] ) ) {
					$tmp_args['input_class'] = $field['input_class'];
				}
				if ( isset( $field['label_class'] ) ) {
					$tmp_args['label_class'] = $field['label_class'];
				}
				if ( isset( $field['label'] ) ) {
					$tmp_args['label'] = $field['label'];
					if ( ! isset( $field['placeholder'] ) ) {
						$tmp_args['placeholder'] = $field['label'];
					}
				}
				if ( isset( $field['field_type'] ) ) {
					$tmp_args['field_type'] = $field['field_type'];
				}


				if ( isset( $this->th_checkout_fields[ $key ] ) ) {
					$field = $this->th_checkout_fields[ $key ];


					/* For Class Attribute */
					if ( ! is_array( $field['class'] ) ) {
						$field['class'] = [];
					}

					if ( isset( $tmp_args['class'] ) && $field['class'] ) {
						$field['class'] = array_merge( $tmp_args['class'], $field['class'] );
					} else if ( isset( $field['class'] ) ) {
						$field['class'] = $tmp_args['class'];
					}

					/* For cssready Attribute */
					if ( isset( $field['cssready'] ) && ! is_array( $field['cssready'] ) ) {
						$field['cssready'] = [];
					}

					if ( isset( $tmp_args['cssready'] ) && isset( $field['cssready'] ) ) {
						$field['cssready'] = array_merge( $tmp_args['cssready'], $field['cssready'] );
					} else if ( isset( $field['cssready'] ) ) {
						$field['cssready'] = $tmp_args['cssready'];
					}

					/* For input_class Attribute */
					if ( isset( $field['input_class'] ) && ! is_array( $field['input_class'] ) ) {
						$field['input_class'] = [];
					}

					if ( isset( $tmp_args['input_class'] ) && isset( $field['input_class'] ) ) {
						$field['input_class'] = array_merge( $tmp_args['input_class'], $field['input_class'] );
					} else if ( isset( $field['input_class'] ) ) {
						$field['input_class'] = $tmp_args['input_class'];
					}

					/* For label_class Attribute */
					if ( isset( $field['label_class'] ) && ! is_array( $field['label_class'] ) ) {
						$field['label_class'] = [];
					}


					if ( isset( $tmp_args['label_class'] ) && isset( $field['label_class'] ) ) {
						$field['label_class'] = array_merge( $tmp_args['label_class'], $field['label_class'] );
					} else {
						$field['label_class'] = $tmp_args['label_class'];
					}

					/* overide label*/
					if ( isset( $field['label'] ) ) {
						$field['label'] = $tmp_args['label'];
					}
					if ( isset( $field['placeholder'] ) || ! isset( $field['placeholder'] ) ) {
						$field['placeholder'] = $tmp_args['label'];
					}


				}

				return $field;
			}


			if ( ! in_array( $key, $this->register_checkout_fields ) && (isset($this->wc_checkout_fields[ $field_key ])  && is_array( $this->wc_checkout_fields[ $field_key ] ) ) &&  ( isset($this->wc_checkout_fields[ $field_key ]) && ! array_key_exists( $key, $this->wc_checkout_fields[ $field_key ] )) ) {
				return [];
			}

			if ( is_array( $this->thewcfe_listed_fields ) && count( $this->thewcfe_listed_fields ) > 0 && ! array_key_exists( $key, $this->thewcfe_listed_fields ) ) {
				return [];
			};

			if ( $field['type'] == 'file' && isset( $field['label_class'] ) && is_array( $field['label_class'] ) ) {
				$unset_key = array_search( "wfacp-form-control-label", $field['label_class'] );
				if ( isset( $field['label_class'][ $unset_key ] ) ) {
					unset( $field['label_class'][ $unset_key ] );
				}

			}


			if ( $field['type'] == 'file' ) {

				$field['class'][] = 'wfacp_file_wrap';
			} else if ( $field['type'] == 'radio' ) {
				$field['class'][] = 'wfacp_radio_wrap';

				$unset_key = array_search( "wfacp-anim-wrap", $field['class'] );
				if ( isset( $field['class'][ $unset_key ] ) ) {
					unset( $field['class'][ $unset_key ] );
				}


			}

			return $field;
		}


		public function billing_checkout_fields( $fields, $country ) {
			if ( ! $this->is_enabled() ) {
				return $fields;
			}


			$this->frontend_fields['billing'] = $fields;

			return $fields;

		}

		public function shipping_checkout_fields( $fields, $country ) {
			if ( ! $this->is_enabled() ) {
				return $fields;
			}

			$this->frontend_fields['shipping'] = $fields;

			return $fields;

		}


		public function register_function( $fields, $type = 'billing' ) {

			if ( empty( $fields ) ) {
				return;
			}

			foreach ( $fields as $key => $field ) {
				if ( ! isset( $field['custom'] ) ) {
					continue;
				}


				$key                       = str_replace( "{$type}_", '', $key );
				$field['class'][]          = 'wfacp-col-full';
				$field['cssready'][]       = 'wfacp-col-full';
				$field['third_party']      = 'yes';
				$field['third_party_type'] = $type;
				$field['custom']           = "1";


				new WFACP_Add_Address_Field( $key, $field, $type );
			}


		}

		public function add_default_wfacp_styling( $args, $key ) {


			$type = '';
			if ( isset( $args['type'] ) ) {
				$type = $args['type'];
			}
			if ( $type == '' ) {
				return $args;
			}



			if ( $type == 'file' && ! in_array( 'wfacp_th_file_type', $args['class'] ) ) {
				$args['class'][] = 'wfacp_th_file_type';
			} else if ( $type == 'radio' && ! in_array( 'wfacp_th_radio_type', $args['class'] ) ) {
				$args['class'][] = 'wfacp_th_radio_type';
			} else if ( $type == 'checkboxgroup' && ! in_array( 'wfacp_th_checkoutbox_group', $args['class'] ) ) {
				$args['class'][] = 'wfacp_th_checkoutbox_group';
			} else if ( $type == 'datetime_local' && ! in_array( 'wfacp_th_datetime_local', $args['class'] ) ) {
				$args['class'][] = 'wfacp_th_datetime_local';
			} else if ( $type == 'date' && ! in_array( 'wfacp_th_date_local', $args['class'] ) ) {
				$args['class'][] = 'wfacp_th_date_local';
			} else if ( $type == 'timepicker' && ! in_array( 'wfacp_th_timepicker_local', $args['class'] ) ) {
				$args['class'][] = 'wfacp_th_timepicker_local';
			} else if ( $type == 'time' && ! in_array( 'wfacp_th_time_local', $args['class'] ) ) {
				$args['class'][] = 'wfacp_th_time_local';
			} else if ( $type == 'month' && ! in_array( 'wfacp_th_month_local', $args['class'] ) ) {
				$args['class'][] = 'wfacp_th_month_local';
			} else if ( $type == 'week' && ! in_array( 'wfacp_th_week_local', $args['class'] ) ) {
				$args['class'][] = 'wfacp_th_week_local';
			}


			return $args;

		}

		public function check_insert_after_field_exist( $fields ) {
			try {
				if ( is_array( $fields ) && count( $fields ) > 0 ) {
					foreach ( $fields as $key => $field ) {
						if ( $key != 'billing' && $key != 'shipping' ) {
							continue;
						}
						foreach ( $field as $k => $v ) {
							if ( isset( $v['third_party'] ) ) {
								$fields[ $key ][ $v['name'] ] = $v;
								unset( $fields[ $key ][ $k ] );
							}
						}
					}
				}

				return $fields;
			} catch ( Exception $e ) {
				// Log the error
				error_log( 'Error in check_insert_after_field_exist: ' . $e->getMessage() );

				// Return the original fields to prevent checkout from breaking
				return $fields;
			}
		}

		public function internal_css() {
			if ( ! $this->is_enabled() ) {
				return;
			}

			?>


            <style>
                #wfacp-sec-wrapper .wfacp-form-control-wrapper span.thwcfe-required-error {
                    display: none;
                }

                #wfacp-sec-wrapper .wfacp-form-control-wrapper span.thwcfe-email-error {
                    display: none;
                }

                #wfacp-sec-wrapper .wfacp-form-control-wrapper span.thwcfe-phone-error {
                    display: none;
                }

                #wfacp-e-form .wfacp_main_form .woocommerce-input-wrapper .wfacp-form-control.thwcfe-checkout-file {
                    border: 2px dashed #D9D9D9;
                    padding: 10px;
                    width: 100%;
                }


                body #wfacp-sec-wrapper input[type='url'],
                body #wfacp-sec-wrapper input[type='datetime-local'],
                body #wfacp-sec-wrapper input[type='time'],
                body #wfacp-sec-wrapper input[type='week'],
                body #wfacp-sec-wrapper input[type='month'] {
                    font-size: 14px;
                    line-height: 1;
                    width: 100%;
                    background-color: #fff;
                    border-radius: 4px;
                    position: relative;
                    color: #404040;
                    display: block;
                    min-height: 48px;
                    padding: 20px 12px 2px;
                    vertical-align: top;
                    box-shadow: none;
                    border: 1px solid #bfbfbf;
                    font-weight: 400;
                    height: auto;
                    margin-bottom: 0;
                    margin-top: 0;
                    max-width: 100%;
                }

                #wfacp-sec-wrapper .wfacp-form:not(.wfacp-top).wfacp-modern-label p.wfacp-form-control-wrapper input[type=url],
                #wfacp-sec-wrapper .wfacp-form:not(.wfacp-top).wfacp-modern-label p.wfacp-form-control-wrapper input[type=datetime-local],
                #wfacp-sec-wrapper .wfacp-form:not(.wfacp-top).wfacp-modern-label p.wfacp-form-control-wrapper input[type=time],
                #wfacp-sec-wrapper .wfacp-form:not(.wfacp-top).wfacp-modern-label p.wfacp-form-control-wrapper input[type=week],
                #wfacp-sec-wrapper .wfacp-form:not(.wfacp-top).wfacp-modern-label p.wfacp-form-control-wrapper input[type=month] {
                    padding: 12px 12px !important;
                    transition-delay: 0s, 0s;
                    transition-duration: .2s, 0s;
                    transition-property: all, width;
                    transition-timing-function: ease-out, ease;
                }

                #wfacp-sec-wrapper .wfacp-form:not(.wfacp-top).wfacp-modern-label p.wfacp-form-control-wrapper.wfacp-anim-wrap input[type=url],
                #wfacp-sec-wrapper .wfacp-form:not(.wfacp-top).wfacp-modern-label p.wfacp-form-control-wrapper.wfacp-anim-wrap input[type=datetime-local],
                #wfacp-sec-wrapper .wfacp-form:not(.wfacp-top).wfacp-modern-label p.wfacp-form-control-wrapper.wfacp-anim-wrap input[type=time],
                #wfacp-sec-wrapper .wfacp-form:not(.wfacp-top).wfacp-modern-label p.wfacp-form-control-wrapper.wfacp-anim-wrap input[type=week],
                #wfacp-sec-wrapper .wfacp-form:not(.wfacp-top).wfacp-modern-label p.wfacp-form-control-wrapper.wfacp-anim-wrap input[type=month] {
                    padding: 20px 12px 4px !important;
                }


                /* Date type and other type css */
                #wfacp-sec-wrapper .wfacp-form:not(.wfacp-top) p.wfacp-form-control-wrapper:not(.wfacp-anim-wrap) label.wfacp-form-control-label {
                    right: auto;
                    line-height: 1.6 !important;
                }

                body #wfacp-sec-wrapper .wfacp-form:not(.wfacp-top) p.wfacp-form-control-wrapper:not(.wfacp-anim-wrap) input[type=datetime-local],
                body #wfacp-sec-wrapper .wfacp-form:not(.wfacp-top) p.wfacp-form-control-wrapper:not(.wfacp-anim-wrap) input[type=time],
                body #wfacp-sec-wrapper .wfacp-form:not(.wfacp-top) p.wfacp-form-control-wrapper:not(.wfacp-anim-wrap) input[type=week],
                body #wfacp-sec-wrapper .wfacp-form:not(.wfacp-top) p.wfacp-form-control-wrapper:not(.wfacp-anim-wrap) input[type=month],
                body #wfacp-sec-wrapper .wfacp-form:not(.wfacp-top) p.wfacp-form-control-wrapper:not(.wfacp-anim-wrap) input[type=date] {
                    padding: 10px 12px !important;
                }

                /* Grouping Css For checkout field */


                body #wfacp-sec-wrapper .wfacp_main_form.woocommerce #wfacp_checkout_form .wfacp_radio_wrap > label,
                body #wfacp-sec-wrapper .wfacp_main_form.woocommerce #wfacp_checkout_form .wfacp_th_checkoutbox_group > label {
                    position: relative;
                    display: block;
                    left: auto;
                    right: auto;
                    bottom: auto;
                    margin: 0 0 12px;
                }

                body #wfacp-sec-wrapper .wfacp_main_form.woocommerce #wfacp_checkout_form .wfacp_radio_wrap .woocommerce-input-wrapper,
                body #wfacp-sec-wrapper .wfacp_main_form.woocommerce #wfacp_checkout_form .wfacp_th_checkoutbox_group .woocommerce-input-wrapper {
                    display: block;
                }

                body #wfacp-sec-wrapper .wfacp_main_form.woocommerce #wfacp_checkout_form .wfacp_radio_wrap input[type="radio"],
                body #wfacp-sec-wrapper .wfacp_main_form.woocommerce #wfacp_checkout_form .wfacp_th_checkoutbox_group input[type="checkbox"] {
                    position: relative;
                    left: auto;
                    right: auto;
                    top: auto;
                    bottom: auto;
                    margin-right: 8px;
                }

                body #wfacp-sec-wrapper .wfacp_main_form.woocommerce #wfacp_checkout_form .wfacp_radio_wrap input[type="radio"] + label,
                body #wfacp-sec-wrapper .wfacp_main_form.woocommerce #wfacp_checkout_form .wfacp_th_checkoutbox_group input[type="checkbox"] + label {
                    position: relative;
                    left: auto;
                    padding: 0;
                    margin: 0;
                    top: auto;
                    bottom: auto;
                    display: inline-block !important;
                    right: auto;
                    font-size: 14px;
                }

                body #wfacp-sec-wrapper .wfacp_main_form.woocommerce #wfacp_checkout_form .wfacp_th_checkoutbox_group label.checkbox {
                    left: auto;
                    padding: 0 !important;
                    right: auto;
                    display: inline-block !important;
                    width: auto;
                    top: auto;
                    bottom: auto;
                }


                body #wfacp-sec-wrapper .wfacp_main_form.woocommerce #wfacp_checkout_form .woocommerce-input-wrapper .description {
                    display: block;
                    margin-top: 8px;
                }

                body #wfacp-sec-wrapper .wfacp_main_form.woocommerce #wfacp_checkout_form .description:empty {
                    display: none;
                }

                body #wfacp-sec-wrapper .wfacp_main_form.woocommerce #wfacp_checkout_form .wfacp_radio_wrap label,
                body #wfacp-sec-wrapper .wfacp_main_form.woocommerce #wfacp_checkout_form .wfacp_th_checkoutbox_group label {
                    opacity: 1;
                    font-size: 14px !important;
                    line-height: 1 !important;
                    top: auto !important;
                }

                body #wfacp-sec-wrapper .wfacp_main_form.woocommerce #wfacp_checkout_form .wfacp_th_checkoutbox_group .woocommerce-input-wrapper label {
                    pointer-events: inherit;
                }

                .ui-widget.ui-widget-content {
                    z-index: 999 !important;
                }

                body #wfacp-sec-wrapper .wfacp_main_form.woocommerce #wfacp_checkout_form #wc_checkout_add_ons .wfacp-form-control-wrapper.wfacp_checkbox_field label.wfacp-form-control-label,
                body #wfacp-sec-wrapper .wfacp_main_form.woocommerce #wfacp_checkout_form #wc_checkout_add_ons .wfacp-form-control-wrapper.wc_checkout_add_ons_fileupload label.wfacp-form-control-label {
                    position: relative;
                    left: auto;
                    right: auto;
                    bottom: auto;
                    margin: 0;
                    padding-left: 0 !important;
                    display: inline-block !important;
                    top: auto;
                    pointer-events: auto;
                }

                body #wfacp-sec-wrapper .wfacp_main_form.woocommerce #wfacp_checkout_form #wc_checkout_add_ons .wfacp-form-control-wrapper.wfacp_checkbox_field label.wfacp-form-control-label input[type="checkbox"],
                body #wfacp-sec-wrapper .wfacp_main_form.woocommerce #wfacp_checkout_form #wc_checkout_add_ons .wfacp-form-control-wrapper.wc_checkout_add_ons_fileupload label.wfacp-form-control-label input[type="checkbox"] {
                    position: relative;
                    top: 0;
                    margin: 0 4px 0 0;
                    left: auto;
                    bottom: auto;
                    right: auto;
                }
                #wfacp-e-form .wfacp_th_file_type label.wfacp-form-control-label:not(.radio):not(.checkbox) {
                    opacity: 0;
                }
            </style>

            <script>
                (function ($) {
                    $(document).ready(function () {


                        wfacp_frontend.hooks.addFilter('wfacp_field_validated', function (validated, $this, $parent) {

                            if ($this.hasClass('thwcfe-checkout-file')) {
                                let file_val = $this.parents('.woocommerce-input-wrapper').find('input[type=hidden]').val();
                                let file_id = $this.parents('.woocommerce-input-wrapper').find('input[type=hidden]').attr("name");
                                if ('' === file_val) {
                                    $("#" + file_id + '_field').addClass('woocommerce-invalid woocommerce-invalid-required-field');
                                    $this.addClass('wfacp_error_border');
                                    validated = false;
                                } else {
                                    $this.removeClass('wfacp_error_border');
                                    $("#" + file_id + '_field').removeClass('woocommerce-invalid woocommerce-invalid-required-field');
                                    validated = true;
                                }

                            }

                            return validated;
                        });


                    });

                })(jQuery);

            </script>
			<?php

		}

		public function is_enabled() {

			if ( ! class_exists( 'WFACP_Core' ) ) {
				return false;
			}

			return true;
		}

		public function disabled_third_party_billing_fields( $fields ) {

			if (isset($this->wc_checkout_fields['billing'] ) && is_array( $this->wc_checkout_fields['billing'] ) && count( $this->wc_checkout_fields['billing'] ) ) {
				foreach ( $this->wc_checkout_fields['billing'] as $key => $v ) {


					if ( isset( $fields[ $key ] ) ) {
						unset( $fields[ $key ] );
					}
				}
			}

			return $fields;
		}

		public function update_posted_data_vice_versa_keys( $keys ) {
			$missingKeys = [];
			if ( is_array( $this->wc_checkout_fields ) && count( $this->wc_checkout_fields ) > 0 ) {
				foreach ( $this->wc_checkout_fields as $i => $default_checkout_field ) {
					if ( $i == 'billing' || $i == 'shipping' ) {
						foreach ( $default_checkout_field as $key => $field ) {
							if ( strpos( $key, $i ) !== false ) {
								$missingKeys[] = $key;
								continue;
							}
							$missingKeys[] = $i . "_" . $key;
						}
					}

				}
			}
			if ( is_array( $missingKeys ) && count( $missingKeys ) > 0 ) {
				foreach ( $missingKeys as $i => $v ) {
					$keys[ $v ] = $v;
				}
			}
			return $keys;
		}

	}

	WFACP_Plugin_Compatibilities::register( new WFACP_TH_Checkout_Field_Editor_pro_ThemeHigh(), 'wfacp-THWCFE' );
}
