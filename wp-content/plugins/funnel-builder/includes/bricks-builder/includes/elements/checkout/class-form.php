<?php

namespace FunnelKit\Bricks\Elements\Checkout;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use FunnelKit\Bricks\Elements\Element;

use WFACP_Common;

if ( ! class_exists( '\FunnelKit\Bricks\Elements\Checkout\Form' ) ) {
	class Form extends Element {
		public $category = 'funnelkit';
		public $name = 'wfacp-form';
		public $icon = 'wfacp-icon-icon_checkout';

		private $group_fields = array();
		private $html_fields = array();
		public $progress_bar = array();
		public $section_fields = array();
		public $scripts = array( 'triggerJSHooksCheckout' );

		/**
		 * Retrieves the label for the Checkout Form element.
		 *
		 * @return string The label for the Checkout Form element.
		 */
		public function get_label() {
			return esc_html__( 'Checkout Form' );
		}

		/**
		 * Enqueues the necessary scripts for the checkout form.
		 */
		public function enqueue_scripts() {
			wp_enqueue_style( 'funnelkit-bricks-integration-wfacp' );
		}

		/**
		 * Sets the control groups for styling the various components.
		 *
		 * This method initializes the control groups array with predefined groups
		 * and uses the add_group method to add new groups for styling fields and sections.
		 * It also sets common control groups and removes the default typography group.
		 *
		 * @return void
		 */
		public function set_control_groups() {
			// Initialize control groups array
			$this->control_groups = array();

			$this->add_group( 'contentSteps', esc_html__( 'Steps' ) );
			$this->add_group( 'contentCollapsibleOrderSummary', esc_html__( 'Collapsible Order Summary' ) );

			$template = wfacp_template();

			if ( ! is_null( $template ) ) {
				$steps              = $template->get_fieldsets();
				$do_not_show_fields = WFACP_Common::get_html_excluded_field();
				$exclude_fields     = array();

				foreach ( $steps as $fieldsets ) {
					foreach ( $fieldsets as $section_data ) {
						if ( empty( $section_data['fields'] ) ) {
							continue;
						}

						$count            = count( $section_data['fields'] );
						$html_field_count = 0;

						if ( ! empty( $section_data['html_fields'] ) ) {
							foreach ( $do_not_show_fields as $h_key ) {
								if ( isset( $section_data['html_fields'][ $h_key ] ) ) {
									++ $html_field_count;
									$this->html_fields[ $h_key ] = true;
								}
							}
						}

						if ( $html_field_count === $count ) {
							continue;
						}

						if ( is_array( $section_data['fields'] ) && count( $section_data['fields'] ) > 0 ) {
							foreach ( $section_data['fields'] as $fval ) {
								if ( isset( $fval['id'] ) && in_array( $fval['id'], $do_not_show_fields, true ) ) {
									$exclude_fields[]                 = $fval['id'];
									$this->html_fields[ $fval['id'] ] = true;
									continue;
								}
							}
						}

						if ( count( $exclude_fields ) === count( $section_data['fields'] ) ) {
							continue;
						}

						$this->group_fields[ $section_data['name'] ] = $section_data['fields'];
						$this->add_group( $section_data['name'], $section_data['name'] );
					}
				}
			}

			$this->add_group( 'contentCoupon', esc_html__( 'Coupon' ) );
			$this->add_group( 'contentOrderSummary', esc_html__( 'Order Summary' ) );
			$this->add_group( 'contentPaymentGateways', esc_html__( 'Payment Gateways' ) );
			$this->add_group( 'contentCheckoutButtons', esc_html__( 'Checkout Button(s)' ) );

			// Predefined control groups for various styling sections
			$this->add_group( 'styleCheckoutForm', esc_html__( 'Checkout Form' ), self::TAB_STYLE );
			$this->add_group( 'styleHeader', esc_html__( 'Header' ), self::TAB_STYLE );
			$this->add_group( 'styleCollapsibleOrderSummary', esc_html__( 'Collapsible Order Summary' ), self::TAB_STYLE );
			$this->add_group( 'styleHeading', esc_html__( 'Heading' ), self::TAB_STYLE );

			// Add new control groups using the add_group method
			$this->add_group( 'styleFields', esc_html__( 'Fields' ), self::TAB_STYLE );
			$this->add_group( 'styleSection', esc_html__( 'Section' ), self::TAB_STYLE );
			$this->add_group( 'styleProductSwitcher', esc_html__( 'Product Switcher' ), self::TAB_STYLE );
			$this->add_group( 'styleOrderSummary', esc_html__( 'Order Summary' ), self::TAB_STYLE );
			$this->add_group( 'styleCoupon', esc_html__( 'Coupon' ), self::TAB_STYLE );
			$this->add_group( 'styleSectionPaymentMethods', esc_html__( 'Payment Methods' ), self::TAB_STYLE );
			$this->add_group( 'stylePrivacyPolicy', esc_html__( 'Privacy Policy' ), self::TAB_STYLE );
			$this->add_group( 'styleTermsConditions', __( 'Terms & Conditions' ), self::TAB_STYLE );
			$this->add_group( 'styleCheckoutButtons', esc_html__( 'Checkout Button(s)' ), self::TAB_STYLE );
			$this->add_group( 'stylefieldClasses', esc_html__( 'Field Classes' ), self::TAB_STYLE );

			// Set common control groups
			$this->set_common_control_groups();

			// Remove the default typography control group
			unset( $this->control_groups['_typography'] );
		}

		/**
		 * Sets the controls for the checkout form.
		 *
		 * This method registers the sections and styles for the checkout form.
		 *
		 * @return void
		 */
		public function set_controls() {
			$template = wfacp_template();
			if ( is_null( $template ) ) {
				return;
			}

			$this->controls['_alignSelf']['default'] = 'stretch';

			$this->register_sections();
			$this->register_styles();
		}

		/**
		 * Registers the sections for the checkout form.
		 *
		 * This method is responsible for registering the breadcrumb bar section for the checkout form.
		 */
		public function register_sections() {
			$this->breadcrumb_bar();
			$this->mobile_mini_cart();
			$this->register_section_fields();
			$this->coupon_fields();
			$this->order_summary_fields();
			$this->payment_method();
		}

		/**
		 * Registers the styles for the checkout form.
		 *
		 * This method registers the styles for the checkout form.
		 *
		 * @return void
		 */
		public function register_styles() {
			$this->global_typography();
			$this->get_progress_settings();
			$this->collapsible_order_summary();
			$this->get_heading_settings();
			$this->fields_typo_settings();
			$this->section_typo_settings();

			if ( is_array( $this->html_fields ) && ! isset( $this->html_fields['order_summary'] ) ) {
				$this->html_fields['order_summary'] = 1;
			}
			if ( is_array( $this->html_fields ) && ! isset( $this->html_fields['order_coupon'] ) ) {
				$this->html_fields['order_coupon'] = 1;
			}

			if ( is_array( $this->html_fields ) && ! isset( $this->html_fields['product_switching'] ) ) {
				unset( $this->control_groups['styleProductSwitcher'] );
			}

			foreach ( $this->html_fields as $key => $v ) {
				$this->generate_html_block( $key );
			}

			$this->payment_method_styling();
			$this->privacy_policy_styling();
			$this->terms_policy_styling();
			$this->payment_buttons_styling();

			$this->class_section();
		}

		/**
		 * Generates the HTML block for a specific field key.
		 *
		 * @param string $field_key The key of the field to generate the HTML block for.
		 *
		 * @return void
		 */
		protected function generate_html_block( $field_key ) {
			if ( method_exists( $this, $field_key ) ) {
				$this->{$field_key}( $field_key );
			}
		}

		/**
		 * Adds breadcrumb bar functionality to the checkout form.
		 *
		 * This method generates the breadcrumb bar for the checkout form based on the number of steps in the form.
		 * It also handles the display options for the breadcrumb bar, such as enabling/disabling the progress bar,
		 * selecting the type of display (tabs or breadcrumb), and customizing the labels for each step.
		 *
		 * @return void
		 */
		public function breadcrumb_bar() {
			$template     = wfacp_template();
			$num_of_steps = $template->get_step_count();

			if ( $num_of_steps >= 1 ) {
				$stepsCounter = 1;

				$tab_name              = __( 'Steps' );
				$enable_condition_name = __( 'Enable Steps' );

				$options = array(
					'tab'       => __( 'Tabs' ),
					'bredcrumb' => __( 'Breadcrumb' ),
				);

				if ( $num_of_steps === 1 ) {
					$tab_name              = __( 'Form Header' );
					$enable_condition_name = __( 'Enable' );
					unset( $options['bredcrumb'] );
				}

				$this->set_current_group( 'contentSteps' );
				$this->add_group( 'contentSteps', $tab_name );
				$this->add_switcher( 'enable_progress_bar', $enable_condition_name, false, array(
						array(
							'selector' => '.wfacp_form_steps',
							'property' => 'display',
							'value'    => 'block',
						),
					) );

				$enable_options = array(
					'enable_progress_bar',
					'=',
					true,
				);

				$this->add_select( 'select_type', esc_html__( 'Select Type' ), $options, 'tab', $enable_options );

				$bredcrumb_controls = array(
					array( 'select_type', '=', 'bredcrumb' ),
					array( 'enable_progress_bar', '=', true ),
				);

				$progress_controls = array(
					array( 'select_type', '=', 'progress_bar' ),
					array( 'enable_progress_bar', '=', true ),
				);

				$labels = array(

					[
						'heading'     => __( 'Shipping', 'woocommerce' ),
						'sub-heading' => __( 'Where to ship it?', 'funnel-builder' ),
					],
					[
						'heading'     => __( 'Products', 'funnel-builder' ),
						'sub-heading' => __( 'Select your product', 'funnel-builder' ),
					],
					[
						'heading'     => __( 'Payment', 'woocommerce' ),
						'sub-heading' => __( 'Confirm your order', 'funnel-builder' ),
					],

				);

				for ( $bi = 0; $bi < $num_of_steps; $bi ++ ) {
					$heading    = $labels[ $bi ]['heading'];
					$subheading = $labels[ $bi ]['sub-heading'];

					if ( $num_of_steps > 1 ) {
						/* translators: %s: Steps counter */
						$this->add_heading( sprintf( __( 'Step %s', 'funnel-builder-bricks-integration' ), $stepsCounter ), array( 'enable_progress_bar', '=', true ) );
					}
					/* translators: %s: Steps counter */
					$this->add_text( 'step_' . $bi . '_bredcrumb', __( 'Title', 'funnel-builder-bricks-integration' ), sprintf( __( 'Step %s', 'funnel-builder-bricks-integration' ), $stepsCounter ), $bredcrumb_controls );
					/* translators: %s: Steps counter */
					$this->add_text( 'step_' . $bi . '_progress_bar', __( 'Heading', 'funnel-builder-bricks-integration' ), sprintf( __( 'Step %s', 'funnel-builder-bricks-integration' ), $stepsCounter ), $progress_controls );

					$this->add_text( 'step_' . $bi . '_heading', __( 'Heading' ), $heading, array(
							array( 'select_type', '=', 'tab' ),
							array( 'enable_progress_bar', '=', true ),
						) );

					$this->add_text( 'step_' . $bi . '_subheading', __( 'Sub Heading' ), $subheading, array(
							array( 'select_type', '=', 'tab' ),
							array( 'enable_progress_bar', '=', true ),
						) );

					++ $stepsCounter;
				}

				if ( $num_of_steps > 1 ) {
					$condtion_control = array(
						array(
							'select_type',
							'=',
							array(
								'bredcrumb',
								'progress_bar',
							),
						),
						array( 'enable_progress_bar', '=', true ),
					);

					$cart_title          = __( 'Title' );
					$progress_cart_title = __( 'Cart title' );
					$setting_description = __( 'Note: Cart settings will work for Global Checkout when user navigates from Product > Cart > Checkout' );
					$cart_text           = __( 'Cart' );

					$options = array(
						'yes' => __( 'Yes' ),
						'no'  => __( 'No' ),

					);

					$this->add_heading( 'Cart', $bredcrumb_controls );

					$this->add_select( 'step_cart_link_enable', __( 'Add to Breadcrumb' ), $options, 'yes', $condtion_control );
					$this->add_text( 'step_cart_progress_bar_link', $progress_cart_title, $cart_text, $progress_controls );
					$this->add_text( 'step_cart_bredcrumb_link', $cart_title, $cart_text, $bredcrumb_controls, '', $setting_description );
				}
			}
		}

		/**
		 * Adds a mobile mini cart to the form.
		 *
		 * This method sets the current group to 'contentCollapsibleOrderSummary' and adds various elements to the mini cart.
		 * The elements include switchers for enabling the collapse order summary and product image, text inputs for the collapsed and expanded view texts,
		 * a text input for the coupon button text, switchers for enabling the coupon and collapsible coupon field, and switchers for the quantity switcher and item deletion.
		 *
		 * @return void
		 */
		public function mobile_mini_cart() {
			$this->set_current_group( 'contentCollapsibleOrderSummary' );
			$this->add_switcher( 'enable_callapse_order_summary', __( 'Enable' ), false, array(
					array(
						'selector' => '.wfacp_order_summary_container',
						'property' => 'display',
						'value'    => 'block',
					),
				) );

			$this->add_switcher( 'enable_order_field_collapsed', __( 'Expanded Order Summary' ), false, array(
				array(
					'selector' => '.wfacp_order_summary_container',
					'property' => 'display',
					'value'    => 'block',
				),
			) );
			$this->add_switcher( 'order_summary_enable_product_image_collapsed', __( 'Enable Image' ), true );

			$this->add_text( 'cart_collapse_title', __( 'Collapsed View Text' ), __( 'Show Order Summary' ) );
			$this->add_text( 'cart_expanded_title', __( 'Expanded View Text' ), __( 'Hide Order Summary' ) );
			$this->add_text( 'collapse_coupon_button_text', __( 'Coupon Button Text' ), __( 'Apply', 'woocommerce' )  );

			$this->add_switcher( 'collapse_enable_coupon', __( 'Enable Coupon' ), false );
			$this->add_switcher( 'collapse_enable_coupon_collapsible', __( 'Collapsible Coupon Field' ), false );

			$this->add_switcher( 'collapse_order_quantity_switcher', __( 'Quantity Switcher' ), true );
			$this->add_switcher( 'collapse_order_delete_item', __( 'Allow Deletion' ), true );




            if(true === wfacp_pro_dependency()){

                /**
                 * Strike Through for order summary
                 */
                $this->add_switcher( 'collapsible_mini_cart_enable_strike_through_price', __( 'Regular & Discounted Price' ), false );
                $this->add_switcher( 'collapsible_mini_cart_enable_low_stock_trigger', __( 'Low Stock Trigger' ), false );
                $this->add_text( 'collapsible_mini_cart_low_stock_message', __( 'Message' ), __( '{{quantity}} LEFT IN STOCK', 'woofunnels-aero-checkout' ), array(
                    'collapsible_mini_cart_enable_low_stock_trigger',
                    '=',
                    true
                ) );

                $this->add_switcher( 'collapsible_mini_cart_enable_saving_price_message', __( 'Enable Total Saving', 'woofunnels-aero-checkout' ), false );
                $this->add_text( 'collapsible_mini_cart_saving_price_message', __( 'Message' ), __( 'You saved {{saving_amount}} ({{saving_percentage}}) on this order', 'woofunnels-aero-checkout' ), array(
                    'collapsible_mini_cart_enable_saving_price_message',
                    '=',
                    true
                ) );

            }

		}

		/**
		 * Registers section fields for the checkout form.
		 *
		 * This method iterates through the fieldsets and sections of the checkout form template
		 * and registers the fields for each section, excluding any fields that are marked as excluded.
		 *
		 * @return void
		 */
		public function register_section_fields() {
			foreach ( $this->group_fields as $group_key => $group_fields ) {
				$this->set_current_group( $group_key );
				$this->register_fields( $group_fields );
			}
		}

		/**
		 * Registers fields for the checkout form.
		 *
		 * This method adds fields to the section_fields array based on the provided $temp_fields.
		 * It also adds headings for billing and shipping address dividers.
		 * It skips fields with missing 'id' or 'label' keys.
		 * It determines the field's default class based on the template_cls and default_cls arrays.
		 * It skips fields listed in the do_not_show_fields array.
		 * It skips fields with keys 'billing_same_as_shipping' and 'shipping_same_as_billing'.
		 * It sets options for the field based on the field type.
		 * It applies filters to the options array.
		 * It adds the select field to the section_fields array.
		 *
		 * @param array $temp_fields The fields to be registered.
		 *
		 * @return void
		 */
		public function register_fields( $temp_fields ) {
			$template      = wfacp_template();
			$template_slug = $template->get_template_slug();
			$template_cls  = $template->get_template_fields_class();

			$default_cls        = $template->default_css_class();
			$do_not_show_fields = WFACP_Common::get_html_excluded_field();

			$this->section_fields[] = $temp_fields;
			foreach ( $temp_fields as $loop_key => $field ) {
				if ( in_array( $loop_key, [ 'wfacp_start_divider_billing', 'wfacp_start_divider_shipping' ], true ) ) {
					$address_key_group = ( $loop_key === 'wfacp_start_divider_billing' ) ? __( 'Billing Address' ,'woocommerce') : __( 'Shipping Address' ,'woocommerce');
					$this->add_heading( $address_key_group );
				}

				if ( ! isset( $field['id'] ) || ! isset( $field['label'] ) ) {
					continue;
				}

				$field_key = $field['id'];

				if ( isset( $template_cls[ $field_key ] ) ) {
					$field_default_cls = $template_cls[ $field_key ]['class'];
				} else {
					$field_default_cls = $default_cls['class'];
				}

				if ( in_array( $field_key, $do_not_show_fields, true ) ) {
					$this->html_fields[ $field_key ] = true;
					continue;
				}

				$skipKey = array( 'billing_same_as_shipping', 'shipping_same_as_billing' );
				if ( in_array( $field_key, $skipKey, true ) ) {
					continue;
				}

				$options = $this->get_class_options();
				if ( isset( $field['type'] ) && 'wfacp_html' === $field['type'] ) {
					$options           = array( 'wfacp-col-full' => __( 'Full' ) );
					$field_default_cls = 'wfacp-col-full';
				}

				$options = apply_filters( 'wfacp_widget_fields_classes', $options, $field, $this->get_class_options() );

				$this->add_select( 'wfacp_' . $template_slug . '_' . $field_key . '_field', $field['label'], $options, $field_default_cls );
			}
		}

		/**
		 * Retrieves the class options for the checkout form.
		 *
		 * @return array An array of class options for the checkout form.
		 */
		protected function get_class_options() {
			return array(
				'wfacp-col-full'       => __( 'Full' ),
				'wfacp-col-left-half'  => __( 'One Half' ),
				'wfacp-col-left-third' => __( 'One Third' ),
				'wfacp-col-two-third'  => __( 'Two Third' ),
			);
		}

		/**
		 * Adds coupon fields to the form.
		 */
		public function coupon_fields() {
			$this->set_current_group( 'contentCoupon' );
			$this->add_text( 'form_coupon_button_text', __( 'Coupon Button Text' ), __( 'Apply', 'woocommerce' ) );
		}

		/**
		 * Sets the order summary fields for the checkout form.
		 *
		 * This method sets the current group to 'contentOrderSummary' and adds a switcher for enabling/disabling the product image in the order summary.
		 *
		 * @return void
		 */
		public function order_summary_fields() {
			$this->set_current_group( 'contentOrderSummary' );
			$this->add_switcher( 'order_summary_enable_product_image', __( 'Enable Image' ), true );


			if(true === wfacp_pro_dependency()){


			/**
			 * Strike Through for order summary
			 */
			$this->add_switcher( 'order_summary_field_enable_strike_through_price', __( 'Regular & Discounted Price' ), true );
			$this->add_switcher( 'order_summary_field_enable_low_stock_trigger', __( 'Low Stock Trigger' ), true );
			$this->add_text( 'order_summary_field_low_stock_message', __( 'Message' ), __( '{{quantity}} LEFT IN STOCK', 'woofunnels-aero-checkout' ), array(
				'order_summary_field_enable_low_stock_trigger',
				'=',
				true
			) );

			$this->add_switcher( 'order_summary_field_enable_saving_price_message', __( 'Enable Total Saving', 'woofunnels-aero-checkout' ), true );
			$this->add_text( 'order_summary_field_saving_price_message', __( 'Message' ), __( 'You saved {{saving_amount}} ({{saving_percentage}}) on this order', 'woofunnels-aero-checkout' ), array(
				'order_summary_field_enable_saving_price_message',
				'=',
				true
			) );

			}
		}

		/**
		 * Sets up the payment method section of the checkout form.
		 *
		 * This method is responsible for adding the necessary elements and content for the payment method section of the checkout form.
		 * It sets the current group to 'contentPaymentGateways', adds a heading, a heading text, and a subheading textarea.
		 * The subheading textarea contains the default text for the payment information subheading.
		 *
		 * @return void
		 */
		public function payment_method() {
			$this->set_current_group( 'contentPaymentGateways' );
			$this->add_heading( __( 'Section' ) );

			$payment_default = method_exists('WFACP_Common', 'translation_string_to_check')
				? WFACP_Common::translation_string_to_check(__('Payment Information', 'woofunnels-aero-checkout'))
				: __('Payment Information', 'woofunnels-aero-checkout');

			$security_text = esc_attr__('All transactions are secure and encrypted. Credit card information is never stored on our servers.', 'woofunnels-aero-checkout');

			if (class_exists('WFACP_Common') && method_exists('WFACP_Common', 'translation_string_to_check')) {
				$security_text = WFACP_Common::translation_string_to_check($security_text);
			}

			$this->add_text( 'wfacp_payment_method_heading_text', __( 'Heading' ),$payment_default );
			$this->add_textarea( 'wfacp_payment_method_subheading', __( 'Sub heading' ), $security_text);

			$this->set_current_group( 'contentCheckoutButtons' );
			$this->form_buttons();
		}

		/**
		 * Generates and configures the form buttons for each step of the checkout process.
		 *
		 * This method creates a series of buttons for each step in the checkout process,
		 * including a "Next Step" button for intermediate steps and a "Place Order Now" button
		 * for the final step. It also optionally includes a "Return to Step" link for
		 * navigating back to previous steps and a customizable text below the place order button.
		 *
		 * @return void
		 */
		public function form_buttons() {
			$template = wfacp_template();
			$count    = $template->get_step_count();

			$backLinkArr = array();

			for ( $i = 1; $i <= $count; $i ++ ) {
				$button_default_text = __( 'NEXT STEP →' );
				$button_key          = 'wfacp_payment_button_' . $i . '_text';
				$button_label        = "Step {$i}";
				$text_key            = $i;
				if ( $i === $count ) {
					$button_key          = 'wfacp_payment_place_order_text';
					$text_key            = 'place_order';
					$button_default_text = __( 'Place Order', 'woocommerce' );
					$button_label        = __( 'Place Order', 'woocommerce' );
				}

				$this->add_heading( $button_label );
				$this->add_text( $button_key, __( 'Button Text' ), esc_js( $button_default_text ), array() );
				$this->icon_text( $text_key );

				if ( $i === $count ) {
					$this->add_switcher( 'enable_price_in_place_order_button', __( 'Enable Price' ), false );
				}

				if ( $i > 1 ) {
					$backCount = $i - 1;

					$backLinkArr[ 'payment_button_back_' . $i . '_text' ] = array(
						'label'   => __( 'Return to Step ', 'funnel-builder-bricks-integration' ) . $backCount,
						'default' => sprintf( '« Return to Step %s ', $i - 1 ),
					);
				}
			}

			$this->add_divider();
			if ( is_array( $backLinkArr ) && count( $backLinkArr ) > 0 ) {
				$this->add_heading( __( 'Return Link Text' ) );
				$cart_name = __( '« Return to Cart' );
				$this->add_text( 'return_to_cart_text', 'Return to Cart', $cart_name, array( 'step_cart_link_enable', '=', true ) );
				foreach ( $backLinkArr as $i => $val ) {
					$this->add_text( $i, $val['label'], $val['default'], array() );
				}
			}

			$this->add_text( 'text_below_placeorder_btn', __( 'Text Below Place Order Button' ), sprintf( 'We Respect Your Privacy & Information ' ), array() );
		}

		/**
		 * Adds the global typography styles for the checkout form.
		 *
		 * This method adds the global typography styles for the checkout form.
		 *
		 * @return void
		 */
		public function global_typography() {
			$this->set_current_group( 'styleCheckoutForm' );

			$global_setting_options = array(
				'body.wfacp_main_wrapper',
				'body #wfacp-e-form *:not(i)',
				'body .wfacp_qv-main *',
				'#wfacp-e-form .wfacp_section_heading.wfacp_section_title',
				'#wfacp-e-form .wfacp_main_form .wfacp_whats_included h3',
				'#wfacp-e-form .wfacp_main_form .wfacp_whats_included .wfacp_product_switcher_description .wfacp_description a',
				'#wfacp-e-form .wfacp_main_form .wfacp_whats_included .wfacp_product_switcher_description .wfacp_description',
				'#wfacp-e-form .wfacp_main_form .wfacp-section h4',
				'#wfacp-e-form .wfacp_main_form p.wfacp-form-control-wrapper label.wfacp-form-control-label',
				'#wfacp-e-form .wfacp_main_form input[type="text"]',
				'#wfacp-e-form .wfacp_main_form input[type="email"]',
				'#wfacp-e-form .wfacp_main_form input[type="tel"]',
				'#wfacp-e-form .wfacp_main_form input[type="number"]',
				'#wfacp-e-form .wfacp_main_form input[type="date"]',
				'#wfacp-e-form .wfacp_main_form select',
				'#wfacp-e-form .wfacp_main_form textarea',
				'#wfacp-e-form .wfacp_main_form p',
				'#wfacp-e-form .wfacp_main_form a',
				'#wfacp-e-form .wfacp_main_form label span a',
				'#wfacp-e-form .wfacp_main_form a',
				'#wfacp-e-form .wfacp_main_form button',
				'#wfacp-e-form #payment button#place_order',
				'#wfacp-e-form .wfacp_main_form.woocommerce  button#place_order',
				'#wfacp-e-form .wfacp_main_form span',
				'#wfacp-e-form .wfacp_main_form label',
				'#wfacp-e-form .wfacp_main_form ul li',
				'#wfacp-e-form .wfacp_main_form ul li span',
				'#wfacp-e-form .woocommerce-form-login-toggle .woocommerce-info ',
				'#wfacp-e-form .wfacp_main_form ul li span',
				'#wfacp-e-form .wfacp_main_form .wfacp-payment-dec',
				'#wfacp-e-form .wfacp_main_form label.checkbox',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-title > div',
				'#wfacp-e-form .wfacp_main_form .wfacp_shipping_table ul li label',
				'#wfacp-e-form .wfacp_main_form .select2-container .select2-selection--single .select2-selection__rendered',
				'#et-boc .et-l span.select2-selection.select2-selection--multiple',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset.wfacp-selected-product .wfacp_product_sec *',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset.wfacp-selected-product .wfacp_quantity_selector input',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset.wfacp-selected-product .wfacp_product_price_sec span',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset .wfacp_product_sec *',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset .wfacp_quantity_selector input',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset .wfacp_product_price_sec span',
				'#wfacp-e-form .wfacp_main_form #product_switching_field fieldset .wfacp_best_value',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel .wfacp_product_switcher_col_2 .wfacp_you_save_text',
				'#wfacp-e-form .wfacp_main_form .wfacp_whats_included .wfacp_product_switcher_description h4',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset .wfacp_product_sec .wfacp_product_select_options .wfacp_qv-button',
				'#wfacp-e-form .wfacp_main_form #product_switching_field .wfacp_product_switcher_col_2 .wfacp_product_subs_details > span:not(.subscription-details):not(.woocommerce-Price-amount):not(.woocommerce-Price-currencySymbol)',
				'#wfacp-e-form .wfacp_main_form .wfacp-coupon-section .wfacp-coupon-page .woocommerce-info > span',
				'#wfacp-e-form .wfacp_main_form .wfacp_woocommerce_form_coupon .wfacp-coupon-section .woocommerce-info .wfacp_showcoupon',
				'#wfacp-e-form .wfacp_main_form label.woocommerce-form__label span',
				'#wfacp-e-form .wfacp_main_form table tfoot tr th',
				'#wfacp-e-form .wfacp_main_form table tfoot .shipping_total_fee td',
				'#wfacp-e-form .wfacp_main_form table tfoot tr td',
				'#wfacp-e-form .wfacp_main_form table tfoot tr td span.woocommerce-Price-amount.amount',
				'#wfacp-e-form .wfacp_main_form table tfoot tr td span.woocommerce-Price-amount.amount bdi',
				'#wfacp-e-form .wfacp_main_form table tfoot tr td p',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset .wfacp_best_value',
				'#wfacp-e-form .wfacp_main_form table tbody .wfacp_order_summary_item_name',
				'#wfacp-e-form .wfacp_main_form table tfoot tr:not(.order-total) td small',
				'#wfacp-e-form .wfacp_main_form table tfoot tr:not(.order-total) th small',
				'#wfacp-e-form .wfacp_main_form.woocommerce table tfoot tr.order-total td small',
				'#wfacp-e-form .wfacp_main_form table.shop_table tbody .wfacp_order_summary_item_name',
				'#wfacp-e-form .wfacp_main_form table.shop_table tbody .product-name .product-quantity',
				'#wfacp-e-form .wfacp_main_form table.shop_table tbody td.product-total',
				'#wfacp-e-form .wfacp_main_form table.shop_table tbody .wfacp_order_summary_container dl',
				'#wfacp-e-form .wfacp_main_form table.shop_table tbody .wfacp_order_summary_container dd',
				'#wfacp-e-form .wfacp_main_form table.shop_table tbody .wfacp_order_summary_container dt',
				'#wfacp-e-form .wfacp_main_form table.shop_table tbody .wfacp_order_summary_container p',
				'#wfacp-e-form .wfacp_main_form table.shop_table tbody tr span.amount',
				'#wfacp-e-form .wfacp_main_form table.shop_table tbody tr span.amount bdi',
				'#wfacp-e-form .wfacp_main_form table.shop_table tbody .wfacp_order_summary_item_name',
				'#wfacp-e-form .wfacp_main_form table.shop_table tbody .cart_item .product-total span',
				'#wfacp-e-form .wfacp_main_form table.shop_table tbody .cart_item .product-total small',
				'#wfacp-e-form .wfacp_main_form table.shop_table tbody .cart_item .product-total span.amount',
				'#wfacp-e-form .wfacp_main_form .shop_table tbody .cart_item .product-total .amount bdi',
				'#wfacp-e-form .wfacp_main_form table.shop_table tbody .product-name .product-quantity',
				'#wfacp-e-form .wfacp_main_form table.shop_table tbody td.product-total',
				'#wfacp-e-form .wfacp_main_form table tbody dl',
				'#wfacp-e-form .wfacp_main_form table tbody dd',
				'#wfacp-e-form .wfacp_main_form table tbody dt',
				'#wfacp-e-form .wfacp_main_form table tbody p',
				'#wfacp-e-form .wfacp_main_form table.shop_table tbody tr span.amount',
				'#wfacp-e-form .wfacp_main_form table.shop_table tbody tr span.amount bdi',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset .wfacp_product_sec .wfacp_product_select_options .wfacp_qv-button',
				'#wfacp-e-form .wfacp_main_form #product_switching_field .wfacp_product_switcher_col_2 .wfacp_product_subs_details > span:not(.subscription-details):not(.woocommerce-Price-amount):not(.woocommerce-Price-currencySymbol)',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset .wfacp_you_save_text',
				'#wfacp-e-form .wfacp_main_form .wfacp_row_wrap .wfacp_you_save_text span',
				'#wfacp-e-form .wfacp_main_form .wfacp_whats_included .wfacp_product_switcher_description .wfacp_description p',
				'#wfacp-e-form .wfacp_main_form .wfacp_coupon_field_msg',
				'#wfacp-e-form .wfacp_main_form .wfacp-coupon-page .wfacp_coupon_remove_msg',
				'#wfacp-e-form .wfacp_main_form .wfacp-coupon-page .wfacp_coupon_error_msg',
				'body:not(.wfacp_pre_built) .select2-results__option',
				'body:not(.wfacp_pre_built) .select2-container--default .select2-search--dropdown .select2-search__field',
				'#wfacp-e-form .wfacp_main_form .wfacp_order_total_field table.wfacp_order_total_wrap tr td',
				'#wfacp-e-form .wfacp_main_form .wfacp_order_total_field table.wfacp_order_total_wrap tr td span',
				'#wfacp-e-form .wfacp_main_form .wfacp_order_total .wfacp_order_total_wrap',
				'#wfacp-e-form .wfacp_main_form #payment button#place_order',
				'#wfacp-e-form .wfacp_main_form  button#place_order',
				'#wfacp-e-form .wfacp_main_form.woocommerce  button#place_order',
				'#wfacp-e-form .wfacp_main_form .woocommerce-checkout button.button.button-primary.wfacp_next_page_button',
				'#wfacp-e-form .wfacp-order2StepTitle.wfacp-order2StepTitleS1',
				'#wfacp-e-form .wfacp-order2StepSubTitle.wfacp-order2StepSubTitleS1',
				'#wfacp-e-form .wfacp_main_form .wfacp_steps_sec ul li a',
				'#wfacp-e-form .wfacp_custom_breadcrumb ul li a',
				'#wfacp-e-form .wfacp_main_form table tfoot tr td span ',
				'#wfacp-e-form .wfacp_main_form p.wfacp-form-control-wrapper:not(.wfacp-anim-wrap) label.wfacp-form-control-label abbr',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset .wfacp_you_save_text',
				'#wfacp-e-form .wfacp_main_form .wfacp_row_wrap .wfacp_you_save_text span',
				'#wfacp-e-form .wfacp_main_form .wfacp_row_wrap .wfacp_product_subs_details span',
				'#wfacp-e-form .wfacp_main_form p.wfacp-form-control-wrapper.wfacp_checkbox_field label',
				'#wfacp-e-form .wfacp_main_form .create-account label',
				'#wfacp-e-form .wfacp_main_form .create-account label span',
				'#wfacp-e-form .wfacp_main_form p.wfacp-form-control-wrapper.wfacp_checkbox_field label span',
				'#wfacp-e-form .wfacp_main_form p.wfacp-form-control-wrapper.wfacp_custom_field_radio_wrap > label ',
				'#wfacp-e-form .wfacp_main_form table tfoot tr:not(.order-total) ul',
				'#wfacp-e-form .wfacp_main_form table tfoot tr:not(.order-total) ul li',
				'#wfacp-e-form .wfacp_main_form table tfoot tr:not(.order-total) ul li label',
				'#wfacp-e-form .wfacp_main_form table tfoot tr:not(.order-total) td span.woocommerce-Price-amount.amount',
				'#wfacp-e-form .wfacp_main_form table tfoot tr:not(.order-total) td span.woocommerce-Price-amount.amount bdi',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset.wfacp-selected-product .wfacp_product_sec .wfacp_product_name_inner *',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset.wfacp-selected-product .wfacp_product_sec .wfacp_product_attributes .wfacp_selected_attributes  *',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset.wfacp-selected-product .wfacp_quantity_selector input',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset.wfacp-selected-product .wfacp_product_price_sec span',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset.wfacp-selected-product .wfacp_product_switcher_col_2 .wfacp_product_subs_details > span',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset.wfacp-selected-product .wfacp_product_subs_details span',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset.wfacp-selected-product .wfacp_product_subs_details *',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset.wfacp-selected-product .wfacp_product_sec .wfacp_product_select_options .wfacp_qv-button',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_sec .wfacp_product_name_inner *',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_sec .wfacp_product_attributes .wfacp_selected_attributes  *',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_quantity_selector input',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_price_sec span',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_switcher_col_2 .wfacp_product_subs_details > span',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_subs_details span',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_subs_details *',
				'#wfacp-e-form .wfacp_main_form .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_sec .wfacp_product_select_options .wfacp_qv-button',
				'#wfacp-e-form .wfacp_main_form .wfacp_woocommerce_form_coupon .wfacp-coupon-section .wfacp-coupon-field-btn',
				'#wfacp-e-form .wfacp_mb_mini_cart_sec_accordion_content form.checkout_coupon button.button.wfacp-coupon-btn',
				'#wfacp-e-form .wfacp_main_form .wfacp_shipping_options',
				'#wfacp-e-form .wfacp_main_form .wfacp_shipping_options ul li',
				'#wfacp-e-form .wfacp_main_form .wfacp_shipping_options ul li p',
				'#wfacp-e-form .wfacp_main_form .wfacp_shipping_options ul li .wfacp_shipping_price span',
				'#wfacp-e-form .wfacp_main_form .wfacp_shipping_options ul li .wfacp_shipping_price',
				'#wfacp-e-form .wfacp_main_form .wfacp_payment',
				'#wfacp-e-form .wfacp_main_form .wfacp_payment p',
				'#wfacp-e-form .wfacp_main_form .wfacp_payment p span',
				'#wfacp-e-form .wfacp_main_form .wfacp_payment p a',
				'#wfacp-e-form .wfacp_main_form .wfacp_payment label',
				'#wfacp-e-form .wfacp_main_form .wfacp_payment ul',
				'#wfacp-e-form .wfacp_main_form .wfacp_payment ul li',
				'#wfacp-e-form .wfacp_main_form .wfacp_payment ul li input',
				'#wfacp-e-form .wfacp_main_form .wfacp_payment #add_payment_method #payment div.payment_box',
				'#wfacp-e-form .wfacp_main_form .wfacp_payment #add_payment_method #payment .payment_box p',
				'#wfacp-e-form .wfacp-coupon-section .woocommerce-info > a',
				'#wfacp-e-form .wfacp-coupon-section .woocommerce-info > a:not(.wfacp_close_icon):not(.button-social-login):not(.wfob_btn_add):not(.ywcmas_shipping_address_button_new):not(.wfob_qv-button):not(.wfob_read_more_link):not(.wfacp_step_text_have ):not(.wfacp_cart_link)',
				'#wfacp-e-form  table.shop_table tfoot tr:not(.order-total):not(.cart-discount)',
				'#wfacp-e-form  table.shop_table tfoot tr:not(.order-total):not(.cart-discount) td',
				'#wfacp-e-form  table.shop_table tfoot tr:not(.order-total):not(.cart-discount) th',
				'#wfacp-e-form  table.shop_table tfoot tr:not(.order-total):not(.cart-discount) th span',
				'#wfacp-e-form  table.shop_table tfoot tr:not(.order-total):not(.cart-discount) td span',
				'#wfacp-e-form  table.shop_table tfoot tr:not(.order-total):not(.cart-discount) td small',
				'#wfacp-e-form  table.shop_table tfoot tr:not(.order-total):not(.cart-discount) td bdi',
				'#wfacp-e-form  table.shop_table tfoot tr:not(.order-total):not(.cart-discount) td a',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_payment #payment .payment_methods p',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_payment #payment .payment_methods label',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_payment #payment .payment_methods span',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_payment #payment .payment_methods p a',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_payment #payment .payment_methods strong',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_payment #payment .payment_methods input',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_payment #add_payment_method #payment .payment_box p',
				'#wfacp-e-form  table.shop_table tbody .wfacp_order_summary_item_name',
				'#wfacp-e-form  table.shop_table tbody .product-name .product-quantity',
				'#wfacp-e-form  table.shop_table tbody td.product-total',
				'#wfacp-e-form  table.shop_table tbody .cart_item .product-total span',
				'#wfacp-e-form  table.shop_table tbody .cart_item .product-total span.amount',
				'#wfacp-e-form  table.shop_table tbody .cart_item .product-total span.amount bdi',
				'#wfacp-e-form  table.shop_table tbody .cart_item .product-total small',
				'#wfacp-e-form  table.shop_table tbody .wfacp_order_summary_container dl',
				'#wfacp-e-form  table.shop_table tbody .wfacp_order_summary_container dd',
				'#wfacp-e-form  table.shop_table tbody .wfacp_order_summary_container dt',
				'#wfacp-e-form  table.shop_table tbody .wfacp_order_summary_container p',
				'#wfacp-e-form  table.shop_table tbody tr span.amount',
				'#wfacp-e-form  table.shop_table tbody tr span.amount bdi',
				'#wfacp-e-form  table.shop_table tbody dl',
				'#wfacp-e-form  table.shop_table tbody dd',
				'#wfacp-e-form  table.shop_table tbody dt',
				'#wfacp-e-form  table.shop_table tbody p',
				'#wfacp-e-form  table.shop_table tbody tr td span:not(.wfacp-pro-count)',
				'#wfacp-e-form table.shop_table tfoot tr.cart-discount td',
				'#wfacp-e-form table.shop_table tfoot tr.cart-discount td span',
				'#wfacp-e-form table.shop_table tfoot tr.cart-discount td a',
				'#wfacp-e-form table.shop_table tfoot tr.cart-discount td span',
				'#wfacp-e-form table.shop_table tfoot tr.cart-discount td span bdi',
				'#wfacp-e-form table.shop_table tfoot tr.cart-discount th .wfacp_coupon_code',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total th',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td span.woocommerce-Price-amount.amount',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td span.woocommerce-Price-amount.amount bdi',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td p',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td span',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td span',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td small',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td a',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td p',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total th',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total th span',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total th small',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total th a'
			);

			$this->add_font_family( 'wfacp_font_family', $global_setting_options, __( 'Family' ) );

			$this->add_background_color( 'default_primary_color', array(), '', __( 'Primary Color' ) );

			$fields_content_color = array(
				'#wfacp-e-form .wfacp_main_form .woocommerce-form-login-toggle .woocommerce-info',
				'#wfacp-e-form .wfacp_main_form .woocommerce-form-login.login p',
				'#wfacp-e-form .wfacp_main_form .woocommerce-privacy-policy-text p',
				'#wfacp-e-form .wfacp_main_form .woocommerce-info .message-container',
				'#wfacp-e-form .wfacp_main_form #wc_checkout_add_ons .description',
				'#wfacp-e-form .wfacp_main_form .woocommerce-checkout-review-order h3',
				'#wfacp-e-form .wfacp_main_form .aw_addon_wrap label',
				'#wfacp-e-form .wfacp_main_form p:not(.woocommerce-shipping-contents):not(.wfacp_dummy_preview_heading):not(.checkout-inline-error-message)',
				'#wfacp-e-form .wfacp_main_form p label:not(.wfacp-form-control-label):not(.wfob_title):not(.wfob_span):not(.checkbox)',
				'#wfacp-e-form .wfacp_main_form',
				'#wfacp-e-form .wfacp_main_form .woocommerce-message',
				'#wfacp-e-form .wfacp_main_form .woocommerce-error',
				'#wfacp-e-form .wfacp_main_form .wfacp_payment h4',
				'#wfacp-e-form #payment .woocommerce-privacy-policy-text p',
				'#wfacp-e-form .wfacp_main_form .wfacp_whats_included .wfacp_product_switcher_description .wfacp_description p',
				'#wfacp-e-form .wfacp-form label.woocommerce-form__label .woocommerce-terms-and-conditions-checkbox-text',
				'#wfacp-e-form fieldset',
				'#wfacp-e-form fieldset legend',
			);

			$this->add_color( 'default_text_color', $fields_content_color, '', __( 'Content Color' ) );

			$default_link_color_option = array(
				'#wfacp-e-form .woocommerce-form-login-toggle .woocommerce-info a',
				'#wfacp-e-form a:not(.wfacp_close_icon):not(.button-social-login):not(.wfob_btn_add):not(.ywcmas_shipping_address_button_new):not(.wfob_qv-button):not(.wfob_read_more_link):not(.wfacp_step_text_have ):not(.wfacp_cart_link):not(.wfacp_summary_link):not(.wfacp_collapsible)',
				'#wfacp-e-form a:not(.wfacp_summary_link) span:not(.wfob_btn_text_added):not(.wfob_btn_text_remove)',
				'#wfacp-e-form label a',
				'#wfacp-e-form ul li a:not(.wfacp_breadcrumb_link)',
				'#wfacp-e-form table tr td a',
				'#wfacp-e-form .wfacp_steps_sec ul li a',
				'#wfacp-e-form a.wfacp_remove_coupon',
				'#wfacp-e-form a:not(.button-social-login):not(.wfob_read_more_link):not(.wfacp_collapsible):not(.wfob_btn_add)',
				'#wfacp-e-form .wfacp-login-wrapper input#rememberme + span',
				'#wfacp-e-form #product_switching_field .wfacp_product_switcher_col_2 .wfacp_product_switcher_description a.wfacp_qv-button',
				'#wfacp-e-form .wfacp_main_form .wfacp_collapsible',
			);

			$this->add_color( 'default_link_color', $default_link_color_option, '', __( 'Link Color' ) );

			$selector = array(
				'#wfacp-e-form .wfacp-form',
			);
			$default  = array(
				'top'    => 0,
				'right'  => 10,
				'bottom' => 10,
				'left'   => 10,
			);

			$this->add_padding( 'wfacp_form_border_padding', $selector, $default );
		}

		/**
		 * Retrieves the progress settings for the checkout form.
		 *
		 * @return void
		 */
		public function get_progress_settings() {
			$template = wfacp_template();

			$number_of_steps = $template->get_step_count();

			if ( $number_of_steps < 1 ) {
				return;
			}

			$step_text = __( 'Steps' );
			if ( $number_of_steps <= 1 ) {
				$step_text = __( 'Header' );
			}

			$controls_condition = array(
				'select_type',
				'=',
				array(
					'bredcrumb',
					'progress_bar',
					'tab',
				),
			);

			$tab_condition          = array( 'select_type', '=', array( 'tab' ) );
			$breadcrumb_condition   = array( 'select_type', '=', array( 'bredcrumb' ) );
			$progress_bar_condition = array( 'select_type', '=', array( 'progress_bar' ) );
			$exclude                = array( 'text-align', 'color' );

			$this->set_current_group( 'styleHeader' );
			$this->add_group( 'styleHeader', $step_text, self::TAB_STYLE );

			$this->add_heading( __( 'Typography' ), $controls_condition );
			$this->add_typography( 'tab_heading_typography', '#wfacp-e-form .wfacp_form_steps .wfacp-order2StepTitle.wfacp-order2StepTitleS1', array(), $tab_condition, 'Heading', $exclude );
			$this->add_typography( 'tab_subheading_typography', '#wfacp-e-form .wfacp_form_steps .wfacp-order2StepSubTitle.wfacp-order2StepSubTitleS1', array(), $tab_condition, 'Sub Heading', $exclude );

			$alignment_option = array( '#wfacp-e-form .wfacp-payment-tab-list .wfacp-order2StepHeaderText' );
			$this->add_text_alignments( 'tab_text_alignment', $alignment_option, '', 'center', array( 'select_type', '=', 'tab' ) );
			$this->add_typography( 'progress_bar_heading_typography', '#wfacp-e-form .wfacp_custom_breadcrumb .wfacp_steps_sec ul li a', array(), $progress_bar_condition, 'Heading', $exclude );
			$this->add_typography( 'breadcrumb_heading_typography', '#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_steps_sec ul li a', array(), $breadcrumb_condition, 'Heading', $exclude );

			$this->add_heading( 'Colors', array(
					'select_type',
					'=',
					array(
						'bredcrumb',
						'progress_bar',
					),
				) );

			$this->add_color( 'breadcrumb_text_color', array( '#wfacp-e-form .wfacp-form .wfacp_main_form.woocommerce .wfacp_steps_sec ul li a' ), '', 'Color ', $breadcrumb_condition );

			$active_color = array(
				'#wfacp-e-form .wfacp_custom_breadcrumb .wfacp_steps_sec ul li.wfacp_bred_active:before',
				'#wfacp-e-form .wfacp_custom_breadcrumb .wfacp_steps_sec ul li.wfacp_active_prev:before',
				'#wfacp-e-form .wfacp_custom_breadcrumb .wfacp_steps_sec ul li.df_cart_link.wfacp_bred_visited:before',
			);

			$this->add_background_color( 'progress_bar_line_color', array( '#wfacp-e-form .wfacp_custom_breadcrumb .wfacp_steps_sec ul:before' ), '', 'Line', $progress_bar_condition );
			$this->add_border_color( 'progress_bar_circle_color', array( '#wfacp-e-form .wfacp_custom_breadcrumb .wfacp_steps_sec ul li:before' ), '', __( 'Circle Border' ), false, $progress_bar_condition );

			$this->add_background_color( 'progress_bar_active_color', $active_color, '', 'Active Step', $progress_bar_condition );
			$this->add_color( 'progressbar_text_color', array( ' #wfacp-e-form .wfacp_custom_breadcrumb .wfacp_steps_sec ul li a' ), '', 'Text ', $progress_bar_condition );

			if ( $number_of_steps > 1 ) {
				$this->add_heading( __( 'Active Step' ) );
			} else {
				$this->add_heading( 'Colors', array(
						'select_type',
						'=',
						array(
							'tab',
						),
					) );
			}

			$this->add_background_color( 'active_step_bg_color', array( '#wfacp-e-form .wfacp_form_steps .wfacp-payment-tab-list.wfacp-active' ), '', 'Background Color', $tab_condition );
			$this->add_color( 'active_step_text_color', array( '#wfacp-e-form .wfacp_form_steps .wfacp-payment-tab-list.wfacp-active .wfacp_tcolor' ), '', 'Text Color', $tab_condition );
			$this->add_border_color( 'active_tab_border_bottom_color', array( '#wfacp-e-form .wfacp-payment-tab-list.wfacp-active' ), '', __( 'Tab Border Color' ), false, $tab_condition );

			if ( $number_of_steps > 1 ) {
				$this->add_background_color( 'active_step_count_bg_color', array( '#wfacp-e-form .wfacp_form_steps .wfacp-payment-tab-list.wfacp-active .wfacp-order2StepNumber' ), '', 'Count Background Color', $tab_condition );
				$this->add_border_color( 'active_step_count_border_color', array( '#wfacp-e-form .wfacp_form_steps .wfacp-payment-tab-list.wfacp-active .wfacp-order2StepNumber' ), '', __( 'Count Border Color' ), false, $tab_condition );
				$this->add_color( 'active_step_count_text_color', array( '#wfacp-e-form .wfacp_form_steps .wfacp-payment-tab-list.wfacp-active .wfacp-order2StepNumber' ), '', 'Count Text Color', $tab_condition );
			}

			if ( $number_of_steps > 1 ) {
				$this->add_heading( __( 'Inactive Step' ) );

				$inactive_bg_color = array(
					'#wfacp-e-form .wfacp_form_steps .wfacp-payment-tab-list',
				);

				$this->add_background_color( 'inactive_step_bg_color', $inactive_bg_color, '', __( 'Background Color' ), $tab_condition );
				$this->add_color( 'inactive_step_text_color', array( '#wfacp-e-form .wfacp_form_steps .wfacp-payment-tab-list .wfacp_tcolor' ), '', __( 'Text Color' ), $tab_condition );
				$this->add_border_color( 'inactive_tab_border_bottom_color', array( '#wfacp-e-form .wfacp-payment-tab-list' ), '', __( 'Tab Border Color' ), false, $tab_condition );
				$this->add_background_color( 'inactive_step_count_bg_color', array( '#wfacp-e-form .wfacp_form_steps .wfacp-payment-tab-list .wfacp-order2StepNumber' ), '', 'Count Background Color', $tab_condition );
				$this->add_border_color( 'inactive_step_count_border_color', array( '#wfacp-e-form .wfacp_form_steps .wfacp-payment-tab-list .wfacp-order2StepNumber' ), '', __( 'Count Border Color' ), false, $tab_condition );
				$this->add_color( 'inactive_step_count_text_color', array( '#wfacp-e-form .wfacp_form_steps .wfacp-payment-tab-list .wfacp-order2StepNumber' ), '', 'Count Text Color', $tab_condition );
			}

			$this->add_heading( 'Border Radius', $tab_condition );

			$label = __( 'Step Bar Border Radius' );
			$this->add_border_radius( 'border_radius_steps', '#wfacp-e-form .wfacp_form_steps .wfacp-payment-tab-list', array(), $label, $tab_condition );

			$selector = array(
				'#wfacp-e-form .tab',
			);

			$default = array(
				'top'    => 0,
				'right'  => 0,
				'bottom' => 15,
				'left'   => 0,
			);
			$this->add_margin( 'wfacp_tab_margin', $selector, $default, $tab_condition );
		}

		/**
		 * Adds the collapsible order summary styles.
		 *
		 * This method adds the collapsible order summary styles.
		 *
		 * @return void
		 */
		public function collapsible_order_summary() {
			$this->set_current_group( 'styleCollapsibleOrderSummary' );
			$this->add_background_color( 'collapsible_order_summary_bg_color', array( '#wfacp-e-form .wfacp_mb_mini_cart_wrap .wfacp_mb_cart_accordian' ), '#f7f7f7', __( 'Collapsed Background' ) );
			$this->add_background_color( 'expanded_order_summary_bg_color', array(
					'#wfacp-e-form .wfacp_mb_mini_cart_sec_accordion_content',
				), '#f7f7f7', __( 'Expanded Background' ) );
			$this->add_color( 'expanded_order_summary_link_color', array(
					'#wfacp-e-form .wfacp_show_icon_wrap a span',
					'#wfacp-e-form .wfacp_show_price_wrap span',
				), '#323232', __( 'Text Color' ) );

			$selector = array(
				'#wfacp-e-form .wfacp_collapsible_order_summary_wrap',
			);

			$default = array(
				'top'    => 0,
				'right'  => 0,
				'bottom' => 15,
				'left'   => 0,
			);

			$this->add_margin( 'wfacp_collapsible_margin', $selector, $default );
			$this->add_border_radius( 'wfacp_collapsible_border_radius', array(
				'#wfacp-e-form .wfacp_mb_mini_cart_wrap .wfacp_mb_cart_accordian',
				'#wfacp-e-form .wfacp_mb_mini_cart_wrap .wfacp_mb_mini_cart_sec_accordion_content'
			), array(), __( 'Border Radius' ) );
		}

		/**
		 * Adds the heading settings for the checkout form.
		 *
		 * This method adds the heading settings for the checkout form.
		 *
		 * @return void
		 */
		public function get_heading_settings() {
			$this->set_current_group( 'styleHeading' );
			$this->add_heading( __( 'Heading' ) );

			$section_title_option = array( '#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_section_title' );

			$this->add_typography( 'section_heading_typo', $section_title_option );

			$subheading_option = array( '#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-comm-title h4' );

			// Sub heading start here
			$this->add_heading( __( 'Sub Heading' ) );
			$this->add_typography( 'section_sub_heading_typo', $subheading_option );

			$advanced_option = array( '#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-section .wfacp-comm-title' );

			$this->add_heading( __( 'Advanced' ) );
			$this->add_background_color( 'form_heading_bg_color', $advanced_option, 'transparent' );
			$this->add_padding( 'form_heading_padding', $advanced_option );

			$default = array(
				'top'    => 0,
				'right'  => 0,
				'bottom' => 10,
				'left'   => 0,
			);
			$this->add_margin( 'form_heading_margin', $advanced_option, $default );
			$this->add_border( 'form_heading_border', $advanced_option );
		}

		/**
		 * Add the style settings for the form fields.
		 *
		 * This method adds the style settings for the form fields.
		 *
		 * @return void
		 */
		public function fields_typo_settings() {
			$this->set_current_group( 'styleFields' );

			$this->add_heading( __( 'Label' ) );

			$label_position_options = array(
				'wfacp-modern-label' => __( 'Floating' ),
				'wfacp-top'          => __( 'Outside' ),
				'wfacp-inside'       => __( 'Inside' ),

			);

			$this->add_select( 'wfacp_label_position', __( 'Label Position' ), $label_position_options, 'wfacp-inside' );

			$form_fields_label_typo = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce p.wfacp-form-control-wrapper label.wfacp-form-control-label',
				'#wfacp-e-form .wfacp_main_form.woocommerce .create-account label',
				'#wfacp-e-form .wfacp_main_form.woocommerce .create-account label span',
				'#wfacp-e-form .wfacp_main_form.woocommerce p.wfacp-form-control-wrapper:not(.wfacp-anim-wrap) label.wfacp-form-control-label abbr',
				'#wfacp-e-form .wfacp-form.wfacp-top .form-row:not(.wfacp_checkbox_field) label.wfacp-form-control-label',
				'#wfacp-e-form .wfacp-form.wfacp-top .form-row:not(.wfacp_checkbox_field) label.wfacp-form-control-label abbr.required',
				'#wfacp-e-form .wfacp-form.wfacp-top .form-row:not(.wfacp_checkbox_field) label.wfacp-form-control-label .optional',
			);

			$this->add_typography( 'wfacp_form_fields_label_typo', $form_fields_label_typo, array(), array(), '', array( 'color' ) );

			$form_fields_label_color_opt = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-form-control-label',
				'#wfacp-e-form .wfacp_allowed_countries strong',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-form-control-label abbr',
			);

			$this->add_color( 'wfacp_form_fields_label_color', $form_fields_label_color_opt );

			$input_typo_selectors = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce input[type="text"]',
				'#wfacp-e-form .wfacp_main_form.woocommerce input[type="email"]',
				'#wfacp-e-form .wfacp_main_form.woocommerce input[type="tel"]',
				'#wfacp-e-form .wfacp_main_form.woocommerce input[type="password"]',
				'#wfacp-e-form .wfacp_main_form.woocommerce input[type="number"]',
				'#wfacp-e-form .wfacp_main_form.woocommerce input[type="date"]',
				'#wfacp-e-form .wfacp_main_form.woocommerce select',
				'#wfacp-e-form .wfacp_main_form.woocommerce textarea',
				'#wfacp-e-form .wfacp_main_form.woocommerce number',
				'#wfacp-e-form .woocommerce-input-wrapper .wfacp-form-control',
				'#wfacp-e-form .wfacp_main_form.woocommerce .select2-container .select2-selection--single .select2-selection__rendered',
				'body:not(.wfacp_pre_built) .select2-results__option',
				'body:not(.wfacp_pre_built) .select2-container--default .select2-search--dropdown .select2-search__field',
				'#wfacp-e-form .wfacp_main_form.woocommerce .form-row label.checkbox',
				'#wfacp-e-form .wfacp_main_form.woocommerce .form-row label.checkbox *',

			);

			$default_typography_options = array(
				'font-size' => '14px',
			);

			$this->add_heading( __( 'Input' ) );
			$this->add_typography( 'wfacp_form_fields_input_typo', $input_typo_selectors, $default_typography_options, array(), '', array( 'color' ) );

			$input_color_selectors = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce .woocommerce-input-wrapper .wfacp-form-control',
				'#wfacp-e-form .wfacp_main_form.woocommerce .select2-container .select2-selection--single .select2-selection__rendered',
				'#wfacp-e-form .wfacp_main_form.woocommerce select',
				'#wfacp-e-form .wfacp_main_form.woocommerce .form-row label.checkbox',
				'#wfacp-e-form .wfacp_main_form.woocommerce .form-row label.checkbox *',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_shipping_options *',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_shipping_options ul',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_shipping_options ul li',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_shipping_options ul li p',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_shipping_options ul li label',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_shipping_options ul li .wfacp_shipping_price span',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_shipping_options ul li .wfacp_shipping_price',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_subscription_count_wrap p',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_shipping_table ul#shipping_method label',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_shipping_table ul#shipping_method span',

			);
			$this->add_color( 'wfacp_form_fields_input_color', $input_color_selectors );

			$input_bg_color_selectors = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce .woocommerce-input-wrapper .wfacp-form-control:not(.input-checkbox):not(.hidden)',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-form-control:not(.input-checkbox):not(.hidden)',
				'#wfacp-e-form .wfacp_allowed_countries strong',
				'#wfacp-e-form .wfacp_main_form.woocommerce .select2-container .select2-selection--single .select2-selection__rendered',
				'#wfacp-e-form .wfacp_main_form.woocommerce select',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-login-wrapper input[type=email]',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-login-wrapper input[type=number]',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-login-wrapper input[type=password]',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-login-wrapper input[type=tel]',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-login-wrapper select',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-login-wrapper input[type=text]',
				'#wfacp-e-form .wfacp-form.wfacp-inside .form-row .wfacp-form-control-label:not(.checkbox)',

			);

			$this->add_background_color( 'wfacp_form_fields_input_bg_color', $input_bg_color_selectors, '#ffffff' );

			$this->add_heading( __( 'Border' ) );

			$form_fields_border_selectors = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce input[type="text"]',
				'#wfacp-e-form .wfacp_main_form.woocommerce input[type="email"]',
				'#wfacp-e-form .wfacp_main_form.woocommerce input[type="tel"]',
				'#wfacp-e-form .wfacp_main_form.woocommerce input[type="password"]',
				'#wfacp-e-form .wfacp_main_form.woocommerce input[type="number"]',
				'#wfacp-e-form .wfacp_main_form.woocommerce input[type="date"]',
				'#wfacp-e-form .wfacp_main_form.woocommerce select',
				'#wfacp-e-form .wfacp_main_form.woocommerce textarea',
				'#wfacp-e-form .wfacp_main_form .woocommerce-input-wrapper input[type="number"].wfacp-form-control',
				'#wfacp-e-form .wfacp_main_form .woocommerce-input-wrapper input[type="date"].wfacp-form-control',
				'#wfacp-e-form .wfacp_main_form .woocommerce-input-wrapper input[type="text"].wfacp-form-control',
				'#wfacp-e-form .wfacp_main_form .woocommerce-input-wrapper input[type="email"].wfacp-form-control',
				'#wfacp-e-form .wfacp_allowed_countries strong',
				'#wfacp-e-form .wfacp_main_form.woocommerce .select2-container .select2-selection--single .select2-selection__rendered',
				'#wfacp-e-form .iti__selected-flag',
			);

			$default = array(
				'radius' => array(
					'top'    => 4,
					'right'  => 4,
					'bottom' => 4,
					'left'   => 4,
				),
			);
			$this->add_border( 'wfacp_form_fields_border', $form_fields_border_selectors, $default );

			$validation_error = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce p.woocommerce-invalid-required-field .wfacp-form-control',
				'#wfacp-e-form .wfacp_main_form.woocommerce p.woocommerce-invalid-email .wfacp-form-control',
				'#wfacp-e-form .wfacp_main_form.woocommerce p.wfacp_coupon_failed .wfacp_coupon_code',
				'#wfacp-e-form .wfacp_main_form.woocommerce p.woocommerce-invalid-required-field:not(.wfacp_select2_country_state):not(.wfacp_state_wrap) .woocommerce-input-wrapper .select2-container .select2-selection--single .select2-selection__rendered',
			);

			$focus_fields_color = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce p.form-row:not(.woocommerce-invalid-email) .wfacp-form-control:not(.wfacp_coupon_code):focus',
				'#wfacp-e-form p.form-row:not(.woocommerce-invalid-email) .wfacp-form-control:not(.input-checkbox):focus',
				'#wfacp-e-form .wfacp_main_form.woocommerce p.wfacp_coupon_failed .wfacp_coupon_code',
				'#wfacp-e-form .wfacp_main_form .form-row:not(.woocommerce-invalid-required-field) .woocommerce-input-wrapper .select2-container .select2-selection--single .select2-selection__rendered:focus',
				'#wfacp-e-form .wfacp_main_form.woocommerce .form-row:not(.woocommerce-invalid-required-field) .woocommerce-input-wrapper .select2-container .select2-selection--single:focus>span.select2-selection__rendered',
			);

			$this->add_border_color( 'wfacp_form_fields_focus_color', $focus_fields_color, '#61bdf7', __( 'Focus Color' ), true );
			$this->add_border_color( 'wfacp_form_fields_validation_color', $validation_error, '#d50000', __( 'Error Validation Color' ), true );
		}


		/**
		 * Sets the typo settings for the section.
		 *
		 * This method sets the background color, border, padding, and margin for the form section.
		 *
		 * @return void
		 */
		public function section_typo_settings() {
			$this->set_current_group( 'styleSection' );

			$selectors = array(
				'#wfacp-e-form .wfacp-section',
			);
			$this->add_background_color( 'form_section_bg_color', $selectors, '', __( 'Background Color' ) );
			$this->add_border_shadow( 'form_section_box_shadow', $selectors );
			$this->add_divider();
			$this->add_border( 'form_section_border', $selectors );
			$this->add_divider();

			$this->add_padding( 'form_section_padding', $selectors );

			$this->add_margin( 'form_section_margin', $selectors, array(
					'top'    => 0,
					'right'  => 0,
					'bottom' => 10,
					'left'   => 0,
				) );
		}

		/**
		 * Sets the styling for the payment method section in the form.
		 *
		 * This method adds typography, color, and background color settings for the payment method section in the form.
		 *
		 * @return void
		 * @since 1.0.0
		 *
		 */
		public function payment_method_styling() {
			$this->set_current_group( 'styleSectionPaymentMethods' );

			$payment_method_typo_selectors = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_payment #payment .payment_methods p',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_payment #payment .payment_methods label',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_payment #payment .payment_methods span',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_payment #payment .payment_methods p a',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_payment #payment .payment_methods strong',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_payment #payment .payment_methods input',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_payment #add_payment_method #payment .payment_box p',
			);

			$this->add_typography( 'wfacp_form_payment_method_typo', $payment_method_typo_selectors, array(), array(), '', array( 'color' ) );

			/* Color Setting  */

			$this->add_heading( __( 'Colors' ) );

			$payment_method_label_color = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce .woocommerce-checkout #payment ul.payment_methods li label',
				'#wfacp-e-form .wfacp_main_form.woocommerce .woocommerce-checkout #payment ul.payment_methods li label span',
				'#wfacp-e-form .wfacp_main_form.woocommerce .woocommerce-checkout #payment ul.payment_methods li label a',
			);

			$this->add_color( 'wfacp_form_payment_method_label_color', $payment_method_label_color, '', __( 'Text Color' ) );

			$payment_method_description_color = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_payment #payment .payment_methods li .payment_box p',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_payment #payment .payment_methods li .payment_box p span',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_payment #payment .payment_methods li .payment_box  p strong',
			);

			$this->add_color( 'wfacp_form_payment_method_description_color', $payment_method_description_color, '', __( 'Description Color' ) );

			$payment_method_description_bg_color = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_payment #payment .payment_methods li .payment_box',
			);

			$this->add_background_color( 'wfacp_form_payment_method_description_bg_color', $payment_method_description_bg_color, '', __( 'Information Background Color' ) );
		}

		/**
		 * Sets the styling for the privacy policy section.
		 *
		 * This method sets the font size and color for the privacy policy text in the checkout form.
		 *
		 * @return void
		 */
		public function privacy_policy_styling() {
			$typo = array(
				'#wfacp-e-form #payment .woocommerce-privacy-policy-text p',
				'#wfacp-e-form #payment .woocommerce-privacy-policy-text a',
			);

			$color = array(
				'#wfacp-e-form #payment .woocommerce-privacy-policy-text p',
			);

			$this->set_current_group( 'stylePrivacyPolicy' );
			$this->add_font_size( 'wfacp_privacy_policy_font_size', $typo, __( 'Font Size (in px)' ), '12px' );
			$this->add_color( 'wfacp_privacy_policy_color', $color, '#777777', __( 'Color' ), array(), true );
		}

		/**
		 * Sets the styling for the terms and policy section.
		 *
		 * This method sets the font size and color for various elements in the terms and policy section of the checkout form.
		 *
		 * @return void
		 */
		public function terms_policy_styling() {
			$typo = array(
				'#wfacp-e-form .wfacp-form .wfacp_main_form.woocommerce .woocommerce-terms-and-conditions-wrapper .form-row label',
				'#wfacp-e-form .wfacp-form .wfacp_main_form.woocommerce .woocommerce-terms-and-conditions-wrapper .form-row label span',
				'#wfacp-e-form .wfacp-form .wfacp_main_form.woocommerce.woocommerce-terms-and-conditions-wrapper .form-row label a',
				'#wfacp-e-form .wfacp-form .wfacp_main_form.woocommerce label.woocommerce-form__label .woocommerce-terms-and-conditions-checkbox-text',
				'#wfacp-e-form .wfacp-form .wfacp_main_form.woocommerce label.woocommerce-form__label .woocommerce-terms-and-conditions-checkbox-text a',
			);

			$color = array(
				'#wfacp-e-form .wfacp-form .wfacp_main_form.woocommerce .woocommerce-terms-and-conditions-wrapper .form-row',
				'#wfacp-e-form  .wfacp-form .wfacp_main_form.woocommerce .woocommerce-terms-and-conditions-wrapper .woocommerce-terms-and-conditions-checkbox-text',
				'#wfacp-e-form .wfacp-form .wfacp_main_form.woocommerce label.woocommerce-form__label .woocommerce-terms-and-conditions-checkbox-text',
			);

			$range = array(
				'%'  => array(
					'min' => 0,
					'max' => 100,
				),
				'px' => array(
					'min'  => 0,
					'max'  => 22,
					'step' => 1,
				),
			);

			$this->set_current_group( 'styleTermsConditions' );
			$this->add_font_size( 'wfacp_terms_condition_font_size', $typo, __( 'Font Size (in px)' ), '14px', array(), $range );
			$this->add_color( 'wfacp_terms_condition_color', $color, '', __( 'Color' ) );
		}

		/**
		 * Configures the styling options for the payment buttons in the WooCommerce form.
		 *
		 * This function sets up various styling options for payment buttons on the checkout form,
		 * including width, alignment, typography, colors, padding, margin, and borders. Additionally,
		 * it configures the styling for the return link and additional text.
		 *
		 * @return void
		 */
		public function payment_buttons_styling() {
			$this->set_current_group( 'styleCheckoutButtons' );

			$selector = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-next-btn-wrap button',
				'#wfacp-e-form .wfacp_main_form.woocommerce #payment button#place_order',
				'#wfacp-e-form .wfacp_main_form.woocommerce  button#place_order',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_payment #ppcp-hosted-fields .button',
				'#wfacp-e-form .wfacp_main_form.woocommerce .button.button#place_order',
				'#wfacp-e-form .wfacp_main_form.woocommerce .button.wfacp_next_page_button',

			);

			$this->add_width( 'wfacp_button_width', $selector, __( 'Button Width (in %)' ), '100%', array(), array() );

			$alignment = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce .woocommerce-checkout .wfacp-order-place-btn-wrap',
				'#wfacp-e-form .wfacp_main_form.woocommerce .woocommerce-checkout .wfacp-next-btn-wrap',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_payment #ppcp-hosted-fields',
			);

			$this->add_text_alignments( 'wfacp_form_button_alignment', $alignment, 'center' );

			$btntypo = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce #payment button#place_order',
				'#wfacp-e-form .wfacp_main_form.woocommerce  button#place_order',
				'#wfacp-e-form .wfacp_main_form.woocommerce  .wfacp_payment #ppcp-hosted-fields .button',
				'#wfacp-e-form .wfacp_main_form.woocommerce .woocommerce-checkout button.button.button-primary.wfacp_next_page_button',
			);

			$fields_options = array(
				'font_weight' => '700',
				'font_size'   => '25px',
			);

			$this->add_typography( 'wfacp_form_payment_button_typo', $btntypo, $fields_options, array(), '', array( 'color' ) );

			/* Button Icon Style*/
			$this->button_icon_style();

			$button_selectors = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-next-btn-wrap button',
				'#wfacp-e-form .wfacp_main_form.woocommerce #payment button#place_order',
				'#wfacp-e-form .wfacp_main_form.woocommerce  button#place_order',
				'#wfacp-e-form .wfacp_main_form.woocommerce  .wfacp_payment #ppcp-hosted-fields .button',
				'#wfacp-e-form .wfacp_main_form.woocommerce .button.button#place_order',
				'#wfacp-e-form .wfacp_main_form.woocommerce .button.wfacp_next_page_button',
				'#wfacp_qr_model_wrap .wfacp_qr_wrap .wfacp_qv-summary .button',
				'body #wfob_qr_model_wrap .wfob_qr_wrap .button',
				'body #wfob_qr_model_wrap .wfob_option_btn',
			);

			/* Button Background hover tab */
			$this->add_heading( __( 'Color' ) );
			$this->add_background_color( 'wfacp_button_bg_color', $button_selectors, '', __( 'Background' ) );
			$this->add_color( 'wfacp_button_label_color', $button_selectors, '', __( 'Label' ) );

			$this->add_divider();

			$default = array(
				'top'      => '15',
				'right'    => '25',
				'bottom'   => '15',
				'left'     => '25',
				'unit'     => 'px',
				'isLinked' => false,
			);

			$this->add_padding( 'wfacp_button_padding', $selector, $default );
			$this->add_margin( 'wfacp_button_margin', $selector );

			$this->add_divider();

			$this->add_border( 'wfacp_button_border', $selector );

			$stepBackLink = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce #wfacp_checkout_form .wfacp-back-btn-wrap a',
				'#wfacp-e-form .wfacp_main_form.woocommerce #wfacp_checkout_form .btm_btn_sec.wfacp_back_cart_link .wfacp-back-btn-wrap a',
				'#wfacp-e-form .wfacp_main_form.woocommerce #wfacp_checkout_form .wfacp-back-btn-wrap a.wfacp_back_page_button',
				'#wfacp-e-form .wfacp_main_form.woocommerce #wfacp_checkout_form  .place_order_back_btn a',
			);

			/* Back Link color setting */

			$this->add_heading( __( 'Return Link' ) );
			$this->add_color( 'step_back_link_color', $stepBackLink, '', 'Color' );

			/* Back link color setting End*/

			$this->add_heading( __( 'Additional Text' ) );
			$this->add_color( 'additional_text_color', array( '#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-payment-dec' ) );
			$this->add_background_color( 'additional_bg_color', array( '#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-payment-dec' ), '', __( 'Background' ) );
			$this->add_switcher( 'wfacp_make_button_sticky_on_mobile', __( 'Sticky on Mobile' ), false );
		}

		/**
		 * This method is responsible for generating the class section of the form.
		 *
		 * @return void
		 */
		public function class_section() {
			$template           = wfacp_template();
			$template_slug      = $template->get_template_slug();
			$do_not_show_fields = WFACP_Common::get_html_excluded_field();

			$this->set_current_group( 'stylefieldClasses' );

			$sections = $this->section_fields;
			foreach ( $sections as $val ) {
				foreach ( $val as $loop_key => $field ) {
					if ( in_array( $loop_key, array( 'wfacp_start_divider_billing', 'wfacp_start_divider_shipping' ), true ) ) {
						$address_key_group = ( $loop_key === 'wfacp_start_divider_billing' ) ? __( 'Billing Address' ,'woocommerce') : __( 'Shipping Address' ,'woocommerce');
						$this->add_heading( $address_key_group );
					}

					if ( ! isset( $field['id'] ) || ! isset( $field['label'] ) ) {
						continue;
					}

					$field_key = $field['id'];

					if ( in_array( $field_key, $do_not_show_fields, true ) ) {
						$this->html_fields[ $field_key ] = true;
						continue;
					}

					$skipKey = array( 'billing_same_as_shipping', 'shipping_same_as_billing' );
					if ( in_array( $field_key, $skipKey, true ) ) {
						continue;
					}
					$this->add_text( 'wfacp_' . $template_slug . '_' . $field_key . '_field_class', $field['label'], '', array(), '', __( 'Custom Class' ) );
				}
			}
		}

		/**
		 * Configures the styling options for button icons in the WooCommerce form.
		 *
		 * This function sets up various styling options for the icons and subtext of buttons on the
		 * checkout form, including color and font size.
		 *
		 * @return void
		 */
		public function button_icon_style() {
			$template_obj  = wfacp_template();
			$template_slug = $template_obj->get_template_slug();

			$this->add_heading( __( 'Button Icon' ) );

			$btn_icon_selector = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-order-place-btn-wrap button:before',
				'#wfacp-e-form .wfacp-next-btn-wrap button:before',
			);

			$this->add_color( $template_slug . '_btn_icon_color', $btn_icon_selector, '#ffffff', __( 'Icon Color' ) );
			$this->add_heading( __( 'Sub Text' ) );

			$button_sub_text_selector = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-order-place-btn-wrap button:after',
				'#wfacp-e-form .wfacp-next-btn-wrap button:after',
			);

			$this->add_font_size( $template_slug . '_button_sub_text_font_size', $button_sub_text_selector, __( 'Font Size (in px)' ), '12px', array() );
			$this->add_color( $template_slug . '_button_sub_text_color', $button_sub_text_selector, '#ffffff', __( 'Text Color' ) );
		}

		/**
		 * Handles product switching functionality.
		 *
		 * This method is responsible for handling the product switching functionality
		 * based on the provided field key.
		 *
		 * @param string $field_key The key of the field to be switched.
		 *
		 * @return void
		 */
		protected function product_switching( $field_key ) {
			$this->set_current_group( 'styleProductSwitcher' );

			/*  Selected Items Setting */

			$this->add_heading( __( 'Selected Items' ) );

			/* Typography  */
			$product_switcher_typo_option = array(
				'{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_product_sec .wfacp_product_name_inner *',
				'{{WRAPPER}} #wfacp-e-form #wfacp-sec-wrapper .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_product_sec .wfacp_product_name_inner .wfacp_product_switcher_item',
				'{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_product_sec .wfacp_product_attributes .wfacp_selected_attributes  *',
				'{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_quantity_selector input',
				'{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_product_switcher_col_2 .wfacp_product_subs_details > span',
				'{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_product_subs_details span',
				'{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_product_subs_details *',
				'{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_product_sec .wfacp_product_select_options .wfacp_qv-button',

				'{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_product_price_sec > span',
				'{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_product_price_sec > span *',
				'{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_product_price_sec > span bdi',
				'{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_product_price_sec ins span',
				'{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_product_price_sec ins span bdi',
				'{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_product_price_sec del',
				'{{WRAPPER}} #wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_product_price_sec del *',
			);

			$this->add_typography( 'selected_item_typography', $product_switcher_typo_option, array(), array(), '', array( 'color', 'text-align' ) );

			/* Items Color */
			$selector = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce .woocommerce-cart-form__cart-item.cart_item.wfacp-selected-product .wfacp_row_wrap .wfacp_product_choosen_label .wfacp_product_switcher_item',
				'#wfacp-e-form .wfacp_main_form.woocommerce .woocommerce-cart-form__cart-item.cart_item.wfacp-selected-product .wfacp_row_wrap .product-name .wfacp_product_switcher_item',
				'#wfacp-e-form .wfacp_main_form.woocommerce .woocommerce-cart-form__cart-item.cart_item.wfacp-selected-product .wfacp_row_wrap .wfacp_product_choosen_label .wfacp_product_row_quantity',
			);

			$this->add_color( $field_key . '_label_color', $selector, '', __( 'Item Color' ) );

			/* Items Price Color */

			$item_price_color_opt = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce .shop_table.wfacp-product-switch-panel .wfacp-selected-product .product-price',
				'#wfacp-e-form .wfacp_main_form.woocommerce .shop_table.wfacp-product-switch-panel .wfacp-selected-product .product-price span',
			);

			$this->add_color( $field_key . '_price_color', $item_price_color_opt, '', __( 'Item Price Color' ) );

			$variant_color = array(
				'#wfacp-e-form .wfacp_main_form .wfacp_selected_attributes .wfacp_pro_attr_single span',
				'#wfacp-e-form .wfacp_main_form .wfacp_selected_attributes .wfacp_pro_attr_single span:last-child',
				'#wfacp-e-form .wfacp_main_form.woocommerce #product_switching_field .wfacp_product_switcher_col_2 .wfacp_product_subs_details',
				' #wfacp-e-form .wfacp_main_form.woocommerce #product_switching_field .wfacp_product_switcher_col_2 .wfacp_product_subs_details span',
			);

			$this->add_color( $field_key . '_variant_color', $variant_color, '#666666', __( 'Variant Color' ) );

			/* Background Color */
			$item_bg_color = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce .woocommerce-cart-form__cart-item.cart_item.wfacp-selected-product',
			);

			$this->add_background_color( $field_key . '_item_background', $item_bg_color, '', __( 'Background Color' ) );

			/* Saving text Start*/
			$this->product_switching_saving_text( $field_key . '_selected' );
			/* Saving text End*/

			$fields_options = array(
				'width' => array(
					'top'    => 1,
					'bottom' => 1,
					'left'   => 1,
					'right'  => 1,
				),
				'style' => 'solid',
				'color' => array(
					'hex' => '#dddddd',
				),
			);

			/* Border */
			$this->add_border( $field_key . '_item_border', array( '#wfacp-e-form .wfacp_main_form.woocommerce .woocommerce-cart-form__cart-item.cart_item.wfacp-selected-product' ), $fields_options );

			/* Optional Item Setting */

			$this->add_heading( __( 'Non-selected Items' ) );

			$product_switcher_typo_optional = [
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_sec .wfacp_product_name_inner *',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_sec .wfacp_product_attributes .wfacp_selected_attributes  *',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_quantity_selector input',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_switcher_col_2 .wfacp_product_subs_details > span',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_subs_details span',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_subs_details *',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_sec .wfacp_product_select_options .wfacp_qv-button',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_price_sec > span',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_price_sec > span *',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_price_sec > span bdi',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_price_sec ins span',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_price_sec ins span bdi',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_price_sec del',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_price_sec del *',
			];

			$this->add_typography( $field_key . '_optional_item_typography', $product_switcher_typo_optional, array(), array(), '', array( 'color', 'text-align' ) );

			/* Label Color Setting */
			$optional_label_color_opt = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce .woocommerce-cart-form__cart-item.cart_item .wfacp_row_wrap .wfacp_product_choosen_label .wfacp_product_switcher_item',
				'#wfacp-e-form .wfacp_main_form.woocommerce .woocommerce-cart-form__cart-item.cart_item .wfacp_row_wrap .wfacp_product_choosen_label .wfacp_product_row_quantity',
			);

			$this->add_color( $field_key . '_optional_label_color', $optional_label_color_opt, '', esc_attr__( 'Item Color' ) );

			/* Items Price Color */

			$optional_price_color_option = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce .shop_table.wfacp-product-switch-panel .product-price',
				'#wfacp-e-form .wfacp_main_form.woocommerce .shop_table.wfacp-product-switch-panel .wfacp_product_price_sec span',
			);

			$this->add_color( $field_key . '_optional_price_color', $optional_price_color_option, '', 'Item Price Color' );

			/* Background Color */

			$this->add_background_color( $field_key . '_optional_background', array( '.woocommerce-cart-form__cart-item.cart_item:not(.wfacp-selected-product)' ), '#ffffff', __( 'Background Color' ) );

			$this->add_background_color( $field_key . '_optional_background_hover', array( '.wfacp-product-switch-panel .woocommerce-cart-form__cart-item.cart_item:not(.wfacp-selected-product):hover' ), '#fbfbfb', __( 'Background Hover Color' ) );

			$this->product_switching_saving_text( $field_key . '_non_selected' );

			/* Border */
			$this->add_border( $field_key . '_optional_border', array( '#wfacp-e-form .wfacp_main_form.woocommerce .woocommerce-cart-form__cart-item.cart_item:not(.wfacp-selected-product)' ), $fields_options );

			// Best value Controls

			if ( true === WFACP_Common::is_best_value_available() ) {
				$this->add_heading( __( 'Best Value' ) );
				$selector = array(
					'#wfacp-e-form .wfacp_main_form.woocommerce #product_switching_field fieldset .wfacp_best_value',
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_best_value.wfacp_top_left_corner',
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_best_value.wfacp_top_right_corner',
				);

				/* Best Value: Color Setting */
				$this->add_typography( $field_key . '_best_value_typography', array( '#wfacp-e-form .wfacp_main_form.woocommerce #product_switching_field fieldset .wfacp_best_value' ) );
				$this->add_color( $field_key . '_best_value_text_color', $selector );
				$this->add_background_color( $field_key . '_best_value_bg_color', $selector, '', __( 'Background Color' ) );

				$this->add_border_color( '_best_value_border_color', array( '#wfacp-e-form .wfacp_main_form .shop_table.wfacp-product-switch-panel .woocommerce-cart-form__cart-item.cart_item.wfacp_best_val_wrap' ), '', __( 'Best Value Item Border Color' ) );

				/* Typography */
				$this->add_border( $field_key . '_best_value_border', $selector );
			}

			if ( true === WFACP_Common::is_what_included_available() ) {
				$this->add_heading( __( 'Custom Product Description' ) );

				/* Section Heading Setting */
				$what_included_heading_opt = array(
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_whats_included h3',
				);
				$this->add_heading( __( 'Heading' ) );
				$this->add_typography( $field_key . '_what_included_heading', $what_included_heading_opt );
				$this->add_color( $field_key . '_what_included_heading_color', $what_included_heading_opt );

				/* Product Title Setting */
				$this->add_heading( __( 'Title' ) );
				$this->add_typography( $field_key . '_what_included_product_title', array( '#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_whats_included .wfacp_product_switcher_description h4' ) );
				$this->add_color( $field_key . '_what_included_product_title_color', array( '#wfacp-e-form .wfacp_whats_included .wfacp_product_switcher_description h4' ), '#666666' );

				/* Product Description Setting */
				$this->add_heading( __( 'Description' ) );
				$fields_options = array(
					'font_weight' => array(
						'default' => '400',
					),
				);

				$description_typo = array(
					' #wfacp-e-form .wfacp_main_form.woocommerce .wfacp_whats_included .wfacp_product_switcher_description .wfacp_description p',
					' #wfacp-e-form .wfacp_main_form.woocommerce .wfacp_whats_included .wfacp_product_switcher_description .wfacp_description a',
					' #wfacp-e-form .wfacp_main_form.woocommerce .wfacp_whats_included .wfacp_product_switcher_description .wfacp_description',
				);
				$this->add_typography( $field_key . '_what_included_product_description', $description_typo, $fields_options );
				$this->add_color( $field_key . '_what_included_product_title_description', $description_typo, '#6c6c6c' );

				$this->add_heading( __( 'Advanced' ) );
				$advance_typo = array(
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_whats_included',
				);
				$this->add_background_color( $field_key . '_what_included_bg', $advance_typo, '', __( 'Background Color' ) );

				$fields_options = array(
					'width'  => array(
						'top'    => 1,
						'bottom' => 1,
						'left'   => 1,
						'right'  => 1,
					),
					'style'  => 'solid',
					'radius' => array(
						'top'    => 1,
						'right'  => 1,
						'bottom' => 1,
						'left'   => 1,
						'unit'   => 'px',
					),
					'color'  => array(
						'hex' => '#efefef',
					),
				);

				$this->add_border( $field_key . '_what_included_border', array( '#wfacp-e-form .wfacp_main_form.woocommerce .wfacp_whats_included' ), $fields_options );

				$description = __( 'Note: Add this CSS class <strong>"wfacp_for_mb_style"</strong> here if your checkout page width is less than 375px on desktop browser' );

				$this->add_text( 'product_switcher_mobile_style', __( 'CSS Class' ), '', array(), $description );
			}
		}

		/**
		 * Sets the styling for the product switching saving text.
		 *
		 * This method sets the font size and color for the saving text in the product switching section of the checkout form.
		 *
		 * @param string $field_key The field key.
		 *
		 * @return void
		 */
		protected function product_switching_saving_text( $field_key ) {
			if ( false !== strpos( $field_key, '_non_selected' ) ) {
				$save_text_color_option = array(
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_you_save_text',
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_you_save_text span',
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_you_save_text span',
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_you_save_text span bdi',
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_switcher_col_2 .wfacp_product_subs_details > span:not(.subscription-details):not(.woocommerce-Price-amount):not(.woocommerce-Price-currencySymbol)',
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_switcher_col_2 .wfacp_product_subs_details lebel',
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_product_switcher_col_2 .wfacp_product_subs_details span:not(.subscription-details):not(.woocommerce-Price-amount):not(.woocommerce-Price-currencySymbol)',
				);

				$typography = array(
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_you_save_text',
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_you_save_text span',
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel fieldset:not(.wfacp-selected-product) .wfacp_you_save_text span bdi',
				);
			} else {
				$save_text_color_option = array(
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_you_save_text',
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_you_save_text span',
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_you_save_text span',
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_you_save_text span bdi',
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_product_switcher_col_2 .wfacp_product_subs_details > span:not(.subscription-details):not(.woocommerce-Price-amount):not(.woocommerce-Price-currencySymbol)',
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_product_switcher_col_2 .wfacp_product_subs_details lebel',
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_product_switcher_col_2 .wfacp_product_subs_details span:not(.subscription-details):not(.woocommerce-Price-amount):not(.woocommerce-Price-currencySymbol)',
				);

				$typography = array(
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_you_save_text',
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_you_save_text span',
					'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-product-switch-panel .wfacp-selected-product .wfacp_you_save_text span bdi',
				);
			}

			$this->add_heading( __( 'Saving Text' ) );

			$this->add_typography( $field_key . '_you_save_typo', $typography, array(), array(), '', array( 'color', 'text-align' ) );
			$this->add_color( $field_key . '_you_save_color', $save_text_color_option, '#b22323' );
		}

		/**
		 * Adds an icon and text for a specific step in the funnel builder.
		 *
		 * @param int $counter_step The step counter.
		 *
		 * @return void
		 */
		public function icon_text( $counter_step ) {
			$this->add_text( 'step_' . $counter_step . '_text_after_place_order', __( ' Sub Text' ), '', array() );

			$icon_list = array(
				'aero-e902' => __( 'Arrow 1' ),
				'aero-e906' => __( 'Arrow 2' ),
				'aero-e907' => __( 'Arrow 3' ),
				'aero-e908' => __( 'Checkmark' ),
				'aero-e905' => __( 'Cart 1' ),
				'aero-e901' => __( 'Lock 1' ),
				'aero-e900' => __( 'Lock 2' ),
			);

			$bwf_icon_list = apply_filters( 'bwf_icon_list', $icon_list );

			$this->add_switcher( 'enable_icon_with_place_order_' . $counter_step, __( 'Enable Icon' ), false );

			$condition = array(
				'enable_icon_with_place_order_' . $counter_step,
				'=',
				true,
			);

			$this->add_select( 'icons_with_place_order_list_' . $counter_step, __( 'Select Icons Style' ), $bwf_icon_list, 'aero-e901', $condition );
		}

		/**
		 * Adds the styling options for the order summary section of the checkout form.
		 *
		 * This method sets up various styling options for the order summary section of the checkout form,
		 * including color, typography, and border settings.
		 *
		 * @param string $field_key The field key.
		 *
		 * @return void
		 */
		protected function order_summary( $field_key ) {
			$this->set_current_group( 'styleOrderSummary' );
			$this->add_heading( 'Product' );


			$cart_item_color = [

				'#wfacp-e-form  table.shop_table tbody .wfacp_order_summary_item_name',
				'#wfacp-e-form  table.shop_table tbody .cart_item  .product-quantity',
				'#wfacp-e-form  table.shop_table tbody .cart_item .product-total > span bdi',
				'#wfacp-e-form  table.shop_table tbody .cart_item .product-total > span bdi span',
				'#wfacp-e-form  table.shop_table tbody .cart_item .product-total > ins > span.amount',
				'#wfacp-e-form  table.shop_table tbody .cart_item .product-total > ins bdi',
				'#wfacp-e-form  table.shop_table tbody .cart_item .product-total > ins span',
				'#wfacp-e-form  table.shop_table tbody .cart_item .wfacp_order_summary_item_total > span bdi',
				'#wfacp-e-form  table.shop_table tbody .cart_item .wfacp_order_summary_item_total > span bdi span',
                '#wfacp-e-form .wfacp_main_form  .wfacp_order_summary_item_total table.shop_table tbody .wfacp_order_summary_item_total > span.amount bdi ',
                '#wfacp-e-form .wfacp_main_form .shop_table tbody .cart_item .product-total .amount bdi ',
				'#wfacp-e-form  table.shop_table tbody .cart_item .wfacp_order_summary_item_total > ins > span.amount',
				'#wfacp-e-form  table.shop_table tbody .cart_item .wfacp_order_summary_item_total > ins bdi',
				'#wfacp-e-form  table.shop_table tbody .cart_item .wfacp_order_summary_item_total > ins span',
				'#wfacp-e-form  table.shop_table tbody .cart_item .product-total small',
				'#wfacp-e-form  table.shop_table tbody .cart_item .product-name-area dl',
				'#wfacp-e-form  table.shop_table tbody .cart_item .product-name-area dd',
				'#wfacp-e-form  table.shop_table tbody .cart_item .product-name-area dt',
				'#wfacp-e-form  table.shop_table tbody .cart_item .product-name-area p',
				'#wfacp-e-form  table.shop_table tbody tr td:not(.product-total) span:not(.wfacp-pro-count)',
			];


			$this->add_typography( $field_key . '_cart_item_typo', $cart_item_color );

			$this->add_color( $field_key . '_cart_item_color', $cart_item_color, '#666666' );

			$border_image_color = array( '#wfacp-e-form table.shop_table tr.cart_item .product-image img' );
			$this->add_border_color( 'mini_product_image_border_color', $border_image_color, '', __( 'Image Border Color' ), false, array( 'order_summary_enable_product_image', '=', true ) );

			$this->add_border_radius( $field_key . '_cart_item_image_border_radius', $border_image_color );


			if(true === wfacp_pro_dependency()){


			/**
			 * Strike Through Style Setting
			 */

			$selector            = '#wfacp-e-form #wfacp-sec-wrapper';
			$strike_through_typo = [
				$selector . ' .product-total del',
				$selector . ' .product-total del *',
				$selector . ' .product-total del span.woocommerce-Price-currencySymbol',
			];

			$this->add_typography( $field_key . '_strike_through_typo', $strike_through_typo, '', '', esc_html__( 'Strike Through' ) );

			/**
			 * Low Stock Message Style Setting
			 */
			$low_stock_message = [
				$selector . ' .wfacp_stocks',
			];
			$this->add_typography( $field_key . '_low_stock_message_typo', $low_stock_message, '', '', esc_html__( 'Low Stock' ) );

			/**
			 * Saved Price Setting
			 *
			 */
			$saving_price_message = [
				$selector . ' table.shop_table tr:not(.order-total):not(.cart-discount).wfacp-saving-amount td',
				$selector . ' table.shop_table tr:not(.order-total):not(.cart-discount).wfacp-saving-amount td svg path',
				$selector . ' table.shop_table tr:not(.order-total):not(.cart-discount).wfacp-saving-amount td *',
				$selector . ' table.shop_table tr:not(.order-total):not(.cart-discount).wfacp-saving-amount td span *',
			];

			$this->add_typography( $field_key . '_saving_price_message_typo', $saving_price_message, '', '', esc_html__( 'Save Price Typography' ) );

			}
			/**
			 * Subtotal Style Setting
			 */
			$this->add_heading( __( 'Subtotal' ) );

			$cart_subtotal_color_option = array(
				'#wfacp-e-form  table.shop_table tfoot tr:not(.order-total):not(.cart-discount)',
				'#wfacp-e-form  table.shop_table tfoot tr:not(.order-total):not(.cart-discount) td',
				'#wfacp-e-form  table.shop_table tfoot tr:not(.order-total):not(.cart-discount) th',
				'#wfacp-e-form  table.shop_table tfoot tr:not(.order-total):not(.cart-discount) th span',
				'#wfacp-e-form  table.shop_table tfoot tr:not(.order-total):not(.cart-discount) td span',
				'#wfacp-e-form  table.shop_table tfoot tr:not(.order-total):not(.cart-discount) td small',
				'#wfacp-e-form  table.shop_table tfoot tr:not(.order-total):not(.cart-discount) td bdi',
				'#wfacp-e-form  table.shop_table tfoot tr:not(.order-total):not(.cart-discount) td a',
			);

			$fields_options = array(
				'font_weight' => '400',
			);

			$this->add_typography( 'order_summary_product_meta_typo', $cart_subtotal_color_option );
			$this->add_color( 'order_summary_product_meta_color', $cart_subtotal_color_option );

			/* ------------------------------------ Coupon Start------------------------------------ */

			$this->add_heading( __( 'Coupon code' ) );
			$coupon_selector = array(
				'#wfacp-e-form  table.shop_table tfoot tr.cart-discount th',
				'#wfacp-e-form  table.shop_table tfoot tr.cart-discount th span',
				'#wfacp-e-form  table.shop_table tfoot tr.cart-discount td',
				'#wfacp-e-form  table.shop_table tfoot tr.cart-discount td span',
				'#wfacp-e-form  table.shop_table tfoot tr.cart-discount td a',
			);

			$this->add_font_size( $field_key . '_display_font_size', $coupon_selector, __( 'Font Size (in px)' ), '14px', array() );

			$coupon_selector_label_color = array(
				'#wfacp-e-form table.shop_table tfoot tr.cart-discount th',
				'#wfacp-e-form table.shop_table tfoot tr.cart-discount th span:not(.wfacp_coupon_code)',
			);
			$this->add_color( $field_key . '_display_label_color', $coupon_selector_label_color, '', __( 'Text Color' ) );
			$coupon_selector_val_color = array(
				'#wfacp-e-form table.shop_table tfoot tr.cart-discount td',
				'#wfacp-e-form table.shop_table tfoot tr.cart-discount td span',
				'#wfacp-e-form table.shop_table tfoot tr.cart-discount td a',
				'#wfacp-e-form table.shop_table tfoot tr.cart-discount td span',
				'#wfacp-e-form table.shop_table tfoot tr.cart-discount td span bdi',
				'#wfacp-e-form table.shop_table tfoot tr.cart-discount th .wfacp_coupon_code',
			);
			$this->add_color( $field_key . '_display_val_color', $coupon_selector_val_color, '#24ae4e', __( 'Code Color' ) );

			/* ------------------------------------ End ------------------------------------ */

			$cart_total_color_option = array(
				'#wfacp-e-form  table.shop_table tfoot tr.order-total th',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td span.woocommerce-Price-amount.amount',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td span.woocommerce-Price-amount.amount bdi',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td p',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td span',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td span',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td small',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td a',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td p',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total th',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total th span',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total th small',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total th a',
			);

			$cart_total_label_typo_option = array(
				'#wfacp-e-form  table.shop_table tfoot tr.order-total th',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total th',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total th span',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total th small',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total th a',
			);
			$cart_total_value_typo_option = array(
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td span.woocommerce-Price-amount.amount',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td span.woocommerce-Price-amount.amount bdi',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td p',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td span',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td span',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td small',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td a',
				'#wfacp-e-form  table.shop_table tfoot tr.order-total td p',
			);

			$this->add_heading( 'Total' );

			$this->add_typography( $field_key . '_cart_total_label_typo', $cart_total_label_typo_option, $fields_options, array(), __( 'Label Typography' ), array( 'text-align', 'color' ) );
			$this->add_typography( $field_key . '_cart_subtotal_heading_typo', $cart_total_value_typo_option, $fields_options, array(), __( 'Price Typography' ), array( 'text-align', 'color' ) );
			$this->add_color( $field_key . '_cart_subtotal_heading_color', $cart_total_color_option, '' );

			$this->add_heading( __( 'Divider' ) );
			$divider_line_color = array(
				'#wfacp-e-form  table.shop_table tbody .wfacp_order_summary_item_name',
				'#wfacp-e-form table.shop_table tr.cart_item',
				'#wfacp-e-form table.shop_table tr.cart-subtotal',
				'#wfacp-e-form table.shop_table tr.order-total',
			);

			$this->add_border_color( $field_key . '_divider_line_color', $divider_line_color, '' );
		}

		/**
		 * Adds the styling options for the coupon field of the checkout form.
		 *
		 * This method sets up various styling options for the coupon field of the checkout form,
		 * including color, typography, and border settings.
		 *
		 * @param string $field_key The field key.
		 *
		 * @return void
		 */
		protected function order_coupon( $field_key ) {
			$this->coupon_field_settings( $field_key );
		}

		/**
		 * Sets the settings for the coupon field.
		 *
		 * @param string $field_key The key of the coupon field.
		 *
		 * @return void
		 */
		protected function coupon_field_settings( $field_key ) {
			$this->coupon_field_style( $field_key );
		}

		/**
		 * Adds the styling options for the coupon field of the checkout form.
		 *
		 * This method sets up various styling options for the coupon field of the checkout form,
		 * including color, typography, and border settings.
		 *
		 * @param string $field_key The field key.
		 *
		 * @return void
		 */
		protected function coupon_field_style( $field_key ) {
			$this->set_current_group( 'styleCoupon' );
			$this->add_heading( __( 'Link' ), '' );
			$coupon_typography_opt = array(
				'#wfacp-e-form .wfacp-coupon-section .wfacp-coupon-page .woocommerce-info > a',
				'#wfacp-e-form .wfacp-coupon-section .wfacp-coupon-page .woocommerce-info > a:not(.wfacp_close_icon):not(.button-social-login):not(.wfob_btn_add):not(.ywcmas_shipping_address_button_new):not(.wfob_qv-button):not(.wfob_read_more_link):not(.wfacp_step_text_have ):not(.wfacp_cart_link)',
			);

			$this->add_typography( $field_key . '_coupon_typography', $coupon_typography_opt, array(), array(), __( 'Typography' ), array( 'color' ) );
			$this->add_color( $field_key . '_coupon_text_color', $coupon_typography_opt );

			$this->add_heading( __( 'Field' ) );

			$form_fields_label_typo = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-coupon-section .wfacp-coupon-page p.wfacp-form-control-wrapper label.wfacp-form-control-label',
			);

			$fields_options = array(
				'font_weight' => '400',
			);

			$this->add_typography( $field_key . '_label_typo', $form_fields_label_typo, $fields_options, array(), __( 'Label Typography' ), array( 'text-align', 'color' ) );
			$this->add_color( $field_key . '_label_color', $form_fields_label_typo, '', __( 'Label Color' ) );

			$form_fields_coupon_typo = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-coupon-section .wfacp-coupon-page p.wfacp-form-control-wrapper .wfacp-form-control',
			);

			$this->add_typography( $field_key . '_input_typo', $form_fields_coupon_typo, array(), array(), __( 'Coupon Typography' ), array( 'text-align', 'color' ) );
			$this->add_color( $field_key . '_input_color', $form_fields_coupon_typo, '', __( 'Coupon Color' ) );

			$focus_color = array( '#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-coupon-section .wfacp-coupon-page p.wfacp-form-control-wrapper .wfacp-form-control:focus' );
			$this->add_border_color( $field_key . '_focus_color', $focus_color, '#61bdf7', __( 'Focus Color' ), true );

			$default = array(
				'radius' => array(
					'top'    => 4,
					'right'  => 4,
					'bottom' => 4,
					'left'   => 4,
				),
			);

			$this->add_border( $field_key . '_coupon_border', $form_fields_coupon_typo, $default );

			$this->add_heading( __( 'Button' ) );

			/* Button color setting */
			$btnkey = array(
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-coupon-section .wfacp-coupon-page .wfacp-coupon-field-btn',
				'#wfacp-e-form .wfacp_main_form.woocommerce .wfacp-coupon-section .wfacp-coupon-page .wfacp-coupon-btn',
			);

			$this->add_background_color( $field_key . '_btn_bg_color', $btnkey, '', __( 'Background' ) );
			$this->add_color( $field_key . '_btn_text_color', $btnkey, '', __( 'Label' ) );

			$this->add_typography( $field_key . '_btn_typo', $btnkey, array(), array(), __( 'Button Typography' ) );
			/* Button color setting End*/
		}

		/**
		 * Renders the checkout form.
		 *
		 * This method is responsible for rendering the checkout form on the frontend.
		 * It includes a div element with a height of 1px, sets the form data, and includes the form template.
		 *
		 * @return void
		 */
		public function render() {
			if ( ! wp_doing_ajax() && is_admin() ) {
				return;
			}

			$id       = uniqid();
			$settings = $this->settings;

			\Bricks\Woocommerce_Helpers::maybe_init_cart_context();

			\FunnelKit\Bricks_Integration::set_locals( 'wfacp_form', $id );
			WFACP_Common::set_session( $id, $settings );

			$template = wfacp_template();

			if ( null === $template ) {
				return;
			}

			if ( WFACP_Common::is_theme_builder() ) {
				do_action( 'wfacp_mini_cart_widgets_elementor_editor', $this );
			}

			$template->set_form_data( $settings );

			?>
            <div <?php echo $this->render_attributes( '_root' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                <div style="height: 1px"></div>
				<?php include $template->wfacp_get_form(); ?>
            </div>
			<?php
		}
	}
}