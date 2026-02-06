<?php
if ( ! defined( 'WFACP_TEMPLATE_DIR' ) ) {
	return '';
}
try {

	$initial_packages        = WC()->shipping->get_packages();
	$show_package_details    = count( WC()->cart->recurring_carts ) > 1;
	$show_package_name       = true;
	$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods', array() );
// Create new subscriptions for each subscription product in the cart (that is not a renewal)
	foreach ( WC()->cart->recurring_carts as $recurring_cart_key => $recurring_cart ) {
		// This ensures we get the correct package IDs (these are filtered by WC_Subscriptions_Cart).
		WC_Subscriptions_Cart::set_calculation_type( 'recurring_total' );
		WC_Subscriptions_Cart::set_recurring_cart_key( $recurring_cart_key );
		WC_Subscriptions_Cart::set_cached_recurring_cart( $recurring_cart );
		// Allow third parties to filter whether the recurring cart has a shipment.
		$cart_has_next_shipment = apply_filters( 'woocommerce_subscriptions_cart_has_next_shipment', 0 !== $recurring_cart->next_payment_date, $recurring_cart );
		// Create shipping packages for each subscription item
		if ( $cart_has_next_shipment && WC_Subscriptions_Cart::cart_contains_subscriptions_needing_shipping( $recurring_cart ) ) {

			// This will get a package with the 'recurring_cart_key' set to 'none' (because WC_Subscriptions_Cart::display_recurring_totals() set WC_Subscriptions_Cart::$calculation_type to 'recurring_total', but WC_Subscriptions_Cart::$recurring_cart_key has not been set), which ensures that it's a unique package, which we need in order to get all the available packages, not just the package for the recurring cart calculation we completed previously where WC_Subscriptions_Cart::filter_package_rates() removed all unchosen rates and which WC then cached
			foreach ( $recurring_cart->get_shipping_packages() as $recurring_cart_package_key => $base_package ) {
				$product_names                      = array();
				$package_index                      = isset( $recurring_cart_package['package_index'] ) ? $recurring_cart_package['package_index'] : 0;
				$base_package['recurring_cart_key'] = $recurring_cart_key;

				$package = WC()->shipping->calculate_shipping_for_package( $base_package );
				if ( $show_package_details ) {
					foreach ( $package['contents'] as $item_id => $values ) {
						$product_names[] = $values['data']->get_title() . ' &times;' . $values['quantity'];
					}
					$package_details = implode( ', ', $product_names );
				} else {
					$package_details = '';
				}

				$chosen_initial_method = isset( $chosen_shipping_methods[ $package_index ] ) ? $chosen_shipping_methods[ $package_index ] : '';
				if ( count( $package['rates'] ) > 1 ) {
					$package['rates'] = WFACP_Common::sort_shipping( $package['rates'] );
				}
				if ( isset( $chosen_shipping_methods[ $recurring_cart_package_key ] ) ) {
					$chosen_recurring_method = $chosen_shipping_methods[ $recurring_cart_package_key ];
				} elseif ( in_array( $chosen_initial_method, $package['rates'], true ) ) {
					$chosen_recurring_method = $chosen_initial_method;
				} else {
					$chosen_recurring_method = empty( $package['rates'] ) ? '' : current( $package['rates'] )->id;
				}


				$shipping_selection_displayed = false;
				$only_one_shipping_option     = count( $package['rates'] ) === 1;
				if ( $only_one_shipping_option || ( isset( $package['rates'][ $chosen_initial_method ] ) && isset( $initial_packages[ $package_index ] ) && $package['rates'] == $initial_packages[ $package_index ]['rates'] && apply_filters( 'wcs_cart_totals_shipping_html_price_only', true, $package, $recurring_cart ) ) ) {
					$shipping_method = $only_one_shipping_option ? current( $package['rates'] ) : $package['rates'][ $chosen_initial_method ];
					// packages match, display shipping amounts only
					?>
                    <tr class="shipping recurring-total <?php echo esc_attr( $recurring_cart_key ); ?>">
                        <th><span><?php echo( sprintf( __( 'Shipping via %s', 'woocommerce-subscriptions' ), WFACP_Common::shipping_method_label( $shipping_method ) ) ); ?></span></th>
                        <td>
							<?php echo wp_kses_post( wcs_cart_totals_shipping_method_price_label( $shipping_method, $recurring_cart ) ); ?>
							<?php if ( 1 === count( $package['rates'] ) ) : ?>
								<?php wcs_cart_print_shipping_input( $recurring_cart_package_key, $shipping_method ); ?>
								<?php do_action( 'woocommerce_after_shipping_rate', $shipping_method, $recurring_cart_package_key ); ?>
							<?php
							endif;
							if ( ! empty( $show_package_details ) ) :
								echo '<p class="woocommerce-shipping-contents"><small>' . esc_html( $package_details ) . '</small></p>';
							endif;
							?>
                        </td>
                    </tr>
					<?php
				} else {
					// Display the options
					$product_names = array();

					$shipping_selection_displayed = true;

					if ( $show_package_name ) {
						$package_name = apply_filters( 'woocommerce_shipping_package_name', sprintf( _n( 'Shipping', 'Shipping %d', ( $package_index + 1 ), 'woocommerce-subscriptions' ), ( $package_index + 1 ) ), $package_index, $package );
					} else {
						$package_name = '';
					}

					wc_get_template( 'wfacp/checkout/cart-recurring-shipping.php', array(
						'package'              => $package,
						'available_methods'    => $package['rates'],
						'show_package_details' => $show_package_details,
						'package_details'      => $package_details,
						'package_name'         => $package_name,
						'index'                => $recurring_cart_package_key,
						'chosen_method'        => $chosen_recurring_method,
						'recurring_cart_key'   => $recurring_cart_key,
						'recurring_cart'       => $recurring_cart,
					) );
					$show_package_name = false;
				}
				do_action( 'woocommerce_subscriptions_after_recurring_shipping_rates', $recurring_cart_package_key, $base_package, $recurring_cart, $chosen_recurring_method, $shipping_selection_displayed );
			}
		}
		WC_Subscriptions_Cart::set_calculation_type( 'none' );
		WC_Subscriptions_Cart::set_recurring_cart_key( 'none' );
	}
} catch ( Exception|Error $e ) {
	if ( function_exists( 'wcs_cart_totals_shipping_html' ) ) {
		wcs_cart_totals_shipping_html();
	}
}