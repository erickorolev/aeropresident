<?php

class ControlLayoutFields_Uninstall_View extends Settings_Vtiger_Index_View
{
    public function process(Vtiger_Request $request)
    {
        global $adb;
        global $vtiger_current_version;
        echo "<div class=\"container-fluid\">\r\n                <div class=\"widget_header row-fluid\">\r\n                    <h3>Conditional Layouts</h3>\r\n                </div>\r\n                <hr>";
        $module = Vtiger_Module::getInstance("ControlLayoutFields");
        if ($module) {
            $module->delete();
        }
        $message = $this->removeData();
        echo $message;
        if (version_compare($vtiger_current_version, "7.0.0", "<")) {
            $template_folder = "layouts/vlayout";
        } else {
            $template_folder = "layouts/v7";
        }
        $link1 = $template_folder . "/modules/ControlLayoutFields";
        $res_template = $this->delete_folder($link1);
        echo "&nbsp;&nbsp;- Delete Conditional Layouts template folder";
        if ($res_template) {
            echo " - DONE";
        } else {
            echo " - <b>ERROR</b>";
        }
        echo "<br>";
        $res_module = $this->delete_folder("modules/ControlLayoutFields");
        echo "&nbsp;&nbsp;- Delete Conditional Layouts module folder";
        if ($res_module) {
            echo " - DONE";
        } else {
            echo " - <b>ERROR</b>";
        }
        echo "<br>";
        if (version_compare($vtiger_current_version, "7.0.0", "<")) {
            $folderLayoutNeedToDelete = "layouts/v7";
        } else {
            $folderLayoutNeedToDelete = "layouts/vlayout";
        }
        $this->delete_folder($folderLayoutNeedToDelete);
    }
    public function delete_folder($tmp_path)
    {
        if (!is_writeable($tmp_path) && is_dir($tmp_path) && isFileAccessible($tmp_path)) {
            chmod($tmp_path, 511);
        }
        $handle = opendir($tmp_path);
        while ($tmp = readdir($handle)) {
            if ($tmp != ".." && $tmp != "." && $tmp != "") {
                if (is_writeable($tmp_path . DS . $tmp) && is_file($tmp_path . DS . $tmp) && isFileAccessible($tmp_path)) {
                    unlink($tmp_path . DS . $tmp);
                } else {
                    if (!is_writeable($tmp_path . DS . $tmp) && is_file($tmp_path . DS . $tmp) && isFileAccessible($tmp_path)) {
                        chmod($tmp_path . DS . $tmp, 438);
                        unlink($tmp_path . DS . $tmp);
                    }
                }
                if (is_writeable($tmp_path . DS . $tmp) && is_dir($tmp_path . DS . $tmp) && isFileAccessible($tmp_path)) {
                    $this->delete_folder($tmp_path . DS . $tmp);
                } else {
                    if (!is_writeable($tmp_path . DS . $tmp) && is_dir($tmp_path . DS . $tmp) && isFileAccessible($tmp_path)) {
                        chmod($tmp_path . DS . $tmp, 511);
                        $this->delete_folder($tmp_path . DS . $tmp);
                    }
                }
            }
        }
        closedir($handle);
        rmdir($tmp_path);
        if (!is_dir($tmp_path)) {
            return true;
        }
        return false;
    }
    public function removeData()
    {
        global $adb;
        $message = "";
        $sql = "DROP TABLE `vte_control_layout_fields`;";
        $result = $adb->pquery($sql, array());
        $sql = "DROP TABLE `vte_control_layout_fields_task`;";
        $result = $adb->pquery($sql, array());
        $message .= "&nbsp;&nbsp;- Delete Conditional Layouts tables";
        if ($result) {
            $message .= " - DONE";
        } else {
            $message .= " - <b>ERROR</b>";
        }
        $message .= "<br>";
        $adb->pquery("DELETE FROM vtiger_settings_field WHERE `name` = ?", array("Conditional Layouts"));
        return $message;
    }
}

?>