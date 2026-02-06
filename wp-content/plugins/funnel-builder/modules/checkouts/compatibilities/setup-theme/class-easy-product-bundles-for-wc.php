<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/*
 * Easy Product Bundles for WooCommerce
 * Author Name: Product Bundles Team
 * https://www.asanaplugins.com/
 */
if ( ! class_exists( 'WFACP_Easy_Product_Bundles_for_WooCommerce' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Easy_Product_Bundles_for_WooCommerce {
		public function __construct() {
			add_filter( 'wfacp_show_item_quantity', [ $this, 'do_not_display' ], 10, 2 );
			add_filter( 'wfacp_show_you_save_text', [ $this, 'do_not_display' ], 10, 2 );
			add_filter( 'wfacp_enable_delete_item', [ $this, 'do_not_display' ], 10, 2 );
			add_filter( 'wfacp_mini_cart_enable_delete_item', [ $this, 'do_not_display' ], 10, 2 );
			add_filter( 'wfacp_display_quantity_increment', [ $this, 'do_not_display' ], 10, 2 );
			add_filter( 'wfacp_show_undo_message_for_item', [ $this, 'do_not_undo' ], 10, 2 );
			add_filter( 'wfacp_exclude_product_cart_count', [ $this, 'do_not_undo' ], 10, 2 );
		}

		public function do_not_display( $status, $cart_item ) {
			if ( isset( $cart_item['asnp_wepb_parent_id'] ) ) {
				$status = false;
			}
			return $status;
		}

		public function do_not_undo( $status, $cart_item ) {
			if ( isset( $cart_item['asnp_wepb_parent_id'] ) ) {
				$status = true;
			}
			return $status;
		}
	}

	new WFACP_Easy_Product_Bundles_for_WooCommerce();
}