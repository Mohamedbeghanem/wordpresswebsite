<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WFACP_Compatibility_With_Lumise_Fancy' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Compatibility_With_Lumise_Fancy {
		public function __construct() {
			add_filter( 'wfacp_product_image_thumbnail_html', [ $this, 'add_customized_image' ], 10, 2 );
		}

		public function add_customized_image( $img_src, $cart_item ) {

			if ( empty( $img_src ) && is_null( $cart_item ) ) {
				return $img_src;
			}
			$flag_img    = false;
			$product_obj = $cart_item['data'];

			/** Compatibility with Fancy Product Designer plugin*/
			if ( class_exists( 'Fancy_Product_Designer' ) && isset( $cart_item['fpd_data'] ) ) {
				$fpd_data = $cart_item['fpd_data'];
				$flag_img = $fpd_data['fpd_product_thumbnail'];

			}

			// Compatibility with Lumise Product Designer Tool plugin
			global $lumise;
			if ( ! empty( $lumise ) && isset( $cart_item['lumise_data'] ) ) {
				$cart_item_data = $lumise->lib->get_cart_data( $cart_item['lumise_data'] );
				if ( isset( $cart_item_data['screenshots'] ) && is_array( $cart_item_data['screenshots'] ) ) {
					$flag_img = isset( $cart_item_data['screenshots'][0] ) ? $lumise->cfg->upload_url . $cart_item_data['screenshots'][0] : '';
				}
			}
			if ( false !== $flag_img ) {
				return '<img src="' . esc_attr( $flag_img ) . '" alt="' . esc_html( $product_obj->get_name() ) . '" />';
			}

			return $img_src;
		}


	}


	WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_Lumise_Fancy(), 'lumise_fancy_customized' );

}
