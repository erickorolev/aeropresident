    <?php

/* * *******************************************************************************
 * The content of this file is subject to the PDF Maker license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */

class PDFMaker_Detail_View extends Vtiger_Index_View {

    function __construct(){
        parent::__construct();
        $this->exposeMethod('showRelatedList');
    }

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

        $recordId = $request->get('templateid');

        if(!$this->record){
            $this->record = PDFMaker_DetailView_Model::getInstance($moduleName, $recordId);
        }
        $recordModel = $this->record->getRecord();

        $viewer->assign('RECORD', $recordModel);
        $viewer->assign('MODULE_MODEL', $this->record->getModule());

        $detailViewLinkParams = array('MODULE'=>$moduleName,'RECORD'=>$recordId);
        $detailViewLinks = $this->record->getDetailViewLinks($detailViewLinkParams);
        $viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);

        if ($display) {
            $this->preProcessDisplay($request);
        }
    }
    
    public function process(Vtiger_Request $request) {
        PDFMaker_Debugger_Model::GetInstance()->Init();

        $PDFMakerModel = Vtiger_Module_Model::getInstance('PDFMaker');

        if ($PDFMakerModel->CheckPermissions("DETAIL") == false)
            $PDFMakerModel->DieDuePermission();

        $viewer = $this->getViewer($request);

        $is_block = false;

        if ($request->has('templateid') && !$request->isEmpty('templateid')) {
            $templateid = $request->get('templateid');
            $pdftemplateResult = $PDFMakerModel->GetDetailViewData($templateid);
            $recordModel = PDFMaker_Record_Model::getInstanceById($templateid);

            $viewer->assign("FILENAME", $pdftemplateResult["filename"]);
            $viewer->assign("DESCRIPTION", $pdftemplateResult["description"]);
            $viewer->assign("TEMPLATEID", $pdftemplateResult["templateid"]);
            $viewer->assign("MODULENAME", getTranslatedString($pdftemplateResult["module"]));

            if ($pdftemplateResult["type"] != "") {
                $is_block = true;

                if ($pdftemplateResult["type"]) {
                    $viewer->assign("TEMPLATEBLOCKTYPEINFO","LBL_".strtoupper($pdftemplateResult["type"])."_INFORMATIONS");
                }
            }

            $pdf_body = decode_html($pdftemplateResult["body"]);
            $pdf_header = decode_html($pdftemplateResult["header"]);
            $pdf_footer = decode_html($pdftemplateResult["footer"]);


            $PDFMakerModel->addAwesomeStyle($pdf_body,false);
            $PDFMakerModel->addAwesomeStyle($pdf_header,false);
            $PDFMakerModel->addAwesomeStyle($pdf_footer,false);

            if (vtlib_isModuleActive("ITS4YouStyles")){

                $viewer->assign("ISSTYLESACTIVE", "yes");
                $ITS4YouStylesModuleModel = new ITS4YouStyles_Module_Model();
                $ITS4YouStylesModuleModel->loadStyles($pdftemplateResult["templateid"],"PDFMaker");
                $pdf_body = $ITS4YouStylesModuleModel->addStyles($pdf_body);
                $pdf_header = $ITS4YouStylesModuleModel->addStyles($pdf_header);
                $pdf_footer = $ITS4YouStylesModuleModel->addStyles($pdf_footer);
                $Styles_List = $ITS4YouStylesModuleModel->getRelatedRecords($pdftemplateResult["templateid"],"PDFMaker","desc",true);
                $viewer->assign("STYLES_LIST", $Styles_List);
            }

            $viewer->assign("BODY", $pdf_body);
            $viewer->assign("HEADER", $pdf_header);
            $viewer->assign("FOOTER", $pdf_footer);
            $viewer->assign("IS_ACTIVE", $pdftemplateResult["is_active"]);
            $viewer->assign("IS_DEFAULT", $pdftemplateResult["is_default"]);
            $viewer->assign("ACTIVATE_BUTTON", $pdftemplateResult["activateButton"]);
            $viewer->assign("DEFAULT_BUTTON", $pdftemplateResult["defaultButton"]);


            if ($pdftemplateResult["permissions"]["edit"]) {
                $viewer->assign("EXPORT", "yes");
            }

            if ($PDFMakerModel->GetVersionType() != "deactivate") {
                if ($pdftemplateResult["permissions"]["edit"]) {
                    $viewer->assign("EDIT", "permitted");
                    $viewer->assign("IMPORT", "yes");
                }

                if ($pdftemplateResult["permissions"]["delete"]) {
                    $viewer->assign("DELETE", "permitted");
                }
            }

        } else {
            $recordModel = PDFMaker_Record_Model::getCleanInstance("PDFMaker");
        }

        $category = getParentTab();
        $viewer->assign("CATEGORY", $category);
        $viewer->assign('PDFMAKER_RECORD_MODEL', $recordModel);
        $version_type = $PDFMakerModel->GetVersionType();
        $viewer->assign("VERSION", $version_type . " " . PDFMaker_Version_Helper::$version);
        $viewer->assign("IS_BLOCK", $is_block);

        $Watermark = $recordModel->getWatemarkData();
        $viewer->assign('WATERMARK', $Watermark);

        $viewer->view('Detail.tpl', 'PDFMaker');
    }

    function preProcessTplName(Vtiger_Request $request) {
        return 'DetailViewPreProcess.tpl';
    }

    public function showRelatedList(Vtiger_Request $request){
        $related_module = $request->get("relatedModule");
        if ($related_module == "ITS4YouStyles") {
            $viewer = $this->getViewer($request);
            $ITS4YouStyles_Module_Model = new ITS4YouStyles_Module_Model();
            echo $ITS4YouStyles_Module_Model->showITS4YouStyles($request, $viewer);
        }
    }


    function getHeaderScripts(Vtiger_Request $request) {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();

        $jsFileNames = array(
            'modules.Vtiger.resources.Detail',
            'modules.Vtiger.resources.RelatedList',
            'modules.PDFMaker.resources.Detail'
        );
        if (vtlib_isModuleActive("ITS4YouStyles")){
             $jsFileNames[] = "modules.ITS4YouStyles.resources.CodeMirror.lib.codemirror";
             $jsFileNames[] = "modules.ITS4YouStyles.resources.CodeMirror.mode.javascript.javascript";
             $jsFileNames[] = "modules.ITS4YouStyles.resources.CodeMirror.addon.selection.active-line";
             $jsFileNames[] = "modules.ITS4YouStyles.resources.CodeMirror.addon.edit.matchbrackets";
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

}