<?php
require_once 'modules/VDProgressView/models/Module.php';
class VDProgressView_Settings_View extends Settings_Vtiger_Index_View
{

    public function __construct()
    {
        parent::__construct();
    }
    public function preProcess(Vtiger_Request $request)
    {
        parent::preProcess($request);
        $adb = PearDatabase::getInstance();
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->assign("QUALIFIED_MODULE", $module);        
    }
    public function process(Vtiger_Request $request)
    {
        $module = $request->getModule();
        $adb = PearDatabase::getInstance();
        $mode = $request->getMode();
        if ($mode) {
            $this->{$mode}($request);
        } else {
            $this->renderSettingsUI($request);
        }
        
    }
    
    public function renderSettingsUI(Vtiger_Request $request)
    {
        $tablename = VDProgressView_Module_Model::TABLENAME;
        $adb = PearDatabase::getInstance();
        $module = $request->getModule();
        $module_model = Vtiger_Module_Model::getInstance($module);
        $listViewEntries = $module_model->getlistViewEntries();
        $viewer = $this->getViewer($request);
        $rs = $adb->pquery("SELECT * FROM `$tablename`;", array());
        $enable = $adb->query_result($rs, 0, "active");
        $viewer->assign("ENABLE", $enable);
        $viewer->assign("MODULE", $module);
        $viewer->assign("MODULE_MODEL", $module_model);
        $viewer->assign("LISTVIEW_ENTRIES", $listViewEntries);
        echo $viewer->view("Settings.tpl", $module, true);
    }
    /**
     * Function to get the list of Script models to be included
     * @param Vtiger_Request $request
     * @return <Array> - List of Vtiger_JsScript_Model instances
     */
    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();
        $jsFileNames = array("modules.VDProgressView.resources.Settings", "~/libraries/jquery/bootstrapswitch/js/bootstrap-switch.min.js");
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        unset($headerScriptInstances["modules.VDProgressView.resources.Edit"]);
        return $headerScriptInstances;
    }
    public function getHeaderCss(Vtiger_Request $request)
    {
        $headerCssInstances = parent::getHeaderCss($request);
        $cssFileNames = array("~layouts/v7/modules/VDProgressView/resources/style.css", "~/libraries/jquery/bootstrapswitch/css/bootstrap3/bootstrap-switch.min.css");
        $cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerCssInstances = array_merge($headerCssInstances, $cssInstances);
        return $headerCssInstances;
    }
}

?>