<?php

/* * *******************************************************************************
 * The content of this file is subject to the PDF Maker license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */

class PDFMaker_Edit_View extends Vtiger_Index_View {

    public $cu_language = ""; 
    private $ModuleFields = array();
    private $All_Related_Modules = array();
    
    public function preProcess(Vtiger_Request $request, $display = true) {
        parent::preProcess($request, false);
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $viewer->assign('QUALIFIED_MODULE', $moduleName);

        $moduleName = $request->getModule();
        if (!empty($moduleName)) {
            $moduleModel = new PDFMaker_PDFMaker_Model('PDFMaker');
            $currentUser = Users_Record_Model::getCurrentUserModel();
            $userPrivilegesModel = Users_Privileges_Model::getInstanceById($currentUser->getId());
            $permission = $userPrivilegesModel->hasModulePermission($moduleModel->getId());
            $viewer->assign('MODULE', $moduleName);

            if (!$permission) {
                $viewer->assign('MESSAGE', 'LBL_PERMISSION_DENIED');
                $viewer->view('OperationNotPermitted.tpl', $moduleName);
                exit;
            }

            $linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'));
            $linkModels = $moduleModel->getSideBarLinks($linkParams);

            $viewer->assign('QUICK_LINKS', $linkModels);
        }

        $viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
        $viewer->assign('CURRENT_VIEW', $request->get('view'));
        if ($display) {
            $this->preProcessDisplay($request);
        }
    }

    function preProcessTplName(Vtiger_Request $request) {
        return 'EditViewPreProcess.tpl';
    }

    public function process(Vtiger_Request $request) {
        global $theme, $image_path;

        PDFMaker_Debugger_Model::GetInstance()->Init();

        $PDFMaker = new PDFMaker_PDFMaker_Model();

        $PDFMakerModel = Vtiger_Module_Model::getInstance('PDFMaker');

        $viewer = $this->getViewer($request);
  
        $cu_model = Users_Record_Model::getCurrentUserModel();
        $mode = "";
        $is_block = false;

        if ($request->has("mode") && !$request->isEmpty("mode")) {
            $mode = $request->get("mode");
        }
        if ($request->has('templateid') && !$request->isEmpty('templateid')) {
            $templateid = $request->get('templateid');
            $pdftemplateResult = $PDFMakerModel->GetEditViewData($templateid);
            $recordModel = PDFMaker_Record_Model::getInstanceById($templateid);

            $viewer->assign("PDF_TEMPLATE_RESULT", $pdftemplateResult);
            
            $select_module = $pdftemplateResult["module"];
            $select_format = $pdftemplateResult["format"];
            $select_orientation = $pdftemplateResult["orientation"];
            $nameOfFile = $pdftemplateResult["file_name"];
            $PDF_password = $pdftemplateResult["pdf_password"];



            $is_portal = $pdftemplateResult["is_portal"];
            $is_listview = $pdftemplateResult["is_listview"];
            $is_active = $pdftemplateResult["is_active"];
            $is_default = $pdftemplateResult["is_default"];
            $order = $pdftemplateResult["order"];
            $owner = $pdftemplateResult["owner"];
            $sharingtype = $pdftemplateResult["sharingtype"];
            $sharingMemberArray = $PDFMakerModel->GetSharingMemberEditArray($templateid);
            $disp_header = $pdftemplateResult["disp_header"];
            $disp_footer = $pdftemplateResult["disp_footer"];

            if ($pdftemplateResult["type"] != "") {
                $is_block = true;

                if ($pdftemplateResult["type"]) {
                    $viewer->assign("TEMPLATEBLOCKTYPE",$pdftemplateResult["type"]);
                    $blocktype = vtranslate(ucfirst($pdftemplateResult["type"]),"PDFMaker");
                    $viewer->assign("TEMPLATEBLOCKTYPEVAL",$blocktype);
                }
            }

            if(!$pdftemplateResult["permissions"]["edit"])
                $PDFMakerModel->DieDuePermission();

            if (vtlib_isModuleActive("ITS4YouStyles")){
                $ITS4YouStylesModuleModel = new ITS4YouStyles_Module_Model();
                $Style_Files = $ITS4YouStylesModuleModel->getStyleFiles($templateid,"PDFMaker");
                $viewer->assign("ITS4YOUSTYLE_FILES",$Style_Files);

                $Style_Content = $ITS4YouStylesModuleModel->getStyleContent($templateid,"PDFMaker","ASC");
                $viewer->assign("STYLES_CONTENT",$Style_Content);
            }

        } else {

            $recordModel = PDFMaker_Record_Model::getCleanInstance("PDFMaker");

            if ($mode == "Blocks") $is_block = true;

            $select_module = $templateid = $nameOfFile = "";

            if ($request->has("return_module") && !$request->isEmpty("return_module"))
                $select_module = $request->get("return_module");

            $select_format = "A4";
            $select_orientation = "portrait";
            $is_default = $is_portal = $is_listview = "0";
            $is_active = $order = "1";
            $owner = $cu_model->getId();
            
            if (getTabId('ITS4YouMultiCompany') && vtlib_isModuleActive('ITS4YouMultiCompany')) {
                $Company_Data = ITS4YouMultiCompany_Record_Model::getCompanyByUserId($cu_model->getId());
                if ($Company_Data != null) {
                    $sharingtype = "share";
                    $companyid = $Company_Data->getId();
                    $sharingMemberArray["Companies"] = array("Companies:".$companyid => $companyid);
                } else {
                    $sharingtype = "private";
                }
            } else {
                $sharingtype = "public";
                $sharingMemberArray = array();
            }

            $disp_header = "3";
            $disp_footer = "7";

            $PDFMakerModel->CheckTemplatePermissions($select_module, $templateid);
        }

        if (!$is_block) {

            $Block_Types = array();

            foreach (array("header","footer") AS $block_type ) {
                $selectedid = "";
                if (isset($pdftemplateResult[$block_type."id"]) && !empty($pdftemplateResult[$block_type."id"])) $selectedid = $pdftemplateResult[$block_type."id"];

                $BRequest = new Vtiger_Request(array('mode' => 'Blocks','blocktype' => $block_type, 'select_module' => $select_module));
                $BlockList = $PDFMakerModel->GetListviewData("templateid","ASC", $BRequest);

                $Block_Types[$block_type] = array("name"=>vtranslate(ucfirst($block_type),"PDFMaker"),"selected" => ($selectedid!=""?"fromlist":"custom"), "selectedid" => $selectedid, "types"=> array("custom"=>vtranslate("Custom","PDFMaker"),"fromlist"=>vtranslate("From list","PDFMaker")),"list"=>$BlockList);
            }

            $viewer->assign("BLOCK_TYPES", $Block_Types);
        }

        $viewer->assign("IS_BLOCK", $is_block);

        if ($PDFMakerModel->GetVersionType() == "professional")
            $type = "professional";
        else
            $type = "basic";
        $viewer->assign("TYPE", $type);

        if ($request->has("isDuplicate") && $request->get("isDuplicate") == "true") {
            $viewer->assign("FILENAME", "");
            $viewer->assign("DUPLICATE_FILENAME", $pdftemplateResult["filename"]);
        }
        else
            $viewer->assign("FILENAME", $pdftemplateResult["filename"]);

        $viewer->assign("DESCRIPTION", $pdftemplateResult["description"]);

        if (!$request->has("isDuplicate") OR ($request->has("isDuplicate") && $request->get("isDuplicate") != "true"))
            $viewer->assign("SAVETEMPLATEID", $templateid);
        if ($templateid != "")
            $viewer->assign("EMODE", "edit");

        $viewer->assign("TEMPLATEID", $templateid);
        $viewer->assign("MODULENAME", vtranslate($select_module,$select_module));
        $viewer->assign("SELECTMODULE", $select_module);
        $viewer->assign("BODY", $pdftemplateResult["body"]);
       
        $this->cu_language = $cu_model->get('language');
        
        $viewer->assign("THEME", $theme);
        $viewer->assign("IMAGE_PATH", $image_path);
        $app_strings_big = Vtiger_Language_Handler::getModuleStringsFromFile($this->cu_language);
        $app_strings = $app_strings_big['languageStrings'];
        $viewer->assign("APP", $app_strings);
        $viewer->assign("PARENTTAB", getParentTab());

        $modArr = $PDFMaker->GetAllModules();
        $Modulenames = $modArr[0];
        $ModuleIDS = $modArr[1];
        
        $viewer->assign("MODULENAMES", $Modulenames);

// ******************************************   Company and User information: **********************************
        $CUI_BLOCKS["Assigned"] = vtranslate("LBL_USER_INFO",'PDFMaker');
        $CUI_BLOCKS["Logged"] = vtranslate("LBL_LOGGED_USER_INFO",'PDFMaker');
        $CUI_BLOCKS["Modifiedby"] = vtranslate("LBL_MODIFIEDBY_USER_INFO",'PDFMaker');
        $CUI_BLOCKS["Creator"] = vtranslate("LBL_CREATOR_USER_INFO",'PDFMaker');
        $viewer->assign("CUI_BLOCKS", $CUI_BLOCKS);

        $adb = PearDatabase::getInstance();
        $sql = "SELECT * FROM vtiger_organizationdetails";
        $result = $adb->pquery($sql, array());

        $organization_logoname = decode_html($adb->query_result($result, 0, 'logoname'));
        $organization_header = decode_html($adb->query_result($result, 0, 'headername'));
        $organization_stamp_signature = $adb->query_result($result, 0, 'stamp_signature');

        global $site_URL;
        $path = $site_URL . "/test/logo/";

        if (isset($organization_stamp_signature)) {
            $organization_stamp_signature_img = "<img src=\"" . $path . $organization_stamp_signature . "\">";
            $viewer->assign("COMPANY_STAMP_SIGNATURE", $organization_stamp_signature_img);
        }
        if (isset($organization_header)) {
            $organization_header_img = "<img src=\"" . $path . $organization_header . "\">";
            $viewer->assign("COMPANY_HEADER_SIGNATURE", $organization_header_img);
        }

        if (getTabId('ITS4YouMultiCompany') && vtlib_isModuleActive('ITS4YouMultiCompany')) {
            $ismulticompany = true;

            $PDFMakerFieldsModel = new PDFMaker_Fields_Model();
            $Acc_Info = $PDFMakerFieldsModel->getSelectModuleFields("ITS4YouMultiCompany","COMPANY");
        } else {
            $ismulticompany = false;

            $Settings_Vtiger_CompanyDetails_Model = Settings_Vtiger_CompanyDetails_Model::getInstance();
            $CompanyDetails_Fields = $Settings_Vtiger_CompanyDetails_Model->getFields();

            foreach ($CompanyDetails_Fields AS $field_name => $field_type){
                if ($field_name == "organizationname"){
                    $field_name = "name";
                } elseif ($field_name == "code"){
                    $field_name = "zip";
                } elseif ($field_name == "logoname"){
                    continue;
                }

                $l = "LBL_COMPANY_".strtoupper($field_name);
                $label = vtranslate($l,'PDFMaker');
                if ($label == "" || $l == $label) $label = vtranslate($field_name);

                $Acc_Info["COMPANY_".strtoupper($field_name)] = $label;
            }
        }
        $viewer->assign("ACCOUNTINFORMATIONS", $Acc_Info);

        $organization_logo_img = '';

        if ($ismulticompany) {
            $organization_logo_img = '$COMPANY_LOGO$';
        } elseif (isset($organization_logoname)) {
            $organization_logo_img = "<img src=\"" . $path . $organization_logoname . "\">";
        }
        $viewer->assign("COMPANYLOGO", $organization_logo_img);

        $sql_user_block = "SELECT blockid, blocklabel FROM vtiger_blocks WHERE tabid = ? ORDER BY sequence ASC";
        $res_user_block = $adb->pquery($sql_user_block,array('29'));
        $user_block_info_arr = array();
        while ($row_user_block = $adb->fetch_array($res_user_block)) {
            $sql_user_field = "SELECT fieldid, uitype FROM vtiger_field WHERE block = ? and (displaytype != ? OR uitype = ?) ORDER BY sequence ASC";
            $res_user_field = $adb->pquery($sql_user_field,array($row_user_block['blockid'],'3','55'));
            $num_user_field = $adb->num_rows($res_user_field);

            if ($num_user_field > 0) {
                $user_field_id_array = array();

                while ($row_user_field = $adb->fetch_array($res_user_field)) {
                    $user_field_id_array[] = $row_user_field['fieldid'];
                }

                $user_block_info_arr[$row_user_block['blocklabel']] = $user_field_id_array;
            }
        }

        $user_mod_strings = $this->getModuleLanguageArray("Users"); 

        $b = 0;

        $User_Types = array("a" => "", "l" => "R_", "m" => "M_", "c" => "C_");        
        
        foreach ($user_block_info_arr AS $block_label => $block_fields) {
            $b++;

            if (isset($user_mod_strings[$block_label]) AND $user_mod_strings[$block_label] != "")
                $optgroup_value = $user_mod_strings[$block_label];
            else
                $optgroup_value = vtranslate($block_label,'PDFMaker');

            if (count($block_fields) > 0) {
                $sql1 = "SELECT * FROM vtiger_field WHERE fieldid IN (" . generateQuestionMarks($block_fields) . ") AND presence != '1'";
                $result1 = $adb->pquery($sql1, $block_fields);

                while ($row1 = $adb->fetchByAssoc($result1)) {
                    $fieldname = $row1['fieldname'];
                    $fieldlabel = $row1['fieldlabel'];

                    $option_key = strtoupper("Users" . "_" . $fieldname);

                    if (isset($current_mod_strings[$fieldlabel]) AND $current_mod_strings[$fieldlabel] != "")
                        $option_value = $current_mod_strings[$fieldlabel];
                    elseif (isset($app_strings[$fieldlabel]) AND $app_strings[$fieldlabel] != "")
                        $option_value = $app_strings[$fieldlabel];
                    else
                        $option_value = $fieldlabel;

                    foreach ($User_Types AS $user_type => $user_prefix) {
                        if ($fieldname == 'currency_id') {

                            $User_Info[$user_type][$optgroup_value][$user_prefix.$option_key] = vtranslate('LBL_CURRENCY_ID', 'PDFMaker');
                            $User_Info[$user_type][$optgroup_value][$user_prefix."USERS_CURRENCY_NAME"] = $option_value;
                            $User_Info[$user_type][$optgroup_value][$user_prefix."USERS_CURRENCY_CODE"] = vtranslate('LBL_CURRENCY_CODE', 'PDFMaker');
                            $User_Info[$user_type][$optgroup_value][$user_prefix."USERS_CURRENCY_SYMBOL"] = vtranslate('LBL_CURRENCY_SYMBOL', 'PDFMaker');
                        } else {
                            $User_Info[$user_type][$optgroup_value][$user_prefix.$option_key] = $option_value;
                        } 
                    }
                }
            }

            //variable RECORD ID added
            if ($b == 1) {
                $option_value = "Record ID";
                $option_key = strtoupper("USERS_CRMID");
                foreach ($User_Types AS $user_type => $user_prefix) {
                    $User_Info[$user_type][$user_prefix.$optgroup_value][$option_key] = $option_value;
                }
            }
            //end
        }

// ****************************************** END: Company and User information **********************************

        $viewer->assign("USERINFORMATIONS", $User_Info);

        $Invterandcon = array("" => vtranslate("LBL_PLS_SELECT",'PDFMaker'),
            "TERMS_AND_CONDITIONS" => vtranslate("LBL_TERMS_AND_CONDITIONS",'PDFMaker'));

        $viewer->assign("INVENTORYTERMSANDCONDITIONS", $Invterandcon);

//custom functions
        $customFunctions = $this->getCustomFunctionsList();
        $viewer->assign("CUSTOM_FUNCTIONS", $customFunctions);
//labels
        
        $global_lang_labels = @array_flip($app_strings);
        $global_lang_labels = @array_flip($global_lang_labels);
        asort($global_lang_labels);
        $viewer->assign("GLOBAL_LANG_LABELS", $global_lang_labels);

        $module_lang_labels = array();
        if ($select_module != "") {
            $mod_lang = $this->getModuleLanguageArray($select_module);
            $module_lang_labels = @array_flip($mod_lang);
            $module_lang_labels = @array_flip($module_lang_labels);
            asort($module_lang_labels);
        }
        else
            $module_lang_labels[""] = vtranslate("LBL_SELECT_MODULE_FIELD",'PDFMaker');

        
        $viewer->assign("MODULE_LANG_LABELS", $module_lang_labels);

        list($custom_labels, $languages) = $PDFMaker->GetCustomLabels();
        $currLangId = "";
        foreach ($languages as $langId => $langVal) {
            if ($langVal["prefix"] == $current_language) {
                $currLangId = $langId;
                break;
            }
        }

        $vcustom_labels = array();
        if (count($custom_labels) > 0) {

            foreach ($custom_labels as $oLbl) {
                $currLangVal = $oLbl->GetLangValue($currLangId);
                if ($currLangVal == "")
                    $currLangVal = $oLbl->GetFirstNonEmptyValue();

                $vcustom_labels[$oLbl->GetKey()] = $currLangVal;
            }
            asort($vcustom_labels);
        } else {
            $vcustom_labels = vtranslate("LBL_SELECT_MODULE_FIELD",'PDFMaker');
        }
        $viewer->assign("CUSTOM_LANG_LABELS", $vcustom_labels);

        $Header_Footer_Strings = array("" => vtranslate("LBL_PLS_SELECT",'PDFMaker'),
            "PAGE" => $app_strings["Page"],
            "PAGES" => $app_strings["Pages"],
        );

        $viewer->assign("HEADER_FOOTER_STRINGS", $Header_Footer_Strings);

//PDF FORMAT SETTINGS

        $Formats = array("A3" => "A3",
            "A4" => "A4",
            "A5" => "A5",
            "A6" => "A6",
            "Letter" => "Letter",
            "Legal" => "Legal",
            "Custom" => "Custom");     // ITS4YOU VlZa

        $viewer->assign("FORMATS", $Formats);
        if (strpos($select_format, ";") > 0) {
            $tmpArr = explode(";", $select_format);

            $select_format = "Custom";
            $custom_format["width"] = $tmpArr[0];
            $custom_format["height"] = $tmpArr[1];
            $viewer->assign("CUSTOM_FORMAT", $custom_format);
        }

        $viewer->assign("SELECT_FORMAT", $select_format);

//PDF ORIENTATION SETTINGS

        $Orientations = array("portrait" => vtranslate("portrait",'PDFMaker'),
            "landscape" => vtranslate("landscape",'PDFMaker'));

        $viewer->assign("ORIENTATIONS", $Orientations);

        $viewer->assign("SELECT_ORIENTATION", $select_orientation);

//PDF STATUS SETTINGS
        $Status = array("1" => $app_strings["Active"],
            "0" => vtranslate("Inactive",'PDFMaker'));
        $viewer->assign("STATUS", $Status);
        $viewer->assign("IS_ACTIVE", $is_active);
        if ($is_active == "0") {
            $viewer->assign("IS_DEFAULT_DV_CHECKED", 'disabled="disabled"');
            $viewer->assign("IS_DEFAULT_LV_CHECKED", 'disabled="disabled"');
        } elseif ($is_default > 0) {
            $is_default_bin = str_pad(base_convert($is_default, 10, 2), 2, "0", STR_PAD_LEFT);
            $is_default_lv = substr($is_default_bin, 0, 1);
            $is_default_dv = substr($is_default_bin, 1, 1);
            if ($is_default_lv == "1")
                $viewer->assign("IS_DEFAULT_LV_CHECKED", 'checked="checked"');
            if ($is_default_dv == "1")
                $viewer->assign("IS_DEFAULT_DV_CHECKED", 'checked="checked"');
        }

        $viewer->assign("ORDER", $order);

        if ($is_portal == "1")
            $viewer->assign("IS_PORTAL_CHECKED", 'checked="checked"');

        if ($is_listview == "1")
            $viewer->assign("IS_LISTVIEW_CHECKED", 'yes');

//PDF MARGIN SETTINGS
        if ($request->has("templateid") && !$request->isEmpty("templateid")) {
            $Margins = array("top" => $pdftemplateResult["margin_top"],
                "bottom" => $pdftemplateResult["margin_bottom"],
                "left" => $pdftemplateResult["margin_left"],
                "right" => $pdftemplateResult["margin_right"]);

            $Decimals = array("point" => $pdftemplateResult["decimal_point"],
                "decimals" => $pdftemplateResult["decimals"],
                "thousands" => ($pdftemplateResult["thousands_separator"] != "sp" ? $pdftemplateResult["thousands_separator"] : " ")
            );
        } else {
            $Margins = array("top" => "2", "bottom" => "2", "left" => "2", "right" => "2");
            $Decimals = array("point" => ",", "decimals" => "2", "thousands" => " ");
        }
        $viewer->assign("MARGINS", $Margins);
        $viewer->assign("DECIMALS", $Decimals);

//PDF HEADER / FOOTER
        $header = "";
        $footer = "";
        if ($request->has("templateid") && !$request->isEmpty("templateid")) {
            $header = $pdftemplateResult["header"];
            $footer = $pdftemplateResult["footer"];
        }
        $viewer->assign("HEADER", $header);
        $viewer->assign("FOOTER", $footer);

        $hfVariables = array("##PAGE##" => vtranslate("LBL_CURRENT_PAGE",'PDFMaker'),
            "##PAGES##" => vtranslate("LBL_ALL_PAGES",'PDFMaker'),
            "##PAGE##/##PAGES##" => vtranslate("LBL_PAGE_PAGES",'PDFMaker'));

        $viewer->assign("HEAD_FOOT_VARS", $hfVariables);

        $dateVariables = array("##DD.MM.YYYY##" => vtranslate("LBL_DATE_DD.MM.YYYY",'PDFMaker'),
            "##DD-MM-YYYY##" => vtranslate("LBL_DATE_DD-MM-YYYY",'PDFMaker'),
            "##MM-DD-YYYY##" => vtranslate("LBL_DATE_MM-DD-YYYY",'PDFMaker'),
            "##YYYY-MM-DD##" => vtranslate("LBL_DATE_YYYY-MM-DD",'PDFMaker'));

        $viewer->assign("DATE_VARS", $dateVariables);

//PDF FILENAME FIELDS

        $PDFMakerFieldsModel = new PDFMaker_Fields_Model();
        $filenameFields = $PDFMakerFieldsModel->getFilenameFields();
        $viewer->assign("FILENAME_FIELDS", $filenameFields);
        $viewer->assign("NAME_OF_FILE", $nameOfFile);
        $viewer->assign("PDF_PASSWORD", $PDF_password);
//Sharing
        $template_owners = get_user_array(false);
        $viewer->assign("TEMPLATE_OWNERS", $template_owners);
        $viewer->assign("TEMPLATE_OWNER", $owner);

        $sharing_types = Array("public" => vtranslate("PUBLIC_FILTER",'PDFMaker'),
            "private" => vtranslate("PRIVATE_FILTER",'PDFMaker'),
            "share" => vtranslate("SHARE_FILTER",'PDFMaker'));
        $viewer->assign("SHARINGTYPES", $sharing_types);
        $viewer->assign("SHARINGTYPE", $sharingtype);

        $cmod = $this->getModuleLanguageArray("Settings");
        //$cmod = return_specified_module_language($current_language, "Settings");
        $viewer->assign("CMOD", $cmod);
        //Constructing the Role Array


        if (getTabId('ITS4YouMultiCompany') && vtlib_isModuleActive('ITS4YouMultiCompany')) {
            $Members_Groups = Settings_ITS4YouMultiCompany_Member_Model::getAll();
        } else {
            $Members_Groups = Settings_Groups_Member_Model::getAll();
        }

        $viewer->assign('SELECTED_MEMBERS_GROUP', $sharingMemberArray);
        $viewer->assign('MEMBER_GROUPS', $Members_Groups);

//Ignored picklist values
        $pvsql = "SELECT value FROM vtiger_pdfmaker_ignorepicklistvalues";
        $pvresult = $adb->pquery($pvsql,array());
        $pvvalues = "";
        while ($pvrow = $adb->fetchByAssoc($pvresult))
            $pvvalues.=$pvrow["value"] . ", ";
        $viewer->assign("IGNORE_PICKLIST_VALUES", rtrim($pvvalues, ", "));

        

//formatable VATBLOCK content
        $vatblock_table = '<table border="1" cellpadding="3" cellspacing="0" style="border-collapse:collapse;">
                		<tr>
                            <td>' . $app_strings["Name"] . '</td>
                            <td>' . vtranslate("LBL_VATBLOCK_VAT_PERCENT",'PDFMaker') . '</td>
                            <td>' . vtranslate("LBL_VATBLOCK_SUM",'PDFMaker') . '</td>
                            <td>' . vtranslate("LBL_VATBLOCK_VAT_VALUE",'PDFMaker') . '</td>
                        </tr>
                		<tr>
                            <td colspan="4">#VATBLOCK_START#</td>
                        </tr>
                		<tr>
                			<td>$VATBLOCK_LABEL$</td>
                			<td>$VATBLOCK_VALUE$</td>
                			<td>$VATBLOCK_NETTO$</td>
                			<td>$VATBLOCK_VAT$</td>
                		</tr>
                		<tr>
                            <td colspan="4">#VATBLOCK_END#</td>
                        </tr>
                    </table>';

        $vatblock_table = str_replace(array("\r\n", "\r", "\n", "\t"), "", $vatblock_table);
        //$vatblock_table = ereg_replace(" {2,}", ' ', $vatblock_table); //preg_replace
        $viewer->assign("VATBLOCK_TABLE", $vatblock_table);

        

        $tacModules = array();
        $tac4you = is_numeric(getTabId("Tac4you"));
        if ($tac4you == true) {
            $sql = "SELECT tac4you_module FROM vtiger_tac4you_module WHERE presence = ?";
            $result = $adb->pquery($sql,array('1'));
            while ($row = $adb->fetchByAssoc($result))
                $tacModules[$row["tac4you_module"]] = $row["tac4you_module"];
        }

        $desc4youModules = array();
        $desc4you = is_numeric(getTabId("Descriptions4you"));
        if ($desc4you == true) {
            $sql = "SELECT b.name FROM vtiger_links AS a INNER JOIN vtiger_tab AS b USING (tabid) WHERE linktype = ? AND linkurl = ?";
            $result = $adb->pquery($sql,array('DETAILVIEWWIDGET', 'block://ModDescriptions4you:modules/Descriptions4you/ModDescriptions4you.php'));
            while ($row = $adb->fetchByAssoc($result))
                $desc4youModules[$row["name"]] = $row["name"];
        }

//Product block fields start
// Product bloc templates
        $sql = "SELECT * FROM vtiger_pdfmaker_productbloc_tpl";
        $result = $adb->pquery($sql,array());
        $Productbloc_tpl[""] = vtranslate("LBL_PLS_SELECT",'PDFMaker');
        while ($row = $adb->fetchByAssoc($result)) {
            $Productbloc_tpl[$row["body"]] = $row["name"];
        }
        $viewer->assign("PRODUCT_BLOC_TPL", $Productbloc_tpl);

        $ProductBlockFields = $PDFMaker->GetProductBlockFields($select_module);
        foreach ($ProductBlockFields as $viewer_key => $pbFields) {
            $viewer->assign($viewer_key, $pbFields);
        }
//Product block fields end
//Related block postprocessing

        $Related_Blocks = $PDFMaker->GetRelatedBlocks($select_module);
        $viewer->assign("RELATED_BLOCKS", $Related_Blocks);
//Related blocks end

        if ($templateid != "" || $select_module != "") {
            
            $SelectModuleFields = $PDFMakerFieldsModel->getSelectModuleFields($select_module);
            $RelatedModules = $PDFMakerFieldsModel->getRelatedModules($select_module); 
            
            $viewer->assign("RELATED_MODULES", $RelatedModules);
            
            
            $viewer->assign("SELECT_MODULE_FIELD", $SelectModuleFields);
            $smf_filename = $SelectModuleFields;
            if ($select_module == "Invoice" || $select_module == "Quotes" || $select_module == "SalesOrder" || $select_module == "PurchaseOrder" || $select_module == "Issuecards" || $select_module == "Receiptcards" || $select_module == "Creditnote" || $select_module == "StornoInvoice")
                unset($smf_filename["Details"]);
            $viewer->assign("SELECT_MODULE_FIELD_FILENAME", $smf_filename);
        }

// header / footer display settings
        $disp_optionsArr = array("DH_FIRST", "DH_OTHER");
        $disp_header_bin = str_pad(base_convert($disp_header, 10, 2), 2, "0", STR_PAD_LEFT);
        for ($i = 0; $i < count($disp_optionsArr); $i++) {
            if (substr($disp_header_bin, $i, 1) == "1")
                $viewer->assign($disp_optionsArr[$i], 'checked="checked"');
        }
        if ($disp_header == "3")
            $viewer->assign("DH_ALL", 'checked="checked"');

        $disp_optionsArr = array("DF_FIRST", "DF_LAST", "DF_OTHER");
        $disp_footer_bin = str_pad(base_convert($disp_footer, 10, 2), 3, "0", STR_PAD_LEFT);
        for ($i = 0; $i < count($disp_optionsArr); $i++) {
            if (substr($disp_footer_bin, $i, 1) == "1")
                $viewer->assign($disp_optionsArr[$i], 'checked="checked"');
        }
        if ($disp_footer == "7")
            $viewer->assign("DF_ALL", 'checked="checked"');

        $ListView_Block = array("" => vtranslate("LBL_PLS_SELECT",'PDFMaker'),
            "LISTVIEWBLOCK_START" => vtranslate("LBL_ARTICLE_START",'PDFMaker'),
            "LISTVIEWBLOCK_END" => vtranslate("LBL_ARTICLE_END",'PDFMaker'),
            "CRIDX" => vtranslate("LBL_COUNTER",'PDFMaker'),
        );

        $viewer->assign("LISTVIEW_BLOCK_TPL", $ListView_Block);

        $version_type = ucfirst($PDFMakerModel->GetVersionType());

        $viewer->assign("VERSION", $version_type . " " . PDFMaker_Version_Helper::$version);

        $category = getParentTab();
        $viewer->assign("CATEGORY", $category);

        if ($type == "professional" && $select_module != ""){
            $selectedModuleName = $select_module;
            $viewer->assign('SELECTED_MODULE_NAME', $selectedModuleName);
            $viewer->assign('SOURCE_MODULE', $selectedModuleName);
        }

        $viewer->assign('MAX_UPLOAD_LIMIT_MB', Vtiger_Util_Helper::getMaxUploadSize());
        $viewer->assign('MAX_UPLOAD_LIMIT_BYTES', Vtiger_Util_Helper::getMaxUploadSizeInBytes());

        $Watermark = $recordModel->getWatemarkData();
        $viewer->assign('WATERMARK', $Watermark);


        $fontawesomeclass = file_get_contents('layouts/v7/modules/PDFMaker/resources/FontAwesome.css');
        $viewer->assign('FONTAWESOMECLASS', $fontawesomeclass);

        $viewer->assign('FONTAWESOMEICONS', PDFMaker_FontAwesome_Helper::getIcons());

        $viewer->view('Edit.tpl', 'PDFMaker');
    }

    /**
     * Function to get the list of Script models to be included
     * @param Vtiger_Request $request
     * @return <Array> - List of Vtiger_JsScript_Model instances
     */
    function getHeaderScripts(Vtiger_Request $request) {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();

        $jsFileNames = array(
            "modules.PDFMaker.resources.ckeditor.ckeditor",
            "libraries.jquery.ckeditor.adapters.jquery",
            "libraries.jquery.jquery_windowmsg",
            "modules.$moduleName.resources.AdvanceFilter"
        );

        if (vtlib_isModuleActive("ITS4YouStyles")){
            $jsFileNames[] = "modules.ITS4YouStyles.resources.CodeMirror.lib.codemirror";
            $jsFileNames[] = "modules.ITS4YouStyles.resources.CodeMirror.mode.javascript.javascript";
            $jsFileNames[] = "modules.ITS4YouStyles.resources.CodeMirror.addon.selection.active-line";
            $jsFileNames[] = "modules.ITS4YouStyles.resources.CodeMirror.addon.edit.matchbrackets";
            $jsFileNames[] = "modules.ITS4YouStyles.resources.CodeMirror.addon.runmode.runmode";
        }

        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }

    public function getHeaderCss(Vtiger_Request $request) {
        $headerCssInstances = parent::getHeaderCss($request);
        if (vtlib_isModuleActive("ITS4YouStyles")){
            $cssFileNames = array(
                '~/modules/ITS4YouStyles/resources/CodeMirror/lib/codemirror.css',
            );
            $cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
            $headerCssInstances = array_merge($headerCssInstances, $cssInstances);
        }
        return $headerCssInstances;
    }

    /**
     * Function to get array with module languages
     * @param module name
     * @return <Array> - Array of Module languages
     */
    function getModuleLanguageArray($module) {
    
        if (file_exists("languages/".$this->cu_language."/".$module.".php")) 
            $current_mod_strings_lang = $this->cu_language;
        else
            $current_mod_strings_lang = "en_us";

        $current_mod_strings_big = Vtiger_Language_Handler::getModuleStringsFromFile($current_mod_strings_lang,$module);
        return $current_mod_strings_big['languageStrings'];
    }
    
    /**
     * Function to get array with available custom functions 
     * @param none
     * @return <Array> - Array of Module languages
     */
    function getCustomFunctionsList() { 
        
        $ready = false;
        $function_name = "";
        $function_params = array();
        $functions = array();

        $files = glob('modules/PDFMaker/resources/functions/*.php');
        foreach ($files as $file) {
            $filename = $file;
            $source = fread(fopen($filename, "r"), filesize($filename));
            $tokens = token_get_all($source);
            foreach ($tokens as $token) {
                if (is_array($token)) {
                    if ($token[0] == T_FUNCTION)
                        $ready = true;
                    elseif ($ready) {
                        if ($token[0] == T_STRING && $function_name == "")
                            $function_name = $token[1];
                        elseif ($token[0] == T_VARIABLE)
                            $function_params[] = $token[1];
                    }
                }
                elseif ($ready && $token == "{") {
                    $ready = false;
                    $functions[$function_name] = $function_params;
                    $function_name = "";
                    $function_params = array();
                }
            }
        }

        $customFunctions[""] = vtranslate("LBL_PLS_SELECT",'PDFMaker');
        foreach ($functions as $funName => $params) {
            $parString = implode("|", $params);
            $custFun = trim($funName . "|" . str_replace("$", "", $parString), "|");
            $customFunctions[$custFun] = $funName;
        }
        
        return $customFunctions;                
    }
}