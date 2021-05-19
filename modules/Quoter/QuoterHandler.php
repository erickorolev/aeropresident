<?php

class QuoterHandler extends VTEventHandler
{
    public function handleEvent($eventName, $entityData)
    {
        if ($eventName == "vtiger.entity.aftersave") {
            $modulesHandle = array("Quotes", "Invoice", "SalesOrder", "PurchaseOrder", "PSTemplates");
            $currentModule = $entityData->getModuleName();
            $targetModule = false;
            if ($currentModule == "PSTemplates") {
                $targetModule = $_REQUEST["target_module"];
            }
            if (in_array($currentModule, $modulesHandle)) {
                global $adb;
                global $updateInventoryProductRel_deduct_stock;
                global $vtiger_current_version;
                $res = $adb->pquery("SELECT taxname, taxlabel FROM vtiger_inventorytaxinfo WHERE deleted = 0", array());
                $taxTypes = array();
                if (0 < $adb->num_rows($res)) {
                    $blocks = array("LBL_ITEM_DETAILS");
                    $fields = array();
                    for ($i = 0; $i < $adb->num_rows($res); $i++) {
                        $taxname = $adb->query_result($res, $i, "taxname");
                        $taxlabel = $adb->query_result($res, $i, "taxlabel");
                        $taxTypes[] = $taxname;
                        $fields["LBL_ITEM_DETAILS"][$taxname] = array("label" => $taxlabel, "uitype" => 9, "typeofdata" => "N~O");
                    }
                    $this->createCustomField($blocks, $fields, "VTEItems", "vtiger_vteitems");
                }
                $record = $entityData->getId();
                $totalProductCount = $_REQUEST["totalProductCount"];
                $crmids = array();
                if (isset($entityData->focus->_recurring_mode) && $entityData->focus->_recurring_mode == "recurringinvoice_from_so" && isset($entityData->focus->_salesorderid) && $entityData->focus->_salesorderid != "") {
                    $salesorder_id = $entityData->focus->_salesorderid;
                    $query1 = "SELECT * FROM `vtiger_vteitems` WHERE related_to=?";
                    $res = $adb->pquery($query1, array($salesorder_id));
                    if (0 < $adb->num_rows($res)) {
                        while ($row = $adb->fetchByAssoc($res)) {
                            $vteItemRecordModel = Vtiger_Record_Model::getInstanceById($row["vteitemid"]);
                            $vteItemRecordModel->getData();
                            $vteItemRecordModel->set("id", "");
                            $vteItemRecordModel->set("mode", "");
                            $vteItemRecordModel->set("related_to", $record);
                            foreach ($row as $k => $v) {
                                if (strpos($k, "cf_invoice_") !== false) {
                                    $k2 = str_replace("cf_invoice_", "cf_salesorder_", $k);
                                    if (array_key_exists($k2, $row)) {
                                        $vteItemRecordModel->set($k, $row[$k2]);
                                    }
                                }
                            }
                            $vteItemRecordModel->save();
                            $sql = "UPDATE vtiger_vteitems v1\r\n                                    INNER JOIN vtiger_vteitems v2 ON v2.vteitemid = ?\r\n                                    SET v1.sequence = v2.sequence\r\n                                    WHERE v1.vteitemid = ?";
                            $adb->pquery($sql, array($row["vteitemid"], $vteItemRecordModel->getId()));
                        }
                    }
                    $salesOrderId = $entityData->focus->_salesorderid;
                    $rs = $adb->pquery("SELECT * FROM `vtiger_salesorder` as a\r\n                                    INNER JOIN `vtiger_salesordercf` as b ON a.`salesorderid` = b.`salesorderid`\r\n                                    WHERE a.`salesorderid` = ?", array($salesOrderId));
                    if (0 < $adb->num_rows($rs)) {
                        while ($row = $adb->fetchByAssoc($rs)) {
                            $pre_tax_total = $row["pre_tax_total"];
                            $s_h_percent = $row["s_h_percent"];
                            $s_h_amount = $row["s_h_amount"];
                            $adjustment = $row["adjustment"];
                            $tax = $row["tax"];
                            $sql2 = "UPDATE `vtiger_invoice` SET `adjustment`= ?, `s_h_amount`= ?, `s_h_percent`= ?, `pre_tax_total` = ? WHERE `invoiceid`=?";
                            $params2 = array($adjustment, $s_h_amount, $s_h_percent, $pre_tax_total, $record);
                            $adb->pquery($sql2, $params2);
                            $adb->pquery("UPDATE `vtiger_invoicecf` SET `tax`=? WHERE `invoiceid`=?", array($tax, $record));
                            $entityData->set("hdnS_H_Percent", $s_h_percent);
                            $entityData->set("tax", $tax);
                        }
                    }
                    $this->handleEventForRecurringInvoice($eventName, $entityData);
                } else {
                    if (0 < $totalProductCount) {
                        $adb->pquery("UPDATE vtiger_vteitems SET  `running_item_value` = '', `section_value` = '' WHERE related_to = ?", array($record));
                        $quoterModel = new Quoter_Module_Model();
                        if ($targetModule) {
                            $setting = $quoterModel->getSettingForModule($targetModule, true);
                            $totalFieldSetting = $quoterModel->getTotalFieldsSetting($targetModule);
                        } else {
                            $setting = $quoterModel->getSettingForModule($currentModule, true);
                            $totalFieldSetting = $quoterModel->getTotalFieldsSetting($currentModule);
                        }
                        $adb->pquery("DELETE FROM vtiger_inventorysubproductrel WHERE id =?", array($record));
                        unset($setting["item_name"]);
                        $uitypeSeven = array("quantity", "listprice", "discount_amount", "purchase_cost", "margin", "total", "net_price", "tax_totalamount");
                        for ($i = 1; $i <= $totalProductCount; $i++) {
                            $productId = vtlib_purify($_REQUEST["hdnProductId" . $i]);
                            $vteItemId = $this->checkRecordExisted($record, $productId, $i);
                            if ($vteItemId) {
                                $recordModel = Vtiger_Record_Model::getInstanceById($vteItemId);
                                $recordModel->set("id", $vteItemId);
                                $recordModel->set("mode", "edit");
                            } else {
                                $recordModel = Vtiger_Record_Model::getCleanInstance("VTEItems");
                            }
                            foreach ($taxTypes as $taxType) {
                                $val = vtlib_purify($_REQUEST[$taxType . "_percentage" . $i]);
                                $recordModel->set($taxType, $val);
                            }
                            foreach ($setting as $value) {
                                $val = vtlib_purify($_REQUEST[$value->columnName . $i]);
                                if ($quoterModel->isCustomFields($value->columnName)) {
                                    if ($value->productField || $value->serviceField) {
                                        $productRecord = Vtiger_Record_Model::getInstanceById($productId);
                                        $productModule = $productRecord->getModule();
                                        $moduleName = $productRecord->getModuleName();
                                        if ($moduleName == "Products") {
                                            $productFieldModel = Vtiger_Field_Model::getInstance($value->productField, $productModule);
                                        } else {
                                            if ($moduleName == "Services") {
                                                $productFieldModel = Vtiger_Field_Model::getInstance($value->serviceField, $productModule);
                                            }
                                        }
                                        if ($productFieldModel) {
                                            $fieldDataType = $productFieldModel->getFieldDataType();
                                        }
                                        if ($fieldDataType == "time") {
                                            $val = Vtiger_Time_UIType::getTimeValueWithSeconds($_REQUEST[$value->columnName . $i]);
                                        } else {
                                            if ($fieldDataType == "date") {
                                                $val = Vtiger_Date_UIType::getDBInsertValue($_REQUEST[$value->columnName . $i]);
                                            }
                                        }
                                        if (is_array($_REQUEST[$value->columnName . $i])) {
                                            $values = $_REQUEST[$value->columnName . $i];
                                            $values = array_filter($values, function ($var) {
                                                return $var !== "";
                                            });
                                            $values = array_unique($values);
                                            $val = implode(" |##| ", $values);
                                        }
                                    } else {
                                        $val = $val;
                                    }
                                }
                                if (version_compare($vtiger_current_version, "7.0.0", ">=") && in_array($value->columnName, $uitypeSeven)) {
                                    $val = CurrencyField::convertToUserFormat($val, NULL, true);
                                }
                                $recordModel->set($value->columnName, $val);
                            }
                            $recordModel->set("productid", $productId);
                            $recordModel->set("related_to", $record);
                            if ($record) {
                                $recordModelQuotes = Vtiger_Record_Model::getInstanceById($record);
                                $assignedTo = $recordModelQuotes->get("assigned_user_id");
                                $recordModel->set("assigned_user_id", $assignedTo);
                            }
                            $recordModel->save();
                            $crmids[] = $recordModel->getId();
                            $adb->pquery("UPDATE vtiger_vteitems SET `sequence` = ? WHERE vteitemid = ?", array($i, $recordModel->getId()));
                            $level = $_REQUEST["level" . $i];
                            $adb->pquery("UPDATE vtiger_vteitems SET `level` = ? WHERE productid =? AND related_to = ? AND sequence = ?", array($level, $productId, $record, $i));
                            $parentId = vtlib_purify($_REQUEST["parentProductId" . $i]);
                            if (!empty($parentId)) {
                                $adb->pquery("INSERT INTO vtiger_inventorysubproductrel(id,sequence_no,productid) VALUES (?,?,?)", array($record, $i, $parentId));
                            }
                            if (isset($_REQUEST["section" . $i]) && !empty($_REQUEST["section" . $i])) {
                                $section = vtlib_purify($_REQUEST["section" . $i]);
                                $adb->pquery("UPDATE vtiger_vteitems SET `section_value` = ? WHERE productid = ? AND related_to = ? AND sequence = ?", array($section, $productId, $record, $i));
                            }
                            if (isset($_REQUEST["running_item_name" . $i]) && !empty($_REQUEST["running_item_name" . $i]) && is_array($_REQUEST["running_item_name" . $i])) {
                                $running_item_name = vtlib_purify($_REQUEST["running_item_name" . $i]);
                                $running_item_value = vtlib_purify($_REQUEST["running_item_value" . $i]);
                                $arrValue = array();
                                $count = count($running_item_name);
                                for ($j = 0; $j < $count; $j++) {
                                    $arrValue[$running_item_name[$j]] = $running_item_value[$j];
                                }
                                $strValue = serialize($arrValue);
                                $adb->pquery("UPDATE vtiger_vteitems SET  `running_item_value` = ? WHERE productid = ? AND related_to = ? AND sequence = ?", array($strValue, $productId, $record, $i));
                            }
                        }
                        $arrTotalValue = array();
                        $arrTotalFieldName = array();
                        foreach ($totalFieldSetting as $totalFieldName => $totalSetting) {
                            if (!empty($_REQUEST[$totalFieldName]) && is_numeric($_REQUEST[$totalFieldName])) {
                                $arrTotalValue[$totalFieldName] = $_REQUEST[$totalFieldName];
                            } else {
                                $arrTotalValue[$totalFieldName] = 0;
                            }
                            $totalFieldName = $adb->sql_escape_string($totalFieldName);
                            $arrTotalFieldName[] = " " . $totalFieldName . " = ? ";
                        }
                        $strTotalFieldName = implode(",", $arrTotalFieldName);
                        $tableName = "vtiger_" . strtolower($currentModule);
                        $tablecfName = "vtiger_" . strtolower($currentModule) . "cf";
                        $tableIndex = $entityData->focus->table_index;
                        $totalQuery = "UPDATE " . $tableName . " \r\n                                INNER JOIN " . $tablecfName . " ON " . $tablecfName . "." . $tableIndex . " = " . $tableName . "." . $tableIndex . "\r\n                                SET " . $strTotalFieldName . " WHERE " . $tableName . "." . $tableIndex . " = " . $record;
                        $adb->pquery($totalQuery, array_values($arrTotalValue));
                        if (!empty($crmids)) {
                            $adb->pquery("UPDATE vtiger_crmentity INNER JOIN vtiger_vteitems ON  vtiger_vteitems.vteitemid = vtiger_crmentity.crmid\r\n                        SET deleted = 1 WHERE vtiger_vteitems.related_to = ? AND crmid NOT IN (" . generateQuestionMarks($crmids) . ")", array($record, $crmids));
                        }
                        $sql = "UPDATE vtiger_inventoryproductrel\r\n\t\t\t\t\t\tINNER JOIN vtiger_vteitems ON (\r\n\t\t\t\t\t\t\t\tvtiger_vteitems.related_to = vtiger_inventoryproductrel.id\r\n\t\t\t\t\t\t\t\tAND vtiger_inventoryproductrel.productid = vtiger_vteitems.productid\r\n\t\t\t\t\t\t\t\tAND vtiger_inventoryproductrel.sequence_no = vtiger_vteitems.sequence\r\n\t\t\t\t\t\t)\r\n\t\t\t\t\t\tINNER JOIN vtiger_crmentity ON (\r\n\t\t\t\t\t\t\tvtiger_crmentity.crmid = vtiger_vteitems.vteitemid\r\n\t\t\t\t\t\t\tAND vtiger_crmentity.deleted = 0\r\n\t\t\t\t\t\t)\r\n\t\t\t\t\t\tSET vtiger_inventoryproductrel.quantity = vtiger_vteitems.quantity\r\n\t\t\t\t\t\t, vtiger_inventoryproductrel.listprice = vtiger_vteitems.listprice\r\n\t\t\t\t\t\tWHERE (vtiger_inventoryproductrel.quantity <> vtiger_vteitems.quantity\r\n\t\t\t\t\t\t\t\tOR vtiger_inventoryproductrel.listprice <> vtiger_vteitems.listprice)\r\n\t\t\t\t\t\tAND vtiger_inventoryproductrel.id = ?";
                        $adb->pquery($sql, array($record));
                        if ($currentModule == "PurchaseOrder") {
                            $requestProductIdsList = $requestQuantitiesList = array();
                            $totalNoOfProducts = $_REQUEST["totalProductCount"];
                            for ($i = 1; $i <= $totalNoOfProducts; $i++) {
                                $productId = $_REQUEST["hdnProductId" . $i];
                                $requestProductIdsList[$productId] = $productId;
                                if (array_key_exists($productId, $requestQuantitiesList)) {
                                    $requestQuantitiesList[$productId] = $requestQuantitiesList[$productId] + $_REQUEST["quantity" . $i];
                                    continue;
                                }
                                $requestQuantitiesList[$productId] = $_REQUEST["quantity" . $i];
                            }
                            if ($isNew && $data["postatus"] === "Received Shipment") {
                                foreach ($requestProductIdsList as $productId) {
                                    addToProductStock($productId, $requestQuantitiesList[$productId]);
                                }
                            } else {
                                if ($data["postatus"] === "Received Shipment" && !$isNew) {
                                    $result = $adb->pquery("SELECT productid, quantity FROM vtiger_inventoryproductrel WHERE id = ?", array($record));
                                    $numOfRows = $adb->num_rows($result);
                                    for ($i = 0; $i < $numOfRows; $i++) {
                                        $productId = $adb->query_result($result, $i, "productid");
                                        $productIdsList[$productId] = $productId;
                                        $quantitiesList[$productId] = $adb->query_result($result, $i, "quantity");
                                    }
                                    $newProductIds = array_diff($requestProductIdsList, $productIdsList);
                                    if ($newProductIds) {
                                        foreach ($newProductIds as $productId) {
                                            addToProductStock($productId, $requestQuantitiesList[$productId]);
                                        }
                                    }
                                    $deletedProductIds = array_diff($productIdsList, $requestProductIdsList);
                                    if ($deletedProductIds) {
                                        foreach ($deletedProductIds as $productId) {
                                            $productStock = getPrdQtyInStck($productId);
                                            $quantity = $productStock - $quantitiesList[$productId];
                                            updateProductQty($productId, $quantity);
                                        }
                                    }
                                    $updatedProductIds = array_intersect($productIdsList, $requestProductIdsList);
                                    if ($updatedProductIds) {
                                        foreach ($updatedProductIds as $productId) {
                                            $quantityDiff = $quantitiesList[$productId] - $requestQuantitiesList[$productId];
                                            if ($quantityDiff < 0) {
                                                $quantityDiff = 0 - $quantityDiff;
                                                addToProductStock($productId, $quantityDiff);
                                            } else {
                                                if (0 < $quantityDiff) {
                                                    $productStock = getPrdQtyInStck($productId);
                                                    $quantity = $productStock - $quantityDiff;
                                                    updateProductQty($productId, $quantity);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            if ($updateInventoryProductRel_deduct_stock && in_array($currentModule, $modulesHandle)) {
                                $adb->pquery("UPDATE vtiger_inventoryproductrel SET incrementondel=1 WHERE id=?", array($record));
                                $product_info = $adb->pquery("SELECT productid,sequence_no, quantity from vtiger_inventoryproductrel WHERE id=?", array($record));
                                $numrows = $adb->num_rows($product_info);
                                for ($index = 0; $index < $numrows; $index++) {
                                    $productid = $adb->query_result($product_info, $index, "productid");
                                    $qty = $adb->query_result($product_info, $index, "quantity");
                                    $sequence_no = $adb->query_result($product_info, $index, "sequence_no");
                                    $qtyinstk = getProductQtyInStock($productid);
                                    $upd_qty = $qtyinstk - $qty;
                                    updateProductQty($productid, $upd_qty);
                                    $sub_prod_query = $adb->pquery("SELECT productid from vtiger_inventorysubproductrel WHERE id=? AND sequence_no=?", array($record, $sequence_no));
                                    if (0 < $adb->num_rows($sub_prod_query)) {
                                        for ($j = 0; $j < $adb->num_rows($sub_prod_query); $j++) {
                                            $sub_prod_id = $adb->query_result($sub_prod_query, $j, "productid");
                                            $sqtyinstk = getProductQtyInStock($sub_prod_id);
                                            $supd_qty = $sqtyinstk - $qty;
                                            updateProductQty($sub_prod_id, $supd_qty);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                if ($entityData->getModuleName() == "Products" && $_REQUEST["relationOperation"] == true) {
                    $sourceRecord = $_REQUEST["sourceRecord"];
                    if (!empty($sourceRecord)) {
                        global $adb;
                        $rs = $adb->pquery("SELECT * FROM vtiger_products WHERE productid =?", array($sourceRecord));
                        if ($adb->num_rows($rs)) {
                            $unitPrice = $adb->query_result($rs, 0, "unit_price");
                            if ($unitPrice == 0) {
                                $quoterModel = new Quoter_Module_Model();
                                $quoterModel->updateParentProduct($sourceRecord);
                            }
                        }
                    }
                }
            }
        }
    }
    public function handleEventForRecurringInvoice($eventName, $entityData)
    {
        global $adb;
        $currentModule = $entityData->getModuleName();
        if ($currentModule == "PSTemplates") {
            return NULL;
        }
        $record = $entityData->getId();
        $quoterModule = new Quoter_Module_Model();
        $allSettings = $quoterModule->getSettings();
        $setting = $allSettings[$currentModule];
        $totalFieldsSetting = $quoterModule->getTotalFieldsSetting($currentModule);
        $sql = "SELECT *\r\n                FROM vtiger_vteitems v\r\n                INNER JOIN vtiger_crmentity c ON v.vteitemid = c.crmid\r\n                WHERE c.deleted=0 AND v.related_to=?";
        $sql = "SELECT ITEM.*,SUB_ITEM.productid as parent_id, vi.vteitemid\r\n                FROM vtiger_inventoryproductrel AS ITEM\r\n                INNER JOIN vtiger_crmentity AS C ON C.crmid = ITEM.id\r\n                LEFT JOIN vtiger_inventorysubproductrel AS SUB_ITEM ON ITEM.id = SUB_ITEM.id AND ITEM.sequence_no = SUB_ITEM.sequence_no\r\n                LEFT JOIN vtiger_vteitems vi ON vi.related_to=ITEM.id AND vi.productid=ITEM.productid AND vi.sequence=ITEM.sequence_no\r\n                WHERE C.deleted=0 AND ITEM.id=?";
        $rs = $adb->pquery($sql, array($record));
        $currentUser = Users_Record_Model::getInstanceById(1, "Users");
        $currentUser->id = 1;
        vglobal("current_user", $currentUser);
        if (0 < $adb->num_rows($rs)) {
            while ($row = $adb->fetchByAssoc($rs)) {
                if (!$setting) {
                    continue;
                }
                $vteitemid = $row["vteitemid"];
                if (empty($vteitemid)) {
                    continue;
                }
                $recordModel = Vtiger_Record_Model::getInstanceById($vteitemid);
                $notCalculated = array();
                $calculated = array();
                foreach ($setting as $columnSetting) {
                    $columnName = $columnSetting->columnName;
                    if ($columnSetting->formula == "") {
                        $calculated[] = $columnName;
                    } else {
                        $notCalculated[] = $columnSetting;
                        if (($key = array_search($columnName, $calculated)) !== false) {
                            unset($calculated[$key]);
                        }
                    }
                }
                $t1 = count($notCalculated);
                $t2 = 0;
                while (true) {
                    foreach ($notCalculated as $columnSetting) {
                        $columnName = $columnSetting->columnName;
                        if (!in_array($columnName, $calculated)) {
                            $formula = $columnSetting->formula;
                            $tax_total = 0;
                            foreach ($row as $k => $v) {
                                if (strpos($k, "tax") !== false && $k != "tax_total") {
                                    $v = floatval($v);
                                    $recordModel->set($k, $v);
                                    $tax_total += $v;
                                }
                            }
                            $formula = str_replace("\$tax_total\$", $tax_total, $formula);
                            foreach ($calculated as $columnName2) {
                                if (!empty($columnName2)) {
                                    $expression = "\$" . $columnName2 . "\$";
                                    if (strpos($formula, $expression) !== false) {
                                        $val = $recordModel->get($columnName2);
                                        $val = floatval($val);
                                        $formula = str_replace($expression, $val, $formula);
                                        if (strpos($formula, "\$") === false) {
                                            break;
                                        }
                                    }
                                }
                            }
                            if (strpos($formula, "\$") === false) {
                                $val = eval("return " . $formula . " ;");
                                $val = floatval($val);
                                $val = round($val, 2);
                                $recordModel->set($columnName, $val);
                                $calculated[] = $columnName;
                                $t2 += 1;
                            }
                        }
                    }
                    if ($t1 == $t2) {
                        break;
                    }
                }
                $recordModel->set("mode", "edit");
                $recordModel->save();
                if ($row["section_value"]) {
                    $adb->pquery("UPDATE vtiger_vteitems SET `section_value` = ? WHERE vteitemid = ?", array($row["section_value"], $vteitemid));
                }
                if ($row["running_item_value"]) {
                    $adb->pquery("UPDATE vtiger_vteitems SET `running_item_value` = ? WHERE vteitemid = ?", array($row["running_item_value"], $vteitemid));
                }
            }
            if (!empty($totalFieldsSetting)) {
                $entityModule = CRMEntity::getInstance($currentModule);
                $vmodule = Vtiger_Module::getInstance($currentModule);
                $tabId = $vmodule->getId();
                $tab_name_index = $entityModule->tab_name_index;
                $sql1 = "SELECT * from vtiger_vteitems WHERE related_to=?";
                $rs1 = $adb->pquery($sql1, array($record));
                $vteitems = array();
                if (0 < $adb->num_rows($rs1)) {
                    while ($row1 = $adb->fetchByAssoc($rs1)) {
                        $vteitems[] = $row1;
                    }
                }
                $columnNames = array();
                foreach ($totalFieldsSetting as $fieldName => $fieldSetting) {
                    $columnNames[] = $fieldName;
                }
                $columnNames = implode("','", $columnNames);
                $fieldNames = array();
                $sql2 = "SELECT columnname, fieldname, tablename FROM vtiger_field \r\n                         WHERE tablename IN ('" . $entityModule->table_name . "', '" . $entityModule->customFieldTable[0] . "')\r\n                         AND tabid = ? AND columnname IN ('" . $columnNames . "')";
                $rs2 = $adb->pquery($sql2, array($tabId));
                if (0 < $adb->num_rows($rs2)) {
                    while ($row2 = $adb->fetchByAssoc($rs2)) {
                        $fieldname = $row2["fieldname"];
                        $tablename = $row2["tablename"];
                        $columnname = $row2["columnname"];
                        $fieldNames[$columnname] = array("fieldname" => $fieldname, "tablename" => $tablename);
                    }
                }
                $sqls = array();
                $totalFieldsSettingRemain = array();
                foreach ($totalFieldsSetting as $fieldName => $fieldSetting) {
                    $v = 0;
                    $formula = $fieldSetting["fieldFormula"];
                    if (empty($formula)) {
                        $fieldname2 = $fieldNames[$fieldName]["fieldname"];
                        $new_value = $entityData->get($fieldname2);
                        $totalFieldsSetting[$fieldName]["new_value"] = $new_value;
                        $totalFieldsSetting[$fieldName]["is_calculated"] = true;
                        continue;
                    }
                    if (strpos($formula, "SUM") !== false) {
                        $formula = getFormulaFromRegex($formula);
                        foreach ($vteitems as $vteitem) {
                            $formula1 = $formula;
                            foreach ($vteitem as $k1 => $v1) {
                                $expression = "\$" . $k1 . "\$";
                                if (strpos($formula1, $expression) !== false) {
                                    $v1 = floatval($v1);
                                    $formula1 = str_replace($expression, $v1, $formula1);
                                    if (strpos($formula1, "\$") === false) {
                                        $v2 = eval("return " . $formula1 . " ;");
                                        $v2 = floatval($v2);
                                        $v2 = round($v2, 2);
                                        $v += $v2;
                                        break;
                                    }
                                }
                            }
                        }
                        $sql3 = "UPDATE " . $fieldNames[$fieldName]["tablename"] . "\r\n                                SET " . $fieldName . " = ?\r\n                                WHERE " . $tab_name_index[$fieldNames[$fieldName]["tablename"]] . " = ?";
                        $param3 = array($v, $record);
                        $sqls[] = array("sql" => $sql3, "param" => $param3);
                        $totalFieldsSetting[$fieldName]["new_value"] = $v;
                        $totalFieldsSetting[$fieldName]["is_calculated"] = true;
                    } else {
                        $totalFieldsSettingRemain[$fieldName] = $fieldSetting;
                    }
                }
                while (true) {
                    foreach ($totalFieldsSettingRemain as $fieldName => $fieldSetting) {
                        $v = 0;
                        $formula = $fieldSetting["fieldFormula"];
                        if (!empty($formula)) {
                            foreach ($totalFieldsSetting as $fieldName2 => $fieldSetting2) {
                                $expression = "\$" . $fieldName2 . "\$";
                                if (strpos($formula, $expression) !== false) {
                                    if (empty($totalFieldsSetting[$fieldName2]["is_calculated"])) {
                                        break;
                                    }
                                    $v1 = $totalFieldsSetting[$fieldName2]["new_value"];
                                    $v1 = floatval($v1);
                                    $formula = str_replace($expression, $v1, $formula);
                                    if (strpos($formula, "\$") === false) {
                                        $v2 = eval("return " . $formula . " ;");
                                        $v2 = floatval($v2);
                                        $v2 = round($v2, 2);
                                        $sql3 = "UPDATE " . $fieldNames[$fieldName]["tablename"] . "\r\n                                                SET " . $fieldName . " = ?\r\n                                                WHERE " . $tab_name_index[$fieldNames[$fieldName]["tablename"]] . " = ?";
                                        $param3 = array($v2, $record);
                                        $sqls[] = array("sql" => $sql3, "param" => $param3);
                                        $totalFieldsSetting[$fieldName]["new_value"] = $v2;
                                        $totalFieldsSetting[$fieldName]["is_calculated"] = true;
                                        unset($totalFieldsSettingRemain[$fieldName]);
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    if (empty($totalFieldsSettingRemain)) {
                        break;
                    }
                }
                foreach ($sqls as $sql) {
                    $adb->pquery($sql["sql"], $sql["param"]);
                }
            }
        }
    }
    public function createCustomField($blocks, $fields, $module, $table)
    {
        $vmodule = Vtiger_Module::getInstance($module);
        if ($vmodule) {
            foreach ($blocks as $blcks) {
                $block = Vtiger_Block::getInstance($blcks, $vmodule);
                if (!$block && $blcks) {
                    $block = new Vtiger_Block();
                    $block->label = $blcks;
                    $block->__create($vmodule);
                }
                $adb = PearDatabase::getInstance();
                $sql_1 = "SELECT sequence FROM `vtiger_field` WHERE block = '" . $block->id . "' ORDER BY sequence DESC LIMIT 0,1";
                $res_1 = $adb->query($sql_1);
                $sequence = 0;
                if ($adb->num_rows($res_1)) {
                    $sequence = $adb->query_result($res_1, "sequence", 0);
                }
                foreach ($fields[$blcks] as $name => $a_field) {
                    $field = Vtiger_Field::getInstance($name, $vmodule);
                    if (!$field && $name && $table) {
                        $sequence++;
                        $field = new Vtiger_Field();
                        $field->name = $name;
                        $field->label = $a_field["label"];
                        $field->table = $table;
                        $field->uitype = $a_field["uitype"];
                        if ($a_field["uitype"] == 15 || $a_field["uitype"] == 16 || $a_field["uitype"] == "33") {
                            $field->setPicklistValues($a_field["picklistvalues"]);
                        }
                        $field->sequence = $sequence;
                        $field->__create($block);
                        if ($a_field["uitype"] == 10) {
                            $field->setRelatedModules(array($a_field["related_to_module"]));
                        }
                    }
                }
            }
        }
    }
    public function checkRecordExisted($crmid, $productid, $seq)
    {
        global $adb;
        $sql = "SELECT vteitemid FROM vtiger_vteitems\r\n              INNER JOIN  vtiger_crmentity ON vtiger_vteitems.vteitemid = vtiger_crmentity.crmid\r\n              WHERE related_to = ? AND productid = ? AND sequence = ? AND  deleted = 0";
        $rs = $adb->pquery($sql, array($crmid, $productid, $seq));
        if (0 < $adb->num_rows($rs)) {
            return $adb->query_result($rs, 0, "vteitemid");
        }
        return false;
    }
}
function getFormulaFromRegex($reg)
{
    preg_match("/SUM\\((\\w|\\\$|\\+|\\-|\\*|\\/|\\(|\\))+\\)/", $reg, $matches, PREG_OFFSET_CAPTURE);
    if (!empty($matches)) {
        $matches = $matches[0][0];
        preg_match("/SUM\\(([^}]+)\\)/", $matches, $matches2, PREG_OFFSET_CAPTURE);
        if (!empty($matches2)) {
            $matches2 = $matches2[1][0];
        }
        return $matches2;
    }
    return "";
}

?>