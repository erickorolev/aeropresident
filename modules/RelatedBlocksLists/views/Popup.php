<?php

class RelatedBlocksLists_Popup_View extends Vtiger_Footer_View
{
    protected $listViewHeaders = false;
    public function __construct()
    {
        parent::__construct();
    }
    public function checkPermission(Vtiger_Request $request)
    {
    }
    public function process(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $companyDetails = Vtiger_CompanyDetails_Model::getInstanceById();
        $companyLogo = $companyDetails->getLogo();
        $this->initializeListViewContents($request, $viewer);
        $viewer->assign("COMPANY_LOGO", $companyLogo);
        $viewer->view("Popup.tpl", $moduleName);
    }
    public function postProcess(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $viewer->view("PopupFooter.tpl", $moduleName);
    }
    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = $this::getHeaderScripts($request);
        $moduleName = $request->getModule();
        $jsFileNames = ["modules.Vtiger.resources.Popup", "modules." . $moduleName . ".resources.Popup", "modules.Vtiger.resources.BaseList", "modules." . $moduleName . ".resources.BaseList", "libraries.jquery.jquery_windowmsg", "modules.Vtiger.resources.validator.BaseValidator", "modules.Vtiger.resources.validator.FieldValidator", "modules." . $moduleName . ".resources.validator.FieldValidator"];
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }
    public function initializeListViewContents(Vtiger_Request $request, Vtiger_Viewer $viewer)
    {
        $moduleName = $request->getModule();
        $relatedModuleName = $request->get("related_module");
        $cvId = $request->get("cvid");
        $pageNumber = $request->get("page");
        $orderBy = $request->get("orderby");
        $sortOrder = $request->get("sortorder");
        $sourceModule = $request->get("src_module");
        $sourceField = $request->get("src_field");
        $sourceRecord = $request->get("src_record");
        $searchKey = $request->get("search_key");
        $searchValue = $request->get("search_value");
        $currencyId = $request->get("currency_id");
        $relatedParentModule = $request->get("related_parent_module");
        $relatedParentId = $request->get("related_parent_id");
        global $currentModule;
        $currentModule = $sourceModule;
        $searchParams = $request->get("search_params");
        if ($request->get("selected_id")) {
            $selectedId = $request->get("selected_id");
        }
        $getUrl = $request->get("get_url");
        $multiSelectMode = true;
        if (empty($cvId)) {
            $cvId = "0";
        }
        if (empty($pageNumber)) {
            $pageNumber = "1";
        }
        $pagingModel = new Vtiger_Paging_Model();
        $pagingModel->set("page", $pageNumber);
        $moduleModel = Vtiger_Module_Model::getInstance($relatedModuleName);
        $recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceForModule($moduleModel);
        $isRecordExists = Vtiger_Util_Helper::checkRecordExistance($relatedParentId);
        if ($isRecordExists) {
            $relatedParentModule = "";
            $relatedParentId = "";
        } else {
            if ($isRecordExists === NULL) {
                $relatedParentModule = "";
                $relatedParentId = "";
            }
        }
        if (!empty($relatedParentModule) && !empty($relatedParentId)) {
            $parentRecordModel = Vtiger_Record_Model::getInstanceById($relatedParentId, $relatedParentModule);
            $listViewModel = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
            $searchModuleModel = $listViewModel->getRelatedModuleModel();
        } else {
            $db = PearDatabase::getInstance();
            $currentUser = vglobal("current_user");
            $modelClassName = Vtiger_Loader::getComponentClassName("Model", "ListView", "RelatedBlocksLists");
            $instance = new $modelClassName();
            $queryGenerator = new QueryGenerator($moduleModel->get("name"), $currentUser);
            $listFields = $moduleModel->getPopupViewFieldsList();
            $listFields[] = "id";
            $queryGenerator->setFields($listFields);
            $controller = new ListViewController($db, $currentUser, $queryGenerator);
            $listViewModel = $instance->set("module", $moduleModel)->set("query_generator", $queryGenerator)->set("listview_controller", $controller);
            $searchModuleModel = $listViewModel->getModule();
        }
        $listViewModel->set("selected_id", $selectedId);
        $listViewModel->set("related_module_name", $relatedModuleName);
        if (!empty($orderBy)) {
            $listViewModel->set("orderby", $orderBy);
            $listViewModel->set("sortorder", $sortOrder);
        }
        if (!empty($sourceModule)) {
            $listViewModel->set("src_module", $sourceModule);
            $listViewModel->set("src_field", $sourceField);
            $listViewModel->set("src_record", $sourceRecord);
        }
        if (!empty($searchKey) && !empty($searchValue)) {
            $listViewModel->set("search_key", $searchKey);
            $listViewModel->set("search_value", $searchValue);
        }
        if (!empty($searchParams)) {
            $transformedSearchParams = $this->transferListSearchParamsToFilterCondition($searchParams, $searchModuleModel);
            $listViewModel->set("search_params", $transformedSearchParams);
        }
        if (!empty($relatedParentModule) && !empty($relatedParentId)) {
            $this->listViewHeaders = $listViewModel->getHeaders();
            $models = $listViewModel->getEntries($pagingModel);
            $noOfEntries = count($models);
            foreach ($models as $recordId => $recordModel) {
                foreach ($this->listViewHeaders as $fieldName => $fieldModel) {
                    $recordModel->set($fieldName, $recordModel->getDisplayValue($fieldName));
                }
                $models[$recordId] = $recordModel;
            }
            $this->listViewEntries = $models;
            if (0 < count($this->listViewEntries)) {
                $parent_related_records = true;
            }
        } else {
            $this->listViewHeaders = $listViewModel->getListViewHeaders();
            $this->listViewEntries = $listViewModel->getListViewEntries($pagingModel);
        }
        if (!$parent_related_records && !empty($relatedParentModule) && !empty($relatedParentId)) {
            $relatedParentModule = NULL;
            $relatedParentId = NULL;
            $listViewModel = Vtiger_ListView_Model::getInstanceForPopup($relatedModuleName);
            if (!empty($orderBy)) {
                $listViewModel->set("orderby", $orderBy);
                $listViewModel->set("sortorder", $sortOrder);
            }
            if (!empty($sourceModule)) {
                $listViewModel->set("src_module", $sourceModule);
                $listViewModel->set("src_field", $sourceField);
                $listViewModel->set("src_record", $sourceRecord);
            }
            if (!empty($searchKey) && !empty($searchValue)) {
                $listViewModel->set("search_key", $searchKey);
                $listViewModel->set("search_value", $searchValue);
            }
            if (!empty($searchParams)) {
                $transformedSearchParams = $this->transferListSearchParamsToFilterCondition($searchParams, $searchModuleModel);
                $listViewModel->set("search_params", $transformedSearchParams);
            }
            $this->listViewHeaders = $listViewModel->getListViewHeaders();
            $this->listViewEntries = $listViewModel->getListViewEntries($pagingModel);
        }
        if (empty($searchParams)) {
            $searchParams = [];
        }
        foreach ($searchParams as $fieldListGroup) {
            foreach ($fieldListGroup as $fieldSearchInfo) {
                $fieldSearchInfo["searchValue"] = $fieldSearchInfo[2];
                $fieldSearchInfo["fieldName"] = $fieldName = $fieldSearchInfo[0];
                $fieldSearchInfo["comparator"] = $fieldSearchInfo[1];
                $searchParams[$fieldName] = $fieldSearchInfo;
            }
        }
        $noOfEntries = count($this->listViewEntries);
        if (empty($sortOrder)) {
            $sortOrder = "ASC";
        }
        if ($sortOrder == "ASC") {
            $nextSortOrder = "DESC";
            $sortImage = "downArrowSmall.png";
        } else {
            $nextSortOrder = "ASC";
            $sortImage = "upArrowSmall.png";
        }
        $viewer->assign("MODULE", $moduleName);
        $viewer->assign("RELATED_MODULE", $relatedModuleName);
        $viewer->assign("MODULE_NAME", $moduleName);
        $viewer->assign("MODULE_MODEL", $moduleModel);
        $viewer->assign("SOURCE_MODULE", $sourceModule);
        $viewer->assign("SOURCE_FIELD", $sourceField);
        $viewer->assign("SOURCE_RECORD", $sourceRecord);
        $viewer->assign("RELATED_PARENT_MODULE", $relatedParentModule);
        $viewer->assign("RELATED_PARENT_ID", $relatedParentId);
        $viewer->assign("SEARCH_KEY", $searchKey);
        $viewer->assign("SEARCH_VALUE", $searchValue);
        $viewer->assign("ORDER_BY", $orderBy);
        $viewer->assign("SORT_ORDER", $sortOrder);
        $viewer->assign("NEXT_SORT_ORDER", $nextSortOrder);
        $viewer->assign("SORT_IMAGE", $sortImage);
        $viewer->assign("GETURL", $getUrl);
        $viewer->assign("CURRENCY_ID", $currencyId);
        $viewer->assign("RECORD_STRUCTURE_MODEL", $recordStructureInstance);
        $viewer->assign("RECORD_STRUCTURE", $recordStructureInstance->getStructure());
        $viewer->assign("PAGING_MODEL", $pagingModel);
        $viewer->assign("PAGE_NUMBER", $pageNumber);
        $viewer->assign("LISTVIEW_ENTRIES_COUNT", $noOfEntries);
        $viewer->assign("LISTVIEW_HEADERS", $this->listViewHeaders);
        $viewer->assign("LISTVIEW_ENTRIES", $this->listViewEntries);
        $viewer->assign("SEARCH_DETAILS", $searchParams);
        $viewer->assign("SELECTEDIDS", $selectedId);
        if (PerformancePrefs::getBoolean("LISTVIEW_COMPUTE_PAGE_COUNT", false)) {
            if (!$this->listViewCount) {
                $this->listViewCount = $listViewModel->getListViewCount();
            }
            $totalCount = $this->listViewCount;
            $pageLimit = $pagingModel->getPageLimit();
            $pageCount = ceil((array) $totalCount / (array) $pageLimit);
            if ($pageCount == 0) {
                $pageCount = 1;
            }
            $viewer->assign("PAGE_COUNT", $pageCount);
            $viewer->assign("LISTVIEW_COUNT", $totalCount);
        }
        $viewer->assign("MULTI_SELECT", $multiSelectMode);
        $viewer->assign("CURRENT_USER_MODEL", Users_Record_Model::getCurrentUserModel());
    }
    public function getListViewCount(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $relatedModuleName = $request->get("related_module");
        $cvId = $request->get("cvid");
        $pageNumber = $request->get("page");
        $orderBy = $request->get("orderby");
        $sortOrder = $request->get("sortorder");
        $sourceModule = $request->get("src_module");
        $sourceField = $request->get("src_field");
        $sourceRecord = $request->get("src_record");
        $searchKey = $request->get("search_key");
        $searchValue = $request->get("search_value");
        $currencyId = $request->get("currency_id");
        $relatedParentModule = $request->get("related_parent_module");
        $relatedParentId = $request->get("related_parent_id");
        if (!empty($relatedParentModule) && !empty($relatedParentId)) {
            $parentRecordModel = Vtiger_Record_Model::getInstanceById($relatedParentId, $relatedParentModule);
            $listViewModel = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $moduleName, $label);
        } else {
            $listViewModel = Vtiger_ListView_Model::getInstanceForPopup($relatedModuleName);
        }
        if (!empty($sourceModule)) {
            $listViewModel->set("src_module", $sourceModule);
            $listViewModel->set("src_field", $sourceField);
            $listViewModel->set("src_record", $sourceRecord);
            $listViewModel->set("currency_id", $currencyId);
        }
        if (!empty($orderBy)) {
            $listViewModel->set("orderby", $orderBy);
            $listViewModel->set("sortorder", $sortOrder);
        }
        if (!empty($searchKey) && !empty($searchValue)) {
            $listViewModel->set("search_key", $searchKey);
            $listViewModel->set("search_value", $searchValue);
        }
        if (!empty($relatedParentModule) && !empty($relatedParentId)) {
            $count = $listViewModel->getRelatedEntriesCount();
        } else {
            $count = $listViewModel->getListViewCount();
        }
        return $count;
    }
    public function getPageCount(Vtiger_Request $request)
    {
        $listViewCount = $this->getListViewCount($request);
        $pagingModel = new Vtiger_Paging_Model();
        $pageLimit = $pagingModel->getPageLimit();
        $pageCount = ceil((array) $listViewCount / (array) $pageLimit);
        if ($pageCount == 0) {
            $pageCount = 1;
        }
        $result = [];
        $result["page"] = $pageCount;
        $result["numberOfRecords"] = $listViewCount;
        $response = new Vtiger_Response();
        $response->setResult($result);
        $response->emit();
    }
    public function transferListSearchParamsToFilterCondition($listSearchParams, $moduleModel)
    {
        return Vtiger_Util_Helper::transferListSearchParamsToFilterCondition($listSearchParams, $moduleModel);
    }
}

?>