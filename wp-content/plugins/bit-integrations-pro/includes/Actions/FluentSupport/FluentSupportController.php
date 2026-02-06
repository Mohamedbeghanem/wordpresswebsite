<?php

/**
 * Fluent Support Integration
 */

namespace BitCode\FI\Actions\FluentSupport;

use WP_Error;
use BitCode\FI\Core\Util\IpTool;
use BitCode\FI\Core\Util\HttpHelper;

use BitCode\FI\Actions\FluentSupport\RecordApiHelper;

/**
 * Provide functionality for Fluent Support integration
 */
class FluentSupportController
{

    /**
     * Process ajax request for generate_token
     *
     * @param Object $requestsParams Params to authorize
     *
     * @return JSON Fluent Support api response and status
     */

    public function checkAuthorization($tokenRequestParams)
    {

        if (
            empty($tokenRequestParams->userName)
            || empty($tokenRequestParams->password)
        ) {
            wp_send_json_error(
                __(
                    'Requested parameter is empty',
                    'bit-integrations'
                ),
                400
            );
        }

        $authorizationHeader["Authorization"] = 'Basic ' . base64_encode("$tokenRequestParams->userName:$tokenRequestParams->password");
        $apiEndpoint = BTCBI_PRO_API_MAIN . '/fluent-support/v2/tickets';
        $apiResponse = HttpHelper::get($apiEndpoint, null, $authorizationHeader);

        if (is_wp_error($apiResponse)) {
            wp_send_json_error(
                empty($apiResponse->error) ? 'Unknown' : $apiResponse->error,
                400
            );
        }
        wp_send_json_success(is_string($apiResponse) ? json_decode($apiResponse) : $apiResponse, 200);
    }



    // get all support staff

    public function getAllSupportStaff($tokenRequestParams)
    {
        if (
            empty($tokenRequestParams->userName)
            || empty($tokenRequestParams->password)
        ) {
            wp_send_json_error(
                __(
                    'Requested parameter is empty',
                    'bit-integrations'
                ),
                400
            );
        }
        $authorizationHeader["Authorization"] = 'Basic ' . base64_encode("$tokenRequestParams->userName:$tokenRequestParams->password");
        $apiEndpoint = BTCBI_PRO_API_MAIN . '/fluent-support/v2/agents';
        $apiResponse = HttpHelper::get($apiEndpoint, null, $authorizationHeader);

        if (is_wp_error($apiResponse)) {
            wp_send_json_error(
                empty($apiResponse->error) ? 'Unknown' : $apiResponse->error,
                400
            );
        }
        wp_send_json_success(is_string($apiResponse) ? json_decode($apiResponse) : $apiResponse, 200);
    }

    public function execute($integrationData, $fieldValues)
    {
        $integrationDetails = $integrationData->flow_details;
        $integrationId = $integrationData->id;
        $fieldMap = $integrationDetails->field_map;
        $userName = $integrationDetails->userName;
        $password = $integrationDetails->password;

        if (
            empty($integrationDetails) || empty($integrationDetails->userName) || empty($integrationDetails->password)

        ) {
            return new WP_Error('REQ_FIELD_EMPTY', __('module, fields are required for Freshdesk api', 'bit-integrations'));
        }
        $recordApiHelper = new RecordApiHelper($integrationId);
        $fluentSupportApiResponse = $recordApiHelper->execute(
            $fieldValues,
            $fieldMap,
            $integrationDetails,
            $userName,
            $password
        );

        if (is_wp_error($fluentSupportApiResponse)) {
            return $fluentSupportApiResponse;
        }
        return $fluentSupportApiResponse;
    }
}
