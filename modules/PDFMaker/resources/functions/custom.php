<?php

if (!function_exists('getNumEnding')) {

	function getNumEnding($number,$ending1,$ending2,$ending3){
		$number = $number % 100;
		if($number>=11 && $number<=19){
			$ending=$ending3;
		}else{
			$i = $number % 10;
			switch($i){
				case (1): $ending = $ending1; break;
				case (2): case (3): case (4): $ending = $ending2; break;
				default: $ending=$ending3;
			}
		}
		return $ending;
	}

}

if (!function_exists('subStrNum')) {

	function subStrNum($string,$quantity){
		$string = substr($string,0,-$quantity);
		return $string;
	}

}

if (!function_exists('getFieldValueOfItemCustom')) {
    function getFieldValueOfItemCustom($fieldName, $recordId, $itemNo, $module)
    {
        $result = '';

        if (!empty($module) && !empty($fieldName)) {
            global $adb;

            $moduleModel = Vtiger_Module_Model::getInstance($module);
            $vteItemModuleModel = Vtiger_Module_Model::getInstance('VTEItems');
            $productsModuleModel = Vtiger_Module_Model::getInstance('Products');
            $servicesModuleModel = Vtiger_Module_Model::getInstance('Services');
            $quoterModuleModel = new Quoter_Module_Model();
            $setting = $quoterModuleModel->getSettingForModule($module);
            $fieldName = sprintf("%s", strtolower($fieldName));

            if (checkVTEItemsActive()) {
                $query = "SELECT
					case when vtiger_products.productid != '' then vtiger_products.productname else vtiger_service.servicename end as item_name,
					case when vtiger_products.productid != '' then 'Products' else 'Services' end as mapping_module,
 		                        vtiger_vteitems.*
 	                            FROM vtiger_vteitems
								INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_vteitems.vteitemid
 		                        LEFT JOIN vtiger_products
 		                                ON vtiger_products.productid=vtiger_vteitems.productid
 		                        LEFT JOIN vtiger_service
 		                                ON vtiger_service.serviceid=vtiger_vteitems.productid
 		                        WHERE related_to=? AND  vtiger_vteitems.sequence = ? AND deleted = 0 
 		                        GROUP BY sequence
 		                        ORDER BY sequence
 		                        LIMIT 1";
            } else {
                $query = "SELECT
					case when vtiger_products.productid != '' then vtiger_products.productname else vtiger_service.servicename end as item_name,
 		                        vtiger_inventoryproductrel.description AS product_description,
 		                        vtiger_inventoryproductrel.*
 	                            FROM vtiger_inventoryproductrel
								LEFT JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_inventoryproductrel.productid
 		                        LEFT JOIN vtiger_products
 		                                ON vtiger_products.productid=vtiger_inventoryproductrel.productid
 		                        LEFT JOIN vtiger_service
 		                                ON vtiger_service.serviceid=vtiger_inventoryproductrel.productid
 		                        WHERE id=? AND vtiger_inventoryproductrel.sequence_no = ?
 		                        ORDER BY sequence_no
 		                        LIMIT 1";
            }
            $params = array($recordId, $itemNo);
            $queryResult = $adb->pquery($query, $params);

            if ($adb->num_rows($queryResult)) {
                $quoterRecordModel = new Quoter_Record_Model();
                $data = $adb->fetchByAssoc($queryResult);
                $result = $data[$fieldName];
                $fieldModel = $moduleModel->getField($fieldName);

                if (!$fieldModel) {
                    $fieldModel = $vteItemModuleModel->getField($fieldName);

                    foreach ($setting as $fieldSetting) {
                        if ($fieldName == $fieldSetting->columnName) {
                            if ($data['mapping_module'] == 'Products') {
                                $fieldModel = $productsModuleModel->getField($fieldSetting->productField);
                            } else {
                                if ($data['mapping_module'] == 'Services') {
                                    $fieldModel = $servicesModuleModel->getField($fieldSetting->serviceField);
                                }
                            }
                            if (!$fieldModel) {
                                $fieldModel = $vteItemModuleModel->getField($fieldName);
                            }
                        }
                    }
                }

                if ($fieldModel->getFieldDataType() == 'boolean') {
                    $result = $result ? 'Yes' : 'No';
                } else {
                    if (is_numeric($result)) {
                        if (floatval($result) == 0) {
                            $result = '';
                        } else {
                            $result = $quoterRecordModel->numberFormat($result);
                        }
                    }
                }
            }
        }

        if (strpos($result, '|##|') !== false) {
            $result = str_replace('|##|', ',', $result);
        }

        if (DateTime::createFromFormat('Y-m-d', $result) !== false) {
            $result = DateTimeField::convertToUserFormat($result);
        }
		
		$result = substr($result,0,-3);
		$result = str_replace("&#039;"," ",$result);

        return $result;
    }
}