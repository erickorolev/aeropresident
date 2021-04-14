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
        $advanced_query = decode_html($request->get("advanced_query"));
        $result = $this->checkAdvancedQuery($advanced_query);
        if (!$result) {
            $response = new Vtiger_Response();
            $response->setResult(array("success" => "0", "message" => vtranslate("Advanced query is false", $request->getModule(false))));
            $response->emit();
        } else {
            $max_sequence = 0;
            $sql_max_sequence = "SELECT MAX(sequence) as max_sequence FROM `relatedblockslists_blocks`";
            $results = $adb->pquery($sql_max_sequence, array());
            if (0 < $adb->num_rows($results)) {
                $max_sequence = $adb->query_result($results, 0, "max_sequence");
            }
            $max_sequence = $max_sequence + 1;
            if (empty($blockid)) {
                $sql = "INSERT INTO `relatedblockslists_blocks` (`module`, `relmodule`, `type`, `active`,`after_block`,`limit_per_page`,filterfield,filtervalue,sortfield,sorttype,sequence,advanced_query) VALUES (?, ?, ?, ?, ?,?,?,?,?,?,?, ?)";
                $adb->pquery($sql, array($sourceModule, $select_module, $type, $status, $after_block, $limit_per_page, $filterfield, $filtervalue, $sortfield, $sorttype, $max_sequence, $advanced_query));
                $blockid = $adb->getLastInsertID();
            } else {
                $sql = "UPDATE `relatedblockslists_blocks` SET `module`=?, `relmodule`=?, `type`=?, `active`=?, `after_block`=? , `limit_per_page` = ?, `filterfield` = ?, `filtervalue` = ?, `sortfield` = ?, `sorttype` = ?, `advanced_query`=? WHERE (`blockid`=?)";
                $adb->pquery($sql, array($sourceModule, $select_module, $type, $status, $after_block, $limit_per_page, $filterfield, $filtervalue, $sortfield, $sorttype, $advanced_query, $blockid));
            }
            $array_width = array();
            $sql_width = "SELECT fieldname,width FROM `relatedblockslists_fields` WHERE blockid=?";
            $results = $adb->pquery($sql_width, array($blockid));
            while ($row = $adb->fetch_row($results)) {
                $array_width[$row["fieldname"]] = $row["width"];
            }
            $adb->pquery("DELETE FROM `relatedblockslists_fields` WHERE blockid=?", array($blockid));
            if ($selectedFieldsList) {
                foreach ($selectedFieldsList as $sequence => $fieldname) {
                    $width = isset($array_width[$fieldname]) ? $array_width[$fieldname] : "";
                    $adb->pquery("INSERT INTO `relatedblockslists_fields` (`blockid`, `fieldname`, `sequence`,`width`) VALUES (?, ?, ?,?)", array($blockid, $fieldname, $sequence, $width));
                }
            } else {
                foreach ($fields as $sequence => $fieldname) {
                    $width = isset($array_width[$fieldname]) ? $array_width[$fieldname] : "";
                    $adb->pquery("INSERT INTO `relatedblockslists_fields` (`blockid`, `fieldname`, `sequence`,`width`) VALUES (?, ?, ?,?)", array($blockid, $fieldname, $sequence, $width));
                }
            }
            $customizable_options = array("chk_detail_view_icon" => 0, "chk_edit_view_icon" => 0, "chk_detail_edit_icon" => 0, "chk_edit_edit_icon" => 0, "chk_detail_delete_icon" => 0, "chk_edit_delete_icon" => 0, "chk_detail_add_btn" => 0, "chk_edit_view_add_btn" => 0, "chk_detail_select_btn" => 0, "chk_edit_select_btn" => 0, "chk_detail_inline_edit" => 0, "chk_edit_inline_edit" => 0);
            foreach ($customizable_options as $option => $value) {
                if ($request->get($option) == "on") {
                    $customizable_options[$option] = 1;
                }
            }
            if (0 < $blockid) {
                $sql = "UPDATE `relatedblockslists_blocks` SET `customizable_options`=? WHERE (`blockid`=?)";
                $adb->pquery($sql, array(Vtiger_Functions::jsonEncode($customizable_options), $blockid));
            }
            $response = new Vtiger_Response();
            $response->setEmitType(Vtiger_Response::$EMIT_JSON);
            $response->setResult(array("blockid" => $blockid, "after_block" => vtranslate($after_block, $sourceModule)));
            $response->emit();
        }
    }
    public function checkAdvancedQuery($advanced_query = "")
    {
        global $adb;
        $success = true;
        if (!empty($advanced_query)) {
            try {
                $adb->dieOnError = false;
                $chkAdvancedQuery = str_replace("\$recordid\$", "1", $advanced_query);
                $results = $adb->pquery($chkAdvancedQuery, array());
                if ($results === false) {
                    $success = false;
                }
            } catch (Exception $e) {
            }
        }
        return $success;
    }
}

?>