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
		
		$termconditionsql = $adb->pquery("SELECT tandc FROM  vtiger_inventory_tandc WHERE type='Quotes'  LIMIT 1");
		$terms_conditions = $adb->query_result($termconditionsql,0,"tandc"); 
		
		$potsql = $adb->pquery("SELECT contact_id FROM  vtiger_potential WHERE potentialid='".$record."'  LIMIT 1");
		$contactid = $adb->query_result($potsql,0,"contact_id"); 
		
		$contactsql = $adb->pquery("SELECT firstname,lastname FROM  vtiger_contactdetails WHERE contactid='".$contactid."' LIMIT 1");
		$firstname = $adb->query_result($contactsql,0,"firstname");
		$lastname = $adb->query_result($contactsql,0,"lastname");

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
					$name = $adb->query_result($flightsql,0,"name");
					$cf_1183 = $adb->query_result($flightsql,0,"cf_1183");
					
					$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
					$modelData = $recordModel->getData();
					$recordModel->set('mode', '');
					$recordModel->set("subject", trim($firstname." ".$lastname).". ".$name." - ".$cf_1183);
					if ($record>0)
					{
						//$recordModel->set("potential_id", $record);
					}
					if ($contactid>0)
					{
						$recordModel->set("contact_id", $contactid);
					}
					
					$recordModel->set("terms_conditions", $terms_conditions);
					
					
					$recordModel->save();  
					$returnid=$recordModel->getId();

					$subject="";
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