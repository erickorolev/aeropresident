<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Potentials_CheckBeforeSave_Action extends Vtiger_Action_Controller {

     function checkPermission(Vtiger_Request $request) {
     	return;
     }
     
     public function process(Vtiger_Request $request) {
     	global $adb;
     	 $dataArr = $request->get('checkBeforeSaveData');
     	 $response = "OK";
         $message = "";

         if($request->get('detailViewAjaxMode')) {

         	 if($dataArr['sales_stage'] == 'Closed Lost' && !$dataArr['cf_1252']) {
			     $response = "ALERT";
			     $message = "Закрытие сделки со статусом Закрыта неудачно возможно только в режиме редактирования карты сделки";
			 }
			 echo json_encode(array('response' => $response, 'message' => $message));

         }

     	return;
     } 

 }