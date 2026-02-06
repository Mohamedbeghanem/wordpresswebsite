<?php

if (!defined('ABSPATH')) {
    exit;
}
$scheme = parse_url(home_url())['scheme'];

define('BIT_DEV_URL', defined('BITAPPS_DEV') && BITAPPS_DEV ? 'http://localhost:3000' : false);
define('BTCBI_PRO_PLUGIN_BASENAME', plugin_basename(BTCBI_PRO_PLUGIN_MAIN_FILE));
define('BTCBI_PRO_PLUGIN_BASEDIR', plugin_dir_path(BTCBI_PRO_PLUGIN_MAIN_FILE));
define('BTCBI_PRO_ROOT_URI', set_url_scheme(plugins_url('', BTCBI_PRO_PLUGIN_MAIN_FILE), $scheme));
define('BTCBI_PRO_PLUGIN_DIR_PATH', plugin_dir_path(BTCBI_PRO_PLUGIN_MAIN_FILE));
define('BTCBI_PRO_ASSET_URI', BTCBI_PRO_ROOT_URI . '/assets');
define('BTCBI_PRO_ASSET_JS_URI', BIT_DEV_URL ? BIT_DEV_URL : BTCBI_PRO_ROOT_URI . '/assets');
// Autoload vendor files.
require_once BTCBI_PRO_PLUGIN_BASEDIR . 'vendor/autoload.php';
// Initialize the plugin.
BitCode\FI\Plugin::load(BTCBI_PRO_PLUGIN_MAIN_FILE);
