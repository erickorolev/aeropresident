<?php

class Quoter_Record_Model extends Inventory_Record_Model
{
    public function getCurrencyInfo()
    {
        $moduleName = $this->getModuleName();
        $currencyInfo = $this->getInventoryCurrencyInfo($moduleName, $this->getId());
        return $currencyInfo;
    }
    public function getInventoryCurrencyInfo($module, $id)
    {
        global $log;
        global $adb;
        $log->debug("Entering into function getInventoryCurrencyInfo(" . $module . ", " . $id . ").");
        $inv_table_array = array("PurchaseOrder" => "vtiger_purchaseorder", "SalesOrder" => "vtiger_salesorder", "Quotes" => "vtiger_quotes", "Invoice" => "vtiger_invoice", "PSTemplates" => "vtiger_pstemplates");
        $inv_id_array = array("PurchaseOrder" => "purchaseorderid", "SalesOrder" => "salesorderid", "Quotes" => "quoteid", "Invoice" => "invoiceid", "PSTemplates" => "pstemplatesid");
        $inventory_table = $inv_table_array[$module];
        $inventory_id = $inv_id_array[$module];
        $res = $adb->pquery("select currency_id, " . $inventory_table . ".conversion_rate as conv_rate, vtiger_currency_info.* from " . $inventory_table . "\n\t\t\t\t\t\tinner join vtiger_currency_info on " . $inventory_table . ".currency_id = vtiger_currency_info.id\n\t\t\t\t\t\twhere " . $inventory_id . "=?", array($id));
        $currency_info = array();
        $currency_info["currency_id"] = $adb->query_result($res, 0, "currency_id");
        $currency_info["conversion_rate"] = $adb->query_result($res, 0, "conv_rate");
        $currency_info["currency_name"] = $adb->query_result($res, 0, "currency_name");
        $currency_info["currency_code"] = $adb->query_result($res, 0, "currency_code");
        $currency_info["currency_symbol"] = $adb->query_result($res, 0, "currency_symbol");
        $log->debug("Exit from function getInventoryCurrencyInfo(" . $module . ", " . $id . ").");
        return $currency_info;
    }
    public function getProductTaxes()
    {
        $taxDetails = $this->get("taxDetails");
        if ($taxDetails) {
            return $taxDetails;
        }
        $record = $this->getId();
        if ($record) {
            $relatedProducts = getAssociatedProducts($this->getModuleName(), $this->getEntity());
            $taxDetails = $relatedProducts[1]["final_details"]["taxes"];
        } else {
            $taxDetails = getAllTaxes("available", "", $this->getEntity()->mode, $this->getId());
        }
        $this->set("taxDetails", $taxDetails);
        return $taxDetails;
    }
    public function getShippingTaxes()
    {
        $shippingTaxDetails = $this->get("shippingTaxDetails");
        if ($shippingTaxDetails) {
            return $shippingTaxDetails;
        }
        $record = $this->getId();
        if ($record) {
            $relatedProducts = getAssociatedProducts($this->getModuleName(), $this->getEntity());
            $shippingTaxDetails = $relatedProducts[1]["final_details"]["sh_taxes"];
        } else {
            $shippingTaxDetails = getAllTaxes("available", "sh", "edit", $this->getId());
        }
        $this->set("shippingTaxDetails", $shippingTaxDetails);
        return $shippingTaxDetails;
    }
    /**
     * Function to set data of parent record model to this record
     * @param Vtiger_Record_Model $parentRecordModel
     * @return Inventory_Record_Model
     */
    public function setParentRecordData(Vtiger_Record_Model $parentRecordModel)
    {
        $userModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        $moduleName = $parentRecordModel->getModuleName();
        $data = array();
        $fieldMappingList = $parentRecordModel->getInventoryMappingFields();
        foreach ($fieldMappingList as $fieldMapping) {
            $parentField = $fieldMapping["parentField"];
            $inventoryField = $fieldMapping["inventoryField"];
            $fieldModel = Vtiger_Field_Model::getInstance($parentField, Vtiger_Module_Model::getInstance($moduleName));
            if ($fieldModel->getPermissions()) {
                $data[$inventoryField] = $parentRecordModel->get($parentField);
            } else {
                $data[$inventoryField] = $fieldMapping["defaultValue"];
            }
        }
        return $this->setData($data);
    }
    /**
     * Function to get URL for Export the record as PDF
     * @return <type>
     */
    public function getExportPDFUrl()
    {
        return "index.php?module=" . $this->getModuleName() . "&action=ExportPDF&record=" . $this->getId();
    }
    /**
     * Function to get the send email pdf url
     * @return <string>
     */
    public function getSendEmailPDFUrl()
    {
        return "module=" . $this->getModuleName() . "&view=SendEmail&mode=composeMailData&record=" . $this->getId();
    }
    /**
     * Function to get this record and details as PDF
     */
    public function getPDF()
    {
        $recordId = $this->getId();
        $moduleName = $this->getModuleName();
        $controllerClassName = "Vtiger_" . $moduleName . "PDFController";
        $controller = new $controllerClassName($moduleName);
        $controller->loadRecord($recordId);
        $fileName = getModuleSequenceNumber($moduleName, $recordId);
        $controller->Output($fileName . ".pdf", "D");
    }
    /**
     * Function to get the pdf file name . This will conver the invoice in to pdf and saves the file
     * @return <String>
     *
     */
    public function getPDFFileName()
    {
        $moduleName = $this->getModuleName();
        if ($moduleName == "Quotes") {
            vimport("~~/modules/" . $moduleName . "/QuotePDFController.php");
            $controllerClassName = "Vtiger_QuotePDFController";
        } else {
            vimport("~~/modules/" . $moduleName . "/" . $moduleName . "PDFController.php");
            $controllerClassName = "Vtiger_" . $moduleName . "PDFController";
        }
        $recordId = $this->getId();
        $controller = new $controllerClassName($moduleName);
        $controller->loadRecord($recordId);
        $sequenceNo = getModuleSequenceNumber($moduleName, $recordId);
        $translatedName = vtranslate($moduleName, $moduleName);
        $filePath = "storage/" . $translatedName . "_" . $sequenceNo . ".pdf";
        $controller->Output($filePath, "F");
        return $filePath;
    }
    public function getProducts($module, $record, $setting, $seid = "")
    {
        global $log;
        global $adb;
        global $vtiger_current_version;
        $output = "";
        global $theme;
        global $current_user;
        $no_of_decimal_places = getCurrencyDecimalPlaces();
        $theme_path = "themes/" . $theme . "/";
        $image_path = $theme_path . "images/";
        $product_Detail = array();
        $additionalProductFieldsString = $additionalServiceFieldsString = "";
        $lineItemSupportedModules = array("Accounts", "Contacts", "Leads", "Potentials");
        if ($module == "Quotes" || $module == "PurchaseOrder" || $module == "SalesOrder" || $module == "Invoice" || $module == "PSTemplates") {
            $query = "SELECT\n\t\t\t\t\tcase when vtiger_products.productid != '' then vtiger_products.productname else vtiger_service.servicename end as item_name,\n \t\t            case when vtiger_products.productid != '' then vtiger_products.product_no else vtiger_service.service_no end as productcode,\n\t\t\t\t\tcase when vtiger_products.productid != '' then vtiger_products.unit_price else vtiger_service.unit_price end as unit_price,\n \t\t            case when vtiger_products.productid != '' then vtiger_products.qtyinstock else 'NA' end as qtyinstock,\n \t\t            case when vtiger_products.productid != '' then 'Products' else 'Services' end as entitytype,\n \t\t                        vtiger_vteitems.listprice,\n \t\t                        vtiger_vteitems.sequence,\n \t\t                        vtiger_vteitems.comment AS product_description,\n \t\t                        vtiger_vteitems.*,vtiger_crmentity.deleted\n \t                            FROM vtiger_vteitems\n\t\t\t\t\t\t\t\tLEFT JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_vteitems.vteitemid\n \t\t                        LEFT JOIN vtiger_products\n \t\t                                ON vtiger_products.productid=vtiger_vteitems.productid\n \t\t                        LEFT JOIN vtiger_service\n \t\t                                ON vtiger_service.serviceid=vtiger_vteitems.productid\n \t\t                        WHERE related_to=? AND  deleted = 0\n\t\t\t\t\t\t\t\tGROUP BY sequence \n \t\t                        ORDER BY sequence";
            $params = array($record);
        } else {
            if (in_array($module, $lineItemSupportedModules)) {
                if (version_compare($vtiger_current_version, "7.0.0", "<")) {
                    $query = "SELECT\n \t\t                        vtiger_products.productname as item_name,\n \t\t                        vtiger_products.productcode as productcode,\n \t\t                        vtiger_products.unit_price as listprice,\n \t\t                        vtiger_products.qtyinstock as qtyinstock,\n \t\t                        vtiger_seproductsrel.*,vtiger_crmentity.deleted,\n \t\t                        vtiger_crmentity.description AS product_description\n \t\t                        FROM vtiger_products\n \t\t                        INNER JOIN vtiger_crmentity\n \t\t                                ON vtiger_crmentity.crmid=vtiger_products.productid\n \t\t                        INNER JOIN vtiger_seproductsrel\n \t\t                                ON vtiger_seproductsrel.productid=vtiger_products.productid\n \t\t                        WHERE vtiger_seproductsrel.crmid=?";
                    $params = array($seid);
                } else {
                    $query = "(SELECT vtiger_products.productid, vtiger_products.productname as item_name, vtiger_products.product_no as productcode, vtiger_products.purchase_cost,\n\t\t\t\t\tvtiger_products.unit_price AS listprice, vtiger_products.qtyinstock as qtyinstock, vtiger_crmentity.deleted, \"Products\" AS entitytype,\n\t\t\t\t\tvtiger_products.is_subproducts_viewable, vtiger_crmentity.description " . $additionalProductFieldsString . " FROM vtiger_products\n\t\t\t\t\tINNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_products.productid\n\t\t\t\t\tINNER JOIN vtiger_seproductsrel ON vtiger_seproductsrel.productid=vtiger_products.productid\n\t\t\t\t\tINNER JOIN vtiger_productcf ON vtiger_products.productid = vtiger_productcf.productid\n\t\t\t\t\tWHERE vtiger_seproductsrel.crmid=? AND vtiger_crmentity.deleted=0 AND vtiger_products.discontinued=1)\n\t\t\t\t\tUNION\n\t\t\t\t\t(SELECT vtiger_service.serviceid AS productid, vtiger_service.servicename as item_name, vtiger_service.service_no AS productcode,\n\t\t\t\t\tvtiger_service.purchase_cost, vtiger_service.unit_price as listprice, \"NA\" as qtyinstock, vtiger_crmentity.deleted,\n\t\t\t\t\t\"Services\" AS entitytype, 1 AS is_subproducts_viewable, vtiger_crmentity.description " . $additionalServiceFieldsString . " FROM vtiger_service\n\t\t\t\t\tINNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_service.serviceid\n\t\t\t\t\tINNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_service.serviceid\n\t\t\t\t\tINNER JOIN vtiger_servicecf ON vtiger_service.serviceid = vtiger_servicecf.serviceid\n\t\t\t\t\tWHERE vtiger_crmentityrel.crmid=? AND vtiger_crmentity.deleted=0 AND vtiger_service.discontinued=1)";
                    $params = array($seid, $seid);
                }
            }
        }
        $result = $adb->pquery($query, $params);
        $num_rows = $adb->num_rows($result);
        $quoterModel = new Quoter_Module_Model();
        $customColumnSeting = $quoterModel->getCustomColumnSetting($setting);
        $userModel = Users_Record_Model::getCurrentUserModel();
        $currency_grouping_separator = $userModel->get("currency_grouping_separator");
        $currency_decimal_separator = $userModel->get("currency_decimal_separator");
        if (!empty($customColumnSeting)) {
            $productModuleModel = Vtiger_Module_Model::getInstance("Products");
            $serviceModuleModel = Vtiger_Module_Model::getInstance("Services");
        }
        $inventoryModules = getInventoryModules();
        if (in_array($module, $inventoryModules)) {
            $taxtype = $this->getInventoryTaxType($module, $record, $seid);
        }
        if (0 < $num_rows) {
            for ($i = 1; $i <= $num_rows; $i++) {
                $deleted = $adb->query_result($result, $i - 1, "deleted");
                $hdnProductId = $adb->query_result($result, $i - 1, "productid");
                $hdnProductcode = $adb->query_result($result, $i - 1, "productcode");
                $productname = $adb->query_result($result, $i - 1, "item_name");
                $comment = $adb->query_result($result, $i - 1, "comment");
                $qtyinstock = $adb->query_result($result, $i - 1, "qtyinstock");
                $unitprice = $adb->query_result($result, $i - 1, "unit_price");
                $entitytype = $adb->query_result($result, $i - 1, "entitytype");
                $level = $adb->query_result($result, $i - 1, "level");
                $section = $adb->query_result($result, $i - 1, "section_value");
                $running_item_value = $adb->query_result($result, $i - 1, "running_item_value");
                $description = $adb->query_result($result, $i - 1, "product_description");
                $sequence = $adb->query_result($result, $i - 1, "sequence");
                $product_Detail[$i]["level" . $i] = $level;
                $product_Detail[$i]["section" . $i] = $section;
                if ($running_item_value) {
                    $product_Detail[$i]["running_item_value" . $i] = unserialize(html_entity_decode($running_item_value));
                } else {
                    $product_Detail[$i]["running_item_value" . $i] = array();
                }
                foreach ($setting as $index => $value) {
                    if ($seid) {
                        $customColumnName = str_replace("cf_" . strtolower($module), "cf_" . strtolower($seid), $value->columnName);
                        $itemVal = $adb->query_result($result, $i - 1, $customColumnName);
                    } else {
                        $itemVal = $adb->query_result($result, $i - 1, $value->columnName);
                    }
                    if ($quoterModel->isCustomFields($value->columnName)) {
                        if ($value->productField && $entitytype == "Products") {
                            $productFieldModel = Vtiger_Field_Model::getInstance($value->productField, $productModuleModel);
                            if ($productFieldModel) {
                                if (is_numeric($itemVal)) {
                                    if ($_REQUEST["mode"] == "getItemsEdit" || $_REQUEST["module"] == "QuotingTool") {
                                        $newItemVal = $itemVal;
                                    } else {
                                        if ($productFieldModel->get("uitype") == 71 || $productFieldModel->get("uitype") == 72 || $productFieldModel->get("uitype") == 7 || $productFieldModel->get("uitype") == 9) {
                                            $newItemVal = $itemVal;
                                        } else {
                                            $newItemVal = number_format($itemVal, $no_of_decimal_places, $currency_decimal_separator, $currency_grouping_separator);
                                        }
                                    }
                                    $itemVal = $newItemVal;
                                }
                                $productFieldModel->set("fieldvalue", $itemVal);
                                $typeofdata = $this->setTypeDataField($productFieldModel->get("typeofdata"), $value->isMandatory);
                                $productFieldModel->set("typeofdata", $typeofdata);
                                if ($productFieldModel->get("uitype") == 10) {
                                    $productFieldModel = $this->setReferenceModule($productFieldModel, "Products");
                                }
                                $product_Detail[$i][$value->columnName . $i] = $productFieldModel;
                            }
                        } else {
                            if ($value->serviceField && $entitytype == "Services") {
                                $serviceFieldModel = Vtiger_Field_Model::getInstance($value->serviceField, $serviceModuleModel);
                                if ($serviceFieldModel) {
                                    if (is_numeric($itemVal)) {
                                        if ($_REQUEST["mode"] == "getItemsEdit" || $_REQUEST["module"] == "QuotingTool") {
                                            $newItemVal = $itemVal;
                                        } else {
                                            if ($serviceFieldModel->get("uitype") == 71 || $serviceFieldModel->get("uitype") == 72 || $serviceFieldModel->get("uitype") == 7 || $serviceFieldModel->get("uitype") == 9) {
                                                $newItemVal = $itemVal;
                                            } else {
                                                $newItemVal = number_format($itemVal, $no_of_decimal_places, $currency_decimal_separator, $currency_grouping_separator);
                                            }
                                        }
                                        $itemVal = $newItemVal;
                                    }
                                    $serviceFieldModel->set("fieldvalue", $itemVal);
                                    $typeofdata = $this->setTypeDataField($serviceFieldModel->get("typeofdata"), $value->isMandatory);
                                    $serviceFieldModel->set("typeofdata", $typeofdata);
                                    if ($serviceFieldModel->get("uitype") == 10) {
                                        $serviceFieldModel = $this->setReferenceModule($serviceFieldModel, "Services");
                                    }
                                    $product_Detail[$i][$value->columnName . $i] = $serviceFieldModel;
                                }
                            } else {
                                $product_Detail[$i][$value->columnName . $i] = $itemVal;
                            }
                        }
                    } else {
                        if (is_numeric($itemVal)) {
                            $product_Detail[$i][$value->columnName . $i] = decimalFormat(number_format($itemVal, $no_of_decimal_places, ".", ""));
                        } else {
                            $product_Detail[$i][$value->columnName . $i] = $itemVal;
                        }
                    }
                }
                if ($deleted || !isset($deleted)) {
                    $product_Detail[$i]["productDeleted" . $i] = true;
                } else {
                    if (!$deleted) {
                        $product_Detail[$i]["productDeleted" . $i] = false;
                    }
                }
                if (!empty($entitytype)) {
                    $product_Detail[$i]["entityType" . $i] = $entitytype;
                }
                if ($i != 1) {
                    $product_Detail[$i]["delRow" . $i] = "Del";
                }
                $product_Detail[$i]["hdnProductId" . $i] = $hdnProductId;
                $product_Detail[$i]["productName" . $i] = from_html($productname);
                if ($_REQUEST["action"] == "CreateSOPDF" || $_REQUEST["action"] == "CreatePDF" || $_REQUEST["action"] == "SendPDFMail") {
                    $product_Detail[$i]["productName" . $i] = htmlspecialchars($product_Detail[$i]["productName" . $i]);
                }
                $product_Detail[$i]["hdnProductcode" . $i] = $hdnProductcode;
                $product_Detail[$i]["comment" . $i] = $comment;
                $product_Detail[$i]["qtyInStock" . $i] = decimalFormat($qtyinstock);
                $product_Detail[$i]["unitPrice" . $i] = decimalFormat(number_format($unitprice, $no_of_decimal_places, ".", ""));
                $pre = $i - 1;
                if ($this->isSubProduct($i, $record) && 1 < $i) {
                    $parentId = $this->getParentProductId($record, $i);
                    if (0 < $parentId) {
                        if ($parentId == $product_Detail[$pre]["hdnProductId" . $pre]) {
                            $product_Detail[$i]["arrRowName"] = $product_Detail[$pre]["arrRowName"];
                            array_push($product_Detail[$i]["arrRowName"], $i);
                        } else {
                            $product_Detail[$i]["arrParentRowName"] = array_slice($product_Detail[$pre]["arrRowName"], 0, $level - 1);
                            $product_Detail[$i]["arrRowName"] = $product_Detail[$i]["arrParentRowName"];
                            array_push($product_Detail[$i]["arrRowName"], $i);
                        }
                        $product_Detail[$i]["rowName"] = implode("-", $product_Detail[$i]["arrRowName"]);
                        $product_Detail[$i]["parentProductId" . $i] = $parentId;
                    }
                } else {
                    $product_Detail[$i]["arrRowName"] = array($i);
                    $product_Detail[$i]["rowName"] = $i;
                }
                if ($this->isParentProduct($hdnProductId)) {
                    $product_Detail[$i]["isParentProduct"] = true;
                }
                $product_Detail[$i]["total_format" . $i] = $this->numberFormat($product_Detail[$i]["total" . $i]);
                $product_Detail[$i]["net_price_format" . $i] = $this->numberFormat($product_Detail[$i]["net_price" . $i]);
                $tax_details = getTaxDetailsForProduct($hdnProductId, "all");
                $tax_total = 0;
                for ($tax_count = 0; $tax_count < count($tax_details); $tax_count++) {
                    $tax_name = $tax_details[$tax_count]["taxname"];
                    $tax_label = $tax_details[$tax_count]["taxlabel"];
                    $tax_value = "0";
                    if ($record != "") {
                        if ($taxtype == "individual") {
                            $tax_value = $adb->query_result($result, $i - 1, $tax_name);
                        } else {
                            $tax_value = $tax_details[$tax_count]["percentage"];
                        }
                    } else {
                        $tax_value = $tax_details[$tax_count]["percentage"];
                    }
                    $product_Detail[$i]["taxes"][$tax_count]["taxname"] = $tax_name;
                    $product_Detail[$i]["taxes"][$tax_count]["taxlabel"] = $tax_label;
                    $product_Detail[$i]["taxes"][$tax_count]["percentage"] = decimalFormat($tax_value);
                    $tax_total += $tax_value;
                }
                $product_Detail[$i]["tax_total" . $i] = decimalFormat(number_format($tax_total, $no_of_decimal_places, ".", ""));
                $product_Detail[1]["final_details"]["taxtype"] = $taxtype;
                $product_Detail[$i]["description" . $i] = $description;
                $product_Detail[$i]["sequence" . $i] = $sequence;
                if ($_REQUEST["record"] == "" && $_REQUEST["salesorder_id"] != "") {
                    $conversionRate = $conversionRateForPurchaseCost = 1;
                    $productRecordModel = Vtiger_Record_Model::getInstanceById($hdnProductId);
                    $currencies = Inventory_Module_Model::getAllCurrencies();
                    $priceDetails = $productRecordModel->getPriceDetails();
                    $rs = $adb->pquery("select currency_id from vtiger_salesorder where salesorderid = ?", array($record));
                    $currencyId = $adb->query_result($rs, 0, "currency_id");
                    foreach ($priceDetails as $currencyDetails) {
                        if ($currencyId == $currencyDetails["curid"]) {
                            $conversionRate = $currencyDetails["conversionrate"];
                        }
                    }
                    foreach ($currencies as $currencyInfo) {
                        if ($currencyId == $currencyInfo["curid"]) {
                            $conversionRateForPurchaseCost = $currencyInfo["conversionrate"];
                            break;
                        }
                    }
                    $decimalPlace = getCurrencyDecimalPlaces();
                    $purchaseCosts = round((double) $productRecordModel->get("purchase_cost") * (double) $conversionRateForPurchaseCost, $decimalPlace);
                    if ($purchaseCosts) {
                        $product_Detail[$i]["listprice" . $i] = $purchaseCosts;
                    }
                }
            }
        } else {
            if ($module == "Quotes" || $module == "PurchaseOrder" || $module == "SalesOrder" || $module == "Invoice" || $module == "PSTemplates") {
                $recordModel = Inventory_Record_Model::getInstanceById($record, $module);
                $product_Detail = $recordModel->getProducts();
                $total = count($product_Detail);
                for ($i = 1; $i <= $total; $i++) {
                    if ($product_Detail[$i]["qty" . $i] == "") {
                        $product_Detail = array();
                        break;
                    }
                    $product_Detail[$i]["quantity" . $i] = $product_Detail[$i]["qty" . $i];
                    $product_Detail[$i]["listprice" . $i] = $product_Detail[$i]["listPrice" . $i];
                    $product_Detail[$i]["total" . $i] = $product_Detail[$i]["totalAfterDiscount" . $i];
                    $product_Detail[$i]["total_format" . $i] = $product_Detail[$i]["totalAfterDiscount" . $i];
                    $product_Detail[$i]["net_price" . $i] = $product_Detail[$i]["netPrice" . $i];
                    $product_Detail[$i]["net_price_format" . $i] = $product_Detail[$i]["netPrice" . $i];
                    $product_Detail[$i]["level" . $i] = "1";
                }
            }
        }
        return $product_Detail;
    }
    public function setReferenceModule($fieldModel, $module)
    {
        global $adb;
        $sql = "select relmodule from vtiger_fieldmodulerel where fieldid=? and module =? ";
        $rs = $adb->pquery($sql, array($fieldModel->getId(), $module));
        if ($row = $adb->fetchByAssoc($rs)) {
            $relmodule = $row["relmodule"];
        }
        if ($relmodule) {
            $fieldModel->set("relmodule", $relmodule);
            if ($fieldModel->get("fieldvalue")) {
                $entityNames = getEntityName($relmodule, array($fieldModel->get("fieldvalue")));
                $fieldModel->set("displayName", $entityNames[$fieldModel->get("fieldvalue")]);
            }
        }
        return $fieldModel;
    }
    public function getInventoryTaxType($module, $id, $seid)
    {
        global $log;
        global $adb;
        $log->debug("Entering into function getInventoryTaxType(" . $module . ", " . $id . ").");
        if ($id != "" && $seid != "") {
            $module = $seid;
        }
        $inv_table_array = array("PurchaseOrder" => "vtiger_purchaseorder", "SalesOrder" => "vtiger_salesorder", "Quotes" => "vtiger_quotes", "Invoice" => "vtiger_invoice", "PSTemplates" => "vtiger_pstemplates");
        $inv_id_array = array("PurchaseOrder" => "purchaseorderid", "SalesOrder" => "salesorderid", "Quotes" => "quoteid", "Invoice" => "invoiceid", "PSTemplates" => "pstemplatesid");
        $res = $adb->pquery("select taxtype from " . $inv_table_array[$module] . " where " . $inv_id_array[$module] . "=?", array($id));
        $taxtype = $adb->query_result($res, 0, "taxtype");
        $log->debug("Exit from function getInventoryTaxType(" . $module . ", " . $id . ").");
        return $taxtype;
    }
    public function calculateValueByFormula($arrVariable, $formula)
    {
        foreach ($arrVariable as $key => $variable) {
            if (!$variable) {
                $value = 0;
            } else {
                $value = $variable;
            }
            $formula = str_replace("\$" . $key . "\$", $value, $formula);
        }
        $result = eval("return " . $formula . ";");
        if ($result) {
            return $result;
        }
        return 0;
    }
    public function setTypeDataField($typeData, $isMandatory)
    {
        if ($isMandatory == 1) {
            $tmp = explode("~", $typeData);
            return $tmp[0] . "~M";
        }
        $tmp = explode("~", $typeData);
        return $tmp[0] . "~O";
    }
    public function orderArrayByIndex(&$arr)
    {
        if (!empty($arr)) {
            usort($arr, function ($a, $b) {
                if ($a->index == $b->index) {
                    return 0;
                }
                return $a->index < $b->index ? -1 : 1;
            });
        }
    }
    public function isSubProduct($sequenceNo, $record)
    {
        global $adb;
        $rs = $adb->pquery("SELECT * FROM vtiger_inventorysubproductrel WHERE id = ? AND sequence_no = ?", array($record, $sequenceNo));
        $rowNum = $adb->num_rows($rs);
        if (0 < $rowNum) {
            return true;
        }
        return false;
    }
    public function isParentProduct($hdnProductId)
    {
        global $adb;
        $rs = $adb->pquery("SELECT * FROM vtiger_seproductsrel WHERE productid = ? ", array($hdnProductId));
        $rowNum = $adb->num_rows($rs);
        if (0 < $rowNum) {
            return true;
        }
        return false;
    }
    public function getParentProductId($record, $sequenceNo)
    {
        global $adb;
        $rs = $adb->pquery("SELECT * FROM vtiger_inventorysubproductrel WHERE id = ? AND sequence_no = ?", array($record, $sequenceNo));
        $rowNum = $adb->num_rows($rs);
        if (0 < $rowNum) {
            return $adb->query_result($rs, 0, "productid");
        }
        return 0;
    }
    public function numberFormat($number)
    {
        if (is_numeric($number)) {
            $userModel = Users_Record_Model::getCurrentUserModel();
            $currency_grouping_separator = $userModel->get("currency_grouping_separator");
            $currency_decimal_separator = $userModel->get("currency_decimal_separator");
            $no_of_decimal_places = getCurrencyDecimalPlaces();
            return number_format($number, $no_of_decimal_places, $currency_decimal_separator, $currency_grouping_separator);
        }
        return $number;
    }
    public function getTotalValues($module, $listColumn, $record)
    {
        global $adb;
        $result = array();
        if ($record) {
            global $adb;
            $tableName = "vtiger_" . strtolower($module);
            $tablecfName = "vtiger_" . strtolower($module) . "cf";
            $strListColumn = implode(",", $listColumn);
            $strListColumn = strtolower($strListColumn);
            $strListColumn = $adb->sql_escape_string($strListColumn);
            $moduleFocus = CRMEntity::getInstance($module);
            $table_index = $moduleFocus->table_index;
            $query = "SELECT " . $strListColumn . " FROM " . $tableName . " \n                        INNER JOIN " . $tablecfName . " ON " . $tablecfName . "." . $table_index . " = " . $tableName . "." . $table_index . "\n                        WHERE " . $tableName . "." . $table_index . " = ? ";
            $rs = $adb->pquery($query, array($record));
            if (0 < $adb->num_rows($rs)) {
                $no_of_decimal_places = getCurrencyDecimalPlaces();
                foreach ($listColumn as $column) {
                    $value = $adb->query_result($rs, 0, strtolower($column));
                    $result[$column] = number_format($value, $no_of_decimal_places, ".", "");
                }
            }
        }
        return $result;
    }
    /**
     * Function to get Image Details
     * @return <array> Image Details List
     */
    public function getImageDetails($recordId)
    {
        $db = PearDatabase::getInstance();
        $imageDetails = array();
        if ($recordId) {
            $sql = "SELECT vtiger_attachments.*, vtiger_crmentity.setype FROM vtiger_attachments\n\t\t\t\t\t\tINNER JOIN vtiger_seattachmentsrel ON vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid\n\t\t\t\t\t\tINNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_attachments.attachmentsid\n\t\t\t\t\t\tWHERE vtiger_crmentity.setype = 'Products Image' AND vtiger_seattachmentsrel.crmid = ?";
            $result = $db->pquery($sql, array($recordId));
            $count = $db->num_rows($result);
            for ($i = 0; $i < $count; $i++) {
                $imageIdsList[] = $db->query_result($result, $i, "attachmentsid");
                $imagePathList[] = $db->query_result($result, $i, "path");
                $imageName = $db->query_result($result, $i, "name");
                $imageOriginalNamesList[] = decode_html($imageName);
                $imageNamesList[] = $imageName;
            }
            if (is_array($imageOriginalNamesList)) {
                $countOfImages = count($imageOriginalNamesList);
                for ($j = 0; $j < $countOfImages; $j++) {
                    $imageDetails[] = array("id" => $imageIdsList[$j], "orgname" => $imageOriginalNamesList[$j], "path" => $imagePathList[$j] . $imageIdsList[$j], "name" => $imageNamesList[$j]);
                }
            }
        }
        return $imageDetails;
    }
}

?>