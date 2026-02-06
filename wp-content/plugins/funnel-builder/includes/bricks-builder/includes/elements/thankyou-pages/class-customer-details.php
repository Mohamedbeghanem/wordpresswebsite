<?php

namespace FunnelKit\Bricks\Elements\ThankYouPages;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( '\FunnelKit\Bricks\Elements\ThankYouPages\Customer_Details' ) ) {
	class Customer_Details extends \Bricks\Element {
		public $category = 'funnelkit';
		public $name = 'wfty-customer-detail';
		public $icon = 'wfty-icon-offer_title';

		/**
		 * Retrieves the label for the "Customer Details" element.
		 *
		 * @return string The label for the "Customer Details" element.
		 */
		public function get_label() {
			return esc_html__( 'Customer Details' );
		}

		/**
		 * Sets the control groups for the Customer Details element.
		 *
		 * This method initializes the control groups array for the Customer Details element.
		 * It sets the title and tab for each control group.
		 * It also calls the set_common_control_groups() method to set common control groups.
		 * Finally, it removes the '_typography' control group from the control groups array.
		 *
		 * @return void
		 */
		public function set_control_groups() {
			$this->control_groups = array();

			$this->control_groups['contentCustomerDetails'] = array(
				'title' => esc_html__( 'Customer Details' ),
				'tab'   => 'content',
			);

			$this->control_groups['styleHeading'] = array(
				'title' => esc_html__( 'Heading' ),
				'tab'   => 'style',
			);

			$this->control_groups['styleDetails'] = array(
				'title' => esc_html__( 'Details' ),
				'tab'   => 'style',
			);

			$this->set_common_control_groups();

			unset( $this->control_groups['_typography'] );
		}

		/**
		 * Set the controls for the Customer Details element.
		 */
		public function set_controls() {
			$defaults = WFFN_Core()->thank_you_pages->default_shortcode_settings();

			// Control for the customerDetailsHeading
			$this->controls['customerDetailsHeading'] = array(
				'group'   => 'contentCustomerDetails',
				'label'   => esc_html__( 'Heading' ),
				'type'    => 'text',
				'default' => isset( $defaults['customer_details_heading'] ) ? $defaults['customer_details_heading'] : __( 'Customer Details' ),
			);

			// Control for the layoutLabel
			$this->controls['layoutLabel'] = array(
				'group' => 'contentCustomerDetails',
				'label' => esc_html__( 'Layout' ),
				'type'  => 'separator',
			);

			// Control for the customerLayout
			$this->controls['customerLayout'] = array(
				'group'    => 'contentCustomerDetails',
				'label'    => esc_html__( 'Structure' ),
				'type'     => 'select',
				'options'  => array(
					'50'  => esc_html__( 'Two Columns' ),
					'100' => esc_html__( 'Full Width' ),
				),
				'default'  => '50',
				'inline'   => true,
				'rerender' => true,
				'css'      => array(),
			);

			// Control for enabling extra content
			$this->controls['enableExtraContent'] = array(
				'group'   => 'contentCustomerDetails',
				'label'   => esc_html__( 'Show Extra Thankyou Content' ),
				'description' => esc_html__( 'When enabled, this will display additional content/hooks from WooCommerce on the thank you page. Useful for compatibility with payment gateways and plugins.' ),
				'type'    => 'checkbox',
				'default' => false,
				'rerender' => true,
			);

			$this->controls['typographySectionHeading'] = array(
				'group'   => 'styleHeading',
				'label'   => esc_html__( 'Typography' ),
				'type'    => 'typography',
				'exclude' => array( 'text-align' ), //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				'default' => array(
					'font-family' => 'Open Sans',
					'font-weight' => '600',
					'font-size'   => '24px',
					'line-height' => '1.5em',
				),
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '.bricks-customer-details-wrapper .wfty-customer-info-heading.wfty_title',
					),
				),
			);

			$this->controls['_alignSelf']['default'] = 'stretch';

			$this->controls['headingAlign'] = array(
				'group'   => 'styleHeading',
				'label'   => esc_html__( 'Alignment' ),
				'type'    => 'text-align',
				'exclude' => array( 'justify' ), //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				'css'     => array(
					array(
						'property' => 'text-align',
						'selector' => '.bricks-customer-details-wrapper .wfty_title',
					),
				),
			);

			$this->controls['headingLabel'] = array(
				'group' => 'styleDetails',
				'label' => esc_html__( 'Heading' ),
				'type'  => 'separator',
			);

			$this->controls['typographyHeading'] = array(
				'group'   => 'styleDetails',
				'label'   => esc_html__( 'Typography' ),
				'type'    => 'typography',
				'exclude' => array( 'text-align' ), //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				'default' => array(
					'font-family' => 'Open Sans',
					'font-weight' => '400',
					'font-size'   => '20px',
					'line-height' => '1.5em',
					'color'       => array(
						'hex' => '#000000',
					),
				),
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '.bricks-customer-details-wrapper .wfty_customer_info .wfty_text_bold strong',
					),
				),
			);

			$this->controls['detailsLabel'] = array(
				'group' => 'styleDetails',
				'label' => esc_html__( 'Details' ),
				'type'  => 'separator',
			);

			$this->controls['typographyDetails'] = array(
				'group'   => 'styleDetails',
				'label'   => esc_html__( 'Typography' ),
				'type'    => 'typography',
				'exclude' => array( 'text-align' ), //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				'default' => array(
					'font-family' => 'Open Sans',
					'font-weight' => '400',
					'font-size'   => '15px',
					'line-height' => '1.5em',
					'color'       => array(
						'hex' => '#565656',
					),
				),
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '.bricks-customer-details-wrapper .wffn_customer_details_table .wfty_wrap .wfty_box.wfty_customer_details_2_col table tr th, .bricks-customer-details-wrapper .wffn_customer_details_table .wfty_wrap .wfty_box.wfty_customer_details_2_col table tr td, .bricks-customer-details-wrapper .wffn_customer_details_table, .bricks-customer-details-wrapper .wfty_view, .bricks-customer-details-wrapper .wffn_customer_details_table *',
					),
				),
			);
		}

		/**
		 * Renders the customer details element.
		 */
		public function render() {
			$settings       = $this->settings;
			$heading_text   = $settings['customerDetailsHeading'];
			$layout_setting = $settings['customerLayout'];
			$enable_extra_content = !empty($settings['enableExtraContent']) ? 'yes' : 'no';

			if ( $layout_setting === '50' ) {
				$layout_setting = '2c';
			}

			$this->set_attribute( 'wrapper', 'class', 'bricks-customer-details-wrapper' );

			?>
            <div <?php echo $this->render_attributes( '_root' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                <div <?php echo $this->render_attributes( 'wrapper' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<?php
					echo do_shortcode( '[wfty_customer_details layout_settings ="' . $layout_setting . '" customer_details_heading="' . $heading_text . '" enable_extra_content="' . $enable_extra_content . '"]' );
					?>
                </div>
            </div>
			<?php
		}
	}
}