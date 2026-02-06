<?php

defined('ABSPATH') || exit;


function wpnotif_create_message_log($phone, $msg, $gateway, $data)
{
    $logger = new WPNotifLogger();
    $logger->setData($data);
    $logger->setMessage($msg);
    $logger->setPhone($phone);
    $logger->setGateway($gateway);
    $logger->save();
}


add_action('wpnotif_activated', 'wpnotif_create_req_logs_db');
function wpnotif_create_req_logs_db()
{
    global $wpdb;


    $tb = $wpdb->prefix . 'wpnotif_message_logs';
    if ($wpdb->get_var("SHOW TABLES LIKE '$tb'") != $tb) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $tb (
                  request_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		          phone VARCHAR(40) NOT NULL,
		          email VARCHAR(100) NOT NULL,
		          message TEXT NULL,
		          mode VARCHAR(100) NOT NULL,
		          plugin VARCHAR(255) NULL,
		          user_type VARCHAR(255) NULL,
		          gateway_id VARCHAR(255) NULL,
		          request_type VARCHAR(100) NOT NULL,
		          user_agent VARCHAR(255) NULL,
		          ip VARCHAR(200) NOT NULL,
		          response VARCHAR(255) NULL,
		          time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
		          PRIMARY KEY  (request_id),
		          INDEX idx_phone (phone),
		          INDEX idx_plugin (plugin),
		          INDEX idx_user_type (user_type),
                   INDEX idx_ip (ip)
	            ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta(array($sql));
    }
}

