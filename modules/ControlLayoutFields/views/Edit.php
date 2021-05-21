<?php

class ControlLayoutFields_Edit_View extends Settings_Vtiger_Index_View
{
    public function __construct()
    {
        parent::__construct();
        $this->vteLicense();
    }
    public function vteLicense()
    {
        $vTELicense = new ControlLayoutFields_VTELicense_Model("ControlLayoutFields");
        if (!$vTELicense->validate()) {
            header("Location: index.php?module=ControlLayoutFields&parent=Settings&view=ListAll&mode=step2");
        }
    }
    public function process(Vtiger_Request $request)
    {
        $mode = $request->getMode();
        if ($mode) {
            $this->{$mode}($request);
        } else {
            $this->step1($request);
        }
    }
    public function preProcess(Vtiger_Request $request)
    {
        parent::preProcess($request);
        $viewer = $this->getViewer($request);
        $recordId = $request->get("record");
        $viewer->assign("RECORDID", $recordId);
        if ($recordId) {
            $clfModel = ControlLayoutFields_Record_Model::getInstanceById($recordId, "ControlLayoutFields");
            $viewer->assign("CLF_MODEL", $clfModel);
        }
        $viewer->assign("RECORD_MODE", $request->getMode());
        $viewer->view("EditHeader.tpl", $request->getModule(false));
    }
    public function step1(Vtiger_Request $request)
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $qualifiedModuleName = $request->getModule();
        $recordId = $request->get("record");
        $selected_module = $request->get("selected_module");
        $selected_des = $request->get("descriptions");
        if ($recordId) {
            $clfModel = ControlLayoutFields_Record_Model::getInstanceById($recordId, "ControlLayoutFields");
            $viewer->assign("RECORDID", $recordId);
            $viewer->assign("MODULE_MODEL", $clfModel->getInfo());
            $viewer->assign("MODE", "edit");
        } else {
            $clfModel = ControlLayoutFields_Record_Model::getCleanInstance("ControlLayoutFields");
            $selectedModule = $request->get("source_module");
            if (!empty($selectedModule)) {
                $viewer->assign("SELECTED_MODULE", $selectedModule);
            }
        }
        if (!empty($selected_module)) {
            $viewer->assign("SELECTED_MODULE", $selected_module);
        }
        if (!empty($selected_des)) {
            $viewer->assign("SELECTED_DES", $selected_des);
        }
        $viewer->assign("ALL_MODULES", ControlLayoutFields_Module_Model::getSupportedModules());
        $viewer->assign("MODULE", $moduleName);
        $viewer->assign("QUALIFIED_MODULE", $qualifiedModuleName);
        $viewer->assign("CURRENT_USER", $currentUser);
        $admin = Users::getActiveAdminUser();
        $viewer->assign("ACTIVE_ADMIN", $admin);
        $viewer->view("EditStep1.tpl", $qualifiedModuleName);
    }
    public function step2(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $qualifiedModuleName = $request->getModule();
        $recordId = $request->get("record");
        if ($recordId) {
            $clfModel = ControlLayoutFields_Record_Model::getInstanceById($recordId, "ControlLayoutFields");
            $selectedModuleInfo = $clfModel->getInfo();
            $selectedModuleName = $selectedModuleInfo["module"];
            $conditions = $selectedModuleInfo["condition"];
            $selectedModule = Vtiger_Module_Model::getInstance($selectedModuleName);
            $selectedModuleName = $selectedModule->getName();
        } else {
            $selectedModuleName = $request->get("module_name");
            $selectedModule = Vtiger_Module_Model::getInstance($selectedModuleName);
            $clfModel = ControlLayoutFields_Record_Model::getCleanInstance($selectedModuleName);
        }
        $requestData = $request->getAll();
        $viewer->assign("MODULE_MODEL", $selectedModule);
        $viewer->assign("RECORD", $recordId);
        $viewer->assign("OLD_FILTER", $selectedModuleInfo["condition"]);
        $viewer->assign("SELECTED_MODULE_NAME", $selectedModuleName);
        $viewer->assign("DESCRIPTIONS", $request->get("description"));
        $dateFilters = Vtiger_Field_Model::getDateFilterTypes();
        foreach ($dateFilters as $comparatorKey => $comparatorInfo) {
            $comparatorInfo["startdate"] = DateTimeField::convertToUserFormat($comparatorInfo["startdate"]);
            $comparatorInfo["enddate"] = DateTimeField::convertToUserFormat($comparatorInfo["enddate"]);
            $comparatorInfo["label"] = vtranslate($comparatorInfo["label"], $qualifiedModuleName);
            $dateFilters[$comparatorKey] = $comparatorInfo;
        }
        $viewer->assign("DATE_FILTERS", $dateFilters);
        $viewer->assign("ROLES", ControlLayoutFields_Field_Model::getRoles());
        $tmpModel = ControlLayoutFields_Record_Model::getCleanInstance($selectedModuleName);
        $recordStructureInstance = Settings_Workflows_RecordStructure_Model::getInstanceForWorkFlowModule($tmpModel, Settings_Workflows_RecordStructure_Model::RECORD_STRUCTURE_MODE_FILTER);
        $viewer->assign("RECORD_STRUCTURE_MODEL", $recordStructureInstance);
        $recordStructure = $recordStructureInstance->getStructure();
        if (in_array($selectedModuleName, getInventoryModules())) {
            $itemsBlock = "LBL_ITEM_DETAILS";
            unset($recordStructure[$itemsBlock]);
        }
        $viewer->assign("RECORD_STRUCTURE", $recordStructure);
        $viewer->assign("ADVANCED_FILTER_OPTIONS", ControlLayoutFields_Field_Model::getAdvancedFilterOptions());
        $viewer->assign("ADVANCED_FILTER_OPTIONS_BY_TYPE", ControlLayoutFields_Field_Model::getAdvancedFilterOpsByFieldType());
        $viewer->assign("FIELD_EXPRESSIONS", ControlLayoutFields_Module_Model::getExpressions());
        $viewer->assign("META_VARIABLES", ControlLayoutFields_Module_Model::getMetaVariables());
        $viewer->assign("ADVANCE_CRITERIA", ControlLayoutFields_Module_Model::transformToAdvancedFilterCondition(json_decode(html_entity_decode($conditions), true)));
        $viewer->assign("IS_FILTER_SAVED_NEW", true);
        $viewer->assign("MODULE", $moduleName);
        $viewer->assign("QUALIFIED_MODULE", $qualifiedModuleName);
        $viewer->view("EditStep2.tpl", $qualifiedModuleName);
    }
    public function Step3(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $qualifiedModuleName = "ControlLayoutFields";
        $recordId = $request->get("record");
        $selected_module = $request->get("selected_module");
        if ($recordId) {
            $clfModel = ControlLayoutFields_Record_Model::getInstanceById($recordId, "ControlLayoutFields");
        } else {
            $clfModel = ControlLayoutFields_Record_Model::getCleanInstance("ControlLayoutFields");
        }
        $viewer->assign("RECORD", $recordId);
        $viewer->assign("MODULE", $moduleName);
        $viewer->assign("CLF_ID", $clfModel->getId());
        $viewer->assign("TASK_LIST", $clfModel->getTasks());
        $viewer->assign("QUALIFIED_MODULE", $qualifiedModuleName);
        $viewer->assign("SELECTED_MODULE", $selected_module);
        $viewer->view("EditStep3.tpl", $qualifiedModuleName);
    }
    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();
        $jsFileNames = array("modules.Settings.Vtiger.resources.Edit", "modules." . $moduleName . ".resources.Edit", "modules." . $moduleName . ".resources.Edit1", "modules." . $moduleName . ".resources.Edit2", "modules." . $moduleName . ".resources.Edit3", "modules." . $moduleName . ".resources.AdvanceFilter", "~libraries/jquery/ckeditor/ckeditor.js", "modules.Vtiger.resources.CkEditor", "~libraries/jquery/jquery.datepick.package-4.1.0/jquery.datepick.js", "~/libraries/jquery/posabsolute-jQuery-Validation-Engine/js/jquery.validationEngine.js");
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }
    public function getHeaderCss(Vtiger_Request $request)
    {
        $headerCssInstances = parent::getHeaderCss($request);
        $moduleName = $request->getModule();
        $cssFileNames = array("~libraries/jquery/jquery.datepick.package-4.1.0/jquery.datepick.css", "~/libraries/jquery/posabsolute-jQuery-Validation-Engine/css/validationEngine.jquery.css");
        $cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerCssInstances = array_merge($cssInstances, $headerCssInstances);
        return $headerCssInstances;
    }
}

?>