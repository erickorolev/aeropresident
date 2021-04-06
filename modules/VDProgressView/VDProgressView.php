<?php

require_once "data/CRMEntity.php";
require_once "data/Tracker.php";
require_once "vtlib/Vtiger/Module.php";
class VDProgressView extends CRMEntity
{
    /**
     * Invoked when special actions are performed on the module.
     * @param String Module name
     * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
     */
    public function vtlib_handler($modulename, $event_type)
    {
        if ($event_type == "module.postinstall") {
            self::addWidgetTo();
            self::iniData();
            self::resetValid();
        } else {
            if ($event_type == "module.disabled") {
                self::removeWidgetTo();
            } else {
                if ($event_type == "module.enabled") {
                    self::addWidgetTo();
                } else {
                    if ($event_type == "module.preuninstall") {
                        self::removeWidgetTo();
                        self::removeValid();
                    } else {
                        if ($event_type == "module.preupdate") {
                        } else {
                            if ($event_type == "module.postupdate") {
                                self::removeWidgetTo();
                                self::addWidgetTo();
                                self::iniData();
                                self::resetValid();
                            }
                        }
                    }
                }
            }
        }
    }
    public static function iniData()
    {
        global $adb;
    }
    /**
     * Add header script to other module.
     * @return unknown_type
     */
    public static function addWidgetTo()
    {
        global $adb;
        global $vtiger_current_version;
        $widgetType = "HEADERSCRIPT";
        $widgetName = "VDProgressViewJs";
        if (version_compare($vtiger_current_version, "7.0.0", "<")) {
            $template_folder = "layouts/vlayout";
        } else {
            $template_folder = "layouts/v7";
        }
        $link = $template_folder . "/modules/VDProgressView/resources/VDProgressView.js";
        include_once "vtlib/Vtiger/Module.php";
        $moduleNames = array("VDProgressView");
        foreach ($moduleNames as $moduleName) {
            $module = Vtiger_Module::getInstance($moduleName);
            if ($module) {
                $module->addLink($widgetType, $widgetName, $link);
            }
        }
        $rs = $adb->pquery("SELECT * FROM `vtiger_settings_field` WHERE `name` = ?", array("Progress View"));
        if ($adb->num_rows($rs) == 0) {
            $max_id = $adb->getUniqueID("vtiger_settings_field");
            $adb->pquery("INSERT INTO `vtiger_settings_field` (`fieldid`, `blockid`, `name`, `description`, `linkto`, `sequence`) VALUES (?, ?, ?, ?, ?, ?)", array($max_id, "4", "Progress View", "Settings area for Progress View", "index.php?module=VDProgressView&parent=Settings&view=Settings", $max_id));
        }
        $rs = $adb->pquery("SELECT * FROM `vtiger_ws_entity` WHERE `name` = ?", array("VDProgressView"));
        if ($adb->num_rows($rs) == 0) {
            $adb->pquery("INSERT INTO `vtiger_ws_entity` (`name`, `handler_path`, `handler_class`, `ismodule`)\r\n            VALUES (?, 'include/Webservices/VtigerModuleOperation.php', 'VtigerModuleOperation', '1');", array("VDProgressView"));
        }
    }
    public static function removeWidgetTo()
    {
        global $adb;
        global $vtiger_current_version;
        $widgetType = "HEADERSCRIPT";
        $widgetName = "VDProgressViewJs";
        if (version_compare($vtiger_current_version, "7.0.0", "<")) {
            $template_folder = "layouts/vlayout";
        } else {
            $template_folder = "layouts/v7";
        }
        $link = $template_folder . "/modules/VDProgressView/resources/VDProgressView.js";
        include_once "vtlib/Vtiger/Module.php";
        $moduleNames = array("VDProgressView");
        foreach ($moduleNames as $moduleName) {
            $module = Vtiger_Module::getInstance($moduleName);
            if ($module) {
                $module->deleteLink($widgetType, $widgetName, $link);
            }
        }
        $adb->pquery("DELETE FROM vtiger_settings_field WHERE `name` = ?", array("Progress View"));
    }
    public static function removeValid()
    {
        global $adb;        
    }
    public static function resetValid()
    {
        global $adb;        
    }
}

?>