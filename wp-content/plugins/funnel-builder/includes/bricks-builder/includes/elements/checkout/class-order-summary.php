<?php

namespace FunnelKit\Bricks\Elements\Checkout;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use FunnelKit\Bricks\Elements\Element;
use WFACP_Common;

if ( ! class_exists( '\FunnelKit\Bricks\Elements\Checkout\Order_Summary' ) ) {
	class Order_Summary extends Element {
		public $category = 'funnelkit';
		public $name = 'wfacp-order-summary';
		public $icon = 'wfacp-icon-icon_minicart';

		/**
		 * Retrieves the label for the Mini Cart element.
		 *
		 * @return string The label for the Mini Cart element.
		 */
		public function get_label() {
			return esc_html__( 'Mini Cart' );
		}

		/**
		 * Sets the control groups for the order summary element.
		 *
		 * This method initializes the control groups array for the order summary element.
		 * Each control group represents a section of the order summary element and contains
		 * a title and a tab. The control groups are used for organizing the settings of the
		 * order summary element in the admin panel.
		 *
		 * @return void
		 */
		public function set_control_groups() {
			$this->control_groups = array();

			$this->control_groups['contentHeading'] = array(
				'title' => esc_html__( 'Heading' ),
				'tab'   => 'content',
			);

			$this->control_groups['contentProducts'] = array(
				'title' => esc_html__( 'Products' ),
				'tab'   => 'content',
			);

			$this->control_groups['contentCoupon'] = array(
				'title' => esc_html__( 'Coupon' ),
				'tab'   => 'content',
			);

			$this->control_groups['styleHeading'] = array(
				'title' => esc_html__( 'Heading' ),
				'tab'   => 'style',
			);

			$this->control_groups['styleProducts'] = array(
				'title' => esc_html__( 'Products' ),
				'tab'   => 'style',
			);

			$this->control_groups['styleCoupon'] = array(
				'title'    => esc_html__( 'Coupon' ),
				'tab'      => 'style',
				'required' => array( 'enable_coupon', '=', true ),
			);

			$this->control_groups['styleCartTotal'] = array(
				'title' => esc_html__( 'Cart Total' ),
				'tab'   => 'style',
			);

			$this->control_groups['styleSettings'] = array(
				'title' => esc_html__( 'Settings' ),
				'tab'   => 'style',
			);

			$this->set_common_control_groups();

			unset( $this->control_groups['_typography'] );
		}

		/**
		 * Sets the controls for the Order Summary element.
		 */
		public function set_controls() {
			/**
			 * Heading
			 */
			$this->controls['mini_cart_heading'] = array(
				'group'   => 'contentHeading',
				'label'   => esc_html__( 'Title' ),
				'type'    => 'text',
				'default' => esc_html__( 'Order Summary' ),
			);

			/**
			 * Products
			 */
			$this->controls['enable_product_image'] = array(
				'group'   => 'contentProducts',
				'label'   => esc_html__( 'Image' ),
				'type'    => 'checkbox',
				'default' => true,
			);

			$this->controls['enable_quantity_box'] = array(
				'group'   => 'contentProducts',
				'label'   => esc_html__( 'Quantity Switcher' ),
				'type'    => 'checkbox',
				'default' => false,
			);

			$this->controls['enable_delete_item'] = array(
				'group'   => 'contentProducts',
				'label'   => esc_html__( 'Allow Deletion' ),
				'type'    => 'checkbox',
				'default' => false,
			);

			/**
			 * Mini Cart Strike Through
			 */

			$this->controls['mini_cart_enable_strike_through_price'] = array(
				'group'   => 'contentProducts',
				'label'   => __( 'Regular & Discounted Price', 'woofunnels-aero-checkout' ),
				'type'    => 'checkbox',
				'default' => false,
			);

			$this->controls['mini_cart_enable_low_stock_trigger'] = array(
				'group'   => 'contentProducts',
				'label'   => __( 'Low Stock Trigger', 'woofunnels-aero-checkout' ),
				'type'    => 'checkbox',
				'default' => false,
			);

			$this->controls['mini_cart_low_stock_message'] = array(
				'group'    => 'contentProducts',
				'label'    => __( 'Message', 'woofunnels-aero-checkout' ),
				'type'     => 'text',
				'default'  => __( '{{quantity}} LEFT IN STOCK', 'woofunnels-aero-checkout' ),
				'required' => array( 'mini_cart_enable_low_stock_trigger', '=', true ),
			);

			$this->controls['mini_cart_enable_saving_price_message'] = array(
				'group'   => 'contentProducts',
				'label'   => __( 'Total Saving', 'woofunnels-aero-checkout' ),
				'type'    => 'checkbox',
				'default' => false,
			);
			$this->controls['mini_cart_saving_price_message']        = array(
				'group'    => 'contentProducts',
				'label'    => __( 'Message', 'woofunnels-aero-checkout' ),
				'type'     => 'text',
				'default'  => __( 'You saved {{saving_amount}} ({{saving_percentage}}) on this order', 'woofunnels-aero-checkout' ),
				'required' => array( 'mini_cart_enable_saving_price_message', '=', true ),
			);

			/**
			 * Coupon
			 */
			$this->controls['enable_coupon'] = array(
				'group'   => 'contentCoupon',
				'label'   => esc_html__( 'Enable' ),
				'type'    => 'checkbox',
				'default' => false,
			);

			$this->controls['enable_coupon_collapsible'] = array(
				'group'    => 'contentCoupon',
				'label'    => esc_html__( 'Collapsible' ),
				'type'     => 'checkbox',
				'default'  => false,
				'required' => array( 'enable_coupon', '=', true ),
			);

			$this->controls['mini_cart_coupon_button_text'] = array(
				'group'    => 'contentCoupon',
				'label'    => esc_html__( 'Coupon Button Text' ),
				'type'     => 'text',
				'default'  => esc_html__( __( 'Apply', 'woocommerce' )  ),
				'required' => array( 'enable_coupon', '=', true ),
			);

			/**
			 * Heading
			 */
			$this->controls['mini_cart_section_typo'] = array(
				'group' => 'styleHeading',
				'label' => esc_html__( 'Typography' ),
				'type'  => 'typography',
				'css'   => array(
					array(
						'property' => 'typography',
						'selector' => '.wfacp_mini_cart_start_h .wfacp-order-summary-label',
					),
				),
			);

			/* ------------------------------------ Products Start------------------------------------ */

			$mini_cart_product_typo = [
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container table.wfacp_mini_cart_items',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container tr.cart_item td:not(.product-total)',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container table.wfacp_mini_cart_items .product-total > span bdi',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container table.wfacp_mini_cart_items .product-total > ins span bdi',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container table.wfacp_mini_cart_items .product-total > span:not(.wfacp_cart_product_name_h):not(.wfacp_delete_item_wrap)',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container table.wfacp_mini_cart_items .product-total ins span:not(.wfacp_cart_product_name_h):not(.wfacp_delete_item_wrap)',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container table.wfacp_mini_cart_items .product-total small',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container table.wfacp_mini_cart_items dl',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container table.wfacp_mini_cart_items dt',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container table.wfacp_mini_cart_items dd',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container table.wfacp_mini_cart_items dd p',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container tr.cart_item td .product-name',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container tr.cart_item td small',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container span.subscription-details',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container tr.cart_item td p',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container tr.cart_item td .product-name span:not(.subscription-details)',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container tr.cart_item td .product-name',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container tr.cart_item td .product-name bdi',
			];




			$css = array();
			foreach ( $mini_cart_product_typo as $selector ) {
				$css[] = array(
					'property' => 'typography',
					'selector' => $selector,
				);
			}
			$this->controls['mini_cart_product_typo'] = array(
				'group' => 'styleProducts',
				'label' => esc_html__( 'Typography' ),
				'type'  => 'typography',
				'inline'  => true,
				'popup'   => false,
				'css'   => $css,
			);


			$this->controls['mini_cart_product_image_border'] = array(
				'group' => 'styleProducts',
				'label' => esc_html__( 'Image Border' ),
				'type'  => 'border',
				'css'   => array(
					array(
						'property' => 'border',
						'selector' => '.wfacp_mini_cart_start_h .wfacp_order_sum .product-image .wfacp-pro-thumb img',
					),
				),
			);

			/* ------------------------------------ End ------------------------------------ */

			/**
			 * Strike Through Style Setting
			 */

			$selector            = '.wfacp_mini_cart_start_h .wfacp_order_summary_container.wfacp_min_cart_widget';
			$strike_through_typo = [
				$selector . ' .product-total del',
				$selector . ' .product-total del *',
				$selector . ' .product-total del span.woocommerce-Price-currencySymbol',
			];

			$this->controls['mini_cart_strike_through_typo'] = array(
				'group' => 'styleProducts',
				'label' => esc_html__( 'Strike Through Typography' ),
				'type'  => 'typography',
				'css'   => array(
					array(
						'property' => 'typography',
						'selector' => implode( ',', $strike_through_typo ),
					),
				),
			);

			/**
			 * Low Stock Message Style Setting
			 */
			$mini_cart_low_stock_message                        = [
				$selector . ' .wfacp_stocks',
			];
			$this->controls['mini_cart_low_stock_message_typo'] = array(
				'group' => 'styleProducts',
				'label' => esc_html__( 'Low Stock Typography' ),
				'type'  => 'typography',
				'css'   => array(
					array(
						'property' => 'typography',
						'selector' => implode( ',', $mini_cart_low_stock_message ),
					),
				),
			);

			/**
			 * Saved Price Setting
			 *
			 */
			$mini_saving_price_message = [
				$selector . ' table.shop_table tr:not(.order-total):not(.cart-discount).wfacp-saving-amount td',
				$selector . ' table.shop_table tr:not(.order-total):not(.cart-discount).wfacp-saving-amount td svg path',
				$selector . ' table.shop_table tr:not(.order-total):not(.cart-discount).wfacp-saving-amount td *',
				$selector . ' table.shop_table tr:not(.order-total):not(.cart-discount).wfacp-saving-amount td span *',
			];


			$this->controls['mini_cart_enable_saving_price_message_typo'] = array(
				'group' => 'styleProducts',
				'label' => esc_html__( 'Save Price Typography' ),
				'type'  => 'typography',
				'css'   => array(
					array(
						'property' => 'typography',
						'selector' => implode( ',', $mini_saving_price_message ),
					),
				),
			);

			/* ------------------------------------ Coupon Fields Start ------------------------------------ */

			$this->controls['separatorStyleCouponLink'] = array(
				'group'    => 'styleCoupon',
				'label'    => esc_html__( 'Link' ),
				'type'     => 'separator',
				'required' => array( 'enable_coupon_collapsible', '=', true ),
			);

			$this->controls['mini_cart_coupon_heading_typo'] = array(
				'group'    => 'styleCoupon',
				'label'    => esc_html__( 'Typography' ),
				'type'     => 'typography',
				'css'      => array(
					array(
						'property' => 'typography',
						'selector' => '.wfacp_mini_cart_start_h .wfacp-coupon-section .wfacp-coupon-page .wfacp_main_showcoupon',
					),
				),
				'required' => array( 'enable_coupon_collapsible', '=', true ),
			);

			$this->controls['separatorStyleCouponField'] = array(
				'group' => 'styleCoupon',
				'label' => esc_html__( 'Field' ),
				'type'  => 'separator',
			);

			$this->controls['wfacp_form_mini_cart_coupon_label_typo'] = array(
				'group'   => 'styleCoupon',
				'label'   => esc_html__( 'Label Typography' ),
				'type'    => 'typography',
				'default' => array(
					'font-weight' => '400',
				),
				'css'     => array(
					array(
						'selector' => '.wfacp_mini_cart_start_h form.checkout_coupon.woocommerce-form-coupon .wfacp-form-control-label',
						'property' => 'typography',
					),
					array(
						'selector' => '.wfacp_mini_cart_start_h form.checkout_coupon.woocommerce-form-coupon',
						'property' => 'background-color',
						'value'    => 'inherit',
					),
					array(
						'selector' => '.wfacp_mini_cart_start_h form.checkout_coupon.woocommerce-form-coupon .wfacp-row',
						'property' => 'display',
						'value'    => 'flex',
					),
					array(
						'selector' => '.wfacp_mini_cart_start_h form.checkout_coupon.woocommerce-form-coupon .wfacp-row form-row',
						'property' => 'flex',
						'value'    => '1',
					),
					array(
						'selector' => '.wfacp_mini_cart_start_h form.checkout_coupon.wfacp_display_block',
						'property' => 'margin-top',
						'value'    => '0px',
					),
					array(
						'selector' => '.wfacp_mini_cart_start_h form.checkout_coupon.wfacp_display_none',
						'property' => 'margin-bottom',
						'value'    => '0px',
					),
				),
			);

			$this->controls['wfacp_form_mini_cart_coupon_input_typo'] = array(
				'group'   => 'styleCoupon',
				'label'   => esc_html__( 'Coupon Typography' ),
				'type'    => 'typography',
				'css'     => array(
					array(
						'property' => 'typography',
						'selector' => '.wfacp_mini_cart_start_h form.checkout_coupon.woocommerce-form-coupon .wfacp-form-control',
					),
				),
				'exclude' => array( 'text-align' ), //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
			);

			$this->set_current_group( 'styleCoupon' );
			$this->add_border_color( 'wfacp_form_mini_cart_coupon_focus_color', array( '.wfacp_mini_cart_start_h form.checkout_coupon.woocommerce-form-coupon .wfacp-form-control:focus' ), '#61bdf7', __( 'Focus Color' ), true, array(), '.wfacp_mini_cart_start_h' );

			$this->controls['wfacp_form_mini_cart_coupon_border'] = array(
				'group'   => 'styleCoupon',
				'label'   => esc_html__( 'Border' ),
				'type'    => 'border',
				'default' => array(
					'radius' => array(
						'top'    => 4,
						'right'  => 4,
						'bottom' => 4,
						'left'   => 4,
					),
				),
				'css'     => array(
					array(
						'property' => 'border',
						'selector' => '.wfacp_mini_cart_start_h form.checkout_coupon.woocommerce-form-coupon .wfacp-form-control',
					),
				),
			);

			$this->controls['separatorStyleCouponButton'] = array(
				'group' => 'styleCoupon',
				'label' => esc_html__( 'Button' ),
				'type'  => 'separator',
			);

			$this->controls['mini_cart_coupon_btn_color'] = array(
				'group' => 'styleCoupon',
				'label' => esc_html__( 'Background' ),
				'type'  => 'color',
				'css'   => array(
					array(
						'property' => 'background-color',
						'selector' => '.wfacp_mini_cart_start_h button.wfacp-coupon-btn',
					),
				),
			);

			$this->controls['wfacp_form_mini_cart_coupon_button_typo'] = array(
				'group' => 'styleCoupon',
				'label' => esc_html__( 'Button Typography' ),
				'type'  => 'typography',
				'css'   => array(
					array(
						'property' => 'typography',
						'selector' => '.wfacp_mini_cart_start_h button.wfacp-coupon-btn',
					),
				),
			);

			/* ------------------------------------ End ------------------------------------ */

			/* ------------------------------------ Subtotal Start------------------------------------ */

			$this->controls['separatorStyleSubtotal'] = array(
				'group' => 'styleCartTotal',
				'label' => esc_html__( 'Subtotal' ),
				'type'  => 'separator',
			);


			$mini_cart_product_meta_typo = [
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container table.wfacp_mini_cart_reviews tr:not(.order-total):not(.cart-discount):not(.wfacp-saving-amount)',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container table.wfacp_mini_cart_reviews tr:not(.order-total):not(.cart-discount):not(.wfacp-saving-amount) td',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container table.wfacp_mini_cart_reviews tr:not(.order-total):not(.cart-discount):not(.wfacp-saving-amount) th',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container table.wfacp_mini_cart_reviews tr:not(.order-total):not(.cart-discount):not(.wfacp-saving-amount) th span',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container table.wfacp_mini_cart_reviews tr:not(.order-total):not(.cart-discount):not(.wfacp-saving-amount) td span',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container table.wfacp_mini_cart_reviews tr:not(.order-total):not(.cart-discount):not(.wfacp-saving-amount) td small',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container table.wfacp_mini_cart_reviews tr:not(.order-total):not(.cart-discount):not(.wfacp-saving-amount) td bdi',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container table.wfacp_mini_cart_reviews tr:not(.order-total):not(.cart-discount):not(.wfacp-saving-amount) td a',
			];


			$css = array();
			foreach ( $mini_cart_product_meta_typo as $selector ) {
				$css[] = array(
					'property' => 'typography',
					'selector' => $selector,
				);
			}


			$this->controls['mini_cart_product_meta_typo'] = array(
				'group' => 'styleCartTotal',
				'label' => esc_html__( 'Typography' ),
				'type'  => 'typography',
				'css'   => $css,
				'inline'  => true,
			);

			/* ------------------------------------ End ------------------------------------ */

			/* ------------------------------------ Coupon Start------------------------------------ */

			$this->controls['separatorStyleCouponCode'] = array(
				'group' => 'styleCartTotal',
				'label' => esc_html__( 'Coupon code' ),
				'type'  => 'separator',
			);

			$coupon_selector = array(
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .shop_table tbody tr.cart-discount th',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .shop_table tbody tr.cart-discount th span',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .shop_table tbody tr.cart-discount td',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .shop_table tbody tr.cart-discount td span',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .shop_table tbody tr.cart-discount td a',
			);

			$this->controls['mini_cart_coupon_display_font_size'] = array(
				'group'   => 'styleCartTotal',
				'label'   => esc_html__( 'Font Size (in px)' ),
				'type'    => 'slider',
				'css'     => $this->generate_css( $coupon_selector, 'font-size' ),
				'units'   => array(
					'px' => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default' => '14px',
			);

			$coupon_selector_label_color = array(
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .shop_table tbody tr.cart-discount th',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .shop_table tbody tr.cart-discount th span:not(.wfacp_coupon_code)',
			);

			$this->controls['mini_cart_coupon_display_label_color'] = array(
				'group' => 'styleCartTotal',
				'label' => esc_html__( 'Text Color' ),
				'type'  => 'color',
				'css'   => array(
					array(
						'property' => 'color',
						'selector' => implode( ',', $coupon_selector_label_color ),
					),
				),
			);

			$coupon_selector_val_color = array(
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .shop_table tbody tr.cart-discount td',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .shop_table tbody tr.cart-discount td span',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .shop_table tbody tr.cart-discount td a',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .shop_table .cart-discount td span',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .shop_table .cart-discount td span bdi',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .shop_table .cart-discount th .wfacp_coupon_code',
			);

			$this->controls['mini_cart_coupon_display_val_color'] = array(
				'group'   => 'styleCartTotal',
				'label'   => esc_html__( 'Code Color' ),
				'type'    => 'color',
				'default' => array(
					'hex' => '#24ae4e',
				),
				'css'     => array(
					array(
						'property' => 'color',
						'selector' => implode( ',', $coupon_selector_val_color ),
					),
				),
			);

			/* ------------------------------------ End ------------------------------------ */

			/* ------------------------------------ Total Start------------------------------------ */

			$this->controls['separatorStyleTotal'] = array(
				'group' => 'styleCartTotal',
				'label' => esc_html__( 'Total' ),
				'type'  => 'separator',
			);

			$cart_total_color_option = array(
				'.wfacp_mini_cart_start_h table.shop_table tr.order-total td span.amount',
				'.wfacp_mini_cart_start_h table.shop_table tr.order-total td span.amount bdi',
				'.wfacp_mini_cart_start_h table.shop_table tr.order-total td',
				'.wfacp_mini_cart_start_h table.shop_table tr.order-total td span',
				'.wfacp_mini_cart_start_h table.shop_table tr.order-total td small',
				'.wfacp_mini_cart_start_h table.shop_table tr.order-total th',
				'.wfacp_mini_cart_start_h table.shop_table tr.order-total th span',
			);

			$cart_total_label_typo_option = array(
				'.wfacp_mini_cart_start_h  table.shop_table tbody tr.order-total th',
				'.wfacp_mini_cart_start_h  table.shop_table tbody tr.order-total th',
				'.wfacp_mini_cart_start_h  table.shop_table tbody tr.order-total th span',
				'.wfacp_mini_cart_start_h  table.shop_table tbody tr.order-total th small',
				'.wfacp_mini_cart_start_h  table.shop_table tbody tr.order-total th a',
			);

			$cart_total_value_typo_option = array(
				'.wfacp_mini_cart_start_h  table.shop_table tbody tr.order-total td',
				'.wfacp_mini_cart_start_h  table.shop_table tbody tr.order-total td',
				'.wfacp_mini_cart_start_h  table.shop_table tbody tr.order-total td span.woocommerce-Price-amount.amount',
				'.wfacp_mini_cart_start_h  table.shop_table tbody tr.order-total td span.woocommerce-Price-amount.amount bdi',
				'.wfacp_mini_cart_start_h  table.shop_table tbody tr.order-total td p',
				'.wfacp_mini_cart_start_h  table.shop_table tbody tr.order-total td span',
				'.wfacp_mini_cart_start_h  table.shop_table tbody tr.order-total td span',
				'.wfacp_mini_cart_start_h  table.shop_table tbody tr.order-total td small',
				'.wfacp_mini_cart_start_h  table.shop_table tbody tr.order-total td a',
				'.wfacp_mini_cart_start_h  table.shop_table tbody tr.order-total td p',
			);

			$css = array();
			foreach ( $cart_total_label_typo_option as $selector ) {
				$css[] = array(
					'property' => 'typography',
					'selector' => $selector,
				);
			}

			$this->controls['mini_cart_total_label_typo'] = array(
				'group'   => 'styleCartTotal',
				'label'   => esc_html__( 'Label Typography' ),
				'type'    => 'typography',
				'css'     => $css,
				'exclude' => array( 'text-align','color' ), //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
			);

			$css = array();
			foreach ( $cart_total_value_typo_option as $selector ) {
				$css[] = array(
					'property' => 'typography',
					'selector' => $selector,
				);
			}

			$this->controls['mini_cart_total_typo'] = array(
				'group'   => 'styleCartTotal',
				'label'   => esc_html__( 'Price Typography' ),
				'type'    => 'typography',
				'css'     =>$css,
				'exclude' => array( 'text-align' ,'color'), //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
			);

			$this->set_current_group( 'styleCartTotal' );
			$this->add_color( 'mini_cart_total_color', $cart_total_color_option, '', __( 'Color' ) );

			/* ------------------------------------ End ------------------------------------ */

			/* ------------------------------------ Mini Cart Global Settings  ------------------------------------ */

			$this->controls['separatorStyleDefaultFont'] = array(
				'group' => 'styleSettings',
				'label' => esc_html__( 'Default Font' ),
				'type'  => 'separator',
			);

			$wfacp_mini_cart_font_family = [
				'.wfacp_mini_cart_start_h *',
				'.wfacp_mini_cart_start_h tr.order-total td span.woocommerce-Price-amount.amount',
				'.wfacp_mini_cart_start_h tr.order-total td span.woocommerce-Price-amount.amount bdi',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .wfacp_mini_cart_items',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .wfacp_mini_cart_items .product-total',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .wfacp_mini_cart_items .product-total span',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .wfacp_mini_cart_items .product-total small',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .wfacp_mini_cart_items dl',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .wfacp_mini_cart_items dt',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .wfacp_mini_cart_items dd',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .wfacp_mini_cart_items dd p',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .wfacp_mini_cart_reviews',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .wfacp_mini_cart_reviews tr:not(.order-total)',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .wfacp_mini_cart_reviews tr:not(.order-total) td',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .wfacp_mini_cart_reviews tr:not(.order-total) th',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .wfacp_mini_cart_reviews tr:not(.order-total) td span',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .wfacp_mini_cart_reviews tr:not(.order-total) td small',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .wfacp_mini_cart_reviews tr:not(.order-total) td a',
				'.wfacp_mini_cart_start_h span.wfacp_coupon_code',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .wfacp_mini_cart_reviews tr.order-total td span.woocommerce-Price-amount.amount',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .wfacp_mini_cart_reviews tr.order-total td span.woocommerce-Price-amount.amount bdi',
				'.wfacp_mini_cart_start_h table.shop_table .order-total td',
				'.wfacp_mini_cart_start_h table.shop_table .order-total th',
				'.wfacp_mini_cart_start_h table.shop_table .order-total td span',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container tr.cart_item .product-name',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .cart_item td',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .cart_item td small',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .cart_item td p',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .cart_item td .product-name span',
				'.wfacp_mini_cart_start_h .wfacp_order_summary_container .cart_item td .product-name',
				'.wfacp_mini_cart_start_h .wfacp-coupon-section .wfacp_main_showcoupon',
				'.wfacp_mini_cart_start_h .shop_table tr.order-total td',
				'.wfacp_mini_cart_start_h .shop_table tr.order-total th',
				'.wfacp_mini_cart_start_h .shop_table tr.order-total td span',
				'.wfacp_mini_cart_start_h .shop_table tr.order-total td small',
				'.wfacp_mini_cart_start_h .checkout_coupon.woocommerce-form-coupon .wfacp-form-control-label',
				'.wfacp_mini_cart_start_h .checkout_coupon.woocommerce-form-coupon .wfacp-form-control',
				'.wfacp_mini_cart_start_h .wfacp-coupon-btn',
			];

			$this->set_current_group( 'styleSettings' );
			$this->add_font_family( 'wfacp_mini_cart_font_family', $wfacp_mini_cart_font_family );

			$this->controls['separatorStyleDivider'] = array(
				'group' => 'styleSettings',
				'label' => esc_html__( 'Divider' ),
				'type'  => 'separator',
			);

			$this->controls['mini_cart_divider_color'] = array(
				'group' => 'styleSettings',
				'label' => esc_html__( 'Color' ),
				'type'  => 'color',
				'css'   => array(
					array(
						'property' => 'border-color',
						'selector' => implode( ',', array(
							'.wfacp_mini_cart_start_h .wfacp_mini_cart_elementor .cart_item',
							'.wfacp_mini_cart_start_h table.shop_table tr.cart-subtotal',
							'.wfacp_mini_cart_start_h table.shop_table tr.order-total',
							'.wfacp_mini_cart_start_h table.shop_table tr.wfacp_ps_error_state td',
							'.wfacp_mini_cart_start_h .wfacp-coupon-section .wfacp-coupon-page',
							'.wfacp_mini_cart_start_h .wfob_bump_wrapper.wfacp_below_mini_cart_items:empty',
						) ),
					),
				),
			);
		}

		/**
		 * Renders the order summary element.
		 *
		 * This method sets the session with the element's ID and settings, and then renders the order summary element.
		 * It also adds the element's ID to the session of mini cart widgets.
		 *
		 * @return void
		 */
		public function render() {
			WFACP_Common::set_session( $this->id, $this->settings );
			\FunnelKit\Bricks_Integration::set_locals( 'wfacp_form_summary', $this->id );

			$template = wfacp_template();

			if ( null === $template ) {
				return;
			}

			if ( WFACP_Common::is_theme_builder() ) {
				do_action( 'wfacp_mini_cart_widgets_elementor_editor', $this );
			}

			$key       = 'wfacp_mini_cart_widgets_' . $template->get_template_type();
			$widgets   = WFACP_Common::get_session( $key );
			$widgets[] = $this->id;

			WFACP_Common::set_session( $key, $widgets );

			?>
            <div <?php echo $this->render_attributes( '_root' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                <div style="height: 1px"></div>
				<?php $template->get_mini_cart_widget( $this->id ); ?>
            </div>
			<?php
		}
	}
}