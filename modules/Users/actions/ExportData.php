<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class Users_ExportData_Action extends Vtiger_ExportData_Action {

	var $exportableFields = array(	'user_name'		=> 'User Name',
									'title'			=> 'Title',
									'first_name'	=> 'First Name',
									'last_name'		=> 'Last Name',
									'email1'		=> 'Email',
									'email2'		=> 'Other Email',
									'secondaryemail'=> 'Secondary Email',
									'phone_work'	=> 'Office Phone',
									'phone_mobile'	=> 'Mobile',
									'phone_fax'		=> 'Fax',
									'address_street'=> 'Street',
									'address_city'	=> 'City',
									'address_state'	=> 'State',
									'address_country'	=> 'Country',
            // SalesPlatform.ru begin
									//'address_postalcode'=> 'Postal Code');
            'address_postalcode'=> 'Postal Code',
            'roleid' => 'Role');
            // SalesPlatform.ru end
        //SalesPlatform.ru begin
        public function validateRequest(Vtiger_Request $request) {
            $request->validateWriteAccess(true);
        }
        //SalesPlatform.ru end
	/**
	 * Function exports the data based on the mode
	 * @param Vtiger_Request $request
	 */
	function ExportData(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$moduleName = $request->get('source_module');
		if ($moduleName) {
			$this->moduleInstance = Vtiger_Module_Model::getInstance($moduleName);
			$this->moduleFieldInstances = $this->moduleInstance->getFields();
			$this->focus = CRMEntity::getInstance($moduleName);
			$query = $this->getExportQuery($request);
			$result = $db->pquery($query, array());
			$headers = $this->exportableFields;
			foreach ($headers as $header) {
				$translatedHeaders[] = vtranslate(html_entity_decode($header, ENT_QUOTES), $moduleName);
			}

			$entries = array();
                        $allRoles = Settings_Roles_Record_Model::getAll();
			for ($i=0; $i<$db->num_rows($result); $i++) {
				$entries[$i] = $db->fetchByAssoc($result, $i);
                                $role =  $allRoles[$entries[$i]['roleid']];
                                if(!empty($role)) {
                                         $entries[$i]['roleid'] = $role->getName();
                                }
			}
                        
                        

			return $this->output($request, $translatedHeaders, $entries);
		}
	}

	/**
	 * Function that generates Export Query based on the mode
	 * @param Vtiger_Request $request
	 * @return <String> export query
	 */
	function getExportQuery(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$cvId = $request->get('viewname');
		$moduleName = $request->get('source_module');

		$queryGenerator = new QueryGenerator($moduleName, $currentUser);
		if (!empty($cvId)) {
			$queryGenerator->initForCustomViewById($cvId);
		}

		$acceptedFields = array_keys($this->exportableFields);
		$queryGenerator->setFields($acceptedFields);
		return $queryGenerator->getQuery();
	}

}
