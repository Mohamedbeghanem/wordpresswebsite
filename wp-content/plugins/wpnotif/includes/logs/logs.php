<?php

if (!defined('ABSPATH')) {
    exit;
}


function wpnotif_settings_message_logs()
{
    $nonce = wp_create_nonce('wpnotif_admin_message_logs');
    ?>
    <div class="wpnotif_log_table_container">
        <div class="wpnotif_log_table_heading">
            <?php esc_attr_e('WPNotif Logs', 'wpnotif'); ?>
        </div>
        <table id="wpnotif_message_logs" data-nonce="<?php echo esc_attr($nonce); ?>">
            <thead>
            <tr>
                <th><?php esc_attr_e('Date & Time', 'wpnotif'); ?></th>
                <th><?php esc_attr_e('To', 'wpnotif'); ?></th>
                <th><?php esc_attr_e('Route', 'wpnotif'); ?></th>
                <th><?php esc_attr_e('Plugin', 'wpnotif'); ?></th>
                <th><?php esc_attr_e('User Type', 'wpnotif'); ?></th>
                <th><?php esc_attr_e('Action', 'wpnotif'); ?></th>
                <th><?php esc_attr_e('Content', 'wpnotif'); ?></th>
            </tr>
            </thead>
        </table>
    </div>
    <?php
    wp_enqueue_script('datatables', 'https://cdn.datatables.net/v/dt/jszip-2.5.0/dt-1.10.20/af-2.3.4/b-1.6.1/b-colvis-1.6.1/b-flash-1.6.1/b-html5-1.6.1/b-print-1.6.1/cr-1.5.2/fc-3.3.0/fh-3.1.6/kt-2.5.1/r-2.2.3/rg-1.1.1/rr-1.2.6/sc-2.0.1/sl-1.3.1/datatables.min.js', array(
        'jquery'
    ), null);

    wp_register_script('wpnotif-admin-message-logs', WPNotif::get_dir('/assets/js/logs.min.js'), array(
        'jquery',
        'datatables',
    ), WPNotif::get_version(), true);

    $obj = array(
        'ajax_url' => admin_url('admin-ajax.php'),
    );
    wp_localize_script('wpnotif-admin-message-logs', 'wpnmeslog', $obj);
    wp_enqueue_script('wpnotif-admin-message-logs');

    ?>
    <style>
        #wpcontent {
            background-color: #fafafa;
        }
    </style>
    <?php

}

add_action('wp_ajax_wpnotif_message_log_data', 'wpnotif_message_log_data');

function wpnotif_message_log_data()
{
    if (!current_user_can('manage_options')) {
        die();
    }
    if (!wp_verify_nonce($_REQUEST['nonce'], 'wpnotif_admin_message_logs')) {
        die();
    }

    global $wpdb;

    $start = absint($_REQUEST['start']);
    $end = $start + absint($_REQUEST['length']);

    $table = $wpdb->prefix . 'wpnotif_message_logs';
    $sql = "SELECT COUNT(*) FROM $table";
    $total_entries = $wpdb->get_var($sql);

    $query = "SELECT * FROM $table ORDER BY request_id DESC LIMIT %d,%d";
    $query = $wpdb->prepare($query, $start, $end);
    $logs = $wpdb->get_results($query);

    $results = array();
    $data = array();

    $gateway_names = [];
    foreach ($logs as $log) {
        if (!empty($log->email)) {
            $to = $log->email;
        } else {
            $to = $log->phone;
        }
        $mode = $log->mode;

        $route = wpnotif_log_get_mode_label($mode);
        if ($mode == 'sms') {
            $gateway_id = $log->gateway_id;
            if (!empty($gateway_id)) {
                if (isset($gateway_names[$mode][$gateway_id])) {
                    $gateway_name = $gateway_names[$mode][$gateway_id];
                } else {
                    $gateway_name = wpnotif_log_get_gateway_name($gateway_id);
                    $gateway_names[$mode][$gateway_id] = $gateway_name;
                }
                if (!empty($gateway_name)) {
                    $route = sprintf('%s (%s)', $route, $gateway_name);
                }
            }
        }


        $date = new DateTime($log->time);
        $date = date_format($date, "d M 'y h:i A");

        $action = '';
        if (!empty($log->request_type)) {
            $action = $log->request_type;
        }
        $data[] = [
            'date_time' => $date,
            'to' => $to,
            'route' => $route,
            'plugin' => $log->plugin,
            'user_type' => $log->user_type,
            'action' => $action,
            'content' => $log->message,
        ];
    }

    $results['recordsTotal'] = $total_entries;
    $results['recordsFiltered'] = $total_entries;
    $results['data'] = $data;
    wp_send_json($results);
}


function wpnotif_log_get_mode_label($mode)
{
    $mode = strtolower($mode);
    $modes = [
        'sms' => __('SMS', 'wpnotif'),
        'whatsapp' => __('WhatsApp', 'wpnotif'),
        'email' => __('Email', 'wpnotif'),
    ];
    return $modes[$mode];
}

function wpnotif_log_get_gateway_name($gateway_no)
{
    $smsgateways = WPNotif::instance()->getGateWayArray();
    foreach ($smsgateways as $gateway_key => $gateway) {
        if ($gateway['value'] == $gateway_no) {
            if (isset($gateway['label'])) {
                return $gateway['label'];
            }
            return $gateway_key;
        }
    }
    return '';
}


function wpnotif_log_get_whatsapp_gateway_name($gateway_no)
{
    $gateways = getWhatsAppGateWayArray();
    foreach ($gateways as $gateway_key => $gateway) {
        if ($gateway['value'] == $gateway_no) {
            if (isset($gateway['label'])) {
                return $gateway['label'];
            }
            return $gateway_key;
        }
    }
    return '';
}


add_action('wpnotif_dbasic_cron_job','wpnotif_collect_telemetry');

function wpnotif_collect_telemetry()
{
    $usage_sharing = get_option('wpnotif_usage_data_sharing', 0);

    if ($usage_sharing != 1) {
        return;
    }

    global $wpdb;
    $usage_id = get_option('wpnotif_usage_data_sharing_random_id', false);
    if (!$usage_id) {
        $usage_id = md5(uniqid('wpnotif'));
        update_option('wpnotif_usage_data_sharing_random_id', $usage_id);
    }
    $usage = [];
    $newsletter_history_table = $wpdb->prefix . "wpnotif_newsletter_history";

    $rid = get_option('wpnotif_usage_last_nl_history', 0);
    $newsletter_count = $wpdb->get_var($wpdb->prepare("select count(*) from $newsletter_history_table where id > %s", $rid));
    $rid = $wpdb->get_var("select id from $newsletter_history_table ORDER by id DESC LIMIT 1");


    $usage['newsletter_history_count'] = $newsletter_count;

    $last_time = get_option('wpnotif_usage_last_msg_history', 0);
    $date = date('Y-m-d H:i:s', $last_time);

    $message_info = $wpdb->get_results($wpdb->prepare("SELECT 
    mode,plugin,request_type,user_type,
    COUNT(*) AS count 
FROM 
    wp_wpnotif_message_logs
WHERE time > %s
GROUP BY 
    plugin,mode,request_type,user_type
ORDER BY 
    count DESC;", $date));


    $usage['message_count'] = $message_info;
    if(empty($message_info) && empty($newsletter_count)){
        return;
    }

    $usage = json_encode($usage);
    $data = array(
        'plugin' => 'wpnotif',
        'type' => 'usage',
        'd' => base64_encode($usage)
    );
    
    $response = wpnotif_telemetry($data);
    if ($response) {
        update_option('wpnotif_usage_last_nl_history', $rid);
        update_option('wpnotif_usage_last_msg_history', time());
    }
}

function wpnotif_telemetry($data)
{

    $url = 'https://bridge.unitedover.com/feedback/usage/wpnotif.php';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    curl_close($ch);
    return $response == 1;
}