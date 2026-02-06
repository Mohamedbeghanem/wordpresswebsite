<?php

defined('ABSPATH') || exit;

class WPNotifLogger
{

    public $phone;
    public $message;
    public $mode;
    public $gateway_id;
    public $user_agent;
    public $ip;
    public $data = [];

    function __construct()
    {
        $this->data = array('request_type' => '-', 'plugin' => 'wpnotif', 'user_type' => 'user');
        $this->user_agent = wp_unslash($_SERVER['HTTP_USER_AGENT']);
        $this->ip = wpnotif_get_ip();
    }

    public function save()
    {

        $db_version = get_option('wpnotif_db_version', '1.0');
        if ($db_version == '1.0') {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'wpnotif_message_logs';
        $data = $this->data;
        $data['phone'] = $this->phone;
        $data['message'] = $this->message;
        $data['mode'] = $this->mode;
        $data['gateway_id'] = $this->gateway_id;
        $data['user_agent'] = $this->user_agent;
        $data['ip'] = $this->ip;

        return $wpdb->insert($table, $data);
    }

    public function setData($info)
    {


        if (isset($info['user_type'])) {
            $this->data['user_type'] = $info['user_type'];
        }

        $data = $info['data'];
        $this->parseData($data);

        if (isset($data['data'])) {
            if(!is_object($data['data'])) {
                $this->parseData($data['data']);
            }
        }

    }

    public function parseData($data)
    {
        $keys = ['plugin', 'request_type'];
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                $this->data[$key] = $data[$key];
            }
        }
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    public function setGateway($gateway)
    {
        if ($gateway == 1001) {
            $this->mode = 'whatsapp';
            $whatsapp = get_option('wpnotif_whatsapp');
            $this->gateway_id = $whatsapp['whatsapp_gateway'];
        } else {
            $this->mode = 'sms';
            $this->gateway_id = $gateway;
        }
    }
}