<?php

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

require_once('modules/com_vtiger_workflow/VTEntityCache.inc');
require_once('modules/com_vtiger_workflow/VTWorkflowUtils.php');
require_once('modules/com_vtiger_workflow/VTSimpleTemplate.inc');

require_once('modules/PDFMaker/PDFMaker.php');
require_once("modules/PDFMaker/resources/mpdf/mpdf.php");

class VTPDFMakerTask extends VTTask {

    public $executeImmediately = true;

    public function getFieldNames() {
        return array('title', 'description', 'folder', 'template', 'template_language');
    }

    public function getFolders() {
        
        $fieldvalue= array();
        
        $adb = PearDatabase::getInstance();
        $sql = "select foldername,folderid from vtiger_attachmentsfolder order by foldername";
        $res = $adb->pquery($sql, array());
        for ($i = 0; $i < $adb->num_rows($res); $i++) {
            $fid = $adb->query_result($res, $i, "folderid");
            $fname = $adb->query_result($res, $i, "foldername");
            $fieldvalue[$fid] =  $fname;
        } 
        return $fieldvalue;
    }
    
    public function getTemplates($selected_module) {
       
        $PDFMaker = new PDFMaker_PDFMaker_Model();
        $templates = $PDFMaker->GetAvailableTemplates($selected_module);
        $def_template = array();
        $fieldvalue = array();
        if ($PDFMaker->CheckPermissions("DETAIL") !== false) {
            foreach ($templates as $templateid => $valArr) {
                if ($valArr["is_default"] == "1" || $valArr["is_default"] == "3")
                    $def_template[$templateid] =  $valArr["templatename"];
                else
                    $fieldvalue[$templateid] = $valArr["templatename"];
            }   
            
            if (count($def_template) > 0) $fieldvalue = (array) $def_template + (array) $fieldvalue;
        }
        
        if (count($fieldvalue) == 0) $fieldvalue[] = "none";
        
        return $fieldvalue;
    }
    
    public function getLanguages() {
        
        $langvalue = array();
        $currlang = array();
        
        $adb = PearDatabase::getInstance();
        $temp_res = $adb->query("SELECT label, prefix FROM vtiger_language WHERE active=1");

        while ($temp_row = $adb->fetchByAssoc($temp_res)) {
            $template_languages[$temp_row["prefix"]] = $temp_row["label"];

            if($temp_row["prefix"] == $current_language)
                $currlang[$temp_row["prefix"]] = $temp_row["label"];
            else
                $langvalue[$temp_row["prefix"]] = $temp_row["label"];
        }
        $langvalue = (array) $currlang + (array) $langvalue;
        
        return $langvalue;
    }
    
    function getAdmin() {
        $user = new Users();
        $user->retrieveCurrentUserInfoFromFile(1);
        global $current_user;
        $this->originalUser = $current_user;
        $current_user = $user;
        return $user;
    }

    public function doTask($entityData) {
        global $current_user, $log, $root_directory;

        $request = new Vtiger_Request($_REQUEST, $_REQUEST);
        
        $adb = PearDatabase::getInstance();
        $PDFMaker = new PDFMaker_PDFMaker_Model();
        
        $userId = $entityData->get('assigned_user_id');
        if ($userId === null) {
            $userId = vtws_getWebserviceEntityId('Users', 1);
        }

        $moduleName = $entityData->getModuleName();
        $adminUser = $this->getAdmin();
        $id = $entityData->getId();

        list($id2, $assigned_user_id) = explode("x", $userId);
        list($id3, $parentid) = explode("x", $id);

        $focus = CRMEntity::getInstance("Documents");
        $focus->parentid = $parentid;

        $modFocus = CRMEntity::getInstance($moduleName);
        if (isset($focus->parentid)) {
            $modFocus->retrieve_entity_info($focus->parentid, $moduleName);
            $modFocus->id = $focus->parentid;
        }

        $templateid = $this->template;

        if ($templateid != "" && $templateid != "0")  
        {    
            if ($PDFMaker->isTemplateDeleted($templateid)) return; 
            
            $foldername = $adb->getOne("SELECT foldername FROM vtiger_attachmentsfolder WHERE folderid='" . $this->folder . "'", 0, "foldername");

            $fieldname = $adb->getOne("SELECT fieldname FROM vtiger_field WHERE uitype=4 AND tabid=" . getTabId($moduleName), 0, "fieldname");

            if (isset($modFocus->column_fields[$fieldname]) && $modFocus->column_fields[$fieldname] != "") {
                $file_name = $PDFMaker->generate_cool_uri($foldername . "_" . $modFocus->column_fields[$fieldname]) . ".pdf";
            } else {
                $file_name = $PDFMaker->generate_cool_uri($foldername . "_" . $templateid . $focus->parentid . date("ymdHi")) . ".pdf";
            }

            $focus->column_fields['notes_title'] = $this->title;
            $focus->column_fields['assigned_user_id'] = $assigned_user_id;
            $focus->column_fields['filename'] = $file_name;
            $focus->column_fields['notecontent'] = $this->description;
            $focus->column_fields['filetype'] = 'application/pdf';
            $focus->column_fields['filesize'] = '';
            $focus->column_fields['filelocationtype'] = 'I';
            $focus->column_fields['fileversion'] = '';
            $focus->column_fields['filestatus'] = 'on';
            $focus->column_fields['folderid'] = $this->folder;
            $focus->save('Documents');

            $language = $this->template_language;
            $PDFMaker->createPDFAndSaveFile($request, $templateid, $focus, array($modFocus->id), $file_name, $moduleName, $language);
        }
    }
}