<?php

class RelatedBlocksLists_PopupAjax_View extends RelatedBlocksLists_Popup_View
{
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod("getListViewCount");
        $this->exposeMethod("getRecordsCount");
        $this->exposeMethod("getPageCount");
    }
    public function preProcess(Vtiger_Request $request)
    {
        return true;
    }
    public function postProcess(Vtiger_Request $request)
    {
        return true;
    }
    public function process(Vtiger_Request $request)
    {
        $mode = $request->get("mode");
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        } else {
            $viewer = $this->getViewer($request);
            $moduleName = $request->getModule();
            $this->initializeListViewContents($request, $viewer);
            echo $viewer->view("PopupContents.tpl", $moduleName, true);
        }
    }
}

?>