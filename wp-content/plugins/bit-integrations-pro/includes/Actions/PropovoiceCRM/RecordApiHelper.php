<?php

namespace BitCode\FI\Actions\PropovoiceCRM;

use BitCode\FI\Core\Util\Common;
use BitCode\FI\Core\Util\HttpHelper;
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
            $actionValue = $value->propovoiceCrmFormField;
            if ($triggerValue === 'custom') {
                $dataFinal[$actionValue] = Common::replaceFieldWithValue($value->customValue, $data);
            } elseif (!is_null($data[$triggerValue])) {
                $dataFinal[$actionValue] = $data[$triggerValue];
            }
        }
        return $dataFinal;
    }

    public function createLead($finalData){
        $propovoiceLeadInstance = new \Ndpv\Model\Lead();
        $propovoiceLeadInstance->create($finalData);
    }

    public function execute(
        $fieldValues,
        $fieldMap,
        $integrationDetails,
        $mainAction
    ) {
        $finalData = $this->generateReqDataFromFieldMap($fieldValues, $fieldMap);
        $apiResponse = null;
        if ($mainAction == '1') {
            $tags = explode(',',$integrationDetails->tags);
            $label = $integrationDetails->label;
            $finalData['tags'] = $tags;
            $finalData['level_id'] = $label;
            $apiResponse = $this->createLead($finalData);
            if ($apiResponse) {
                LogHandler::save($this->_integrationID, json_encode(['type' => 'insert', 'type_name' => 'add-subscriber']), 'success', json_encode($apiResponse));
            } else {
                LogHandler::save($this->_integrationID, json_encode(['type' => 'insert', 'type_name' => 'add-subscriber']), 'error', json_encode($apiResponse));
            }
        }
        return $apiResponse;
    }
}
