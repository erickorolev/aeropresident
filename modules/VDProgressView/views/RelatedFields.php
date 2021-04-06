<?php

class VDProgressView_RelatedFields_View extends Vtiger_IndexAjax_View
{
    public function process(Vtiger_Request $request)
    {
        $moduleSelected = $request->get("moduleSelected");
        $module = $request->get("module");
        $record = $request->get("record");
        $moduleModel = Vtiger_Module_Model::getInstance($module);
        $selectedModuleModel = Vtiger_Module_Model::getInstance($moduleSelected);
        $viewer = $this->getViewer($request);
        if ($record) {
            $Entries = $moduleModel->getlistViewEntries("id=" . $record);
            $recordentries = $Entries[0];
            $viewer->assign("RECORDENTRIES", $recordentries);
        }
        $viewer->assign("SELECTED_MODULE_FIELDS", $selectedModuleModel->getFields());
        $viewer->assign("SELECTED_MODULE_NAME", $moduleSelected);
        echo $viewer->view("RelatedFields.tpl", $module, true);
    }
}

?>