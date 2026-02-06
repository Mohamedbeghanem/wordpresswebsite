<?php
if ( ! class_exists( 'WFACP_Compatibility_With_Yith_Gift' ) ) {
	/**
	 * YITH WooCommerce GIft Certificates Premium
	 *  https://yithemes.com/themes/plugins/yith-woocommerce-gift-cards
	 * #[AllowDynamicProperties]
	 *
	 * class WFACP_Compatibility_With_Yith_Gift
	 */
	class  WFACP_Compatibility_With_Yith_Gift {

		public function __construct() {
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'action' ] );
			add_filter( 'wfacp_product_image_thumbnail_html', [ $this, 'call_thumbnail' ], 10, 2 );

		}

		public function action() {
			add_filter( 'woocommerce_checkout_coupon_message', array( $this, 'yith_ywgc_rename_coupon_label' ), 15, 1 );
		}


		public function yith_ywgc_rename_coupon_label( $text ) {
			if ( get_option( 'ywgc_apply_gift_card_on_coupon_form', 'no' ) == 'yes' ) {
				$text_option = get_option( 'ywgc_apply_coupon_label_text', esc_html__( 'Have a coupon?', 'yith-woocommerce-gift-cards' ) );
				$text        = $text_option . ' <a href="#" class="showcoupon wfacp_showcoupon">' . esc_html__( 'Click here to enter your code', 'woocommerce' ) . '</a>';
			}

			return $text;
		}

		public function call_thumbnail( $image, $cart_item, $cart_item_key = '' ) {

			if ( isset( $cart_item['ywgc_product_id'] ) ) {
				return apply_filters( 'woocommerce_cart_item_thumbnail', $cart_item['data']->get_image(), $cart_item, $cart_item_key );
			}


			return $image;
		}
	}


	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_Yith_Gift(), 'yith-gift' );

}
