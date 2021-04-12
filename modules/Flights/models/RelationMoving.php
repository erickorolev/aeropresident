<?php

class Flights_RelationMoving_Model
{
    const MODULE_NAME = 'Flights';

    private Vtiger_Record_Model $parentModel;
    private Vtiger_Record_Model $childModel;
    /**
     * @var false|Vtiger_Module|Vtiger_Module_Model
     */
    private $flightModel;
    private Vtiger_Paging_Model $pagingModel;
    protected string $relField;
    /**
     * @var false|mixed
     */
    private Vtiger_Relation_Model $relationModel;

    public function __construct(int $from_id, int $to_id, string $relField = 'cf_potentials_id')
    {
        $this->parentModel = Vtiger_Record_Model::getInstanceById($from_id);
        $this->childModel = Vtiger_Record_Model::getInstanceById($to_id);
        $this->flightModel = Vtiger_Module_Model::getInstance(self::MODULE_NAME);
        $this->relField = $relField;
        $pagingModel = new Vtiger_Paging_Model();
        $pagingModel->set('page', 1);
        $pagingModel->set('limit', 100);
        $this->pagingModel = $pagingModel;
        $this->relationModel = Vtiger_Relation_Model::getInstance($this->childModel->getModule(), $this->flightModel, self::MODULE_NAME);
    }

    public function copy(): void
    {
        $relListModel = Vtiger_RelationListView_Model::getInstance($this->parentModel, self::MODULE_NAME, self::MODULE_NAME);
        $entries = $relListModel->getEntries($this->pagingModel);
        foreach ($entries as $entry) {
            $recordModel = Vtiger_Record_Model::getInstanceById($entry->getId());
            $recordModel->set('mode', 'edit');
            $recordModel->set($this->relField, $this->childModel->getId());
            $recordModel->save();
            $this->relationModel->addRelation($this->childModel->getId(), $entry->getId());
        }
    }
}