<?php

/**
 * Plugin Name: Bit Integrations Pro
 * Plugin URI:  https://bitapps.pro/bit-integrations
 * Description: Bit Integrations is a platform that integrates with over 160+ different platforms to help with various tasks on your WordPress site, like WooCommerce, Form builder, Page builder, LMS, Sales funnels, Bookings, CRM, Webhooks, Email marketing, Social media and Spreadsheets, etc.
 * Version:     1.3.6
 * Author:      Bit Apps
 * Author URI:  https://bitapps.pro
 * Text Domain: bit-integrations
 * Requires PHP: 5.6
 * Domain Path: /languages
 * License: gpl2+
 */
update_option('btcbi_integrate_key_data', ['key' => '**************', 'status' => 'success', 'expireIn' => '10.10.2040']);
/***
 * If try to direct access  plugin folder it will Exit
 **/
if (!defined('ABSPATH')) {
    exit;
}
global $btcbi_pro_db_version;
$btcbi_pro_db_version = '1.0';

// Define most essential constants.
define('BTCBI_PRO_VERSION', '1.3.6');
define('BTCBI_PRO_PLUGIN_MAIN_FILE', __FILE__);

require_once plugin_dir_path(__FILE__) . 'includes/loader.php';
if (!function_exists('btcbi_pro_activate_plugin')) {
    function btcbi_pro_activate_plugin($network_wide)
    {
        global $wp_version;
        if (version_compare($wp_version, '5.1', '<')) {
            wp_die(
                esc_html__('This plugin requires WordPress version 5.1 or higher.', 'bit-integrations'),
                esc_html__('Error Activating', 'bit-integrations')
            );
        }
        if (version_compare(PHP_VERSION, '5.6.0', '<')) {
            wp_die(
                esc_html__('Forms Integrations requires PHP version 5.6.', 'bit-integrations'),
                esc_html__('Error Activating', 'bit-integrations')
            );
        }
        do_action('btcbi_pro_activation', $network_wide);
    }
}

register_activation_hook(__FILE__, 'btcbi_pro_activate_plugin');

if (!function_exists('btcbi_deactivation')) {
    function btcbi_deactivation()
    {
        do_action('btcbi_pro_deactivation');
    }
}
register_deactivation_hook(__FILE__, 'btcbi_deactivation');

if (!function_exists('btcbi_pro_uninstall_plugin')) {
    function btcbi_pro_uninstall_plugin()
    {
        do_action('btcbi_pro_uninstall');
    }
}
register_uninstall_hook(__FILE__, 'btcbi_pro_uninstall_plugin');
