<?php

class VDProgressView_SaveProgress_Action extends Vtiger_Action_Controller
{
    public function checkPermission(Vtiger_Request $request)
    {
    }
    public function process(Vtiger_Request $request)
    {
        global $adb;
        $module = $request->get("module");
        $record = $request->get("record");
        $custom_module = $request->get("custom_module");
        $confirm = $request->get('confirm');
        if ($confirm == 'on') {
            $confirm = 1;
        } else {
            $confirm = 0;
        }
        $active_val = 0;
        $active = $request->get("active");
        if ($active == "Active") {
            $active_val = 1;
        }
        $field_name = $request->get("field_name");
        if ($request->get('listColor') == 'on') {
            $listColor = 1;
        } else {
            $listColor = 0;
        }
        $VDModuleModel = Vtiger_Module_Model::getInstance($module);
        $redirectUrl = $VDModuleModel->getSettingURL();
        if (!empty($custom_module)) {
            if (0 < $record) {
                $sql = "UPDATE `vd_progressview_settings` SET module=?,field_name=?,active=?,listColor=?,confirm=? WHERE id=" . $record;
            } else {
                $sql = "INSERT INTO vd_progressview_settings (\r\n                    module,\r\n                    field_name,                  \r\n                    active, listColor, confirm\r\n                )\r\n                VALUES\r\n                    (?, ?, ?, ?, ?)";
            }
        }
        $adb->pquery($sql, array($custom_module, $field_name, $active_val, $listColor, $confirm));
        header("Location: " . $redirectUrl);
    }
}

?>