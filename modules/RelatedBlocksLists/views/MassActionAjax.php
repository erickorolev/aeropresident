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
        $this->exposeMethod("saveWidthField");
    }    
    public function process(Vtiger_Request $request)
    {
        $mode = $request->get("mode");
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }
    /**
     * Function returns the popup edit form
     * @param Vtiger_Request $request
     */
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
        $blocksList = array();
        if ($blockid != "") {
            $sql = "SELECT * FROM `relatedblockslists_blocks` WHERE blockid=? AND active=1";
            $rs = $adb->pquery($sql, array($blockid));
        } else {
            $sql = "SELECT * FROM `relatedblockslists_blocks` WHERE module=? AND active=1";
            $rs = $adb->pquery($sql, array($source_module));
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
                $advanced_query = decode_html($row["advanced_query"]);
                $fields = array();
                $selected_fields = array();
                $multipicklist_fields = array();
                $reference_fields = array();
                $sqlField = "SELECT * FROM `relatedblockslists_fields` WHERE blockid = ? ORDER BY sequence";
                $rsFields = $adb->pquery($sqlField, array($blockid));
                if (0 < $adb->num_rows($rsFields)) {
                    $mandatoryFields = array();
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
                $new_selected_fields = array();
                foreach ($selected_fields as $fieldname) {
                    if ($relModule == "Events") {
                        $relModule = "Calendar";
                    }
                    $new_selected_fields[] = $this->reGenerateFieldName($fieldname, $relModule);
                }
                $blocksList[$blockid]["selected_fields"] = implode(",", $new_selected_fields);
                $blocksList[$blockid]["multipicklist_fields"] = implode(",", $multipicklist_fields);
                $blocksList[$blockid]["reference_fields"] = implode(",", $reference_fields);
                $relatedRecords = array();
                $recordStructureInstance = array();
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
                    $relatedQuery = str_replace(" GROUP BY vtiger_activity.activityid", "", $relatedQuery);
                    if (!empty($advanced_query)) {
                        $sqlQueryInjection = $this->getListQueryInjection($relModule);
                        if (!empty($sqlQueryInjection)) {
                            $split = preg_split("/from/i", $relatedQuery);
                            $relatedQuery = $split[0] . " FROM " . $sqlQueryInjection;
                        } else {
                            $pos = stripos($relatedQuery, "where");
                            if ($pos) {
                                $split = preg_split("/where/i", $relatedQuery);
                                $relatedQuery = $split[0] . " WHERE vtiger_crmentity.deleted = 0 ";
                            } else {
                                $relatedQuery = $relatedQuery . " WHERE vtiger_crmentity.deleted = 0 ";
                            }
                        }
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
                        $page_limit = self::PAGE_LIMIT;
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
                        $results = $adb->pquery($sqlField, array());
                        if (0 < $adb->num_rows($results)) {
                            $tablename = $adb->query_result($results, 0, "tablename");
                            $columnname = $adb->query_result($results, 0, "columnname");
                            $relatedQuery .= " AND " . $tablename . "." . $columnname . " = '" . $row["filtervalue"] . "'";
                        }
                    }
                    if (!empty($advanced_query)) {
                        $advanced_query = str_replace("\$recordid\$", $record, $advanced_query);
                        $advanced_query = trim($advanced_query);
                        $advanced_query = rtrim($advanced_query, ";");
                        $res_advanced_query = $adb->pquery($advanced_query, array());
                        if (0 < $adb->num_rows($res_advanced_query)) {
                            $relatedQuery .= " AND vtiger_crmentity.crmid IN (" . $advanced_query . ") ";
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
                                $relatedQuery = (string) $relatedQuery . " ORDER BY " . $qualifiedOrderBy . " " . $sorttype;
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
        $viewer->assign("CURRENT_TABID", getTabid($source_module));
        $viewer->assign("SOURCE_MODULE", $source_module);
        $viewer->assign("SOURCE_RECORD", $record);
        $viewer->assign("USER_MODEL", Users_Record_Model::getCurrentUserModel());
        $sql = "SELECT\n            actions\n        FROM\n            `vtiger_relatedlists`\n        WHERE\n            tabid = (\n                SELECT\n                    tabid\n                FROM\n                    vtiger_tab\n                WHERE\n                    `name` = '" . $source_module . "'\n            )\n        AND related_tabid = (\n            SELECT\n                tabid\n            FROM\n                vtiger_tab\n            WHERE\n                `name` = '" . $relModule . "'\n        )";
        $results = $adb->pquery($sql, array());
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
            $content = preg_replace("/name=\"" . $field . "\\[\\]\"/is", "name=\"" . $newFieldName . "[]\"", $content);
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
        $blocksList = array();
        if ($blockid != "") {
            $sql = "SELECT * FROM `relatedblockslists_blocks` WHERE blockid=? AND active=1";
            $rs = $adb->pquery($sql, array($blockid));
        } else {
            $sql = "SELECT * FROM `relatedblockslists_blocks` WHERE module=? AND active=1";
            $rs = $adb->pquery($sql, array($source_module));
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
                $advanced_query = decode_html($row["advanced_query"]);
                $fields = array();
                $sqlField = "SELECT * FROM `relatedblockslists_fields` WHERE blockid = ? ORDER BY sequence";
                $rsFields = $adb->pquery($sqlField, array($blockid));
                $recordModelBase = Vtiger_Record_Model::getCleanInstance($relModule);
                $recordStructureInstanceBase = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModelBase, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
                if (0 < $adb->num_rows($rsFields)) {
                    $mandatoryFields = array();
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
                $relatedRecords = array();
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
                    $relatedQuery = str_replace(" GROUP BY vtiger_activity.activityid", "", $relatedQuery);
                    if (!empty($advanced_query)) {
                        $sqlQueryInjection = $this->getListQueryInjection($relModule);
                        if (!empty($sqlQueryInjection)) {
                            $split = preg_split("/from/i", $relatedQuery);
                            $relatedQuery = $split[0] . " FROM " . $sqlQueryInjection;
                        } else {
                            $pos = stripos($relatedQuery, "where");
                            if ($pos) {
                                $split = preg_split("/where/i", $relatedQuery);
                                $relatedQuery = $split[0] . " WHERE vtiger_crmentity.deleted = 0 ";
                            } else {
                                $relatedQuery = $relatedQuery . " WHERE vtiger_crmentity.deleted = 0 ";
                            }
                        }
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
                        $page_limit = self::PAGE_LIMIT;
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
                        $results = $adb->pquery($sqlField, array());
                        if (0 < $adb->num_rows($results)) {
                            $tablename = $adb->query_result($results, 0, "tablename");
                            $columnname = $adb->query_result($results, 0, "columnname");
                            $relatedQuery .= " AND " . $tablename . "." . $columnname . " = '" . $row["filtervalue"] . "'";
                        }
                    }
                    if (!empty($advanced_query)) {
                        $advanced_query = str_replace("\$recordid\$", $record, $advanced_query);
                        $advanced_query = trim($advanced_query);
                        $advanced_query = rtrim($advanced_query, ";");
                        $res_advanced_query = $adb->pquery($advanced_query, array());
                        if (0 < $adb->num_rows($res_advanced_query)) {
                            $relatedQuery .= " AND vtiger_crmentity.crmid IN (" . $advanced_query . ") ";
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
                    if (!empty($sortfield) && !empty($sorttype)) {
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
                                $relatedQuery = (string) $relatedQuery . " ORDER BY " . $qualifiedOrderBy . " " . $sorttype;
                            }
                        }
                    }
                    $relatedQuery .= " LIMIT " . $startIndex . "," . $page_limit;
                    $rsData = $adb->pquery($relatedQuery, array());
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
        if ($record) {
            $parentRecordModule = Vtiger_Module_Model::getInstance($source_module);
            $parentRecordModel = Vtiger_Record_Model::getInstanceById($record, $parentRecordModule);
            $viewer->assign("PARENT_NAME", $parentRecordModel->getDisplayableValues());
        }
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
        $results = $adb->pquery($sql, array());
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
        $parentModule = $request->get("parent_module");
        $parentRecord = $request->get("parent_record");
        $viewer = $this->getViewer($request);
        $sql = "SELECT * FROM `relatedblockslists_blocks` WHERE blockid=? AND active=1";
        $rs = $adb->pquery($sql, array($blockid));
        $block_filter_field = $adb->query_result($rs, 0, "filterfield");
        $block_filter_value = $adb->query_result($rs, 0, "filtervalue");
        $relmodule_model = Vtiger_Module_Model::getInstance($relmodule);
        $sqlField = "SELECT * FROM `relatedblockslists_fields` WHERE blockid = ? ORDER BY sequence";
        $rsFields = $adb->pquery($sqlField, array($blockid));
        $fields = array();
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
        if ($parentRecord) {
            $parentRecordModule = Vtiger_Module_Model::getInstance($parentModule);
            $parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecord, $parentRecordModule);
            $viewer->assign("PARENT_NAME", $parentRecordModel->getDisplayableValues());
        }
        $viewer->assign("RELMODULE_MODEL", $relmodule_model);
        $viewer->assign("RELMODULE_NAME", $relmodule_model->getName());
        $viewer->assign("FIELDS_LIST", $fields);
        $viewer->assign("RELATED_RECORD_MODEL", $recordModel);
        $viewer->assign("RECORD_STRUCTURE_MODEL", $recordStructureInstance);
        $viewer->assign("BLOCKID", $blockid);
        $viewer->assign("BLOCK_FILTER_FIELD", $block_filter_field);
        $viewer->assign("BLOCK_FILTER_VALUE", $block_filter_value);
        $viewer->assign("SOURCE_MODULE", $parentModule);
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
        $rs = $adb->pquery($sql, array($blockid));
        $blocktype = $adb->query_result($rs, 0, "type");
        $viewer = $this->getViewer($request);
        if (!empty($record)) {
            $recordModel = Vtiger_Record_Model::getInstanceById($record);
            $source_module = $recordModel->getModuleName();
        }
        $relatedRecordModel = Vtiger_Record_Model::getInstanceById($related_record);
        $relmodule_model = $relatedRecordModel->getModule();
        $sqlField = "SELECT * FROM `relatedblockslists_fields` WHERE blockid = ? ORDER BY sequence";
        $rsFields = $adb->pquery($sqlField, array($blockid));
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
        $rs = $adb->pquery($sql, array($blockid));
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
        $rsFields = $adb->pquery($sqlField, array($blockid));
        $recordModel = Vtiger_Record_Model::getCleanInstance($relmodule_model->getName());
        $recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
        $selected_fields = array();
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
        return (string) $relModule . "_" . $fieldname;
    }
    public function getListQueryInjection($module)
    {
        global $current_user;
        require "user_privileges/user_privileges_" . $current_user->id . ".php";
        require "user_privileges/sharing_privileges_" . $current_user->id . ".php";
        $userNameSql = getSqlForNameInDisplayFormat(array("first_name" => "vtiger_users.first_name", "last_name" => "vtiger_users.last_name"), "Users");
        switch ($module) {
            case "HelpDesk":
                $query = " vtiger_troubletickets\n\t\t\tINNER JOIN vtiger_ticketcf\n\t\t\t\tON vtiger_ticketcf.ticketid = vtiger_troubletickets.ticketid\n\t\t\tINNER JOIN vtiger_crmentity\n\t\t\t\tON vtiger_crmentity.crmid = vtiger_troubletickets.ticketid\n\t\t\tLEFT JOIN vtiger_groups\n\t\t\t\tON vtiger_groups.groupid = vtiger_crmentity.smownerid\n\t\t\tLEFT JOIN vtiger_users\n\t\t\t\tON vtiger_crmentity.smownerid = vtiger_users.id";
                $query .= " " . getNonAdminAccessControlQuery($module, $current_user);
                $query .= "WHERE vtiger_crmentity.deleted = 0 ";
                break;
            case "Accounts":
                $query = " vtiger_account\n\t\t\tINNER JOIN vtiger_crmentity\n\t\t\t\tON vtiger_crmentity.crmid = vtiger_account.accountid\n\t\t\tINNER JOIN vtiger_accountbillads\n\t\t\t\tON vtiger_account.accountid = vtiger_accountbillads.accountaddressid\n\t\t\tINNER JOIN vtiger_accountshipads\n\t\t\t\tON vtiger_account.accountid = vtiger_accountshipads.accountaddressid\n\t\t\tINNER JOIN vtiger_accountscf\n\t\t\t\tON vtiger_account.accountid = vtiger_accountscf.accountid\n\t\t\tLEFT JOIN vtiger_groups\n\t\t\t\tON vtiger_groups.groupid = vtiger_crmentity.smownerid\n\t\t\tLEFT JOIN vtiger_users\n\t\t\t\tON vtiger_users.id = vtiger_crmentity.smownerid";
                $query .= getNonAdminAccessControlQuery($module, $current_user);
                $query .= "WHERE vtiger_crmentity.deleted = 0 ";
                break;
            case "Potentials":
                $query = " vtiger_potential\n\t\t\tINNER JOIN vtiger_crmentity\n\t\t\t\tON vtiger_crmentity.crmid = vtiger_potential.potentialid\n\t\t\tINNER JOIN vtiger_potentialscf\n\t\t\t\tON vtiger_potentialscf.potentialid = vtiger_potential.potentialid\n\t\t\tLEFT JOIN vtiger_groups\n\t\t\t\tON vtiger_groups.groupid = vtiger_crmentity.smownerid\n\t\t\tLEFT JOIN vtiger_users\n\t\t\t\tON vtiger_users.id = vtiger_crmentity.smownerid";
                $query .= getNonAdminAccessControlQuery($module, $current_user);
                $query .= "WHERE vtiger_crmentity.deleted = 0 ";
                break;
            case "Leads":
                $query = " vtiger_leaddetails\n\t\t\tINNER JOIN vtiger_crmentity\n\t\t\t\tON vtiger_crmentity.crmid = vtiger_leaddetails.leadid\n\t\t\tINNER JOIN vtiger_leadsubdetails\n\t\t\t\tON vtiger_leadsubdetails.leadsubscriptionid = vtiger_leaddetails.leadid\n\t\t\tINNER JOIN vtiger_leadaddress\n\t\t\t\tON vtiger_leadaddress.leadaddressid = vtiger_leadsubdetails.leadsubscriptionid\n\t\t\tINNER JOIN vtiger_leadscf\n\t\t\t\tON vtiger_leaddetails.leadid = vtiger_leadscf.leadid\n\t\t\tLEFT JOIN vtiger_groups\n\t\t\t\tON vtiger_groups.groupid = vtiger_crmentity.smownerid\n\t\t\tLEFT JOIN vtiger_users\n\t\t\t\tON vtiger_users.id = vtiger_crmentity.smownerid";
                $query .= getNonAdminAccessControlQuery($module, $current_user);
                $query .= "WHERE vtiger_crmentity.deleted = 0 AND vtiger_leaddetails.converted = 0 ";
                break;
            case "Products":
                $query = " vtiger_products\n\t\t\tINNER JOIN vtiger_crmentity\n\t\t\t\tON vtiger_crmentity.crmid = vtiger_products.productid\n\t\t\tINNER JOIN vtiger_productcf\n\t\t\t\tON vtiger_products.productid = vtiger_productcf.productid\n\t\t\tLEFT JOIN vtiger_groups\n\t\t\t\tON vtiger_groups.groupid = vtiger_crmentity.smownerid\n\t\t\tLEFT JOIN vtiger_users\n\t\t\t\tON vtiger_users.id = vtiger_crmentity.smownerid";
                $query .= getNonAdminAccessControlQuery($module, $current_user);
                $query .= " WHERE vtiger_crmentity.deleted = 0 ";
                break;
            case "Documents":
                $query = " vtiger_notes\n\t\t\tINNER JOIN vtiger_crmentity\n\t\t\t\tON vtiger_crmentity.crmid = vtiger_notes.notesid\n\t\t\tLEFT JOIN vtiger_groups\n\t\t\t\tON vtiger_groups.groupid = vtiger_crmentity.smownerid\n\t\t\tLEFT JOIN vtiger_users\n\t\t\t\tON vtiger_users.id = vtiger_crmentity.smownerid\n\t\t\tLEFT JOIN vtiger_attachmentsfolder\n\t\t\t\tON vtiger_notes.folderid = vtiger_attachmentsfolder.folderid";
                $query .= getNonAdminAccessControlQuery($module, $current_user);
                $query .= "WHERE vtiger_crmentity.deleted = 0 ";
                break;
            case "Contacts":
                $query = " vtiger_contactdetails\n\t\t\tINNER JOIN vtiger_crmentity\n\t\t\t\tON vtiger_crmentity.crmid = vtiger_contactdetails.contactid\n\t\t\tINNER JOIN vtiger_contactaddress\n\t\t\t\tON vtiger_contactaddress.contactaddressid = vtiger_contactdetails.contactid\n\t\t\tINNER JOIN vtiger_contactsubdetails\n\t\t\t\tON vtiger_contactsubdetails.contactsubscriptionid = vtiger_contactdetails.contactid\n\t\t\tINNER JOIN vtiger_contactscf\n\t\t\t\tON vtiger_contactscf.contactid = vtiger_contactdetails.contactid\n\t\t\tLEFT JOIN vtiger_groups\n\t\t\t\tON vtiger_groups.groupid = vtiger_crmentity.smownerid\n\t\t\tLEFT JOIN vtiger_users\n\t\t\t\tON vtiger_users.id = vtiger_crmentity.smownerid\n\t\t\tLEFT JOIN vtiger_contactdetails vtiger_contactdetails2\n\t\t\t\tON vtiger_contactdetails.reportsto = vtiger_contactdetails2.contactid\n\t\t\tLEFT JOIN vtiger_customerdetails\n\t\t\t\tON vtiger_customerdetails.customerid = vtiger_contactdetails.contactid";
                $query .= getNonAdminAccessControlQuery($module, $current_user);
                $query .= "WHERE vtiger_crmentity.deleted = 0 ";
                break;
            case "Calendar":
                $query = " vtiger_activity\n\t\tLEFT JOIN vtiger_activitycf\n\t\t\tON vtiger_activitycf.activityid = vtiger_activity.activityid\n\t\tLEFT JOIN vtiger_cntactivityrel\n\t\t\tON vtiger_cntactivityrel.activityid = vtiger_activity.activityid\n\t\tLEFT JOIN vtiger_contactdetails\n\t\t\tON vtiger_contactdetails.contactid = vtiger_cntactivityrel.contactid\n\t\tLEFT JOIN vtiger_seactivityrel\n\t\t\tON vtiger_seactivityrel.activityid = vtiger_activity.activityid\n\t\tLEFT OUTER JOIN vtiger_activity_reminder\n\t\t\tON vtiger_activity_reminder.activity_id = vtiger_activity.activityid\n\t\tLEFT JOIN vtiger_crmentity\n\t\t\tON vtiger_crmentity.crmid = vtiger_activity.activityid\n\t\tLEFT JOIN vtiger_users\n\t\t\tON vtiger_users.id = vtiger_crmentity.smownerid\n\t\tLEFT JOIN vtiger_groups\n\t\t\tON vtiger_groups.groupid = vtiger_crmentity.smownerid\n\t\tLEFT JOIN vtiger_users vtiger_users2\n\t\t\tON vtiger_crmentity.modifiedby = vtiger_users2.id\n\t\tLEFT JOIN vtiger_groups vtiger_groups2\n\t\t\tON vtiger_crmentity.modifiedby = vtiger_groups2.groupid";
                $query .= getNonAdminAccessControlQuery($module, $current_user);
                $query .= " WHERE vtiger_crmentity.deleted = 0 AND activitytype != 'Emails' ";
                break;
            case "Emails":
                $query = " vtiger_activity\n\t\t\tINNER JOIN vtiger_crmentity\n\t\t\t\tON vtiger_crmentity.crmid = vtiger_activity.activityid\n\t\t\tLEFT JOIN vtiger_users\n\t\t\t\tON vtiger_users.id = vtiger_crmentity.smownerid\n\t\t\tLEFT JOIN vtiger_seactivityrel\n\t\t\t\tON vtiger_seactivityrel.activityid = vtiger_activity.activityid\n\t\t\tLEFT JOIN vtiger_groups\n\t\t\t\tON vtiger_groups.groupid = vtiger_crmentity.smownerid\n\t\t\tLEFT JOIN vtiger_salesmanactivityrel\n\t\t\t\tON vtiger_salesmanactivityrel.activityid = vtiger_activity.activityid\n\t\t\tLEFT JOIN vtiger_emaildetails\n\t\t\t\tON vtiger_emaildetails.emailid = vtiger_activity.activityid";
                $query .= getNonAdminAccessControlQuery($module, $current_user);
                $query .= "WHERE vtiger_activity.activitytype = 'Emails'";
                $query .= "AND vtiger_crmentity.deleted = 0 ";
                break;
            case "Faq":
                $query = " vtiger_faq\n\t\t\tINNER JOIN vtiger_crmentity\n\t\t\t\tON vtiger_crmentity.crmid = vtiger_faq.id";
                $query .= getNonAdminAccessControlQuery($module, $current_user);
                $query .= "WHERE vtiger_crmentity.deleted = 0 ";
                break;
            case "Vendors":
                $query = " vtiger_vendor\n\t\t\tINNER JOIN vtiger_crmentity\n\t\t\t\tON vtiger_crmentity.crmid = vtiger_vendor.vendorid\n\t\t\tINNER JOIN vtiger_vendorcf\n\t\t\t\tON vtiger_vendor.vendorid = vtiger_vendorcf.vendorid\n\t\t\tWHERE vtiger_crmentity.deleted = 0 ";
                break;
            case "PriceBooks":
                $query = " vtiger_pricebook\n\t\t\tINNER JOIN vtiger_crmentity\n\t\t\t\tON vtiger_crmentity.crmid = vtiger_pricebook.pricebookid\n\t\t\tINNER JOIN vtiger_pricebookcf\n\t\t\t\tON vtiger_pricebook.pricebookid = vtiger_pricebookcf.pricebookid\n\t\t\tLEFT JOIN vtiger_currency_info\n\t\t\t\tON vtiger_pricebook.currency_id = vtiger_currency_info.id\n\t\t\tWHERE vtiger_crmentity.deleted = 0 ";
                break;
            case "Quotes":
                $query = " vtiger_quotes\n\t\t\tINNER JOIN vtiger_crmentity\n\t\t\t\tON vtiger_crmentity.crmid = vtiger_quotes.quoteid\n\t\t\tINNER JOIN vtiger_quotesbillads\n\t\t\t\tON vtiger_quotes.quoteid = vtiger_quotesbillads.quotebilladdressid\n\t\t\tINNER JOIN vtiger_quotesshipads\n\t\t\t\tON vtiger_quotes.quoteid = vtiger_quotesshipads.quoteshipaddressid\n\t\t\tLEFT JOIN vtiger_quotescf\n\t\t\t\tON vtiger_quotes.quoteid = vtiger_quotescf.quoteid\n\t\t\tLEFT JOIN vtiger_currency_info\n\t\t\t\tON vtiger_quotes.currency_id = vtiger_currency_info.id\n\t\t\tLEFT JOIN vtiger_groups\n\t\t\t\tON vtiger_groups.groupid = vtiger_crmentity.smownerid\n\t\t\tLEFT JOIN vtiger_users\n\t\t\t\tON vtiger_users.id = vtiger_crmentity.smownerid\n\t\t\tLEFT JOIN vtiger_users as vtiger_usersQuotes\n\t\t\t        ON vtiger_usersQuotes.id = vtiger_quotes.inventorymanager";
                $query .= getNonAdminAccessControlQuery($module, $current_user);
                $query .= "WHERE vtiger_crmentity.deleted = 0 ";
                break;
            case "PurchaseOrder":
                $query = " vtiger_purchaseorder\n\t\t\tINNER JOIN vtiger_crmentity\n\t\t\t\tON vtiger_crmentity.crmid = vtiger_purchaseorder.purchaseorderid\n\t\t\tLEFT JOIN vtiger_purchaseordercf\n\t\t\t\tON vtiger_purchaseordercf.purchaseorderid = vtiger_purchaseorder.purchaseorderid\n\t\t\tLEFT JOIN vtiger_currency_info\n\t\t\t\tON vtiger_purchaseorder.currency_id = vtiger_currency_info.id\n\t\t\tLEFT JOIN vtiger_groups\n\t\t\t\tON vtiger_groups.groupid = vtiger_crmentity.smownerid\n\t\t\tLEFT JOIN vtiger_users\n\t\t\t\tON vtiger_users.id = vtiger_crmentity.smownerid";
                $query .= getNonAdminAccessControlQuery($module, $current_user);
                $query .= "WHERE vtiger_crmentity.deleted = 0 ";
                break;
            case "SalesOrder":
                $query = " vtiger_salesorder\n\t\t\tINNER JOIN vtiger_crmentity\n\t\t\t\tON vtiger_crmentity.crmid = vtiger_salesorder.salesorderid\n\t\t\tINNER JOIN vtiger_sobillads\n\t\t\t\tON vtiger_salesorder.salesorderid = vtiger_sobillads.sobilladdressid\n\t\t\tINNER JOIN vtiger_soshipads\n\t\t\t\tON vtiger_salesorder.salesorderid = vtiger_soshipads.soshipaddressid\n\t\t\tLEFT JOIN vtiger_salesordercf\n\t\t\t\tON vtiger_salesordercf.salesorderid = vtiger_salesorder.salesorderid\n\t\t\tLEFT JOIN vtiger_currency_info\n\t\t\t\tON vtiger_salesorder.currency_id = vtiger_currency_info.id\n\t\t\tLEFT JOIN vtiger_groups\n\t\t\t\tON vtiger_groups.groupid = vtiger_crmentity.smownerid\n\t\t\tLEFT JOIN vtiger_users\n\t\t\t\tON vtiger_users.id = vtiger_crmentity.smownerid";
                $query .= getNonAdminAccessControlQuery($module, $current_user);
                $query .= "WHERE vtiger_crmentity.deleted = 0 ";
                break;
            case "Invoice":
                $query = " vtiger_invoice\n\t\t\tINNER JOIN vtiger_crmentity\n\t\t\t\tON vtiger_crmentity.crmid = vtiger_invoice.invoiceid\n\t\t\tINNER JOIN vtiger_invoicebillads\n\t\t\t\tON vtiger_invoice.invoiceid = vtiger_invoicebillads.invoicebilladdressid\n\t\t\tINNER JOIN vtiger_invoiceshipads\n\t\t\t\tON vtiger_invoice.invoiceid = vtiger_invoiceshipads.invoiceshipaddressid\n\t\t\tLEFT JOIN vtiger_currency_info\n\t\t\t\tON vtiger_invoice.currency_id = vtiger_currency_info.id\n\t\t\tINNER JOIN vtiger_invoicecf\n\t\t\t\tON vtiger_invoice.invoiceid = vtiger_invoicecf.invoiceid\n\t\t\tLEFT JOIN vtiger_groups\n\t\t\t\tON vtiger_groups.groupid = vtiger_crmentity.smownerid\n\t\t\tLEFT JOIN vtiger_users\n\t\t\t\tON vtiger_users.id = vtiger_crmentity.smownerid";
                $query .= getNonAdminAccessControlQuery($module, $current_user);
                $query .= "WHERE vtiger_crmentity.deleted = 0 ";
                break;
            case "Campaigns":
                $query = " vtiger_campaign\n\t\t\tINNER JOIN vtiger_crmentity\n\t\t\t\tON vtiger_crmentity.crmid = vtiger_campaign.campaignid\n\t\t\tINNER JOIN vtiger_campaignscf\n\t\t\t        ON vtiger_campaign.campaignid = vtiger_campaignscf.campaignid\n\t\t\tLEFT JOIN vtiger_groups\n\t\t\t\tON vtiger_groups.groupid = vtiger_crmentity.smownerid\n\t\t\tLEFT JOIN vtiger_users\n\t\t\t\tON vtiger_users.id = vtiger_crmentity.smownerid";
                $query .= getNonAdminAccessControlQuery($module, $current_user);
                $query .= "WHERE vtiger_crmentity.deleted = 0 ";
                break;
            case "Users":
                $query = " vtiger_users\n\t\t\t\t \tINNER JOIN vtiger_user2role ON vtiger_users.id = vtiger_user2role.userid\n\t\t\t\t \tINNER JOIN vtiger_role ON vtiger_user2role.roleid = vtiger_role.roleid\n\t\t\t\t\tWHERE deleted=0 AND status <> 'Inactive'";
                break;
            case "ProjectTask":
                $query = " vtiger_projecttask\n\t\t\tINNER JOIN vtiger_crmentity\n\t\t\t\tON vtiger_crmentity.crmid = vtiger_projecttask.projecttaskid\n\t\t\tINNER JOIN vtiger_projecttaskcf\n\t\t\t        ON vtiger_projecttask.projecttaskid = vtiger_projecttaskcf.projecttaskid\n\t\t\tLEFT JOIN vtiger_groups\n\t\t\t\tON vtiger_groups.groupid = vtiger_crmentity.smownerid\n\t\t\tLEFT JOIN vtiger_users\n\t\t\t\tON vtiger_users.id = vtiger_crmentity.smownerid";
                $query .= getNonAdminAccessControlQuery($module, $current_user);
                $query .= "WHERE vtiger_crmentity.deleted = 0 ";
                break;
            case "Project":
                $query = " vtiger_project\n\t\t\tINNER JOIN vtiger_crmentity\n\t\t\t\tON vtiger_crmentity.crmid = vtiger_project.projectid\n\t\t\tINNER JOIN vtiger_projectcf\n\t\t\t        ON vtiger_project.projectid = vtiger_projectcf.projectid\n\t\t\tLEFT JOIN vtiger_groups\n\t\t\t\tON vtiger_groups.groupid = vtiger_crmentity.smownerid\n\t\t\tLEFT JOIN vtiger_users\n\t\t\t\tON vtiger_users.id = vtiger_crmentity.smownerid";
                $query .= getNonAdminAccessControlQuery($module, $current_user);
                $query .= "WHERE vtiger_crmentity.deleted = 0 ";
                break;
            case "ProjectMilestone":
                $query = " vtiger_projectmilestone\n\t\t\tINNER JOIN vtiger_crmentity\n\t\t\t\tON vtiger_crmentity.crmid = vtiger_projectmilestone.projectmilestoneid\n\t\t\tINNER JOIN vtiger_projectmilestonecf\n\t\t\t        ON vtiger_projectmilestone.projectmilestoneid = vtiger_projectmilestonecf.projectmilestoneid\n\t\t\tLEFT JOIN vtiger_groups\n\t\t\t\tON vtiger_groups.groupid = vtiger_crmentity.smownerid\n\t\t\tLEFT JOIN vtiger_users\n\t\t\t\tON vtiger_users.id = vtiger_crmentity.smownerid";
                $query .= getNonAdminAccessControlQuery($module, $current_user);
                $query .= "WHERE vtiger_crmentity.deleted = 0 ";
                break;
            default:
                $focus = CRMEntity::getInstance($module);
                if (isset($focus->customFieldTable)) {
                    $cusTableName = $focus->customFieldTable[0];
                } else {
                    $cusTableName = "vtiger_" . strtolower($module) . "cf";
                }
                $query = " " . $focus->table_name . "\n                            INNER JOIN vtiger_crmentity\n                                ON vtiger_crmentity.crmid = " . $focus->table_name . "." . $focus->table_index . "\n                            INNER JOIN " . $cusTableName . "\n                                     ON " . $cusTableName . "." . $focus->table_index . "=" . $focus->table_name . "." . $focus->table_index . "\n                            LEFT JOIN vtiger_groups\n                                ON vtiger_groups.groupid = vtiger_crmentity.smownerid\n                            LEFT JOIN vtiger_users\n                            ON vtiger_users.id = vtiger_crmentity.smownerid";
                $query .= getNonAdminAccessControlQuery($module, $current_user);
                $query .= "WHERE vtiger_crmentity.deleted = 0 ";
        }
        return $query;
    }
}

?>