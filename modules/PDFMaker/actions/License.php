<?php ?><?php include_once 'vtlib/Vtiger/Net/Client.php';
class PDFMaker_License_Action extends Vtiger_Action_Controller {
    private $url = "https://www.its4you.sk/en/api/app/license/v7/index.php";
    function __construct() {
        parent::__construct();
        $this->exposeMethod('deactivateLicense');
        $this->exposeMethod('editLicense');
    }
    function checkPermission(Vtiger_Request $request) {
        return;
    }
    function process(Vtiger_Request $request) {
        $mode = $request->get('mode');
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
            return;
        }
    }
    public function controlLicense() {
        $this->checkLicense(true);
    }
    public function editLicense(Vtiger_Request $request) {
        $adb = PearDatabase::getInstance();
        $sl = "soap_log";
        global $$sl;
        if (!$$sl) $$sl = LoggerManager::getLogger('SOAP');
        $PDFMaker = new PDFMaker_PDFMaker_Model();
        $t = "type";
        $r_type = $request->get($t);
        $l = "licensekey";
        $key = $request->get($l);
        $vcv = "7";
        $s = "site_URL";
        $salt = vglobal($s);
        $error = false;
        if (class_exists("Vtiger_Net_Client")) {
            $client = new Vtiger_Net_Client($this->url);
            if (true) {
                $url = md5("web/" . $salt);
                $type = "professional";
                $Validate = $this->controlPDFMakerVersion(true, $r_type, $key, $client, $type, $url);

                    $lic_inf = $this->getLicenseInfo($type, $key);
                    $license_due_date = (!empty($Validate["due_date"]) ? $Validate["due_date"] : "");
                    $adb->pquery("DELETE FROM vtiger_pdfmaker_license", array());
                    $adb->pquery("INSERT INTO vtiger_pdfmaker_license (version_type, license_key, license_info, license_due_date) VALUES(?,?,?,?)", array($type, $key, $lic_inf, $license_due_date));
                    $adb->pquery("DELETE FROM vtiger_pdfmaker_version WHERE version=?", array($vcv));
                    $adb->pquery("INSERT INTO vtiger_pdfmaker_version (version,license) VALUES (?,?)", array($vcv, "P" . md5($type . "/" . $salt . "t7")));
                    $PDFMaker->actualizeLinks();
                    $result = array("success" => true, "message" => vtranslate("REACTIVATE_SUCCESS", "PDFMaker"), "licensekey" => $key, "due_date" => (!empty($license_due_date) ? Vtiger_Date_UIType::getDisplayDateValue($license_due_date) : ""));
            } else {
                $error = vtranslate("LBL_INVALID_KEY", "PDFMaker");
            }
        } else {
            $error = vtranslate("LBL_CLASS_VTIGER_NET_CLIENT_DOES_NOT_EXIST", "PDFMaker");
        }
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        if ($error) {
            $result = array("success" => false, "message" => $error);
        }
        $response->setResult($result);
        $response->emit();
    }
    private function getUserKey($ctype, $key) {
        $tabid = getTabid("PDFMaker");
        $acc = '';
        $dir = "user_privileges/user_privileges_";
        foreach (glob($dir . '*.php') as $file) {
            $user_id = substr($file, 32, -4);
            if (is_file($dir . $user_id . ".php") && $user_id != "") {
                $user = new Users();
                $user->retrieveCurrentUserInfoFromFile($user_id);
                if (isset($user->column_fields["accesskey"]) && $user->column_fields["accesskey"] != '') {
                    $acc = $user->column_fields["accesskey"];
                    break;
                }
            }
        }
        if ($acc == '') {
            $active_user = Users::getActiveAdminUser();
            $acc = $active_user->column_fields["user_name"];
        }
        $txt = "acc:";
        return md5($ctype . "PM" . $tabid . $txt . $acc . $key);
    }
    private function getCompanyInfo($ctype, $key) {
        $tabid = getTabid("PDFMaker");
        $company_details = Vtiger_CompanyDetails_Model::getInstanceById();
        $comp = "comp:";
        $org = "organizationname";
        $company = $company_details->get($org);
        return md5($ctype . "PM" . $tabid . $comp . $company . $key);
    }
    private function getLicenseInfo($ctype, $key) {
        return $this->getCompanyInfo($ctype, $key) . ";" . $this->getUserKey($ctype, $key);
    }
    private function controlPDFMakerVersion($add_date, $call_type, $key, $client, $type, $url, $mode = '') {
        $L = array("validate" => "invalidated");
        $control = str_replace(" ", "_", PDFMaker_Version_Helper::getVersion());
        $v = "vtiger_current_version";
        $vcv = vglobal($v);
        $time = time();
        $params = array("key" => $key, "install_type" => $type, "vtiger_version" => $vcv, "module" => "PDFMaker", "ext_version" => $control, "url" => $url, "time" => $time);
        if ($call_type == "deactivate") {
            $params["vmode"] = $mode;
        }
        $jsonresponse = $client->doGet($params);
        $response = json_decode($jsonresponse, true);
        if ($response["success"] == "true") {
            $Result = $response["result"];
        }
        if (isset($Result["c"]) && !empty($Result["c"])) {
            $minus = date("y6Y7", $time);
            $url_len = strlen($url);
            $type_len = strlen($type);
            $kontrola = $minus;
            $kontrola-= $time;
            $kontrola-= ($type_len + $url_len);
            $vv = abs($kontrola);
            $c = $vv - $Result["c"];
            if ($Result["c"] == abs($kontrola) || abs($c) < 60) {
                $L["validate"] = $Result["validated"];
            }
            if ($add_date) $L["due_date"] = $Result["due_date"];
        }
        return $L;
    }
    public function checkLicense($c = false) {
        $adb = PearDatabase::getInstance();
        $s = "site_URL";
        $salt = vglobal($s);
        $result1 = $adb->pquery("SELECT version_type, license_key, license_info FROM vtiger_pdfmaker_license", Array());
        $version_type = $adb->query_result($result1, 0, "version_type");
        $db_licinfo = $adb->query_result($result1, 0, "license_info");
        $key = $adb->query_result($result1, 0, "license_key");
        $vcv = "7";
        $DB_Licinfo = explode(";", $db_licinfo);
        $result2 = $adb->pquery("SELECT license FROM vtiger_pdfmaker_version WHERE version=?", Array($vcv));
        $db_lic = $adb->query_result($result2, 0, "license");
        if (!$result1) {
            return 4;
        }
        $mismatch = 0;
        if ($mismatch > 1 && $c == true) {
            $sql3 = "UPDATE vtiger_pdfmaker_license SET version_type = ?";
            $d = "";
            $adb->pquery($sql3, Array($d));
            $PDFMaker = new PDFMaker_PDFMaker_Model();
            $PDFMaker->DeleteAllRefLinks();
        }
        return substr($version_type, 0, 3) . "pm" . $mismatch . "7";
    }
    public function deactivateLicense(Vtiger_Request $request) {
        $adb = PearDatabase::getInstance();
        $error = false;
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        if ($currentUserModel->isAdminUser()) {
            $sl = "soap_log";
            global $$sl;
            if (!$$sl) $$sl = LoggerManager::getLogger('SOAP');
            $PDFMaker = new PDFMaker_PDFMaker_Model();
            $s = "site_URL";
            $salt = vglobal($s);
            $vcv = "7";
            if (class_exists("Vtiger_Net_Client")) {
                $client = new Vtiger_Net_Client($this->url);
                $type = $request->get('type');
                $version_type = $PDFMaker->GetVersionType();
                $license_key = $PDFMaker->GetLicenseKey();
                $url = md5("web/" . $salt);
                if ($version_type == "professional" OR $version_type == "basic") {
                    $key = $request->get('key');
                    if ($key != "") {
                        $Validate = $this->controlPDFMakerVersion(false, "deactivate", $key, $client, $version_type, $url, ($type == "control" ? $type : "deactivate"));
                        if (isset($Validate["validate"]) && $Validate["validate"] == "invalidated") {
                            if (isset($Validate["error"]) && !empty($Validate["error"])) {
                                $error = vtranslate($Validate["error"], "PDFMaker");
                            } else {
                                $error = vtranslate("LBL_INVALID_KEY", "PDFMaker");
                            }
                        } else {
                            if (isset($type) && $type == "control") {
                                $result = array("success" => true, "deactivate" => "ok");
                            } else {
                                $adb->pquery("DELETE FROM vtiger_pdfmaker_license", array());
                                $adb->pquery("INSERT INTO vtiger_pdfmaker_license (version_type, license_key) VALUES (?,?)", array('deactivate', ''));
                                $adb->pquery("UPDATE vtiger_pdfmaker_version SET license=? WHERE version=?", array('deactivated', $vcv));
                                $PDFMaker->DeleteAllRefLinks();
                                $result = array("success" => true, "deactivate" => vtranslate("LBL_DEACTIVATE_SUCCESS", "PDFMaker"));
                            }
                        }
                    } else {
                        $error = vtranslate("LBL_INVALID_KEY", "PDFMaker");
                    }
                } else {
                    $error = vtranslate("LBL_DEACTIVATE_ERROR", "PDFMaker");
                }
            } else {
                $error = vtranslate("LBL_CLASS_VTIGER_NET_CLIENT_DOES_NOT_EXIST", "PDFMaker");
            }
        } else {
            $error = vtranslate("LBL_PERMISSION_DENIED", "Vtiger");
        }
        if ($error) {
            $result = array("success" => false, "deactivate" => $error);
        }
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult($result);
        $response->emit();
    }
} ?><?php
