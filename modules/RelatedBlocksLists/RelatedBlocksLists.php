<?php

require_once "data/CRMEntity.php";
require_once "data/Tracker.php";
require_once "vtlib/Vtiger/Module.php";
class RelatedBlocksLists extends CRMEntity
{
    public function vtlib_handler($modulename, $event_type)
    {
        if ($event_type == "module.postinstall") {
            $this::addWidgetTo();
            $this::addEventHandle();
            $this::checkEnable();
            $this::resetValid();
        } else {
            if ($event_type == "module.disabled") {
                $this::removeWidgetTo();
                $this::removeEventHandle();
            } else {
                if ($event_type == "module.enabled") {
                    $this::addWidgetTo();
                    $this::addEventHandle();
                } else {
                    if ($event_type == "module.preuninstall") {
                        $this::removeEventHandle();
                        $this::removeWidgetTo();
                        $this::removeValid();
                    } else {
                        if ($event_type != "module.preupdate") {
                            if ($event_type == "module.postupdate") {
                                $this::checkEnable();
                                $this::removeWidgetTo();
                                $this::addWidgetTo();
                                $this::removeEventHandle();
                                $this::addEventHandle();
                                $this::convertAfterBlockValue();
                                $this::resetValid();
                            }
                        }
                    }
                }
            }
        }
    }
    public static function convertAfterBlockValue()
    {
        global $adb;
        $query = "UPDATE relatedblockslists_blocks AS RB\r\n                    INNER JOIN vtiger_blocks AS B ON RB.after_block = B.blocklabel\r\n                    INNER JOIN vtiger_tab AS T ON RB.module = T.`name` AND T.tabid = B.tabid\r\n                    SET RB.after_block = B.blockid";
        $adb->pquery($query, []);
    }
    public static function resetValid()
    {
        global $adb;
        $adb->pquery("DELETE FROM `vte_modules` WHERE module=?;", ["RelatedBlocksLists"]);
        $adb->pquery("INSERT INTO `vte_modules` (`module`, `valid`) VALUES (?, ?);", ["RelatedBlocksLists", "0"]);
    }
    public static function removeValid()
    {
        global $adb;
        $adb->pquery("DELETE FROM `vte_modules` WHERE module=?;", ["RelatedBlocksLists"]);
    }
    public static function checkEnable()
    {
        global $adb;
        $rs = $adb->pquery("SELECT `enable` FROM `relatedblockslists_settings`;", []);
        if ($adb->num_rows($rs) == 0) {
            $adb->pquery("INSERT INTO `relatedblockslists_settings` (`enable`) VALUES ('0');", []);
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
        $adb->pquery("INSERT INTO `vtiger_settings_field` (`fieldid`, `blockid`, `name`, `description`, `linkto`, `sequence`) VALUES (?, ?, ?, ?, ?, ?)", [$max_id, "4", "Related Blocks & Lists", "Settings area for Related Blocks & Lists", "index.php?module=RelatedBlocksLists&parent=Settings&view=Settings", $max_id]);
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
        $adb->pquery("DELETE FROM vtiger_settings_field WHERE `name` = ?", ["Related Blocks & Lists"]);
    }
}

?>