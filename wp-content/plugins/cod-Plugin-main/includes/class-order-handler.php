<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class COD_DZ_Order_Handler {

    public static function ajax_create_order() {
        // Check nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'cod-dz-nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
        }

        // Basic spam & abuse checks
        if ( get_option( 'cod_dz_security_honeypot_enable' ) ) {
            $honeypot_field = isset( $_POST['honeypot_field'] ) ? sanitize_text_field( wp_unslash( $_POST['honeypot_field'] ) ) : '';
            if ( ! empty( $honeypot_field ) ) {
                wp_send_json_error( array( 'message' => 'Spam detected.' ) );
            }
        }

        if ( get_option( 'cod_dz_security_rate_limiter_enable' ) ) {
            if ( ! COD_DZ_Rate_Limiter::check() ) {
                wp_send_json_error( array( 'message' => 'Too many requests. Please wait.' ) );
            }
        }

        if ( get_option( 'cod_dz_security_honeypot_enable' ) && ! COD_DZ_Behavior_Guard::verify() ) {
            wp_send_json_error( array( 'message' => 'Suspicious behavior detected.' ) );
        }

        // Collect data from POST
        $variation_id     = isset( $_POST['variation_id'] ) ? intval( $_POST['variation_id'] ) : 0;
        $quantity         = isset( $_POST['quantity'] ) ? intval( $_POST['quantity'] ) : 1;
        $customer_name    = isset( $_POST['customer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_name'] ) ) : '';
        $customer_phone   = isset( $_POST['customer_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_phone'] ) ) : '';
        $customer_state   = isset( $_POST['customer_state'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_state'] ) ) : '';
        $customer_city    = isset( $_POST['customer_city'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_city'] ) ) : '';
        $customer_address = isset( $_POST['customer_address'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_address'] ) ) : '';
        $delivery_type    = isset( $_POST['delivery_type'] ) ? sanitize_text_field( wp_unslash( $_POST['delivery_type'] ) ) : 'home';

        // Validate required fields
        if ( empty( $variation_id ) ) {
            wp_send_json_error( array( 'message' => 'Veuillez sélectionner une variation du produit.' ) );
        }
        foreach ( array( $customer_name, $customer_phone, $customer_state, $customer_city, $customer_address ) as $f ) {
            if ( empty( $f ) ) {
                wp_send_json_error( array( 'message' => 'Veuillez remplir tous les champs obligatoires.' ) );
            }
        }

        try {
            $order_id = self::create_order( array(
                'variation_id'     => $variation_id,
                'quantity'         => $quantity,
                'customer_name'    => $customer_name,
                'customer_phone'   => $customer_phone,
                'customer_state'   => $customer_state,
                'customer_city'    => $customer_city,
                'customer_address' => $customer_address,
                'delivery_type'    => $delivery_type,
            ) );

            $redirect_url = self::get_thank_you_url( $order_id );

            wp_send_json_success( array(
                'message'  => 'Commande créée avec succès!',
                'order_id' => $order_id,
                'redirect' => $redirect_url,
            ) );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    private static function create_order( $data ) {
        // load variation product
        $variation = wc_get_product( $data['variation_id'] );
        if ( ! $variation ) {
            throw new Exception( 'Produit introuvable.' );
        }

        // Build address
        $address = array(
            'first_name' => $data['customer_name'],
            'last_name'  => '',
            'company'    => '',
            'email'      => '',
            'phone'      => $data['customer_phone'],
            'address_1'  => $data['customer_address'],
            'address_2'  => '',
            'city'       => $data['customer_city'],
            'state'      => $data['customer_state'],
            'country'    => 'DZ',
            'postcode'   => '',
        );

        $order = wc_create_order();

        // add variation product (WC accepts a product object)
        $order->add_product( $variation, $data['quantity'] );

        // Add delivery fee
        $delivery_fee = ( $data['delivery_type'] === 'home' ) ? floatval( get_option( 'cod_dz_home_delivery_cost', 700 ) ) : floatval( get_option( 'cod_dz_office_delivery_cost', 400 ) );
        if ( $delivery_fee > 0 ) {
            $order->add_fee( 'Frais de livraison', $delivery_fee );
        }

        // set addresses
        $order->set_address( $address, 'billing' );
        $order->set_address( $address, 'shipping' );

        // payment method COD
        $order->set_payment_method( 'cod' );
        $order->set_payment_method_title( 'Paiement à la livraison' );

        $order->calculate_totals();
        $order->update_status( 'completed', 'Commande créée via le formulaire COD DZ.', true );
        $order->save();

        return $order->get_id();
    }

    private static function get_thank_you_url( $order_id ) {
        $thank_you_page_id = get_option( 'cod_dz_thank_you_page' );
        $order = wc_get_order( $order_id );
        if ( $thank_you_page_id && get_permalink( $thank_you_page_id ) ) {
            return add_query_arg( array( 'order_id' => $order_id, 'key' => $order->get_order_key() ), get_permalink( $thank_you_page_id ) );
        }
        return $order->get_checkout_order_received_url();
    }
}
