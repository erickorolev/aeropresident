<?php

class RelatedBlocksLists_MassActionAjax_View extends Vtiger_IndexAjax_View
{
    const PAGE_LIMIT = 5;
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod("generateEditView");
        $this->exposeMethod("generateDetailView");
        $this->exposeMethod("generateRecordDetailView");
        $this->exposeMethod("generateRecordEditView");
        $this->exposeMethod("generateNewBlock");
    }
    public function process(Vtiger_Request $request)
    {
        $mode = $request->get("mode");
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }
    public function generateEditView(Vtiger_Request $request)
    {
        global $adb;
        $moduleName = $request->getModule();
        $record = $request->get("record");
        $blockid = $request->get("blockid");
        $source_module = $request->get("source_module");
        $viewer = $this->getViewer($request);
        if ($record != "") {
            $recordModel = Vtiger_Record_Model::getInstanceById($record);
        } else {
            $recordModel = Vtiger_Record_Model::getCleanInstance($source_module);
        }
        $blocksList = [];
        if ($blockid != "") {
            $sql = "SELECT * FROM `relatedblockslists_blocks` WHERE blockid=? AND active=1";
            $rs = $adb->pquery($sql, [$blockid]);
        } else {
            $sql = "SELECT * FROM `relatedblockslists_blocks` WHERE module=? AND active=1";
            $rs = $adb->pquery($sql, [$source_module]);
        }
        if (0 < $adb->num_rows($rs)) {
            while ($row = $adb->fetch_array($rs)) {
                $blockid = $row["blockid"];
                $relModule = $row["relmodule"];
                $relmodule_model = Vtiger_Module_Model::getInstance($relModule);
                $blocksList[$blockid]["relmodule"] = $relmodule_model;
                $blocksList[$blockid]["type"] = $row["type"];
                $blocksList[$blockid]["filterfield"] = $row["filterfield"];
                $blocksList[$blockid]["filtervalue"] = $row["filtervalue"];
                $page_limit = $row["limit_per_page"];
                $fields = [];
                $selected_fields = [];
                $multipicklist_fields = [];
                $reference_fields = [];
                $sqlField = "SELECT * FROM `relatedblockslists_fields` WHERE blockid = ? ORDER BY sequence";
                $rsFields = $adb->pquery($sqlField, [$blockid]);
                if (0 < $adb->num_rows($rsFields)) {
                    $mandatoryFields = [];
                    while ($rowField = $adb->fetch_array($rsFields)) {
                        $fieldModel = $relmodule_model->getField($rowField["fieldname"]);
                        if ($fieldModel) {
                            $selected_fields[] = $rowField["fieldname"];
                            if ($fieldModel->get("uitype") == "33") {
                                $multipicklist_fields[] = $this->reGenerateFieldName($rowField["fieldname"], $relModule);
                            } else {
                                if ($fieldModel->getFieldDataType() == "reference") {
                                    $reference_fields[] = $this->reGenerateFieldName($rowField["fieldname"], $relModule);
                                }
                            }
                            $defaultvalue = $rowField["defaultvalue"];
                            $mandatory = $rowField["mandatory"];
                            $fieldModel->set("related_default_fieldvalue", $defaultvalue);
                            $fieldModel->set("related_mandatory", $mandatory);
                            $fields[$rowField["fieldname"]] = $fieldModel;
                            if ($mandatory == 1) {
                                $mandatoryFields[] = $rowField["fieldname"];
                            }
                            if (strpos($rowField["fieldname"], "acf_dtf") !== false) {
                                $selected_fields[] = $rowField["fieldname"] . "_time";
                            }
                            if ($rowField["fieldname"] == "date_start" && ($relModule == "Events" || $relModule == "Calendar")) {
                                $selected_fields[] = "time_start";
                            } else {
                                if ($rowField["fieldname"] == "due_date" && $relModule == "Events") {
                                    $selected_fields[] = "time_end";
                                }
                            }
                        }
                    }
                }
                $blocksList[$blockid]["fields"] = $fields;
                $new_selected_fields = [];
                foreach ($selected_fields as $fieldname) {
                    $new_selected_fields[] = $this->reGenerateFieldName($fieldname, $relModule);
                }
                $blocksList[$blockid]["selected_fields"] = implode(",", $new_selected_fields);
                $blocksList[$blockid]["multipicklist_fields"] = implode(",", $multipicklist_fields);
                $blocksList[$blockid]["reference_fields"] = implode(",", $reference_fields);
                $relatedRecords = [];
                $recordStructureInstance = [];
                if ($record != "") {
                    global $currentModule;
                    $currentModule = $source_module;
                    if ($relModule == "Events") {
                        $relModule = "Calendar";
                    }
                    $relationListView = Vtiger_RelationListView_Model::getInstance($recordModel, $relModule);
                    $relatedQuery = $relationListView->getRelationQuery();
                    if ($relModule == "ModComments") {
                        $split = preg_split("/from/i", $relatedQuery);
                        $relatedQuery = $split[0] . ",vtiger_crmentity.crmid FROM " . $split[1];
                    }
                    $relatedBlocksListsModule = new RelatedBlocksLists_Module_Model();
                    if ($request->get("page")) {
                        $page = $request->get("page");
                    } else {
                        $page = 1;
                    }
                    if (!empty($page_limit)) {
                        $page_limit = $page_limit;
                    } else {
                        $page_limit = 5;
                    }
                    if ($row["relmodule"] == "Calendar") {
                        $relatedQuery .= " AND vtiger_activity.activitytype = 'Task'";
                    } else {
                        if ($row["relmodule"] == "Events") {
                            $relatedQuery .= " AND vtiger_activity.activitytype != 'Task'";
                        }
                    }
                    if (!empty($row["filterfield"]) && !empty($row["filtervalue"])) {
                        $sqlField = "SELECT columnname,tablename FROM `vtiger_field` WHERE fieldname='" . $row["filterfield"] . "' AND tabid = (SELECT tabid FROM vtiger_tab WHERE name = '" . $row["relmodule"] . "')";
                        $results = $adb->pquery($sqlField, []);
                        if (0 < $adb->num_rows($results)) {
                            $tablename = $adb->query_result($results, 0, "tablename");
                            $columnname = $adb->query_result($results, 0, "columnname");
                            $relatedQuery .= " AND " . $tablename . "." . $columnname . " = '" . $row["filtervalue"] . "'";
                        }
                    }
                    if (!empty($row["sortfield"]) && !empty($row["sorttype"])) {
                        $relModuleModel = Vtiger_Module_Model::getInstance($relModule);
                        $sortfield = $row["sortfield"];
                        $sorttype = $row["sorttype"];
                        $orderByFieldModuleModel = $relModuleModel->getFieldByColumn($sortfield);
                        if ($orderByFieldModuleModel && $orderByFieldModuleModel->isReferenceField()) {
                            $queryComponents = $split = spliti(" where ", $relatedQuery);
                            list($selectAndFromClause, $whereCondition) = $queryComponents;
                            $qualifiedOrderBy = "vtiger_crmentity" . $orderByFieldModuleModel->get("column");
                            $selectAndFromClause .= " LEFT JOIN vtiger_crmentity AS " . $qualifiedOrderBy . " ON " . $orderByFieldModuleModel->get("table") . "." . $orderByFieldModuleModel->get("column") . " = " . $qualifiedOrderBy . ".crmid ";
                            $relatedQuery = $selectAndFromClause . " WHERE " . $whereCondition;
                            $relatedQuery .= " ORDER BY " . $qualifiedOrderBy . ".label " . $sorttype;
                        } else {
                            if ($orderByFieldModuleModel && $orderByFieldModuleModel->isOwnerField()) {
                                $relatedQuery .= " ORDER BY COALESCE(CONCAT(vtiger_users.first_name,vtiger_users.last_name),vtiger_groups.groupname) " . $sorttype;
                            } else {
                                $qualifiedOrderBy = $sortfield;
                                $orderByField = $relModuleModel->getFieldByColumn($sortfield);
                                if ($orderByField) {
                                    $qualifiedOrderBy = $relModuleModel->getOrderBySql($qualifiedOrderBy);
                                }
                                $relatedQuery = $relatedQuery . " ORDER BY " . $qualifiedOrderBy . " " . $sorttype;
                            }
                        }
                    }
                    $blocksList[$blockid]["page_info"] = $relatedBlocksListsModule->getPageInfo($relatedQuery, $page, $page_limit);
                    $startIndex = $blocksList[$blockid]["page_info"]["start_index"] - 1;
                    $relatedQuery .= " LIMIT " . $startIndex . "," . $page_limit;
                    $rsData = $adb->pquery($relatedQuery);
                    if (0 < $adb->num_rows($rsData)) {
                        while ($rowData = $adb->fetch_array($rsData)) {
                            $recordModel = Vtiger_Record_Model::getInstanceById($rowData["crmid"]);
                            $_moduleModel = new RelatedBlocksLists_Module_Model();
                            $recordModel = $_moduleModel->getCalendarRecord($recordModel, $relModule);
                            $relatedRecords[] = $recordModel;
                            $recordStructureInstance[] = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
                        }
                    }
                }
                $recordModelBase = Vtiger_Record_Model::getCleanInstance($relModule);
                $recordStructureInstanceBase = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModelBase, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
                $blocksList[$blockid]["data"] = $relatedRecords;
                $blocksList[$blockid]["data_structure"] = $recordStructureInstance;
                $blocksList[$blockid]["data_structure_base"] = $recordStructureInstanceBase;
            }
        }
        $viewer->assign("BLOCKS_LIST", $blocksList);
        $viewer->assign("RECORD_MODEL", $recordModel);
        $viewer->assign("MANDATORY_FIELDS", implode(",", $mandatoryFields));
        $viewer->assign("QUALIFIED_MODULE", $moduleName);
        $viewer->assign("SOURCE_MODULE", $source_module);
        $viewer->assign("SOURCE_RECORD", $record);
        $viewer->assign("USER_MODEL", Users_Record_Model::getCurrentUserModel());
        $sql = "SELECT\n            actions\n        FROM\n            `vtiger_relatedlists`\n        WHERE\n            tabid = (\n                SELECT\n                    tabid\n                FROM\n                    vtiger_tab\n                WHERE\n                    `name` = '" . $source_module . "'\n            )\n        AND related_tabid = (\n            SELECT\n                tabid\n            FROM\n                vtiger_tab\n            WHERE\n                `name` = '" . $relModule . "'\n        )";
        $results = $adb->pquery($sql, []);
        if (0 < $adb->num_rows($results)) {
            $is_select_button = false;
            $actions = $adb->query_result($results, 0, "actions");
            $actions = strtolower($actions);
            if (strpos($actions, "select") !== false) {
                $is_select_button = true;
            }
            $viewer->assign("IS_SELECT_BUTTON", $is_select_button);
        }
        $_REQUEST["view"] = "Edit";
        $content = $viewer->view("RelatedEditView.tpl", $moduleName, true);
        foreach ($selected_fields as $field) {
            $newFieldName = $this->reGenerateFieldName($field, $relModule);
            $displayField = $field . "_display";
            $displayNewFieldName = $newFieldName . "_display";
            $content = preg_replace("/name=\"" . $field . "\"/is", "name=\"" . $newFieldName . "\"", $content);
            $content = preg_replace("/name=\"" . $displayField . "\"/is", "name=\"" . $displayNewFieldName . "\"", $content);
        }
        echo $content;
    }
    public function generateDetailView(Vtiger_Request $request)
    {
        global $adb;
        $moduleName = $request->getModule();
        $record = $request->get("record");
        $ajax = $request->get("ajax");
        $blockid = $request->get("blockid");
        $source_module = $request->get("source_module");
        $viewer = $this->getViewer($request);
        if ($record != "") {
            $recordModel = Vtiger_Record_Model::getInstanceById($record);
        } else {
            $recordModel = Vtiger_Record_Model::getCleanInstance($source_module);
        }
        $blocksList = [];
        if ($blockid != "") {
            $sql = "SELECT * FROM `relatedblockslists_blocks` WHERE blockid=? AND active=1";
            $rs = $adb->pquery($sql, [$blockid]);
        } else {
            $sql = "SELECT * FROM `relatedblockslists_blocks` WHERE module=? AND active=1";
            $rs = $adb->pquery($sql, [$source_module]);
        }
        $select_record_avaialble = false;
        if (0 < $adb->num_rows($rs)) {
            while ($row = $adb->fetch_array($rs)) {
                $blockid = $row["blockid"];
                $expand = $row["expand"];
                $isGetPage = $request->get("page");
                if (!empty($isGetPage)) {
                    $expand = 0;
                }
                $relModule = $row["relmodule"];
                $relmodule_model = Vtiger_Module_Model::getInstance($relModule);
                $blocksList[$blockid]["relmodule"] = $relmodule_model;
                $blocksList[$blockid]["type"] = $row["type"];
                $blocksList[$blockid]["expand"] = $expand;
                $blocksList[$blockid]["limit_per_page"] = $row["limit_per_page"];
                $blocksList[$blockid]["filterfield"] = $row["filterfield"];
                $blocksList[$blockid]["filtervalue"] = $row["filtervalue"];
                $sortfield = $row["sortfield"];
                $sorttype = $row["sorttype"];
                $fields = [];
                $sqlField = "SELECT * FROM `relatedblockslists_fields` WHERE blockid = ? ORDER BY sequence";
                $rsFields = $adb->pquery($sqlField, [$blockid]);
                $recordModelBase = Vtiger_Record_Model::getCleanInstance($relModule);
                $recordStructureInstanceBase = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModelBase, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
                if (0 < $adb->num_rows($rsFields)) {
                    $mandatoryFields = [];
                    while ($rowField = $adb->fetch_array($rsFields)) {
                        if ($relModule == "Calendar") {
                            $recordStructure = $recordStructureInstanceBase->getStructure();
                            foreach ($recordStructure as $block) {
                                foreach ($block as $field) {
                                    if ($field->getName() == $rowField["fieldname"]) {
                                        $fields[$rowField["fieldname"]] = $field;
                                    }
                                }
                            }
                        } else {
                            $fieldModel = $relmodule_model->getField($rowField["fieldname"]);
                            $fields[$rowField["fieldname"]] = $fieldModel;
                        }
                        $defaultvalue = $rowField["defaultvalue"];
                        $mandatory = $rowField["mandatory"];
                        if ($mandatory == 1) {
                            $mandatoryFields[] = $rowField["fieldname"];
                        }
                        $fieldModel->set("related_default_fieldvalue", $defaultvalue);
                        $fieldModel->set("related_mandatory", $mandatory);
                    }
                }
                $blocksList[$blockid]["fields"] = $fields;
                $relatedRecords = [];
                if ($record != "") {
                    global $currentModule;
                    $currentModule = $source_module;
                    if ($relModule == "Events") {
                        $relModule = "Calendar";
                    }
                    $relationListView = Vtiger_RelationListView_Model::getInstance($recordModel, $relModule);
                    $relatedQuery = $relationListView->getRelationQuery();
                    if ($relModule == "ModComments") {
                        $split = preg_split("/from/i", $relatedQuery);
                        $relatedQuery = $split[0] . ",vtiger_crmentity.crmid FROM " . $split[1];
                    }
                    $relatedBlocksListsModule = new RelatedBlocksLists_Module_Model();
                    if ($request->get("page")) {
                        $page = $request->get("page");
                    } else {
                        $page = 1;
                    }
                    if (!empty($blocksList[$blockid]["limit_per_page"])) {
                        $page_limit = $blocksList[$blockid]["limit_per_page"];
                    } else {
                        $page_limit = 5;
                    }
                    if ($row["relmodule"] == "Calendar") {
                        $relatedQuery .= " AND vtiger_activity.activitytype = 'Task'";
                    } else {
                        if ($row["relmodule"] == "Events") {
                            $relatedQuery .= " AND vtiger_activity.activitytype != 'Task'";
                        }
                    }
                    if (!empty($row["filterfield"]) && !empty($row["filtervalue"])) {
                        $sqlField = "SELECT columnname,tablename FROM `vtiger_field` WHERE fieldname='" . $row["filterfield"] . "' AND tabid = (SELECT tabid FROM vtiger_tab WHERE name = '" . $row["relmodule"] . "')";
                        $results = $adb->pquery($sqlField, []);
                        if (0 < $adb->num_rows($results)) {
                            $tablename = $adb->query_result($results, 0, "tablename");
                            $columnname = $adb->query_result($results, 0, "columnname");
                            $relatedQuery .= " AND " . $tablename . "." . $columnname . " = '" . $row["filtervalue"] . "'";
                        }
                    }
                    $blocksList[$blockid]["page_info"] = $relatedBlocksListsModule->getPageInfo($relatedQuery, $page, $page_limit);
                    $startIndex = $blocksList[$blockid]["page_info"]["start_index"] - 1;
                    $getAllRelatedQuery = $relatedQuery;
                    $reAll = $adb->query($getAllRelatedQuery);
                    if (0 < $adb->num_rows($reAll)) {
                        $existsIDs = "";
                        while ($rowAll = $adb->fetch_array($reAll)) {
                            $crmid = $rowAll["crmid"];
                            $existsIDs = $existsIDs == "" ? $crmid : $existsIDs . "," . $crmid;
                        }
                        $relModuleModel = Vtiger_Module_Model::getInstance($relModule);
                        $relListViewModel = Vtiger_ListView_Model::getInstance($relModule);
                        $sqlAvaialble = $relListViewModel->getQuery();
                        $sqlAvaialble .= " and vtiger_crmentity.crmid NOT IN (" . $existsIDs . ") ";
                        $reAvaialble = $adb->query($sqlAvaialble);
                        if (0 < $adb->num_rows($reAvaialble)) {
                            $select_record_avaialble = true;
                        }
                    } else {
                        $select_record_avaialble = true;
                    }
                    if (!empty($sortfield) && !empty($sorttype) && !empty($relModuleModel)) {
                        $orderByFieldModuleModel = $relModuleModel->getFieldByColumn($sortfield);
                        if ($orderByFieldModuleModel && $orderByFieldModuleModel->isReferenceField()) {
                            $queryComponents = $split = spliti(" where ", $relatedQuery);
                            list($selectAndFromClause, $whereCondition) = $queryComponents;
                            $qualifiedOrderBy = "vtiger_crmentity" . $orderByFieldModuleModel->get("column");
                            $selectAndFromClause .= " LEFT JOIN vtiger_crmentity AS " . $qualifiedOrderBy . " ON " . $orderByFieldModuleModel->get("table") . "." . $orderByFieldModuleModel->get("column") . " = " . $qualifiedOrderBy . ".crmid ";
                            $relatedQuery = $selectAndFromClause . " WHERE " . $whereCondition;
                            $relatedQuery .= " ORDER BY " . $qualifiedOrderBy . ".label " . $sorttype;
                        } else {
                            if ($orderByFieldModuleModel && $orderByFieldModuleModel->isOwnerField()) {
                                $relatedQuery .= " ORDER BY COALESCE(CONCAT(vtiger_users.first_name,vtiger_users.last_name),vtiger_groups.groupname) " . $sorttype;
                            } else {
                                $qualifiedOrderBy = $sortfield;
                                $orderByField = $relModuleModel->getFieldByColumn($sortfield);
                                if ($orderByField) {
                                    $qualifiedOrderBy = $relModuleModel->getOrderBySql($qualifiedOrderBy);
                                }
                                $relatedQuery = $relatedQuery . " ORDER BY " . $qualifiedOrderBy . " " . $sorttype;
                            }
                        }
                    }
                    $relatedQuery .= " LIMIT " . $startIndex . "," . $page_limit;
                    $rsData = $adb->pquery($relatedQuery, []);
                    if (0 < $adb->num_rows($rsData)) {
                        while ($rowData = $adb->fetch_array($rsData)) {
                            $recordModel = Vtiger_Record_Model::getInstanceById($rowData["crmid"], $relModule);
                            $_moduleModel = new RelatedBlocksLists_Module_Model();
                            if ($relModule == "Calendar") {
                                $recordModel = $_moduleModel->getCalendarRecord($recordModel, $relModule);
                            }
                            $relatedRecords[] = $recordModel;
                            $recordStructureInstance[] = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
                        }
                    }
                }
                $blocksList[$blockid]["data"] = $relatedRecords;
                $blocksList[$blockid]["data_structure"] = $recordStructureInstance;
                $blocksList[$blockid]["data_structure_base"] = $recordStructureInstanceBase;
            }
        }
        $viewer->assign("SELECT_RECORD_AVAIALBLE", $select_record_avaialble);
        $viewer->assign("BLOCKS_LIST", $blocksList);
        $viewer->assign("RECORD_MODEL", $recordModel);
        $viewer->assign("RELMODULE_NAME", $recordModel->getModuleName());
        $viewer->assign("QUALIFIED_MODULE", $moduleName);
        $viewer->assign("SOURCE_MODULE", $source_module);
        $viewer->assign("SOURCE_RECORD", $record);
        $viewer->assign("MANDATORY_FIELDS", implode(",", $mandatoryFields));
        $viewer->assign("AJAX", $ajax);
        $viewer->assign("USER_MODEL", Users_Record_Model::getCurrentUserModel());
        $rel_moduleName = $relModule;
        $createPermission = Users_Privileges_Model::isPermitted($rel_moduleName, "EditView");
        if ($createPermission) {
            $viewer->assign("PERMISSION_TO_MODULE", true);
        }
        $sql = "SELECT\n            actions\n        FROM\n            `vtiger_relatedlists`\n        WHERE\n            tabid = (\n                SELECT\n                    tabid\n                FROM\n                    vtiger_tab\n                WHERE\n                    `name` = '" . $source_module . "'\n            )\n        AND related_tabid = (\n            SELECT\n                tabid\n            FROM\n                vtiger_tab\n            WHERE\n                `name` = '" . $relModule . "'\n        )";
        $results = $adb->pquery($sql, []);
        if (0 < $adb->num_rows($results)) {
            $is_select_button = false;
            $actions = $adb->query_result($results, 0, "actions");
            $actions = strtolower($actions);
            if (strpos($actions, "select") !== false) {
                $is_select_button = true;
            }
            $viewer->assign("IS_SELECT_BUTTON", $is_select_button);
        }
        echo $viewer->view("RelatedDetailView.tpl", $moduleName, true);
    }
    public function generateNewBlock(Vtiger_Request $request)
    {
        global $adb;
        $moduleName = $request->getModule();
        $relmodule = $request->get("relmodule");
        $blockid = $request->get("blockid");
        $modeView = $request->get("modeView");
        $viewer = $this->getViewer($request);
        $relmodule_model = Vtiger_Module_Model::getInstance($relmodule);
        $sqlField = "SELECT * FROM `relatedblockslists_fields` WHERE blockid = ? ORDER BY sequence";
        $rsFields = $adb->pquery($sqlField, [$blockid]);
        $fields = [];
        $recordModel = Vtiger_Record_Model::getCleanInstance($relmodule);
        $recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
        if (0 < $adb->num_rows($rsFields)) {
            while ($rowField = $adb->fetch_array($rsFields)) {
                if ($relmodule == "Calendar") {
                    $recordStructure = $recordStructureInstance->getStructure();
                    foreach ($recordStructure as $block) {
                        foreach ($block as $field) {
                            if ($field->getName() == $rowField["fieldname"]) {
                                $fields[$rowField["fieldname"]] = $field;
                            }
                        }
                    }
                } else {
                    $fieldModel = $relmodule_model->getField($rowField["fieldname"]);
                    if ($fieldModel) {
                        $fields[$rowField["fieldname"]] = $fieldModel;
                    }
                }
                $defaultvalue = $rowField["defaultvalue"];
                $mandatory = $rowField["mandatory"];
                $fieldModel->set("related_default_fieldvalue", $defaultvalue);
                $fieldModel->set("related_mandatory", $mandatory);
            }
        }
        foreach ($fields as $fieldName => $fieldValue) {
            $fields[$fieldName]->set("name", $relmodule . "_" . $fieldValue->get("name"));
        }
        $viewer->assign("RELMODULE_MODEL", $relmodule_model);
        $viewer->assign("RELMODULE_NAME", $relmodule_model->getName());
        $viewer->assign("FIELDS_LIST", $fields);
        $viewer->assign("RELATED_RECORD_MODEL", $recordModel);
        $viewer->assign("RECORD_STRUCTURE_MODEL", $recordStructureInstance);
        $viewer->assign("BLOCKID", $blockid);
        $_REQUEST["modeView"] = $modeView;
        $_REQUEST["view"] = "Edit";
        $viewer->assign("QUALIFIED_MODULE", $moduleName);
        $viewer->assign("USER_MODEL", Users_Record_Model::getCurrentUserModel());
        echo $viewer->view("BlockEditFields.tpl", $moduleName, true);
    }
    public function generateRecordDetailView(Vtiger_Request $request)
    {
        global $adb;
        $moduleName = $request->getModule();
        $record = $request->get("record");
        $related_record = $request->get("related_record");
        $blockid = $request->get("blockid");
        $source_module = $request->get("source_module");
        $sql = "SELECT * FROM `relatedblockslists_blocks` WHERE blockid=? AND active=1";
        $rs = $adb->pquery($sql, [$blockid]);
        $blocktype = $adb->query_result($rs, 0, "type");
        $viewer = $this->getViewer($request);
        if (!empty($record)) {
            $recordModel = Vtiger_Record_Model::getInstanceById($record);
            $source_module = $recordModel->getModuleName();
        }
        $relatedRecordModel = Vtiger_Record_Model::getInstanceById($related_record);
        $relmodule_model = $relatedRecordModel->getModule();
        $sqlField = "SELECT * FROM `relatedblockslists_fields` WHERE blockid = ? ORDER BY sequence";
        $rsFields = $adb->pquery($sqlField, [$blockid]);
        $recordModel = Vtiger_Record_Model::getCleanInstance($relmodule_model->getName());
        $recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_DETAIL);
        if (0 < $adb->num_rows($rsFields)) {
            while ($rowField = $adb->fetch_array($rsFields)) {
                if ($relatedRecordModel->getModuleName() == "Calendar" && $relatedRecordModel->get("activitytype") != "Task") {
                    $eventModuleModel = Vtiger_Module_Model::getInstance("Events");
                    $fieldModel = $eventModuleModel->getField($rowField["fieldname"]);
                } else {
                    if ($relatedRecordModel->getModuleName() == "Calendar") {
                        $recordStructure = $recordStructureInstance->getStructure();
                        foreach ($recordStructure as $block) {
                            foreach ($block as $field) {
                                if ($field->getName() == $rowField["fieldname"]) {
                                    $fieldModel = $field;
                                }
                            }
                        }
                    } else {
                        $fieldModel = $relmodule_model->getField($rowField["fieldname"]);
                    }
                }
                if ($fieldModel) {
                    $fields[$rowField["fieldname"]] = $fieldModel;
                }
            }
        }
        $viewer->assign("RELMODULE_MODEL", $relmodule_model);
        $viewer->assign("RELMODULE_NAME", $relmodule_model->getName());
        $viewer->assign("FIELDS_LIST", $fields);
        $viewer->assign("RELATED_RECORD_MODEL", $relatedRecordModel);
        $viewer->assign("RECORD_STRUCTURE_MODEL", $recordStructureInstance);
        $viewer->assign("RELMODULE_NAME", $relmodule_model->getName());
        $viewer->assign("BLOCKID", $blockid);
        $viewer->assign("SOURCE_RECORD", $record);
        $viewer->assign("SOURCE_MODULE", $source_module);
        $viewer->assign("BLOCKTYPE", $blocktype);
        $viewer->assign("USER_MODEL", Users_Record_Model::getCurrentUserModel());
        $viewer->assign("QUALIFIED_MODULE", $moduleName);
        echo $viewer->view("RelatedRecordDetail.tpl", $moduleName, true);
    }
    public function generateRecordEditView(Vtiger_Request $request)
    {
        global $adb;
        $moduleName = $request->getModule();
        $record = $request->get("record");
        $related_record = $request->get("related_record");
        $blockid = $request->get("blockid");
        $rowno = $request->get("rowno");
        $source_module = $request->get("source_module");
        $sql = "SELECT * FROM `relatedblockslists_blocks` WHERE blockid=? AND active=1";
        $rs = $adb->pquery($sql, [$blockid]);
        $blocktype = $adb->query_result($rs, 0, "type");
        $viewer = $this->getViewer($request);
        if ($record != "") {
            $recordModel = Vtiger_Record_Model::getInstanceById($record);
        } else {
            $recordModel = Vtiger_Record_Model::getCleanInstance($source_module);
        }
        $source_module = $recordModel->getModuleName();
        $relatedRecordModel = Vtiger_Record_Model::getInstanceById($related_record);
        $relmodule_model = Vtiger_Module_Model::getInstance($relatedRecordModel->getModuleName());
        $sqlField = "SELECT * FROM `relatedblockslists_fields` WHERE blockid = ? ORDER BY sequence";
        $rsFields = $adb->pquery($sqlField, [$blockid]);
        $recordModel = Vtiger_Record_Model::getCleanInstance($relmodule_model->getName());
        $recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
        $selected_fields = [];
        if (0 < $adb->num_rows($rsFields)) {
            while ($rowField = $adb->fetch_array($rsFields)) {
                $selected_fields[] = $rowField["fieldname"];
                if ($relatedRecordModel->getModuleName() == "Calendar" && $relatedRecordModel->get("activitytype") != "Task") {
                    $eventModuleModel = Vtiger_Module_Model::getInstance("Events");
                    $fieldModel = $eventModuleModel->getField($rowField["fieldname"]);
                } else {
                    if ($relatedRecordModel->getModuleName() == "Calendar") {
                        $recordStructure = $recordStructureInstance->getStructure();
                        foreach ($recordStructure as $block) {
                            foreach ($block as $field) {
                                if ($field->getName() == $rowField["fieldname"]) {
                                    $fieldModel = $field;
                                }
                            }
                        }
                    } else {
                        $fieldModel = $relmodule_model->getField($rowField["fieldname"]);
                    }
                }
                $fields[$rowField["fieldname"]] = $fieldModel;
            }
        }
        $viewer->assign("RELMODULE_MODEL", $relmodule_model);
        $viewer->assign("RELMODULE_NAME", $relmodule_model->getName());
        $viewer->assign("FIELDS_LIST", $fields);
        $viewer->assign("RELATED_RECORD_MODEL", $relatedRecordModel);
        $viewer->assign("RECORD_STRUCTURE_MODEL", $recordStructureInstance);
        $viewer->assign("RELMODULE_NAME", $relmodule_model->getName());
        $viewer->assign("BLOCKID", $blockid);
        $viewer->assign("SOURCE_RECORD", $record);
        $viewer->assign("SOURCE_MODULE", $source_module);
        $viewer->assign("BLOCKTYPE", $blocktype);
        $viewer->assign("ROWNO", $rowno);
        $viewer->assign("USER_MODEL", Users_Record_Model::getCurrentUserModel());
        $viewer->assign("QUALIFIED_MODULE", $moduleName);
        $content = $viewer->view("RelatedRecordEdit.tpl", $moduleName, true);
        foreach ($selected_fields as $field) {
            $newFieldName = $this->reGenerateFieldName($field, $relatedRecordModel->getModuleName());
            $content = preg_replace("/name=\"" . $field . "\"/is", "name=\"" . $newFieldName . "\"", $content);
        }
        echo $content;
    }
    public function reGenerateFieldName($fieldname, $relModule)
    {
        return $relModule . "_" . $fieldname;
    }
}

?>