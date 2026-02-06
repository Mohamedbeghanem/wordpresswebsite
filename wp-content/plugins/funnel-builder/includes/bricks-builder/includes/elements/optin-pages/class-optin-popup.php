<?php

namespace FunnelKit\Bricks\Elements\OptinPages;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use WFFN_Optin_Form_Controller_Custom_Form;

if ( ! class_exists( '\FunnelKit\Bricks\Elements\OptinPages\Optin_Popup' ) ) {
	class Optin_Popup extends Optin_Form {
		public $category = 'funnelkit';
		public $name = 'wffn-optin-popup';
		public $icon = 'ti-layout-cta-left';
		public $scripts = array( 'triggerPhoneFieldReload', 'triggerPopupsReload' );

		/**
		 * Retrieves the label for the Optin Popup element.
		 *
		 * @return string The label for the Optin Popup element.
		 */
		public function get_label() {
			return esc_html__( 'Optin Popup' );
		}

		/**
		 * Sets the control groups for the Optin Popup class.
		 *
		 * This method initializes the control groups array for the Optin Popup class.
		 * Each control group represents a specific element or style that can be customized.
		 * The control group array contains the title and tab information for each control group.
		 *
		 * @return void
		 */
		public function set_control_groups() {
			$this->control_groups = array();

			$this->control_groups['contentProgressBar'] = array(
				'title' => esc_html__( 'Progress Bar' ),
				'tab'   => 'content',
			);

			$this->control_groups['contentHeading'] = array(
				'title' => esc_html__( 'Heading' ),
				'tab'   => 'content',
			);

			$this->control_groups['contentForm'] = array(
				'title' => esc_html__( 'Form' ),
				'tab'   => 'content',
			);

			$this->control_groups['contentButton'] = array(
				'title' => esc_html__( 'Button' ),
				'tab'   => 'content',
			);

			$this->control_groups['contentPopup'] = array(
				'title' => esc_html__( 'Popup' ),
				'tab'   => 'content',
			);

			$this->control_groups['styleProgressBar'] = array(
				'title' => esc_html__( 'Progress Bar' ),
				'tab'   => 'style',
			);

			$this->control_groups['styleHeading'] = array(
				'title' => esc_html__( 'Heading' ),
				'tab'   => 'style',
			);

			$this->control_groups['styleForm'] = array(
				'title' => esc_html__( 'Form' ),
				'tab'   => 'style',
			);

			$this->control_groups['styleButton'] = array(
				'title' => esc_html__( 'Button' ),
				'tab'   => 'style',
			);

			$this->control_groups['stylePopup'] = array(
				'title' => esc_html__( 'Popup' ),
				'tab'   => 'style',
			);

			$this->control_groups['styleCloseButton'] = array(
				'title' => esc_html__( 'Close Button' ),
				'tab'   => 'style',
			);

			$this->set_common_control_groups();

			unset( $this->control_groups['_typography'] );
		}

		/**
		 * Set the controls for the Optin Popup class.
		 *
		 * This method sets the default values and options for the controls used in the Optin Popup class.
		 *
		 * @return void
		 */
		public function set_controls() {
			parent::set_controls();

			// Add the 'popup_bar_pp' control
			$this->controls['popup_bar_pp'] = array(
				'group'   => 'contentProgressBar',
				'label'   => esc_html__( 'Enable' ),
				'type'    => 'checkbox',
				'default' => true,
			);

			// Add the 'popup_bar_text_position' control
			$this->controls['popup_bar_text_position'] = array(
				'group'    => 'contentProgressBar',
				'label'    => esc_html__( 'Show progress text above the bar' ),
				'type'     => 'checkbox',
				'default'  => true,
				'css'      => array(
					array(
						'property' => 'display',
						'selector' => '.bwf_pp_overlay .bwf_pp_bar_wrap .bwf_pp_bar .pp-bar-text',
						'value'    => 'none',
					),
					array(
						'property' => 'display',
						'selector' => '.bwf_pp_overlay .pp-bar-text-wrapper',
						'value'    => 'block',
					),
				),
				'required' => array( 'popup_bar_pp', '=', true ),
			);

			$this->controls['popup_bar_animation'] = array(
				'group'    => 'contentProgressBar',
				'label'    => esc_html__( 'Animation' ),
				'type'     => 'checkbox',
				'default'  => true,
				'required' => array( 'popup_bar_pp', '=', true ),
			);

			$this->controls['popup_bar_text'] = array(
				'group'    => 'contentProgressBar',
				'label'    => esc_html__( 'Text' ),
				'type'     => 'text',
				'default'  => esc_html__( '75% Complete' ),
				'required' => array( 'popup_bar_pp', '=', true ),
			);

			$this->controls['popup_heading'] = array(
				'group'   => 'contentHeading',
				'label'   => esc_html__( 'Heading' ),
				'type'    => 'textarea',
				'default' => __( 'You\'re just one step away!' ),
			);

			$this->controls['popup_sub_heading'] = array(
				'group'   => 'contentHeading',
				'label'   => esc_html__( 'Sub Heading' ),
				'type'    => 'textarea',
				'default' => __( 'Enter your details below and we\'ll get you signed up' ),
			);

			$this->controls['btn_text'] = array(
				'group'   => 'contentButton',
				'label'   => esc_html__( 'Title' ),
				'type'    => 'text',
				'default' => esc_html__( 'Signup Now' ),
			);

			$this->controls['btn_subheading_text'] = array(
				'group' => 'contentButton',
				'label' => esc_html__( 'Subtitle' ),
				'type'  => 'text',
			);

			$this->controls['btn_alignment'] = array(
				'group'   => 'contentButton',
				'label'   => esc_html__( 'Button Alignment' ),
				'type'    => 'text-align',
				'exclude' => array( 'justify' ), //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				'default' => 'center',
				'css'     => array(
					array(
						'property' => 'text-align',
						'selector' => '#bwf-custom-button-wrap',
					),
				),
			);

			$this->controls['btn_text_alignment'] = array(
				'group'   => 'contentButton',
				'label'   => esc_html__( 'Text Alignment' ),
				'type'    => 'text-align',
				'exclude' => array( 'justify' ), //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				'css'     => array(
					array(
						'property' => 'text-align',
						'selector' => '#bwf-custom-button-wrap a',
					),
				),
			);

			$this->controls['separatorButtonIcon'] = array(
				'group' => 'contentButton',
				'label' => esc_html__( 'Button Icon' ),
				'type'  => 'separator',
			);

			$this->controls['btn_icon'] = array(
				'group' => 'contentButton',
				'label' => esc_html__( 'Icon' ),
				'type'  => 'icon',
			);

			$this->controls['btn_icon_position'] = array(
				'group'   => 'contentButton',
				'label'   => esc_html( 'Icon Position' ),
				'type'    => 'select',
				'options' => array(
					'left'  => __( 'Before' ),
					'right' => __( 'After' ),
				),
			);

			$this->controls['popup_open_animation'] = array(
				'group'   => 'contentPopup',
				'label'   => esc_html( 'Effect' ),
				'type'    => 'select',
				'options' => array(
					'fade'       => __( 'Fade' ),
					'slide-up'   => __( 'Slide Up' ),
					'slide-down' => __( 'Slide Down' ),
				),
				'default' => 'fade',
			);

			$this->controls['separatorProgressBarSize'] = array(
				'group' => 'styleProgressBar',
				'label' => esc_html__( 'Size' ),
				'type'  => 'separator',
			);

			$this->controls['popup_bar_width'] = array(
				'group'    => 'styleProgressBar',
				'label'    => esc_html__( 'Width' ),
				'type'     => 'slider',
				'css'      => array(
					array(
						'property' => 'width',
						'selector' => '.bwf_pp_overlay .bwf_pp_bar_wrap .bwf_pp_bar',
					),
				),
				'units'    => array(
					'%' => array(
						'min' => 1,
						'max' => 100,
					),
				),
				'default'  => '75%',
				'required' => array( 'popup_bar_pp', '=', true ),
			);

			$this->controls['popup_bar_heights'] = array(
				'group'    => 'styleProgressBar',
				'label'    => esc_html__( 'Height' ),
				'type'     => 'slider',
				'css'      => array(
					array(
						'property' => 'height',
						'selector' => '.bwf_pp_overlay .bwf_pp_bar_wrap',
					),
				),
				'units'    => array(
					'px' => array(
						'min' => 1,
						'max' => 100,
					),
				),
				'default'  => '40px',
				'required' => array( 'popup_bar_pp', '=', true ),
			);

			$this->controls['popup_bar_inner_gaps'] = array(
				'group'    => 'styleProgressBar',
				'label'    => esc_html__( 'Padding' ),
				'type'     => 'slider',
				'css'      => array(
					array(
						'property' => 'padding',
						'selector' => '.bwf_pp_overlay .bwf_pp_bar_wrap',
					),
				),
				'units'    => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'default'  => '4px',
				'required' => array( 'popup_bar_pp', '=', true ),
			);

			$this->controls['separatorProgressBarStyling'] = array(
				'group' => 'styleProgressBar',
				'label' => esc_html__( 'Styling' ),
				'type'  => 'separator',
			);

			$this->controls['progress_bar_typography'] = array(
				'group'   => 'styleProgressBar',
				'label'   => esc_html__( 'Typography' ),
				'type'    => 'typography',
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '.bwf_pp_overlay .pp-bar-text',
					),
				),
				'inline'  => true,
				'default' => array(
					'color' => array(
						'hex' => '#ffffff',
					),
				),
			);

			$this->controls['progress_color'] = array(
				'group' => 'styleProgressBar',
				'label' => esc_html__( 'Color' ),
				'type'  => 'color',
				'css'   => array(
					array(
						'property' => 'background-color',
						'selector' => '.bwf_pp_overlay .bwf_pp_bar',
					),
				),
			);

			$this->controls['progress_background_color'] = array(
				'group' => 'styleProgressBar',
				'label' => esc_html__( 'Background' ),
				'type'  => 'color',
				'css'   => array(
					array(
						'property' => 'background-color',
						'selector' => '.bwf_pp_overlay .bwf_pp_bar_wrap',
					),
				),
			);

			$this->controls['separatorStyleHeading'] = array(
				'group' => 'styleHeading',
				'label' => esc_html__( 'Heading' ),
				'type'  => 'separator',
			);

			$this->controls['popup_heading_typography'] = array(
				'group'   => 'styleHeading',
				'label'   => esc_html__( 'Typography' ),
				'type'    => 'typography',
				'default' => array(
					'font-family' => 'Open Sans',
					'font-weight' => '400',
					'font-size'   => '17px',
					'line-height' => '1.5',
				),
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '.bwf_pp_overlay .bwf_pp_opt_head',
					),
				),
			);

			$this->controls['separatorStyleSubHeading'] = array(
				'group' => 'styleHeading',
				'label' => esc_html__( 'Sub-Heading' ),
				'type'  => 'separator',
			);

			$this->controls['popup_subheading_typography'] = array(
				'group'   => 'styleHeading',
				'label'   => esc_html__( 'Typography' ),
				'type'    => 'typography',
				'default' => array(
					'font-family' => 'Open Sans',
					'font-weight' => '700',
					'font-size'   => '24px',
					'line-height' => '1.5',
				),
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '.bwf_pp_overlay .bwf_pp_opt_sub_head',
					),
				),
			);

			$this->controls['btn_width'] = array(
				'group'   => 'styleButton',
				'label'   => esc_html__( 'Button width (in %)' ),
				'type'    => 'slider',
				'css'     => array(
					array(
						'property' => 'min-width',
						'selector' => '#bwf-custom-button-wrap a',
					),
				),
				'units'   => array(
					'%' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'default' => '30%',
			);

			$this->controls['btn_bg_color'] = array(
				'group'   => 'styleButton',
				'label'   => esc_html__( 'Background' ),
				'type'    => 'color',
				'default' => array(
					'hex' => '#000000',
				),
				'css'     => array(
					array(
						'property' => 'background-color',
						'selector' => '#bwf-custom-button-wrap a',
					),
				),
			);

			$this->controls['btn_color'] = array(
				'group'   => 'styleButton',
				'label'   => esc_html__( 'Label' ),
				'type'    => 'color',
				'default' => array(
					'hex' => '#ffffff',
				),
				'css'     => array(
					array(
						'property' => 'color',
						'selector' => '#bwf-custom-button-wrap a',
					),
					array(
						'property' => 'color',
						'selector' => '#bwf-custom-button-wrap .bwf_subheading',
					),
				),
			);

			$this->controls['separatorButtonTypography'] = array(
				'group' => 'styleButton',
				'label' => esc_html__( 'Typography' ),
				'type'  => 'separator',
			);

			$this->controls['btn_text_typo'] = array(
				'group'  => 'styleButton',
				'label'  => esc_html__( 'Heading' ),
				'type'   => 'typography',
				'css'    => array(
					array(
						'property' => 'typography',
						'selector' => '#bwf-custom-button-wrap .bwf_heading',
					),
					array(
						'property' => 'typography',
						'selector' => '#bwf-custom-button-wrap .bwf_icon',
					),
				),
				'inline' => true,
			);

			$this->controls['btn_subheading_text_typo'] = array(
				'group'  => 'styleButton',
				'label'  => esc_html__( 'Sub Heading' ),
				'type'   => 'typography',
				'css'    => array(
					array(
						'property' => 'typography',
						'selector' => '#bwf-custom-button-wrap .bwf_subheading',
					),
				),
				'inline' => true,
			);

			$this->controls['btn_text_alignment_border'] = array(
				'group'   => 'styleButton',
				'label'   => esc_html__( 'Border' ),
				'type'    => 'border',
				'css'     => array(
					array(
						'property' => 'border',
						'selector' => '#bwf-custom-button-wrap a',
					),
				),
				'inline'  => true,
				'small'   => true,
				'default' => array(
					'width' => array(
						'top'    => 0,
						'right'  => 0,
						'bottom' => 0,
						'left'   => 0,
					),
					'style' => 'solid',
					'color' => array(
						'hex' => '#E69500',
					),
				),
			);

			$this->controls['btn_text_alignment_box_shadow'] = array(
				'group'  => 'styleButton',
				'label'  => esc_html__( 'Box Shadow' ),
				'type'   => 'box-shadow',
				'css'    => array(
					array(
						'property' => 'box-shadow',
						'selector' => '#bwf-custom-button-wrap a',
					),
				),
				'inline' => true,
				'small'  => true,
			);

			$this->controls['separatorButtonAdvanced'] = array(
				'group' => 'styleButton',
				'label' => esc_html__( 'Advanced' ),
				'type'  => 'separator',
			);

			$this->controls['btn_text_padding'] = array(
				'group'   => 'styleButton',
				'label'   => esc_html__( 'Padding' ),
				'type'    => 'spacing',
				'default' => array(
					'top'    => 5,
					'right'  => 5,
					'bottom' => 5,
					'left'   => 5,
				),
				'css'     => array(
					array(
						'property' => 'padding',
						'selector' => '#bwf-custom-button-wrap a',
					),
				),
			);

			$this->controls['btn_text_margin'] = array(
				'group'   => 'styleButton',
				'label'   => esc_html__( 'Margin' ),
				'type'    => 'spacing',
				'default' => array(
					'top'    => 5,
					'right'  => 5,
					'bottom' => 5,
					'left'   => 5,
				),
				'css'     => array(
					array(
						'property' => 'margin',
						'selector' => '#bwf-custom-button-wrap a',
					),
				),
			);

			$this->controls['separatorButtonAdvanced'] = array(
				'group' => 'stylePopup',
				'label' => esc_html__( 'Advanced' ),
				'type'  => 'separator',
			);

			$this->controls['popup_wrap_width'] = array(
				'group'   => 'stylePopup',
				'label'   => esc_html__( 'Button width (in px)' ),
				'type'    => 'slider',
				'css'     => array(
					array(
						'property' => 'max-width',
						'selector' => '.bwf_pp_wrap',
					),
				),
				'units'   => array(
					'px' => array(
						'min'  => 0,
						'max'  => 2500,
						'step' => 5,
					),
				),
				'default' => '600px',
			);

			$this->controls['popup_padding'] = array(
				'group'   => 'stylePopup',
				'label'   => esc_html__( 'Padding' ),
				'type'    => 'spacing',
				'default' => array(
					'top'    => 40,
					'right'  => 40,
					'bottom' => 40,
					'left'   => 40,
				),
				'css'     => array(
					array(
						'property' => 'padding',
						'selector' => '.bwf_pp_wrap .bwf_pp_cont',
					),
				),
			);

			$this->controls['separatorCloseButtonPosition'] = array(
				'group' => 'styleCloseButton',
				'label' => esc_html__( 'Position' ),
				'type'  => 'separator',
			);

			$this->controls['close_button_vertical'] = array(
				'group'   => 'styleCloseButton',
				'label'   => esc_html__( 'Vertical Position' ),
				'type'    => 'slider',
				'css'     => array(
					array(
						'property' => 'top',
						'selector' => '.bwf_pp_close',
					),
				),
				'units'   => array(
					'px' => array(
						'max' => 650,
						'min' => - 90,
					),
					'%'  => array(
						'max'  => 100,
						'min'  => 0,
						'step' => 0.1,
					),
				),
				'default' => '-8px',
			);

			$this->controls['close_button_horizontal'] = array(
				'group'   => 'styleCloseButton',
				'label'   => esc_html__( 'Horizontal Position' ),
				'type'    => 'slider',
				'css'     => array(
					array(
						'property' => 'right',
						'selector' => '.bwf_pp_close',
					),
				),
				'units'   => array(
					'px' => array(
						'max' => 1000,
						'min' => - 350,
					),
					'%'  => array(
						'max'  => 100,
						'min'  => 0,
						'step' => 0.1,
					),
				),
				'default' => '-14px',
			);

			$this->controls['separatorCloseButtonSize'] = array(
				'group' => 'styleCloseButton',
				'label' => esc_html__( 'Size' ),
				'type'  => 'separator',
			);

			$this->controls['icon_size'] = array(
				'group'   => 'styleCloseButton',
				'label'   => esc_html__( 'Font Size' ),
				'type'    => 'slider',
				'css'     => array(
					array(
						'property' => 'font-size',
						'selector' => '.bwf_pp_close',
					),
				),
				'units'   => array(
					'px' => array(
						'max' => 50,
						'min' => 5,
					),
					'em' => array(
						'max'  => 20,
						'min'  => 0,
						'step' => 0.1,
					),
				),
				'default' => '25px',
			);

			$this->controls['close_btn_inner_gap'] = array(
				'group'   => 'styleCloseButton',
				'label'   => esc_html__( 'Padding' ),
				'type'    => 'slider',
				'css'     => array(
					array(
						'property' => 'padding',
						'selector' => '.bwf_pp_close',
					),
				),
				'units'   => array(
					'px' => array(
						'max' => 150,
						'min' => 0,
					),
					'em' => array(
						'max'  => 20,
						'min'  => 0,
						'step' => 0.1,
					),
				),
				'default' => '0px',
			);

			$this->controls['close_btn_border'] = array(
				'group'   => 'styleCloseButton',
				'label'   => esc_html__( 'Border Radius' ),
				'type'    => 'slider',
				'css'     => array(
					array(
						'property' => 'border-radius',
						'selector' => '.bwf_pp_close',
					),
				),
				'units'   => array(
					'px' => array(
						'max' => 50,
						'min' => 0,
					),
					'em' => array(
						'max'  => 20,
						'min'  => 0,
						'step' => 0.1,
					),
				),
				'default' => '15px',
			);

			$this->controls['separatorCloseButtonColor'] = array(
				'group' => 'styleCloseButton',
				'label' => esc_html__( 'Color' ),
				'type'  => 'separator',
			);

			$this->controls['close_button_background_color'] = array(
				'group'   => 'styleCloseButton',
				'label'   => esc_html__( 'Background' ),
				'type'    => 'color',
				'default' => array(
					'hex' => '#6E6E6E',
				),
				'css'     => array(
					array(
						'property' => 'background-color',
						'selector' => '.bwf_pp_close',
					),
				),
			);

			$this->controls['close_button_color'] = array(
				'group'   => 'styleCloseButton',
				'label'   => esc_html__( 'Color' ),
				'type'    => 'color',
				'default' => array(
					'hex' => '#ffffff',
				),
				'css'     => array(
					array(
						'property' => 'color',
						'selector' => '.bwf_pp_close',
					),
				),
			);

			$this->controls['separatorContentTextAfterButton'] = array(
				'group' => 'contentForm',
				'label' => esc_html__( 'Text After Button' ),
				'type'  => 'separator',
			);

			$this->controls['popup_footer_text'] = array(
				'group'   => 'contentForm',
				'label'   => esc_html__( 'Text' ),
				'type'    => 'text',
				'default' => esc_html__( 'Your Information is 100% Secure' ),
			);

			$this->controls['separatorStyleTextAfterButton'] = array(
				'group' => 'styleForm',
				'label' => esc_html__( 'Text After Button' ),
				'type'  => 'separator',
			);

			$this->controls['popup_footer_typography'] = array(
				'group'   => 'styleForm',
				'label'   => esc_html__( 'Typography' ),
				'type'    => 'typography',
				'default' => array(
					'font-family' => 'Open Sans',
					'font-weight' => '700',
					'font-size'   => '16px',
					'line-height' => '1',
					'color'       => array(
						'hex' => '#000000',
					),
				),
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '.bwf_pp_wrap .bwf_pp_cont .bwf_pp_footer',
					),
				),
			);
		}

		/**
		 * Renders the optin popup.
		 *
		 * This method is responsible for rendering the optin popup based on the provided settings.
		 *
		 * @return void
		 * @since 1.0.0
		 *
		 */
		public function render() {
			$settings = $this->settings;

			$button_args = array(
				'title'    => $settings['btn_text'],
				'subtitle' => isset( $settings['btn_subheading_text'] ) ? $settings['btn_subheading_text'] : '',
				'type'     => 'anchor',
				'link'     => '#',
			);

			if ( ! empty( $settings['btn_icon'] ) ) {
				$icon                     = self::render_icon( $settings['btn_icon'] );
				$icon_position            = ! empty( $settings['btn_icon_position'] ) ? $settings['btn_icon_position'] : 'left';
				$button_args['show_icon'] = true;
				$button_args['icon_html'] = '<span class="bwf_icon ' . esc_attr( $icon_position ) . '">' . $icon . '</span>';
			}

			$wrapper_class = 'bricks-form-fields-wrapper';
			$show_labels   = isset( $settings['show_labels'] ) ? $settings['show_labels'] : false;
			$wrapper_class .= $show_labels ? '' : ' wfop_hide_label';

			$optinPageId    = WFOPP_Core()->optin_pages->get_optin_id();
			$optin_fields   = WFOPP_Core()->optin_pages->form_builder->get_optin_layout( $optinPageId );
			$optin_settings = WFOPP_Core()->optin_pages->get_optin_form_integration_option( $optinPageId );

			foreach ( $optin_fields as $step_slug => $optinFields ) {
				foreach ( $optinFields as $key => $optin_field ) {
					$optin_fields[ $step_slug ][ $key ]['width'] = $settings[ $optin_field['InputName'] ];
				}
			}

			$settings['popup_bar_pp']        = isset( $settings['popup_bar_pp'] ) && $settings['popup_bar_pp'] ? 'enable' : 'disable';
			$settings['popup_bar_animation'] = isset( $settings['popup_bar_animation'] ) && $settings['popup_bar_animation'] ? 'yes' : 'no';
			$settings['button_border_size']  = 0;
			?>
            <div <?php echo $this->render_attributes( '_root' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php
				$custom_form = WFOPP_Core()->form_controllers->get_integration_object( 'form' );
				if ( $custom_form instanceof WFFN_Optin_Form_Controller_Custom_Form ) {
					?>
                    <div class="wfop_popup_wrapper wfop_pb_widget_wrap">
						<?php
						$custom_form->wffn_get_button_html( $button_args );
						$show_class = '';
						?>
                        <div class="bwf_pp_overlay <?php echo esc_attr( $show_class ); ?> bwf_pp_effect_<?php echo esc_attr( $settings['popup_open_animation'] ); ?>">
                            <div class="bwf_pp_wrap">
                                <a class="bwf_pp_close" href="javascript:void(0);">&times;</a>
                                <div class="bwf_pp_cont">
									<?php
									$settings = wp_parse_args( $settings, WFOPP_Core()->optin_pages->form_builder->form_customization_settings_default() );
									$custom_form->_output_form( $wrapper_class, $optin_fields, $optinPageId, $optin_settings, 'popover', $settings );
									?>
                                </div>
                            </div>
                        </div>
                    </div>
					<?php
				}
				?>
            </div>

			<?php
		}
	}
}