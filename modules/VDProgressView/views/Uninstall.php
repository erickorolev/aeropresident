<?php

include_once "vtlib/Vtiger/Module.php";
class VDProgressView_Uninstall_View extends Settings_Vtiger_Index_View
{
    public function process(Vtiger_Request $request)
    {
        global $adb;
        echo "<div class=\"container-fluid\">\r\n                <div class=\"widget_header row-fluid\">\r\n                    <h3>Progress Bar/Bills</h3>\r\n                </div>\r\n                <hr>";
        $module = Vtiger_Module::getInstance("VDProgressView");
        if ($module) {
            $module->delete();
        }
        $message = $this->removeData();
        echo $message;
        $res_template = $this->delete_folder("layouts/v7/modules/VDProgressView");
        $res_template = $this->delete_folder("layouts/vlayout/modules/VDProgressView");
        echo "&nbsp;&nbsp;- Delete VDProgressView template folder";
        if ($res_template) {
            echo " - DONE";
        } else {
            echo " - <b>ERROR</b>";
        }
        echo "<br>";
        $res_module = $this->delete_folder("modules/VDProgressView");
        echo "&nbsp;&nbsp;- Delete Progress Bar/Bills module folder";
        if ($res_module) {
            echo " - DONE";
        } else {
            echo " - <b>ERROR</b>";
        }
        echo "<br>";
        $adb->pquery("DELETE FROM vtiger_settings_field WHERE `name` = ?", array("Progress View"));
        echo "Module was Uninstalled.</div>";
    }
    public function delete_folder($tmp_path)
    {
        if (!is_writeable($tmp_path) && is_dir($tmp_path)) {
            chmod($tmp_path, 511);
        }
        $handle = opendir($tmp_path);
        while ($tmp = readdir($handle)) {
            if ($tmp != ".." && $tmp != "." && $tmp != "") {
                if (is_writeable($tmp_path . DS . $tmp) && is_file($tmp_path . DS . $tmp)) {
                    unlink($tmp_path . DS . $tmp);
                } else {
                    if (!is_writeable($tmp_path . DS . $tmp) && is_file($tmp_path . DS . $tmp)) {
                        chmod($tmp_path . DS . $tmp, 438);
                        unlink($tmp_path . DS . $tmp);
                    }
                }
                if (is_writeable($tmp_path . DS . $tmp) && is_dir($tmp_path . DS . $tmp)) {
                    $this->delete_folder($tmp_path . DS . $tmp);
                } else {
                    if (!is_writeable($tmp_path . DS . $tmp) && is_dir($tmp_path . DS . $tmp)) {
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
        $sql = "DROP TABLE `vd_progressview_settings`;";
        $result = $adb->pquery($sql, array());
        $message .= "&nbsp;&nbsp;- Delete Progress Bar tables";
        if ($result) {
            $message .= " - DONE";
        } else {
            $message .= " - <b>ERROR</b>";
        }
        $message .= "<br>";
        return $message;
    }
}

?>