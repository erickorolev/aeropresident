<?php

class ControlLayoutFields_ActionAjax_Action extends Vtiger_IndexAjax_View
{
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod("checkCLFForModule");
        $this->exposeMethod("getFieldValue");        

    }    
    public function process(Vtiger_Request $request)
    {
        $mode = $request->getMode();
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }
    public function checkCLFForModule(Vtiger_Request $request)
    {
        global $adb;
        $current_module = $request->get("current_module");
        $extension = $request->get("extension");
        if (!empty($current_module)) {
            $db = PearDatabase::getInstance();
            $result = $db->pquery("SELECT * FROM vte_control_layout_fields c\n                                    INNER JOIN vte_control_layout_fields_task t ON t.clf_id = c.id\n                                    WHERE c.module = ?", array($current_module));
            $noOfrecord = $db->num_rows($result);
            $clf_info = array();
            $record_info = array();
            if (0 < $noOfrecord) {
                for ($i = 0; $i < $noOfrecord; $i++) {
                    $condition = $db->query_result($result, $i, "condition");
                    $condition = json_decode(html_entity_decode($condition));
                    $actions = $db->query_result($result, $i, "actions");
                    $clf_info[] = array("condition" => $this->splitCondition($condition), "actions" => json_decode(html_entity_decode($actions)));
                }
                $record_id = $request->get("record_id");
                if (!empty($record_id)) {
                    $current_record_model = Vtiger_Record_Model::getInstanceById($record_id);
                    $record_info = $current_record_model->getData();
                    if (isset($record_info["related_to"]) && 0 < $record_info["related_to"]) {
                        $related_record_model = Vtiger_Record_Model::getInstanceById($record_info["related_to"]);
                        if ($related_record_model->getModule()->getName() == "Accounts") {
                            $record_info["accountname"] = $related_record_model->get("accountname");
                        }
                    }
                }
            }
            if (!empty($extension) && $extension == "VTEButton") {
                $vtebuttons_id = $request->get("vtebuttons_id");
                $sql = "SELECT automated_update_field,automated_update_value FROM `vte_buttons_settings` WHERE id = ?;";
                $re = $adb->pquery($sql, array($vtebuttons_id));
                if (0 < $adb->num_rows($re)) {
                    $automated_update_field = $adb->query_result($re, 0, "automated_update_field");
                    $automated_update_value = $adb->query_result($re, 0, "automated_update_value");
                    if (!empty($automated_update_field) && !empty($automated_update_value)) {
                        $record_info[$automated_update_field] = $automated_update_value;
                    }
                }
            }
            global $current_user;
            $roleid = $current_user->roleid;
            $response = new Vtiger_Response();
            $response->setResult(array("clf_info" => $clf_info, "record_info" => $record_info, "role_id" => $roleid));
            $response->emit();
        }
    }
    public function splitCondition($conditions)
    {
        $allConditions = array();
        $anyConditions = array();
        if (!empty($conditions)) {
            foreach ($conditions as $p_index => $p_info) {
                foreach ($p_info->columns as $index => $info) {
                    $columnname = $info->columnname;
                    $columnname = explode(":", $columnname);
                    $value = $info->value;
                    $value = decode_html($value);
                    if ($info->groupid == 0) {
                        $allConditions[] = array("columnname" => $columnname[2], "comparator" => $info->comparator, "value" => $value);
                    } else {
                        $anyConditions[] = array("columnname" => $columnname[2], "comparator" => $info->comparator, "value" => $value);
                    }
                }
            }
        }
        return array("all" => $allConditions, "any" => $anyConditions);
    }
    public function getFieldValue(Vtiger_Request $request)
    {
        $module = $request->get("current_module");
        $field_name = $request->get("field_name");
        $record_id = $request->get("record_id");
        $record_model = Vtiger_Record_Model::getInstanceById($record_id, $module);
        $field_value = $record_model->get($field_name);
        $response = new Vtiger_Response();
        $response->setResult(array("value" => $field_value));
        $response->emit();
    }
}

?>