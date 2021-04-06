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
require_once('modules/com_vtiger_workflow/VTEmailRecipientsTemplate.inc');
require_once('modules/com_vtiger_workflow/tasks/VTEmailTask.inc');
require_once('modules/Emails/mail.php');
require_once('include/simplehtmldom/simple_html_dom.php');
require_once('modules/Emails/models/Mailer.php');
require_once('modules/PDFMaker/PDFMaker.php');
require_once("modules/PDFMaker/resources/mpdf/mpdf.php");

class VTPDFMakerMailTask extends VTEmailTask {

    // Sending email takes more time, this should be handled via queue all the time.
    public $executeImmediately = false;

    public function getFieldNames(){
        return array("subject", "content", "recepient", 'emailcc', 'emailbcc', 'fromEmail', 'template', 'template_language', 'signature','replyTo');
    }
    public function doTask($entity){
        global $current_user;
 
        $util = new VTWorkflowUtils();
        $admin = $util->adminUser();
        $module = $entity->getModuleName();

        $taskContents = Zend_Json::decode($this->getContents($entity));
        $relatedInfo = Zend_Json::decode($this->getRelatedInfo());
        $from_email	= $taskContents['fromEmail'];
        $from_name	= $taskContents['fromName'];
        $to_email	= $taskContents['toEmail'];
        $cc		= $taskContents['ccEmail'];
        $bcc        = $taskContents['bccEmail'];
        $replyTo 	= $taskContents['replyTo'];
        $subject	= $taskContents['subject'];
        $content	= $taskContents['content'];

        $entityCache = new VTEntityCache($admin);

        $et = new VTEmailRecipientsTemplate($this->recepient);
        $to_email = $et->render($entityCache, $entity->getId());

        if(!empty($to_email)) {
            //Storing the details of emails
            $entityIdDetails = vtws_getIdComponents($entity->getId());
            $entityId = $entityIdDetails[1];
            $moduleName = 'Emails';
            $userId = $current_user->id;
            $emailFocus = CRMEntity::getInstance($moduleName);
            $processedContent = Emails_Mailer_Model::getProcessedContent($content); // To remove script tags
            $mailerInstance = Emails_Mailer_Model::getInstance();
            $mailerInstance->isHTML(true);
            $processedContentWithURLS = $mailerInstance->convertToValidURL($processedContent);

            $emailFocus->column_fields['assigned_user_id'] = $userId;
            $emailFocus->column_fields['subject'] = $subject;
            $emailFocus->column_fields['description'] = $processedContentWithURLS;
            $emailFocus->column_fields['from_email'] = $from_email;
            $emailFocus->column_fields['saved_toid'] = $to_email;
            $emailFocus->column_fields['ccmail'] = $cc;
            $emailFocus->column_fields['bccmail'] = $bcc;
            $emailFocus->column_fields['parent_id'] = $entityId."@$userId|";
            $emailFocus->column_fields['email_flag'] = 'SENT';
            $emailFocus->column_fields['activitytype'] = $moduleName;
            $emailFocus->column_fields['date_start'] = date('Y-m-d');
            $emailFocus->column_fields['time_start'] = date('H:i:s');
            $emailFocus->column_fields['mode'] = '';
            $emailFocus->column_fields['id'] = '';
            $emailFocus->save($moduleName);

            // To add entry in ModTracker
            $entityFocus = CRMEntity::getInstance($module);
            $entityFocus->retrieve_entity_info($entityId, $module);
            relateEntities($entityFocus, $module, $entityId, 'Emails', $emailFocus->id);
            
            //Including email tracking details
            $emailId = $emailFocus->id;
            $imageDetails = Vtiger_Functions::getTrackImageContent($emailId, $entityId);
            $content = $content.$imageDetails;

            if (stripos($content, '<img src="cid:logo" />')) {
                $mailerInstance->AddEmbeddedImage('layouts/v7/skins/images/logo_mail.jpg', 'logo', 'logo.jpg',"base64","image/jpg");
            }

            $nameEmailArray = Vtiger_Functions::extractNameEmail($from_email);
            if($nameEmailArray) {
                $from_name = $nameEmailArray['name'];
                $from_email = $nameEmailArray['email'];
            }

            if (is_array($this->template))
                $Templates = $this->template;
            else
                $Templates = array($this->template);
            
            if (count($Templates) > 0) {
                $request = new Vtiger_Request($_REQUEST, $_REQUEST);
                $adb = PearDatabase::getInstance();
                $PDFMaker = new PDFMaker_PDFMaker_Model();

                list($id3, $id) = explode("x", $entity->getId());

                $modFocus = CRMEntity::getInstance($module);

                $modFocus->retrieve_entity_info($id, $module);
                $modFocus->id = $id;

                $language = $this->template_language;
                
                foreach ($Templates AS $templateid) {
                    if ($templateid != "0" && $templateid != "") {

                        if ($PDFMaker->isTemplateDeleted($templateid)) return; 
                        $result = $adb->query("SELECT fieldname FROM vtiger_field WHERE uitype=4 AND tabid=" . getTabId($module));
                        $fieldname = $adb->query_result($result, 0, "fieldname");
                        if (isset($modFocus->column_fields[$fieldname]) && $modFocus->column_fields[$fieldname] != "") {
                            $file_name = $PDFMaker->generate_cool_uri($modFocus->column_fields[$fieldname]) . ".pdf";
                        } else {
                            $file_name = $templateid . $emailFocus->parentid . date("ymdHi") . ".pdf";
                        }
                        $PDFMaker->createPDFAndSaveFile($request,$templateid, $emailFocus, array($modFocus->id), $file_name, $module, $language);
                    }
                }
               /*
                $status = send_mail($module, $to_email, $from_name, $from_email, $subject, $content, $cc, $bcc, 'all', $emailId, $logo);*/

            } else {/*
                $status = send_mail($module, $to_email, $from_name, $from_email, $subject, $content, $cc, $bcc, '', '', $logo);
*/
            }

            //set properties
            $toEmail = trim($to_email,',');
            if(!empty($toEmail)) {
                if(is_array($toEmail)) {
                    foreach ($toEmail as $email) {
                        $mailerInstance->AddAddress($email);
                    }
                }else{
                    $toEmails = explode(',', $toEmail);
                    foreach ($toEmails as $email) {
                        $mailerInstance->AddAddress($email);
                    }

                }
            }
            //Retrieve MessageID from Mailroom table only if module is not users
            $inReplyToMessageId = $mailerInstance->retrieveMessageIdFromMailroom($entityId);
            $generatedMessageId = $mailerInstance->generateMessageID();

            if (empty($inReplyToMessageId)) {
                $inReplyToMessageId = $generatedMessageId;
            }

            //Set messageId header for every sending email
            if (!empty($generatedMessageId)) {
                $mailerInstance->MessageID = $generatedMessageId;

            }

            //If variable is not empty then add custom header
            if (!empty($inReplyToMessageId)) {
                $mailerInstance->AddCustomHeader("In-Reply-To", $inReplyToMessageId);
            }

            $this->addCCAddress($mailerInstance, $cc);
            $this->addCCAddress($mailerInstance, $bcc,true);
            $mailerInstance->From = $from_email;
            $mailerInstance->FromName = decode_html($from_name);
            $mailerInstance->AddReplyTo($replyTo);
            $mailerInstance->Subject = strip_tags(decode_html($subject));
            $mailerInstance->Body = decode_emptyspace_html($content);
            $mailerInstance->Body = Emails_Mailer_Model::convertCssToInline($mailerInstance->Body);
            $mailerInstance->Body = Emails_Mailer_Model::makeImageURLValid($mailerInstance->Body);
            $emailRecord = Emails_Record_Model::getInstanceById($emailId);
            $mailerInstance->Body = $emailRecord->convertUrlsToTrackUrls($mailerInstance->Body,$entityId);
            $plainBody = decode_html($content);
            $plainBody = preg_replace(array("/<p>/i","/<br>/i","/<br \/>/i"),array("\n","\n","\n"),$plainBody);
            $plainBody = strip_tags($plainBody);
            $plainBody = Emails_Mailer_Model::convertToAscii($plainBody);
            $plainBody = $emailRecord->convertUrlsToTrackUrls($plainBody,$entityId,'plain');
            $mailerInstance->AltBody = $plainBody;

            //Block to get file details if comment is having attachment
            if(!empty($relatedInfo) && $relatedInfo['module'] == 'ModComments'){
                $modcommentsRecordId = $relatedInfo['id'];
                $modcommentsRecordModel = ModComments_Record_Model::getInstanceById($modcommentsRecordId);
                $modcommentsRecordModel->set('id',$modcommentsRecordId);
                $fileDetails = $modcommentsRecordModel->getFileDetails();
                //If no attachment details are found
                $path = '';

                //There can be multiple attachments for a single comment
                foreach($fileDetails as $fileDetail){
                    if(!empty($fileDetail)){
                        $filename = decode_html($fileDetail['name']);
                        $path = $fileDetail['path'].$fileDetail['attachmentsid'].'_'.$filename;
                        $mailerInstance->AddAttachment($path, $filename);
                    }
                }
            }

            $sql = "select vtiger_attachments.* from vtiger_attachments inner join vtiger_seattachmentsrel on vtiger_attachments.attachmentsid = vtiger_seattachmentsrel.attachmentsid inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_attachments.attachmentsid where vtiger_crmentity.deleted=0 and vtiger_seattachmentsrel.crmid=?";
            $res = $adb->pquery($sql, array($emailId));
            $count = $adb->num_rows($res);

            for($i=0;$i<$count;$i++){
                $fileid = $adb->query_result($res,$i,'attachmentsid');
                $filename = decode_html($adb->query_result($res,$i,'name'));
                $filepath = $adb->query_result($res,$i,'path');
                $filewithpath = $filepath.$fileid."_".$filename;

                if(is_file($filewithpath)) {
                    $mailerInstance->AddAttachment($filewithpath, $filename);
                }
            }

            $mailerInstance->send(true);

            $error = $mailerInstance->getError();
            if(!empty($emailId)) {
                $emailFocus->setEmailAccessCountValue($emailId);
            }
            if($path){
                if(!empty($fileDetails) && is_array($fileDetails)){
                    foreach($fileDetails as $fileDetail){
                        $modcommentsRecordModel->uploadAndSaveFile($emailId,$fileDetail['attachmentsid']);
                    }
                }
            }
            if($error) {
                //If mail is not sent then removing the details about email
                $emailFocus->trash($moduleName, $emailId);
            } else {
                //If mail sending is success store message Id for given crmId
                if($generatedMessageId && $entityId){
                    $mailerInstance->updateMessageIdByCrmId($generatedMessageId,$entityId);
                }
            }
        }
        $util->revertUser();
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
        
        return $fieldvalue;
    }
    
    public function getLanguages() {
        global $current_language;

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
}