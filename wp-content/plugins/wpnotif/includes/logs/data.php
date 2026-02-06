<?php


if (!defined('ABSPATH')) {
    exit;
}

WPNotifDashboardData::instance();

final class WPNotifDashboardData
{

    const LOGIN_TIME_SAVE_IN_S = 30;
    protected static $_instance = null;

    public function __construct()
    {
        add_action('wp_ajax_wpnotif_admin_dashboard_stats', array($this, 'admin_dashboard'));
    }

    /**
     *  Constructor.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function admin_dashboard()
    {
        $this->check_permission();

        if ($_REQUEST['graph_type'] == 'monthly_notifications') {
            $data = $this->get_monthly_messages(12);
        } else {
            $data = $this->get_daily_messages(6);
        }
        wp_send_json_success($data);
    }

    public function check_permission()
    {
        if (!current_user_can('manage_options')) {
            die();
        }
        if (!wp_verify_nonce($_REQUEST['nonce'], 'wpnotif_admin_dashboard')) {
            die();
        }

    }

    public function get_monthly_messages($duration)
    {
        $overall_total_messages = $this->get_total_messages_count();

        global $wpdb;

        $duration = absint($duration);

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}wpnotif_message_logs WHERE time > now()-interval %d month ";
        $sql = $wpdb->prepare($sql, $duration);
        $total_messages = $wpdb->get_var($sql);

        $data = array();
        $sql = "SELECT COUNT(*) as total_messages, DATE_FORMAT(time,'%m-%Y') as duration FROM {$wpdb->prefix}wpnotif_message_logs WHERE time > now()-interval %d month GROUP BY DATE_FORMAT(time,'%m-%Y') ";
        $sql = $wpdb->prepare($sql, $duration + 1);
        $records = $wpdb->get_results($sql);

        $dateTime = new DateTime();
        for ($i = 1; $i <= $duration; $i++) {
            $date_key = $dateTime->format('m-Y');
            $month = $dateTime->format('M');
            $data[$date_key] = ['x' => $month, 'y' => 0];
            $dateTime->modify('-1 month');
        }

        foreach ($records as $record) {
            if (isset($data[$record->duration])) {
                $data[$record->duration]['y'] = $record->total_messages;
            }
        }


        $total_sms = $this->get_total_sms_count();
        $result = array(
            'total_data' => $this->format_number($total_messages),
            'graph' => array_reverse(array_values($data)),
            'overall_total_messages' => $this->format_number($overall_total_messages),
            'overall_total_sms' => $this->format_number($total_sms),
            'type' => 'monthly_notifications'
        );
        return $result;
    }

    public function get_total_messages_count()
    {
        global $wpdb;
        /*$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}wpnotif_message_logs WHERE mode = %s ";
        $sql = $wpdb->prepare($sql, $route);*/
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}wpnotif_message_logs";
        $total = $wpdb->get_var($sql);
        return $total;
    }

    public function get_total_sms_count()
    {
        global $wpdb;
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}wpnotif_message_logs WHERE `mode`='sms'";
        $total = $wpdb->get_var($sql);
        return $total;
    }

    public function format_number($number)
    {
        $suffix = '';
        if ($number > 9999) {
            $number = floor($number / 1000);
            $suffix = 'k';
        }
        $number = round($number, 2);
        return $number . $suffix;
    }

    public function get_daily_messages($duration)
    {
        global $wpdb;

        $duration = absint($duration);

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}wpnotif_message_logs WHERE time > now()-interval %d month ";
        $sql = $wpdb->prepare($sql, $duration);
        $total_messages = $wpdb->get_var($sql);

        $sql = "SELECT COUNT(*) as total_messages, DATE_FORMAT(time,'%Y-%m-%d') as duration FROM {$wpdb->prefix}wpnotif_message_logs WHERE time > now()-interval $duration month GROUP BY DATE_FORMAT(time,'%Y-%m-%d') ";

        $records = $wpdb->get_results($sql);
        $data = array();
        foreach ($records as $record) {
            $data[$record->duration] = $record->total_messages;
        }

        $start = new DateTime();
        $end = new DateTime();
        $start->modify("-$duration months");

        $range = $this->date_range($start, $end);

        $result = array();
        foreach ($range as $date_info) {
            $date = $date_info[0];
            $timestamp = $date_info[1];
            $day_logins = !empty($data[$date]) ? $data[$date] : 0;
            $result[] = [$timestamp, $day_logins];
        }
        return array(
            'total_data' => $this->format_number($total_messages),
            'graph' => $result,
            'type' => 'daily_notifications'
        );
    }

    public function date_range($start, $end)
    {
        $dates = [];
        $start->setTime(0, 0, 0);
        $end->setTime(0, 0, 0);
        $current = $start->getTimestamp();
        $end_time = $end->getTimestamp();

        while ($current <= $end_time) {

            $time_stamp = $start->getTimestamp();
            $dates[] = [$start->format('Y-m-d'), $time_stamp * 1000];
            $start->modify('+1 day');
            $current = $time_stamp;
        }

        return $dates;
    }
}
