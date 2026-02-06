<?php

/**
 * Freshdesk Record Api
 */

namespace BitCode\FI\Actions\FluentSupport;

use BitCode\FI\Core\Util\HttpHelper;
use BitCode\FI\Core\Util\Common;
use BitCode\FI\Log\LogHandler;

/**
 * Provide functionality for Record insert, upsert
 */
class RecordApiHelper
{

    private $_integrationID;

    public function __construct($integrationId)
    {
        $this->_integrationID = $integrationId;
    }

    public function generateReqDataFromFieldMap($data, $fieldMap)
    {
        $dataFinal = [];

        foreach ($fieldMap as $key => $value) {
            $triggerValue = $value->formField;
            $actionValue = $value->fluentSupportFormField;
            if ($triggerValue === 'custom') {
                $dataFinal[$actionValue] = Common::replaceFieldWithValue($value->customValue, $data);
            } else if (!is_null($data[$triggerValue])) {
                $dataFinal[$actionValue] = $data[$triggerValue];
            }
        }
        return $dataFinal;
    }

    public function createTicketAndCustomer($finalData, $userName, $password, $clientPriority)
    {
        if (
            empty($userName)
            || empty($password)
        ) {
            wp_send_json_error(
                __(
                    'Requested parameter is empty',
                    'bit-integrations'
                ),
                400
            );
        }
        $prePraperData = [
            "ticket" => [
                "create_customer" => "yes",
                "title" => $finalData['title'],
                "content" => $finalData['content'],
                "priority" => $clientPriority,
            ],
            "newCustomer" => [
                "first_name" => $finalData['first_name'],
                "last_name" => $finalData['last_name'],
                "email" => $finalData['email']
            ]
        ];

        $authorizationHeader["Authorization"] = 'Basic ' . base64_encode("$userName:$password");
        $apiEndpoint =  BTCBI_PRO_API_MAIN . '/fluent-support/v2/tickets';
        return HttpHelper::post($apiEndpoint, $prePraperData, $authorizationHeader);
    }

    public function getCustomerExits($finalData, $userName, $password)
    {
        if (
            empty($userName)
            || empty($password)
        ) {
            wp_send_json_error(
                __(
                    'Requested parameter is empty',
                    'bit-integrations'
                ),
                400
            );
        }
        $authorizationHeader["Authorization"] = 'Basic ' . base64_encode("$userName:$password");
        $apiEndpoint = BTCBI_PRO_API_MAIN . '/fluent-support/v2/customers';
        $apiResponse = HttpHelper::get($apiEndpoint, null, $authorizationHeader);

        $allCustomer = $apiResponse->customers->data;
        $customerId = null;
        foreach ($allCustomer as $key => $value) {
            if ($value->email === $finalData['email']) {
                return $customerId = $value->id;
            }
        }

        return $customerId;
    }

    public function createTicketByExitCustomer($finalData, $userName, $password, $clientPriority)
    {
        if (
            empty($userName)
            || empty($password)
        ) {
            wp_send_json_error(
                __(
                    'Requested parameter is empty',
                    'bit-integrations'
                ),
                400
            );
        }
        $prePraperData = [
            "ticket" => [
                "customer_id" => $finalData['customer_id'],
                "title" => $finalData['title'],
                "content" => $finalData['content'],
                "priority" => $clientPriority
            ]
        ];
        $authorizationHeader["Authorization"] = 'Basic ' . base64_encode("$userName:$password");
        $apiEndpoint =  BTCBI_PRO_API_MAIN . '/fluent-support/v2/tickets';
        return HttpHelper::post($apiEndpoint, $prePraperData, $authorizationHeader);
    }

    public function assignSupportStaff($ticketID, $userName, $password, $supportStaffId)
    {
        if (
            empty($userName)
            || empty($password)
        ) {
            wp_send_json_error(
                __(
                    'Requested parameter is empty',
                    'bit-integrations'
                ),
                400
            );
        }
        $prePraperData = [
            "prop_name" => "agent_id",
            "prop_value" => $supportStaffId
        ];
        $authorizationHeader["Authorization"] = 'Basic ' . base64_encode("$userName:$password");
        $apiEndpoint =  BTCBI_PRO_API_MAIN . '/fluent-support/v2/tickets/' . $ticketID . '/property';
        return HttpHelper::request($apiEndpoint, 'PUT', $prePraperData, $authorizationHeader);
    }

    public function getSingleTicket($ticketID, $userName, $password)
    {
        if (
            empty($userName)
            || empty($password)
        ) {
            wp_send_json_error(
                __(
                    'Requested parameter is empty',
                    'bit-integrations'
                ),
                400
            );
        }
        $authorizationHeader["Authorization"] = 'Basic ' . base64_encode("$userName:$password");
        $apiEndpoint =  BTCBI_PRO_API_MAIN . '/fluent-support/v2/tickets/' . $ticketID;
        return HttpHelper::get($apiEndpoint, null, $authorizationHeader);
    }


    public function execute(
        $fieldValues,
        $fieldMap,
        $integrationDetails,
        $userName,
        $password
    ) {
        $fieldData = [];
        $finalData = $this->generateReqDataFromFieldMap($fieldValues, $fieldMap);
        $customerExits = $this->getCustomerExits($finalData, $userName, $password);
        $clientPriority =  !empty($integrationDetails->actions->client_priority) ? $integrationDetails->actions->client_priority : 'normal';
        if ($customerExits) {
            $finalData['customer_id'] = $customerExits;
            $apiResponse = $this->createTicketByExitCustomer($finalData, $userName, $password, $clientPriority);
        } else {
            $apiResponse = $this->createTicketAndCustomer($finalData, $userName, $password, $clientPriority);
        }
        $ticketID = $apiResponse->ticket->id;
        $supportStaffId = $integrationDetails->actions->support_staff;
        $this->assignSupportStaff($ticketID, $userName, $password, $supportStaffId);
        $apiResponseSingleTicket = $this->getSingleTicket($ticketID, $userName, $password);
        if (property_exists($apiResponse, 'errors')) {
            LogHandler::save($this->_integrationID, json_encode(['type' =>  'contact', 'type_name' => 'add-contact']), 'error', json_encode($apiResponseSingleTicket));
        } else {
            LogHandler::save($this->_integrationID, json_encode(['type' =>  'record', 'type_name' => 'add-contact']), 'success', json_encode($apiResponseSingleTicket));
        }
        return $apiResponse;
    }
}
