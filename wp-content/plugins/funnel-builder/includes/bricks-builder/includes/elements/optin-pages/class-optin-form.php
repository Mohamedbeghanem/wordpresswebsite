<?php

namespace FunnelKit\Bricks\Elements\OptinPages;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use WFFN_Optin_Form_Controller_Custom_Form;

if ( ! class_exists( '\FunnelKit\Bricks\Elements\OptinPages\Optin_Form' ) ) {
	class Optin_Form extends \Bricks\Element {
		public $category = 'funnelkit';
		public $name = 'wffn-optin-form';
		public $icon = 'ti-layout-cta-left';
		public $scripts = array( 'triggerPhoneFieldReload' );

		/**
		 * Retrieves the label for the "Optin Form" element.
		 *
		 * @return string The label for the "Optin Form" element.
		 */
		public function get_label() {
			return esc_html__( 'Optin Form' );
		}

		/**
		 * Sets the control groups for the OptinForm class.
		 *
		 * This method initializes the control groups array for the OptinForm class.
		 * It sets two control groups: 'contentForm' and 'styleForm', with their respective titles and tabs.
		 * It also calls the 'set_common_control_groups' method to set any common control groups.
		 * Finally, it removes the '_typography' control group from the control groups array.
		 *
		 * @return void
		 */
		public function set_control_groups() {
			$this->control_groups = array();

			$this->control_groups['contentForm'] = array(
				'title' => esc_html__( 'Form' ),
				'tab'   => 'content',
			);

			$this->control_groups['styleForm'] = array(
				'title' => esc_html__( 'Form' ),
				'tab'   => 'style',
			);

			$this->set_common_control_groups();

			unset( $this->control_groups['_typography'] );
		}

		/**
		 * Set the controls for the optin form.
		 *
		 * This method retrieves the optin page ID and gets the form fields for that optin page.
		 * It then defines an options array for the form field width and loops through the form fields to set the controls.
		 *
		 * @return void
		 */
		public function set_controls() {
			$this->controls['_alignSelf']['default'] = 'stretch';

			$optinPageId = WFOPP_Core()->optin_pages->get_optin_id();
			$get_fields  = array();
			if ( $optinPageId > 0 ) {
				$get_fields = WFOPP_Core()->optin_pages->form_builder->get_form_fields( $optinPageId );
			}

			// Define the options array
			$options = array(
				'wffn-sm-100' => __( 'Full' ),
				'wffn-sm-50'  => __( 'One Half' ),
				'wffn-sm-33'  => __( 'One Third' ),
				'wffn-sm-67'  => __( 'Two Third' ),
			);

			// Ensure $get_fields is an array before looping
			foreach ( (array) $get_fields as $field ) {
				$default = isset( $field['width'] ) ? $field['width'] : 'wffn-sm-100';

				$this->controls[ $field['InputName'] ] = array(
					'group'   => 'contentForm',
					'label'   => $field['label'],
					'type'    => 'select',
					'options' => $options,
					'default' => $default,
				);
			}

			$this->controls['show_labels'] = array(
				'group'   => 'contentForm',
				'label'   => esc_html__( 'Show Label' ),
				'type'    => 'checkbox',
				'default' => true,
			);

			$this->controls['separatorContentSubmitButton'] = array(
				'group' => 'contentForm',
				'label' => esc_html__( 'Submit Button' ),
				'type'  => 'separator',
			);

			$this->controls['button_text'] = array(
				'group'       => 'contentForm',
				'label'       => esc_html__( 'Title' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Send Me My Free Guide' ),
				'placeholder' => esc_html__( 'Enter the Button Text' ),
			);

			$this->controls['subtitle'] = array(
				'group'       => 'contentForm',
				'label'       => esc_html__( 'Sub Title' ),
				'type'        => 'text',
				'placeholder' => esc_html__( 'Enter subtitle' ),
			);

			$this->controls['button_submitting_text'] = array(
				'group'   => 'contentForm',
				'label'   => esc_html__( 'Submitting Text' ),
				'type'    => 'text',
				'default' => esc_html__( 'Submitting...' ),
			);

			$this->controls['separatorSpacing'] = array(
				'group' => 'styleForm',
				'label' => esc_html__( 'Spacing' ),
				'type'  => 'separator',
			);

			$this->controls['column_gap'] = array(
				'group'   => 'styleForm',
				'label'   => esc_html__( 'Columns' ),
				'type'    => 'slider',
				'css'     => array(
					array(
						'property' => 'padding-right',
						'selector' => '.bwfac_form_sec',
						'value'    => 'calc( %s/2 )',
					),
					array(
						'property' => 'padding-left',
						'selector' => '.bwfac_form_sec',
						'value'    => 'calc( %s/2 )',
					),
					array(
						'property' => 'margin-right',
						'selector' => '.bricks-form-fields-wrapper',
						'value'    => 'calc( -%s/2 )',
					),
					array(
						'property' => 'margin-left',
						'selector' => '.bricks-form-fields-wrapper',
						'value'    => 'calc( -%s/2 )',
					),
				),
				'units'   => array(
					'px' => array(
						'min'  => 0,
						'max'  => 60,
						'step' => 1,
					),
				),
				'default' => '10px',
			);

			$this->controls['row_gap'] = array(
				'group'   => 'styleForm',
				'label'   => esc_html__( 'Rows' ),
				'type'    => 'slider',
				'css'     => array(
					array(
						'property' => 'margin-bottom',
						'selector' => '.bwfac_form_sec',
					),
				),
				'units'   => array(
					'px' => array(
						'min'  => 0,
						'max'  => 60,
						'step' => 1,
					),
				),
				'default' => '10px',
			);

			$this->controls['label_spacing'] = array(
				'group'    => 'styleForm',
				'label'    => esc_html__( 'Label' ),
				'type'     => 'slider',
				'css'      => array(
					array(
						'property' => 'margin-top',
						'selector' => '.bwfac_form_sec .wfop_input_cont',
					),
				),
				'units'    => array(
					'px' => array(
						'min'  => 0,
						'max'  => 60,
						'step' => 1,
					),
				),
				'default'  => '0px',
			);

			$this->controls['separatorLabel'] = array(
				'group' => 'styleForm',
				'label' => esc_html__( 'Label' ),
				'type'  => 'separator',
			);

			$this->controls['label_color'] = array(
				'group'    => 'styleForm',
				'label'    => esc_html__( 'Text' ),
				'type'     => 'color',
				'inline'   => true,
				'css'      => array(
					array(
						'property' => 'color',
						'selector' => '.bwfac_form_sec > label, .bwfac_form_sec .wfop_input_cont > label',
					),
				),
			);

			$this->controls['mark_required_color'] = array(
				'group'    => 'styleForm',
				'label'    => esc_html__( 'Asterisk' ),
				'type'     => 'color',
				'css'      => array(
					array(
						'property' => 'color',
						'selector' => '.bwfac_form_sec > label > span, .bwfac_form_sec .wfop_input_cont > label > span',
					),
				),
			);

			$this->controls['label_typography'] = array(
				'group'    => 'styleForm',
				'label'    => esc_html__( 'Typography' ),
				'type'     => 'typography',
				'exclude'  => array( 'color' ), //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				'css'      => array(
					array(
						'property' => 'typography',
						'selector' => '.bwfac_form_sec > label, .bwfac_form_sec .wfop_input_cont > label',
					),
				),
				'inline'   => true,
			);

			$this->controls['separatorInput'] = array(
				'group' => 'styleForm',
				'label' => esc_html__( 'Input' ),
				'type'  => 'separator',
			);

			$this->controls['field_text_color'] = array(
				'group'   => 'styleForm',
				'label'   => esc_html__( 'Text' ),
				'type'    => 'color',
				'inline'  => true,
				'css'     => array(
					array(
						'property' => 'color',
						'selector' => '.bwfac_form_sec .wffn-optin-input, .bwfac_form_sec .wffn-optin-input::placeholder',
					),
				),
				'default' => array(
					'hex' => '#3F3F3F',
				),
			);

			$this->controls['field_typography'] = array(
				'group'   => 'styleForm',
				'label'   => esc_html__( 'Typography' ),
				'type'    => 'typography',
				'exclude' => array( 'color' ), //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				'default' => array(
					'font-family' => 'Open Sans',
					'font-weight' => '400',
					'font-size'   => '16px',
				),
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '.bwfac_form_sec .wffn-optin-input',
					),
				),
			);

			$this->controls['field_background_color'] = array(
				'group'   => 'styleForm',
				'label'   => esc_html__( 'Background' ),
				'type'    => 'color',
				'default' => array(
					'hex' => '#ffffff',
				),
				'css'     => array(
					array(
						'property' => 'background-color',
						'selector' => '.bwfac_form_sec .wffn-optin-input',
					),
				),
			);

			$this->controls['input_size'] = array(
				'group'   => 'styleForm',
				'label'   => esc_html__( 'Field Size' ),
				'type'    => 'select',
				'options' => self::get_input_fields_sizes(),
				'default' => '12px',
				'inline'  => true,
				'css'     => array(
					array(
						'property' => 'padding-top',
						'selector' => '.wffn-custom-optin-from .wffn-optin-input',
					),
					array(
						'property' => 'padding-bottom',
						'selector' => '.wffn-custom-optin-from .wffn-optin-input',
					),
					array(
						'property' => 'line-height',
						'selector' => '.wffn-custom-optin-from .wffn-optin-input',
						'value'    => 'normal',
					),
				),
			);

			$this->controls['field_border'] = array(
				'group'   => 'styleForm',
				'label'   => esc_html__( 'Border' ),
				'type'    => 'border',
				'css'     => array(
					array(
						'property' => 'border',
						'selector' => '.bwfac_form_sec .wffn-optin-input',
					),
				),
				'inline'  => true,
				'small'   => true,
				'default' => array(
					'width' => array(
						'top'    => 2,
						'right'  => 2,
						'bottom' => 2,
						'left'   => 2,
					),
					'style' => 'solid',
					'color' => array(
						'hex' => '#d8d8d8',
					),
				),
			);

			$this->controls['separatorStyleSubmitButton'] = array(
				'group' => 'styleForm',
				'label' => esc_html__( 'Submit Button' ),
				'type'  => 'separator',
			);

			$this->controls['button_text_typo'] = array(
				'group'   => 'styleForm',
				'label'   => esc_html__( 'Heading' ),
				'type'    => 'typography',
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '.bwfac_form_sec #wffn_custom_optin_submit .bwf_heading',
					),
				),
				'inline'  => true,
				'default' => array(
					'color' => array(
						'hex' => '#ffffff',
					),
				),
			);

			$this->controls['button_subheading_text_typo'] = array(
				'group'   => 'styleForm',
				'label'   => esc_html__( 'Sub Heading' ),
				'type'    => 'typography',
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '.bwfac_form_sec #wffn_custom_optin_submit .bwf_subheading',
					),
				),
				'inline'  => true,
				'default' => array(
					'color' => array(
						'hex' => '#ffffff',
					),
				),
			);

			$this->controls['button_bg_color'] = array(
				'group'   => 'styleForm',
				'label'   => esc_html__( 'Background' ),
				'type'    => 'color',
				'default' => array(
					'hex' => '#FBA506',
				),
				'css'     => array(
					array(
						'property' => 'background-color',
						'selector' => '.bwfac_form_sec #wffn_custom_optin_submit',
					),
				),
			);

			$this->controls['bwf_button_border'] = array(
				'group'   => 'styleForm',
				'label'   => esc_html__( 'Border' ),
				'type'    => 'border',
				'css'     => array(
					array(
						'property' => 'border',
						'selector' => '.bwfac_form_sec #wffn_custom_optin_submit',
					),
				),
				'inline'  => true,
				'small'   => true,
				'default' => array(
					'width' => array(
						'top'    => 2,
						'right'  => 2,
						'bottom' => 2,
						'left'   => 2,
					),
					'style' => 'solid',
					'color' => array(
						'hex' => '#E69500',
					),
				),
			);

			$this->controls['button_text_alignment_box_shadow'] = array(
				'group'  => 'styleForm',
				'label'  => esc_html__( 'Box Shadow' ),
				'type'   => 'box-shadow',
				'css'    => array(
					array(
						'property' => 'box-shadow',
						'selector' => '.bwfac_form_sec #wffn_custom_optin_submit',
					),
				),
				'inline' => true,
				'small'  => true,
			);

			$this->controls['button_width'] = array(
				'group'   => 'styleForm',
				'label'   => esc_html__( 'Button width (in %)' ),
				'type'    => 'slider',
				'css'     => array(
					array(
						'property' => 'min-width',
						'selector' => '.bwfac_form_sec #wffn_custom_optin_submit',
					),
				),
				'units'   => array(
					'%' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'default' => '100%',
			);

			$this->controls['button_alignment'] = array(
				'group'   => 'styleForm',
				'label'   => esc_html__( 'Alignment' ),
				'type'    => 'text-align',
				'exclude' => array( 'justify' ), //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				'css'     => array(
					array(
						'property' => 'text-align',
						'selector' => '.wffn-custom-optin-from #bwf-custom-button-wrap',
					),
				),
			);

			$this->controls['button_text_alignment'] = array(
				'group'   => 'styleForm',
				'label'   => esc_html__( 'Text Alignment' ),
				'type'    => 'text-align',
				'exclude' => array( 'justify' ), //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				'css'     => array(
					array(
						'property' => 'text-align',
						'selector' => '.wffn-custom-optin-from #bwf-custom-button-wrap span',
					),
				),
			);

			$this->controls['button_text_padding'] = array(
				'group'   => 'styleForm',
				'label'   => esc_html__( 'Padding' ),
				'type'    => 'spacing',
				'default' => array(
					'top'    => 15,
					'right'  => 15,
					'bottom' => 15,
					'left'   => 15,
				),
				'css'     => array(
					array(
						'property' => 'padding',
						'selector' => '.bwfac_form_sec #wffn_custom_optin_submit',
					),
				),
			);

			$this->controls['button_text_margin'] = array(
				'group'   => 'styleForm',
				'label'   => esc_html__( 'Margin' ),
				'type'    => 'spacing',
				'default' => array(
					'top'    => 15,
					'right'  => 0,
					'bottom' => 25,
					'left'   => 0,
				),
				'css'     => array(
					array(
						'property' => 'margin',
						'selector' => '.bwfac_form_sec #wffn_custom_optin_submit',
					),
				),
			);
		}

		/**
		 * Retrieves an array of input field sizes.
		 *
		 * This method returns an array of input field sizes, where the keys represent the size in pixels and the values represent the corresponding label.
		 *
		 * @return array An associative array of input field sizes.
		 */
		public static function get_input_fields_sizes() {
			return array(
				'6px'  => __( 'Small' ),
				'9px'  => __( 'Medium' ),
				'12px' => __( 'Large' ),
				'15px' => __( 'Extra Large' ),
			);
		}

		/**
		 * Renders the optin form.
		 *
		 * This method is responsible for rendering the optin form based on the provided settings.
		 *
		 * @return void
		 * @since 1.0.0
		 *
		 */
		public function render() {
			$settings                       = $this->settings;
			$settings['button_border_size'] = 0;

			$wrapper_class = 'bricks-form-fields-wrapper';
			$show_labels   = isset( $settings['show_labels'] ) ? true : false;
			$wrapper_class .= $show_labels ? '' : ' wfop_hide_label';

			$optinPageId    = WFOPP_Core()->optin_pages->get_optin_id();
			$optin_fields   = WFOPP_Core()->optin_pages->form_builder->get_optin_layout( $optinPageId );
			$optin_settings = WFOPP_Core()->optin_pages->get_optin_form_integration_option( $optinPageId );

			foreach ( $optin_fields as $step_slug => $optinFields ) {
				foreach ( $optinFields as $key => $optin_field ) {
					$optin_fields[ $step_slug ][ $key ]['width'] = $settings[ $optin_field['InputName'] ];
				}
			}

			?>
            <div <?php echo $this->render_attributes( '_root' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php
				$custom_form = WFOPP_Core()->form_controllers->get_integration_object( 'form' );
				if ( $custom_form instanceof WFFN_Optin_Form_Controller_Custom_Form ) {
					$settings = wp_parse_args( $settings, WFOPP_Core()->optin_pages->form_builder->form_customization_settings_default() );
					$custom_form->_output_form( $wrapper_class, $optin_fields, $optinPageId, $optin_settings, 'inline', $settings );
				}
				?>
            </div>
			<?php
		}
	}
}