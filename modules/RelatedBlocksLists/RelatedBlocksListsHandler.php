<?php

require_once "include/events/VTEventHandler.inc";
class RelatedBlocksListsHandler extends VTEventHandler
{
    /**
     * Handle event
     *
     * @param $eventName
     * @param $data
     */
    public function handleEvent($eventName, $data)
    {
        error_reporting(0);
        global $adb;
        if ($eventName == "vtiger.entity.aftersave") {
            $moduleName = $data->getModuleName();
            $parentModuleModel = Vtiger_Module_Model::getInstance($moduleName);
            $parentRecordId = $data->getId();
            if (isset($_REQUEST["relatedblockslists"]) && $moduleName != "Emails") {
                $arrRelModuleModel = array();
                $arrRelModuleFields = array();
                $relatedblockslists = $_REQUEST["relatedblockslists"];
                unset($_REQUEST["relatedblockslists"]);
                $filestmp = $_FILES;
                foreach ($relatedblockslists as $blockid => $relatedRecords) {
                    $i = 0;
                    foreach ($relatedRecords as $relatedRecord) {
                        $i++;
                        $related_module = $relatedRecord["module"];
                        if ($arrRelModuleModel[$related_module]) {
                            $relModuleModel = $arrRelModuleModel[$related_module];
                            $fieldModelList = $arrRelModuleFields[$related_module];
                        } else {
                            $relModuleModel = Vtiger_Module_Model::getInstance($related_module);
                            $fieldModelList = $relModuleModel->getFields();
                            $arrRelModuleModel[$related_module] = $relModuleModel;
                            $arrRelModuleFields[$related_module] = $fieldModelList;
                        }
                        $relRecordId = $relatedRecord["recordId"];
                        if ($relRecordId) {
                            $relRecordModel = Vtiger_Record_Model::getInstanceById($relRecordId);
                            $modelData = $relRecordModel->getData();
                            $relRecordModel->set("id", $relRecordId);
                            $relRecordModel->set("mode", "edit");
                            $is_changed = false;
                            foreach ($fieldModelList as $fieldName => $fieldModel) {
                                if ($related_module == "Events") {
                                    $related_module = "Calendar";
                                }
                                $fieldName_ori = $fieldName;
                                $fieldName = (string) $related_module . "_" . $fieldName;
                                $fieldValue = $relatedRecord[$fieldName];
                                $fieldDataType = $fieldModel->getFieldDataType();
                                if ($fieldDataType == "time") {
                                    $fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
                                }
                                if ($fieldValue !== NULL) {
                                    if ($fieldDataType == "time") {
                                        $fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
                                    }
                                    $newValue = trim($fieldValue);
                                    $oldValue = html_entity_decode($relRecordModel->get($fieldName_ori), ENT_QUOTES);
                                    if ($fieldDataType == "time") {
                                        $oldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($oldValue);
                                        $oldValue = strtotime($oldValue);
                                        $newValue = strtotime($newValue);
                                    }
                                    if ($fieldDataType == "date") {
                                        $newValue = Vtiger_Date_UIType::getDBInsertedValue($newValue);
                                    }
                                    if ($fieldDataType == "boolean" && $newValue == "on") {
                                        $newValue = 1;
                                    }
                                    if ($fieldDataType == "currency") {
                                        $newValue = number_format($newValue);
                                        $oldValue = number_format($oldValue);
                                    }
                                    if ($fieldDataType == "multipicklist" && is_array($fieldValue) && $fieldValue[0] == "") {
                                        unset($fieldValue[0]);
                                        $newValue = implode(" |##| ", $fieldValue);
                                    }
                                    if ($fieldDataType == "reference") {
                                        if ($newValue == "" || $newValue == NULL) {
                                            $newValue = 0;
                                        }
                                        $newValue = number_format($newValue);
                                        $oldValue = number_format($oldValue);
                                    }
                                    if ($newValue != $oldValue) {
                                        $is_changed = true;
                                    }
                                }
                                if ($fieldValue !== NULL) {
                                    if (!is_array($fieldValue)) {
                                        $fieldValue = trim($fieldValue);
                                    }
                                    $fieldName = substr($fieldName, strlen((string) $related_module . "_"));
                                    $relRecordModel->set($fieldName, $fieldValue);
                                }
                            }
                            $_moduleModel = new RelatedBlocksLists_Module_Model();
                            $relRecordModel = $_moduleModel->setDataForCalendarRecord($relRecordModel, $relatedRecord);
                        } else {
                            $is_changed = true;
                            $relRecordModel = Vtiger_Record_Model::getCleanInstance($related_module);
                            $relRecordModel->set("mode", "");
                            foreach ($fieldModelList as $fieldName => $fieldModel) {
                                if ($related_module == "Events") {
                                    $related_module = "Calendar";
                                }
                                $fieldName = (string) $related_module . "_" . $fieldName;
                                $fieldValue = $relatedRecord[$fieldName];
                                $fieldDataType = $fieldModel->getFieldDataType();
                                if ($fieldDataType == "time") {
                                    $fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
                                }
                                if ($fieldDataType == "multipicklist" && is_array($fieldValue) && $fieldValue[0] == "") {
                                    unset($fieldValue[0]);
                                }
                                if ($fieldValue !== NULL) {
                                    if (!is_array($fieldValue)) {
                                        $fieldValue = trim($fieldValue);
                                    }
                                    $fieldName = substr($fieldName, strlen((string) $related_module . "_"));
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
                            $_moduleModel = new RelatedBlocksLists_Module_Model();
                            $relRecordModel = $_moduleModel->setDataForCalendarRecord($relRecordModel, $relatedRecord);
                            $_REQUEST["ajxaction"] = "DETAILVIEW";
                        }
                        $isParentRecordRel = true;
                        if ($moduleName == "Accounts" && in_array($related_module, array("Contacts", "Quotes", "SalesOrder", "Invoice"))) {
                            $relRecordModel->set("account_id", $data->getId());
                        } else {
                            if ($moduleName == "Contacts" && in_array($related_module, array("PurchaseOrder", "Quotes", "SalesOrder", "Invoice"))) {
                                $relRecordModel->set("contact_id", $data->getId());
                            } else {
                                if ($moduleName == "Campaigns" && $related_module == "Potentials") {
                                    $relRecordModel->set("campaignid", $data->getId());
                                } else {
                                    if ($moduleName == "Potentials" && in_array($related_module, array("Quotes", "SalesOrder"))) {
                                        $relRecordModel->set("potential_id", $data->getId());
                                    } else {
                                        if ($moduleName == "Products" && in_array($related_module, array("Faq", "HelpDesk", "Campaigns"))) {
                                            $relRecordModel->set("productid", $data->getId());
                                        } else {
                                            if ($moduleName == "Quotes" && $related_module == "SalesOrder") {
                                                $relRecordModel->set("quote_id", $data->getId());
                                            } else {
                                                if ($moduleName == "SalesOrder" && $related_module == "Invoice") {
                                                    $relRecordModel->set("salesorder_id", $data->getId());
                                                } else {
                                                    if ($moduleName == "Vendors" && in_array($related_module, array("Products", "PurchaseOrder"))) {
                                                        $relRecordModel->set("vendor_id", $data->getId());
                                                    } else {
                                                        if ($related_module == "Calendar" || $related_module == "Events") {
                                                            if ($moduleName == "Contacts") {
                                                                $_REQUEST["contactidlist"] = $data->getId();
                                                            } else {
                                                                $relRecordModel->set("parent_id", $data->getId());
                                                            }
                                                        } else {
                                                            $dependentFieldSql = $adb->pquery("SELECT tabid, fieldname, columnname FROM vtiger_field WHERE uitype='10' AND" . " fieldid IN (SELECT fieldid FROM vtiger_fieldmodulerel WHERE relmodule=? AND module=?)", array($moduleName, $related_module));
                                                            $numOfFields = $adb->num_rows($dependentFieldSql);
                                                            if (0 < $numOfFields) {
                                                                $dependentColumn = $adb->query_result($dependentFieldSql, 0, "columnname");
                                                                $dependentField = $adb->query_result($dependentFieldSql, 0, "fieldname");
                                                                $relRecordModel->set($dependentField, $data->getId());
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
                        if ($is_changed || $data->isNew() == true) {
                            if ($related_module == "Documents") {
                                $isParentRecordRel = false;
                                if ($relRecordModel->get("notes_title") == "") {
                                    $relRecordModel->set("notes_title", $filestmp["relatedblockslists"][$blockid]["name"][$i]["Documents_filename"]);
                                    $relRecordModel->set("filelocationtype", "I");
                                    $relRecordModel->set("filestatus", 1);
                                }
                                unset($_FILES);
                                $fileData = array("name" => $filestmp["relatedblockslists"][$blockid]["name"][$i]["Documents_filename"], "type" => $filestmp["relatedblockslists"][$blockid]["type"][$i]["Documents_filename"], "tmp_name" => $filestmp["relatedblockslists"][$blockid]["tmp_name"][$i]["Documents_filename"], "error" => $filestmp["relatedblockslists"][$blockid]["error"][$i]["Documents_filename"], "size" => $filestmp["relatedblockslists"][$blockid]["size"][$i]["Documents_filename"]);
                                $_FILES["filename"] = $fileData;
                            }
                            if ($related_module == "Documents") {
                                $isParentRecordRel = false;
                                $relRecordModel->set("filestatus", 1);
                                $relRecordModel->set("filelocationtype", "I");
                            }
                            $relRecordModel->save();
                            $relRecordId = $relRecordModel->getId();
                            if (!$isParentRecordRel) {
                                $relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relModuleModel);
                                if ($relationModel) {
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

?>