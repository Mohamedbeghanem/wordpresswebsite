<?php
namespace BitCode\FI\Admin;

use BitCode\FI\Core\Util\DateTimeHelper;
use BitCode\FI\Core\Util\Capabilities;
use BitCode\FI\Core\Util\Hooks;
use BitCode\FI\Plugin;

/**
 * The admin menu and page handler class
 */

class Admin_Bar
{
    public function register()
    {
        Hooks::add('in_admin_header', [$this, 'RemoveAdminNotices']);
        Hooks::add('admin_menu', [$this, 'AdminMenu'], 11);
        Hooks::add('admin_enqueue_scripts', [$this, 'AdminAssets'], 11);
        Hooks::filter('btcbi_localized_script', [$this, 'filterAdminScriptVar']);
        add_filter('script_loader_tag', [$this, 'scriptTagFilter'], 0, 3);
    }

    /**
     * Register the admin menu
     *
     * @return void
     */
    public function AdminMenu()
    {
        global $submenu;

        $capability = Hooks::apply('manage_wp_integrations', 'manage_options');
        if (Capabilities::Check($capability)) {
            $rootExists = !empty($GLOBALS['admin_page_hooks']['bit-integrations']);
            if ($rootExists) {
                remove_menu_page('bit-integrations');
            }
            add_menu_page(__('Integrations for WordPress Forms', 'bit-integrations'), 'Bit Integrations Pro', $capability, 'bit-integrations', $rootExists ? '' : [$this, 'rootPage'], 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" data-name="Layer 1" viewBox="0 0 600 600"><path fill="#a7aaad" d="M522.81 5H77.19A72.19 72.19 0 0 0 5 77.19v445.62A72.19 72.19 0 0 0 77.19 595h445.62A72.19 72.19 0 0 0 595 522.81V77.19A72.19 72.19 0 0 0 522.81 5Zm-80.4 228.31a1.43 1.43 0 0 1-1.42 1.42H401.3a1.42 1.42 0 0 1-1.42-1.42v-23.78a1.41 1.41 0 0 1 1.42-1.42H441a1.42 1.42 0 0 1 1.42 1.42Zm0-55.15a1.43 1.43 0 0 1-1.42 1.42h-41.11l-176.28-.08q-19 0-19 23v49.62q0 23 24.37 23L398.5 275a1.42 1.42 0 0 1 1.43 1.42v-17.84a1.42 1.42 0 0 1 1.42-1.42H441a1.43 1.43 0 0 1 1.42 1.42v199.05a1.42 1.42 0 0 1-1.42 1.42h-39.7a1.41 1.41 0 0 1-1.42-1.42V312.26a1.43 1.43 0 0 1-1.43 1.42l-173.07.05q-25.26 0-25.26 22.6v70.44q0 13.74 16.84 13.74l140.84.13a1.43 1.43 0 0 1 1.42 1.42v35.57a1.42 1.42 0 0 1-1.42 1.42H205.88q-48.28 0-48.29-47.4V331.9q0-26.19 34-36a1.41 1.41 0 0 0-.1-2.74Q162 286.64 162 259.68v-61.14Q162 141 218.73 141H441a1.42 1.42 0 0 1 1.42 1.42Z"/></svg>'), 30);
            $submenu['bit-integrations'][] = [__('Bit Integrations Pro', 'bit-integrations'), $capability, 'admin.php?page=bit-integrations#/'];
        }
    }

    /**
     * Filter variables for admin script
     *
     * @param Array $previousValue Current values
     *
     * @return $previousValue Filtered Values
     */
    public function filterAdminScriptVar(array $previousValue)
    {
        $isBitFiLicActive = Plugin::instance()->isLicenseActive();
        if ($isBitFiLicActive) {
            $previousValue['isPro'] = true;
        }
        return $previousValue;
    }

    /**
     * Load the asset libraries
     *
     * @return void
     */
    public function AdminAssets($current_screen)
    {
        if (strpos($current_screen, 'bit-integrations') === false) {
            return;
        }

        $parsed_url = parse_url(get_admin_url());
        $site_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
        $site_url .= empty($parsed_url['port']) ? null : ':' . $parsed_url['port'];
        $base_path_admin = str_replace($site_url, '', get_admin_url());

        if (defined('BITAPPS_DEV') && BITAPPS_DEV) {
            wp_enqueue_script('vite-client-helper-BTCBI-MODULE', BIT_DEV_URL . '/config/devHotModule.js', [], null);
            wp_enqueue_script('vite-client-BTCBI-MODULE', BIT_DEV_URL . '/@vite/client', [], null);
            wp_enqueue_script('index-BTCBI-MODULE', BIT_DEV_URL . '/main.jsx', [], null);
          }
      
          if (!defined('BITAPPS_DEV')) {
            wp_enqueue_script('index-BTCBI-MODULE', BTCBI_PRO_ASSET_URI . '/main.js', [], null);
            wp_enqueue_style('bf-css', BTCBI_PRO_ASSET_URI . '/main.css');
          }

        if (wp_script_is('wp-i18n')) {
            $deps = ['btcbi-vendors', 'btcbi-runtime', 'wp-i18n'];
        } else {
            $deps = ['btcbi-vendors', 'btcbi-runtime', ];
        }

        wp_enqueue_script(
            'btcbi-admin-script',
            BTCBI_PRO_ASSET_JS_URI . '/index.js',
            $deps,
            BTCBI_PRO_VERSION,
            true
        );

        if (!wp_script_is('tinymce_js')) {
            wp_enqueue_script('tinymce_js', includes_url('js/tinymce/') . 'wp-tinymce.php', null, false, true);
        }
        global $wp_rewrite;
        $api = [
            'base' => get_rest_url() . 'bit-integrations/v1',
            'separator' => $wp_rewrite->permalink_structure ? '?' : '&'
        ];

        $users = get_users(['fields' => ['ID', 'user_nicename', 'user_email', 'display_name']]);
        $userMail = [];
        // $userNames = [];
        foreach ($users as $key => $value) {
            $userMail[$key]['label'] = !empty($value->display_name) ? $value->display_name : '';
            $userMail[$key]['value'] = !empty($value->user_email) ? $value->user_email : '';
            $userMail[$key]['id'] = $value->ID;
            // $userNames[$value->ID] = ['name' => $value->display_name, 'url' => get_edit_user_link($value->ID)];
        }

        $btcbi = apply_filters(
            'btcbi_localized_script',
            [
                'nonce' => wp_create_nonce('btcbi_nonce'),
                'assetsURL' => BTCBI_PRO_ASSET_URI,
                'baseURL' => $base_path_admin . 'admin.php?page=bit-integrations#',
                'licenseURL' => $base_path_admin . 'admin.php?page=bit-integrations-license',
                'siteURL' => site_url(),
                'ajaxURL' => admin_url('admin-ajax.php'),
                'api' => $api,
                'dateFormat' => get_option('date_format'),
                'timeFormat' => get_option('time_format'),
                'timeZone' => DateTimeHelper::wp_timezone_string(),
                'userMail' => $userMail
            ]
        );
        if (get_locale() !== 'en_US' && file_exists(BTCBI_PRO_PLUGIN_BASEDIR . '/languages/generatedString.php')) {
            include_once BTCBI_PRO_PLUGIN_BASEDIR . '/languages/generatedString.php';
            $btcbi['translations'] = $bit_integrations_i18n_strings;
        }
        wp_localize_script('index-BTCBI-MODULE', 'btcbi', $btcbi);
    }

    /**
     * Bit-Integrations  apps-root id provider
     *
     * @return void
     */
    public function rootPage()
    {
        include BTCBI_PRO_PLUGIN_BASEDIR . '/views/view-root.php';
    }

    public function RemoveAdminNotices()
    {
        global $plugin_page;
        if (strpos($plugin_page, 'bit-integrations') === false) {
            return;
        }
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
    }

    public function scriptTagFilter($html, $handle, $href) {
        $newTag = $html;
        if (preg_match('/BTCBI-MODULE/', $handle)) {
          $newTag = preg_replace('/<script /', '<script type="module" ', $newTag);
        }
        return $newTag;
      }
}
