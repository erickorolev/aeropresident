<?php

/* * *******************************************************************************
 * The content of this file is subject to the PDF Maker license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */

class PDFMaker_SavePDFTemplate_Action extends Vtiger_Action_Controller {

    public function checkPermission(Vtiger_Request $request) {
    }

    public function process(Vtiger_Request $request) {

        PDFMaker_Debugger_Model::GetInstance()->Init();
        $adb = PearDatabase::getInstance();
        $adb->println("TRANS save pdfmaker starts");
        $adb->startTransaction();

        $cu_model = Users_Record_Model::getCurrentUserModel();

        $watermark_text = $blocktype = $modulename = "";

        if ($request->has('blocktype') && !$request->isEmpty('blocktype')) {
            $blocktype = $request->get('blocktype');
        }

        $S_Data = $request->getAll();
        $filename = $request->get('filename');
        if ($request->has('modulename') && !$request->isEmpty('modulename')) {
            $modulename = $request->get('modulename');
        }

        $templateid = $request->get('templateid');
        $description = $request->get('description');
        $body = $S_Data['body'];
        
        $pdf_format = $request->get('pdf_format');
        $pdf_orientation = $request->get('pdf_orientation');
        $owner = $request->get('template_owner');
        $sharingtype = $request->get('sharing');
        $is_active = $request->get('is_active');
       
        $is_default_dv = $request->get('is_default_dv');
        $is_default_lv = $request->get('is_default_lv');
        $is_portal = $request->get('is_portal');
        $is_listview = $request->get('is_listview');
        
        if ($is_default_dv != "") $is_default_dv = "1"; else $is_default_dv = "0";
        if ($is_default_lv != "") $is_default_lv = "1"; else $is_default_lv = "0";
        if ($is_portal != "") $is_portal = "1"; else $is_portal = "0";
        if ($is_listview != "") $is_listview = "1"; else $is_listview = "0";
        
        $order = $request->get('tmpl_order');
        
        $dh_first = $request->get('dh_first');
        $dh_other = $request->get('dh_other');
        $df_first = $request->get('df_first');
        $df_last = $request->get('df_last');
        $df_other = $request->get('df_other');
        
        if ($dh_first != "") $dh_first = "1"; else $dh_first = "0";
        if ($dh_other != "") $dh_other = "1"; else $dh_other = "0";
        if ($df_first != "") $df_first = "1"; else $df_first = "0";
        if ($df_last != "") $df_last = "1"; else $df_last = "0";
        if ($df_other != "") $df_other = "1"; else $df_other = "0";

        if (isset($templateid) && $templateid != '') {
            $adb->pquery("UPDATE vtiger_pdfmaker SET filename =?, module =?, description =?, body =? WHERE templateid =?", array($filename, $modulename, $description, $body, $templateid));
            $adb->pquery("DELETE FROM vtiger_pdfmaker_settings WHERE templateid =?", array($templateid));
            $adb->pquery("DELETE FROM vtiger_pdfmaker_userstatus WHERE templateid=? AND userid=?", array($templateid, $cu_model->id));
        } else {
            $templateid = $adb->getUniqueID('vtiger_pdfmaker');
            $adb->pquery("insert into vtiger_pdfmaker (filename,module,description,body,deleted,templateid,type) values (?,?,?,?,?,?,?)", array($filename, $modulename, $description, $body, 0, $templateid, $blocktype));
        }

        $margin_top = $request->get('margin_top');
        if ($margin_top < 0) $margin_top = 0;

        $margin_bottom = $request->get('margin_bottom');
        if ($margin_bottom < 0) $margin_bottom = 0;    
            
        $margin_left = $request->get('margin_left');
        if ($margin_left < 0) $margin_left = 0; 
        
        $margin_right = $request->get('margin_right');
        if ($margin_right < 0) $margin_right = 0; 
        
 
        $dec_point = $request->get('dec_point');
        $dec_decimals = $request->get('dec_decimals');
        $dec_thousands = $request->get('dec_thousands');
        
        if ($dec_thousands == " ") $dec_thousands = "sp";

        $encoding = $request->get('encoding');
        if ($encoding == "") $encoding = "auto";

        $nameOfFile = $request->get('nameOfFile');
        $PDFPassword = $request->get('PDFPassword');

        $watermark_type = $request->get('watermark_type');
        $watermark_alpha = $request->get('watermark_alpha');

        if (empty($watermark_alpha) || !is_numeric($watermark_alpha)) {
            $watermark_alpha = NULL;
        }

        if ($watermark_type == "text") {
            $watermark_text = $request->get('watermark_text');
        }
        $watermark_img_id = "";
        if ($watermark_type == "image") {
            $watermark_img_id = $request->get('watermark_img_id');
        }

        foreach (array("header","footer") AS $block_type ) {
            $block_type_val = $request->get('blocktype'.$block_type.'_val');

            if ($block_type_val != "custom") {
                $S_Data[$block_type.'_body'] = '';
                $S_Data[$block_type.'_id'] = $S_Data['blocktype'.$block_type.'_list'];
            }
        }

        $Data = array('header_body','footer_body','header_id','footer_id');
        foreach ($Data AS $column ) {
            if (!isset($S_Data[$column])) $S_Data[$column] = '';
        }

        if ($pdf_format == 'Custom') {
            $pdf_cf_width = $request->get('pdf_format_width');
            $pdf_cf_height = $request->get('pdf_format_height');
            $pdf_format = $pdf_cf_width . ';' . $pdf_cf_height;
        }

        $disp_header = base_convert($dh_first . $dh_other, 2, 10);
        $disp_footer = base_convert($df_first . $df_last . $df_other, 2, 10);

        $sql4 = "INSERT INTO vtiger_pdfmaker_settings (templateid, margin_top, margin_bottom, margin_left, margin_right, format, orientation, 
                                               decimals, decimal_point, thousands_separator, header, footer, encoding, file_name, is_portal,
                                               is_listview, owner, sharingtype, disp_header, disp_footer, headerid, footerid, pdf_password, watermark_type, watermark_alpha, watermark_text, watermark_img_id)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $params4 = array($templateid, $margin_top, $margin_bottom, $margin_left, $margin_right, $pdf_format, $pdf_orientation,
            $dec_decimals, $dec_point, $dec_thousands, $S_Data['header_body'], $S_Data['footer_body'], $encoding, $nameOfFile, $is_portal,
            $is_listview, $owner, $sharingtype, $disp_header, $disp_footer, ($S_Data['header_id']==""?NULL:$S_Data['header_id']), ($S_Data['footer_id']==""?NULL:$S_Data['footer_id']),$PDFPassword, $watermark_type, $watermark_alpha, $watermark_text,$watermark_img_id);

        if ($is_portal == "1") {
            $adb->pquery("UPDATE vtiger_pdfmaker_settings INNER JOIN vtiger_pdfmaker USING(templateid) SET is_portal = '0' WHERE is_portal = '1' AND module=?", array($modulename));
        }


        $adb->pquery($sql4, $params4);
// ITS4YOU-END
//ignored picklist values
        $adb->pquery("DELETE FROM vtiger_pdfmaker_ignorepicklistvalues", array());
        
        $ignore_picklist_values =  $request->get('ignore_picklist_values');
        $pvvalues = explode(",", $ignore_picklist_values);
        foreach ($pvvalues as $value)
            $adb->pquery("INSERT INTO vtiger_pdfmaker_ignorepicklistvalues(value) VALUES(?)",array(trim($value)));
// end ignored picklist values
//unset the former default template because only one template can be default per user x module
        $is_default_bin = $is_default_lv . $is_default_dv;
        $is_default_dec = intval(base_convert($is_default_bin, 2, 10)); // convert binary format xy to decimal; where x stands for is_default_lv and y stands for is_default_dv
        if ($is_default_dec > 0) {
            $sql5 = "UPDATE vtiger_pdfmaker_userstatus
            INNER JOIN vtiger_pdfmaker USING(templateid)
            SET is_default=?
            WHERE is_default=? AND userid=? AND module=?";

            switch ($is_default_dec) {
//      in case of only is_default_dv is checked
                case 1:
                    $adb->pquery($sql5, array("0", "1", $cu_model->id, $modulename));
                    $adb->pquery($sql5, array("2", "3", $cu_model->id, $modulename));
                    break;
//      in case of only is_default_lv is checked
                case 2:
                    $adb->pquery($sql5, array("0", "2", $cu_model->id, $modulename));
                    $adb->pquery($sql5, array("1", "3", $cu_model->id, $modulename));
                    break;
//      in case of both is_default_* are checked
                case 3:
                    $sql5 = "UPDATE vtiger_pdfmaker_userstatus
                    INNER JOIN vtiger_pdfmaker USING(templateid)
                    SET is_default=?
                    WHERE is_default > ? AND userid=? AND module=?";
                    $adb->pquery($sql5, array("0", "0", $cu_model->id, $modulename));
            }
        }

        $adb->pquery("INSERT INTO vtiger_pdfmaker_userstatus(templateid, userid, is_active, is_default, sequence) VALUES(?,?,?,?,?)", array($templateid, $cu_model->id, $is_active, $is_default_dec, $order));

//SHARING
        $adb->pquery("DELETE FROM vtiger_pdfmaker_sharing WHERE templateid=?", array($templateid));

        $member_array = $request->get('members');

        if ($sharingtype == "share" && count($member_array) > 0) {
            $groupMemberArray = self::constructSharingMemberArray($member_array);

            $sql8a = "INSERT INTO vtiger_pdfmaker_sharing(templateid, shareid, setype) VALUES ";
            $sql8b = "";
            $params8 = array();
            foreach ($groupMemberArray as $setype => $shareIdArr) {
                foreach ($shareIdArr as $shareId) {
                    $sql8b .= "(?, ?, ?),";
                    $params8[] = $templateid;
                    $params8[] = $shareId;
                    $params8[] = $setype;
                }
            }

            if ($sql8b != "") {
                $sql8b = rtrim($sql8b, ",");
                $sql8 = $sql8a . $sql8b;
                $adb->pquery($sql8, $params8);
            }
        }

        if (!empty($modulename) && empty($blocktype)) {
            $PDFMaker = new PDFMaker_PDFMaker_Model();
            $PDFMaker->AddLinks($modulename);
        }

        $adb->completeTransaction();
	    $adb->println("TRANS save pdfmaker ends");

	    if (!empty($_FILES)) {
            $recordModel = PDFMaker_Record_Model::getInstanceById($templateid);
            foreach($_FILES as $fileindex => $files) {
                if($files['name'] != '' && $files['size'] > 0) {
                    $files['original_name'] = vtlib_purify($_REQUEST[$fileindex.'_hidden']);
                    $recordModel->uploadAndSaveFile($files,'Watermark');
                }
            }
        }

        $redirect = $request->get('redirect');
        if ($redirect == "false") {
            $redirect_url = "index.php?module=PDFMaker&view=Edit&parenttab=Tools&applied=true&templateid=".$templateid;
            
            $return_module = $request->get('return_module');
            $return_view = $request->get('return_view');
            
            if ($return_module != "") $redirect_url .= "&return_module=".$return_module;
            if ($return_view != "") $redirect_url .= "&return_view=".$return_view;
            
           header("Location:".$redirect_url);
        } else {
            header("Location:index.php?module=PDFMaker&view=Detail&parenttab=Tools&templateid=" . $templateid);
        }
    }

    private function constructSharingMemberArray($member_array) {

        $groupMemberArray = Array();

        foreach ($member_array as $member) {
            $memSubArray = explode(':', $member);
            $type = ($memSubArray[0] == "RoleAndSubordinates"?"rs":strtolower($memSubArray[0]));
            $groupMemberArray[$type][] = $memSubArray[1];
        }

        return $groupMemberArray;
    }

}