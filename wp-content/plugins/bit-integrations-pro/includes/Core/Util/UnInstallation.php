<?php

namespace BitCode\FI\Core\Util;

/**
 * Class handling plugin uninstallation.
 *
 * @since 1.0.0
 * @access private
 * @ignore
 */
final class UnInstallation
{
    /**
     * Registers functionality through WordPress hooks.
     *
     * @since 1.0.0-alpha
     */
    public function register()
    {
        $option = get_option('btcbi_app_conf');
        if (isset($option->erase_db)) {
            add_action('btcbi_pro_uninstall', array($this, 'uninstall'));
        }
    }

    public function uninstall()
    {
        global $wpdb;
        $freeVersionInstalled = get_option('btcbi_installed');
        $columns = ["btcbi_pro_db_version", "btcbi_pro_installed", "btcbi_pro_version"];

        if (!$freeVersionInstalled) {
            $tableArray = [
                $wpdb->prefix . "btcbi_flow",
                $wpdb->prefix . "btcbi_log",
            ];
            foreach ($tableArray as $tablename) {
                $wpdb->query("DROP TABLE IF EXISTS $tablename");
            }

            $columns = $columns + ["btcbi_app_conf"];
        }

        foreach ($columns as $column) {
            $wpdb->query("DELETE FROM `{$wpdb->prefix}options` WHERE option_name='$column'");
        }
        $wpdb->query("DELETE FROM `{$wpdb->prefix}options` WHERE `option_name` LIKE '%btcbi_webhook_%'");
    }
}
