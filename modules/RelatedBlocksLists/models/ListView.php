<?php

class RelatedBlocksLists_ListView_Model extends Vtiger_ListView_Model
{
    public function getListViewEntries($pagingModel)
    {
        $db = PearDatabase::getInstance();
        $moduleName = $this->getModule()->get("name");
        $moduleFocus = CRMEntity::getInstance($moduleName);
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $queryGenerator = $this->get("query_generator");
        $listViewContoller = $this->get("listview_controller");
        $searchParams = $this->get("search_params");
        if (empty($searchParams)) {
            $searchParams = array();
        }
        $glue = "";
        if (0 < count($queryGenerator->getWhereFields()) && 0 < count($searchParams)) {
            $glue = QueryGenerator::$AND;
        }
        $queryGenerator->parseAdvFilterList($searchParams, $glue);
        $searchKey = $this->get("search_key");
        $searchValue = $this->get("search_value");
        $operator = $this->get("operator");
        if (!empty($searchKey)) {
            $queryGenerator->addUserSearchConditions(array("search_field" => $searchKey, "search_text" => $searchValue, "operator" => $operator));
        }
        $orderBy = $this->getForSql("orderby");
        $sortOrder = $this->getForSql("sortorder");
        if (empty($orderBy) && empty($sortOrder) && $moduleName != "Users") {
            $orderBy = "vtiger_crmentity.modifiedtime";
            $sortOrder = "DESC";
        }
        error_reporting(1);
        if (!empty($orderBy)) {
            $columnFieldMapping = $moduleModel->getColumnFieldMapping();
            $orderByFieldName = $columnFieldMapping[$orderBy];
            $orderByFieldModel = $moduleModel->getField($orderByFieldName);
            if ($orderByFieldModel && $orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::REFERENCE_TYPE) {
                $queryGenerator = $this->get("query_generator");
                $queryGenerator->addWhereField($orderByFieldName);
            }
        }
        $listQuery = $this->getQuery();
        $sourceModule = $this->get("src_module");
        if (!empty($sourceModule) && method_exists($moduleModel, "getQueryByModuleField")) {
            $overrideQuery = $moduleModel->getQueryByModuleField($sourceModule, $this->get("src_field"), $this->get("src_record"), $listQuery);
            if (!empty($overrideQuery)) {
                $listQuery = $overrideQuery;
            }
        }
        $sourceRecord = $this->get("src_record");
        $related_module_name = $this->get("related_module_name");
        if (!empty($sourceRecord)) {
            $recordModel = Vtiger_Record_Model::getInstanceById($sourceRecord);
            if ($related_module_name == "Events") {
                $relationListView = Vtiger_RelationListView_Model::getInstance($recordModel, "Calendar");
            } else {
                $relationListView = Vtiger_RelationListView_Model::getInstance($recordModel, $related_module_name);
            }
            $relatedQuery = $relationListView->getRelationQuery();
            $position = stripos($relatedQuery, " from ");
            if ($position) {
                $split = spliti(" from ", $relatedQuery);
                $splitCount = count($split);
                $conditonQuery = "SELECT vtiger_crmentity.crmid AS crmid";
                for ($i = 1; $i < $splitCount; $i++) {
                    $conditonQuery = $conditonQuery . " FROM " . $split[$i];
                }
            }
            $existedRecordsResult = $db->pquery($conditonQuery);
            if ($db->num_rows($existedRecordsResult)) {
                $existedRecords = array();
                while ($existedRecordRow = $db->fetchByAssoc($existedRecordsResult)) {
                    $existedRecords[] = $existedRecordRow["crmid"];
                }
                $listQuery .= " AND vtiger_crmentity.crmid NOT IN (" . implode(", ", $existedRecords) . ")";
            }
        }
        $selectedId = $this->get("selected_id");
        if (!empty($selectedId) && $selectedId != "") {
            $otherQuery = sprintf(" AND vtiger_crmentity.crmid NOT IN (%s)", $selectedId);
            $listQuery .= $otherQuery;
        }
        if ($related_module_name == "Events") {
            $listQuery .= " AND vtiger_activity.activitytype != 'Task'";
        } else {
            if ($related_module_name == "Calendar") {
                $listQuery .= " AND vtiger_activity.activitytype = 'Task'";
            }
        }
        $startIndex = $pagingModel->getStartIndex();
        $pageLimit = $pagingModel->getPageLimit();
        if (!empty($orderBy)) {
            if ($orderByFieldModel && $orderByFieldModel->isReferenceField()) {
                $referenceModules = $orderByFieldModel->getReferenceList();
                $referenceNameFieldOrderBy = array();
                foreach ($referenceModules as $referenceModuleName) {
                    $referenceModuleModel = Vtiger_Module_Model::getInstance($referenceModuleName);
                    $referenceNameFields = $referenceModuleModel->getNameFields();
                    $columnList = array();
                    foreach ($referenceNameFields as $nameField) {
                        $fieldModel = $referenceModuleModel->getField($nameField);
                        $columnList[] = $fieldModel->get("table") . $orderByFieldModel->getName() . "." . $fieldModel->get("column");
                    }
                    if (1 < count($columnList)) {
                        $referenceNameFieldOrderBy[] = getSqlForNameInDisplayFormat(array("first_name" => $columnList[0], "last_name" => $columnList[1]), "Users", "") . " " . $sortOrder;
                    } else {
                        $referenceNameFieldOrderBy[] = implode("", $columnList) . " " . $sortOrder;
                    }
                }
                $listQuery .= " ORDER BY " . implode(",", $referenceNameFieldOrderBy);
            } else {
                if (!empty($orderBy) && $orderBy === "smownerid") {
                    $fieldModel = Vtiger_Field_Model::getInstance("assigned_user_id", $moduleModel);
                    if ($fieldModel->getFieldDataType() == "owner") {
                        $orderBy = "COALESCE(CONCAT(vtiger_users.first_name,vtiger_users.last_name),vtiger_groups.groupname)";
                    }
                    $listQuery .= " ORDER BY " . $orderBy . " " . $sortOrder;
                } else {
                    $listQuery .= " ORDER BY " . $orderBy . " " . $sortOrder;
                }
            }
        }
        $viewid = ListViewSession::getCurrentView($moduleName);
        if (empty($viewid)) {
            $viewid = $pagingModel->get("viewid");
        }
        $_SESSION["lvs"][$moduleName][$viewid]["start"] = $pagingModel->get("page");
        ListViewSession::setSessionQuery($moduleName, $listQuery, $viewid);
        $listQuery .= " LIMIT " . $startIndex . "," . ($pageLimit + 1);
        $listResult = $db->pquery($listQuery, array());
        $listViewRecordModels = array();
        $listViewEntries = $listViewContoller->getListViewRecords($moduleFocus, $moduleName, $listResult);
        $pagingModel->calculatePageRange($listViewEntries);
        if ($pageLimit < $db->num_rows($listResult)) {
            array_pop($listViewEntries);
            $pagingModel->set("nextPageExists", true);
        } else {
            $pagingModel->set("nextPageExists", false);
        }
        $index = 0;
        foreach ($listViewEntries as $recordId => $record) {
            $rawData = $db->query_result_rowdata($listResult, $index++);
            $record["id"] = $recordId;
            $listViewRecordModels[$recordId] = $moduleModel->getRecordFromArray($record, $rawData);
        }
        return $listViewRecordModels;
    }
}

?>