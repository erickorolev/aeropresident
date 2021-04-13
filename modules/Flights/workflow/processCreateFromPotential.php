<?php
function createFromPotential($ws_entity){
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

    //получение объекта со всеми данными о текущей записи Модуля "Quote"
    $quoteInstance = Vtiger_Record_Model::getInstanceById($crmid);

    //получение id Сделки
    $potId = (int) $quoteInstance->get('potential_id');

    if($potId) {
        $relInstance = new Flights_RelationMoving_Model($crmid, $potId, 'cf_quotes_id');
        $relInstance->copy();
    }
}