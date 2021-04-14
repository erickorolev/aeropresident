<?php

class RelatedBlocksLists_Uninstall_View extends Settings_Vtiger_Index_View
{
    public function process(Vtiger_Request $request)
    {
        global $adb;
        echo "<div class=\"container-fluid\">\r\n                <div class=\"widget_header row-fluid\">\r\n                    <h3>Related Blocks & Lists</h3>\r\n                </div>\r\n                <hr>";
        $module = Vtiger_Module::getInstance("RelatedBlocksLists");
        if ($module) {
            $module->delete();
        }
        $message = $this->removeData();
        echo $message;
        $res_template_v6 = $this->delete_folder("layouts/vlayout/modules/RelatedBlocksLists");
        $res_template_v7 = $this->delete_folder("layouts/v7/modules/RelatedBlocksLists");
        echo "&nbsp;&nbsp;- Delete Related Blocks & Lists template folder";
        if ($res_template_v6 || $res_template_v7) {
            echo " - DONE";
        } else {
            echo " - <b>ERROR</b>";
        }
        echo "<br>";
        $res_module = $this->delete_folder("modules/RelatedBlocksLists");
        echo "&nbsp;&nbsp;- Delete Related Blocks & Lists module folder";
        if ($res_module) {
            echo " - DONE";
        } else {
            echo " - <b>ERROR</b>";
        }
        echo "<br>Module was Uninstalled.</div>";
    }
    public function delete_folder($tmp_path)
    {
        if (!is_dir($tmp_path)) {
            return false;
        }
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
        $adb->pquery("DELETE FROM vtiger_settings_field WHERE `name` = ?", array("Related Blocks & Lists"));
        $adb->pquery("DELETE FROM `vtiger_eventhandlers` WHERE handler_class =?", array("RelatedBlocksListsHandler"));
        $sql = "DROP TABLE `relatedblockslists_settings`;";
        $result = $adb->pquery($sql, array());
        $sql = "DROP TABLE `relatedblockslists_blocks`;";
        $result = $adb->pquery($sql, array());
        $sql = "DROP TABLE `relatedblockslists_fields`;";
        $result = $adb->pquery($sql, array());
        $message .= "&nbsp;&nbsp;- Delete Related Blocks & Lists tables";
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