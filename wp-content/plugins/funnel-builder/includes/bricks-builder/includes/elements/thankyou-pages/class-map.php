<?php

namespace FunnelKit\Bricks\Elements\ThankYouPages;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( '\FunnelKit\Bricks\Elements\ThankYouPages\Map' ) ) {
	class Map extends \Bricks\Element {
		public $category = 'funnelkit';
		public $name = 'wfty-map-widget';
		public $icon = 'ti-map-alt';

		/**
		 * Retrieves the label for the Map element.
		 *
		 * @return string The label for the Map element.
		 */
		public function get_label() {
			return esc_html__( 'Map' );
		}

		/**
		 * Sets the control groups for the thankyou Pages class.
		 *
		 * This method initializes the control groups array with the necessary control groups for the Thank You Pages class.
		 * Each control group is an associative array with a 'title' and 'tab' key.
		 *
		 * @return void
		 */
		public function set_control_groups() {
			$this->control_groups = array();

			$this->control_groups['contentMap'] = array(
				'title' => esc_html__( 'Map' ),
				'tab'   => 'content',
			);

			$this->control_groups['contentMessage'] = array(
				'title' => esc_html__( 'Message' ),
				'tab'   => 'content',
			);

			$this->control_groups['contentTimeline'] = array(
				'title' => esc_html__( 'Timeline' ),
				'tab'   => 'content',
			);

			$this->control_groups['styleMap'] = array(
				'title' => esc_html__( 'Map' ),
				'tab'   => 'style',
			);

			$this->control_groups['styleMessage'] = array(
				'title' => esc_html__( 'Message' ),
				'tab'   => 'style',
			);

			$this->control_groups['styleTimeline'] = array(
				'title' => esc_html__( 'Timeline' ),
				'tab'   => 'style',
			);

			$this->set_common_control_groups();

			unset( $this->control_groups['_typography'] );
		}

		/**
		 * Set the controls for the Thank You Pages class.
		 *
		 * This method sets the controls for enabling or disabling different elements of the Thank You Pages.
		 *
		 * @return void
		 */
		public function set_controls() {
			$this->controls['_alignSelf']['default'] = 'stretch';

			$this->controls['enableMap'] = array(
				'group'   => 'contentMap',
				'label'   => esc_html__( 'Enable / Disable' ),
				'type'    => 'checkbox',
				'default' => true,
			);

			$this->controls['enableMessage'] = array(
				'group'   => 'contentMessage',
				'label'   => esc_html__( 'Enable / Disable' ),
				'type'    => 'checkbox',
				'default' => true,
			);

			$this->controls['enableTimeline'] = array(
				'group'   => 'contentTimeline',
				'label'   => esc_html__( 'Enable / Disable' ),
				'type'    => 'checkbox',
				'default' => true,
			);

			$this->controls['mapBorder'] = array(
				'group' => 'styleMap',
				'label' => esc_html__( 'Border' ),
				'type'  => 'border',
				'css'   => array(
					array(
						'property' => 'border',
						'selector' => '.map',
					),
				),
			);

			$this->controls['messageBackground'] = array(
				'group' => 'styleMessage',
				'label' => esc_html__( 'Background' ),
				'type'  => 'color',
				'css'   => array(
					array(
						'property' => 'background-color',
						'selector' => '.order-notes',
					),
				),
			);

			$this->controls['messageTextColor'] = array(
				'group' => 'styleMessage',
				'label' => esc_html__( 'Text Color' ),
				'type'  => 'color',
				'css'   => array(
					array(
						'property' => 'color',
						'selector' => '.order-notes',
					),
				),
			);

			$this->controls['messageBorder'] = array(
				'group' => 'styleMessage',
				'label' => esc_html__( 'Border' ),
				'type'  => 'border',
				'css'   => array(
					array(
						'property' => 'border',
						'selector' => '.order-notes',
					),
				),
			);

			$this->controls['stateColor'] = array(
				'group' => 'styleTimeline',
				'label' => esc_html__( 'State Color' ),
				'type'  => 'color',
				'css'   => array(
					array(
						'property' => 'background-color',
						'selector' => '.timeline li:before',
					),
				),
			);
		}

		/**
		 * Renders the map widget element.
		 *
		 * This method is responsible for rendering the map widget element based on the provided settings.
		 *
		 * @return void
		 * @since 1.0.0
		 *
		 */
		public function render() {
			$settings = $this->settings;

			$enable_map      = isset( $settings['enableMap'] ) ? 'yes' : 'no';
			$enable_message  = isset( $settings['enableMessage'] ) ? 'yes' : 'no';
			$enable_timeline = isset( $settings['enableTimeline'] ) ? 'yes' : 'no';

			$this->set_attribute( 'wrapper', 'class', 'bricks-map-widget-wrapper' );
			?>
            <div <?php echo $this->render_attributes( '_root' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                <div <?php echo $this->render_attributes( 'wrapper' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<?php
					echo do_shortcode( '[wfty-map-widget enable_map="' . $enable_map . '" enable_message="' . $enable_message . '" enable_timeline="' . $enable_timeline . '"]' );
					?>
                </div>
            </div>
			<?php
		}
	}
}