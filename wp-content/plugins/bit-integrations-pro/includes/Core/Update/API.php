<?php
namespace BitCode\FI\Core\Update;

use WP_Error;
use BitCode\FI\Core\Util\HttpHelper;
use BitCode\FI\Core\Util\DateTimeHelper;

final class API
{
    public static function getAPiEndPoint()
    {
        return 'https://wp-api.bitapps.pro';
    }

    public static function getUpdatedInfo()
    {
        $licenseKey = self::getKey();
        $pluginInfoResponse = HttpHelper::get(self::getAPiEndPoint() . '/update/bit-integrations-pro', null, ['licKey' => $licenseKey]);
        if (is_wp_error($pluginInfoResponse)) {
            return $pluginInfoResponse;
        }
        if (!empty($pluginInfoResponse->status) && $pluginInfoResponse->status == 'expired') {
            self::removeKeyData();
            return new WP_Error('API_ERROR', $pluginInfoResponse->message);
        }
        if (empty($pluginInfoResponse->data)) {
            return new WP_Error('API_ERROR', $pluginInfoResponse->message);
        }
        $pluginData = $pluginInfoResponse->data;
        $dateTimeHelper = new DateTimeHelper();
        $pluginData->updatedAt = $dateTimeHelper->getFormated($pluginData->updatedAt, 'Y-m-d\TH:i:s.u\Z', DateTimeHelper::wp_timezone(), 'Y-m-d H:i:s', null);
        if (!empty($pluginData->details)) {
            $pluginData->sections['description'] = $pluginData->details;
        } else {
            $pluginData->sections['description'] = '';
        }
        if (!empty($pluginData->changelog)) {
            $pluginData->sections['changelog'] = $pluginData->changelog;
        } else {
            $pluginData->sections['changelog'] = '';
        }
        if ($licenseKey) {
            $pluginData->downloadLink = self::getAPiEndPoint() . '/download/' . $licenseKey;
        } else {
            $pluginData->downloadLink = '';
        }
        return $pluginData;
    }

    public static function activateLicense($licenseKey)
    {
        $data['licenseKey'] = $licenseKey;
        $data['domain'] = site_url();
        $data['slug'] = 'bit-integrations-pro';
        $activateResponse = HttpHelper::post(self::getAPiEndPoint() . '/activate', json_encode($data), ['content-type' => 'application/json']);
        if (!is_wp_error($activateResponse) && $activateResponse->status === 'success') {
            self::setKeyData($licenseKey, $activateResponse);
            return true;
        }
        return empty($activateResponse->message) ? __('Unknown error occurred', 'btcbi') : $activateResponse->message;
    }

    public static function disconnectLicense()
    {
        $integrateData = get_option('btcbi_integrate_key_data');
        if (!empty($integrateData) && is_array($integrateData) && $integrateData['status'] === 'success') {
            $data['licenseKey'] = $integrateData['key'];
            $data['domain'] = site_url();
            $deactivateResponse = HttpHelper::post(self::getAPiEndPoint() . '/deactivate', json_encode($data), ['content-type' => 'application/json']);
            if (!is_wp_error($deactivateResponse) && $deactivateResponse->status === 'success' || $deactivateResponse->code === 'INVALID_LICENSE') {
                self::removeKeyData();
                return true;
            }
            return empty($deactivateResponse->message) ? __('Unknown error occurred', 'btcbi') : $deactivateResponse->message;
        }
        return empty($deactivateResponse->message) ? __('License data is missing', 'btcbi') : $deactivateResponse->message;
    }

    public static function setKeyData($licenseKey, $licData)
    {
        $data['key'] = $licenseKey;
        $data['status'] = $licData->status;
        $data['expireIn'] = $licData->expireIn;
        return update_option('btcbi_integrate_key_data', $data, null);
    }

    public static function getKey()
    {
        $integrateData = get_option('btcbi_integrate_key_data');
        $licenseKey = false;
        if (!empty($integrateData) && is_array($integrateData) && $integrateData['status'] === 'success') {
            $licenseKey = $integrateData['key'];
        }
        return $licenseKey;
    }

    public static function removeKeyData()
    {
        return delete_option('btcbi_integrate_key_data');
    }

    public static function isLicenseActive()
    {
        $integrateData = get_option('btcbi_integrate_key_data');
        if (!empty($integrateData) && is_array($integrateData) && $integrateData['status'] === 'success') {
            return true;
        }
        return false;
    }
}
