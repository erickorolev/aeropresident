<?php

class RelatedBlocksLists_MassSettingsAjax_View extends Vtiger_IndexAjax_View
{
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod("showSettingsForm");
        $this->exposeMethod("getFields");
        $this->exposeMethod("getBlocks");
        $this->exposeMethod("collapseExpandBlocks");
        $this->exposeMethod("showEditFields");
        $this->exposeMethod("saveEditFields");
    }
    public function process(Vtiger_Request $request)
    {
        $mode = $request->get("mode");
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }
    public function getFields(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $select_module = $request->get("select_module");
        $moduleModel = Vtiger_Module_Model::getInstance($select_module);
        $fields = $moduleModel->getFields();
        $viewer = $this->getViewer($request);
        $viewer->assign("FIELDS", $fields);
        $viewer->assign("SELECTED_MODULE_NAME", $select_module);
        $viewer->assign("BLOCK_DATA", ["fields" => []]);
        echo $viewer->view("Fields.tpl", $moduleName, true);
    }
    public function showEditFields(Vtiger_Request $request)
    {
        global $adb;
        $qualifiedModule = $request->getModule(false);
        $blockid = $request->get("blockid");
        $sourceModule = $request->get("sourceModule");
        $field_name = $request->get("field_name");
        $field_label = $request->get("field_label");
        $sql = "SELECT rbl_b.relmodule, rbl_f.fieldname,rbl_f.mandatory, rbl_f.defaultvalue, f.fieldid FROM `relatedblockslists_fields` rbl_f\n        INNER JOIN relatedblockslists_blocks rbl_b ON rbl_b.blockid = rbl_f.blockid\n        INNER JOIN vtiger_tab t ON t.`name` = rbl_b.relmodule\n        INNER JOIN vtiger_field f ON f.tabid = t.tabid and f.fieldname = rbl_f.fieldname\n        WHERE rbl_f.blockid=" . $blockid . " AND rbl_f.fieldname='" . $field_name . "';";
        $results = $adb->pquery($sql, []);
        if (0 < $adb->num_rows($results)) {
            $fieldId = $adb->query_result($results, 0, "fieldid");
            $mandatory = $adb->query_result($results, 0, "mandatory");
            $defaultvalue = $adb->query_result($results, 0, "defaultvalue");
        }
        $fieldInstance = Settings_LayoutEditor_Field_Model::getInstance($fieldId);
        $viewer = $this->getViewer($request);
        $viewer->assign("BLOCKID", $blockid);
        $viewer->assign("FIELD_NAME", $field_name);
        $viewer->assign("FIELD_LABEL", $field_label);
        $viewer->assign("MANDATORY", $mandatory);
        $viewer->assign("DEFAULTVALUE", $defaultvalue);
        $viewer->assign("FIELD_INFO", $fieldInstance->getFieldInfo());
        $viewer->assign("SELECTED_MODULE_NAME", $sourceModule);
        $viewer->assign("FIELD_MODEL", $fieldInstance);
        $viewer->assign("QUALIFIED_MODULE", $qualifiedModule);
        $viewer->assign("USER_MODEL", Users_Record_Model::getCurrentUserModel());
        $viewer->view("EditFieldsView.tpl", $qualifiedModule);
    }
    public function saveEditFields(Vtiger_Request $request)
    {
        global $adb;
        $blockid = $request->get("blockid");
        $field_name = $request->get("field_name");
        $fieldDefaultValue = $request->get("fieldDefaultValue");
        $mandatory = $request->get("mandatory");
        if (empty($mandatory)) {
            $mandatory = 0;
        }
        $sql = "UPDATE `relatedblockslists_fields` SET defaultvalue ='" . $fieldDefaultValue . "', mandatory='" . $mandatory . "' WHERE blockid='" . $blockid . "' AND fieldname='" . $field_name . "'";
        $adb->pquery($sql, []);
        echo "done";
    }
    public function getBlocks(Vtiger_Request $request)
    {
        global $adb;
        $blocksList = [];
        $qualifiedModule = $request->getModule(false);
        $sourceModule = $request->get("sourceModule");
        $blockid = $request->get("blockid");
        if ($blockid) {
            $sql = "SELECT * FROM `relatedblockslists_blocks` WHERE blockid=?";
            $rs = $adb->pquery($sql, [$blockid]);
        } else {
            $sql = "SELECT * FROM `relatedblockslists_blocks` WHERE module=?";
            $rs = $adb->pquery($sql, [$sourceModule]);
        }
        if (0 < $adb->num_rows($rs)) {
            while ($row = $adb->fetch_array($rs)) {
                $blockid = $row["blockid"];
                $sourceModule = $row["module"];
                $relmodule_model = Vtiger_Module_Model::getInstance($row["relmodule"]);
                $blocksList[$blockid]["relmodule"] = $relmodule_model;
                $blocksList[$blockid]["type"] = $row["type"];
                $blocksList[$blockid]["after_block"] = $row["after_block"];
                $blocksList[$blockid]["filterfield"] = $row["filterfield"];
                $blocksList[$blockid]["filtervalue"] = $row["filtervalue"];
                $blocksList[$blockid]["expand"] = $row["expand"];
                $blocksList[$blockid]["sequence"] = $row["sequence"];
                $fields = [];
                $sqlField = "SELECT * FROM `relatedblockslists_fields` WHERE blockid = ? ORDER BY sequence";
                $rsFields = $adb->pquery($sqlField, [$blockid]);
                if (0 < $adb->num_rows($rsFields)) {
                    while ($rowField = $adb->fetch_array($rsFields)) {
                        $fields[] = $relmodule_model->getField($rowField["fieldname"]);
                    }
                }
                $blocksList[$blockid]["fields"] = $fields;
            }
        }
        $viewer = $this->getViewer($request);
        $viewer->assign("FIELDS", $fields);
        $viewer->assign("BLOCKS_LIST", $blocksList);
        $viewer->assign("SOURCE_MODULE", $sourceModule);
        echo $viewer->view("BlocksList.tpl", $qualifiedModule, true);
    }
    public function collapseExpandBlocks(Vtiger_Request $request)
    {
        global $adb;
        $blockid = $request->get("blockid");
        $display_status = $request->get("display_status");
        $sql = "UPDATE `relatedblockslists_blocks` SET expand = " . $display_status . " WHERE blockid = " . $blockid;
        $adb->pquery($sql, []);
        echo "success";
    }
    public function showSettingsForm(Vtiger_Request $request)
    {
        global $adb;
        global $vtiger_current_version;
        $qualifiedModule = $request->getModule(false);
        $sourceModule = $request->get("sourceModule");
        $type = $request->get("type");
        $blockid = $request->get("blockid");
        $viewer = $this->getViewer($request);
        $moduleModel = Settings_LayoutEditor_Module_Model::getInstanceByName($sourceModule);
        $relatedModuleModels = $moduleModel->getRelations();
        $relatedModulesName = [];
        foreach ($relatedModuleModels as $k => $relModuleModel) {
            if ($relModuleModel->getRelationModuleName() == "Calendar") {
                $relatedModulesName[] = "Calendar";
                $relatedModulesName[] = "Events";
            } else {
                $relatedModulesName[] = $relModuleModel->getRelationModuleName();
            }
        }
        $data = [];
        if ($blockid) {
            $sql = "SELECT * FROM `relatedblockslists_blocks` WHERE blockid=?";
            $rs = $adb->pquery($sql, [$blockid]);
            if (0 < $adb->num_rows($rs)) {
                $selectedModule = $adb->query_result($rs, 0, "relmodule");
                $blockid = $adb->query_result($rs, 0, "blockid");
                $active = $adb->query_result($rs, 0, "active");
                $type = $adb->query_result($rs, 0, "type");
                $after_block = $adb->query_result($rs, 0, "after_block");
                $limit_per_page = $adb->query_result($rs, 0, "limit_per_page");
                $filterfield = $adb->query_result($rs, 0, "filterfield");
                $filtervalue = $adb->query_result($rs, 0, "filtervalue");
                $sortfield = $adb->query_result($rs, 0, "sortfield");
                $sorttype = $adb->query_result($rs, 0, "sorttype");
                $fields = [];
                $sqlField = "SELECT * FROM `relatedblockslists_fields` WHERE blockid = ? ORDER BY sequence";
                $rsFields = $adb->pquery($sqlField, [$blockid]);
                if (0 < $adb->num_rows($rsFields)) {
                    while ($rowField = $adb->fetch_array($rsFields)) {
                        $fields[] = $rowField["fieldname"];
                    }
                }
                $data["id"] = $blockid;
                $data["module"] = $selectedModule;
                $data["after_block"] = $after_block;
                $data["fields"] = $fields;
                $data["status"] = $active;
                $data["limit_per_page"] = $limit_per_page;
                $data["filterfield"] = $filterfield;
                $data["filtervalue"] = $filtervalue;
                $data["sortfield"] = $sortfield;
                $data["sorttype"] = $sorttype;
                $moduleHasSet = $this->getModulesHasSet($sourceModule, $selectedModule);
                foreach ($relatedModulesName as $k => $moduleName) {
                    if (in_array($moduleName, $moduleHasSet)) {
                        unset($relatedModulesName[$k]);
                    }
                }
            }
        } else {
            $data["fields"] = [];
            $moduleHasSet = $this->getModulesHasSet($sourceModule);
            foreach ($relatedModulesName as $k => $moduleName) {
                if (in_array($moduleName, $moduleHasSet) && version_compare($vtiger_current_version, "7.0.0", "<")) {
                    unset($relatedModulesName[$k]);
                }
            }
            $selectedModule = reset($relatedModulesName);
        }
        $selectedModuleModel = Vtiger_Module_Model::getInstance($selectedModule);
        $fields = $selectedModuleModel->getFields();
        $all_block_models = $moduleModel->getBlocks();
        $all_block = [];
        foreach ($all_block_models as $blockModel) {
            $all_block[$blockModel->get("id")] = $blockModel->get("label");
        }
        $recordModel = Settings_PickListDependency_Record_Model::getInstance($selectedModule, "", "");
        $allPickLists = $recordModel->getAllPickListFields();
        $all_pick_lists_values = [];
        if (0 < count($allPickLists)) {
            foreach ($allPickLists as $field_name => $field_label) {
                $all_pick_lists_values[$field_name] = array_values(Vtiger_Util_Helper::getPickListValues($field_name));
            }
        }
        $all_pick_lists_of_all_module = [];
        $all_fields_of_all_module = [];
        foreach ($relatedModulesName as $item) {
            $recordModel_1 = Settings_PickListDependency_Record_Model::getInstance($item, "", "");
            $allPickLists_1 = $recordModel_1->getAllPickListFields();
            $all_pick_lists_values_1 = [];
            if (0 < count($allPickLists_1)) {
                foreach ($allPickLists_1 as $field_name_1 => $field_label_1) {
                    $all_pick_lists_values_1[$field_name_1 . "," . $field_label_1] = array_values(Vtiger_Util_Helper::getPickListValues($field_name_1));
                }
            }
            $all_pick_lists_of_all_module[$item] = $all_pick_lists_values_1;
            $moduleModelForSort = Vtiger_Module_Model::getInstance($item);
            $allFieldsModel = $moduleModelForSort->getFields();
            $arrFields = [];
            foreach ($allFieldsModel as $key => $fieldModel) {
                $fieldName = $fieldModel->get("name");
                $fieldLabel = $fieldModel->get("label");
                $arrFields[$fieldName] = $fieldLabel;
            }
            $all_fields_of_all_module[$item] = $arrFields;
        }
        $selected_pick_lists_value = $all_pick_lists_values[$filterfield];
        $selected_fields_sort_of_relmodule = $all_fields_of_all_module[$selectedModule];
        if (version_compare($vtiger_current_version, "7.0.0", "<")) {
            $all_pick_lists_values = json_decode($all_pick_lists_values);
            $all_pick_lists_of_all_module = json_decode($all_pick_lists_of_all_module);
            $all_fields_of_all_module = json_decode($all_fields_of_all_module);
        } else {
            $all_pick_lists_values = Vtiger_Functions::jsonEncode($all_pick_lists_values);
            $all_pick_lists_of_all_module = Vtiger_Functions::jsonEncode($all_pick_lists_of_all_module);
            $all_fields_of_all_module = Vtiger_Functions::jsonEncode($all_fields_of_all_module);
        }
        $viewer->assign("SELECTED_MODULE_NAME", $selectedModule);
        $viewer->assign("SOURCE_MODULE_NAME", $sourceModule);
        $viewer->assign("RELATED_MODULES", $relatedModulesName);
        $viewer->assign("MODULE_MODEL", $moduleModel);
        $viewer->assign("FIELDS", $fields);
        $viewer->assign("ALL_BLOCK_LABELS", $all_block);
        $viewer->assign("BLOCK_DATA", $data);
        $viewer->assign("BLOCKID", $blockid);
        $viewer->assign("ALL_PICK_LISTS", $allPickLists);
        $viewer->assign("ALL_PICK_LISTS_VALUES", $all_pick_lists_values);
        $viewer->assign("ALL_PICK_LISTS_OF_ALL_MODULE", $all_pick_lists_of_all_module);
        $viewer->assign("SELECTED_FIELDS_SORT_OF_RELMODULE", $selected_fields_sort_of_relmodule);
        $viewer->assign("ALL_FIELDS_OF_ALL_MODULE", $all_fields_of_all_module);
        $viewer->assign("SELECTED_PICK_LISTS_VALUE", $selected_pick_lists_value);
        $viewer->assign("TYPE", $type);
        $viewer->assign("QUALIFIED_MODULE", $qualifiedModule);
        $viewer->view("EditView.tpl", $qualifiedModule);
    }
    public function getModulesHasSet($sourceModule, $selectedModule = false)
    {
        global $adb;
        $result = [];
        if ($selectedModule) {
            $rs = $adb->pquery("SELECT relmodule FROM `relatedblockslists_blocks` WHERE module = ? and relmodule != ?", [$sourceModule, $selectedModule]);
        } else {
            $rs = $adb->pquery("SELECT relmodule FROM `relatedblockslists_blocks` WHERE module = ?", [$sourceModule]);
        }
        if ($adb->num_rows($rs)) {
            while ($data = $adb->fetchByAssoc($rs)) {
                $result[] = $data["relmodule"];
            }
        }
        return $result;
    }
}

?>