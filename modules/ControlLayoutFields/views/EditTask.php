<?php

class ControlLayoutFields_EditTask_View extends Vtiger_Index_View
{
    public function process(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $selected_moduleName = $request->get("selected_module");
        $qualifiedModuleName = "ControlLayoutFields";
        $task_id = $request->get("task_id");
        $clf_id = $request->get("clf_id");
        $db = PearDatabase::getInstance();
        $result = $db->pquery("SELECT * FROM vte_control_layout_fields_task\n\t\t\t\t\t\t\t\tWHERE id = ? LIMIT 0,1", array($task_id));
        $noOfTask = $db->num_rows($result);
        $task_info = array();
        if (0 < $noOfTask) {
            $task_info["name"] = $db->query_result($result, 0, "name");
            $task_info["active"] = $db->query_result($result, 0, "active");
            $actions = $db->query_result($result, 0, "actions");
            $task_info["actions"] = json_decode(html_entity_decode($actions));
        }
        $selectedModuleModel = Vtiger_Module_Model::getInstance($selected_moduleName);
        $field_options = array("mandatory" => "Make Field Mandatory", "read_only" => "Make Field Read Only", "hide" => "Hide Field");
        $viewer->assign("TASK_INFO", $task_info);
        $viewer->assign("SELECTED_MODULE", $selected_moduleName);
        $viewer->assign("SELECTED_MODULE_MODEL", $selectedModuleModel);
        $viewer->assign("FIELD_OPTIONS", $field_options);
        $viewer->assign("CLF_ID", $clf_id);
        $viewer->assign("TASK_ID", $task_id);
        $viewer->assign("MODULE", $moduleName);
        $viewer->assign("QUALIFIED_MODULE", $qualifiedModuleName);
        $viewer->view("EditTask.tpl", $qualifiedModuleName);
    }
}

?>