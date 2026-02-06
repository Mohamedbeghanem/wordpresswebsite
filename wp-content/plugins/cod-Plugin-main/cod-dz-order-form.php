<?php
/**
 * Plugin Name:       COD DZ Order Form
 * Plugin URI:        https://example.com/
 * Description:       Custom order form for Algerian e-commerce with COD support
 * Version:           1.0.2
 * Author:            Mohamed Beghanem
 * License:           GPL v2 or later
 * Text Domain:       cod-dz
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'COD_DZ_VERSION', '1.0.1' );
define( 'COD_DZ_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'COD_DZ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Check if WooCommerce is active
 */
function cod_dz_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p><strong>COD DZ Order Form</strong> requires WooCommerce to be installed and active.</p></div>';
        } );
        return false;
    }
    return true;
}

/**
 * Init plugin after WP is ready (WooCommerce functions available)
 */
function cod_dz_init() {
    if ( ! cod_dz_check_woocommerce() ) {
        return;
    }

    // Includes
    require_once COD_DZ_PLUGIN_DIR . 'includes/class-order-handler.php';
    require_once COD_DZ_PLUGIN_DIR . 'includes/class-admin.php';

    // simplified security helpers
    require_once COD_DZ_PLUGIN_DIR . 'security/class-honeypot.php';
    require_once COD_DZ_PLUGIN_DIR . 'security/class-rate-limiter.php';
    require_once COD_DZ_PLUGIN_DIR . 'security/class-behavior-guard.php';

    // Initialize admin
    if ( class_exists( 'COD_DZ_Admin' ) ) {
        new COD_DZ_Admin();
    }

    // Register shortcode at init (ensures WC functions available)
    add_action( 'init', function() {
        add_shortcode( 'cod_dz_form', 'cod_dz_render_form' );
    } );

    // Enqueue scripts
    add_action( 'wp_enqueue_scripts', 'cod_dz_enqueue_assets' );

    // AJAX handlers
    add_action( 'wp_ajax_cod_dz_create_order', array( 'COD_DZ_Order_Handler', 'ajax_create_order' ) );
    add_action( 'wp_ajax_nopriv_cod_dz_create_order', array( 'COD_DZ_Order_Handler', 'ajax_create_order' ) );
}
add_action( 'plugins_loaded', 'cod_dz_init' );

/**
 * Enqueue CSS and JavaScript
 */
function cod_dz_enqueue_assets() {
    wp_enqueue_style(
        'cod-dz-style',
        COD_DZ_PLUGIN_URL . 'assets/css/style.css',
        array(),
        COD_DZ_VERSION
    );

    wp_enqueue_script(
        'cod-dz-script',
        COD_DZ_PLUGIN_URL . 'assets/js/script.js',
        array( 'jquery' ),
        COD_DZ_VERSION,
        true
    );

    wp_localize_script(
        'cod-dz-script',
        'codDzAjax',
        array(
            'ajaxUrl'             => admin_url( 'admin-ajax.php' ),
            'nonce'               => wp_create_nonce( 'cod-dz-nonce' ),
            'home_delivery_cost'  => floatval( get_option( 'cod_dz_home_delivery_cost', 700 ) ),
            'office_delivery_cost'=> floatval( get_option( 'cod_dz_office_delivery_cost', 400 ) ),
        )
    );
}

/**
 * Render the order form shortcode
 */
function cod_dz_render_form( $atts ) {
    $atts = shortcode_atts( array(
        'product_id' => null,
    ), $atts, 'cod_dz_form' );

    $product_id = null;

    // Priority: shortcode attr > product page > default option
    if ( ! empty( $atts['product_id'] ) ) {
        $product_id = intval( $atts['product_id'] );
    } elseif ( is_singular( 'product' ) ) {
        $product_id = get_the_ID();
    } else {
        $default = get_option( 'cod_dz_default_product' );
        if ( $default ) {
            $product_id = intval( $default );
        }
    }

    if ( ! $product_id ) {
        return '<p>Product not found.</p>';
    }

    $product = wc_get_product( $product_id );
    if ( ! $product || ! $product->is_purchasable() ) {
        return '<p>Product not found.</p>';
    }

    // Prepare data for template
    $product_data = array(
        'product_id'   => $product->get_id(),
        'product_name' => $product->get_name(),
        'product_type' => $product->get_type(),
        'price'        => $product->get_price(),
        'variations'   => $product->is_type( 'variable' ) ? $product->get_available_variations() : array(),
        'attributes'   => $product->is_type( 'variable' ) ? $product->get_variation_attributes() : array(),
    );

    // Make data available to the template
    set_query_var( 'product_data', $product_data );

    ob_start();

    // Use the same path you currently have; change if your template path differs.
    $template_file = COD_DZ_PLUGIN_DIR . 'templates/orderform.php';
    if ( file_exists( $template_file ) ) {
        load_template( $template_file, false );
    } else {
        echo '<p>Template not found.</p>';
    }

    return ob_get_clean();
}

/**
 * Activation hook
 */
function cod_dz_activate() {
    // Create necessary options if not exist
    if ( false === get_option( 'cod_dz_default_product' ) ) {
        add_option( 'cod_dz_default_product', '' );
    }
    if ( false === get_option( 'cod_dz_thank_you_page' ) ) {
        add_option( 'cod_dz_thank_you_page', '' );
    }
    if ( false === get_option( 'cod_dz_home_delivery_cost' ) ) {
        add_option( 'cod_dz_home_delivery_cost', '700' );
    }
    if ( false === get_option( 'cod_dz_office_delivery_cost' ) ) {
        add_option( 'cod_dz_office_delivery_cost', '400' );
    }
}
register_activation_hook( __FILE__, 'cod_dz_activate' );

/**
 * Add settings link on plugin page
 */
function cod_dz_add_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page=cod_dz_settings">' . __( 'Settings' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'cod_dz_add_settings_link' );
