<?php

class VDProgressView_ActionAjax_Action extends Vtiger_Action_Controller
{
    public function checkPermission(Vtiger_Request $request)
    {
    }
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod("selectModule");
        $this->exposeMethod("UpdateStatus");
        $this->exposeMethod("ChangeProgressView");
        $this->exposeMethod("DeleteRecord");        
    }
    public function process(Vtiger_Request $request)
    {
        $mode = $request->get("mode");
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }
    public function selectModule(Vtiger_Request $request)
    {
        $moduleSelected = $request->get("moduleSelected");
        $module = $request->get("module");
        $moduleModel = Vtiger_Module_Model::getInstance($moduleSelected);
        $recordStructureModel = Vtiger_RecordStructure_Model::getInstanceForModule($moduleModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_FILTER);
        $recordStructure = $recordStructureModel->getStructure();
        foreach ($recordStructure as $blocks) {
            foreach ($blocks as $fieldLabel => $fieldValue) {
                $fieldModels[$fieldLabel] = $fieldValue;
            }
        }
        $moduleModel = Vtiger_Module_Model::getInstance($module);
        $response = new Vtiger_Response();
        $response->setResult($fieldModels);
        $response->emit();
    }
    public function UpdateStatus(Vtiger_Request $request)
    {
        global $adb;
        $record = $request->get("record");
        $status = $request->get("status");
        $progressview_module = $request->get("progressview_module");
        if ($status == "on") {
            $status = 1;
        } else {
            $status = 0;
        }
        $sql = "UPDATE `vd_progressview_settings` SET active=? WHERE id=?";
        $adb->pquery($sql, array($status, $record));
        if ($status == 1) {
            $sql = "UPDATE `vd_progressview_settings` SET active= 0 WHERE module= ? AND id <> ?";
            $adb->pquery($sql, array($progressview_module, $record));
        }
        $response = new Vtiger_Response();
        $response->setResult("success");
        $response->emit();
    }
    public function ChangeProgressView(Vtiger_Request $request)
    {
        global $adb;
        $record = $request->get("record");
        $moduleSelected = $request->get("moduleSelected");
        $newValue = $request->get("newValue");
        $fieldName = $request->get("fieldName");
        $fieldLabel = $request->get("fieldLabel");
        $recordModel = Vtiger_Record_Model::getInstanceById($record, $moduleSelected);
        $recordModel->set($fieldName, $newValue);
        $recordModel->set("mode", "edit");
        $_REQUEST["ajaxaction"] = "DETAILVIEW";
        $recordModel->save();
        $response = new Vtiger_Response();
        $response->setResult(array("success" => 1, "fieldLabel" => vtranslate($fieldLabel, $moduleSelected), "value" => vtranslate($newValue, $moduleSelected)));
        $response->emit();
    }
    public function DeleteRecord(Vtiger_Request $request)
    {
        global $adb;
        $record = $request->get("record");
        $sql = "DELETE FROM `vd_progressview_settings`  WHERE id=?";
        $adb->pquery($sql, array($record));
        $response = new Vtiger_Response();
        $response->setResult("success");
        $response->emit();
    }
}

?>