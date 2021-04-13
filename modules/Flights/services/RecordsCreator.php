<?php
include_once 'vtlib/Vtiger/Module.php';
include_once 'include/utils/VtlibUtils.php';
include_once 'include/Webservices/Create.php';
include_once 'modules/Webforms/model/WebformsModel.php';
include_once 'modules/Webforms/model/WebformsFieldModel.php';
include_once 'include/QueryGenerator/QueryGenerator.php';
include_once 'includes/runtime/EntryPoint.php';
include_once 'includes/main/WebUI.php';
include_once 'include/Webservices/AddRelated.php';

class Flights_RecordsCreator_Service
{
    private array $data;
    private Users $user;

    public function __construct(array $data, Users $user)
    {
        $this->data = $data;
        $this->user = $user;
    }

    public function createFromRequest(string $ws_id): void
    {
        foreach ($this->data as $entity) {
            if (isset($entity['name'])) {
                $entity['cf_leads_id'] = $ws_id;
                $record = vtws_create('Flights', $entity, $this->user);
            }
        }
    }
}