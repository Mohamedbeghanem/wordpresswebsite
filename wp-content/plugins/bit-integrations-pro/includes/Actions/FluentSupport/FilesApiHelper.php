<?php

/**
 * Slack Files Api
 */

namespace BitCode\FI\Actions\FluentSupport;

/**
 * Provide functionality for Upload files
 */
final class FilesApiHelper
{
    private $_defaultHeader;
    private $_payloadBoundary;

    public function __construct()
    {
        $this->_payloadBoundary = wp_generate_password(24);
        $this->_defaultHeader['Content-Type'] = "multipart/form-data; boundary=" . $this->_payloadBoundary;
    }

    /**
     * Helps to execute upload files api
     *
     * @param String $apiEndPoint Telegram API base URL
     * @param Array  $data        Data to pass to API
     *
     * @return Array $uploadResponse Telegram API response
     */
    public function uploadFiles($apiEndPoint, $data, $api_key)
    {

        $data['avatar'] = new \CURLFILE("{$data['avatar']}");
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => $apiEndPoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: multipart/form-data",
                    "Authorization: ". base64_encode("$api_key")
                )
            )
        );

        $uploadResponse = curl_exec($curl);
        curl_close($curl);
        return $uploadResponse;
    }
}
