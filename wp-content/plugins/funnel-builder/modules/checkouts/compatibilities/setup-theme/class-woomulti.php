<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * CURCY - WooCommerce Multi Currency Premium by VillaTheme Version 2.3.7 and
 * CURCY - Multi Currency for WooCommerce by VillaTheme 2.2.6
 * https://villatheme.com/extensions/woo-multi-currency/
 */
if ( ! class_exists( 'WFACP_Compatibility_With_WooMulti_Curcy' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Compatibility_With_WooMulti_Curcy {
		public $instance = null;
		private $woo_multi_currency_data = null;

		/**
		 * @var WOOMULTI_CURRENCY_Frontend_Price
		 */
		public function __construct() {
			try {
				add_action( 'wfacp_after_checkout_page_found', [ $this, 'action' ] );
				add_filter( 'wfacp_product_raw_data', [ $this, 'wfacp_product_raw_data' ], 10, 2 );
				add_filter( 'wfacp_product_switcher_price_data', [ $this, 'change_price' ], 20, 3 );
			} catch ( Exception $e ) {
				error_log( 'WFACP_Compatibility_With_WooMulti_Curcy::__construct - ' . $e->getMessage() );
			}

		}

		public function change_price( $price_data, $pro, $cart_item_key = '' ) {
			if ( empty( $cart_item_key ) ) {
				$price_data['regular_org'] = $pro->get_regular_price();
				$price_data['price']       = $pro->get_price();
			}

			return $price_data;
		}

		public function action() {
			try {
				$this->instance = WFACP_Common::remove_actions( 'wp_footer', 'WOOMULTI_CURRENCY_Frontend_Design', 'show_action' );
				if ( ! is_null( $this->instance ) && is_object( $this->instance ) && $this->instance instanceof WOOMULTI_CURRENCY_Frontend_Design ) {
					add_action( 'wfacp_footer_before_print_scripts', array( $this->instance, 'show_action' ) );
				}
			} catch ( Exception $e ) {
				error_log( 'WFACP_Compatibility_With_WooMulti_Curcy::action - ' . $e->getMessage() );
			}
		}


		/**
		 * @param $raw_data
		 * @param $product WC_Product;
		 *
		 * @return mixed
		 */
		public function wfacp_product_raw_data( $raw_data, $product ) {
			try {

				$settings = $this->get_currency_instance();

				if ( is_null( $settings ) ) {
					return $raw_data;
				}

				$current_currency = $settings->get_current_currency();
				$fixed_price      = $settings->check_fixed_price();
				$default_currency = $settings->get_default_currency();
				if ( $current_currency == $default_currency ) {
					return $raw_data;
				}
				if ( ! $fixed_price ) {
					return $raw_data;
				}

				$regular_price_wmcp = json_decode( get_post_meta( $product->get_id(), '_regular_price_wmcp', true ), true );
				$sale_price_wmcp    = json_decode( get_post_meta( $product->get_id(), '_sale_price_wmcp', true ), true );
				if ( ! isset( $regular_price_wmcp[ $current_currency ] ) || $regular_price_wmcp[ $current_currency ] < 0 ) {
					return $raw_data;
				}
				$raw_data['regular_price'] = $regular_price_wmcp[ $current_currency ];
				if ( $raw_data['regular_price'] > 0 ) {


					$sale_price = ! is_null( $sale_price_wmcp ) && isset( $sale_price_wmcp[ $current_currency ] ) ? $sale_price_wmcp[ $current_currency ] : 0;

					if ( $sale_price > 0 ) {
						$raw_data['price']      = wmc_revert_price( $sale_price );
						$raw_data['sale_price'] = wmc_revert_price( $sale_price );
					} else {
						$raw_data['price'] = wmc_revert_price( $raw_data['regular_price'] );
					}
					$raw_data['regular_price'] = wmc_revert_price( $raw_data['regular_price'] );

				}

				return $raw_data;
			} catch ( Exception $e ) {
				error_log( 'WFACP_Compatibility_With_WooMulti_Curcy::wfacp_product_raw_data - ' . $e->getMessage() );

				return $raw_data;
			}
		}

		/**
		 * @return WOOMULTI_CURRENCY_Data
		 */
		private function get_currency_instance() {
			try {
				if ( is_null( $this->woo_multi_currency_data ) && class_exists( 'WOOMULTI_CURRENCY_Data' ) ) {
					$this->woo_multi_currency_data = WOOMULTI_CURRENCY_Data::get_ins();
				}
				if ( is_null( $this->woo_multi_currency_data ) && class_exists( 'WOOMULTI_CURRENCY_F_Data' ) ) {
					$this->woo_multi_currency_data = WOOMULTI_CURRENCY_F_Data::get_ins();
				}

				return $this->woo_multi_currency_data;
			} catch ( Exception $e ) {
				error_log( 'WFACP_Compatibility_With_WooMulti_Curcy::get_currency_instance - ' . $e->getMessage() );

				return null;
			}
		}


	}

	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_WooMulti_Curcy(), 'WooMulti' );
}