<?php

class RelatedBlocksLists_ActionAjax_Action extends Vtiger_Action_Controller
{
    public function checkPermission(Vtiger_Request $request)
    {
    }
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod("enableModule");
        $this->exposeMethod("checkEnable");
        $this->exposeMethod("deleteBlock");
        $this->exposeMethod("saveRelatedRecord");
        $this->exposeMethod("getConfiguredBlock");
        $this->exposeMethod("addExistedRecords");
        $this->exposeMethod("updateSequenceNumber");
        $this->exposeMethod("saveWidthField");
    }
    
    public function process(Vtiger_Request $request)
    {
        $mode = $request->get("mode");
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }
    public function updateSequenceNumber(Vtiger_Request $request)
    {
        global $adb;
        $sequence = $request->get("sequence");
        if (0 < count($sequence)) {
            foreach ($sequence as $block_id => $block_sequence) {
                $sql = "UPDATE `relatedblockslists_blocks` SET sequence = '" . $block_sequence . "' WHERE blockid = '" . $block_id . "'";
                $adb->pquery($sql, array());
            }
        }
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult(array("result" => "success"));
        $response->emit();
    }
    public function enableModule(Vtiger_Request $request)
    {
        global $adb;
        $value = $request->get("value");
        $adb->pquery("UPDATE `relatedblockslists_settings` SET `enable`=?", array($value));
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult(array("result" => "success"));
        $response->emit();
    }
    public function checkEnable(Vtiger_Request $request)
    {
        global $adb;
        $rs = $adb->pquery("SELECT `enable` FROM `relatedblockslists_settings`;", array());
        $enable = $adb->query_result($rs, 0, "enable");
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult(array("enable" => $enable));
        $response->emit();
    }
    public function deleteBlock(Vtiger_Request $request)
    {
        $response = new Vtiger_Response();
        try {
            $db = PearDatabase::getInstance();
            $blockid = $request->get("blockid");
            $sql = "DELETE FROM `relatedblockslists_blocks` WHERE (`blockid`=?)";
            $db->pquery($sql, array($blockid));
            $sql = "DELETE FROM `relatedblockslists_fields` WHERE (`blockid`=?)";
            $db->pquery($sql, array($blockid));
            $response->setResult(array("success" => true));
        } catch (Exception $e) {
            $response->setError($e->getCode(), $e->getMessage());
        }
        $response->emit();
    }
    public function getConfiguredBlock(Vtiger_Request $request)
    {
        global $adb;
        global $vtiger_current_version;
        $response = new Vtiger_Response();
        try {
            $source_module = $request->get("source_module");
            if ($source_module == "Calendar" && $request->get("parent_record") != "") {
                $recordModel = Vtiger_Record_Model::getInstanceById($request->get("parent_record"));
                $source_module = $recordModel->getType();
            }
            $sql = "SELECT rb.*, vb.blocklabel FROM `relatedblockslists_blocks` rb\r\n              INNER JOIN vtiger_blocks vb ON vb.blockid=rb.after_block";
            if ($request->get("isSetting")) {
                $sql .= " WHERE rb.module=?";
            } else {
                $sql .= " WHERE rb.module=? AND rb.active=1";
            }
            $sql .= " ORDER BY rb.sequence ASC";
            $rs = $adb->pquery($sql, array($source_module));
            $arrBlocks = array();
            if (0 < $adb->num_rows($rs)) {
                while ($row = $adb->fetch_array($rs)) {
                    $after_block = $row["after_block"];
                    $sequence = $row["sequence"];
                    $picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($row["relmodule"]);
                    if (version_compare($vtiger_current_version, "7.0.0", "<")) {
                        $arrBlocks[$row["blockid"]] = array($after_block, vtranslate($row["blocklabel"], $row["module"]), $sequence, json_encode($picklistDependencyDatasource));
                    } else {
                        $arrBlocks[$row["blockid"]] = array($after_block, vtranslate($row["blocklabel"], $row["module"]), $sequence, Vtiger_Functions::jsonEncode($picklistDependencyDatasource));
                    }
                }
            }
            if (!version_compare($vtiger_current_version, "7.0.0", "<")) {
                if ($request->get("isSetting")) {
                }
                $arr = array();
                foreach ($arrBlocks as $blockId => $item) {
                    $arr[] = array("blockId" => $blockId, "blockData" => $item);
                }
                $arrBlocks = json_encode($arr);
            }
            $response->setResult($arrBlocks);
        } catch (Exception $e) {
            $response->setError($e->getCode(), $e->getMessage());
        }
        $response->emit();
    }
    public function changeAfterBlock($sequence, $arrBlockFieldsCount)
    {
        if ($arrBlockFieldsCount[$sequence]["field"] == 0 && $arrBlockFieldsCount[$sequence] != NULL) {
            $preBlockSeq = $sequence - 1;
            $this->changeAfterBlock($preBlockSeq, $arrBlockFieldsCount);
        } else {
            return $arrBlockFieldsCount[$sequence]["blockid"];
        }
    }
    public function saveRelatedRecord(Vtiger_Request $request)
    {
        error_reporting(0);
        global $adb;
        $response = new Vtiger_Response();
        try {
            $blockid = $request->get("blockid");
            $parentRecordId = $request->get("recordid");
            $sql = "SELECT * FROM `relatedblockslists_blocks`";
            $sql .= " WHERE blockid=? AND active=1";
            $rs = $adb->pquery($sql, array($blockid));
            $source_module = $adb->query_result($rs, 0, "module");
            $related_module = $adb->query_result($rs, 0, "relmodule");
            $related_module_base = $related_module;
            $parentModuleModel = Vtiger_Module_Model::getInstance($source_module);
            $relModuleModel = Vtiger_Module_Model::getInstance($related_module);
            $fieldModelList = $relModuleModel->getFields();
            if ($related_module == "Events") {
                $related_module = "Calendar";
            }
            $relRecordModel = Vtiger_Record_Model::getCleanInstance($related_module);
            $relRecordModel->set("mode", "");
            $arrRelModuleModel[$related_module] = $relModuleModel;
            $arrRelModuleFields[$related_module] = $fieldModelList;
            foreach ($fieldModelList as $fieldName => $fieldModel) {
                $fieldValue = $request->get($fieldName);
                $fieldDataType = $fieldModel->getFieldDataType();
                if ($fieldDataType == "time") {
                    $fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
                }
                if ($fieldValue !== NULL) {
                    if (!is_array($fieldValue)) {
                        $fieldValue = trim($fieldValue);
                    }
                    $relRecordModel->set($fieldName, $fieldValue);
                }
                if (empty($fieldValue)) {
                    $sql = "SELECT filterfield,filtervalue FROM `relatedblockslists_blocks` WHERE blockid='" . $blockid . "' AND relmodule='" . $related_module . "'";
                    $results = $adb->pquery($sql, array());
                    if (0 < $adb->num_rows($results)) {
                        while ($row = $adb->fetchByAssoc($results)) {
                            $filterfield = $row["filterfield"];
                            $filtervalue = $row["filtervalue"];
                            $relRecordModel->set($filterfield, $filtervalue);
                        }
                    } else {
                        $default_value = $fieldModel->get("defaultvalue");
                        if (!empty($default_value)) {
                            $relRecordModel->set($fieldName, $default_value);
                        }
                    }
                }
            }
            if ($related_module == "Calendar") {
                $moduleModel = new RelatedBlocksLists_Module_Model();
                $relRecordModel = $moduleModel->setDataForCalendarRecord($relRecordModel, $_REQUEST, $related_module_base);
            }
            $isParentRecordRel = true;
            if ($source_module == "Accounts" && in_array($related_module, array("Contacts", "Quotes", "SalesOrder", "Invoice"))) {
                $relRecordModel->set("account_id", $parentRecordId);
            } else {
                if ($source_module == "Contacts" && in_array($related_module, array("PurchaseOrder", "Quotes", "SalesOrder", "Invoice"))) {
                    $relRecordModel->set("contact_id", $parentRecordId);
                } else {
                    if ($source_module == "Campaigns" && $related_module == "Potentials") {
                        $relRecordModel->set("campaignid", $parentRecordId);
                    } else {
                        if ($source_module == "Potentials" && in_array($related_module, array("Quotes", "SalesOrder"))) {
                            $relRecordModel->set("potential_id", $parentRecordId);
                        } else {
                            if ($source_module == "Products" && in_array($related_module, array("Faq", "HelpDesk", "Campaigns"))) {
                                $relRecordModel->set("productid", $parentRecordId);
                            } else {
                                if ($source_module == "Quotes" && $related_module == "SalesOrder") {
                                    $relRecordModel->set("quote_id", $parentRecordId);
                                } else {
                                    if ($source_module == "SalesOrder" && $related_module == "Invoice") {
                                        $relRecordModel->set("salesorder_id", $parentRecordId);
                                    } else {
                                        if ($source_module == "Vendors" && in_array($related_module, array("Products", "PurchaseOrder"))) {
                                            $relRecordModel->set("vendor_id", $parentRecordId);
                                        } else {
                                            if ($related_module == "Calendar" || $related_module == "Events") {
                                                if ($source_module == "Contacts") {
                                                    $_REQUEST["contactidlist"] = $parentRecordId;
                                                } else {
                                                    $relRecordModel->set("parent_id", $parentRecordId);
                                                }
                                            } else {
                                                $dependentFieldSql = $adb->pquery("SELECT tabid, fieldname, columnname FROM vtiger_field WHERE uitype='10' AND" . " fieldid IN (SELECT fieldid FROM vtiger_fieldmodulerel WHERE relmodule=? AND module=?)", array($source_module, $related_module));
                                                $numOfFields = $adb->num_rows($dependentFieldSql);
                                                if (0 < $numOfFields) {
                                                    $dependentColumn = $adb->query_result($dependentFieldSql, 0, "columnname");
                                                    $dependentField = $adb->query_result($dependentFieldSql, 0, "fieldname");
                                                    $relRecordModel->set($dependentField, $parentRecordId);
                                                } else {
                                                    $isParentRecordRel = false;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if ($related_module == "Documents") {
                $isParentRecordRel = false;
                $relRecordModel->set("filestatus", 1);
                $relRecordModel->set("notes_title", $_FILES["filename"]["name"]);
            }
            $relRecordModel->save();
            $relRecordId = $relRecordModel->getId();
            if (!$isParentRecordRel) {
                $relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relModuleModel);
                if ($relationModel) {
                    $relationModel->addRelation($parentRecordId, $relRecordId);
                }
            }
            $parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId);
            $parentRecordModel->set("mode", "edit");
            $_REQUEST["ajxaction"] = "DETAILVIEW";
            $parentRecordModel->save();
            $response->setResult(array("related_record" => $relRecordId));
        } catch (Exception $e) {
            $response->setError($e->getCode(), $e->getMessage());
        }
        $response->emit();
    }
    public function addExistedRecords(Vtiger_Request $request)
    {
        global $adb;
        $response = new Vtiger_Response();
        try {
            $blockid = $request->get("blockid");
            $parentRecordId = $request->get("recordid");
            $relatedIdList = $request->get("relatedIdList");
            $sql = "SELECT * FROM `relatedblockslists_blocks`";
            $sql .= " WHERE blockid=? AND active=1";
            $rs = $adb->pquery($sql, array($blockid));
            $source_module = $adb->query_result($rs, 0, "module");
            $block_type = $adb->query_result($rs, 0, "type");
            $related_module = $adb->query_result($rs, 0, "relmodule");
            $parentModuleModel = Vtiger_Module_Model::getInstance($source_module);
            $relModuleModel = Vtiger_Module_Model::getInstance($related_module);
            foreach ($relatedIdList as $relRecordId) {
                $relRecordModel = Vtiger_Record_Model::getInstanceById($relRecordId);
                $relRecordId = $relRecordModel->getId();
                if ($source_module == "Accounts" && in_array($related_module, array("Contacts", "Quotes", "SalesOrder", "Invoice"))) {
                    $relFocus = $relRecordModel->getEntity();
                    $adb->pquery("update `" . $relFocus->table_name . "` set `" . $relFocus->table_name . "`.`accountid`=? where `" . $relFocus->table_index . "`=?", array($parentRecordId, $relRecordId));
                } else {
                    if ($source_module == "Contacts" && in_array($related_module, array("PurchaseOrder", "Quotes", "SalesOrder", "Invoice"))) {
                        $relFocus = $relRecordModel->getEntity();
                        $adb->pquery("update `" . $relFocus->table_name . "` set `" . $relFocus->table_name . "`.`contactid`=? where `" . $relFocus->table_index . "`=?", array($parentRecordId, $relRecordId));
                    } else {
                        if ($source_module == "Campaigns" && $related_module == "Potentials") {
                            $relFocus = $relRecordModel->getEntity();
                            $adb->pquery("update `" . $relFocus->table_name . "` set `" . $relFocus->table_name . "`.`campaignid`=? where `" . $relFocus->table_index . "`=?", array($parentRecordId, $relRecordId));
                        } else {
                            if ($source_module == "Potentials" && in_array($related_module, array("Quotes", "SalesOrder"))) {
                                $relFocus = $relRecordModel->getEntity();
                                $adb->pquery("update `" . $relFocus->table_name . "` set `" . $relFocus->table_name . "`.`potentialid`=? where `" . $relFocus->table_index . "`=?", array($parentRecordId, $relRecordId));
                            } else {
                                if ($source_module == "Products" && in_array($related_module, array("Faq", "HelpDesk", "Campaigns"))) {
                                    $relFocus = $relRecordModel->getEntity();
                                    $adb->pquery("update `" . $relFocus->table_name . "` set `" . $relFocus->table_name . "`.`productid`=? where `" . $relFocus->table_index . "`=?", array($parentRecordId, $relRecordId));
                                } else {
                                    if ($source_module == "Quotes" && $related_module == "SalesOrder") {
                                        $relFocus = $relRecordModel->getEntity();
                                        $adb->pquery("update `" . $relFocus->table_name . "` set `" . $relFocus->table_name . "`.`quoteid`=? where `" . $relFocus->table_index . "`=?", array($parentRecordId, $relRecordId));
                                    } else {
                                        if ($source_module == "SalesOrder" && $related_module == "Invoice") {
                                            $relFocus = $relRecordModel->getEntity();
                                            $adb->pquery("update `" . $relFocus->table_name . "` set `" . $relFocus->table_name . "`.`salesorderid`=? where `" . $relFocus->table_index . "`=?", array($parentRecordId, $relRecordId));
                                        } else {
                                            if ($source_module == "Vendors" && $related_module == "Products") {
                                                $relFocus = $relRecordModel->getEntity();
                                                $adb->pquery("update `" . $relFocus->table_name . "` set `" . $relFocus->table_name . "`.`vendor_id`=? where `" . $relFocus->table_index . "`=?", array($parentRecordId, $relRecordId));
                                            } else {
                                                if ($source_module == "Vendors" && $related_module == "PurchaseOrder") {
                                                    $relFocus = $relRecordModel->getEntity();
                                                    $adb->pquery("update `" . $relFocus->table_name . "` set `" . $relFocus->table_name . "`.`vendorid`=? where `" . $relFocus->table_index . "`=?", array($parentRecordId, $relRecordId));
                                                } else {
                                                    if ($related_module == "Calendar" || $related_module == "Events") {
                                                        if ($source_module == "Contacts") {
                                                            $result = $adb->pquery("SELECT activityid FROM vtiger_cntactivityrel WHERE  contactid =? AND  activityid =?", array($parentRecordId, $relRecordId));
                                                            if ($adb->num_rows($result) == 0) {
                                                                $adb->pquery("INSERT INTO vtiger_cntactivityrel(`contactid`,`activityid`) VALUES(?,?)", array($parentRecordId, $relRecordId));
                                                            }
                                                        } else {
                                                            $result = $adb->pquery("SELECT activityid FROM vtiger_seactivityrel WHERE  crmid =? AND  activityid =?", array($parentRecordId, $relRecordId));
                                                            if ($adb->num_rows($result) == 0) {
                                                                $adb->pquery("INSERT INTO vtiger_seactivityrel(`crmid`,`activityid`) VALUES(?,?)", array($parentRecordId, $relRecordId));
                                                            }
                                                        }
                                                    } else {
                                                        $dependentFieldSql = $adb->pquery("SELECT tabid, fieldname, columnname FROM vtiger_field WHERE uitype='10' AND" . " fieldid IN (SELECT fieldid FROM vtiger_fieldmodulerel WHERE relmodule=? AND module=?)", array($source_module, $related_module));
                                                        $numOfFields = $adb->num_rows($dependentFieldSql);
                                                        if (0 < $numOfFields) {
                                                            $dependentColumn = $adb->query_result($dependentFieldSql, 0, "columnname");
                                                            $dependentField = $adb->query_result($dependentFieldSql, 0, "fieldname");
                                                            $relFocus = $relRecordModel->getEntity();
                                                            $adb->pquery("update `" . $relFocus->table_name . "` set `" . $relFocus->table_name . "`.`" . $dependentColumn . "`=? where `" . $relFocus->table_index . "`=?", array($parentRecordId, $relRecordId));
                                                        } else {
                                                            $relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relModuleModel);
                                                            $relationModel->addRelation($parentRecordId, $relRecordId);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $response->setResult(array("related_records" => $relatedIdList, "block_type" => $block_type));
        } catch (Exception $e) {
            $response->setError($e->getCode(), $e->getMessage());
        }
        $response->emit();
    }
    public function saveWidthField(Vtiger_Request $request)
    {
        global $adb;
        $fieldname = $request->get("field_name");
        $field_width = $request->get("field_width");
        $block_id = $request->get("block_id");
        if ($field_width) {
            $adb->pquery("UPDATE `relatedblockslists_fields` SET `width` = ? WHERE `fieldname` = ?  AND `blockid` = ? ", array($field_width, $fieldname, $block_id));
        }
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult(array("save" => true));
        $response->emit();
    }
}

?>