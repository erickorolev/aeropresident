<?php

class Potentials_Convert_Action extends Vtiger_Action_Controller 
{
	function __construct() {
		parent::__construct();
		 $this->exposeMethod('CreateQ');
	}
	
	public function process(Vtiger_Request $request) {
		$mode = $request->getMode();
		if(!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
	}
	
	function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		$userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($moduleModel->getId());
		if(!$permission) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
		return true;
	}
	 
	function CreateQ(Vtiger_Request $request) 
	{
		global $adb; 
		
		$leadid=$request->get("leadid");
		$record=$request->get("record");
		$moduleName="Quotes";

		$relatedlistproj = $adb->pquery("SELECT flightsid FROM  vtiger_flights WHERE cf_leads_id='".$leadid."'");
		$res_cnt = $adb->num_rows($relatedlistproj);		
		if($res_cnt > 0) {
			
			for($i=0;$i<$res_cnt;$i++) 
			{
				$relcrmid = $adb->query_result($relatedlistproj,$i,"flightsid");
				$flightsql = $adb->pquery("SELECT * FROM  vtiger_flights,vtiger_flightscf,vtiger_crmentity WHERE vtiger_flights.flightsid=vtiger_flightscf.flightsid AND vtiger_crmentity.crmid=vtiger_flights.flightsid AND vtiger_crmentity.deleted!='1' AND  vtiger_flights.flightsid='".$relcrmid."' AND vtiger_flightscf.cf_1237='' LIMIT 1");
				
				$res_cnt_flightsql = $adb->num_rows($flightsql);		
				if($res_cnt_flightsql > 0) 
				{

					$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
					$modelData = $recordModel->getData();
					$recordModel->set('mode', '');
					$recordModel->set("title", "subject");
					$recordModel->set("subject", "subject");
					if ($record>0)
					{
						//$recordModel->set("potential_id", $record);
					}
					if ($contact_id>0)
					{
						$recordModel->set("contact_id", $contact_id);
					}
					$recordModel->save();  
					$returnid=$recordModel->getId();

					$adb->pquery("UPDATE vtiger_quotes SET potentialid='".$record."' WHERE quoteid = '".$returnid."' LIMIT 1");
					$adb->pquery("UPDATE vtiger_flights SET cf_quotes_id='".$returnid."',cf_potentials_id='".$record."' WHERE flightsid = '".$relcrmid."' LIMIT 1");
				}	
			}
		}
		
		$relatedlistproj = $adb->pquery("SELECT flightsid FROM  vtiger_flights WHERE cf_leads_id='".$leadid."' AND cf_quotes_id='0'");
		$res_cnt = $adb->num_rows($relatedlistproj);		
		if($res_cnt > 0) { 
			for($i=0;$i<$res_cnt;$i++) 
			{ 
				$relcrmid = $adb->query_result($relatedlistproj,$i,"flightsid");
				$flightsql = $adb->pquery("SELECT cf_1237 FROM  vtiger_flights,vtiger_flightscf,vtiger_crmentity WHERE vtiger_flights.flightsid=vtiger_flightscf.flightsid AND vtiger_crmentity.crmid=vtiger_flights.flightsid AND vtiger_crmentity.deleted!='1' AND  vtiger_flights.flightsid='".$relcrmid."' AND vtiger_flightscf.cf_1237!='' LIMIT 1");		
				$flightback = $adb->query_result($flightsql,0,"cf_1237"); 
				
				if($flightback > 0) 
				{  
					$flightsql2 = $adb->pquery("SELECT cf_quotes_id FROM  vtiger_flights,vtiger_flightscf,vtiger_crmentity WHERE vtiger_flights.flightsid=vtiger_flightscf.flightsid AND vtiger_crmentity.crmid=vtiger_flights.flightsid AND vtiger_crmentity.deleted!='1' AND  vtiger_flights.cf_leads_id='".$leadid."' AND vtiger_flightscf.cf_1235='".$flightback."' LIMIT 1");
					$cf_quotes_id = $adb->query_result($flightsql2,0,"cf_quotes_id");		 
					$adb->pquery("UPDATE vtiger_flights SET cf_quotes_id='".$cf_quotes_id."',cf_potentials_id='".$record."' WHERE flightsid = '".$relcrmid."' LIMIT 1");
				}
			}
		}
		header("location: index.php?module=Potentials&view=Detail&record=".$record."&mode=showDetailViewByMode");
		exit;
	}
}
?>