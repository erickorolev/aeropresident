<?php

class RelatedBlocksLists_Settings_View extends Settings_Vtiger_Index_View
{
    public function __construct()
    {
        parent::__construct();
    }
    public function preProcess(Vtiger_Request $request)
    {
        parent::preProcess($request);
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
        $adb = PearDatabase::getInstance();
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $rs = $adb->pquery("SELECT `enable` FROM `relatedblockslists_settings`;", array());
        $enable = $adb->query_result($rs, 0, "enable");
        $viewer->assign("ENABLE", $enable);
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
        $jsFileNames = array("modules." . $moduleName . ".resources.Settings");
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }
}

?>