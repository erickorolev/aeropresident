<?php

require_once "data/CRMEntity.php";
require_once "data/Tracker.php";
require_once "vtlib/Vtiger/Module.php";
class RelatedBlocksLists extends CRMEntity
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
            self::addEventHandle();
            self::checkEnable();
            self::resetValid();
            self::createHandle($modulename);
        } else {
            if ($event_type == "module.disabled") {
                self::removeWidgetTo();
                self::removeEventHandle();
            } else {
                if ($event_type == "module.enabled") {
                    self::addWidgetTo();
                    self::addEventHandle();
                    self::createHandle($modulename);
                } else {
                    if ($event_type == "module.preuninstall") {
                        self::removeEventHandle();
                        self::removeWidgetTo();
                        self::removeValid();
                    } else {
                        if ($event_type == "module.preupdate") {
                            self::updateDefaultSetting();
                        } else {
                            if ($event_type == "module.postupdate") {
                                self::checkEnable();
                                self::removeWidgetTo();
                                self::addWidgetTo();
                                self::removeEventHandle();
                                self::addEventHandle();
                                self::convertAfterBlockValue();
                                self::resetValid();
                                self::createHandle($modulename);
                                self::updateDefaultSetting();
                                self::addFields();
                            }
                        }
                    }
                }
            }
        }
    }
    public function updateDefaultSetting()
    {
        global $adb;
        $default = "{\"chk_detail_view_icon\":1,\"chk_edit_view_icon\":1,\"chk_detail_edit_icon\":1,\"chk_edit_edit_icon\":1,\"chk_detail_delete_icon\":1,\"chk_edit_delete_icon\":1,\"chk_detail_add_btn\":1,\"chk_edit_view_add_btn\":1,\"chk_detail_select_btn\":1,\"chk_edit_select_btn\":1,\"chk_detail_inline_edit\":1,\"chk_edit_inline_edit\":1}";
        $query = "UPDATE relatedblockslists_blocks\r\n        SET customizable_options = " . $default . " WHERE customizable_options = '1' OR customizable_options = ''";
        $adb->pquery($query, array());
    }
    public static function convertAfterBlockValue()
    {
        global $adb;
        $query = "UPDATE relatedblockslists_blocks AS RB\r\n                    INNER JOIN vtiger_blocks AS B ON RB.after_block = B.blocklabel\r\n                    INNER JOIN vtiger_tab AS T ON RB.module = T.`name` AND T.tabid = B.tabid\r\n                    SET RB.after_block = B.blockid";
        $adb->pquery($query, array());
    }
    public static function resetValid()
    {
        global $adb;
        $adb->pquery("DELETE FROM `vte_modules` WHERE module=?;", array("RelatedBlocksLists"));
        $adb->pquery("INSERT INTO `vte_modules` (`module`, `valid`) VALUES (?, ?);", array("RelatedBlocksLists", "0"));
    }
    public static function removeValid()
    {
        global $adb;
        $adb->pquery("DELETE FROM `vte_modules` WHERE module=?;", array("RelatedBlocksLists"));
    }
    public static function checkEnable()
    {
        global $adb;
        $rs = $adb->pquery("SELECT `enable` FROM `relatedblockslists_settings`;", array());
        if ($adb->num_rows($rs) == 0) {
            $adb->pquery("INSERT INTO `relatedblockslists_settings` (`enable`) VALUES ('0');", array());
        }
    }
    public static function addEventHandle()
    {
        global $adb;
        $em = new VTEventsManager($adb);
        $em->registerHandler("vtiger.entity.aftersave", "modules/RelatedBlocksLists/RelatedBlocksListsHandler.php", "RelatedBlocksListsHandler");
    }
    public static function removeEventHandle()
    {
        global $adb;
        $em = new VTEventsManager($adb);
        $em->unregisterHandler("RelatedBlocksListsHandler");
    }
    /**
     * Add header script to other module.
     * @return unknown_type
     */
    public static function addWidgetTo()
    {
        global $adb;
        global $vtiger_current_version;
        include_once "vtlib/Vtiger/Module.php";
        $module = Vtiger_Module::getInstance("RelatedBlocksLists");
        if (version_compare($vtiger_current_version, "7.0.0", "<")) {
            $template_folder = "layouts/vlayout";
        } else {
            $template_folder = "layouts/v7";
        }
        if ($module) {
            $module->addLink("HEADERSCRIPT", "RelatedBlocksListsManagerJs", $template_folder . "/modules/RelatedBlocksLists/resources/Manager.js");
            $module->addLink("HEADERSCRIPT", "RelatedBlocksListsJs", $template_folder . "/modules/RelatedBlocksLists/resources/RelatedBlocksLists.js");
            $module->addLink("HEADERSCRIPT", "RelatedBlocksListsPopupJs", $template_folder . "/modules/RelatedBlocksLists/resources/Popup.js");
        }
        $max_id = $adb->getUniqueID("vtiger_settings_field");
        $adb->pquery("INSERT INTO `vtiger_settings_field` (`fieldid`, `blockid`, `name`, `description`, `linkto`, `sequence`) VALUES (?, ?, ?, ?, ?, ?)", array($max_id, "4", "Related Blocks & Lists", "Settings area for Related Blocks & Lists", "index.php?module=RelatedBlocksLists&parent=Settings&view=Settings", $max_id));
    }
    public static function removeWidgetTo()
    {
        global $adb;
        global $vtiger_current_version;
        include_once "vtlib/Vtiger/Module.php";
        $module = Vtiger_Module::getInstance("RelatedBlocksLists");
        if (version_compare($vtiger_current_version, "7.0.0", "<")) {
            $template_folder = "layouts/vlayout";
            $vtVersion = "vt6";
            $linkVT6 = $template_folder . "/modules/RelatedBlocksLists/resources/Manager.js";
            $linkVT6_2 = $template_folder . "/modules/RelatedBlocksLists/resources/RelatedBlocksLists.js";
            $linkVT6_3 = $template_folder . "/modules/RelatedBlocksLists/resources/Popup.js";
        } else {
            $template_folder = "layouts/v7";
            $vtVersion = "vt7";
        }
        if ($module) {
            $module->deleteLink("HEADERSCRIPT", "RelatedBlocksListsManagerJs", $template_folder . "/modules/RelatedBlocksLists/resources/Manager.js");
            $module->deleteLink("HEADERSCRIPT", "RelatedBlocksListsJs", $template_folder . "/modules/RelatedBlocksLists/resources/RelatedBlocksLists.js");
            $module->deleteLink("HEADERSCRIPT", "RelatedBlocksListsPopupJs", $template_folder . "/modules/RelatedBlocksLists/resources/Popup.js");
            if ($vtVersion != "vt6") {
                $module->deleteLink("HEADERSCRIPT", "RelatedBlocksListsManagerJs", $linkVT6);
                $module->deleteLink("HEADERSCRIPT", "RelatedBlocksListsJs", $linkVT6_2);
                $module->deleteLink("HEADERSCRIPT", "RelatedBlocksListsJs", $linkVT6_3);
            }
        }
        $adb->pquery("DELETE FROM vtiger_settings_field WHERE `name` = ?", array("Related Blocks & Lists"));
    }
    private function createHandle($moduleName)
    {
        global $adb;
        $em = new VTEventsManager($adb);
        $em->unregisterHandler("VTWorkflowEventHandler");
        $em->unregisterHandler((string) $moduleName . "Handler");
        $em->registerHandler("vtiger.entity.aftersave", "modules/" . $moduleName . "/" . $moduleName . "Handler.php", (string) $moduleName . "Handler");
        $em->registerHandler("vtiger.entity.aftersave", "modules/com_vtiger_workflow/VTEventHandler.inc", "VTWorkflowEventHandler", "", "[\"VTEntityDelta\"]");
        $em->registerHandler("vtiger.entity.afterrestore", "modules/com_vtiger_workflow/VTEventHandler.inc", "VTWorkflowEventHandler", "", "[]");
        $em->clearTriggerCache("vtiger.entity.aftersave");
    }
    public function checkColumnExist($tableName, $columnName)
    {
        global $adb;
        $sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = ? AND table_name = ? AND column_name = ?";
        $res = $adb->pquery($sql, array($adb->dbName, $tableName, $columnName));
        if (0 < $adb->num_rows($res)) {
            return true;
        }
        return false;
    }
    public static function addFields()
    {
        global $adb;
        if (!self::checkColumnExist("relatedblockslists_blocks", "advanced_query")) {
            $adb->pquery("ALTER TABLE `relatedblockslists_blocks` ADD COLUMN `advanced_query` text", array());
        }
    }
}

?>