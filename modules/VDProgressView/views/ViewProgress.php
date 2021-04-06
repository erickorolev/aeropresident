<?php

class VDProgressView_ViewProgress_View extends Vtiger_IndexAjax_View
{
    public function __construct()
    {
        parent::__construct();        
    }    
    public function process(Vtiger_Request $request)
    {
        $moduleSelected = $request->get("moduleSelected");
        $module = "VDProgressView";
        $record_id = $request->get("record");
        $viewer = $this->getViewer($request);
        global $adb;
        $sql = "SELECT * FROM `vd_progressview_settings` WHERE module='" . $moduleSelected . "' AND active = 1";
        $results = $adb->pquery($sql, array());
        $status_array = array();
        $field_value = "";
        $field_name = "";
        if (0 < $adb->getRowCount($results)) {
            $current_record_model = Vtiger_Record_Model::getInstanceById($record_id);
            $field_name = $adb->query_result($results, 0, "field_name");
            $module_name = $adb->query_result($results, 0, "module");
            $needConfirm = $adb->query_result($results, 0, "confirm");
            if ($adb->query_result($results, 0, "listcolor") > 0) {
                $listColor = true;
            } else {
                $listColor = false;
            }
            $module_model = Vtiger_Module_Model::getInstance($module_name);
            $field_model = Vtiger_Field_Model::getInstance($field_name, $module_model);
            $field_label = $field_model->get("label");
            $field_value = $current_record_model->getDisplayValue($field_name, $record_id);
            $sql = "SELECT * FROM `vtiger_" . $field_name . "`  ORDER BY sortorderid";
            $results = $adb->pquery($sql, array());
            $status_array = array();
            while ($row = $adb->fetchByAssoc($results)) {
                $status_array[] = $row[$field_name];
            }
        }
        if (0 < count($status_array)) {
            $viewer->assign("CURRENT_STATUS", $field_value);
            $viewer->assign("FIELD_NAME", $field_name);
            $viewer->assign("FIELD_LABEL", $field_label);
            $viewer->assign("FIELD_MODEL", $field_model);
            $viewer->assign("LIST_COLOR", $listColor);
            $viewer->assign("PROGRESSBARS", $status_array);
            $viewer->assign('CONFIRM', $needConfirm);
            $viewer->assign("MODULE_NAME", $module_name);
            echo $viewer->view("ViewProgress.tpl", $module, true);
        } else {
            echo "";
        }
    }
}

?>