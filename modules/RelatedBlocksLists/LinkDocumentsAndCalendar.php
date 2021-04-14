<?php

chdir("../../");
set_include_path("../../");
require_once "include/utils/utils.php";
require_once "vtlib/Vtiger/Module.php";
require_once "vtlib/Vtiger/Block.php";
require_once "vtlib/Vtiger/Field.php";
$db = PearDatabase::getInstance();
$parentTabid = getTabid("Calendar");
$sql = "SELECT * FROM `vtiger_relatedlists` WHERE tabid=? AND label='Documents'";
$res = $db->pquery($sql, array($parentTabid));
if ($db->num_rows($res) == 0) {
    $parentModule = Vtiger_Module::getInstance("Calendar");
    $childModule = Vtiger_Module::getInstance("Documents");
    $parentModule->setRelatedList($childModule, "Documents", array("ADD", "SELECT"), "get_attachments");
    echo "<br>Added related list Documents to module Calendar";
} else {
    echo "<br>Related list Documents existed on module Calendar";
}
$parentTabid = getTabid("Events");
$sql = "SELECT * FROM `vtiger_relatedlists` WHERE tabid=? AND label='Documents'";
$res = $db->pquery($sql, array($parentTabid));
if ($db->num_rows($res) == 0) {
    $parentModule = Vtiger_Module::getInstance("Events");
    $childModule = Vtiger_Module::getInstance("Documents");
    $parentModule->setRelatedList($childModule, "Documents", array("ADD", "SELECT"), "get_attachments");
    echo "<br>Added related list Documents to module Events";
} else {
    echo "<br>Related list Documents existed on module Events";
}
$parentTabid = getTabid("Documents");
$sql = "SELECT * FROM `vtiger_relatedlists` WHERE tabid=? AND label='Calendars'";
$res = $db->pquery($sql, array($parentTabid));
if ($db->num_rows($res) == 0) {
    $parentModule = Vtiger_Module::getInstance("Documents");
    $childModule = Vtiger_Module::getInstance("Calendar");
    $parentModule->setRelatedList($childModule, "Calendars", array("ADD", "SELECT"), "get_activities");
    echo "<br>Added related list Calendars to module Documents";
} else {
    echo "<br>Related list Calendars existed on module Documents";
}
$parentTabid = getTabid("Documents");
$sql = "SELECT * FROM `vtiger_relatedlists` WHERE tabid=? AND label='Events'";
$res = $db->pquery($sql, array($parentTabid));
if ($db->num_rows($res) == 0) {
    $parentModule = Vtiger_Module::getInstance("Documents");
    $childModule = Vtiger_Module::getInstance("Events");
    $parentModule->setRelatedList($childModule, "Events", array("ADD", "SELECT"), "get_activities");
    echo "<br>Added related list Events to module Documents";
} else {
    echo "<br>Related list Events existed on module Documents";
}

?>