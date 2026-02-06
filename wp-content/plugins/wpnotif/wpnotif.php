<?php

/*
 * Plugin Name: WPNotif: WordPress SMS & WhatsApp Notifications
 * Description: Send SMS or WhatsApp message to your customers or inform them about their WooCommerce order updates.
 * Version: 3.1.3.5
 * Plugin URI: https://wpnotif.unitedover.com/
 * Author URI: https://www.unitedover.com/
 * Author: UnitedOver
 * Text Domain: wpnotif
 * Requires PHP: 5.5
 * Domain Path: /languages
 * Update URI: https://wpnotif.unitedover.com/
 */

defined('ABSPATH') || exit;

update_option('wpnotif_purchasecode','************************************');
update_option('wpnotif_license_type', 1);
delete_option('wpnotif_dsb');
delete_option('wpnotif_purchasefail');

require_once plugin_dir_path(__FILE__) . 'libphonenumber/vendor/autoload.php';

require dirname(__FILE__) . '/update/Puc/v4p6/Factory.php';
require dirname(__FILE__) . '/update/Puc/v4/Factory.php';
require dirname(__FILE__) . '/update/Puc/v4p6/Autoloader.php';
new Puc_v4p6_Autoloader();

foreach (array('Plugin_UpdateChecker' => 'Puc_v4p6_Plugin_UpdateChecker', 'Vcs_PluginUpdateChecker' => 'Puc_v4p6_Vcs_PluginUpdateChecker',) as $pucGeneralClass => $pucVersionedClass) {
    Puc_v4_Factory::addVersion($pucGeneralClass, $pucVersionedClass, '4.6');

    Puc_v4p6_Factory::addVersion($pucGeneralClass, $pucVersionedClass, '4.6');
}


/**
 * Returns the main instance of WPNotif
 *
 * @return WPNotif instance
 */
function wpnotif()
{
    return WPNotif::instance();
}


wpnotif();


final class WPNotif
{

    public static $version = '3.1.3.5';
    public static $loader_html = false;
    protected static $_instance = null;
    public $newsletter;

    /**
     *  Constructor.
     */
    public function __construct()
    {
        $this->init_hooks();

        require_once plugin_dir_path(__FILE__) . 'includes/functions.php';
        require_once plugin_dir_path(__FILE__) . 'includes/plugins/compatibility.php';
        require_once plugin_dir_path(__FILE__) . 'includes/edit_number.php';
        WPNotif_Handler::instance();

        require_once plugin_dir_path(__FILE__) . 'includes/newsletter/init.php';

        $this->newsletter = WPNotif_NewsLetter::instance();

        add_action('admin_notices', array($this, 'admin_notices'));
    }

    /**
     * Hook into actions and filters.
     */
    private function init_hooks()
    {
        add_action('wp_ajax_wpnotif_save_settings', array($this, 'save_settings'));


        add_action('admin_menu', array($this, 'plugin_menu'));
        add_action('add_meta_boxes', array($this, 'add_quick_sms_meta_box'), 10, 2);

        add_action('admin_bar_menu', array($this, 'add_admin_wpnotif'), 100);

        add_action('admin_footer', array($this, 'add_wp_footer'), 100);
        add_action('wp_footer', array($this, 'add_wp_footer'), 100);


        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wpnotif_enqueue_scripts', array($this, 'frontend_scripts'));

        add_action('wpnotif_load_frontend_scripts', array($this, 'frontend_scripts'));


        add_filter('wpnotif_notification_list_admin', array($this, 'get_status'));
        add_filter('wpnotif_notification_list_customer', array($this, 'get_status'));

        add_action('woocommerce_order_actions_start', array($this, 'admin_order_actions'));
    }

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public static function get_directory_path()
    {
        return plugin_dir_path(__FILE__);
    }

    public static function add_wp_footer()
    {
        if (!self::can_notify_users() || !WPNotif_Handler::isWhatsappWebEnabled()) {
            return;
        }

        global $wpdb;

        $tb = $wpdb->prefix . 'wpnotif_whatsapp_messages';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tb'") != $tb) {
            return;
        }
        $total_messages = $wpdb->get_var('SELECT COUNT(*) FROM ' . $tb);

        $messages = $wpdb->get_results(
                'SELECT * FROM ' . $tb . ' ORDER BY time ASC LIMIT 40'
        );

        $nonce = wp_create_nonce('wpnotif');
        ?>
        <div class="wpnotif-box" style="display: none;">
            <div class="wpnotif-list-head">
                <?php esc_attr_e('Pending WhatsApp Messages', 'wpnotif'); ?>
                <div class="wpnotif-list-head_buttons untdovr-list-head_buttons whatsapp_clear_all">
                    <?php esc_attr_e('Clear All', 'wpnotif'); ?>
                </div>
            </div>
            <input type="hidden" value="<?php echo esc_html($nonce); ?>" class="wpnotif_del_nonce"/>
            <?php
            $i = 0;
            foreach ($messages as $message) {
                $i++;
                $message_time = strtotime($message->time);

                ?>
                <div class="wpnotif-list wpnotif-pending-whatsapp-messages"
                     data-id="<?php echo esc_attr($message->id); ?>"
                     data-mobile="<?php echo esc_attr($message->countrycode . $message->mobileno); ?>">
                    <div class="wpnotif-inline wpnotif-float-left untdovr-float-left wpnotif-whatsapp-logo">
                        <div></div>
                    </div>
                    <div class="wpnotif-inline wpnoti-float-left wpnotif-whatsapp-message">

                        <div class="wpnotif-mobile">
                            +<?php echo esc_html($message->countrycode . ' ' . $message->mobileno); ?>
                            <span class="wpnotif-msg-time wpnotif-time"
                                  data-time="<?php echo esc_attr($message_time); ?>"></span>
                        </div>
                        <div class="wpnotif-message"><?php echo esc_html($message->message); ?></div>
                    </div>
                    <div class="wpnotif-inline wpnotif-float-right untdovr-float-right wpnotif-whatsapp-send-parent">
                        <div class="wpnotif-center-align">
                            <div class="wpnotif-whatsapp-send untdovr-whatsapp-send"><?php esc_attr_e('Send', 'wpnotif'); ?></div>
                            <div class="whatsapp-clear_message wpnotif-cross_icon clear_message"></div>
                        </div>
                    </div>

                </div>
                <?php
            }

            $remaining_messages = ($total_messages - 4);
            if ($remaining_messages > 0) {
                ?>
                <div class="wpnotif-pending-messages">
                    <div class="content"><span><?php echo esc_html($remaining_messages); ?></span> more
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
        <?php

    }

    public static function can_notify_users()
    {
        if (current_user_can('manage_options') || self::can_edit_orders()) {
            return true;
        } else {
            return false;
        }

    }

    public static function can_edit_orders()
    {
        return current_user_can('edit_shop_orders');
    }

    public static function get_status($status)
    {
        $add = array(
                'user_login' => array(
                        'label' => esc_attr__('User Login', 'wpnotif'),
                        'message' => '1',
                        'placeholder' => '1',
                        'unovr_err_desc' => esc_attr__('This works best if you have %s WordPress plugin, or else the message will be sent to WC Billing Phone Number and as per WooCommerce multiple accounts can have same billing number.', 'wpnotif'),
                ),
                'user_registration' => array(
                        'label' => esc_attr__('New User Registration', 'wpnotif'),
                        'message' => '1',
                        'placeholder' => '1',
                ),
                'user_pass_change' => array(
                        'label' => esc_attr__('User Password Change', 'wpnotif'),
                        'message' => '1',
                        'placeholder' => '1',
                ),
        );

        return array_merge($status, $add);
    }

    public static function enqueue_scripts()
    {

        if (!current_user_can('manage_options') && !self::can_edit_orders()) {
            return;
        }

        wp_enqueue_style('google-roboto-regular', self::wpn_admin_fonts());


        wp_register_script('uo-jquery-mask', plugins_url('/assets/js/jquery.mask.min.js', __FILE__), array('jquery'), null, false);
        wp_enqueue_script('uo-jquery-mask');

        wp_enqueue_style('untselect', plugins_url('/assets/css/untselect.min.css', __FILE__), array(), null, 'all');

        wp_enqueue_script('untselect-full', plugins_url('/assets/js/untselect.min.js', __FILE__), array('jquery'), null);


        wp_register_script('wpnotif-settings', plugins_url('/assets/js/settings.js', __FILE__), array(
                'jquery',
                'untselect-full',
                'uo-jquery-mask',
        ), self::get_version(), true);
        $settings_array = array(
                'usingWhatsAppDesktop' => WPNotif_Handler::usingWhatsAppDesktop(),
                "Invalidmsg91senderid" => esc_attr__("Invalid msg91 sender id!", 'wpnotif'),
                "invalidpurchasecode" => esc_attr__("Invalid License Key", 'wpnotif'),
                "Error" => esc_attr__("Error! Please try again later", "wpnotif"),
                "PleasecompleteyourSettings" => esc_attr__("Please complete your settings", 'wpnotif'),
                "PleasecompleteyourAPISettings" => esc_attr__("Please complete your API settings", 'wpnotif'),
                'ajax_url' => admin_url('admin-ajax.php'),
                "isWhatsappWebEnabled" => WPNotif_Handler::isWhatsappWebEnabled(),
                "direction" => is_rtl() ? 'rtl' : 'ltr',
                "Copiedtoclipboard" => esc_attr__('Copied to clipboard!', 'wpnotif'),
                "enterTestMessage" => esc_attr__('Please enter a test message to send!', 'wpnotif')
        );
        wp_localize_script('wpnotif-settings', 'wpnotifobj', $settings_array);

        wp_enqueue_script('wpnotif-settings');


        if (isset($_GET['page']) && $_GET['page'] === 'wpnotif-settings') {

            wp_enqueue_script('apexcharts', 'https://cdn.jsdelivr.net/npm/apexcharts');
            wp_register_script('wpnotif-admin-dashboard', plugins_url('/assets/js/dashboard.min.js', __FILE__), array(
                    'jquery',
                    'apexcharts',
            ), self::get_version(), true);

            $dashboard_array = array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wpnotif_admin_dashboard')
            );
            wp_localize_script('wpnotif-admin-dashboard', 'wpndashboard', $dashboard_array);
            wp_enqueue_script('wpnotif-admin-dashboard');


            wp_enqueue_style('wpnotif-settings', plugins_url('/assets/css/settings.min.css', __FILE__), array(), self::get_version(), 'all');

            if (is_rtl()) {
                wp_enqueue_style('wpnotif-settings-rtl', plugins_url('/assets/css/settings-rtl.min.css', __FILE__), array(), self::get_version(), 'all');
            }
        }
        wp_enqueue_style('wpnotif-admin-settings', plugins_url('/assets/css/admin.min.css', __FILE__), array(), self::get_version(), 'all');


        wp_enqueue_style('wpnotif-admin-message-logs', plugins_url('/assets/css/logs.min.css', __FILE__), array(), self::get_version(), 'all');

        do_action('wpnotif_enqueue_scripts');


    }

    public static function wpn_admin_fonts()
    {

        $fonts = array(
                "Roboto:900,700,500,500i,400,200,300"
        );

        $fonts_collection = add_query_arg(array(

                "family" => urlencode(implode("|", $fonts)),

        ), 'https://fonts.googleapis.com/css');

        return $fonts_collection;
    }


    public static function get_version()
    {
        return WPNotif::$version;
    }

    public static function notify($user_id, $key, $data, $sms = true, $whatsapp = true)
    {
        if ($sms) {
            WPNotif_Handler::instance()->notify_user($user_id, $data, $key, WPNotif_Handler::$sms);
        }

        if ($whatsapp && WPNotif_Handler::isWhatsappEnabled()) {
            if (!empty($data['data']['whatapp_message'])) {
                $data['data']['user_message'] = $data['data']['whatapp_message'];
            }
            WPNotif_Handler::instance()->notify_user($user_id, $data, $key, WPNotif_Handler::$whatsapp);
        }

    }

    public static function is_doing_ajax()
    {
        return defined('DOING_AJAX') && DOING_AJAX ? true : false;
    }

    public function admin_notices()
    {
        if (!empty($_GET['page']) && $_GET['page'] == 'wpnotif-usergroups') {
            if (get_option('wpnotif_create_usergroup_notice', -1) != 1) {
                $link = admin_url('admin.php?page=wpnotif-usergroups');
                ?>
                <div class="notice notice-error wpnotif-notice" style="display: block">
                    <p>
                        <b>WPNotif Notice</b><br/>
                        Please click <a href="<?php echo $link; ?>">here</a> to create predefined groups. This is a one
                        time process and new users will be added automatically.
                    </p>
                </div>
                <?php
            }
        }
    }

    public function admin_order_actions($post_id)
    {
        ?>
        <li class="wpnotif_order_trigger">
            <label>
                <input type="hidden" name="wpnotif_check_send_enabled" value="1"/>
                <input type="checkbox" name="wpnotif_send_order_status" value="1" checked/>
                <?php esc_attr_e('Send status update notification', 'wpnotif'); ?>
                <span title="WPNotif" class="wpnotif_small_logo"></span>
            </label>
        </li>
        <?php
    }

    public function add_admin_wpnotif($admin_bar)
    {

        if (!self::can_notify_users() || !WPNotif_Handler::isWhatsappWebEnabled()) {
            return;
        }
        global $wpdb;

        $tb = $wpdb->prefix . 'wpnotif_whatsapp_messages';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tb'") != $tb) {
            return;
        }

        $messages = $wpdb->get_var(
                'SELECT count(*) FROM ' . $tb . ' ORDER BY time DESC'
        );
        $total_messages = $messages;

        $admin_bar->add_menu(
                array(
                        'id' => 'wpnotif-pending-messages',
                        'parent' => 'top-secondary',
                        'title' => '<span class="wp-notif-total_messages">' . $total_messages . '</span> Pending Messages',
                        'href' => '#',
                        'meta' => array(
                                'title' => __('Pending Messages', 'wpnotif'),
                        ),
                )
        );
    }

    public function add_quick_sms_meta_box($wc_screen_id, $wc_order)
    {
        if (
                class_exists(Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class) && function_exists('wc_get_container')
                && wc_get_container()->get(Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
        ) {
            $screen_id = wc_get_page_screen_id('shop-order');
        } else {
            $screen_id = 'shop_order';
        }

        if ($wc_screen_id != $screen_id) {
            return;
        }
        add_meta_box('wpnotif_quick_sms_meta_box', 'WPNotif', array(
                $this,
                'quick_sms_meta_box'
        ), $screen_id, 'side', 'core');
    }

    public function quick_sms_meta_box($post_or_order_object)
    {
        $order_id = 0;

        if ($post_or_order_object instanceof \WP_Post) {
            $order_id = $post_or_order_object->ID;
        } else {
            $order_id = $post_or_order_object->get_id();
        }

        echo '<div class="wpnotif_post_meta_box untdovr_post_meta_box">';
        $this->send_quick_sms_ui(true, $order_id);
        echo '</div>';
    }

    public function send_quick_sms_ui($order, $order_id = 0, $data = null)
    {
        $nonce = wp_create_nonce('wpnotif');
        $user_consent = array();
        $show_trigger = true;
        if ($order) {
            if ($order_id != 0) {
                $order = new WC_Order($order_id);

                $user_consent = WPNotif_Handler::get_user_consent($order, -1);
                $check_consent = $user_consent['check_consent'];
                if ($check_consent == 1) {
                    ?>
                    <div style="padding-bottom: 8px;"><?php esc_attr_e('User Consent', 'wpnotif'); ?></div>
                    <?php
                    if ($user_consent['sms_consent'] == 1) {
                        ?>
                        <div><input type="checkbox"
                                    disabled="disabled" <?php if ($user_consent['sms_notifications'] == 1) {
                                echo 'checked';
                            } ?>/> <?php esc_attr_e('SMS', 'wpnotif'); ?></div>
                        <?php
                    }
                    if ($user_consent['whatsapp_consent'] == 1) {
                        ?>
                        <div><input type="checkbox"
                                    disabled="disabled" <?php if ($user_consent['whatsapp_notifications'] == 1) {
                                echo 'checked';
                            } ?>/> <?php esc_attr_e('WhatsApp', 'wpnotif'); ?></div>
                        <?php
                    }
                    if ($user_consent['sms_notifications'] != 1 && $user_consent['whatsapp_notifications'] != 1) {
                        $show_trigger = false;
                    }
                }

                ?>

                <?php
            }
        }

        if ($data != null) {
            echo $data;
        }
        ?>
        <input type="hidden" class="wpnotif_nonce" value="<?php echo esc_attr($nonce); ?>"/>
        <div class="quick_sms_grid">
            <div class="quick_sms_response">
                <div class="quick_sms_response_box"><i></i><span class="msg"></span></div>
            </div>
            <div class="quick_sms">
                <input type="hidden" value="<?php echo esc_html($order_id); ?>" name="order_id"
                       class="wpnotif_order_id"/>


                <?php
                if ($order) {
                    $data = WPNotif::data_type('order', $order);
                    $mobile = WPNotif_Handler::get_customer_mobile($order->get_customer_id(), $data);
                    if (!empty($mobile)) {
                        ?>
                        <h2>
                            <?php echo esc_attr__('Quick Notification', 'wpnotif'); ?>
                        </h2>
                        <input type="hidden" class="mobile" name="mobile"
                               value="<?php echo esc_html($mobile['countrycode']);
                               echo esc_html($mobile['mobile']); ?>"/>
                        <?php
                    }
                }
                ?>
                <?php if ($order_id == 0) { ?>
                    <table class="form-table">
                        <tbody>
                        <tr>
                            <th scope="row">
                                <label for="wpnotif_mobile"><?php esc_html_e('Phone Number(s)', 'wpnotif'); ?></label>
                            </th>
                            <td>
                                <div class="mobile_number_container">
                                    <div class="mobile_number_field">
                                        <div class="digcon">
                                            <select class="wpnotif_multiselect_dynamic_enable mobile"
                                                    id="wpnotif_mobile" multiple>

                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <p class="wpnotif_desc"><?php esc_attr_e('Please input numbers with country code', 'wpnotif'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="wpnotif_route"><?php esc_html_e('Route', 'wpnotif'); ?></label>
                            </th>
                            <td>
                                <div class="route">
                                    <div class="wpnotif_route_field">
                                        <select class="wc_message_send_route" id="wpnotif_route">
                                            <option value="both" selected>
                                                <?php echo esc_attr__('Both', 'wpnotif'); ?>
                                            </option>
                                            <option value="sms">
                                                <?php echo esc_attr__('SMS', 'wpnotif'); ?>
                                            </option>
                                            <option value="whatsapp">
                                                <?php echo esc_attr__('WhatsApp', 'wpnotif'); ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="quick_message"><?php esc_html_e('SMS', 'wpnotif'); ?></label>
                            </th>
                            <td>
                                <div class="wpn_quick_message message">
                                    <div class="position-relative">
                                        <textarea id="quick_message" name="quick_message"></textarea>
                                        <a href="https://help.unitedover.com/wpnotif/kb/placeholders" target="_blank">
                                            <span class="placeholder_list"><?php esc_html_e('Placeholder List', 'wpnotif'); ?></span>
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="quick_whatsapp_message"><?php esc_html_e('WhatsApp', 'wpnotif'); ?></label>
                            </th>
                            <td>
                                <div class="wpn_quick_message whatsapp_message">
                                    <div class="position-relative">
                                        <textarea id="quick_whatsapp_message" name="quick_whatsapp_message"></textarea>
                                        <a href="https://help.unitedover.com/wpnotif/kb/placeholders" target="_blank">
                                            <span class="placeholder_list"><?php esc_html_e('Placeholder List', 'wpnotif'); ?></span>
                                        </a>
                                    </div>
                                </div>

                                <div class="send_sms_button_container">
                                    <button class="send_quick_sms"
                                            data-processing="<?php esc_html_e('Processing', 'wpnotif'); ?>"
                                            data-send="<?php esc_html_e('Send', 'wpnotif'); ?>">
                                        <span><?php esc_html_e('Send', 'wpnotif'); ?></span>
                                        <div></div>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                <?php } else {
                    ?>
                    <div class="route">
                        <label for="wpnotif_route"><?php esc_html_e('Route', 'wpnotif'); ?></label>
                        <div class="wpnotif_route_field">
                            <select class="wc_message_send_route" id="wpnotif_route">
                                <option value="both" selected>
                                    <?php echo esc_attr__('Both', 'wpnotif'); ?>
                                </option>
                                <option value="sms">
                                    <?php echo esc_attr__('SMS', 'wpnotif'); ?>
                                </option>
                                <option value="whatsapp">
                                    <?php echo esc_attr__('WhatsApp', 'wpnotif'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="wpn_quick_message message">
                        <label for="quick_message"><?php esc_html_e('SMS', 'wpnotif'); ?></label>
                        <div class="position-relative">
                            <textarea id="quick_message" name="quick_message"></textarea>
                            <a href="https://help.unitedover.com/wpnotif/kb/placeholders" target="_blank">
                                <span class="placeholder_list"><?php esc_html_e('Placeholder List', 'wpnotif'); ?></span>
                            </a>
                        </div>
                    </div>
                    <div class="wpn_quick_message whatsapp_message">
                        <label for="quick_whatsapp_message"><?php esc_html_e('WhatsApp', 'wpnotif'); ?></label>
                        <div class="position-relative">
                            <textarea id="quick_whatsapp_message" name="quick_whatsapp_message"></textarea>
                            <a href="https://help.unitedover.com/wpnotif/kb/placeholders" target="_blank">
                                <span class="placeholder_list"><?php esc_html_e('Placeholder List', 'wpnotif'); ?></span>
                            </a>
                        </div>
                    </div>
                    <div class="send_sms_button_container">
                        <button class="send_quick_sms" data-processing="<?php esc_html_e('Processing', 'wpnotif'); ?>"
                                data-send="<?php esc_html_e('Send', 'wpnotif'); ?>">
                            <span><?php esc_html_e('Send', 'wpnotif'); ?></span>
                        </button>
                    </div>
                    <?php
                } ?>
            </div>

            <?php if ($order_id != 0 && $show_trigger && $order) {
                $statuses = wc_get_order_statuses();
                ?>
                <div class="quick_sms">
                    <!-- <table class="form-table">
                        <tbody>
                            <tr></tr>
                        </tbody>
                    </table> -->

                    <hr/>
                    <h2><?php esc_html_e('Trigger Order Status Message', 'wpnotif'); ?></h2>
                    <div>
                        <label>
                            <?php echo esc_attr__('Status', 'wpnotif'); ?>
                        </label>

                        <select autocomplete="off" class="wc_send_status">
                            <option value="-1" selected>
                                <?php echo esc_attr__('Current Status', 'wpnotif'); ?></option>
                            <?php
                            foreach ($statuses as $key => $status) {
                                $key = esc_attr($key);
                                ?>
                                <option value="<?php echo $key; ?>"><?php echo $status; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label>
                            <?php echo esc_attr__('Route', 'wpnotif'); ?>
                        </label>

                        <select autocomplete="off" class="wc_send_route">
                            <option value="both" selected><?php echo esc_attr__('Both', 'wpnotif'); ?></option>
                            <option value="sms"><?php echo esc_attr__('SMS', 'wpnotif'); ?></option>
                            <option value="whatsapp"><?php echo esc_attr__('WhatsApp', 'wpnotif'); ?></option>
                        </select>
                    </div>
                    <div class="send_sms_button_container">
                        <button class="send_quick_sms trigger_order_status"
                                data-processing="<?php esc_html_e('Processing', 'wpnotif'); ?>"
                                data-send="<?php esc_html_e('Send', 'wpnotif'); ?>">
                            <span><?php esc_html_e('Send', 'wpnotif'); ?></span>
                            <div></div>
                        </button>
                    </div>
                </div>
                <?php
            } ?>


        </div>
        <?php
    }

    public static function data_type($type, $data, $notify = 0)
    {
        return array('type' => $type, 'data' => $data, 'notify' => $notify);
    }

    public function save_settings()
    {
        check_admin_referer('wpnotif_settings_save');

        if (!current_user_can('manage_options')) {
            die();
        }


        if (empty($_POST['wpnotif_purchasecode'])) {
            delete_option('wpnotif_purchasecode');
        } else {
            $pcsave = true;
            if (isset($_REQUEST['pca'])) {
                if ($_REQUEST['pca'] == 1) {
                    $pcsave = true;
                } else {
                    $pcsave = false;
                }
            }
            if ($pcsave) {

                update_option('wpnotif_purchasecode', $_POST['wpnotif_purchasecode']);
                update_option('wpnotif_license_type', $_POST['wpnotif_license_type']);
            }
        }
        if (isset($_POST['wpnotif_gateways'])) {
            $wpnotif_gateways = $_POST['wpnotif_gateways'];
            update_option('wpnotif_gateways', $wpnotif_gateways);
        }


        update_option('wpnotf_admin_user_role', $this->get_post('wpnotf_admin_user_role'));

        if (isset($_POST['twiliosid'])) {
            $twiliosid = sanitize_text_field($_POST['twiliosid']);
            $twiliotoken = sanitize_text_field($_POST['twiliotoken']);
            $twiliosenderid = sanitize_text_field($_POST['twiliosenderid']);


            $tiwilioapicred = array(
                    'twiliosid' => $twiliosid,
                    'twiliotoken' => $twiliotoken,
                    'twiliosenderid' => $twiliosenderid
            );

            if (get_option('wpnotif_twilio_api') !== false) {
                update_option('wpnotif_twilio_api', $tiwilioapicred);
            } else {
                add_option('wpnotif_twilio_api', $tiwilioapicred);
            }


        }

        if (isset($_POST['msg91authkey'])) {
            $msg91authkey = sanitize_text_field($_POST['msg91authkey']);
            $msg91senderid = sanitize_text_field($_POST['msg91senderid']);
            $msg91route = sanitize_text_field($_POST['msg91route']);
            $msg91dlt_te_id = sanitize_text_field($_POST['msg91dlt_te_id']);

            $msg91apicred = array(
                    'msg91authkey' => $msg91authkey,
                    'msg91senderid' => $msg91senderid,
                    'msg91route' => $msg91route,
                    'msg91dlt_te_id' => $msg91dlt_te_id
            );
            if (get_option('wpnotif_msg91_api') !== false) {
                update_option('wpnotif_msg91_api', $msg91apicred);
            } else {
                add_option('wpnotif_msg91_api', $msg91apicred);
            }

        }

        if (isset($_POST['yunpianapikey'])) {
            $yunpianapikey = sanitize_text_field($_POST['yunpianapikey']);
            update_option('wpnotif_yunpianapi', $yunpianapikey);
        }


        $smsgateways = $this->getGateWayArray();

        foreach ($smsgateways as $name => $details) {
            $name = strtolower(str_replace([".", " "], "_", $name));
            $gatewaycred = array();
            foreach ($details['inputs'] as $inputlabel => $input) {

                $inputValue = sanitize_text_field($this->get_post($name . "_" . $input['name']));

                $gatewaycred[$input['name']] = $inputValue;

            }
            $option_key = 'wpnotif_' . strtolower($name);
            if (!empty($gatewaycred)) {
                update_option($option_key, $gatewaycred);
            } else {
                delete_option($option_key);
            }
        }

        update_option('wpnotif_gateway_customer_notifications', $_POST['wpnotif_gateway_customer_notifications']);


        $wpnotif_user_consent_inputs = $this->get_user_consent_settings();
        $user_consent_settings = array();
        foreach ($wpnotif_user_consent_inputs as $key => $consent_settings_input) {
            $label = 'User - ' . $consent_settings_input['label'];
            $message = sanitize_textarea_field($this->get_post('user_' . $key . '_msg'));
            $user_consent_settings[$key]['enable'] = sanitize_text_field($this->get_post('user_' . $key));
            $user_consent_settings[$key]['msg'] = $message;
            $user_consent_settings[$key]['label'] = $label;

            do_action('wpml_register_single_string', self::wpml_slug(), $label, $message);
        }
        update_option('wpnotif_user_consent', $user_consent_settings);


        $admin_settings_inputs = $this->get_settings('admin');

        $admin_settings = array();
        foreach ($admin_settings_inputs as $key => $admin_settings_input) {
            $label = 'Admin - ' . $admin_settings_input['label'];
            $message = sanitize_textarea_field($this->get_post('admin_' . $key . '_msg'));

            $admin_settings[$key]['enable'] = sanitize_text_field($this->get_post('admin_' . $key));
            $admin_settings[$key]['msg'] = $message;
            $admin_settings[$key]['label'] = $label;

            do_action('wpml_register_single_string', self::wpml_slug(), $label, $message);
        }
        update_option('wpnotif_admin_notifications', $admin_settings);


        $options = array('customer', 'vendor');
        foreach ($options as $option) {

            $customer_settings_inputs = $this->get_settings($option);
            $customer_settings = array();
            foreach ($customer_settings_inputs as $key => $customer_settings_input) {
                $label = ucfirst($option) . ' - ' . $customer_settings_input['label'];

                $message = sanitize_textarea_field($this->get_post($option . '_' . $key . '_msg'));

                $customer_settings[$key]['enable'] = sanitize_text_field($this->get_post($option . '_' . $key));
                $customer_settings[$key]['msg'] = $message;
                $customer_settings[$key]['label'] = $label;

                do_action('wpml_register_single_string', self::wpml_slug(), $label, $message);
            }

            update_option('wpnotif_' . $option . '_notifications', $customer_settings);
        }
        update_option('different_gateway_content', $this->get_post('different_gateway_content'));

        $this->update_settings();


        do_action('wpnotif_admin_update_settings');

        WPNotif_NewsLetter_Handler::wpn_background_process();

        wp_die();
    }

    public function get_post($name)
    {
        if (!empty($_POST[$name])) {
            return $_POST[$name];
        } else {
            return '';
        }
    }

    public function getGateWayArray()
    {

        $src = admin_url('admin-ajax.php');

        $data = array('nonce' => wp_create_nonce('wpnotif_qrcode'), 'action' => 'wpnotif_get_qrcode');
        $big_src = add_query_arg($data, $src);

        $data['preview'] = 1;
        $src = add_query_arg($data, $src);

        $key = WPNotif_App_Handler::instance()->get_data();

        $whatsapp_gateway_inputs = array(
                array(
                        'select' => true,
                        'grouped' => 1,
                        'value' => $this->WhatsAppGateways(),
                        'name' => 'whatsapp_gateway',
                        'label' => esc_attr__('WhatsApp Gateway'),
                ),
                array(
                        'require_additional' => true,
                        'name' => 'require_additional',
                        'label' => '',
                        'hide_if' => 'whatsapp_gateway_1 whatsapp_gateway_2'
                ),
                array(
                        'text' => true,
                        'value' => 1,
                        'name' => 'account_sid',
                        'show_if' => 'whatsapp_gateway_1',
                        'label' => esc_attr__('Twilio Account SID'),
                ),
                array(
                        'text' => true,
                        'value' => 1,
                        'name' => 'auth_token',
                        'show_if' => 'whatsapp_gateway_1',
                        'label' => esc_attr__('Twilio Auth Token'),
                ),
                array(
                        'text' => true,
                        'value' => 1,
                        'name' => 'whatsappnumber',
                        'show_if' => 'whatsapp_gateway_1',
                        'label' => esc_attr__('WhatsApp Number'),
                ),
                array(
                        'select' => true,
                        'options' => array('No' => 0, 'Yes' => 1),
                        'name' => 'using_whatsapp_app_on_desktop',
                        'show_if' => 'whatsapp_gateway_2',
                        'label' => esc_attr__('Using WhatsApp app on desktop?'),
                ),
                array(
                        'image' => true,
                        'name' => 'app_cred',
                        'label' => esc_attr__('App Setup QR', 'wpnotif'),
                        'src' => $src,
                        'preview_src' => $big_src,
                        'show_if' => 'whatsapp_gateway_2'
                ),
                array(
                        'text' => true,
                        'label' => esc_attr__('Key (for manual setup)', 'wpnotif'),
                        'name' => 'key',
                        'default_value' => $key,
                        'readonly' => 1,
                        'fix_value' => 1,
                        'show_if' => 'whatsapp_gateway_2'
                ),
                array(
                        'text' => true,
                        'value' => 3,
                        'name' => 'messagebird_accesskey',
                        'show_if' => 'whatsapp_gateway_3',
                        'label' => esc_attr__('Access Key'),
                ),
                array(
                        'text' => true,
                        'value' => 3,
                        'name' => 'messagebird_channel_id',
                        'show_if' => 'whatsapp_gateway_3',
                        'label' => esc_attr__('Whatsapp Channel ID'),
                ),
                array(
                        'text' => true,
                        'value' => 4,
                        'name' => 'karix_uid',
                        'show_if' => 'whatsapp_gateway_4',
                        'label' => esc_attr__('UID'),
                ),
                array(
                        'text' => true,
                        'value' => 4,
                        'name' => 'karix_token',
                        'show_if' => 'whatsapp_gateway_4',
                        'label' => esc_attr__('Token'),
                ),
                array(
                        'text' => true,
                        'value' => 4,
                        'name' => 'karix_sender',
                        'show_if' => 'whatsapp_gateway_4',
                        'label' => esc_attr__('Sender'),
                ),
                array(
                        'text' => true,
                        'value' => 5,
                        'name' => 'gupshup_api_key',
                        'show_if' => 'whatsapp_gateway_5',
                        'label' => esc_attr__('API Key'),
                ),
                array(
                        'text' => true,
                        'value' => 5,
                        'name' => 'gupshup_app_name',
                        'show_if' => 'whatsapp_gateway_5',
                        'label' => esc_attr__('APP Name'),
                ),
                array(
                        'text' => true,
                        'value' => 5,
                        'name' => 'gupshup_source',
                        'show_if' => 'whatsapp_gateway_5',
                        'label' => esc_attr__('Source'),
                ),
                array(
                        'text' => true,
                        'value' => 6,
                        'name' => '360dialog_api_key',
                        'show_if' => 'whatsapp_gateway_6',
                        'label' => esc_attr__('API Key'),
                ),
                array(
                        'text' => true,
                        'value' => 7,
                        'name' => 'damcorp_api_token',
                        'show_if' => 'whatsapp_gateway_7',
                        'label' => esc_attr__('API Token'),
                ),
                array(
                        'text' => true,
                        'value' => 8,
                        'name' => 'spoki_api_key',
                        'show_if' => 'whatsapp_gateway_8',
                        'label' => esc_attr__('API Key'),
                ),


                array(
                        'text' => true,
                        'value' => 9,
                        'name' => 'wati_access_token',
                        'show_if' => 'whatsapp_gateway_9',
                        'label' => esc_attr__('Access Token'),
                ),
                array(
                        'text' => true,
                        'value' => 9,
                        'name' => 'wati_base_url',
                        'show_if' => 'whatsapp_gateway_9',
                        'label' => esc_attr__('Base Url'),
                ),
                array(
                        'text' => true,
                        'value' => 10,
                        'name' => 'whatsapp_cloud_access_token',
                        'show_if' => 'whatsapp_gateway_10',
                        'label' => esc_attr__('Access Token'),
                ),
                array(
                        'text' => true,
                        'value' => 10,
                        'name' => 'whatsapp_from_number_id',
                        'show_if' => 'whatsapp_gateway_10',
                        'label' => esc_attr__('From Phone Number ID'),
                ),


                array(
                        'text' => true,
                        'value' => 11,
                        'name' => 'msg91_whatsapp_auth_key',
                        'show_if' => 'whatsapp_gateway_11',
                        'label' => esc_attr__('Auth Key'),
                ),
                array(
                        'text' => true,
                        'value' => 11,
                        'name' => 'msg91_whatsapp_from',
                        'show_if' => 'whatsapp_gateway_11',
                        'label' => esc_attr__('From'),
                ),
                array(
                        'text' => true,
                        'value' => 11,
                        'name' => 'msg91_whatsapp_template_namespace',
                        'show_if' => 'whatsapp_gateway_11',
                        'label' => esc_attr__('Template Namespace'),
                ),


                array(
                        'text' => true,
                        'value' => 13,
                        'name' => 'chat_api_access_token',
                        'show_if' => 'whatsapp_gateway_13',
                        'label' => esc_attr__('Access Token'),
                ),
                array(
                        'text' => true,
                        'value' => 13,
                        'name' => 'chat_api_instance_id',
                        'show_if' => 'whatsapp_gateway_13',
                        'label' => esc_attr__('Instance ID'),
                ),
                array(
                        'text' => true,
                        'value' => 14,
                        'name' => 'interakt_access_token',
                        'show_if' => 'whatsapp_gateway_14',
                        'label' => esc_attr__('Access Token'),
                ),
                array(
                        'text' => true,
                        'value' => 15,
                        'name' => 'kaylera_access_token',
                        'show_if' => 'whatsapp_gateway_15',
                        'label' => esc_attr__('Access Token'),
                ),
                array(
                        'text' => true,
                        'value' => 16,
                        'name' => 'wa_team_access_token',
                        'show_if' => 'whatsapp_gateway_16',
                        'label' => esc_attr__('Access Token'),
                ),
                array(
                        'text' => true,
                        'value' => 16,
                        'name' => 'wa_team_url',
                        'show_if' => 'whatsapp_gateway_16',
                        'label' => esc_attr__('Team URL'),
                ),

                array(
                        'text' => true,
                        'value' => 17,
                        'name' => 'twilio_new_account_sid',
                        'show_if' => 'whatsapp_gateway_17',
                        'label' => esc_attr__('Twilio Account SID'),
                ),
                array(
                        'text' => true,
                        'value' => 17,
                        'name' => 'twilio_new_auth_token',
                        'show_if' => 'whatsapp_gateway_17',
                        'label' => esc_attr__('Twilio Auth Token'),
                ),
                array(
                        'text' => true,
                        'value' => 17,
                        'name' => 'twilio_new_whatsappnumber',
                        'show_if' => 'whatsapp_gateway_17',
                        'label' => esc_attr__('From'),
                ),
                array(
                        'text' => true,
                        'value' => 17,
                        'name' => 'twilio_new_mssid',
                        'show_if' => 'whatsapp_gateway_17',
                        'label' => esc_attr__('Message Sender ID'),
                ),

                array(
                        'link' => true,
                        'name' => 'download',
                        'label' => esc_attr__('Download'),
                        'link_text' => esc_attr__('Download Android App', 'wpnotif'),
                        'href' => 'https://bridge.unitedover.com/download/?app=wpnotif.apk&type=download',
                        'show_if' => 'whatsapp_gateway_2',
                ),
        );

        $whatsapp_gateway_inputs = apply_filters('wpnotif_whatsapp_gateway_inputs', $whatsapp_gateway_inputs);

        /// next 300
        $smsgateways = array(
                'WhatsApp' => array(
                        'value' => 1001,
                        'inputs' =>
                                $whatsapp_gateway_inputs,
                        'hide' => 1,
                ),

                'Twilio' => array(
                        'value' => 2,
                        'group' => 'starting_group',
                        'inputs' => array()
                ),

                'Msg91' => array(
                        'value' => 3,
                        'group' => 'starting_group',
                        'document_link' => true,
                        'inputs' => array()
                ),

                'MessageBird' => array(
                        'value' => 8,
                        'group' => 'starting_group',
                        'inputs' => array(
                                esc_attr__('API Key') => array(
                                        'text' => true,
                                        'name' => 'accesskey'
                                ),
                                esc_attr__('Originator') => array(
                                        'text' => true,
                                        'name' => 'originator'
                                )
                        )
                ),

                'Plivo' => array(
                        'value' => 11,
                        'group' => 'starting_group',
                        'inputs' => array(
                                esc_attr__('Auth ID') => array(
                                        'text' => true,
                                        'name' => 'auth_id'
                                ),
                                esc_attr__('Auth Token') => array(
                                        'text' => true,
                                        'name' => 'auth_token'
                                ),
                                esc_attr__('Sender') => array(
                                        'text' => true,
                                        'name' => 'sender_id',
                                        'optional' => 1
                                )
                        )
                ),
                'ClickSend' => array(
                        'value' => 6,
                        'group' => 'starting_group',
                        'inputs' => array(
                                esc_attr__('API Username') => array(
                                        'text' => true,
                                        'name' => 'apiusername'
                                ),
                                esc_attr__('API Key') => array(
                                        'text' => true,
                                        'name' => 'apikey'
                                ),
                                esc_attr__('From') => array('text' => true, 'name' => 'from', 'optional' => 1)
                        )
                ),
                'Infobip' => array(
                        'value' => 32,
                        'group' => 'starting_group',
                        'inputs' =>
                                array(
                                        __('Base URL') => array('text' => true, 'name' => 'base_url'),
                                        __('API Key') => array('text' => true, 'name' => 'api_key'),
                                        __('From') => array('text' => true, 'name' => 'from', 'optional' => 1)
                                )
                ),

                'Amazon SNS' => array(
                        'value' => 25,
                        'require_addon' => 1,
                        'inputs' =>
                                array(
                                        __('Access Key ID') => array('text' => true, 'name' => 'access_key'),
                                        __('Secret Access Key') => array('text' => true, 'name' => 'access_secret'),
                                        __('Region') => array('text' => true, 'name' => 'region'),
                                        __('Sender ID') => array('text' => true, 'name' => 'sender_id', 'optional' => 1),
                                        __('Entity ID') => array('text' => true, 'name' => 'entity_id', 'optional' => 1),
                                ),
                ),
                'Amazon Pinpoint' => array(
                        'value' => 30,
                        'require_addon' => 1,
                        'inputs' =>
                                array(
                                        __('Application ID/Project ID') => array('text' => true, 'name' => 'app_id'),
                                        __('Access Key ID') => array('text' => true, 'name' => 'access_key'),
                                        __('Secret Access Key') => array('text' => true, 'name' => 'access_secret'),
                                        __('Region') => array('text' => true, 'name' => 'region'),
                                        __('Sender ID') => array('text' => true, 'name' => 'sender_id', 'optional' => 1),
                                        __('Entity ID') => array('text' => true, 'name' => 'entity_id', 'optional' => 1),
                                ),
                ),
                'Alibaba' => array(
                        'value' => 18,
                        'require_addon' => 1,
                        'inputs' =>
                                array(
                                        esc_attr__('Access Key') => array('text' => true, 'name' => 'access_key'),
                                        esc_attr__('Access Secret') => array('text' => true, 'name' => 'access_secret'),
                                        esc_attr__('From') => array('text' => true, 'name' => 'from', 'optional' => 1)
                                )
                ),

                'Clickatell' => array(
                        'value' => 5,
                        'inputs' => array(
                                esc_attr__('API Key') => array('text' => true, 'name' => 'api_key'),
                                esc_attr__('From') => array(
                                        'text' => true,
                                        'name' => 'from',
                                        'optional' => 1
                                )
                        )
                ),
                'ClockWork' => array(
                        'value' => 7,
                        'inputs' => array(
                                esc_attr__('ClockWork API') => array(
                                        'text' => true,
                                        'name' => 'clockworkapi'
                                ),
                                esc_attr__('From') => array('text' => true, 'name' => 'from', 'optional' => 1)
                        )
                ),
                'Kaleyra' => array(
                        'value' => 15,
                        'inputs' => array(
                                esc_attr__('API Key') => array('text' => true, 'name' => 'api_key'),
                                esc_attr__('SID') => array('text' => true, 'name' => 'sid'),
                                esc_attr__('Sender ID') => array(
                                        'text' => true,
                                        'name' => 'sender_id',
                                )
                        )
                ),
                'Mobily.ws' => array(
                        'value' => 9,
                        'require_addon' => 1,
                        'inputs' => array(
                                esc_attr__('Mobile') => array('text' => true, 'name' => 'mobile'),
                                esc_attr__('Password') => array(
                                        'text' => true,
                                        'name' => 'password'
                                ),
                                esc_attr__('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1)
                        )
                ),
                'Alfa Cell' => array(
                        'value' => 28,
                        'require_addon' => 1,
                        'inputs' => array(
                                __('API Key') => array('text' => true, 'name' => 'api_key'),
                                __('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1)
                        )
                ),
                'Nexmo' => array(
                        'value' => 10,
                        'inputs' => array(
                                esc_attr__('API Key') => array(
                                        'text' => true,
                                        'name' => 'api_key'
                                ),
                                esc_attr__('API Secret') => array(
                                        'text' => true,
                                        'name' => 'api_secret'
                                ),
                                esc_attr__('From') => array('text' => true, 'name' => 'from', 'optional' => 1)
                        )
                ),
            /*'CM'          => array(
                'value'  => 27,
                'inputs' => array(
                    __( 'API Key' ) => array(
                        'text' => true,
                        'name' => 'api_key'
                    ),
                    __( 'From' )    => array( 'text' => true, 'name' => 'from' )
                )
            ),*/
                'SMSAPI' => array(
                        'value' => 12,
                        'inputs' => array(
                                esc_attr__('Token') => array('text' => true, 'name' => 'token'),
                                esc_attr__('From') => array('text' => true, 'name' => 'from', 'optional' => 1)
                        )
                ),

                'Textlocal' => array(
                        'value' => 17,
                        'inputs' =>
                                array(
                                        esc_attr__('API Key') => array('text' => true, 'name' => 'api_key'),
                                        esc_attr__('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1)
                                )
                ),
                'Unifonic' => array(
                        'value' => 14,
                        'inputs' =>
                                array(
                                        esc_attr__('AppSid') => array('text' => true, 'name' => 'appsid'),
                                        esc_attr__('Sender ID') => array('text' => true, 'name' => 'senderid', 'optional' => 1)
                                )
                ),

                'Melipayamak' => array(
                        'value' => 16,
                        'inputs' => array(
                                esc_attr__('Username') => array('text' => true, 'name' => 'username'),
                                esc_attr__('Password') => array('text' => true, 'name' => 'password'),
                                esc_attr__('From') => array('text' => true, 'name' => 'from', 'optional' => 1),

                        )
                ),

                'AfricasTalking' => array(
                        'value' => 26,
                        'inputs' =>
                                array(
                                        __('Username') => array('text' => true, 'name' => 'username'),
                                        __('API Key') => array('text' => true, 'name' => 'api_key'),
                                        __('From') => array('text' => true, 'name' => 'from', 'optional' => 1)
                                )
                ),

                'Adnsms' => array(
                        'value' => 19,
                        'require_addon' => 1,
                        'inputs' =>
                                array(
                                        esc_attr__('API Key') => array('text' => true, 'name' => 'api_key'),
                                        esc_attr__('API Secret') => array('text' => true, 'name' => 'api_secret')
                                )
                ),

                'Netgsm' => array(
                        'value' => 20,
                        'inputs' =>
                                array(
                                        esc_attr__('Username') => array('text' => true, 'name' => 'username'),
                                        esc_attr__('Password') => array('text' => true, 'name' => 'password'),
                                        esc_attr__('From') => array('text' => true, 'name' => 'from', 'optional' => 1)
                                )
                ),

                'SMSC' => array(
                        'value' => 21,
                        'inputs' =>
                                array(
                                        esc_attr__('Login') => array('text' => true, 'name' => 'login'),
                                        esc_attr__('Password') => array('text' => true, 'name' => 'password'),
                                        esc_attr__('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1)
                                )
                ),
                'TargetSMS' => array(
                        'value' => 22,
                        'require_addon' => 1,
                        'inputs' =>
                                array(
                                        esc_attr__('Login') => array('text' => true, 'name' => 'login'),
                                        esc_attr__('Password') => array('text' => true, 'name' => 'password'),
                                        esc_attr__('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1)
                                )
                ),
                'Ghasedak' => array(
                        'value' => 23,
                        'require_addon' => 1,
                        'inputs' =>
                                array(
                                        __('API Key') => array('text' => true, 'name' => 'api_key')
                                )
                ),
                'Farapayamak' => array(
                        'value' => 24,
                        'require_addon' => 1,
                        'inputs' =>
                                array(
                                        __('Username') => array('text' => true, 'name' => 'username'),
                                        __('Password') => array('text' => true, 'name' => 'password'),
                                        __('From') => array('text' => true, 'name' => 'from', 'optional' => 1)
                                )
                ),
                'SendinBlue' => array(
                        'value' => 31,
                        'inputs' =>
                                array(
                                        __('API Key') => array('text' => true, 'name' => 'api_key'),
                                        __('Sender') => array('text' => true, 'name' => 'sender', 'optional' => 1)
                                )
                ),
                'IBulksms' => array(
                        'value' => 29,
                        'require_addon' => 1,
                        'inputs' =>
                                array(
                                        __('Auth Key') => array('text' => true, 'name' => 'auth_key'),
                                        __('Sender ID') => array('text' => true, 'name' => 'sender', 'optional' => 1)
                                )
                ),

                'Yunpian' => array(
                        'value' => 4,
                        'inputs' => array()
                ),
        );
        $smsgateways = apply_filters('unitedover_sms_gateways', $smsgateways);

        $smsgateways = apply_filters('wpnotif_sms_gateways', $smsgateways);

        return $smsgateways;
    }

    public function WhatsAppGateways()
    {
        return array(
                'free' => array(
                        'label' => '',
                        'values' => array(esc_attr__('WhatsApp Business Free', 'wpnotif') => array('value' => 2))
                ),
                'api' => array(
                        'label' => esc_attr__('WhatsApp Business API', 'wpnotif'),
                        'values' => array(
                                esc_attr__('WhatsApp Cloud API', 'wpnotif') => array('value' => 10, 'require_addon' => 1, 'document_link' => true),
                                esc_attr__('Twilio', 'wpnotif') => array('value' => 17, 'require_addon' => 1, 'document_link' => true),
                                esc_attr__('MessageBird', 'wpnotif') => array('value' => 3, 'require_addon' => 1, 'document_link' => true),
                                esc_attr__('Karix', 'wpnotif') => array('value' => 4, 'require_addon' => 1, 'document_link' => true),
                                esc_attr__('Gupshup', 'wpnotif') => array('value' => 5, 'require_addon' => 1, 'document_link' => true),
                                esc_attr__('360Dialog', 'wpnotif') => array('value' => 6, 'require_addon' => 1, 'document_link' => true),
                                esc_attr__('Damcorp', 'wpnotif') => array('value' => 7, 'require_addon' => 1, 'document_link' => true),
                                esc_attr__('Spoki', 'wpnotif') => array('value' => 8, 'require_addon' => 1, 'document_link' => true),
                                esc_attr__('Wati', 'wpnotif') => array('value' => 9, 'require_addon' => 1, 'document_link' => true),
                                esc_attr__('Msg91', 'wpnotif') => array('value' => 11, 'require_addon' => 1, 'document_link' => true),

                                esc_attr__('ChatAPI', 'wpnotif') => array('value' => 13, 'require_addon' => 1, 'document_link' => true),
                                esc_attr__('Interakt', 'wpnotif') => array('value' => 14, 'require_addon' => 1, 'document_link' => true),
                                esc_attr__('Kaylera', 'wpnotif') => array('value' => 15, 'require_addon' => 1, 'document_link' => true),
                                esc_attr__('WATeam', 'wpnotif') => array('value' => 16, 'require_addon' => 1, 'document_link' => true),


                                esc_attr__('Custom WhatsApp Gateway', 'wpnotif') => array('value' => 12, 'require_addon' => 1, 'document_link' => true),
// start from 18

                                esc_attr__('Twilio (Old)', 'wpnotif') => array('value' => 1, 'require_addon' => 1),

                        )
                ),
        );
    }

    public function get_user_consent_settings()
    {
        return array(
                'notify_user' => array(
                        'label' => __('For SMS Message', 'wpnotif'),
                        'message' => '1',
                        'message_label' => __('Checkbox Text', 'wpnotif'),
                        'placeholder' => '0',
                ),
                'whatsapp_message' => array(
                        'label' => __('For WhatsApp Message', 'wpnotif'),
                        'message' => '1',
                        'message_label' => __('Checkbox Text', 'wpnotif'),
                        'placeholder' => '0',
                ),
                'combine_both' => array(
                        'label' => __('Combine Both', 'wpnotif'),
                        'message' => '1',
                        'message_label' => __('Checkbox Text', 'wpnotif'),
                        'placeholder' => '0',
                ),
                'tick_by_default' => array(
                        'label' => __('Tick checkboxes by default', 'wpnotif'),
                        'message' => '0',
                        'placeholder' => '0',
                )


        );
    }

    public static function wpml_slug()
    {
        return 'wpnotif';
    }

    public function get_settings($section)
    {

        $list = array();
        if (function_exists('wc_get_order_statuses')) {
            $statuses = wc_get_order_statuses();
        } else {
            $statuses = array();
        }


        $list = apply_filters('wpnotif_notification_list_' . $section, $list);

        foreach ($statuses as $key => $status) {
            if ($key == 'wc-processing') {
                $status = $status . ' ' . esc_attr__('(New Order)', 'wpnotif');
            }
            $list[$key] = array(
                    'label' => $status,
                    'message' => '1',
                    'placeholder' => '1',
            );
        }
        $list = apply_filters('wpnotif_notification_list_after_wc_status_' . $section, $list);

        return $list;
    }

    public function update_settings()
    {
        $wpnotif_usage_data_sharing = !empty($_POST['wpnotif_usage_data_sharing']) ? $_POST['wpnotif_usage_data_sharing'] : 0;
        update_option('wpnotif_usage_data_sharing', $wpnotif_usage_data_sharing);

        if ($wpnotif_usage_data_sharing == 1) {
            if (!wp_next_scheduled('wpnotif_dbasic_cron_job')) {
                wp_schedule_event(time(), 'daily', 'wpnotif_dbasic_cron_job');
            }
        } else {
            wp_unschedule_hook('wpnotif_dbasic_cron_job');
        }

        update_option('wpnotif_phone_verification', sanitize_text_field($_POST['wpnotif_phone_verification']));

        $settings = self::plugin_settings(false);
        foreach ($settings as $key => $setting) {
            $settings[$key] = sanitize_text_field($_POST[$key]);
        }
        update_option('wpnotif_settings', $settings);


        update_option('wpnotif_pending_payment_notification_time', absint($_POST['pending_payment_notification_time']));
        update_option('wpnotif_abandon_cart_notification_time', absint($_POST['abandon_cart_notif']));
    }

    public static function plugin_settings($withValue = true)
    {
        $options = array(
                'default_countrycode' => '-1'
        );

        if ($withValue) {
            $values = get_option('wpnotif_settings', $options);

            foreach ($values as $value) {
                $key = array_search($value, $values);
                if ($key !== FALSE) {
                    unset($options[$key]);
                }
            }
            $options = array_merge($options, $values);
        }
        return $options;
    }

    public function frontend_scripts()
    {
        if (wp_script_is('wpnotif-frontend')) {
            return;
        }

        add_action('wp_footer', array($this, 'add_countrylist'), 100);
        add_action('admin_footer', array($this, 'add_countrylist'), 100);

        wp_enqueue_style('wpnotif-frontend', plugins_url('/assets/css/frontend.min.css', __FILE__), array(), self::get_version(), 'all');

        wp_enqueue_script('scrollTo', plugins_url('/assets/js/scrollTo.js', __FILE__), array('jquery'), self::get_version());

        wp_register_script('wpnotif-frontend', plugins_url('/assets/js/frontend.min.js', __FILE__), array('jquery', 'scrollTo'), self::get_version());
        $data = array(
                "ohsnap" => __("Oh Snap!", "wpnotif"),
                "yay" => __("Yay!", "wpnotif"),
                "notice" => __("Notice!", "wpnotif"),
        );
        wp_localize_script('wpnotif-frontend', 'wpn_frontend', $data);

        wp_enqueue_script('wpnotif-frontend');
    }

    public function add_countrylist()
    {
        self::display_country_list();
    }

    public static function display_country_list()
    {
        $is_mobile = wp_is_mobile();

        $class = '';
        $stype = 'list';
        if ($is_mobile) {
            $stype = 'mobile';
            $class = 'wpnotif-mobile-list';
        }

        $list = '<ul class="wpnotif-countrylist ' . $class . '" style="display: none;"  data-type="' . $stype . '">';
        $list .= self::get_country_list('', 'li');

        if ($is_mobile)
            $list .= '<li class="spacer" disabled=""></li>';

        $list .= '</ul>';

        if ($is_mobile) {
            $search = '<div class="wpnotif-countrycode-search"><div class="wpnotif-hide-countrycode"></div><input type="text" class="wpnotif_countrycode_search regular-text"></div>';
            $list = '<div class="wpnotif-fullscreen">' . $list . $search . '</div>';
        }


        echo $list;
    }

    public static function get_country_list($selected_country, $wrap = 'option')
    {
        $countryList = self::getCountryList();

        $list = '';
        foreach ($countryList as $key => $value) {
            $key = esc_html($key);
            $value = esc_html($value);
            $sel = '';
            if ($selected_country == $value) {
                $sel = 'selected';
            }
            $list .= '<' . $wrap . ' class="wpnotif-cc-visible" value="' . $value . '" data-country="' . strtolower($key) . '" ' . $sel . '>' . $key . ' (+' . $value . ')</' . $wrap . '>';
        }
        return $list;
    }

    public static function getCountryList()
    {
        return array(
                esc_attr__("Afghanistan", "wpnotif") => "93",
                esc_attr__("Albania", "wpnotif") => "355",
                esc_attr__("Algeria", "wpnotif") => "213",
                esc_attr__("American Samoa", "wpnotif") => "1",
                esc_attr__("Andorra", "wpnotif") => "376",
                esc_attr__("Angola", "wpnotif") => "244",
                esc_attr__("Anguilla", "wpnotif") => "1",
                esc_attr__("Antigua", "wpnotif") => "1",
                esc_attr__("Argentina", "wpnotif") => "54",
                esc_attr__("Armenia", "wpnotif") => "374",
                esc_attr__("Aruba", "wpnotif") => "297",
                esc_attr__("Australia", "wpnotif") => "61",
                esc_attr__("Austria", "wpnotif") => "43",
                esc_attr__("Azerbaijan", "wpnotif") => "994",
                esc_attr__("Bahrain", "wpnotif") => "973",
                esc_attr__("Bangladesh", "wpnotif") => "880",
                esc_attr__("Barbados", "wpnotif") => "1",
                esc_attr__("Belarus", "wpnotif") => "375",
                esc_attr__("Belgium", "wpnotif") => "32",
                esc_attr__("Belize", "wpnotif") => "501",
                esc_attr__("Benin", "wpnotif") => "229",
                esc_attr__("Bermuda", "wpnotif") => "1",
                esc_attr__("Bhutan", "wpnotif") => "975",
                esc_attr__("Bolivia", "wpnotif") => "591",
                esc_attr__("Bonaire, Sint Eustatius and Saba", "wpnotif") => "599",
                esc_attr__("Bosnia and Herzegovina", "wpnotif") => "387",
                esc_attr__("Botswana", "wpnotif") => "267",
                esc_attr__("Brazil", "wpnotif") => "55",
                esc_attr__("British Indian Ocean Territory", "wpnotif") => "246",
                esc_attr__("British Virgin Islands", "wpnotif") => "1",
                esc_attr__("Brunei", "wpnotif") => "673",
                esc_attr__("Bulgaria", "wpnotif") => "359",
                esc_attr__("Burkina Faso", "wpnotif") => "226",
                esc_attr__("Burundi", "wpnotif") => "257",
                esc_attr__("Cambodia", "wpnotif") => "855",
                esc_attr__("Cameroon", "wpnotif") => "237",
                esc_attr__("Canada", "wpnotif") => "1",
                esc_attr__("Cape Verde", "wpnotif") => "238",
                esc_attr__("Cayman Islands", "wpnotif") => "1",
                esc_attr__("Central African Republic", "wpnotif") => "236",
                esc_attr__("Chad", "wpnotif") => "235",
                esc_attr__("Chile", "wpnotif") => "56",
                esc_attr__("China", "wpnotif") => "86",
                esc_attr__("Colombia", "wpnotif") => "57",
                esc_attr__("Comoros", "wpnotif") => "269",
                esc_attr__("Cook Islands", "wpnotif") => "682",
                esc_attr__("Costa Rica", "wpnotif") => "506",
                esc_attr__("Côte d'Ivoire", "wpnotif") => "225",
                esc_attr__("Croatia", "wpnotif") => "385",
                esc_attr__("Cuba", "wpnotif") => "53",
                esc_attr__("Curaçao", "wpnotif") => "599",
                esc_attr__("Cyprus", "wpnotif") => "357",
                esc_attr__("Czech Republic", "wpnotif") => "420",
                esc_attr__("Democratic Republic of the Congo", "wpnotif") => "243",
                esc_attr__("Denmark", "wpnotif") => "45",
                esc_attr__("Djibouti", "wpnotif") => "253",
                esc_attr__("Dominica", "wpnotif") => "1",
                esc_attr__("Dominican Republic", "wpnotif") => "1",
                esc_attr__("Ecuador", "wpnotif") => "593",
                esc_attr__("Egypt", "wpnotif") => "20",
                esc_attr__("El Salvador", "wpnotif") => "503",
                esc_attr__("Equatorial Guinea", "wpnotif") => "240",
                esc_attr__("Eritrea", "wpnotif") => "291",
                esc_attr__("Estonia", "wpnotif") => "372",
                esc_attr__("Ethiopia", "wpnotif") => "251",
                esc_attr__("Falkland Islands", "wpnotif") => "500",
                esc_attr__("Faroe Islands", "wpnotif") => "298",
                esc_attr__("Federated States of Micronesia", "wpnotif") => "691",
                esc_attr__("Fiji", "wpnotif") => "679",
                esc_attr__("Finland", "wpnotif") => "358",
                esc_attr__("France", "wpnotif") => "33",
                esc_attr__("French Guiana", "wpnotif") => "594",
                esc_attr__("French Polynesia", "wpnotif") => "689",
                esc_attr__("Gabon", "wpnotif") => "241",
                esc_attr__("Georgia", "wpnotif") => "995",
                esc_attr__("Germany", "wpnotif") => "49",
                esc_attr__("Ghana", "wpnotif") => "233",
                esc_attr__("Gibraltar", "wpnotif") => "350",
                esc_attr__("Greece", "wpnotif") => "30",
                esc_attr__("Greenland", "wpnotif") => "299",
                esc_attr__("Grenada", "wpnotif") => "1",
                esc_attr__("Guadeloupe", "wpnotif") => "590",
                esc_attr__("Guam", "wpnotif") => "1",
                esc_attr__("Guatemala", "wpnotif") => "502",
                esc_attr__("Guernsey", "wpnotif") => "44",
                esc_attr__("Guinea", "wpnotif") => "224",
                esc_attr__("Guinea-Bissau", "wpnotif") => "245",
                esc_attr__("Guyana", "wpnotif") => "592",
                esc_attr__("Haiti", "wpnotif") => "509",
                esc_attr__("Honduras", "wpnotif") => "504",
                esc_attr__("Hong Kong", "wpnotif") => "852",
                esc_attr__("Hungary", "wpnotif") => "36",
                esc_attr__("Iceland", "wpnotif") => "354",
                esc_attr__("India", "wpnotif") => "91",
                esc_attr__("Indonesia", "wpnotif") => "62",
                esc_attr__("Iran", "wpnotif") => "98",
                esc_attr__("Iraq", "wpnotif") => "964",
                esc_attr__("Ireland", "wpnotif") => "353",
                esc_attr__("Isle Of Man", "wpnotif") => "44",
                esc_attr__("Israel", "wpnotif") => "972",
                esc_attr__("Italy", "wpnotif") => "39",
                esc_attr__("Jamaica", "wpnotif") => "1",
                esc_attr__("Japan", "wpnotif") => "81",
                esc_attr__("Jersey", "wpnotif") => "44",
                esc_attr__("Jordan", "wpnotif") => "962",
                esc_attr__("Kazakhstan", "wpnotif") => "7",
                esc_attr__("Kenya", "wpnotif") => "254",
                esc_attr__("Kiribati", "wpnotif") => "686",
                esc_attr__("Kuwait", "wpnotif") => "965",
                esc_attr__("Kyrgyzstan", "wpnotif") => "996",
                esc_attr__("Laos", "wpnotif") => "856",
                esc_attr__("Latvia", "wpnotif") => "371",
                esc_attr__("Lebanon", "wpnotif") => "961",
                esc_attr__("Lesotho", "wpnotif") => "266",
                esc_attr__("Liberia", "wpnotif") => "231",
                esc_attr__("Libya", "wpnotif") => "218",
                esc_attr__("Liechtenstein", "wpnotif") => "423",
                esc_attr__("Lithuania", "wpnotif") => "370",
                esc_attr__("Luxembourg", "wpnotif") => "352",
                esc_attr__("Macau", "wpnotif") => "853",
                esc_attr__("Macedonia", "wpnotif") => "389",
                esc_attr__("Madagascar", "wpnotif") => "261",
                esc_attr__("Malawi", "wpnotif") => "265",
                esc_attr__("Malaysia", "wpnotif") => "60",
                esc_attr__("Maldives", "wpnotif") => "960",
                esc_attr__("Mali", "wpnotif") => "223",
                esc_attr__("Malta", "wpnotif") => "356",
                esc_attr__("Marshall Islands", "wpnotif") => "692",
                esc_attr__("Martinique", "wpnotif") => "596",
                esc_attr__("Mauritania", "wpnotif") => "222",
                esc_attr__("Mauritius", "wpnotif") => "230",
                esc_attr__("Mayotte", "wpnotif") => "262",
                esc_attr__("Mexico", "wpnotif") => "52",
                esc_attr__("Moldova", "wpnotif") => "373",
                esc_attr__("Monaco", "wpnotif") => "377",
                esc_attr__("Mongolia", "wpnotif") => "976",
                esc_attr__("Montenegro", "wpnotif") => "382",
                esc_attr__("Montserrat", "wpnotif") => "1",
                esc_attr__("Morocco", "wpnotif") => "212",
                esc_attr__("Mozambique", "wpnotif") => "258",
                esc_attr__("Myanmar", "wpnotif") => "95",
                esc_attr__("Namibia", "wpnotif") => "264",
                esc_attr__("Nauru", "wpnotif") => "674",
                esc_attr__("Nepal", "wpnotif") => "977",
                esc_attr__("Netherlands", "wpnotif") => "31",
                esc_attr__("New Caledonia", "wpnotif") => "687",
                esc_attr__("New Zealand", "wpnotif") => "64",
                esc_attr__("Nicaragua", "wpnotif") => "505",
                esc_attr__("Niger", "wpnotif") => "227",
                esc_attr__("Nigeria", "wpnotif") => "234",
                esc_attr__("Niue", "wpnotif") => "683",
                esc_attr__("Norfolk Island", "wpnotif") => "672",
                esc_attr__("North Korea", "wpnotif") => "850",
                esc_attr__("Northern Mariana Islands", "wpnotif") => "1",
                esc_attr__("Norway", "wpnotif") => "47",
                esc_attr__("Oman", "wpnotif") => "968",
                esc_attr__("Pakistan", "wpnotif") => "92",
                esc_attr__("Palau", "wpnotif") => "680",
                esc_attr__("Palestine", "wpnotif") => "970",
                esc_attr__("Panama", "wpnotif") => "507",
                esc_attr__("Papua New Guinea", "wpnotif") => "675",
                esc_attr__("Paraguay", "wpnotif") => "595",
                esc_attr__("Peru", "wpnotif") => "51",
                esc_attr__("Philippines", "wpnotif") => "63",
                esc_attr__("Poland", "wpnotif") => "48",
                esc_attr__("Portugal", "wpnotif") => "351",
                esc_attr__("Puerto Rico", "wpnotif") => "1",
                esc_attr__("Qatar", "wpnotif") => "974",
                esc_attr__("Republic of the Congo", "wpnotif") => "242",
                esc_attr__("Romania", "wpnotif") => "40",
                esc_attr__("Runion", "wpnotif") => "262",
                esc_attr__("Russia", "wpnotif") => "7",
                esc_attr__("Rwanda", "wpnotif") => "250",
                esc_attr__("Saint Helena", "wpnotif") => "290",
                esc_attr__("Saint Kitts and Nevis", "wpnotif") => "1",
                esc_attr__("Saint Pierre and Miquelon", "wpnotif") => "508",
                esc_attr__("Saint Vincent and the Grenadines", "wpnotif") => "1",
                esc_attr__("Samoa", "wpnotif") => "685",
                esc_attr__("San Marino", "wpnotif") => "378",
                esc_attr__("Sao Tome and Principe", "wpnotif") => "239",
                esc_attr__("Saudi Arabia", "wpnotif") => "966",
                esc_attr__("Senegal", "wpnotif") => "221",
                esc_attr__("Serbia", "wpnotif") => "381",
                esc_attr__("Seychelles", "wpnotif") => "248",
                esc_attr__("Sierra Leone", "wpnotif") => "232",
                esc_attr__("Singapore", "wpnotif") => "65",
                esc_attr__("Sint Maarten", "wpnotif") => "1",
                esc_attr__("Slovakia", "wpnotif") => "421",
                esc_attr__("Slovenia", "wpnotif") => "386",
                esc_attr__("Solomon Islands", "wpnotif") => "677",
                esc_attr__("Somalia", "wpnotif") => "252",
                esc_attr__("South Africa", "wpnotif") => "27",
                esc_attr__("South Korea", "wpnotif") => "82",
                esc_attr__("South Sudan", "wpnotif") => "211",
                esc_attr__("Spain", "wpnotif") => "34",
                esc_attr__("Sri Lanka", "wpnotif") => "94",
                esc_attr__("St. Lucia", "wpnotif") => "1",
                esc_attr__("Sudan", "wpnotif") => "249",
                esc_attr__("Suriname", "wpnotif") => "597",
                esc_attr__("Swaziland", "wpnotif") => "268",
                esc_attr__("Sweden", "wpnotif") => "46",
                esc_attr__("Switzerland", "wpnotif") => "41",
                esc_attr__("Syria", "wpnotif") => "963",
                esc_attr__("Taiwan", "wpnotif") => "886",
                esc_attr__("Tajikistan", "wpnotif") => "992",
                esc_attr__("Tanzania", "wpnotif") => "255",
                esc_attr__("Thailand", "wpnotif") => "66",
                esc_attr__("The Bahamas", "wpnotif") => "1",
                esc_attr__("The Gambia", "wpnotif") => "220",
                esc_attr__("Timor-Leste", "wpnotif") => "670",
                esc_attr__("Togo", "wpnotif") => "228",
                esc_attr__("Tokelau", "wpnotif") => "690",
                esc_attr__("Tonga", "wpnotif") => "676",
                esc_attr__("Trinidad and Tobago", "wpnotif") => "1",
                esc_attr__("Tunisia", "wpnotif") => "216",
                esc_attr__("Turkey", "wpnotif") => "90",
                esc_attr__("Turkmenistan", "wpnotif") => "993",
                esc_attr__("Turks and Caicos Islands", "wpnotif") => "1",
                esc_attr__("Tuvalu", "wpnotif") => "688",
                esc_attr__("U.S. Virgin Islands", "wpnotif") => "1",
                esc_attr__("Uganda", "wpnotif") => "256",
                esc_attr__("Ukraine", "wpnotif") => "380",
                esc_attr__("United Arab Emirates", "wpnotif") => "971",
                esc_attr__("United Kingdom", "wpnotif") => "44",
                esc_attr__("United States", "wpnotif") => "1",
                esc_attr__("Uruguay", "wpnotif") => "598",
                esc_attr__("Uzbekistan", "wpnotif") => "998",
                esc_attr__("Vanuatu", "wpnotif") => "678",
                esc_attr__("Venezuela", "wpnotif") => "58",
                esc_attr__("Vietnam", "wpnotif") => "84",
                esc_attr__("Wallis and Futuna", "wpnotif") => "681",
                esc_attr__("Western Sahara", "wpnotif") => "212",
                esc_attr__("Yemen", "wpnotif") => "967",
                esc_attr__("Zambia", "wpnotif") => "260",
                esc_attr__("Zimbabwe", "wpnotif") => "263"
        );

    }

    public function plugin_menu()
    {

        $capabilities = 'manage_options';

        $m = add_menu_page(
                'WPNotif',
                'WPNotif',
                $capabilities,
                'wpnotif-settings',
                array(&$this, 'plugin_settings_ui'),
                '',
                67
        );


        add_submenu_page(
                'wpnotif-settings',
                'WPNotif',
                __('Settings', 'wpnotif'),
                $capabilities,
                'wpnotif-settings'
        );


        do_action('wpnotif_menu');

    }

    public static function wpnotif_show_notice($notice, $links, $dismissible)
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wpnotif_admin_notice notice" style="display: flex;">
            <div class="wpnotif_admin_notice_logo">
                <img src="<?php echo self::get_logo_uri(); ?>"/>
            </div>
            <div class="wpnotif_admin_notice_separator"></div>
            <div class="wpnotif_admin_notice_text">
                <?php echo esc_attr($notice); ?>
            </div>
            <div class="wpnotif_admin_notice_buttons">
                <?php

                if (!empty($dismissible)) {
                    ?>
                    <form method="post">
                        <button class="wpnotif_admin_notice_dismiss" type="submit">
                            <?php echo esc_attr('Dismiss', 'digits'); ?>
                        </button>
                        <input type="hidden" name="<?php echo esc_attr($dismissible); ?>"/>
                    </form>
                    <?php
                }


                foreach ($links as $link) {
                    $name = $link['label'];
                    $url = $link['url'];
                    $link_target = !empty($link['target']) ? 'target="' . $link['target'] . '"' : '_self';
                    $class = !empty($link['class']) ? $link['class'] : '';
                    ?>
                    <a class="wpnotif_admin_notice_button <?php echo $class; ?>"
                            <?php echo $link_target; ?> href="<?php echo esc_attr($url); ?>">
                        <?php echo esc_attr($name); ?>
                    </a>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php
    }

    public function plugin_settings_ui()
    {

        wpnotif_update_db();

        if (!empty($_REQUEST['view']) && $_REQUEST['view'] == 'message-logs') {
            wpnotif_settings_message_logs();
            return;
        }


        $active_tab = 'dashboard';


        if (isset($_GET['tab'])) {
            $active_tab = !empty($_GET['tab']) ? sanitize_text_field($_GET['tab']) : $active_tab;
        }

        $plugin_data = get_plugin_data(__FILE__);

        $code = get_option('wpnotif_purchasecode');

        $request_link = admin_url('admin.php?page=wpnotif-settings&tab=activate');
        $purchase_link = 'https://1.envato.market/G4eZm';

        if (empty($code)) {
            $notice_links = [
                    ['label' => __('Register', 'wpnotif'), 'url' => $request_link, 'class' => 'wpnotif_show_purchasecode'],
                    ['label' => __('Buy Now', 'wpnotif'), 'url' => $purchase_link, 'target' => '_blank'],
            ];
            $notice_text = __('You are using trial version of WPNotif, please register the plugin using license key or else purchase a license to use the plugin with all of its features.', 'digits');
            self::wpnotif_show_notice($notice_text, $notice_links, false);
        }


        ?>
        <div class="wpnotif_admin_fields unovr_activation_form">
            <div class="wpnotif_admin_conf untdovr_admim_conf">
                <div class="wpnotif_preview_overlay">
                    <div class="wpnotif-preview_wrapper">
                        <img src="" draggable="false"/>
                    </div>
                </div>

                <div class="wpnotif_load_overlay_gs untdovr_load_overlay_gs">
                    <div class="wpnotif_load_content untdovr_load_content">

                        <div class="circle-loader">
                            <div class="checkmark draw"></div>
                        </div>
                    </div>
                </div>

                <div class="wpnotif_log_setge unovr_log_setge">

                    <div class="untdovr_admin_mobile_header">
                        <div class="untdovr_admin_mobile_header_wrapper">
                            <div class="untdovr_flex_1">
                                <div class="untdovr-admin_logo">
                                                    <span class="unovr-display_inline">
                                                        <?php WPNotif::admin_header_logo(); ?>
                                                    </span>
                                </div>
                            </div>


                            <div class="wpnotif_admin_mobile_menu untdovr_admin_mobile_menu">
                                <div class="wpnotif_mobile_menu1"></div>
                                <div class="wpnotif_mobile_menu2"></div>
                                <div class="wpnotif_mobile_menu3"></div>
                            </div>
                        </div>
                    </div>

                    <div class="wpnotif_admin_left_side unovr_admin_left_side">
                        <div class="wpnotif_admin_left_side_content unovr_admin_left_side_content">
                            <div class="wpnotif_sts_logo unovr_sts_logo">

                                <div class="untdovr-admin_logo untdovr-hide_on_full">
                                                    <span class="unovr-display_inline">
                                                        <?php WPNotif::admin_header_logo(); ?>
                                                    </span>
                                </div>

                                <ul class="wpnotif_gs_log_men untdovr_gs_log_men" style="display:none;">
                                    <li></li>
                                </ul>

                                <input type="hidden" id="wpnotif_activated" value="1">

                                <!-- <div class="wpnotif-tab-and-form-wrapper"> -->
                                <div class="unovr-tab-wrapper">

                                    <div class="unovr-tab-left unovr-tab-left_item unovr-tab_dashboard">
                                        <span class="unovr-tab-dashboard_icon"></span>
                                        <a href="?page=wpnotif-settings&amp;tab=dashboard"
                                           id="wpnotifs_dashboard"
                                           class="updatetabview wpnotif-nav-tab unovr-nav-tab <?php echo $active_tab == 'dashboard' ? 'wpnotif-nav-tab-active' : ''; ?>"
                                           tab="dashboardtab"><?php esc_html_e('Dashboard', 'wpnotif'); ?></a>
                                    </div>


                                    <ul class="wpnotif-tab-ul untdovr-tab-ul untdovr-left_tab_list">
                                        <li><a href="?page=wpnotif-settings&amp;tab=apisettings"
                                               class="updatetabview wpnotif-nav-tab unovr-nav-tab <?php echo $active_tab == 'apisettings' ? 'wpnotif-nav-tab-active' : ''; ?>"
                                               tab="apisettingstab"><?php esc_html_e('Gateway', 'wpnotif'); ?></a>
                                        </li>

                                        <li><a href="?page=wpnotif-settings&amp;tab=notifications"
                                               class="updatetabview wpnotif-nav-tab unovr-nav-tab <?php echo $active_tab == 'notifications' ? 'wpnotif-nav-tab-active' : ''; ?>"
                                               tab="notificationstab"><?php esc_html_e('Notifications', 'wpnotif'); ?></a>
                                        </li>

                                        <li><a href="?page=wpnotif-settings&amp;tab=general"
                                               class="updatetabview wpnotif-nav-tab unovr-nav-tab <?php echo $active_tab == 'general' ? 'wpnotif-nav-tab-active' : ''; ?>"
                                               tab="generaltab"><?php esc_html_e('General', 'wpnotif'); ?></a>
                                        </li>

                                        <li><a href="?page=wpnotif-settings&amp;tab=sendquicksms"
                                               class="updatetabview wpnotif-nav-tab unovr-nav-tab <?php echo $active_tab == 'sendquicksms' ? 'wpnotif-nav-tab-active' : ''; ?>"
                                               tab="sendquicksmstab"><?php esc_html_e('Quick Notification', 'wpnotif'); ?></a>
                                        </li>

                                    </ul>

                                </div>
                            </div>

                            <!-- </div> -->

                            <div class="untdovr-settings_body">
                                <div id="wpnotif_setting_form_div" class="unovr_settings_Form">
                                    <form method="post" autocomplete="off" id="wpnotif_setting_update"
                                          class="wpnotif_activation_form" enctype="multipart/form-data">
                                        <div data-tab="dashboardtab"
                                             class="wpnotif_admin_in_pt unovr_admin_in_pt dashboardtab digtabview <?php echo $active_tab == 'dashboard' ? 'digcurrentactive' : '" style="display:none;'; ?>">
                                            <?php $this->wpnotif_dashboard(); ?>
                                        </div>

                                        <div class="unovr_admin_tab_bg">
                                            <div data-tab="apisettingstab"
                                                 class="wpnotif_admin_in_pt unovr_admin_in_pt apisettingstab digtabview <?php echo $active_tab == 'apisettings' ? 'digcurrentactive' : '" style="display:none;'; ?>">
                                                <div class="unovr_admin_head">
                                                    <div class="unovr-admin_tab_grid_elem">
                                                        <span><?php esc_html_e('Gateway', 'wpnotif'); ?></span>
                                                    </div>
                                                </div>

                                                <div class="unovr_admin_tab_grid">
                                                    <div class="unovr-admin_tab_grid_elem">
                                                        <?php $this->skeleton_loader(); ?>
                                                        <?php $this->wpnotif_api_settings(); ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div data-tab="notificationstab"
                                                 class="wpnotif_admin_in_pt unovr_admin_in_pt notificationstab digtabview <?php echo $active_tab == 'notifications' ? 'digcurrentactive' : '" style="display:none;'; ?>">
                                                <div class="unovr_admin_head">
                                                    <span><?php esc_html_e('Notifications', 'wpnotif'); ?></span>
                                                </div>
                                                <div class="unovr_admin_tab_grid">
                                                    <div class="unovr-admin_tab_grid_elem">
                                                        <?php $this->notifications_settings(); ?>
                                                    </div>
                                                </div>
                                            </div>


                                            <div data-tab="generaltab"
                                                 class="wpnotif_admin_in_pt unovr_admin_in_pt generaltab digtabview <?php echo $active_tab == 'general' ? 'digcurrentactive' : '" style="display:none;'; ?>">
                                                <div class="unovr_admin_head">
                                                    <span><?php esc_html_e('General', 'wpnotif'); ?></span>
                                                </div>
                                                <div class="unovr_admin_tab_grid">
                                                    <div class="unovr-admin_tab_grid_elem">
                                                        <?php $this->general_settings(); ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div data-tab="sendquicksmstab"
                                                 class="wpnotif_admin_in_pt unovr_admin_in_pt sendquicksmstab digtabview <?php echo $active_tab == 'sendquicksms' ? 'digcurrentactive' : '" style="display:none;'; ?>">
                                                <div class="unovr_admin_head">
                                                    <span><?php esc_html_e('Quick Notification', 'wpnotif'); ?></span>
                                                </div>
                                                <div class="unovr_admin_tab_grid">
                                                    <div class="unovr-admin_tab_grid_elem">
                                                        <?php $this->send_quick_sms_ui(false, 0); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit"
                                                class="wpnotif_admin_submit untdovr_admin_submit wpnotif_admin_submit_floating untdovr_admin_submit_floating"
                                                disabled=""><?php esc_html_e('Save Changes', 'wpnotif'); ?></button>
                                    </form>
                                </div>
                            </div>

                        </div>
                    </div>


                    <div class="wpnotif_admin_side">


                    </div>

                </div>
            </div>
        </div>
        <?php $this->unitedover_logo(); ?>

        <?php
        self::WPNotif_loading();
    }

    public static function admin_header_logo($show_update = true)
    {
        $plugin_updates = get_plugin_updates();
        $text = esc_html(self::get_version());
        $slug = 'wpnotif';
        $base_name = self::get_basename();

        if (isset($plugin_updates[$base_name]) && $show_update) {
            $link = wp_nonce_url(
                    add_query_arg(
                            array(
                                    'puc_check_for_updates' => 1,
                                    'puc_slug' => $slug,
                            ),
                            self_admin_url('plugins.php')
                    ),
                    'puc_check_for_updates'
            );

            $text .= ' <a href="' . $link . '" class="untdover_plugin_update" data-slug="' . $slug . '">' . __('(Update Available)', 'wpnotif') . '</a>';
        }
        ?>
        <a href="https://wpnotif.unitedover.com/" target="_blank">
            <img src="<?php echo esc_attr(self::get_logo_uri()); ?>" alt=""
                 class="untdovr_admin_logo"/></h1>
            <span class="wpnotif_plugin_version untdovr_plugin_version adjust-wpnotif-plugin_version"><?php echo $text; ?></span>
        </a>
        <?php
    }

    public static function get_logo_uri()
    {
        return WPNotif::get_dir('assets/images/WPNotif.svg');
    }

    public static function get_basename()
    {
        return plugin_basename(__FILE__);
    }

    public static function get_dir($path)
    {
        return plugins_url($path, __FILE__);
    }

    public function wpnotif_dashboard()
    {
        $code = get_option('wpnotif_purchasecode');
        $force = empty($code);
        $class = $force ? 'unovr_settings_blur' : '';

        $log_url = admin_url("admin.php?page=wpnotif-settings&view=message-logs")

        ?>
        <div id="wpnotif_dashboard_view" class="unovr_admin_dashboard_wrapper <?php echo esc_attr($class); ?>">
            <div class="wpnotif_gs_log_men wpnotif_gs_log_men_btns_wrapper unovr_admin_dashboard_row">
                <div class="wpnotif_gs_log_men_btns unovr_admin_dashboard_view_btn">
                    <a href="<?php echo esc_attr($log_url); ?>">
                        <div class="unovr_admin_dashboard_button">
                            <span class="unovr_admin_dashboard_ic unovr_admin_dashboard_log"></span>
                            <?php esc_attr_e('View Logs', 'wpnotif'); ?>
                        </div>
                    </a>
                </div>
                <div style="flex:1">
                </div>
            </div>


            <div class="wpnotif_gs_log_men wpnotif_gs_log_men_btns_wrapper unovr_admin_dashboard_row">
                <div class="untdovr_admin_dashboard_graph unovr_admin_dashboard_graph">
                    <div id="wpnotif_dashboard_graph_daily_notifications"
                         class="untdovr_admin_dashboard_graph_details unovr_admin_dashboard_graph_details">
                        <div class="untdovr_admin_dashboard_graph_total unovr_admin_dashboard_graph_total">
                            <div class="untdovr_admin_dashboard_graph_total_value unovr_admin_dashboard_graph_total_value">
                                -
                            </div>
                            <div class="untdovr_admin_dashboard_graph_total_text unovr_admin_dashboard_graph_total_text">
                                <?php esc_attr_e('Daily Messages', 'digits'); ?>
                            </div>
                        </div>
                        <div class="untdovr_admin_dashboard_graph_duration unovr_admin_dashboard_graph_duration">
                            <div class="untdovr_admin_dashboard_graph_duration_text unovr_admin_dashboard_graph_duration_text">
                                <?php esc_attr_e('Last 6 Months', 'digits'); ?>
                            </div>
                        </div>
                    </div>
                    <div id="wpnotif_dashboard_graph_daily_stats">

                    </div>
                </div>

                <div class="unovr_admin_dashboard_sm_section">
                    <div class="unovr_admin_dashboard_buttons unovr_admin_dashboard_buttons_row1">
                        <a href="https://wpnotif.unitedover.com/changelog/" target="_blank">
                            <div class="unovr_admin_dashboard_button">
                                            <span
                                                    class="unovr_admin_dashboard_ic unovr_admin_dashboard_changelog unovr_admin_dashboard_changelog"></span>
                                <?php esc_attr_e('Changelog', 'wpnotif'); ?>
                            </div>
                        </a>
                        <a href="https://help.unitedover.com/?utm_source=wpnotif-wp-settings&utm_medium=kb-button"
                           target="_blank">
                            <div class="unovr_admin_dashboard_button">
                                <span class="unovr_admin_dashboard_ic unovr_admin_dashboard_doc"></span>
                                <?php esc_attr_e('Documentation', 'wpnotif'); ?>
                            </div>
                        </a>
                    </div>
                    <div class="unovr_admin_dashboard_buttons unovr_admin_dashboard_button_row2">
                        <div class="unovr_admin_dashboard_button wpnotif_show_purchasecode">
                            <span class="unovr_admin_dashboard_ic unovr_admin_dashboard_purchase_code"></span>
                            <?php esc_html_e('License Key', 'wpnotif'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="unovr_admin_dashboard_row">
                <div class="unovr_admin_dashboard_sm_section unovr_admin_stats_row">
                    <div class="unovr_admin_dashboard_buttons unovr_admin_dashboard_stats_row1">
                        <div class="unovr_admin_stat_no total_notifications_served">-</div>
                        <div class="unovr_admin_stat_desc">
                            <?php echo __('Notifications Served', 'wpnotif'); ?>
                        </div>
                    </div>
                    <div class="unovr_admin_dashboard_buttons unovr_admin_dashboard_stats_row2">
                        <div class="unovr_admin_stat_no total_sms_served">-</div>
                        <div class="unovr_admin_stat_desc">
                            <?php echo __('SMS Served', 'wpnotif'); ?>
                        </div>
                    </div>
                </div>

                <div class="untdovr_admin_dashboard_graph unovr_admin_dashboard_graph">
                    <div id="wpnotif_dashboard_graph_montly_notifications_stats"
                         class="untdovr_admin_dashboard_graph_details unovr_admin_dashboard_graph_details">
                        <div class="untdovr_admin_dashboard_graph_total unovr_admin_dashboard_graph_total">
                            <div class="untdovr_admin_dashboard_graph_total_value unovr_admin_dashboard_graph_total_value">
                                -
                            </div>
                            <div class="untdovr_admin_dashboard_graph_total_text unovr_admin_dashboard_graph_total_text">
                                <?php esc_attr_e('Monthly Messages', 'digits'); ?>
                            </div>
                        </div>
                        <div class="untdovr_admin_dashboard_graph_duration unovr_admin_dashboard_graph_duration">
                            <div class="untdovr_admin_dashboard_graph_duration_text unovr_admin_dashboard_graph_duration_text">
                                <?php esc_attr_e('Last 12 Months', 'digits'); ?>
                            </div>
                        </div>
                    </div>
                    <div id="wpnotif_dashboard_graph_montly_notifications">

                    </div>
                </div>
            </div>
        </div>
        <?php
        $this->activation($code, $force);
    }

    function activation($code, $force)
    {
        $class = $force ? 'untdovr-admin_activation_show' : '';

        $license_type = get_option('wpnotif_license_type', 1);

        $plugin_version = $this->get_version();


        wp_nonce_field('wpnotif_settings_save');
        ?>


        <div id="wpnotif-admin_activation" class="unovr_overlay_modal_content <?php echo esc_attr($class); ?>" style="">
            <div class="untdovr-admin_activation_modal_container">
                <div class="untdovr-admin_activation_modal_back_drop"></div>
                <div class="untdovr-admin_activation_modal">
                    <div class="untdovr-admin_activation_modal_wrapper">

                        <div class="untdovr-admin_activation_modal_head"> Activate Plugin</div>
                        <div class="untdovr-admin_activation_modal_body">
                            <div class="untdovr-activation_wrapper">

                                <input type="hidden" name="wpnotif_license_type"
                                       value="<?php echo esc_html(get_option('wpnotif_license_type', 1)); ?>"/>

                                <input type="hidden" name="wpnotif_domain"
                                       value="<?php echo esc_html(wpn_network_home_url()); ?>"/>

                                <input type="hidden" name="wpnotif_version"
                                       value="<?php echo esc_html($plugin_version); ?>"/>

                                <table class="form-table">
                                    <div class="wpnotif_domain_type" <?php if (!empty($code)) {
                                        echo 'style="display:none;"';
                                    } ?>>
                                        <div scope="row unovr_domain_type">
                                            <label for="wpnotif_purchasecode"><?php esc_attr_e("Type of Site?", "wpnotif"); ?>
                                            </label>
                                        </div>
                                        <div class="unovr_domain_type_btn_wrapper">
                                            <button class="button" type="button"
                                                    val="1"><?php esc_attr_e('Production Site', 'wpnotif'); ?></button>
                                            <button class="button" type="button"
                                                    val="2"><?php esc_attr_e('Testing Site', 'wpnotif'); ?></button>
                                        </div>
                                    </div>

                                    <tr class="wpnotif_prchcde" <?php if (!empty($code)) {
                                        echo 'style="display:table-row;"';
                                    } ?>>

                                        <td>
                                            <div scope="row">
                                                <label
                                                        for="wpnotif_purchasecode"><?php esc_attr_e("License Key", "wpnotif"); ?>
                                                </label><br>
                                            </div>
                                            <div class="wpnotif_purchase_code_inp">
                                                <div
                                                        class="untdovr_shortcode_tbs untdovr-shortcode_stb wpnotif_shortcode_tbs wpnotif_shortcode_stb">
                                                    <input class="wpnotif_inp_wid31" nocop="1" type="text"
                                                           name="wpnotif_purchasecode" id="wpnotif_purchasecode"
                                                           placeholder="<?php esc_attr_e("License Key", "wpnotif"); ?>"
                                                           autocomplete="off" value="<?php echo esc_attr($code); ?>"
                                                           readonly>
                                                    <div class="wpnotif_btn_unregister_and_icon_wrapper">
                                                        <button class="button wpnotif_btn_unregister"
                                                                type="button"><?php _e('DEREGISTER', 'wpnotif'); ?></button>
                                                        <img class="wpnotif_prc_ver"
                                                             src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/check_animated.svg'; ?>"
                                                             draggable="false" <?php if (!empty($code)) {
                                                            echo 'style="display:block;"';
                                                        } ?>>
                                                        <img class="wpnotif_prc_nover"
                                                             src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/cross_animated.svg'; ?>"
                                                             draggable="false">
                                                    </div>
                                                </div>

                                                <div class="wpnotif_purchase_code_submit_wrapper">
                                                    <button type="submit"
                                                            class="wpnotif_admin_submit untdovr_admin_submit"
                                                            disabled=""><?php esc_html_e('Save', 'wpnotif'); ?>
                                                    </button>
                                                </div>

                                            </div>
                                        </td>

                                    </tr>
                                </table>

                                <div class="_sep_pc wpnotif_prchcde" <?php if (!empty($code)) {
                                    echo 'style="display:block;"';
                                } ?>></div>
                                <!-- <p class="wpnotif_ecr_desc wpnotif_cntr_algn_clr wpnotif_prchcde" <?php if (!empty($code)) {
                                    echo 'style="display:block;"';
                                } ?>>
                                    <?php _e('Please activate your plugin to receive updates', 'wpnotif'); ?>
                                </p> -->


                                <table class="wpnotif_prchcde" <?php if (!empty($code)) {
                                    echo 'style="display:table-row;"';
                                } ?>>
                                    <tr>
                                        <td>
                                            <p class="unovr_ecr_desc unovr_cntr_algn unovr_sme_lft_algn request_live_server_addition wpnotif_ecr_desc wpnotif_cntr_algn wpnotif_sme_lft_algn request_live_server_addition"
                                                    <?php if ($license_type == 1) {
                                                        echo 'style="display:none;"';
                                                    } ?>>
                                                <?php _e('If you want to use the same license key on your production server, you can now manage your license code through our License Manager.', 'wpnotif'); ?>
                                            </p>
                                            <p class="unovr_ecr_desc unovr_cntr_algn unovr_sme_lft_algn request_live_server_addition wpnotif_ecr_desc wpnotif_cntr_algn wpnotif_sme_lft_algn request_testing_server_addition"
                                                    <?php if ($license_type == 2) {
                                                        echo 'style="display:none;"';
                                                    } ?>>
                                                <?php _e('If you want to use the same license key on your testing server, you can now manage your license code through our License Manager.', 'wpnotif'); ?>
                                            </p>


                                            <button href="https://unitedover.com/manage"
                                                    class="button wpnotif_request_server_addition request_live_server_addition"
                                                    type="button" <?php if ($license_type == 1) {
                                                echo 'style="display:none;"';
                                            } ?>>
                                                <div class="unovr_request_label_pillars_left"></div>
                                                <div class="unovr_request_label_pillars_right"></div>
                                                <span class="wpnotif_request_server_addition_default_label a">
                                                                    <?php esc_attr_e('License Manager', 'wpnotif'); ?>
                                                                </span>
                                            </button>
                                            <button href="https://unitedover.com/manage"
                                                    class="button wpnotif_request_server_addition request_testing_server_addition"
                                                    type="button" <?php if ($license_type == 2) {
                                                echo 'style="display:none;"';
                                            } ?>>
                                                <div class="unovr_request_label_pillars_left"></div>
                                                <div class="unovr_request_label_pillars_right"></div>
                                                <span class="wpnotif_request_server_addition_default_label b">
                                                                    <?php esc_attr_e('License Manager', 'wpnotif'); ?>
                                                                </span>
                                            </button>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php

    }

    public function skeleton_loader()
    {
        ?>
        <div id="wpnotif_loading_container" class="untdovr_loading_container">
            <div id="wpnotif_loading_anim">

                <div class="wpn-skeleton-loader unovr-skeleton-loader">
                    <div class="skltn-shine">
                    </div>
                    <div class="skltn-container">
                        <div class="skltn-sec">
                            <div class="skltn-line"></div>
                            <div class="skltn-setting"></div>
                        </div>

                        <div class="skltn-sec skltn-one">
                            <div class="skltn-line"></div>
                            <div class="skltn-setting"></div>
                        </div>

                        <div class="skltn-sec skltn-two">
                            <div class="skltn-line"></div>
                            <div class="skltn-setting"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function wpnotif_api_settings()
    {


        $wpnotif_tapp = get_option('wpnotif_tapp', 2);

        $app = get_option('wpnotif_api');
        $appid = "";
        $appsecret = "";
        $accountkit_type = "";
        if ($app !== false) {
            $appid = $app['appid'];
            $appsecret = $app['appsecret'];
            if (isset($app['accountkit_type'])) {
                $accountkit_type = $app['accountkit_type'];
            } else {
                $accountkit_type = "modal";
            }
        }

        $tiwilioapicred = get_option('wpnotif_twilio_api');
        $twiliosid = "";
        $twiliotoken = "";
        $twiliosenderid = "";


        if ($tiwilioapicred !== false) {
            $twiliosid = $tiwilioapicred['twiliosid'];
            $twiliotoken = $tiwilioapicred['twiliotoken'];
            $twiliosenderid = $tiwilioapicred['twiliosenderid'];
        }


        $msg91apicred = get_option('wpnotif_msg91_api');
        $msg91authkey = "";
        $msg91senderid = "";
        $msg91dlt_te_id = "";

        $msg91route = 1;
        if ($msg91apicred !== false) {
            $msg91authkey = $msg91apicred['msg91authkey'];
            $msg91senderid = $msg91apicred['msg91senderid'];
            $msg91route = $msg91apicred['msg91route'];

            if (isset($msg91apicred['msg91dlt_te_id'])) {
                $msg91dlt_te_id = $msg91apicred['msg91dlt_te_id'];
            }

            if (empty($msg91route)) {
                $msg91route = 4;
            }
        }


        $yunpianapi = get_option('wpnotif_yunpianapi');

        $smsgateways = $this->getGateWayArray();
        ?>


        <input type="hidden" class="wpnotif_save" value='1' name="wpnotif_save"/>
        <div class="wpnotif_gateway_container untdovr_gateway_container">
            <table class="form-table wpnotif_default_gateway_details untdovr_default_gateway_details gateway_table">
                <?php $this->wpnotif_select_gateway('name="wpnotif_tapp" id="wpnotif_tapp"', $wpnotif_tapp); ?>

                <tr class="twiliocred gateway_conf" <?php if ($wpnotif_tapp != 2) {
                    echo 'style="display:none;"';
                } ?>>
                    <th scope="row"><label for="twiliosid"><?php esc_html_e('Account SID', 'wpnotif'); ?> </label>
                    </th>
                    <td>
                        <input type="text" id="twiliosid" name="twiliosid" class="regular-text"
                               value="<?php echo esc_html($twiliosid); ?>"
                               placeholder="<?php esc_html_e('Account SID', 'wpnotif'); ?>" autocomplete="off"/>
                    </td>
                </tr>
                <tr class="twiliocred gateway_conf" <?php if ($wpnotif_tapp != 2) {
                    echo 'style="display:none;"';
                } ?>>
                    <th scope="row"><label for="twiliotoken"><?php esc_html_e('Auth Token', 'wpnotif'); ?> </label>
                    </th>
                    <td>
                        <input type="text" id="twiliotoken" name="twiliotoken" class="regular-text"
                               value="<?php echo esc_html($twiliotoken); ?>" autocomplete="off"
                               placeholder="<?php esc_html_e('Auth Token', 'wpnotif'); ?>"/>
                    </td>
                </tr>
                <tr class="twiliocred gateway_conf" <?php if ($wpnotif_tapp != 2) {
                    echo 'style="display:none;"';
                } ?>>
                    <th scope="row"><label
                                for="twiliosenderid"><?php esc_html_e('Sender ID (From number)', 'wpnotif'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" id="twiliosenderid" name="twiliosenderid" class="regular-text"
                               value="<?php echo esc_html($twiliosenderid); ?>" autocomplete="off"
                               placeholder="<?php esc_html_e('Sender ID', 'wpnotif'); ?>"/>
                    </td>
                </tr>

                <tr class="msg91cred gateway_conf" <?php if ($wpnotif_tapp != 3) {
                    echo 'style="display:none;"';
                } ?>>
                    <th scope="row"><label for="msg91authkey"><?php esc_html_e('Authentication Key', 'wpnotif'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" id="msg91authkey" name="msg91authkey" class="regular-text"
                               value="<?php echo esc_html($msg91authkey); ?>" autocomplete="off"
                               placeholder="<?php esc_html_e('Authentication Key', 'wpnotif'); ?>"/>
                        <input type="hidden" name="msg91route" value="2"/>
                    </td>
                </tr>
                <tr class="msg91cred gateway_conf" <?php if ($wpnotif_tapp != 3) {
                    echo 'style="display:none;"';
                } ?>>
                    <th scope="row"><label for="msg91route"><?php esc_html_e('ROUTE', 'wpnotif'); ?> </label>
                    </th>
                    <td>
                        <select name="msg91route">
                            <option value="4" <?php if ($msg91route == 4)
                                echo 'selected' ?>>
                                <?php esc_html_e('Transactional', 'wpnotif'); ?>
                            </option>
                            <option value="1" <?php if ($msg91route == 1)
                                echo 'selected' ?>>
                                <?php esc_html_e('Promotional', 'wpnotif'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr class="msg91cred gateway_conf" <?php if ($wpnotif_tapp != 3) {
                    echo 'style="display:none;"';
                } ?>>
                    <th scope="row"><label for="msg91senderid"><?php esc_html_e('Sender ID', 'wpnotif'); ?> </label>
                    </th>
                    <td>
                        <input type="text" id="msg91senderid" name="msg91senderid" class="regular-text"
                               value="<?php echo esc_html($msg91senderid); ?>" autocomplete="off"
                               placeholder="<?php esc_html_e('Sender ID', 'wpnotif'); ?>"/>
                        <input type="hidden" id="msg91dlt_te_id" name="msg91dlt_te_id" class="regular-text" value=" "
                               autocomplete="off" placeholder=""/>
                    </td>
                </tr>


                <tr class="yunpiancred gateway_conf" <?php if ($wpnotif_tapp != 4) {
                    echo 'style="display:none;"';
                } ?>>
                    <th scope="row"><label for="yunpianapikey"><?php esc_html_e('API Key', 'wpnotif'); ?> </label>
                    </th>
                    <td>
                        <input type="text" id="yunpianapikey" name="yunpianapikey" class="regular-text"
                               value="<?php echo esc_html($yunpianapi); ?>" autocomplete="off"
                               placeholder="<?php esc_html_e('API Key', 'wpnotif'); ?>"/>
                    </td>
                </tr>

                <?php

                foreach ($smsgateways as $name => $details) {
                    $value = $details['value'];
                    $name = str_replace(array(".", " "), "_", strtolower($name));


                    $gatewayCreds = get_option('wpnotif_' . strtolower($name));

                    foreach ($details['inputs'] as $inputLabel => $input) {
                        $inputname = esc_attr($name . "_" . $input['name']);


                        $inputValue = (isset($gatewayCreds[$input['name']]) && !isset($input['fix_value'])) ? esc_attr($gatewayCreds[$input['name']]) : '';

                        if (empty($inputValue) && isset($input['default_value'])) {
                            $inputValue = esc_attr($input['default_value']);
                        }

                        $optional = 0;
                        if (isset($input['optional'])) {
                            $optional = $input['optional'];
                        }

                        if (isset($input['label'])) {
                            $inputLabel = $input['label'];
                        }

                        $class = '';
                        $hide = false;
                        if (isset($input['show_if'])) {
                            $hide = true;
                            $class = ' wpnotif-hide-elem ' . $input['show_if'];
                        }


                        ?>
                        <tr class="<?php echo esc_html($name); ?>cred gateway_conf <?php echo esc_attr($class); ?>" <?php if ($wpnotif_tapp != $value || $hide) {
                            echo 'style="display:none;"';
                        } ?>>
                            <th scope="row"><label
                                        for="<?php echo $inputname; ?>"> <?php esc_html_e($inputLabel, 'wpnotif');
                                    if ($optional == 1) {
                                        // echo ' (Optional)';
                                    } ?> </label></th>
                            <td>
                                <?php
                                $placeholder = esc_attr__($inputLabel, 'wpnotif');
                                if (isset($input['placeholder'])) {
                                    $placeholder = $input['placeholder'];
                                }

                                if (isset($input['require_additional'])) {
                                    echo '<div class="whatsapp_additional_addon">';
                                    $this->additionalGatewayAddonMessage();
                                    echo '</div>';
                                } else if (isset($input['image'])) {
                                    ?>
                                    <img src="<?php esc_attr_e($input['src']); ?>"
                                         data-src="<?php esc_attr_e($input['preview_src']); ?>"
                                         class="wpnotif_show_image wpnotif_zoom_cursor"
                                         title="<?php echo esc_attr($input['label']); ?>"
                                         draggable="false"/>

                                    <?php
                                } else if (isset($input['textarea'])) {
                                    $rows = isset($input['rows']) ? $input['rows'] : 9;
                                    ?>
                                    <textarea type="text" id="<?php echo $inputname; ?>"
                                              name="<?php echo $inputname; ?>"
                                              class="regular-text" autocomplete="off" rows="<?php echo $rows; ?>"
                                              placeholder="<?php echo $placeholder; ?>"
                                              wpnotif-optional="<?php echo $optional; ?>"><?php echo $inputValue; ?></textarea>
                                    <?php
                                } else if (isset($input['select']) && !isset($input['options'])) {
                                    echo '<select name="' . $inputname . '" class="' . $inputname . '">';

                                    if (isset($input['grouped'])) {

                                        foreach ($input['value'] as $groupKey => $groupValues) {

                                            $optgroup_label = '';

                                            if (!empty($groupValues['label'])) {
                                                $optgroup_label = 'label="' . $groupValues['label'] . '"';
                                            }

                                            echo '<optgroup ' . $optgroup_label . '>';

                                            foreach ($groupValues['values'] as $optionName => $optionValues) {
                                                ?>
                                                <option value="<?php echo $optionValues['value']; ?>" <?php if ($inputValue == $optionValues['value']) {
                                                    echo 'selected';
                                                } ?>><?php echo $optionName; ?>
                                                </option>
                                                <?php
                                            }
                                            echo '</optgroup>';

                                        }
                                    } else {
                                        foreach ($input['value'] as $optionName => $optionValues) {
                                            ?>
                                            <option value="<?php echo $optionValues['value']; ?>" <?php if ($inputValue == $optionValues['value']) {
                                                echo 'selected';
                                            } ?>><?php echo $optionName; ?>
                                            </option>
                                            <?php
                                        }
                                    }
                                    echo '</select>';

                                } else if (isset($input['options'])) {
                                    $options = $input['options'];
                                    ?>
                                    <select id="<?php echo $inputname; ?>" name="<?php echo $inputname; ?>"
                                            wpnotif-optional="<?php echo $optional; ?>">
                                        <?php
                                        foreach ($options as $option => $option_value) {
                                            $option = esc_attr($option);
                                            $sel = '';
                                            if ($option_value == $inputValue)
                                                $sel = 'selected';
                                            echo '<option value="' . $option_value . '" ' . $sel . '>' . $option . '</option>';
                                        }
                                        ?>

                                    </select>
                                    <?php
                                } else if (isset($input['link'])) {

                                    ?>
                                    <a href="<?php echo esc_attr($input['href']); ?>" id="<?php echo $inputname; ?>"
                                       target="_blank"><?php echo $input['link_text']; ?></a>
                                    <?php
                                } else {
                                    $inp_class = '';
                                    if (isset($input['readonly'])) {
                                        $inp_class = ' wpnotif_copy_shortcode';
                                    }

                                    ?>
                                    <input type="text" id="<?php echo $inputname; ?>" name="<?php echo $inputname; ?>"
                                           class="regular-text <?php echo $inp_class; ?>"
                                           value="<?php echo $inputValue; ?>" autocomplete="off"
                                           placeholder="<?php echo $placeholder; ?>" <?php if (isset($input['readonly'])) {
                                        echo 'readonly';
                                    } ?> wpnotif-optional="<?php echo $optional; ?>"/>
                                    <?php
                                }

                                if (isset($input['desc'])) {
                                    echo '<p class="unovr_ecr_desc">';
                                    echo $input['desc'];
                                    echo '</p>';
                                }


                                ?>

                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </table>


            <?php

            $this->wpnotif_test_api_box();
            ?>
        </div>
        <?php
        ?>
        <div class="untdovr_admin_ub_login_flows">
            <?php
            $this->wpnotif_show_digcountrygateways_settings();
            ?>
        </div>
        <?php
        //
    }

    public function wpnotif_select_gateway($gatewayAttributes, $wpnotif_tapp = -1)
    {
        $smsgateways = $this->getGateWayArray();
        $gatewayName = $this->wpnotif_getGatewayName($wpnotif_tapp);

        ?>

        <tr>
            <th scope="row">
                <label><?php esc_html_e('SMS Gateway', 'wpnotif'); ?> </label>
            </th>
            <td class="wpnotif-gs-gatway-select-td">

                <select class="wpnotif_gateway" <?php echo $gatewayAttributes; ?> autocomplete="off">
                    <?php
                    $gateway_groups = apply_filters('wpnotif_group_gateways_list', $smsgateways);
                    foreach ($gateway_groups as $group_name => $gateway_group) {

                        $optgroup_label = 'label="' . esc_attr__($group_name) . '"';
                        if ($group_name == 'starting_group') {
                            $optgroup_label = '';
                        }
                        if ($group_name != 'hide')
                            echo '<optgroup ' . $optgroup_label . '>';

                        foreach ($gateway_group as $name => $details) {
                            $sel = "";
                            $value = $details['value'];

                            if ($value == $wpnotif_tapp) {

                                $gatewayName = $name;
                                $sel = 'selected="selected"';
                            }
                            $name = esc_html($name);
                            $value = esc_html($value);
                            $sel = esc_attr($sel);

                            $gateway_label = isset($details['label']) ? $details['label'] : $name;

                            $require_addon = isset($details['require_addon']) ? $details['require_addon'] : 0;

                            $document_link = isset($details['document_link']) ? $details['document_link'] : 0;

                            echo '<option data-document_link="' . $document_link . '" data-addon="' . $require_addon . '" data-value="' . $value . '" value="' . $value . '" ' . $sel . ' han="' . strtolower(
                                            str_replace(
                                                    array(
                                                            ".",
                                                            " "
                                                    ),
                                                    "_",
                                                    strtolower($name)
                                            )
                                    ) . '">' . $gateway_label . '</option>';
                        }

                        if ($group_name != 'hide')
                            echo '</optgroup>';
                    }
                    ?>
                </select><br/>

                <?php
                $this->additionalGatewayAddonMessage();
                ?>
            </td>
        </tr>
        <?php
    }

    public function wpnotif_getGatewayName($wpnotif_tapp)
    {
        switch ($wpnotif_tapp) {
            case 2:
                return "Twilio";
                break;
            case 3:
                return "Msg91";
                break;
            case 4:
                return "Yunpian";
                break;
            default:
                return '';
                break;
        }
    }

    public function additionalGatewayAddonMessage()
    {
        $list = apply_filters('wpnotif_addon', array());
        $additional_gateway_installed = in_array('additional-gateways', $list);
        if (!$additional_gateway_installed) { ?>
            <p class="unovr_desc require_addon_text wpnotif_require_additional">
                <?php esc_html_e('Please install Additional Gateways addon to use this as gateway'); ?>
                <a href="<?php echo $this->wpnotif_install_additional_link(); ?>"
                   class="wpnotif_install_additional_gateways"><?php esc_html_e('Click Here'); ?></a>
            </p>
            <?php
        }
    }

    public function wpnotif_install_additional_link()
    {
        return admin_url('admin.php?page=wpnotif-settings&tab=apisettings&install_additional=1&nonce=' . wp_create_nonce('install_additional'));
    }

    public function wpnotif_test_api_box()
    {
        $countrycode = esc_attr(get_the_author_meta('digt_countrycode', get_current_user_id()));
        if (empty($countrycode)) {
            $countrycode = self::getDefaultCountryCode();
        }
        ?>
        <div class="wpnotif_api_test">
            <div class="wpnotif_gateway_sep_line untdovr_gateway_sep_line"></div>

            <div class="wpnotif_call_test_api unovr_call_test_api">
                <div class="unovr_admin_sec_head unovr_admin_sec_head_margin">
                    <span><?php esc_html_e('Test Gateway Settings', 'wpnotif'); ?></span>
                </div>


                <div>
                    <table class="form-table form-switch">
                        <tbody>
                        <tr>
                            <th scope="row">
                                <label>
                                    <?php esc_html_e('Message Content', 'wpnotif'); ?>
                                </label>
                            </th>
                            <td>
                                <textarea class="wpnotif_test_message"></textarea>
                                <?php
                                $this->documentationLinkMessage();
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label>
                                    <?php esc_html_e('Phone Number', 'wpnotif'); ?>
                                </label>
                            </th>

                            <td>
                                <div class="unovr_test_number_wrapper">
                                    <div class="digcon">
                                        <div class="unovr_wc_countrycodecontainer_pos_abs unovr_wc_countrycodecontainer"
                                             style="display: inline-block;">
                                            <input dig-save="0" type="text" name="digt_countrycode"
                                                   class="input-text wpnotif_countrycode countrycode unovr_wc_logincountrycode"
                                                   value="<?php echo $countrycode; ?>" maxlength="6" size="3"
                                                   placeholder="<?php echo $countrycode; ?>"
                                                   autocomplete="tel-country-code">
                                        </div>
                                        <input dig-save="0" class="mobile" type="text"
                                               placeholder="<?php _e('Phone Number', 'wpnotif'); ?>"
                                               value="<?php echo esc_attr(get_the_author_meta('wpnotif_phone_no', get_current_user_id())); ?>"
                                               name="mobile/email" style="padding-left:107px">
                                    </div>

                                    <div class="wpnotif_call_test_api_btn unovr_call_test_api_btn">
                                        <?php _e('Send Test', 'wpnotif'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="wpnotif_call_test_response">
                <div class="wpnotif_call_test_response_head"><?php esc_html_e('Response', 'wpnotif'); ?></div>
                <div class="wpnotif_call_test_response_msg"></div>
            </div>
        </div>
        <?php
    }

    public static function getDefaultCountryCode()
    {
        return '+' . WPNotif_Handler::getCountryCode('', true);
    }

    function wpnotif_show_digcountrygateways_settings()
    {

        ?>

        <input type="hidden" name="wpnotif_gateways" class="wpnotif_gateways"
               value='<?php echo esc_attr($this->wpnotif_cgr_get_gateways()); ?>'/>

        <script id="gateway_template" type="text/x-html-template">

            <div class="wpnotif_gateway_box untdovr_admin_ub_login_flow_box active">
                <div class="untdovr_admin_ub_login_flow_head">
                    <div class="untdovr_admin_ub_login_flow_label_wrapper">
                        <div class="wpnotif_gateway_target_head untdovr_admin_ub_login_flow_label">
                            <?php esc_html_e('All Countries', 'wpnotif'); ?>
                        </div>
                    </div>

                    <div class="wpnotif_gateway_box_close"></div>

                    <div class="wpnotif_gateway_collapse_box">
                        <div class="wpnotif_gateway_for_list">
                            <!-- <span class="wpnotif_gate_for"><?php esc_html_e('for', 'wpnotif'); ?></span> -->
                            <span class="wpnotif_ctr_list"></span>
                        </div>
                    </div>

                    <div class="untdovr_admin_ub_login_flow_act">
                        <div class="icon-shape icon-shape-dims wpnotif_gateway_configure_gateway wpnotif_gateay_conf_delete"></div>
                        <!-- <div class="enable_disable_gateway"> -->
                        <div class="input-switch">
                            <input type="checkbox" class="enable_disable_gateway" name="enable_disable_gateway"/>
                            <label for="enable_disable_gateway"></label>
                            <span class="status_text yes unovr_admin_checkbox_yes"></span>
                            <span class="status_text no unovr_admin_checkbox_no"></span>
                        </div>
                        <!-- </div> -->
                        <div class="icon-gear icon-shape-dims icon-gear-dims untdovr_admin_chevron_ic wpnotif_gateway_configure_gateway wpnotif_gateay_conf_expand"></div>
                    </div>
                </div>

                <div class="wpnotif_gateway_configuation_expand_box untdovr_gateway_configuation_expand_box">
                    <div class="wpnotif_gateway_configuation_expand_box_contents">
                        <div class="wpnotif_gateway_sep_line untdovr_gateway_sep_line wpnotif_gateway_sel_sep"></div>

                        <table class="form-table wpnotif-gs-trigger">
                            <?php $this->wpnotif_select_gateway('', -1); ?>
                            <tr class="gateway_countries wpnotif_hide_allc">
                                <th scope="row"><label>
                                        <?php esc_html_e('Countries', 'wpnotif'); ?>
                                    </label></th>
                                <td>
                                    <select multiple="multiple">
                                        <?php
                                        echo $this->get_country_list('');
                                        ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <div class="wpnotif_gateway_sep_line untdovr_gateway_sep_line wpnotif_hide_allc"></div>
                        <table class="form-table selected_gateway_conf"></table>
                        <?php
                        $this->wpnotif_test_api_box();
                        ?>
                    </div>
                </div>
            </div>

        </script>

        <div class="wpnotif_gateway_settings">

        </div>

        <div class="add_gateway_group_button untdovr_admin_add_login_flow"><?php esc_html_e('Add Group', 'wpnotif'); ?>
        </div>
        <?php
    }

    public function wpnotif_cgr_get_gateways()
    {
        $wpnotif_gateways = get_option('wpnotif_gateways', -1);

        if ($wpnotif_gateways == -1 || empty($wpnotif_gateways)) {
            $wpnotif_tapp = get_option('wpnotif_tapp', 2);

            $gateway_array = array();
            $gateway_array['gc_' . $wpnotif_tapp] = array(
                    'gateway' => $wpnotif_tapp,
                    'country' => 'all',
                    'all' => 1,
                    'ccodes' => 0,
                    'enable' => 'on'
            );
            $gateway_array['gc_1001'] = array(
                    'gateway' => 1001,
                    'country' => 'all',
                    'class' => 'hideGatewayList',
                    'data-label' => 'WhatsApp',
                    'all' => 1,
                    'ccodes' => 0,
                    'enable' => 'off'
            );

            return stripslashes(json_encode($gateway_array));
        }


        return stripslashes($wpnotif_gateways);

    }

    public function notifications_settings()
    {

        $vendor_options = $this->get_settings('vendor');

        $customer_options = $this->get_settings('customer');

        $admin_options = $this->get_settings('admin');
        $admin_values = get_option('wpnotif_admin_notifications', $this->admin_default_notifications());

        $admin_roles = get_option('wpnotf_admin_user_role', array('administrator'));

        $user_consent_values = get_option('wpnotif_user_consent', $this->user_consent_default_settings());

        ?>


        <div class="unovr_admin_sec_head">
            <span><?php esc_html_e('User Consent', 'wpnotif'); ?></span>
        </div>
        <?php $this->render_notifications_ui($this->get_user_consent_settings(), $user_consent_values, 'user'); ?>


        <div class="unovr_admin_sec_head unovr_admin_sec_head_margin">
            <span><?php esc_html_e('Admin Notifications', 'wpnotif'); ?></span>
        </div>
        <table class="form-table form-switch">
            <tr>
                <th scope="row"><label><?php esc_html_e('User / User Role', 'wpnotif'); ?></label></th>
                <td>
                    <select name="wpnotf_admin_user_role[]"
                            data-nonce="<?php esc_attr_e(wp_create_nonce('wpnotif_user_list')); ?>"
                            data-source="wpnotif_user_list"
                            class="wpnotif_multiselect_enable" multiple="multiple">
                        <?php

                        if (is_array($admin_roles) && !empty($admin_roles)) {
                            $user_prefix = 'notify_user_';
                            $wp_roles = wp_roles();
                            foreach ($admin_roles as $admin) {
                                if (strpos($admin, $user_prefix) !== 0) {
                                    $wp_role = $wp_roles->get_role($admin);
                                    if (empty($wp_role))
                                        continue;

                                    $id = $admin;
                                    $text = $wp_role->name;
                                } else {
                                    $uid = preg_replace('/[^0-9]/', '', $admin);
                                    $user = get_user_by('ID', $uid);
                                    if (empty($user))
                                        continue;

                                    $id = $user_prefix . $uid;
                                    $text = $user->user_login;
                                }
                                echo '<option value="' . esc_html($id) . '" selected=selected>' . esc_html($text) . '</option>';

                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
        </table>

        <?php $this->render_notifications_ui($admin_options, $admin_values, 'admin'); ?>

        <div class="unovr_admin_sec_head unovr_admin_sec_head_margin">
            <span><?php esc_html_e('Customer Notifications', 'wpnotif'); ?></span>
        </div>

        <table class="form-table form-switch">
            <tr>
                <th scope="row">
                    <label class="top-10"><?php esc_html_e('Enable gateway based different content', 'wpnotif'); ?></label>
                </th>
                <td>
                    <div class="input-switch">
                        <input type="checkbox" id="country_based"
                               name="different_gateway_content" <?php if (get_option('different_gateway_content') == 'on') {
                            echo 'checked';
                        } ?> />
                        <label for="country_based"></label>
                        <span class="status_text yes unovr_admin_checkbox_yes"></span>
                        <span class="status_text no unovr_admin_checkbox_no"></span>
                    </div>
                </td>
            </tr>
        </table>
        <input type="hidden" name="wpnotif_gateway_customer_notifications" id="wpnotif_gateway_customer_notifications"
               value='<?php echo esc_attr(get_option('wpnotif_gateway_customer_notifications')); ?>'/>
        <div class="gateway_based_notifications">

        </div>

        <?php

        echo '<div style="display: none"><div class="user_notifications">';
        $this->render_notifications_ui($customer_options, $this->gateway_based_default_notifications(), 'key');
        echo '</div></div>';

        echo '<div class="simple_user_notifications">';
        $this->render_notifications_ui($customer_options, get_option('wpnotif_customer_notifications', $this->customer_default_notifications()), 'customer');
        echo '</div>';


        if (class_exists('WooCommerce')) {
            ?>
            <div class="unovr_admin_sec_head unovr_admin_sec_head_margin">
                <span><?php esc_html_e('Vendor Notifications (for marketplace)', 'wpnotif'); ?></span>
            </div>
            <?php
            $this->render_notifications_ui($vendor_options, get_option('wpnotif_vendor_notifications'), 'vendor');
        }
        do_action('wpnotif_notifications_settings');
    }

    public function admin_default_notifications()
    {
        $values = array(
                'wc-processing' => array(
                        'enable' => 'on',
                        'msg' => __('You\'ve received a new order for amount {{wc-order-amount}}', 'wpnotif')
                )
        );

        return $values;
    }

    public function user_consent_default_settings()
    {

        $values = array(
                'notify_user' => array(
                        'enable' => 'off',
                        'msg' => __('I would like to receive order updates on SMS', 'wpnotif'),
                ),
                'whatsapp_message' => array(
                        'enable' => 'off',
                        'msg' => __('I would like to receive order updates on WhatsApp', 'wpnotif'),
                ),
                'combine_both' => array(
                        'enable' => 'off',
                        'msg' => __('I would like to receive order updates on SMS and WhatsApp', 'wpnotif'),
                )
        );

        return $values;
    }

    public function render_notifications_ui($admin_options, $admin_values, $suffix)
    {
        $suffix = esc_html($suffix);
        foreach ($admin_options as $key => $values) {
            $checked = '';
            $message = '';
            if (isset($admin_values[$key])) {
                if ($admin_values[$key]['enable'] == 'on') {
                    $checked = 'checked';
                }
                $message = $admin_values[$key]['msg'];
            }
            $key = esc_html($key);
            ?>
            <table class="form-table form-switch">
                <tr>
                    <th scope="row"><label class="top-10"><?php echo esc_html($values['label']); ?></label></th>
                    <td>
                        <div class="input-switch">
                            <input type="checkbox" class="<?php if ($values['message'] == 1) {
                                echo 'notification_toggle';
                            } ?> show_message" name="<?php echo $suffix; ?>_<?php echo $key; ?>"
                                   id="<?php echo $suffix; ?>_<?php echo $key; ?>" <?php echo esc_attr($checked); ?> />
                            <label for="<?php echo $suffix; ?>_<?php echo $key; ?>"></label>
                            <span class="status_text yes unovr_admin_checkbox_yes"></span>
                            <span class="status_text no unovr_admin_checkbox_no"></span>
                        </div>
                        <?php
                        if (isset($values['unovr_desc']) && !function_exists('digits_version')) {
                            $link = 'https://digits.unitedover.com/?utm_source=wpnotif&utm_medium=wpsetting&utm_campaign=loginnotif';
                            ?>
                            <p class=" unovr_desc">
                                <?php echo sprintf($values['unovr_desc'], '<a href="' . $link . '">Digits</a>'); ?>
                            </p>
                            <?php
                        }
                        ?>
                    </td>
                </tr>
            </table>
            <?php
            if ($values['message'] == 1) {
                ?>
                <div class="notification_message">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="<?php echo $suffix; ?>_<?php echo $key; ?>_msg">
                                    <?php
                                    if (isset($values['message_label'])) {
                                        echo esc_html($values['message_label']);
                                    } else {
                                        esc_html_e('Message', 'wpnotif');
                                    }
                                    ?>
                                </label></th>
                            <td>
                                <div class="position-relative">

                                                                    <textarea
                                                                            id="<?php echo $suffix; ?>_<?php echo $key; ?>_msg"
                                                                            name="<?php echo $suffix; ?>_<?php echo $key; ?>_msg"><?php echo esc_html(stripslashes($message)); ?></textarea>

                                    <?php
                                    if (isset($values['placeholder'])) {
                                        if ($values['placeholder'] != 0) {
                                            ?>
                                            <a href="https://help.unitedover.com/wpnotif/kb/placeholders"
                                               target="_blank">
                                                <span class="placeholder_list"><?php esc_html_e('Placeholder List', 'wpnotif'); ?></span>
                                            </a>
                                            <?php
                                        }
                                    }
                                    $this->documentationLinkMessage();
                                    ?>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                <?php
            }
        }

    }

    public static function documentationLinkMessage()
    {
        ?>
        <div class="wpn_gateway_document_link wpn_pointer">
            <p class=" unovr_desc">
                <a href="https://help.unitedover.com/wpnotif/gateway-settings/" target="_blank">
                    Please <span class="wpn_underline">click here</span> to refer to gateway-specific template
                    settings.
                </a>
            </p>
        </div>
        <?php
    }

    public function gateway_based_default_notifications()
    {

        $values = array(
                'wc-processing' => array(
                        'enable' => 'on',
                        'msg' => __('Hey! Your order #{{wc-order}} has been received by {{sitename}}. :)', 'wpnotif')
                )
        );

        return $values;
    }

    public function customer_default_notifications()
    {
        $values = array(
                'wc-processing' => array(
                        'enable' => 'on',
                        'msg' => __('Hey! Your order #{{wc-order}} has been received by {{sitename}}. :)', 'wpnotif')
                )
        );

        return $values;
    }

    public function general_settings()
    {

        $settings = self::plugin_settings(true);

        $phone_verify = get_option('wpnotif_phone_verification', 'off');

        $digits_download_link = 'https://digits.unitedover.com/?utm_source=wpnotif-settings&utm_medium=newsletter-phone-verification&utm_campaign=setting-description';


        $pending_payment_notification_time = get_option('wpnotif_pending_payment_notification_time', 2);
        $abandon_cart_notif = get_option('wpnotif_abandon_cart_notification_time', 2);
        ?>

        <table class="form-table form-switch">
            <tr>
                <th scope="row"><label><?php esc_html_e('Default Country Code', 'wpnotif'); ?></label></th>
                <td>
                    <select name="default_countrycode">
                        <option value="-1"><?php esc_html_e('None', 'wpnotif'); ?></option>
                        <?php
                        echo $this->get_country_list($settings['default_countrycode']);
                        ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row" class="wpnotif-table_label_desc">
                    <label class="top-10"><?php esc_html_e('Phone verification in subscription form', 'wpnotif'); ?></label>
                </th>
                <td>
                    <div class="input-switch">
                        <input type="checkbox" id="wpnotif_phone_verification"
                               name="wpnotif_phone_verification" <?php if ($phone_verify == 'on') {
                            echo 'checked';
                        } ?> />
                        <label for="wpnotif_phone_verification"></label>
                        <span class="status_text yes unovr_admin_checkbox_yes"></span>
                        <span class="status_text no unovr_admin_checkbox_no"></span>
                    </div>
                    <p class=" unovr_desc">
                        <?php echo sprintf(esc_attr__('This feature works better with %sDigits%s. It even comes with free SMS for verification.', 'wpnotif'), '<a href="' . $digits_download_link . '" target="_blank">', '</a>'); ?>
                    </p>
                </td>
            </tr>


            <tr>
                <th scope="row"><label
                            for="pending_payment_notification_time"><?php esc_html_e('Send Pending Order Notification after', 'wpnotif'); ?></label>
                </th>
                <td>
                    <div class="wpnotif_field_floating_text unovr_admin_resend_row unovr_gs_nmb_ovr_spn">
                        <input type="number" name="pending_payment_notification_time"
                               value="<?php echo esc_attr($pending_payment_notification_time); ?>"
                               id="pending_payment_notification_time" max="100" size="3" step="0.2" min="0"/>
                        <span style="left:51px;"><?php esc_attr_e('hour', 'wpnotif'); ?></span>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row"><label
                            for="abandon_cart_notif"><?php esc_html_e('Send abandon cart notification after', 'wpnotif'); ?></label>
                </th>
                <td>
                    <div class="wpnotif_field_floating_text unovr_admin_resend_row unovr_gs_nmb_ovr_spn">
                        <input type="number" name="abandon_cart_notif"
                               value="<?php echo esc_attr($abandon_cart_notif); ?>"
                               id="abandon_cart_notif" size="3" step="1" min="0"/>
                        <span style="left:51px;"><?php esc_attr_e('hour', 'wpnotif'); ?></span>
                    </div>
                </td>
            </tr>


        </table>


        <?php

        WPNotif_NewsLetter::show_settings();


        $usage_sharing = get_option('wpnotif_usage_data_sharing', 1);
        $usage_id = get_option('wpnotif_usage_data_sharing_random_id', false);
        if (!$usage_id) {
            $usage_id = md5(uniqid('wpnotif'));
            update_option('wpnotif_usage_data_sharing_random_id', $usage_id);
        }
        ?>
        <div class="unovr_admin_sec_head unovr_admin_sec_head_margin">
            <span><?php esc_html_e('Miscellaneous', 'wpnotif'); ?></span></div>
        <table class="form-table form-switch">
            <tr>
                <th scope="row"><label><?php esc_html_e('Usage Data Sharing', 'wpnotif'); ?></label></th>
                <td>
                    <div class="input-switch">
                        <input type="checkbox" id="wpnotif_usage_data_sharing"
                               value="1"
                               name="wpnotif_usage_data_sharing" <?php if ($usage_sharing == 1) {
                            echo 'checked';
                        } ?> />
                        <label for="wpnotif_usage_data_sharing"></label>
                        <span class="status_text yes unovr_admin_checkbox_yes"></span>
                        <span class="status_text no unovr_admin_checkbox_no"></span>
                    </div>
                    <input type="hidden" id="random_id" name="random_id" value="<?php echo esc_attr($usage_id); ?>"/>
                    <p class="unovr_desc">
                        <?php echo sprintf(esc_attr__('Help us improve %sWPNotif%s by opting in to share non-sensitive plugin data.', 'wpnotif'), '<b>', '</b>'); ?>
                    </p>
                </td>
            </tr>

        </table>

        <?php
    }

    /*
     * 0 ->for all
     * 1-> admin
     * 2-> user
     * */

    function unitedover_logo()
    {
        ?>
        <div class="a_unitedover_product_container">
            <a href="https://unitedover.com/?utm_source=wpnotif&utm_medium=dashboard&utm_campaign=plugin"
               target="_blank">
                <div class="a_unitedover_product"></div>
            </a>
        </div>
        <?php
    }

    public static function WPNotif_loading()
    {
        if (self::$loader_html) {
            return;
        }

        self::$loader_html = true;
        ?>
        <div class="wpnotif_load_overlay untdovr_load_overlay">
            <div class="wpnotif_load_content">
                <div class="wpnotif_spinner">
                    <div class="wpnotif_double-bounce1"></div>
                    <div class="wpnotif_double-bounce2"></div>
                </div>
            </div>
        </div>
        <?php
    }
}


$wpnotifUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
        'https://bridge.unitedover.com/updates/changelog/updates.php?plugin=wpnotif',
        __FILE__,
        'wpnotif'
);

$wpnotifUpdateChecker->addQueryArgFilter('wpnotif_filter_update_checks');
function wpnotif_filter_update_checks($queryArgs)
{


    $queryArgs['license_key'] = get_option('wpnotif_purchasecode');


    $queryArgs['request_site'] = wpn_network_home_url();

    $queryArgs['license_type'] = get_option('wpnotif_license_type', 1);

    $plugin_data = get_plugin_data(__FILE__);
    $plugin_version = $plugin_data['Version'];

    $queryArgs['version'] = $plugin_version;


    return $queryArgs;
}

/**
 * Add a settings to plugin_action_links
 */
function wpnotif_add_plugin_action_links($links, $file)
{
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $uri = admin_url("admin.php?page=wpnotif-settings");
        $wsl_links = '<a href="' . $uri . '">' . esc_attr__("Settings") . '</a>';

        array_unshift($links, $wsl_links);
    }

    return $links;
}

add_filter('plugin_action_links', 'wpnotif_add_plugin_action_links', 10, 2);


function wpnotif_update_db($force = false)
{
    $current_version = WPNotif::get_version();
    $db_version = get_option('wpnotif_db_version', '1.0');

    if ($current_version != $db_version || $force) {
        do_action('wpnotif_activated');
        update_option('wpnotif_db_version', $current_version, true);
    }
}

register_activation_hook(__FILE__, 'wpnotif_activate');

function wpnotif_activate()
{
    wpnotif_update_db(true);
}

register_deactivation_hook(__FILE__, 'wpnotif_deactivate');

function wpnotif_deactivate()
{
    do_action('wpnotif_deactivated');
}


function wpn_network_home_url($path = '', $scheme = null)
{
    if (!is_multisite()) {
        return wpn_get_home_url(null, $path, $scheme);
    }

    $current_network = get_network();
    $orig_scheme = $scheme;

    if (!in_array($scheme, array('http', 'https', 'relative'))) {
        $scheme = is_ssl() && !is_admin() ? 'https' : 'http';
    }

    if ('relative' == $scheme) {
        $url = $current_network->path;
    } else {
        $url = set_url_scheme('http://' . $current_network->domain . $current_network->path, $scheme);
    }

    if ($path && is_string($path)) {
        $url .= ltrim($path, '/');
    }


    return $url;
}


function wpn_get_home_url($blog_id = null, $path = '', $scheme = null)
{
    global $pagenow;

    $orig_scheme = $scheme;

    if (empty($blog_id) || !is_multisite()) {
        $url = get_option('home');
    } else {
        switch_to_blog($blog_id);
        $url = get_option('home');
        restore_current_blog();
    }

    if (!in_array($scheme, array('http', 'https', 'relative'))) {
        if (is_ssl() && !is_admin() && 'wp-login.php' !== $pagenow) {
            $scheme = 'https';
        } else {
            $scheme = parse_url($url, PHP_URL_SCHEME);
        }
    }

    $url = set_url_scheme($url, $scheme);

    if ($path && is_string($path)) {
        $url .= '/' . ltrim($path, '/');
    }

    return $url;
}
