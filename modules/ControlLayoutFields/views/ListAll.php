<?php

ini_set("display_errors", "0");
class ControlLayoutFields_ListAll_View extends Settings_Vtiger_Index_View
{
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod("listAll");
        $this->exposeMethod("delete");
    }
    public function preProcess(Vtiger_Request $request)
    {
        parent::preProcess($request);
        $adb = PearDatabase::getInstance();
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->assign("QUALIFIED_MODULE", $module);
        $rs = $adb->pquery("SELECT * FROM `vte_modules` WHERE module=? AND valid='1';", array($module));
        if ($adb->num_rows($rs) == 0) {
            $viewer->view("InstallerHeader.tpl", $module);
        }
        $viewer->assign("RECORD_MODE", $request->getMode());
    }
    public function process(Vtiger_Request $request)
    {
        $module = $request->getModule();
        $adb = PearDatabase::getInstance();
        $vTELicense = new ControlLayoutFields_VTELicense_Model($module);
        if (!$vTELicense->validate()) {
            $this->step2($request, $vTELicense);
        } else {
            $rs = $adb->pquery("SELECT * FROM `vte_modules` WHERE module=? AND valid='1';", array($module));
            if ($adb->num_rows($rs) == 0) {
                $this->step3($request);
            } else {
                $mode = $request->get("mode");
                if (!empty($mode)) {
                    $this->invokeExposedMethod($mode, $request);
                    return NULL;
                }
                $this->listAll($request);
            }
        }
    }
    public function step2(Vtiger_Request $request, $vTELicense)
    {
        global $site_URL;
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->assign("VTELICENSE", $vTELicense);
        $viewer->assign("SITE_URL", $site_URL);
        $viewer->view("Step2.tpl", $module);
    }
    public function step3(Vtiger_Request $request)
    {
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->view("Step3.tpl", $module);
    }
    public function listAll(Vtiger_Request $request)
    {
        $db = PearDatabase::getInstance();
        $sql = "SELECT * FROM vte_control_layout_fields ";
        $moduleName = $request->getModule();
        $cvId = $request->get("viewname");
        $pageNumber = $request->get("page");
        $orderBy = $request->get("orderby");
        $sortOrder = $request->get("sortorder");
        $clfModuleFilter = $request->get("ModuleFilter");
        if ($sortOrder == "ASC") {
            $nextSortOrder = "DESC";
            $sortImage = "icon-chevron-down";
        } else {
            $nextSortOrder = "ASC";
            $sortImage = "icon-chevron-up";
        }
        if (!empty($clfModuleFilter)) {
            $sql .= " WHERE module = \"" . $clfModuleFilter . "\" ";
        }
        if (!empty($orderBy)) {
            $sql .= " ORDER BY " . $orderBy . " " . $sortOrder;
        }
        if (empty($pageNumber)) {
            $pageNumber = "1";
        }
        $pagingModel = new Vtiger_Paging_Model();
        $pagingModel->set("page", $pageNumber);
        $startIndex = $pagingModel->getStartIndex();
        $pageLimit = $pagingModel->getPageLimit();
        $list_result = $db->pquery($sql, array());
        $totalEntries = $db->num_rows($list_result);
        $sql .= " LIMIT " . $startIndex . "," . $pageLimit;
        $list_result = $db->pquery($sql, array());
        $noOfEntries = $db->num_rows($list_result);
        $list_entries = array();
        for ($i = 0; $i <= $noOfEntries - 1; $i++) {
            $row = $db->query_result_rowdata($list_result, $i);
            $module_name = $row["module"];
            $descriptions = vtranslate($row["description"], $moduleName);
            $list_entries[$i] = array("id" => $row["id"], "module" => $module_name, "description" => $descriptions);
        }
        $pagingModel->calculatePageRange($list_entries);
        $pageCount = ceil((int) $totalEntries / (int) $pageLimit);
        if ($pageCount == 0) {
            $pageCount = 1;
        }
        if ($pageLimit < $totalEntries && $pageNumber < $pageCount) {
            $pagingModel->set("nextPageExists", true);
        } else {
            $pagingModel->set("nextPageExists", false);
        }
        $viewer = $this->getViewer($request);
        $viewer->assign("VIEWID", $cvId);
        $viewer->assign("MODULE", $moduleName);
        $viewer->assign("PAGING_MODEL", $pagingModel);
        $viewer->assign("PAGE_NUMBER", $pageNumber);
        $viewer->assign("PAGE_COUNT", $pageCount);
        $viewer->assign("CURRENT_USER_MODEL", Users_Record_Model::getCurrentUserModel());
        $viewer->assign("SELECTED_MODULE_FILTER", $clfModuleFilter);
        $viewer->assign("ORDER_BY", $orderBy);
        $viewer->assign("SORT_ORDER", $sortOrder);
        $viewer->assign("NEXT_SORT_ORDER", $nextSortOrder);
        $viewer->assign("SORT_IMAGE", $sortImage);
        $viewer->assign("ALL_MODULES", ControlLayoutFields_Module_Model::getSupportedModules());
        $viewer->assign("LISTVIEW_ENTRIES_COUNT", $noOfEntries);
        $viewer->assign("LISTVIEW_ENTRIES", $list_entries);
        $viewer->view("ListAll.tpl", $moduleName);
    }
    public function delete(Vtiger_Request $request)
    {
        $recordId = $request->get("record");
        $moduleName = $request->get("selected_module");
        $page = $request->get("page");
        $adb = PearDatabase::getInstance();
        if (!empty($recordId)) {
            $sql = "DELETE FROM `vte_control_layout_fields`  WHERE id = ?";
            $adb->pquery($sql, array($recordId));
        }
        header("Location: index.php?module=ControlLayoutFields&parent=Settings&view=ListAll&mode=listAll&ModuleFilter=" . $moduleName . "&page=" . $page);
    }
    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();
        $jsFileNames = array("modules." . $moduleName . ".resources.List");
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }
}

?>