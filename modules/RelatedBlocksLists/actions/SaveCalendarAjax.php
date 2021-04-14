<?php

class RelatedBlocksLists_SaveCalendarAjax_Action extends Vtiger_SaveAjax_Action
{
    public function __construct()
    {
        parent::__construct();
    }    
    public function process(Vtiger_Request $request)
    {
        $request->set("module", $request->get("rel_module"));
        $recordModel = $this->saveRecord($request);
        $fieldModelList = $recordModel->getModule()->getFields();
        $result = array();
        foreach ($fieldModelList as $fieldName => $fieldModel) {
            $recordFieldValue = $recordModel->get($fieldName);
            if (is_array($recordFieldValue) && $fieldModel->getFieldDataType() == "multipicklist") {
                $recordFieldValue = implode(" |##| ", $recordFieldValue);
            }
            $fieldValue = $displayValue = Vtiger_Util_Helper::toSafeHTML($recordFieldValue);
            if ($fieldModel->getFieldDataType() !== "currency") {
                $displayValue = $fieldModel->getDisplayValue($fieldValue, $recordModel->getId(), $recordModel);
            }
            $result[$fieldName] = array("value" => $fieldValue, "display_value" => $displayValue);
        }
        if ($request->get("field") === "firstname" && in_array($request->getModule(), array("Contacts", "Leads"))) {
            $salutationType = $recordModel->getDisplayValue("salutationtype");
            $firstNameDetails = $result["firstname"];
            $firstNameDetails["display_value"] = $salutationType . " " . $firstNameDetails["display_value"];
            if ($salutationType != "--None--") {
                $result["firstname"] = $firstNameDetails;
            }
        }
        $result["_recordLabel"] = $recordModel->getName();
        $result["_recordId"] = $recordModel->getId();
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult($result);
        $response->emit();
    }
    public function saveRecord($request)
    {
        $recordModel = $this->getRecordModelFromRequest($request);
        $fieldName = $request->get("field");
        $_REQUEST[$fieldName] = $request->get("value");
        $_moduleModel = new RelatedBlocksLists_Module_Model();
        $recordModel = $_moduleModel->setDataForCalendarRecord($recordModel, $_REQUEST);
        $recordModel->save();
        if ($request->get("relationOperation")) {
            $parentModuleName = $request->get("sourceModule");
            $parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
            $parentRecordId = $request->get("sourceRecord");
            $relatedModule = $recordModel->getModule();
            $relatedRecordId = $recordModel->getId();
            $relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
            $relationModel->addRelation($parentRecordId, $relatedRecordId);
        }
        if ($request->get("imgDeleted")) {
            $imageIds = $request->get("imageid");
            foreach ($imageIds as $imageId) {
                $status = $recordModel->deleteImage($imageId);
            }
        }
        return $recordModel;
    }
}

?>