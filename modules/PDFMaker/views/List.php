<?php

/* * *******************************************************************************
 * The content of this file is subject to the PDF Maker license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */

class PDFMaker_List_View extends Vtiger_Index_View {

    protected $listViewLinks = false;
    protected $numVer = 0;
    protected $isInstalled = false;

    public function __construct() {
        parent::__construct();
        $this->checkIfIsInstalled();
        $this->exposeMethod('getList');
    }
    /**
     * function checks if module is installed and licensed
     * @return bool
     */
    private function checkIfIsInstalled () {
        $adb = PearDatabase::getInstance();
        $result = @$adb->pquery("SELECT version FROM vtiger_pdfmaker_version WHERE version = ?", array('7'));

        if ($result) {
            $this->numVer = $adb->num_rows($result);
        }
        if ($this->numVer > 0) {
            $num_templates = @$adb->query_result(@$adb->pquery("SELECT id FROM vtiger_pdfmaker_seq", array()), 0, "id");
            if ($num_templates > 0 && is_dir("modules/PDFMaker/resources/mpdf"))
                $this->isInstalled = true;
        }
    }

    public function preProcess(Vtiger_Request $request, $display = true) {
        parent::preProcess($request, false);

        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $viewer->assign('QUALIFIED_MODULE', $moduleName);
        $viewer = $this->getViewer($request);

        $moduleModel = new PDFMaker_PDFMaker_Model('PDFMaker');

        if (!empty($moduleName)) {
            //$moduleModel = PDFMaker_PDFMaker_Model::getInstance($moduleName);

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
        $viewer->assign('MODE', $request->get('mode'));

        if (!$this->isInstalled) {
            $viewer->assign('LEFTPANELHIDE', '1');
        }

        //$PDFMakerModel = Vtiger_Module_Model::getInstance('PDFMaker');
        //$version_type = $PDFMakerModel->GetVersionType();
        $version_type = $moduleModel->GetVersionType();
        $viewer->assign("VERSION_TYPE", $version_type);

        if ($display) {
            $this->preProcessDisplay($request);
        }
    }
    function preProcessTplName(Vtiger_Request $request) {
        return 'ListViewPreProcess.tpl';
    }

    public function postProcess(Vtiger_Request $request) {
        $viewer = $this->getViewer($request);
        $viewer->view('IndexPostProcess.tpl');

        parent::postProcess($request);
    }

    public function process(Vtiger_Request $request) {

        $viewer = $this->getViewer($request);
        $qualifiedModuleName = $request->getModule(false);
        $viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
        $viewer->assign("URL", vglobal("site_URL"));

        if ($this->isInstalled) {
            $this->invokeExposedMethod('getList', $request);
        } else {
            $company_details = Vtiger_CompanyDetails_Model::getInstanceById();
            $viewer->assign("COMPANY_DETAILS", $company_details);
            
            $mb_string_exists = function_exists("mb_get_info");
            if ($mb_string_exists === false) {
                $viewer->assign("MB_STRING_EXISTS", 'false');
            } else {
                $viewer->assign("MB_STRING_EXISTS", 'true');
            }
            
            if ($this->numVer > 0) {
                $current_step = 2;
                if (is_dir("modules/PDFMaker/resources/mpdf")) {
                    require_once ('include/utils/VtlibUtils.php');
                    $step = "3";
                    $total_steps = "2";
                } else {
                    $step = "2";
                    $total_steps = "3";
                }
            } else {
               
                $step = 1;
                $current_step = 1;
                
                if (!is_dir("modules/PDFMaker/resources/mpdf"))
                    $total_steps = 3;
                else
                    $total_steps = 2;
            }
            
            $viewer->assign("STEP", $step);
            $viewer->assign("CURRENT_STEP", $current_step);
            $viewer->assign("TOTAL_STEPS", $total_steps);
            $viewer->view('Install.tpl', 'PDFMaker');
        }
    }

    public function getList(Vtiger_Request $request) {

        PDFMaker_Debugger_Model::GetInstance()->Init();
        $current_user = Users_Record_Model::getCurrentUserModel();

        $l = new PDFMaker_License_Action();
        $l->controlLicense();

        $PDFMakerModel = Vtiger_Module_Model::getInstance('PDFMaker');

        if ($PDFMakerModel->CheckPermissions("DETAIL") == false)
            $PDFMakerModel->DieDuePermission();

        $viewer = $this->getViewer($request);
        $orderby = "templateid";
        $dir = "ASC";
        if ($request->has('sortorder') && $request->get('sortorder') == "DESC") {
            $dir = "DESC";
        }

        if ($request->has('orderby') && !$request->isEmpty('orderby')) {
            $orderby = $request->get('orderby');
            if ($orderby =="neme") $orderby = "filename";
        }

        $version_type = $PDFMakerModel->GetVersionType();
        $license_key = $PDFMakerModel->GetLicenseKey();

        $viewer->assign("VERSION_TYPE", $version_type);
        $viewer->assign("VERSION", ucfirst($version_type) . " " . PDFMaker_Version_Helper::$version);
        $viewer->assign("LICENSE_KEY", $license_key);

        if ($PDFMakerModel->CheckPermissions("EDIT")) {
            $viewer->assign("EXPORT", "yes");
        }

        if ($PDFMakerModel->CheckPermissions("EDIT") && $PDFMakerModel->GetVersionType() != "deactivate") {
            $viewer->assign("EDIT", "permitted");
            $viewer->assign("IMPORT", "yes");
        }

        if ($PDFMakerModel->CheckPermissions("DELETE") && $PDFMakerModel->GetVersionType() != "deactivate") {
            $viewer->assign("DELETE", "permitted");
        }

        $notif = $PDFMakerModel->GetReleasesNotif();
        $viewer->assign("RELEASE_NOTIF", $notif);

        $viewer->assign("PARENTTAB", getParentTab());
        $viewer->assign("ORDERBY", $orderby);
        $viewer->assign("DIR", $dir);

        $Search_Selectbox_Data = $PDFMakerModel->getSearchSelectboxData();
        $viewer->assign("SEARCHSELECTBOXDATA", $Search_Selectbox_Data);
        
        
        $return_data = $PDFMakerModel->GetListviewData($orderby, $dir, $request);
        $viewer->assign("PDFTEMPLATES", $return_data);
        $category = getParentTab();
        $viewer->assign("CATEGORY", $category);

        if ($current_user->isAdminUser()) {
            $viewer->assign('IS_ADMIN', '1');
        }

        $moduleName = $request->getModule();
        $linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'));
        $linkListViewModels = $PDFMakerModel->getListViewLinks($linkParams);

        $viewer->assign('LISTVIEW_MASSACTIONS', $linkListViewModels['LISTVIEWMASSACTION']);

        $viewer->assign('LISTVIEW_LINKS', $linkListViewModels);
       
        $tpl = "ListPDFTemplatesContents";

        $sharing_types = Array(""=>"",
            "public" => vtranslate("PUBLIC_FILTER",'PDFMaker'),
            "private" => vtranslate("PRIVATE_FILTER",'PDFMaker'),
            "share" => vtranslate("SHARE_FILTER",'PDFMaker'));
        $viewer->assign("SHARINGTYPES", $sharing_types);

        $block_types = Array(""=>"",
            "header" => vtranslate("Header",'PDFMaker'),
            "footer" => vtranslate("Footer",'PDFMaker'));
        $viewer->assign("BLOCKTYPES", $block_types);

        $Status = array(
            "status_1" => vtranslate("Active",'PDFMaker'),
            "status_0" => vtranslate("Inactive",'PDFMaker'));
        $viewer->assign("STATUSOPTIONS", $Status);
        
        $Search_Types = array("filename","module","description","sharingtype","owner","status");

        if ($request->has('search_params') && !$request->isEmpty('search_params')) {

            $searchParams = $request->get('search_params');

            foreach($searchParams as $groupInfo){
                if(empty($groupInfo)){
                    continue;
                }
                foreach($groupInfo as $fieldSearchInfo){
                    $fieldName = $st = $fieldSearchInfo[0];
                    $operator = $fieldSearchInfo[1];
                    $search_val = $fieldSearchInfo[2];
                    $viewer->assign("SEARCH_".strtoupper($st)."VAL", $search_val);
                    
                    $searchParams[$fieldName] = $fieldSearchInfo;
                }
            }
        } else {
            $searchParams = array();
        }
        $viewer->assign("MAIN_PRODUCT_SUPPORT", '');
        $viewer->assign("MAIN_PRODUCT_WHITELABEL", '');
        $viewer->assign("MODULE", 'PDFMaker');

        $viewer->assign('SEARCH_DETAILS', $searchParams);
        
        $viewer->view($tpl.".tpl", 'PDFMaker');
    }
    
    function getHeaderScripts(Vtiger_Request $request) {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $jsFileNames = array(
            "layouts.v7.modules.PDFMaker.resources.License",
            "layouts.v7.modules.PDFMaker.resources.List"
        );
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }   
}