<?php

class ControlLayoutFields_Delete_Action extends Vtiger_Action_Controller
{
    public function process(Vtiger_Request $request)
    {
        $recordId = $request->get("record");
        $moduleName = $request->get("selected_module");
        $page = $request->get("page");
        $adb = PearDatabase::getInstance();
        if (!empty($recordId)) {
            $sql = "DELETE FROM `vte_control_layout_fields`  WHERE id = ?";
            $adb->pquery($sql, array($recordId));
        }
        header("Location: index.php?module=ControlLayoutFields&parent=Settings&view=ListAll&ModuleFilter=" . $moduleName . "&page=" . $page);
    }
    public function validateRequest(Vtiger_Request $request)
    {
        $request->validateWriteAccess();
    }
}

?>