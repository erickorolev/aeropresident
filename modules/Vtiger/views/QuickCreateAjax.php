<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_QuickCreateAjax_View extends Vtiger_IndexAjax_View {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();

		if (!(Users_Privileges_Model::isPermitted($moduleName, 'CreateView'))) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', $moduleName));
		}
	}

        private function emailRelated($moduleName, $email){
            $db = PearDatabase::getInstance();
            $fieldsMap = [];
            $recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
            $module = $recordModel->getModule();
            
            foreach($module->getFields() as $fieldModel) {
                if($fieldModel->uitype == 13 && $fieldModel->isActiveField()) {
                    $fieldsMap[$fieldModel->table][] = $fieldModel->column;
                }
            }
            foreach($fieldsMap as $table => $fields){
                $sql = "SELECT " . $module->basetableid . " FROM " . $table ." INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = " 
                        . $table . "." . $module->basetableid . " WHERE vtiger_crmentity.deleted = 0 AND (";
                $parameters = array();
                foreach($fields as $field) {
                    $sql .= $field . " = ?";
                    if (!next($fields)){
                        $sql .= ")";
                    } else {
                        $sql .=" OR ";
                    }
                    $parameters[] = $email;
                }
                $result = $db->pquery($sql, $parameters);
                if ($result) {
                    $emailRelatedId = $db->query_result($result, 0, $module->basetableid);
                    if ($emailRelatedId != null){
                        return $emailRelatedId;
                    }
                }
            }
            
            return null;
        }
        
	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
                $db = PearDatabase::getInstance();

		$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
		$moduleModel = $recordModel->getModule();
		
		$fieldList = $moduleModel->getFields();
		$requestFieldList = array_intersect_key($request->getAll(), $fieldList);

                if ($request->get('email')){
                    $emailAddress = $request->get('email');
                    $requestFieldList['contact_id'] = $this->emailRelated('Contacts', $emailAddress);
                    $requestFieldList['related_to'] = $this->emailRelated('Accounts', $emailAddress);
                    $msgNo = $request->get('_msgno');
                    $folderName = $request->get('_folder');
                }
                
		foreach($requestFieldList as $fieldName => $fieldValue){
			$fieldModel = $fieldList[$fieldName];
			if($fieldModel->isEditable()) {
				$recordModel->set($fieldName, $fieldModel->getDBInsertValue($fieldValue));
			}
		}

		$fieldsInfo = array();
		foreach($fieldList as $name => $model){
			$fieldsInfo[$name] = $model->getFieldInfo();
		}

		$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_QUICKCREATE);
                $picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);

		$viewer = $this->getViewer($request);
                if ($msgNo && $folderName) {
                    $viewer->assign('MSG_NO', $msgNo);
                    $viewer->assign('FOLDER_NAME', $folderName);
                }
          	$recordStructure = $recordStructureInstance->getStructure();
                if (!array_key_exists('contact_id', $recordStructure)) {
                    $viewer->assign('CONTACT_ID', $requestFieldList['contact_id']);
                }
		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE', Vtiger_Functions::jsonEncode($picklistDependencyDatasource));
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('SINGLE_MODULE', 'SINGLE_'.$moduleName);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('FIELDS_INFO', json_encode($fieldsInfo));

		$viewer->assign('SCRIPTS', $this->getHeaderScripts($request));

		$viewer->assign('MAX_UPLOAD_LIMIT_MB', Vtiger_Util_Helper::getMaxUploadSize());
		$viewer->assign('MAX_UPLOAD_LIMIT_BYTES', Vtiger_Util_Helper::getMaxUploadSizeInBytes());
		echo $viewer->view('QuickCreate.tpl',$moduleName,true);

	}
	
	
	public function getHeaderScripts(Vtiger_Request $request) {
		
		$moduleName = $request->getModule();
		
		$jsFileNames = array(
			"modules.$moduleName.resources.Edit"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return $jsScriptInstances;
	}
    
}