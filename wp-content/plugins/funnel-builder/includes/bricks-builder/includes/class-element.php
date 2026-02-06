<?php
namespace FunnelKit\Bricks\Elements;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( '\FunnelKit\Bricks\Elements\Element' ) ) {
	class Element extends \Bricks\Element {
		const TAB_CONTENT = 'content';
		const TAB_STYLE = 'style';

		private $separator_key = 0;
		private $current_group = '';

		/**
		 * Adds a group to the control groups array.
		 *
		 * @param string $title The title of the group.
		 * @param string $tab The tab to which the group belongs. Default is self::TAB_CONTENT.
		 *
		 * @return void
		 */
		public function add_group( $group_key = '', $title = '', $tab = self::TAB_CONTENT ) {
			$this->control_groups[ $group_key ] = array(
				'title' => $title,
				'tab'   => $tab,
			);
		}

		/**
		 * Sets the current group for the element.
		 *
		 * @param string $group The group to set.
		 *
		 * @return void
		 */
		public function set_current_group( $group ) {
			$this->current_group = $group;
		}

		/**
		 * Retrieves the current group of the element.
		 *
		 * @return string The current group of the element.
		 */
		public function get_current_group() {
			return $this->current_group;
		}

		/**
		 * Adds a heading control to the element.
		 *
		 * @param string $label The label of the heading.
		 *
		 * @return void
		 */
		public function add_heading( $label, $required = array() ) {
			$key                    = 'divider_' . $this->separator_key ++;
			$this->controls[ $key ] = array(
				'group'    => $this->get_current_group(),
				'type'     => 'separator',
				'label'    => $label,
				'required' => empty( $required ) ? false : $required,
			);
		}

		/**
		 * Adds a font family control to the element.
		 *
		 * @param string $key The key for the control.
		 * @param array $selectors The CSS selectors for the control.
		 * @param string $label The label for the control. Default is 'Font family'.
		 * @param string $default The default font family. Default is 'Open Sans'.
		 *
		 * @return void
		 */
		public function add_font_family( $key, $selectors, $label = '', $default = 'Open Sans' ) {
			// Ensure the label has a default value
			if ( empty( $label ) ) {
				$label = esc_html__( 'Font family' );
			}

			$css = array();
			foreach ( $selectors as $selector ) {
				$css[] = array(
					'property' => 'typography',
					'selector' => $selector,
				);
			}

			// Structure the control array
			$this->controls[ $key ] = array(
				'group'   => $this->get_current_group(),
				'label'   => $label,
				'type'    => 'typography',
				'default' => array(
					'font-family' => $default,
				),
				'exclude' => array( //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
					'font-weight',
					'text-align',
					'text-transform',
					'font-size',
					'line-height',
					'letter-spacing',
					'color',
					'text-shadow',
					'text-decoration',
					'font-style',
					'font-variation-settings',
				),
				'inline'  => true,
				'popup'   => false,
				'css'     => $css,
			);
		}

		/**
		 * Adds a background color control to the element.
		 *
		 * @param string $key The key for the control.
		 * @param array $selectors The CSS selectors to apply the background color to.
		 * @param string $label The label for the control. Default is 'Background color'.
		 * @param string $default The default background color. Default is '#000000'.
		 */
		public function add_background_color( $key, $selectors, $default = '#000000', $label = '', $required = array() ) {
			// Ensure the label has a default value
			if ( empty( $label ) ) {
				$label = esc_html__( 'Background color' );
			}

			$css = array();
			foreach ( $selectors as $selector ) {
				$css[] = array(
					'property' => 'background-color',
					'selector' => $selector,
				);
			}

			// Structure the control array
			$this->controls[ $key ] = array(
				'group'   => $this->get_current_group(),
				'label'   => $label,
				'type'    => 'color',
				'default' => $default,
			);

			if ( ! empty( $css ) ) {
				$this->controls[ $key ]['css'] = $css;
			}

			if ( ! empty( $required ) ) {
				$this->controls[ $key ]['required'] = $required;
			}
		}

		/**
		 * Adds a color control to the element.
		 *
		 * @param string $key The key of the control.
		 * @param array $selectors The CSS selectors to apply the color to.
		 * @param string $default The default color value.
		 * @param string $label The label for the color control.
		 * @param array $required The required fields for the color control.
		 * @param bool $important Whether the color property is important.
		 *
		 * @return void
		 */
		public function add_color( $key, $selectors, $default = '', $label = '', $required = array(), $important = false ) {
			// Ensure the label has a default value
			if ( empty( $label ) ) {
				$label = esc_html__( 'Color' );
			}

			$css = array();
			foreach ( $selectors as $selector ) {
				$css_selector = array(
					'property' => 'color',
					'selector' => $selector,
				);

				if ( $important ) {
					$css_selector['important'] = true;
				}

				$css[] = $css_selector;
			}

			// Structure the control array
			$this->controls[ $key ] = array(
				'group' => $this->get_current_group(),
				'label' => $label,
				'type'  => 'color',
				'css'   => $css,
			);

			if ( ! empty( $default ) ) {
				$this->controls[ $key ]['default'] = $default;
			}

			if ( ! empty( $required ) ) {
				$this->controls[ $key ]['required'] = $required;
			}
		}

		/**
		 * Adds padding control to the element.
		 *
		 * This method adds a padding control to the element's controls array. The padding control allows the user to set the padding for the specified selectors.
		 *
		 * @param string $key The key for the control.
		 * @param array $selectors The CSS selectors to which the padding will be applied.
		 * @param array $default The default padding values. If empty, the default values will be [ 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 ].
		 *
		 * @return void
		 */
		public function add_padding( $key, $selectors, $default = array() ) {
			if ( empty( $default ) ) {
				$default = array(
					'top'    => 0,
					'right'  => 0,
					'bottom' => 0,
					'left'   => 0,
				);
			}

			$css = array();
			foreach ( $selectors as $selector ) {
				$css[] = array(
					'property' => 'padding',
					'selector' => $selector,
				);
			}

			// Structure the control array
			$this->controls[ $key ] = array(
				'group'   => $this->get_current_group(),
				'label'   => esc_html__( 'Padding' ),
				'type'    => 'spacing',
				'default' => $default,
				'css'     => $css,
			);
		}

		/**
		 * Adds a margin control to the element.
		 *
		 * This method adds a margin control to the element, allowing the user to set the margin values for the specified selectors.
		 *
		 * @param string $key The key for the control.
		 * @param array $selectors An array of CSS selectors to which the margin property should be applied.
		 * @param array $default An optional array of default margin values. If not provided, the default values will be set to 0 for top, right, bottom, and left.
		 *
		 * @return void
		 */
		public function add_margin( $key, $selectors, $default = array(), $required = array() ) {
			if ( empty( $default ) ) {
				$default = array(
					'top'    => 0,
					'right'  => 0,
					'bottom' => 0,
					'left'   => 0,
				);
			}

			$css = array();
			foreach ( $selectors as $selector ) {
				$css[] = array(
					'property' => 'margin',
					'selector' => $selector,
				);
			}

			// Structure the control array
			$this->controls[ $key ] = array(
				'group'   => $this->get_current_group(),
				'label'   => esc_html__( 'Margin' ),
				'type'    => 'spacing',
				'default' => $default,
				'css'     => $css,
			);

			if ( ! empty( $required ) ) {
				$this->controls[ $key ]['required'] = $required;
			}
		}

		/**
		 * Adds a border control to the element.
		 *
		 * @param string $key The key for the control.
		 * @param array $selectors The CSS selectors for the element.
		 * @param array $default The default border properties.
		 *
		 * @return void
		 */
		public function add_border( $key, $selectors, $default = array() ) {
			$css = array();
			foreach ( $selectors as $selector ) {
				$css[] = array(
					'property' => 'border',
					'selector' => $selector,
				);
			}

			$this->controls[ $key ] = array(
				'group' => $this->get_current_group(),
				'label' => esc_html__( 'Border' ),
				'type'  => 'border',
				'css'   => $css,
			);

			if ( ! empty( $default ) ) {
				$this->controls[ $key ]['default'] = $default;
			}
		}

		/**
		 * Adds a border radius control to the element.
		 *
		 * @param string $key The key for the control.
		 * @param array|string $selectors The CSS selectors for the control.
		 * @param array $default The default values for the control.
		 */
		public function add_border_radius( $key, $selectors, $default = array(), $label = '', $required = array() ) {
			if ( ! is_array( $selectors ) ) {
				$selectors = array( $selectors );
			}

			if ( empty( $default ) ) {
				$default = array(
					'top'    => 0,
					'right'  => 0,
					'bottom' => 0,
					'left'   => 0,
				);
			}

			if ( empty( $label ) ) {
				$label = esc_html__( 'Border Radius' );
			}

			$css = array();
			foreach ( $selectors as $selector ) {
				$css[] = array(
					'property' => 'border-radius',
					'selector' => $selector,
				);
			}

			$this->controls[ $key ] = array(
				'group'   => $this->get_current_group(),
				'label'   => $label,
				'type'    => 'border',
				'exclude' => array( //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
					'width',
					'style',
					'color',
				),
				'default' => array(
					'radius' => $default,
				),
				'css'     => $css,
			);

			if ( ! empty( $required ) ) {
				$this->controls[ $key ]['required'] = $required;
			}
		}

		/**
		 * Adds a typography control to the element.
		 *
		 * @param string $key The unique key for the control.
		 * @param array|string $selectors The CSS selectors to apply the typography to.
		 * @param array $default The default typography settings.
		 * @param string $label The label for the typography control.
		 *                                If empty, the default label will be used.
		 *
		 * @return void
		 */
		public function add_typography( $key, $selectors, $default = array(), $required = array(), $label = '', $exclude = array() ) {
			if ( empty( $label ) ) {
				$label = esc_html__( 'Typography' );
			}

			if ( ! is_array( $selectors ) ) {
				$selectors = array( $selectors );
			}

			$css = array();
			foreach ( $selectors as $selector ) {
				$css[] = array(
					'property' => 'typography',
					'selector' => $selector,
				);
			}

			$this->controls[ $key ] = array(
				'group' => $this->get_current_group(),
				'label' => $label,
				'type'  => 'typography',
				'css'   => $css,
			);

			if ( ! empty( $default ) ) {
				$this->controls[ $key ]['default'] = $default;
			}

			if ( ! empty( $required ) ) {
				$this->controls[ $key ]['required'] = $required;
			}

			if ( ! empty( $exclude ) ) {
				$this->controls[ $key ]['exclude'] = $exclude; //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
			}
		}

		/**
		 * Adds text alignments control to the element.
		 *
		 * @param string $key The key for the control.
		 * @param array $selectors The CSS selectors to apply the text alignment to.
		 * @param string $default The default text alignment. Defaults to 'left'.
		 * @param string $label The label for the control. Defaults to 'Alignment'.
		 */
		public function add_text_alignments( $key, $selectors, $label = '', $default = 'left', $required = array() ) {
			if ( empty( $label ) ) {
				$label = esc_html__( 'Alignment' );
			}

			$css = array();
			foreach ( $selectors as $selector ) {
				$css[] = array(
					'property' => 'text-align',
					'selector' => $selector,
				);
			}

			$this->controls[ $key ] = array(
				'group'   => $this->get_current_group(),
				'label'   => $label,
				'type'    => 'text-align',
				'exclude' => array( //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
					'justify',
				),
				'default' => is_rtl() ? 'right' : $default,
				'css'     => $css,
			);

			if ( ! empty( $required ) ) {
				$this->controls[ $key ]['required'] = $required;
			}
		}

		/**
		 * Adds a select control to the element.
		 *
		 * @param string $key The key of the control.
		 * @param string $label The label of the control.
		 * @param array $options The options for the select control.
		 * @param string $default The default value for the select control.
		 *
		 * @return void
		 */
		public function add_select( $key, $label, $options = array(), $default = '', $required = array() ) {
			if ( empty( $options ) ) {
				return;
			}

			$this->controls[ $key ] = array(
				'group'    => $this->get_current_group(),
				'label'    => $label,
				'type'     => 'select',
				'options'  => $options,
				'default'  => $default,
				'inline'   => true,
				'required' => empty( $required ) ? false : $required,
			);
		}

		/**
		 * Adds a border color control with optional box shadow to a list of selectors.
		 *
		 * This method creates a color control for the border color of the specified CSS selectors.
		 * If $box_shadow is true, it also adds a box shadow effect.
		 *
		 * @param string $key The unique key for the control.
		 * @param array $selectors An array of CSS selectors to which the border color will be applied.
		 * @param string $default Optional. The default color value. Default is '#000000' (black).
		 * @param string $label Optional. The label for the control. Default is 'Border Color'.
		 * @param bool $box_shadow Optional. Whether to add a box shadow effect. Default is false.
		 *
		 * @return void
		 */
		public function add_border_color( $key, $selectors, $default = '#000000', $label = '', $box_shadow = false, $required = array(), $variable_selector = '> div' ) {
			// Ensure the label has a default value
			if ( empty( $label ) ) {
				$label = esc_html__( 'Border Color' );
			}

			$css               = array();
			$css_variable_name = '--border-color-' . str_replace( '_', '-', $key );

			$css[] = array(
				'property' => $css_variable_name,
				'selector' => $variable_selector,
			);

			foreach ( $selectors as $selector ) {
				$css[] = array(
					'property' => 'border-color',
					'selector' => $selector,
					'value'    => "var($css_variable_name)",
				);

				if ( $box_shadow ) {
					$css[] = array(
						'property' => 'box-shadow',
						'selector' => $selector,
						'value'    => "0 0 0 1px var($css_variable_name)",
					);
				}
			}

			// Structure the control array
			$this->controls[ $key ] = array(
				'group'    => $this->get_current_group(),
				'label'    => $label,
				'type'     => 'color',
				'default'  => array(
					'hex' => $default,
				),
				'required' => empty( $required ) ? false : $required,
				'css'      => $css,
			);
		}

		/**
		 * Adds a border shadow control to the element.
		 *
		 * @param string $key The key for the control.
		 * @param array $selectors The CSS selectors to apply the box shadow to.
		 * @param array $default The default box shadow values.
		 * @param string $label The label for the control. If empty, a default label will be used.
		 *
		 * @return void
		 */
		public function add_border_shadow( $key, $selectors, $default = array(), $label = '' ) {
			// Ensure the label has a default value
			if ( empty( $label ) ) {
				$label = esc_html__( 'Box Shadow' );
			}

			$css = array();
			foreach ( $selectors as $selector ) {
				$css[] = array(
					'property' => 'box-shadow',
					'selector' => $selector,
				);
			}

			$this->controls[ $key ] = array(
				'group' => $this->get_current_group(),
				'label' => $label,
				'type'  => 'box-shadow',
				'css'   => $css,
			);

			if ( ! empty( $default ) ) {
				$this->controls[ $key ]['default'] = $default;
			}
		}

		/**
		 * Adds a divider control to the element.
		 *
		 * This method generates a unique key for the divider control and adds it to the controls array.
		 * The divider control is used to visually separate different sections or elements within the builder.
		 *
		 * @return void
		 */
		public function add_divider() {
			$key                    = 'divider_' . $this->separator_key ++;
			$this->controls[ $key ] = array(
				'group' => $this->get_current_group(),
				'type'  => 'separator',
			);
		}

		/**
		 * Adds a switcher control to the element.
		 *
		 * @param string $key The unique key for the control.
		 * @param string $label The label for the control. Default is 'Enable'.
		 * @param bool $default The default value for the control. Default is false.
		 *
		 * @return void
		 */
		public function add_switcher( $key, $label = '', $default = false, $css = array(), $rerender = true ) {
			if ( empty( $label ) ) {
				$label = esc_html__( 'Enable' );
			}

			$this->controls[ $key ] = array(
				'group'    => $this->get_current_group(),
				'label'    => $label,
				'type'     => 'checkbox',
				'default'  => $default,
				'rerender' => $rerender,
			);

			if ( ! empty( $css ) ) {
				$this->controls[ $key ]['css'] = $css;
			}
		}

		/**
		 * Adds a text control to the element.
		 *
		 * @param string $key The key of the control.
		 * @param string $label The label for the control. If empty, the default label "Text" will be used.
		 * @param string $default The default value for the control.
		 * @param array $required An array of required fields for the control.
		 *
		 * @return void
		 */
		public function add_text( $key, $label = '', $default = '', $required = array(), $description = '', $plceholder = '' ) {
			if ( empty( $label ) ) {
				$label = esc_html__( 'Text' );
			}

			$this->controls[ $key ] = array(
				'group'       => $this->get_current_group(),
				'label'       => $label,
				'type'        => 'text',
				'default'     => $default,
				'description' => $description,
				'placeholder' => $plceholder,
				'required'    => empty( $required ) ? false : $required,
			);
		}

		/**
		 * Adds a textarea control to the element.
		 *
		 * @param string $key The unique key for the control.
		 * @param string $label The label for the control. If empty, the default label "Text" will be used.
		 * @param string $default The default value for the control.
		 * @param array $required An array of required validation rules for the control.
		 *
		 * @return void
		 */
		public function add_textarea( $key, $label = '', $default = '', $required = array() ) {
			if ( empty( $label ) ) {
				$label = esc_html__( 'Text' );
			}

			$this->controls[ $key ] = array(
				'group'    => $this->get_current_group(),
				'label'    => $label,
				'type'     => 'textarea',
				'default'  => $default,
				'required' => empty( $required ) ? false : $required,
			);
		}

		/**
		 * Adds a font size control to the element.
		 *
		 * @param string $key The key for the control.
		 * @param array $selectors The CSS selectors to apply the font size to.
		 * @param string $label The label for the control. Default is 'Font Size'.
		 * @param string $default The default value for the control.
		 * @param array $required An array of required fields for the control.
		 *
		 * @return void
		 */
		public function add_font_size( $key, $selectors, $label = '', $default = '', $required = array(), $units = array() ) {
			if ( empty( $label ) ) {
				$label = esc_html__( 'Font Size' );
			}

			if ( empty( $units ) ) {
				$units = array(
					'px' => array(
						'min'  => 1,
						'max'  => 100,
						'step' => 1,
					),
					'em' => array(
						'min'  => 1,
						'max'  => 20,
						'step' => 0.1,
					),
				);
			}

			$css = array();
			foreach ( $selectors as $selector ) {
				$css[] = array(
					'property' => 'font-size',
					'selector' => $selector,
				);
			}

			$this->controls[ $key ] = array(
				'group'    => $this->get_current_group(),
				'label'    => $label,
				'type'     => 'slider',
				'css'      => $css,
				'units'    => $units,
				'default'  => $default,
				'required' => empty( $required ) ? false : $required,
			);
		}

		/**
		 * Adds a width control to the element.
		 *
		 * @param string $key The key for the control.
		 * @param array $selectors The CSS selectors for the element.
		 * @param string $label The label for the control. Default is 'Width'.
		 * @param string $default The default value for the control.
		 * @param array $required An array of required fields for the control.
		 * @param array $units An array of units for the control. Default is an array with 'px' and '%'.
		 *
		 * @return void
		 */
		public function add_width( $key, $selectors, $label = '', $default = '', $required = array(), $units = array() ) {
			if ( empty( $label ) ) {
				$label = esc_html__( 'Width' );
			}

			if ( empty( $units ) ) {
				$units = array(
					'px' => array(
						'min'  => 1,
						'max'  => 2500,
						'step' => 1,
					),
					'%'  => array(
						'min'  => 1,
						'max'  => 100,
						'step' => 1,
					),
				);
			}

			$css = array();
			foreach ( $selectors as $selector ) {
				$css[] = array(
					'property' => 'width',
					'selector' => $selector,
				);
			}

			$this->controls[ $key ] = array(
				'group'    => $this->get_current_group(),
				'label'    => $label,
				'type'     => 'slider',
				'css'      => $css,
				'units'    => $units,
				'default'  => $default,
				'required' => empty( $required ) ? false : $required,
			);
		}

		/**
		 * Generates CSS rules for the given selectors and property.
		 *
		 * @param array $selectors An array of CSS selectors.
		 * @param string $property The CSS property to apply.
		 *
		 * @return array An array of CSS rules, each containing a 'property' and 'selector' key.
		 */
		public function generate_css( $selectors, $property ) {
			$css = array();
			foreach ( $selectors as $selector ) {
				$css[] = array(
					'property' => $property,
					'selector' => $selector,
				);
			}

			return $css;
		}
	}
}