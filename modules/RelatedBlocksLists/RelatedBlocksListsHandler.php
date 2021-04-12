<?php

require_once "include/events/VTEventHandler.inc";
class RelatedBlocksListsHandler extends VTEventHandler
{
	public function handleEvent($eventName, $data)
	{
		error_reporting(0);
		global $adb;
		if ($eventName == "vtiger.entity.aftersave") {
			$moduleName = $data->getModuleName();
			$parentModuleModel = Vtiger_Module_Model::getInstance($moduleName);
			$parentRecordId = $data->getId();
			if (isset($_REQUEST["relatedblockslists"]) && $moduleName != "Emails") {
				$arrRelModuleModel = [];
				$arrRelModuleFields = [];
				$relatedblockslists = $_REQUEST["relatedblockslists"];
				unset($_REQUEST["relatedblockslists"]);
				foreach ($relatedblockslists as $blockid => $relatedRecords) {
					foreach ($relatedRecords as $relatedRecord) {
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
								$fieldName_ori = $fieldName;
								$fieldName = $related_module . "_" . $fieldName;
								$fieldValue = $relatedRecord[$fieldName];
								$fieldDataType = $fieldModel->getFieldDataType();
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
									}
									if ($newValue != $oldValue) {
										$is_changed = true;
									}
								}
								if ($fieldValue !== NULL) {
									if (!is_array($fieldValue)) {
										$fieldValue = trim($fieldValue);
									}
									$fieldName = substr($fieldName, strlen($related_module . "_"));
									$relRecordModel->set($fieldName, $fieldValue);
								}
							}
							$_moduleModel = new RelatedBlocksLists_Module_Model();
							$relRecordModel = $_moduleModel->setDataForCalendarRecord($relRecordModel, $relatedRecord);
							if ($is_changed) {
								$relRecordModel->save();
							}
						} else {
							$relRecordModel = Vtiger_Record_Model::getCleanInstance($related_module);
							$relRecordModel->set("mode", "");
							foreach ($fieldModelList as $fieldName => $fieldModel) {
								$fieldName = $related_module . "_" . $fieldName;
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
									$fieldName = substr($fieldName, strlen($related_module . "_"));
									$relRecordModel->set($fieldName, $fieldValue);
								}
								if (empty($fieldValue)) {
									$sql = "SELECT filterfield,filtervalue FROM `relatedblockslists_blocks` WHERE blockid='" . $blockid . "' AND relmodule='" . $related_module . "'";
									$results = $adb->pquery($sql, []);
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
							$relRecordModel->save();
							$relRecordId = $relRecordModel->getId();
						}
						if ($moduleName == "Accounts" && in_array($related_module, ["Contacts", "Quotes", "SalesOrder", "Invoice"])) {
							$relFocus = $relRecordModel->getEntity();
							$adb->pquery("update `" . $relFocus->table_name . "` set `" . $relFocus->table_name . "`.`accountid`=? where `" . $relFocus->table_index . "`=?", [$data->getId(), $relRecordId]);
						} else {
							if ($moduleName == "Contacts" && in_array($related_module, ["PurchaseOrder", "Quotes", "SalesOrder", "Invoice"])) {
								$relFocus = $relRecordModel->getEntity();
								$adb->pquery("update `" . $relFocus->table_name . "` set `" . $relFocus->table_name . "`.`contactid`=? where `" . $relFocus->table_index . "`=?", [$data->getId(), $relRecordId]);
							} else {
								if ($moduleName == "Campaigns" && $related_module == "Potentials") {
									$relFocus = $relRecordModel->getEntity();
									$adb->pquery("update `" . $relFocus->table_name . "` set `" . $relFocus->table_name . "`.`campaignid`=? where `" . $relFocus->table_index . "`=?", [$data->getId(), $relRecordId]);
								} else {
									if ($moduleName == "Potentials" && in_array($related_module, ["Quotes", "SalesOrder"])) {
										$relFocus = $relRecordModel->getEntity();
										$adb->pquery("update `" . $relFocus->table_name . "` set `" . $relFocus->table_name . "`.`potentialid`=? where `" . $relFocus->table_index . "`=?", [$data->getId(), $relRecordId]);
									} else {
										if ($moduleName == "Products" && in_array($related_module, ["Faq", "HelpDesk", "Campaigns"])) {
											$relFocus = $relRecordModel->getEntity();
											$adb->pquery("update `" . $relFocus->table_name . "` set `" . $relFocus->table_name . "`.`productid`=? where `" . $relFocus->table_index . "`=?", [$data->getId(), $relRecordId]);
										} else {
											if ($moduleName == "Quotes" && $related_module == "SalesOrder") {
												$relFocus = $relRecordModel->getEntity();
												$adb->pquery("update `" . $relFocus->table_name . "` set `" . $relFocus->table_name . "`.`quoteid`=? where `" . $relFocus->table_index . "`=?", [$data->getId(), $relRecordId]);
											} else {
												if ($moduleName == "SalesOrder" && $related_module == "Invoice") {
													$relFocus = $relRecordModel->getEntity();
													$adb->pquery("update `" . $relFocus->table_name . "` set `" . $relFocus->table_name . "`.`salesorderid`=? where `" . $relFocus->table_index . "`=?", [$data->getId(), $relRecordId]);
												} else {
													if ($moduleName == "Vendors" && $related_module == "Products") {
														$relFocus = $relRecordModel->getEntity();
														$adb->pquery("update `" . $relFocus->table_name . "` set `" . $relFocus->table_name . "`.`vendor_id`=? where `" . $relFocus->table_index . "`=?", [$data->getId(), $relRecordId]);
													} else {
														if ($moduleName == "Vendors" && $related_module == "PurchaseOrder") {
															$relFocus = $relRecordModel->getEntity();
															$adb->pquery("update `" . $relFocus->table_name . "` set `" . $relFocus->table_name . "`.`vendorid`=? where `" . $relFocus->table_index . "`=?", [$data->getId(), $relRecordId]);
														} else {
															if ($related_module == "Calendar" || $related_module == "Events") {
																if ($moduleName == "Contacts") {
																	$result = $adb->pquery("SELECT activityid FROM vtiger_cntactivityrel WHERE  contactid =? AND  activityid =?", [$data->getId(), $relRecordId]);
																	if ($adb->num_rows($result) == 0) {
																		$adb->pquery("INSERT INTO vtiger_cntactivityrel(`contactid`,`activityid`) VALUES(?,?)", [$data->getId(), $relRecordId]);
																	}
																} else {
																	$result = $adb->pquery("SELECT activityid FROM vtiger_seactivityrel WHERE  crmid =? AND  activityid =?", [$data->getId(), $relRecordId]);
																	if ($adb->num_rows($result) == 0) {
																		$adb->pquery("INSERT INTO vtiger_seactivityrel(`crmid`,`activityid`) VALUES(?,?)", [$data->getId(), $relRecordId]);
																	}
																}
															} else {
																$dependentFieldSql = $adb->pquery("SELECT tabid, fieldname, columnname FROM vtiger_field WHERE uitype='10' AND fieldid IN (SELECT fieldid FROM vtiger_fieldmodulerel WHERE relmodule=? AND module=?)", [$moduleName, $related_module]);
																$numOfFields = $adb->num_rows($dependentFieldSql);
																if (0 < $numOfFields) {
																	$dependentColumn = $adb->query_result($dependentFieldSql, 0, "columnname");
																	$dependentField = $adb->query_result($dependentFieldSql, 0, "fieldname");
																	$relFocus = $relRecordModel->getEntity();
																	$adb->pquery("update `" . $relFocus->table_name . "` set `" . $relFocus->table_name . "`.`" . $dependentColumn . "`=? where `" . $relFocus->table_index . "`=?", [$data->getId(), $relRecordId]);
																} else {
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