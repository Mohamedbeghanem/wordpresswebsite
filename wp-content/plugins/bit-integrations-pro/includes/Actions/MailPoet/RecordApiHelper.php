<?php

/**
 * ZohoRecruit Record Api
 */

namespace BitCode\FI\Actions\MailPoet;

use \MailPoet\API\MP\v1\APIException;
use BitCode\FI\Log\LogHandler;

/**
 * Provide functionality for Record insert,upsert
 */
class RecordApiHelper
{
    private $_integrationID;


    public function __construct($integId)
    {
        $this->_integrationID = $integId;
    }

    public function insertRecord($subscriber, $lists)
    {
        $mailpoet_api = \MailPoet\API\API::MP('v1');
        
        try {
            $response = $mailpoet_api->addSubscriber($subscriber, $lists);
            $response = [
            'success'=> true,
            'id' => $response['id']
            ];
        } catch (APIException $e) {
            $response = [
            'success'=> false,
            'code' => $e->getCode(),
            'message' => $e->getMessage()
            ];
        }
        return $response;
    }

    public function execute($fieldValues, $fieldMap, $lists)
    {
        if (!class_exists(\MailPoet\API\API::class)) {
            return;
        }
        $fieldData = [];

        foreach ($fieldMap as $fieldKey => $fieldPair) {
            if (!empty($fieldPair->mailPoetField)) {
                if ($fieldPair->formField === 'custom' && isset($fieldPair->customValue)) {
                    $fieldData[$fieldPair->mailPoetField] = $fieldPair->customValue;
                } else {
                    $fieldData[$fieldPair->mailPoetField] = $fieldValues[$fieldPair->formField];
                }
            }
        }

        $recordApiResponse = $this->insertRecord($fieldData, $lists);
        if ($recordApiResponse['success']) {
            LogHandler::save($this->_integrationID , ['type' =>  'record', 'type_name' => 'insert'], 'success', $recordApiResponse);
        } else {
            LogHandler::save($this->_integrationID , ['type' =>  'record', 'type_name' => 'insert'], 'error', $recordApiResponse);
        }

        return $recordApiResponse;
    }
}
