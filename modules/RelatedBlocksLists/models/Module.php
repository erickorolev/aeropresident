<?php

class RelatedBlocksLists_Module_Model extends Vtiger_Module_Model
{
    /**
     * Dinh.Nguyen
     * Function to get Settings links
     * @return <Array>
     */
    public function getSettingLinks()
    {
        $settingsLinks[] = array("linktype" => "MODULESETTING", "linklabel" => "Settings", "linkurl" => "index.php?module=RelatedBlocksLists&parent=Settings&view=Settings", "linkicon" => "");
        $settingsLinks[] = array("linktype" => "MODULESETTING", "linklabel" => "Uninstall", "linkurl" => "index.php?module=RelatedBlocksLists&parent=Settings&view=Uninstall", "linkicon" => "");
        return $settingsLinks;
    }
    public function getCalendarRecord($recordModel, $moduleName)
    {
        if (!empty($recordModel)) {
            $dateTimeStartValue = $recordModel->get("date_start");
            if ($dateTimeStartValue) {
                $recordModel->set("date_start_value", $dateTimeStartValue);
                $dateTimeStartValue = $dateTimeStartValue . " " . $recordModel->get("time_start");
                $recordModel->set("date_start", $dateTimeStartValue);
            }
            $dateTimeEndValue = $recordModel->get("due_date");
            if ($dateTimeEndValue && $moduleName != "Calendar") {
                $recordModel->set("isEvent", 1);
                $dateTimeEndValue = $dateTimeEndValue . " " . $recordModel->get("time_end");
                $recordModel->set("due_date", $dateTimeEndValue);
            }
            $visibility = $recordModel->get("visibility");
            if (empty($visibility)) {
                $currentUserModel = Users_Record_Model::getCurrentUserModel();
                $sharedType = $currentUserModel->get("calendarsharedtype");
                if ($sharedType == "public" || $sharedType == "selectedusers") {
                    $recordModel->set("visibility", "Public");
                }
            }
            $eventstatus = $recordModel->get("eventstatus");
            if (empty($eventstatus)) {
                $currentUserModel = Users_Record_Model::getCurrentUserModel();
                $defaulteventstatus = $currentUserModel->get("defaulteventstatus");
                $recordModel->set("eventstatus", $defaulteventstatus);
            }
            $activitytype = $recordModel->get("activitytype");
            if (empty($activitytype)) {
                $currentUserModel = Users_Record_Model::getCurrentUserModel();
                $defaultactivitytype = $currentUserModel->get("defaultactivitytype");
                $recordModel->set("activitytype", $defaultactivitytype);
            }
        }
        return $recordModel;
    }
    public function setDataForCalendarRecord($relRecordModel, $_request, $related_module_base = "")
    {
        if ($related_module_base && $related_module_base == "Events") {
            $startTime = Vtiger_Time_UIType::getTimeValueWithSeconds($_request["time_start"]);
            $startDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($_request["date_start"] . " " . $startTime);
            list($startDate, $startTime) = explode(" ", $startDateTime);
            $relRecordModel->set("date_start", $startDate);
            $relRecordModel->set("time_start", $startTime);
            $endTime = $_request["time_end"];
            $endDate = Vtiger_Date_UIType::getDBInsertedValue($_request["due_date"]);
            if ($endTime) {
                $endTime = Vtiger_Time_UIType::getTimeValueWithSeconds($endTime);
                $endDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($_request["due_date"] . " " . $endTime);
                list($endDate, $endTime) = explode(" ", $endDateTime);
            }
            $relRecordModel->set("time_end", $endTime);
            $relRecordModel->set("due_date", $endDate);
            if ($_request["description"]) {
                $relRecordModel->set("description", $_request["description"]);
            }
            $activityType = $_request["activitytype"];
            if (empty($activityType)) {
                if ($related_module_base == "Events") {
                    $relRecordModel->set("activitytype", "Call");
                } else {
                    $relRecordModel->set("activitytype", "Task");
                }
                $relRecordModel->set("visibility", "Private");
            }
            $setReminder = $_request["set_reminder"];
            if ($setReminder) {
                $_REQUEST["set_reminder"] = "Yes";
            } else {
                $_REQUEST["set_reminder"] = "No";
            }
            $time = strtotime($_request["time_end"]) - strtotime($_request["time_start"]);
            $diffinSec = strtotime($_request["due_date"]) - strtotime($_request["date_start"]);
            $diff_days = floor($diffinSec / (60 * 60 * 24));
            $hours = (double) $time / 3600 + $diff_days * 24;
            $minutes = ((double) $hours - (int) $hours) * 60;
            $relRecordModel->set("duration_hours", (int) $hours);
            $relRecordModel->set("duration_minutes", round($minutes, 0));
            return $relRecordModel;
        }
        $baseModule = $_request["module"];
        if ($_request["module"] == "Events") {
            $_request["module"] = "Calendar";
        }
        $startTime = Vtiger_Time_UIType::getTimeValueWithSeconds($_request[$_request["module"] . "_" . "time_start"]);
        $startDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($_request[$_request["module"] . "_" . "date_start"] . " " . $startTime);
        list($startDate, $startTime) = explode(" ", $startDateTime);
        $relRecordModel->set("date_start", $startDate);
        $relRecordModel->set("time_start", $startTime);
        $endTime = $_request[$_request["module"] . "_" . "time_end"];
        $endDate = Vtiger_Date_UIType::getDBInsertedValue($_request[$_request["module"] . "_" . "due_date"]);
        if ($endTime) {
            $endTime = Vtiger_Time_UIType::getTimeValueWithSeconds($endTime);
            $endDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($_request[$_request["module"] . "_" . "due_date"] . " " . $endTime);
            list($endDate, $endTime) = explode(" ", $endDateTime);
        }
        $relRecordModel->set("time_end", $endTime);
        $relRecordModel->set("due_date", $endDate);
        if ($_request[$_request["module"] . "_" . "description"]) {
            $relRecordModel->set("description", $_request[$_request["module"] . "_" . "description"]);
        }
        $activityType = $_request[$_request["module"] . "_" . "activitytype"];
        if (empty($activityType)) {
            if ($baseModule == "Events") {
                $relRecordModel->set("activitytype", "Call");
            } else {
                $relRecordModel->set("activitytype", "Task");
            }
            $relRecordModel->set("visibility", "Private");
        }
        $setReminder = $_request[$_request["module"] . "_" . "set_reminder"];
        if ($setReminder) {
            $_REQUEST["set_reminder"] = "Yes";
        } else {
            $_REQUEST["set_reminder"] = "No";
        }
        $time = strtotime($_request[$_request["module"] . "_" . "time_end"]) - strtotime($_request[$_request["module"] . "_" . "time_start"]);
        $diffinSec = strtotime($_request[$_request["module"] . "_" . "due_date"]) - strtotime($_request[$_request["module"] . "_" . "date_start"]);
        $diff_days = floor($diffinSec / (60 * 60 * 24));
        $hours = (double) $time / 3600 + $diff_days * 24;
        $minutes = ((double) $hours - (int) $hours) * 60;
        $relRecordModel->set("duration_hours", (int) $hours);
        $relRecordModel->set("duration_minutes", round($minutes, 0));
        return $relRecordModel;
    }
    public function getPageInfo($relatedQuery, $page, $page_limit)
    {
        global $adb;
        $pageInfo = array();
        $position = stripos($relatedQuery, " from ");
        if ($position) {
            $split = preg_split("/\\sfrom\\s/i", $relatedQuery);
            $splitCount = count($split);
            $countQuery = "SELECT COUNT(DISTINCT vtiger_crmentity.crmid) AS count";
            for ($i = 1; $i < $splitCount; $i++) {
                $countQuery = $countQuery . " FROM " . $split[$i];
            }
        }
        $countRs = $adb->pquery($countQuery, array());
        if (0 < $adb->num_rows($countRs)) {
            $totalRecord = $adb->query_result($countRs, 0, "count");
            if (0 < $totalRecord) {
                $totalPage = ceil($totalRecord / $page_limit);
                $startIndex = ($page - 1) * $page_limit;
                if ($totalPage == $page) {
                    $endIndex = $totalRecord;
                } else {
                    $endIndex = $startIndex + $page_limit;
                }
                $pageInfo["total_record"] = $totalRecord;
                $pageInfo["total_page"] = $totalPage;
                $pageInfo["start_index"] = $startIndex + 1;
                $pageInfo["end_index"] = $endIndex;
                $pageInfo["page"] = $page;
                $pageInfo["page_limit"] = $page_limit;
            } else {
                $pageInfo["start_index"] = 1;
            }
        }
        return $pageInfo;
    }
    public static function getRelatedTabIdForField($field_id)
    {
        global $adb;
        $tabid = 0;
        $tmpQuery = "SELECT m.tabid FROM vtiger_field f\r\n                            INNER JOIN vtiger_relatedlists m ON f.fieldid = m.relationfieldid\r\n                            WHERE f.fieldid = ?";
        $countRs = $adb->pquery($tmpQuery, array($field_id));
        if (0 < $adb->num_rows($countRs)) {
            $tabid = $adb->query_result($countRs, 0, "tabid");
        }
        return $tabid;
    }
    public static function getWidthForField($field_name, $block_id, $rel_module = "")
    {
        global $adb;
        $width = "";
        if ($rel_module != "") {
            $field_name = str_replace($rel_module . "_", "", $field_name);
        }
        $tmpQuery = "SELECT f.width FROM relatedblockslists_fields f\r\n                            WHERE f.fieldname = ? AND f.blockid = ?";
        $countRs = $adb->pquery($tmpQuery, array($field_name, $block_id));
        if (0 < $adb->num_rows($countRs)) {
            $width = $adb->query_result($countRs, 0, "width");
        }
        return $width;
    }
    public static function getCustomizableOptionsForBlock($block_id)
    {
        global $adb;
        $tmpQuery = "SELECT customizable_options FROM relatedblockslists_blocks WHERE blockid = ?";
        $countRs = $adb->pquery($tmpQuery, array($block_id));
        if (0 < $adb->num_rows($countRs)) {
            $customizable_options = $adb->query_result($countRs, 0, "customizable_options");
        }
        return json_decode(html_entity_decode($customizable_options));
    }
}

?>