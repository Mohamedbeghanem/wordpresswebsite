<?php
/**
 * https://docs.woocommerce.com/document/chained-products/
 * #[AllowDynamicProperties]
 * class WFACP_WooCommerce_Chained_Products
 */
if ( ! class_exists( 'WFACP_WooCommerce_Chained_Products' ) ) {
	#[AllowDynamicProperties]
	class WFACP_WooCommerce_Chained_Products {

		public function __construct() {
			add_filter( 'wfacp_show_item_quantity', [ $this, 'do_not_display' ], 10, 2 );
			add_filter( 'wfacp_show_you_save_text', [ $this, 'do_not_display' ], 10, 2 );
			add_filter( 'wfacp_mini_cart_enable_delete_item', [ $this, 'do_not_display' ], 10, 2 );
			add_filter( 'wfacp_display_quantity_increment', [ $this, 'do_not_display' ], 10, 2 );

			add_filter( 'wfacp_show_undo_message_for_item', [ $this, 'do_not_undo' ], 10, 2 );
			add_filter( 'wfacp_exclude_product_cart_count', [ $this, 'do_not_undo' ], 10, 2 );
			add_filter( 'wfacp_show_item_quantity_placeholder', [ $this, 'display_item_quantity' ], 10, 3 );


			add_action( 'wfacp_after_checkout_page_found', [ $this, 'action' ], 1 );
			add_action( 'wfacp_before_process_checkout_template_loader', [ $this, 'action' ], 1 );
			add_filter( 'wfacp_product_raw_data', [ $this, 'change_raw_data' ], 999, 2 );
			add_filter( 'wfacp_force_calculate_discount', [ $this, 'force_discount' ], 999, 2 );

		}


		public function do_not_display( $status, $cart_item ) {

			if ( isset( $cart_item['chained_item_of'] ) ) {
				$status = false;
			}

			return $status;
		}

		public function do_not_undo( $status, $cart_item ) {
			if ( isset( $cart_item['chained_item_of'] ) ) {
				$status = true;
			}

			return $status;
		}


		public function display_item_quantity( $cart_item ) {
			if ( isset( $cart_item['chained_item_of'] ) ) {
				?>
                <span><?php echo $cart_item['quantity']; ?></span>
				<?php
			}
		}

		public function action() {

			$instance = WFACP_Public::get_instance();


			remove_action( 'woocommerce_before_calculate_totals', array( $instance, 'calculate_totals' ), 1 );;

			add_action( 'woocommerce_before_calculate_totals', array( $instance, 'calculate_totals' ), 9999 );;
		}

		public function change_raw_data( $raw_data, $product ) {

			if ( ! class_exists( 'WC_Chained_Products' ) || ! $product instanceof WC_Product ) {
				return $raw_data;
			}


			$raw_data['regular_price'] = $product->get_regular_price();
			$raw_data['price']         = $product->get_price();

			return $raw_data;
		}

		public function force_discount( $status, $cart_item ) {


			return true;
		}


	}

	new WFACP_WooCommerce_Chained_Products();
}