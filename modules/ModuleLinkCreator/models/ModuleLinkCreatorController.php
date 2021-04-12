<?php

include_once "vtlib/tools/console.php";
/**
 * Class ModuleLinkCreatorConsole_Module_Model
 */
class ModuleLinkCreator_ModuleController extends Vtiger_Tools_Console_ModuleController
{
    /**
     * ModuleLinkCreator_ModuleController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }
    protected function createFiles(Vtiger_Module $module, Vtiger_Field $entityField)
    {
        global $vtiger_current_version;
        $targetpath = "modules/" . $module->name;
        if (!is_file($targetpath)) {
            mkdir($targetpath);
            if (version_compare($vtiger_current_version, "7.0.0", "<")) {
                $templatepath = "modules/ModuleLinkCreator/resources/ModuleDir/6.0.0";
            } else {
                $templatepath = "modules/ModuleLinkCreator/resources/ModuleDir/7.0.0";
            }
            $moduleFileContents = file_get_contents($templatepath . "/ModuleName.php");
            $moduleFileContentsHandler = file_get_contents($templatepath . "/ModuleNameHandler.php");
            $replacevars = array("__ModuleName__" => $module->name, "<modulename>" => strtolower($module->name), "<entityfieldlabel>" => $entityField->label, "<entitycolumn>" => $entityField->column, "<entityfieldname>" => $entityField->name);
            foreach ($replacevars as $key => $value) {
                $moduleFileContents = str_replace($key, $value, $moduleFileContents);
                $moduleFileContentsHandler = str_replace($key, $value, $moduleFileContentsHandler);
            }
            file_put_contents($targetpath . "/" . $module->name . ".php", $moduleFileContents);
            file_put_contents($targetpath . "/" . $module->name . "Handler.php", $moduleFileContentsHandler);
        }
    }
    /**
     * Create new module base on console
     *
     * @param string $moduleName
     * @param string $parent - Default is "Tools"
     * @param string $entityfieldlabel
     * @return Vtiger_Module
     */
    public function createModule($moduleName, $parent = "Tools", $entityfieldlabel = "Name")
    {
        global $vtiger_current_version;
        global $adb;
        if ($parent == "PROJECT") {
            $parent = "Support";
        }
        $moduleInformation["name"] = $moduleName;
        $moduleInformation["parent"] = $parent;
        $moduleInformation["entityfieldlabel"] = $entityfieldlabel;
        $this->create($moduleInformation);
        $lcasemodname = strtolower($moduleName);
        $primaryKey = $lcasemodname . "id";
        $module_basetable = "vtiger_" . $lcasemodname;
        if (Vtiger_Utils::CheckTable($module_basetable)) {
            $adb->pquery("ALTER TABLE " . $module_basetable . " DROP PRIMARY KEY, ADD PRIMARY KEY (" . $primaryKey . ")", array());
        }
        if (version_compare($vtiger_current_version, "7.0.0", "<")) {
        } else {
            $moduleUserSpecificTable = Vtiger_Functions::getUserSpecificTableName($moduleName);
            if (!Vtiger_Utils::CheckTable($moduleUserSpecificTable)) {
                Vtiger_Utils::CreateTable($moduleUserSpecificTable, "(`recordid` INT(19) NOT NULL,\r\n\t\t\t\t\t   `userid` INT(19) NOT NULL,\r\n\t\t\t\t\t   Index `record_user_idx` (`recordid`, `userid`)\r\n\t\t\t\t\t\t)", true);
            }
        }
        return Vtiger_Module::getInstance($moduleName);
    }
    /**
     * @param Vtiger_Module $module
     * @param array $moduleFields
     *             Example:
     *             $fieldBlocks = array(
     *                  'Applications' => array(                        // module name
     *                      'LBL_APPLICATIONS_INFORMATION' => array(    // block name
     *                          'cf_application_status' => array(        // field name
     *                              'label' => 'Application Status',    // label
     *                              'table' => 'vtiger_applicationscf',    // table
     *                              'uitype' => 16,                        // type
     *                              'picklistvalues' => array('Yes', 'No')    // (option) if uitype is picklist: 15|16|33
     *                          )
     *                      )
     *                  )
     *              );
     */
    public function createFields(Vtiger_Module $module, $moduleFields = array())
    {
        $adb = PearDatabase::getInstance();
        foreach ($moduleFields as $moduleName => $blockNames) {
            foreach ($blockNames as $blockName => $arrFieldInfo) {
                $blockInstance = Vtiger_Block::getInstance($blockName, $module);
                if (!$blockInstance) {
                    $blockInstance = new Vtiger_Block();
                    $blockInstance->label = $blockName;
                    $module->addBlock($blockInstance);
                }
                $rsFieldSequence = $adb->pquery("SELECT sequence FROM `vtiger_field` WHERE block = ? ORDER BY sequence DESC LIMIT 0,1", array($blockInstance->id));
                $sequence = $adb->query_result($rsFieldSequence, "sequence", 0);
                foreach ($arrFieldInfo as $fieldName => $fieldInfo) {
                    $fieldInstance = Vtiger_Field::getInstance($fieldName, $module);
                    if (!$fieldInstance) {
                        $fieldInstance = new Vtiger_Field();
                    }
                    $fieldInstance->name = $fieldName;
                    $fieldInstance->label = isset($fieldInfo["label"]) && $fieldInfo["label"] ? $fieldInfo["label"] : $fieldName;
                    $fieldInstance->uitype = isset($fieldInfo["uitype"]) && $fieldInfo["uitype"] ? $fieldInfo["uitype"] : 1;
                    if (isset($fieldInfo["columnname"]) && $fieldInfo["columnname"]) {
                        $fieldInstance->column = $fieldInfo["columnname"];
                    } else {
                        $fieldInstance->column = $fieldInstance->name;
                    }
                    if (isset($fieldInfo["columntype"]) && $fieldInfo["columntype"]) {
                        $fieldInstance->columntype = $fieldInfo["columntype"];
                    } else {
                        $fieldInstance->columntype = "VARCHAR(100)";
                    }
                    if (isset($fieldInfo["typeofdata"]) && $fieldInfo["typeofdata"]) {
                        $fieldInstance->typeofdata = $fieldInfo["typeofdata"];
                    }
                    if (isset($fieldInfo["table"]) && $fieldInfo["table"]) {
                        $fieldInstance->table = $fieldInfo["table"];
                    } else {
                        $fieldInstance->table = $module->basetable;
                    }
                    if (isset($fieldInfo["helpinfo"])) {
                        $fieldInstance->helpinfo = $fieldInfo["helpinfo"];
                    }
                    if (isset($fieldInfo["summaryfield"])) {
                        $fieldInstance->summaryfield = $fieldInfo["summaryfield"];
                    }
                    if (isset($fieldInfo["masseditable"])) {
                        $fieldInstance->masseditable = $fieldInfo["masseditable"];
                    }
                    if (isset($fieldInfo["displaytype"])) {
                        $fieldInstance->displaytype = $fieldInfo["displaytype"];
                    }
                    if (isset($fieldInfo["generatedtype"])) {
                        $fieldInstance->generatedtype = $fieldInfo["generatedtype"];
                    }
                    if (isset($fieldInfo["readonly"])) {
                        $fieldInstance->readonly = $fieldInfo["readonly"];
                    }
                    if (isset($fieldInfo["presence"])) {
                        $fieldInstance->presence = $fieldInfo["presence"];
                    }
                    if (isset($fieldInfo["defaultvalue"])) {
                        $fieldInstance->defaultvalue = $fieldInfo["defaultvalue"];
                    }
                    if (isset($fieldInfo["maximumlength"])) {
                        $fieldInstance->maximumlength = $fieldInfo["maximumlength"];
                    }
                    if (isset($fieldInfo["quickcreate"])) {
                        $fieldInstance->quickcreate = $fieldInfo["quickcreate"];
                    }
                    if (isset($fieldInfo["quickcreatesequence"])) {
                        $fieldInstance->quicksequence = $fieldInfo["quickcreatesequence"];
                    }
                    if (isset($fieldInfo["info_type"])) {
                        $fieldInstance->info_type = $fieldInfo["info_type"];
                    }
                    $fieldInstance->sequence = ++$sequence;
                    $blockInstance->addField($fieldInstance);
                    if ($fieldInfo["uitype"] == 15 || $fieldInfo["uitype"] == 16 || $fieldInfo["uitype"] == 33) {
                        $fieldInstance->setPicklistValues($fieldInfo["picklistvalues"]);
                    } else {
                        if ($fieldInfo["uitype"] == 10) {
                            $fieldInstance->setRelatedModules(array($fieldInfo["related_to_module"]));
                        }
                    }
                    if (isset($fieldInfo["filter"]) && $fieldInfo["filter"] && isset($fieldInfo["filter"]["name"]) && $fieldInfo["filter"]["name"]) {
                        $filterName = $fieldInfo["filter"]["name"];
                        $filterInstance = Vtiger_Filter::getInstance($filterName, $module);
                        if (!$filterInstance) {
                            $filterInstance = new Vtiger_Filter();
                            $filterInstance->name = $filterName;
                            $filterInstance->isdefault = $fieldInfo["filter"]["isdefault"] ? $fieldInfo["filter"]["isdefault"] : false;
                            $module->addFilter($filterInstance);
                            $filterInstance->addField($fieldInstance);
                        } else {
                            $rsFieldSequence = $adb->pquery("SELECT columnindex FROM `vtiger_cvcolumnlist` WHERE cvid = ? ORDER BY columnindex DESC LIMIT 0,1", array($filterInstance->id));
                            $sequence = $adb->query_result($rsFieldSequence, "columnindex", 0);
                            $filterInstance->addField($fieldInstance, $sequence + 1);
                        }
                    }
                }
            }
        }
    }
    /**
     * @param Vtiger_Module $module
     */
    public function createCustomViews(Vtiger_Module $module, $icons = "")
    {
        global $vtiger_current_version;
        if (version_compare($vtiger_current_version, "7.0.0", "<")) {
            $tempDir = "vlayout";
            $version = "6.0.0";
        } else {
            $tempDir = "v7";
            $version = "7.0.0";
        }
        $targetpath = "modules/" . $module->name . "/views";
        if (!is_file($targetpath)) {
            mkdir($targetpath);
        }
        $files = array();
        $this->findFiles("modules/ModuleLinkCreator/resources/ModuleDir/" . $version . "/views", false, $files);
        foreach ($files as $file) {
            $filename = basename($file, true);
            $moduleFileContents = file_get_contents($file);
            $replacevars = array("__ModuleName__" => $module->name, "<modulename>" => strtolower($module->name));
            foreach ($replacevars as $key => $value) {
                $moduleFileContents = str_replace($key, $value, $moduleFileContents);
            }
            file_put_contents((string) $targetpath . "/" . $filename, $moduleFileContents);
        }
        $moduleLayout = "layouts/" . $tempDir . "/modules/" . $module->name;
        if (!is_file($moduleLayout)) {
            mkdir($moduleLayout);
        }
        $files = array();
        $this->findOnlyDirFiles("modules/ModuleLinkCreator/resources/ModuleDir/" . $version . "/templates", false, $files);
        foreach ($files as $file) {
            $filename = basename($file, true);
            $moduleFileContents = file_get_contents($file);
            file_put_contents((string) $moduleLayout . "/" . $filename, $moduleFileContents);
        }
        $moduleLayoutResources = "layouts/" . $tempDir . "/modules/" . $module->name . "/resources";
        if (!is_file($moduleLayoutResources)) {
            mkdir($moduleLayoutResources);
        }
        $files = array();
        $this->findFiles("modules/ModuleLinkCreator/resources/ModuleDir/" . $version . "/templates/resources", false, $files);
        foreach ($files as $file) {
            $filename = basename($file, true);
            $moduleFileContents = file_get_contents($file);
            $replacevars = array("<modulename>" => $module->name, "moduleNameIcon" => strtolower($module->name), "content-icon-module" => $icons);
            foreach ($replacevars as $key => $value) {
                $moduleFileContents = str_replace($key, $value, $moduleFileContents);
            }
            file_put_contents((string) $moduleLayoutResources . "/" . $filename, $moduleFileContents);
        }
    }
    /**
     * @param Vtiger_Module $module
     */
    public function createCustomModels(Vtiger_Module $module)
    {
        global $vtiger_current_version;
        if (version_compare($vtiger_current_version, "7.0.0", "<")) {
            $version = "6.0.0";
        } else {
            $version = "7.0.0";
        }
        $targetpath = "modules/" . $module->name . "/models";
        if (!is_file($targetpath)) {
            mkdir($targetpath);
        }
        $files = array();
        $this->findFiles("modules/ModuleLinkCreator/resources/ModuleDir/" . $version . "/models", false, $files);
        foreach ($files as $file) {
            $filename = basename($file, true);
            $moduleFileContents = file_get_contents($file);
            $replacevars = array("__ModuleName__" => $module->name, "<modulename>" => strtolower($module->name));
            foreach ($replacevars as $key => $value) {
                $moduleFileContents = str_replace($key, $value, $moduleFileContents);
            }
            file_put_contents((string) $targetpath . "/" . $filename, $moduleFileContents);
        }
    }
    /**
     * @param string $moduleName
     * @param array $tables
     * @return bool
     */
    public function cleanDatabase($moduleName, $tables = array())
    {
        global $adb;
        foreach ($tables as $table) {
            $adb->pquery("DROP TABLE ?", array($table));
        }
        $adb->pquery("DELETE FROM `vtiger_crmentity`  WHERE setype=?", array($moduleName));
        $adb->pquery("DELETE a.* FROM  `vtiger_blocks` a INNER JOIN `vtiger_tab` b ON a.tabid = b.tabid WHERE b.name =? ", array($moduleName));
        return true;
    }
    /**
     * @param string $moduleName
     * @return bool
     */
    public function cleanFolder($moduleName)
    {
        global $vtiger_current_version;
        if (version_compare($vtiger_current_version, "7.0.0", "<")) {
            $tempDir = "vlayout";
        } else {
            $tempDir = "v7";
        }
        $this->removeFolder("layouts/" . $tempDir . "/modules/" . $moduleName);
        $this->removeFolder("modules/" . $moduleName);
        return true;
    }
    /**
     * @param $path
     * @return bool
     */
    public function removeFolder($path)
    {
        if (!isFileAccessible($path) || !is_dir($path)) {
            return false;
        }
        if (!is_writeable($path)) {
            chmod($path, 511);
        }
        $handle = opendir($path);
        while ($tmp = readdir($handle)) {
            if ($tmp == ".." || $tmp == ".") {
                continue;
            }
            $tmpPath = $path . DS . $tmp;
            if (is_file($tmpPath)) {
                if (!is_writeable($tmpPath)) {
                    chmod($tmpPath, 438);
                }
                unlink($tmpPath);
            } else {
                if (is_dir($tmpPath)) {
                    if (!is_writeable($tmpPath)) {
                        chmod($tmpPath, 511);
                    }
                    $this->removeFolder($tmpPath);
                }
            }
        }
        closedir($handle);
        rmdir($path);
        return !is_dir($path);
    }
    /**
     * @param string $moduleName
     * @return bool
     */
    public function cleanLanguage($moduleName)
    {
        $files = glob("languages/*/" . $moduleName . ".php");
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }
    /**
     * @link http://stackoverflow.com/questions/7288029/php-delete-directory-that-is-not-empty
     * @param $dir
     */
    public function rmdir_recursive($dir)
    {
        foreach (scandir($dir) as $file) {
            if ("." === $file || ".." === $file) {
                continue;
            }
            $tmpFile = (string) $dir . "/" . $file;
            if (is_dir($tmpFile)) {
                $this->rmdir_recursive($tmpFile);
            } else {
                unlink($tmpFile);
            }
        }
        rmdir($dir);
    }
    /**
     * @param string $moduleName
     * @return bool
     */
    public function uninstallModule($moduleName)
    {
        global $adb;
        $module = Vtiger_Module::getInstance($moduleName);
        $rs = $adb->pquery("SELECT * FROM `vtiger_fieldmodulerel` WHERE `relmodule` =?", array($moduleName));
        $listFieldModule = array();
        if (0 < $adb->num_rows($rs)) {
            while ($row = $adb->fetchByAssoc($rs)) {
                if ($row["module"] != "ModComments") {
                    $listFieldModule[] = $row["fieldid"];
                }
            }
        }
        foreach ($listFieldModule as $field) {
            $adb->pquery("UPDATE `vtiger_field` SET `presence`='1' WHERE `fieldid`=?", array($field));
            $adb->pquery("DELETE FROM `vtiger_fieldmodulerel` WHERE `fieldid`=?", array($field));
        }
        if ($module) {
            $lowerModuleName = strtolower($module->name);
            $module->delete();
            $tables = array();
            $tables[] = $lowerModuleName;
            $tables[] = $lowerModuleName . "cf";
            $this->cleanDatabase($moduleName, $tables);
            $this->cleanFolder($moduleName);
            $this->cleanLanguage($moduleName);
            $this->removeHandle($moduleName);
        }
        return true;
    }
    /**
     * @param string $sourceModule
     * @param string $prefix
     * @param int $sequenceNumber
     * @return array
     */
    public function customizeRecordNumbering($sourceModule, $prefix = "NO", $sequenceNumber = 1)
    {
        $moduleModel = Settings_Vtiger_CustomRecordNumberingModule_Model::getInstance($sourceModule);
        $moduleModel->set("prefix", $prefix);
        $moduleModel->set("sequenceNumber", $sequenceNumber);
        $result = $moduleModel->setModuleSequence();
        return $result;
    }
    /**
     * @param string $moduleName
     * @param int $fieldTypeId
     */
    public function addModuleRelatedToForEvents($moduleName, $fieldTypeId)
    {
        global $adb;
        $sqlCheckProject = "SELECT * FROM `vtiger_ws_referencetype` WHERE fieldtypeid = ? AND type = ?";
        $rsCheckProject = $adb->pquery($sqlCheckProject, array($fieldTypeId, $moduleName));
        if ($adb->num_rows($rsCheckProject) < 1) {
            $adb->pquery("INSERT INTO `vtiger_ws_referencetype` (`fieldtypeid`, `type`) VALUES (?, ?)", array($fieldTypeId, $moduleName));
        }
    }
    private function findOnlyDirFiles($dir, $file_pattern, &$files)
    {
        $items = glob($dir . "/*.tpl", GLOB_NOSORT);
        foreach ($items as $item) {
            if (is_file($item)) {
                if (!$file_pattern || preg_match("/" . $file_pattern . "/", $item)) {
                    $files[] = $item;
                }
            } else {
                if (is_dir($item) && $dir != $item) {
                    $this->findFiles($item, $file_pattern, $files);
                }
            }
        }
    }
    private function createHandle($moduleName)
    {
        include_once "include/events/VTEventsManager.inc";
        global $adb;
        $em = new VTEventsManager($adb);
        $em->setModuleForHandler($moduleName, (string) $moduleName . "Handler.php");
        $em->registerHandler("vtiger.entity.aftersave", "modules/" . $moduleName . "/" . $moduleName . "Handler.php", (string) $moduleName . "Handler");
    }
    /**
     * @param string $moduleName
     */
    private function removeHandle($moduleName)
    {
        include_once "include/events/VTEventsManager.inc";
        global $adb;
        $em = new VTEventsManager($adb);
        $em->unregisterHandler((string) $moduleName . "Handler");
    }
}
/**
 * Class ModuleLinkCreatorConsole_LanguageController
 */
class ModuleLinkCreatorConsole_LanguageController extends Vtiger_Tools_Console_LanguageController
{
    /**
     * ModuleLinkCreatorConsole_LanguageController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * @param string $moduleName
     * @param array $languageStrings
     * @param array $jsLanguageStrings
     */
    public function createLanguage($moduleName, $languageStrings = array(), $jsLanguageStrings = array())
    {
        global $vtiger_current_version;
        if (version_compare($vtiger_current_version, "7.0.0", "<")) {
            $version = "6.0.0";
        } else {
            $version = "7.0.0";
        }
        $baseLanguageStrings = array("LBL_CUSTOM_INFORMATION" => "Custom Information");
        $baseJsLanguageStrings = array();
        if ($languageStrings && !empty($languageStrings)) {
            $baseLanguageStrings = array_merge($baseLanguageStrings, $languageStrings);
        }
        if ($jsLanguageStrings && !empty($jsLanguageStrings)) {
            $baseJsLanguageStrings = array_merge($baseJsLanguageStrings, $jsLanguageStrings);
        }
        $files = array();
        $this->findFiles("modules/ModuleLinkCreator/resources/ModuleDir/" . $version . "/languages", ".php\$", $files);
        foreach ($files as $file) {
            $filename = basename($file, true);
            $dir = substr($file, 0, strpos($file, $filename));
            $tmp = explode("/", rtrim($dir, "/"));
            $code = $tmp[count($tmp) - 1];
            $newDir = "languages/" . $code;
            if (!file_exists($newDir)) {
                mkdir($newDir);
            }
            $contents = file_get_contents($file);
            $contents = str_replace("'<languageStrings>'", var_export($baseLanguageStrings, true), $contents);
            $contents = str_replace("'<jsLanguageStrings>'", var_export($baseJsLanguageStrings, true), $contents);
            file_put_contents((string) $newDir . "/" . $moduleName . ".php", $contents);
        }
    }
}

?>