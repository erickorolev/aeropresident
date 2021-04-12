<?php

class RelatedBlocksLists_SaveAjax_Action extends Vtiger_Action_Controller
{
    public function __construct()
    {
        parent::__construct();
    }
    public function checkPermission(Vtiger_Request $request)
    {
    }
    public function process(Vtiger_Request $request)
    {
        global $adb;
        $sourceModule = $request->get("sourceModule");
        $select_module = $request->get("select_module");
        $fields = $request->get("fields");
        $selectedFieldsList = $request->get("selectedFieldsList");
        $blockid = trim($request->get("blockid"));
        $after_block = $request->get("after_block");
        $limit_per_page = $request->get("limit_per_page");
        $type = $request->get("type");
        $status = $request->get("status");
        $filterfield = $request->get("filterfield");
        $filtervalue = $request->get("filtervalue");
        $sortfield = $request->get("sortfield");
        $sorttype = $request->get("sorttype");
        $status = $request->get("status");
        $max_sequence = 0;
        $sql_max_sequence = "SELECT MAX(sequence) as max_sequence FROM `relatedblockslists_blocks`";
        $results = $adb->pquery($sql_max_sequence, array());
        if (0 < $adb->num_rows($results)) {
            $max_sequence = $adb->query_result($results, 0, "max_sequence");
        }
        $max_sequence = $max_sequence + 1;
        if (empty($blockid)) {
            $sql = "INSERT INTO `relatedblockslists_blocks` (`module`, `relmodule`, `type`, `active`,`after_block`,`limit_per_page`,filterfield,filtervalue,sortfield,sorttype,sequence) VALUES (?, ?, ?, ?, ?,?,?,?,?,?,?)";
            $adb->pquery($sql, array($sourceModule, $select_module, $type, $status, $after_block, $limit_per_page, $filterfield, $filtervalue, $sortfield, $sorttype, $max_sequence));
            $blockid = $adb->getLastInsertID();
        } else {
            $sql = "UPDATE `relatedblockslists_blocks` SET `module`=?, `relmodule`=?, `type`=?, `active`=?, `after_block`=? , `limit_per_page` = ?, `filterfield` = ?, `filtervalue` = ?, `sortfield` = ?, `sorttype` = ? WHERE (`blockid`=?)";
            $adb->pquery($sql, array($sourceModule, $select_module, $type, $status, $after_block, $limit_per_page, $filterfield, $filtervalue, $sortfield, $sorttype, $blockid));
        }
        $adb->pquery("DELETE FROM `relatedblockslists_fields` WHERE blockid=?", array($blockid));
        if ($selectedFieldsList) {
            foreach ($selectedFieldsList as $sequence => $fieldname) {
                $adb->pquery("INSERT INTO `relatedblockslists_fields` (`blockid`, `fieldname`, `sequence`) VALUES (?, ?, ?)", array($blockid, $fieldname, $sequence));
            }
        } else {
            foreach ($fields as $sequence => $fieldname) {
                $adb->pquery("INSERT INTO `relatedblockslists_fields` (`blockid`, `fieldname`, `sequence`) VALUES (?, ?, ?)", array($blockid, $fieldname, $sequence));
            }
        }
        if (!empty($filterfield)) {
            $moduleModel = Vtiger_Module_Model::getInstance($select_module);
            $fieldModel = Vtiger_Field_Model::getInstance($filterfield, $moduleModel);
            $fieldModel->set("defaultvalue", $filtervalue);
            $fieldModel->save();
        }
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult(array("blockid" => $blockid, "after_block" => vtranslate($after_block, $sourceModule)));
        $response->emit();
    }
}

?>