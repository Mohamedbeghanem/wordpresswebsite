<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * plugin Name: Ecomus Addons by Drfuri (1.7.3)
 * Plugin URI: http://drfuri.com/plugins/ecomus-addons.zip
 */

if ( ! class_exists( 'WFACP_Compatibility_With_Ecomus_Addons' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Compatibility_With_Ecomus_Addons {
		public function __construct() {
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'remove_action' ] );
			add_action( 'wfacp_before_process_checkout_template_loader', [ $this, 'remove_action' ] );
			add_action( 'wfacp_internal_css', [ $this, 'add_css' ] );

		}
		public function remove_action() {

			if(class_exists('Ecomus\Addons\Elementor\Builder\Checkout_Page')){
				WFACP_Common::remove_actions( 'template_include', 'Ecomus\Addons\Elementor\Builder\Checkout_Page', 'redirect_template' );
			}

			if(class_exists('Ecomus\WooCommerce\Checkout')){
				WFACP_Common::remove_actions( 'woocommerce_before_checkout_form', 'Ecomus\WooCommerce\Checkout', 'before_login_form' );
				WFACP_Common::remove_actions( 'woocommerce_before_checkout_form', 'Ecomus\WooCommerce\Checkout', 'login_form' );
				WFACP_Common::remove_actions( 'woocommerce_before_checkout_form', 'Ecomus\WooCommerce\Checkout', 'coupon_form' );
				WFACP_Common::remove_actions( 'woocommerce_before_checkout_form', 'Ecomus\WooCommerce\Checkout', 'after_login_form' );
				WFACP_Common::remove_actions( 'woocommerce_checkout_coupon_message', 'Ecomus\WooCommerce\Checkout', 'coupon_form_name' );

			}
			if(class_exists('Ecomus\WooCommerce\General')){
				WFACP_Common::remove_actions( 'woocommerce_cart_item_name', 'Ecomus\WooCommerce\General', 'review_product_name_html' );

			}
			add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_style' ], 9999 );

		}

		public function dequeue_style() {
			wp_dequeue_style( 'ecomus-woocommerce-style' );

		}
		public function add_css() {
			?>

			<style>
                .clearfix {
                    content: "";
                    display: block;
                    table-layout: inherit;
                }
			</style>
			<?php

		}
	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_Ecomus_Addons(), 'ecomus' );
}
