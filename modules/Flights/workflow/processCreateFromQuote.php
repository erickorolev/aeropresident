<?php
function createFromQuote($ws_entity){
    // WS id
    $ws_id = $ws_entity->getId();
    $module = $ws_entity->getModuleName();
    if (empty($ws_id) || empty($module)) {
        return;
    }

    // CRM id
    $crmid = (int) vtws_getCRMEntityId($ws_id);
    if ($crmid <= 0) {
        return;
    }

    //получение объекта со всеми данными о текущей записи Модуля "SalesOrder"
    $soInstance = Vtiger_Record_Model::getInstanceById($crmid);

    //получение id Quote
    $quId = (int) $soInstance->get('quote_id');

    if($quId) {
        $relInstance = new Flights_RelationMoving_Model($quId, $crmid, 'cf_salesorder_id');
        $relInstance->copy();
    }
}