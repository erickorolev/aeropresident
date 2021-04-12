<?php
/* ***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ************************************************************************************/

class LeadHandler extends VTEventHandler {
    /**
     * @param $eventName
     * @param VTEntityData $entityData
     */
	function handleEvent($eventName, $entityData) {
		if($eventName === 'vtiger.lead.convertlead'){
		    $flightModule = Vtiger_Module_Model::getInstance('Flights');
		    if ($flightModule->isActive()) {
		        $entityIds = $entityData->entityIds;
		        if (isset($entityIds['Potentials'])) {
                    $wsid = $entityIds['Potentials'];
                    $potentialId = (int) vtws_getCRMEntityId($wsid);
                    $relModel = new Flights_RelationMoving_Model((int) $entityData->getId(), $potentialId);
                    $relModel->copy();
                }
            }
		}
	}
}

