<?php

namespace FunnelKit\Bricks\Elements\ThankYouPages;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( '\FunnelKit\Bricks\Elements\ThankYouPages\Order_Details' ) ) {
	class Order_Details extends \Bricks\Element {
		public $category = 'funnelkit';
		public $name = 'wfty-order-detail';
		public $icon = 'wfty-icon-offer_title';

		/**
		 * Retrieves the label for the "Order Details" element.
		 *
		 * @return string The label for the "Order Details" element.
		 */
		public function get_label() {
			return esc_html__( 'Order Details' );
		}

		/**
		 * Sets the control groups for the order details class.
		 *
		 * This method initializes the control groups array with the necessary elements
		 * for the order details class. Each control group is an associative array with
		 * a title and a tab. The control groups are then added to the main control groups
		 * array. The method also sets the common control groups and removes the '_typography'
		 * control group.
		 *
		 * @return void
		 */
		public function set_control_groups() {
			$this->control_groups = array();

			$this->control_groups['contentOrderDetails'] = array(
				'title' => esc_html__( 'Order Details' ),
				'tab'   => 'content',
			);

			$this->control_groups['contentSubscription'] = array(
				'title' => esc_html__( 'Subscription' ),
				'tab'   => 'content',
			);

			$this->control_groups['contentDownload'] = array(
				'title' => esc_html__( 'Download' ),
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

			$this->control_groups['styleSubscription'] = array(
				'title' => esc_html__( 'Subscription' ),
				'tab'   => 'style',
			);

			$this->control_groups['styleDownload'] = array(
				'title' => esc_html__( 'Download' ),
				'tab'   => 'style',
			);

			$this->set_common_control_groups();

			unset( $this->control_groups['_typography'] );
		}

		/**
		 * Set the controls for the Order Details element.
		 */
		public function set_controls() {
			$defaults = WFFN_Core()->thank_you_pages->default_shortcode_settings();

			$this->controls['orderDetailsHeading'] = array(
				'group'   => 'contentOrderDetails',
				'label'   => esc_html__( 'Heading' ),
				'type'    => 'text',
				'default' => isset( $defaults['order_details_heading'] ) ? $defaults['order_details_heading'] : __( 'Order Details' ),
			);

			$this->controls['subscriptionHeadingNotice'] = array(
				'group'   => 'contentSubscription',
				'content' => esc_html__( 'This section will only show up in case of order will have subscription.' ),
				'type'    => 'info',
			);

			$this->controls['orderSubscriptionHeading'] = array(
				'group'   => 'contentSubscription',
				'label'   => esc_html__( 'Heading' ),
				'type'    => 'text',
				'default' => isset( $defaults['order_subscription_heading'] ) ? $defaults['order_subscription_heading'] : __( 'Subscription' ),
			);

			$this->controls['orderSubscriptionPreview'] = array(
				'group' => 'contentSubscription',
				'label' => esc_html__( 'Show Subscription Preview' ),
				'type'  => 'checkbox',
			);

			$this->controls['downloadsHeadingNotice'] = array(
				'group'   => 'contentDownload',
				'content' => esc_html__( 'This section will only show up in case of order will have downloads.' ),
				'type'    => 'info',
			);

			$this->controls['orderDownloadHeading'] = array(
				'group'   => 'contentDownload',
				'label'   => esc_html__( 'Heading' ),
				'type'    => 'text',
				'default' => isset( $defaults['order_download_heading'] ) ? $defaults['order_download_heading'] : __( 'Downloads' ),
			);

			$this->controls['orderDownloadsBtnText'] = array(
				'group'   => 'contentDownload',
				'label'   => esc_html__( 'Download Button Text' ),
				'type'    => 'text',
				'default' => $defaults['order_downloads_btn_text'],
			);

			$this->controls['orderDownloadPreview'] = array(
				'group' => 'contentDownload',
				'label' => esc_html__( 'Show Download Preview' ),
				'type'  => 'checkbox',
			);

			$this->controls['orderDownloadsShowFileDownloads'] = array(
				'group' => 'contentDownload',
				'label' => esc_html__( 'Show File Downloads Column' ),
				'type'  => 'checkbox',
			);

			$this->controls['orderDownloadsShowFileExpiry'] = array(
				'group' => 'contentDownload',
				'label' => esc_html__( 'Show File Expiry Column' ),
				'type'  => 'checkbox',
			);

			$this->controls['typographyHeading'] = array(
				'group'   => 'styleHeading',
				'label'   => esc_html__( 'Typography' ),
				'type'    => 'typography',
				'default' => array(
					'font-family' => 'Open Sans',
					'font-weight' => '600',
					'font-size'   => '24px',
					'line-height' => '1.5em',
					'color'       => array(
						'hex' => '#000000',
					),
				),
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '.bricks-order-details-wrapper .wffn_order_details_table .wfty_title',
					),
				),
			);

			$this->controls['_alignSelf']['default'] = 'stretch';

			$this->controls['headingAlign'] = array(
				'group' => 'styleHeading',
				'label' => esc_html__( 'Heading Alignment' ),
				'type'  => 'text-align',
				'css'   => array(
					array(
						'property' => 'text-align',
						'selector' => '.bricks-order-details-wrapper .wffn_order_details_table .wfty_title',
					),
				),
			);

			$this->controls['productLabel'] = array(
				'group' => 'styleDetails',
				'label' => esc_html__( 'Product' ),
				'type'  => 'separator',
			);

			$this->controls['productTypography'] = array(
				'group'   => 'styleDetails',
				'label'   => esc_html__( 'Typography' ),
				'type'    => 'typography',
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
						'selector' => '.bricks-order-details-wrapper .wffn_order_details_table .wfty_pro_list_cont .wfty_pro_list * , .wffn_order_details_table .wfty_pro_list_cont .wfty_p_name *',
					),
				),
			);

			$this->controls['orderDetailsImg'] = array(
				'group'   => 'styleDetails',
				'label'   => esc_html__( 'Show Images' ),
				'type'    => 'checkbox',
				'default' => true,
			);

			$this->controls['subtotalLabel'] = array(
				'group' => 'styleDetails',
				'label' => esc_html__( 'Subtotal' ),
				'type'  => 'separator',
			);

			$this->controls['subtotalTypography'] = array(
				'group'   => 'styleDetails',
				'label'   => esc_html__( 'Typography' ),
				'type'    => 'typography',
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
						'selector' => '.bricks-order-details-wrapper .wffn_order_details_table .wfty_pro_list_cont table tr:not(:last-child) *',
					),
				),
			);

			$this->controls['totalLabel'] = array(
				'group' => 'styleDetails',
				'label' => esc_html__( 'Total' ),
				'type'  => 'separator',
			);

			$this->controls['totalTypography'] = array(
				'group'   => 'styleDetails',
				'label'   => esc_html__( 'Typography' ),
				'type'    => 'typography',
				'default' => array(
					'font-family' => 'Open Sans',
					'font-weight' => '600',
					'font-size'   => '20px',
					'line-height' => '1.5em',
					'color'       => array(
						'hex' => '#565656',
					),
				),
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '.bricks-order-details-wrapper .wffn_order_details_table .wfty_pro_list_cont table tr:last-child *',
					),
				),
			);

			$this->controls['variationLabel'] = array(
				'group' => 'styleDetails',
				'label' => esc_html__( 'Variation' ),
				'type'  => 'separator',
			);

			$this->controls['variationTypography'] = array(
				'group'   => 'styleDetails',
				'label'   => esc_html__( 'Typography' ),
				'type'    => 'typography',
				'default' => array(
					'font-family' => 'Open Sans',
					'font-weight' => '400',
					'font-size'   => '12px',
					'line-height' => '1.5em',
					'color'       => array(
						'hex' => '#000000',
					),
				),
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '.bricks-order-details-wrapper .wffn_order_details_table .wfty_pro_list_cont .wfty_pro_list .wfty_info *',
					),
				),
			);

			$this->controls['dividerLabel'] = array(
				'group' => 'styleDetails',
				'label' => esc_html__( 'Divider' ),
				'type'  => 'separator',
			);

			$this->controls['dividerColor'] = array(
				'group'   => 'styleDetails',
				'label'   => esc_html__( 'Color' ),
				'type'    => 'color',
				'css'     => array(
					array(
						'property' => 'border-color',
						'selector' => '.bricks-order-details-wrapper .wffn_order_details_table table',
					),
					array(
						'property' => 'color',
						'selector' => '.bricks-order-details-wrapper .wfty_pro_list_cont .wfty_pro_list .wfty-hr',
					),
					array(
						'property' => 'background-color',
						'selector' => '.bricks-order-details-wrapper .wfty_pro_list_cont .wfty_pro_list .wfty-hr',
					),
					array(
						'property' => 'border-top-color',
						'selector' => '.wfty_order_details table tfoot tr:last-child th, .wfty_order_details table tfoot tr:last-child td',
					),
					array(
						'property' => 'opacity',
						'selector' => '.bricks-order-details-wrapper .wfty_pro_list_cont .wfty_pro_list .wfty-hr',
						'value'    => '1',
					),
					array(
						'property' => 'border',
						'selector' => '.bricks-order-details-wrapper .wfty_pro_list_cont .wfty_pro_list .wfty-hr',
						'value'    => 'none',
					),
				),
				'default' => array(
					'hex' => '#dddddd',
				),
			);

			$this->controls['subscriptionLabel'] = array(
				'group' => 'styleSubscription',
				'label' => esc_html__( 'Subscription' ),
				'type'  => 'separator',
			);

			$this->controls['subscriptionTypography'] = array(
				'group'   => 'styleSubscription',
				'label'   => esc_html__( 'Typography' ),
				'type'    => 'typography',
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
						'selector' => '.bricks-order-details-wrapper .wffn_order_details_table .wfty_wrap .wfty_subscription table *, .bricks-order-details-wrapper .wffn_order_details_table .wfty_wrap .wfty_subscription table tr th, .bricks-order-details-wrapper .wffn_order_details_table .wfty_wrap .wfty_subscription table tr td, .bricks-order-details-wrapper .wffn_order_details_table .wfty_wrap .wfty_subscription table tr td:before',
					),
				),
			);

			$this->controls['subscriptionButtonLabel'] = array(
				'group' => 'styleSubscription',
				'label' => esc_html__( 'Button' ),
				'type'  => 'separator',
			);

			$this->controls['buttonTextColor'] = array(
				'group'   => 'styleSubscription',
				'label'   => esc_html__( 'Label' ),
				'type'    => 'color',
				'css'     => array(
					array(
						'property' => 'color',
						'selector' => '.bricks-order-details-wrapper .wffn_order_details_table .wfty_wrap .wfty_subscription table tr td.subscription-actions a',
					),
				),
				'default' => array(
					'hex' => '#ffffff',
				),
			);

			$this->controls['buttonBackgroundColor'] = array(
				'group'   => 'styleSubscription',
				'label'   => esc_html__( 'Background' ),
				'type'    => 'color',
				'css'     => array(
					array(
						'property' => 'background-color',
						'selector' => '.bricks-order-details-wrapper .wffn_order_details_table .wfty_wrap .wfty_subscription table tr td.subscription-actions a',
					),
				),
				'default' => array(
					'hex' => '#70dc1d',
				),
			);

			$this->controls['downloadTypography'] = array(
				'group'   => 'styleDownload',
				'label'   => esc_html__( 'Typography' ),
				'type'    => 'typography',
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
						'selector' => '.bricks-order-details-wrapper .wfty_wrap table.wfty_order_downloads *, .bricks-order-details-wrapper .wfty_wrap table.wfty_order_downloads td:before',
					),
				),
			);

			$this->controls['downloadButtonLabel'] = array(
				'group' => 'styleDownload',
				'label' => esc_html__( 'Button' ),
				'type'  => 'separator',
			);

			$this->controls['downloadButtonTextColor'] = array(
				'group'   => 'styleDownload',
				'label'   => esc_html__( 'Label' ),
				'type'    => 'color',
				'css'     => array(
					array(
						'property' => 'color',
						'selector' => '.bricks-order-details-wrapper .wfty_wrap table.wfty_order_downloads tr td.download-file a',
					),
				),
				'default' => array(
					'hex' => '#ffffff',
				),
			);

			$this->controls['downloadBackgroundColor'] = array(
				'group'   => 'styleDownload',
				'label'   => esc_html__( 'Background' ),
				'type'    => 'color',
				'css'     => array(
					array(
						'property' => 'background-color',
						'selector' => '.bricks-order-details-wrapper .wfty_wrap table.wfty_order_downloads tr td.download-file a',
					),
				),
				'default' => array(
					'hex' => '#70dc1d',
				),
			);

			$this->controls['downloadsStyleNotice'] = array(
				'group'   => 'styleDownload',
				'content' => esc_html__( 'This section will only show up in case of order will have downloads.' ),
				'type'    => 'info',
			);
		}

		/**
		 * Renders the order details element.
		 *
		 * This method is responsible for rendering the order details element on the thankyou page.
		 * It retrieves the necessary settings from the class instance and uses them to generate the HTML output.
		 *
		 * @return void
		 * @since 1.0.0
		 *
		 */
		public function render() {
			$settings                   = $this->settings;
			$classes                    = 'bricks-order-details-wrapper';
			$order_heading_text         = $settings['orderDetailsHeading'];
			$order_subscription_heading = isset( $settings['orderSubscriptionHeading'] ) ? $settings['orderSubscriptionHeading'] : '';

			$download_btn_text       = $settings['orderDownloadsBtnText'];
			$show_column_download    = isset( $settings['orderDownloadsShowFileDownloads'] ) ? 'true' : 'false';
			$show_column_file_expiry = isset( $settings['orderDownloadsShowFileExpiry'] ) ? 'true' : 'false';
			$order_download_heading  = isset( $settings['orderDownloadHeading'] ) ? $settings['orderDownloadHeading'] : '';
			$classes                 .= isset( $settings['orderDownloadPreview'] ) ? '' : ' wfty-hide-download';
			$classes                 .= isset( $settings['orderSubscriptionPreview'] ) ? '' : ' wfty-hide-subscription';

			$order_details_img = 'false';
			if ( isset( $settings['orderDetailsImg'] ) ) {
				$order_details_img = 'true';
			}

			$this->set_attribute( 'wrapper', 'class', $classes );
			?>
            <div <?php echo $this->render_attributes( '_root' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                <div <?php echo $this->render_attributes( 'wrapper' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<?php
					echo do_shortcode( '[wfty_order_details order_details_img="' . $order_details_img . '" order_details_heading="' . $order_heading_text . '" order_subscription_heading="' . $order_subscription_heading . '" order_download_heading="' . $order_download_heading . '" order_downloads_btn_text="' . $download_btn_text . '" order_downloads_show_file_downloads="' . $show_column_download . '"  order_downloads_show_file_expiry="' . $show_column_file_expiry . '"]' );
					?>
                </div>
            </div>
			<?php
		}
	}
}