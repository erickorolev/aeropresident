<?php

class ControlLayoutFields_TasksList_View extends Vtiger_Index_View
{
    public function process(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $qualifiedModuleName = "ControlLayoutFields";
        $recordId = $request->get("record");
        $clfModel = ControlLayoutFields_Record_Model::getInstanceById($recordId, "ControlLayoutFields");
        $viewer->assign("WORKFLOW_MODEL", $clfModel);
        $viewer->assign("TASK_LIST", $clfModel->getTasks());
        $viewer->assign("MODULE", $moduleName);
        $viewer->assign("RECORD", $recordId);
        $viewer->assign("QUALIFIED_MODULE", $qualifiedModuleName);
        $viewer->assign("SELECTED_MODULE", $request->get("selected_module_name"));
        $viewer->view("TasksList.tpl", $qualifiedModuleName);
    }
}

?>