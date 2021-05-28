<?php

class ControlLayoutFields_TaskRecord_Model extends Vtiger_Record_Model
{
    const TASK_STATUS_ACTIVE = 1;
    public function getId()
    {
        return $this->get("id");
    }
    public function getName()
    {
        return $this->get("name");
    }
    public function setName($name)
    {
        $this->name = $name;
    }
    public function setControlLayoutField($clf_id)
    {
        $this->clf_id = $clf_id;
    }
    public function setActive($active)
    {
        $this->active = $active;
    }
    public function getAction()
    {
        return $this->get("actions");
    }
    public function setAction($action)
    {
        $this->actions = $action;
    }
    public function isActive()
    {
        return $this->get("active") == self::TASK_STATUS_ACTIVE;
    }
    public function getControlLayoutFieldId()
    {
        return $this->get("clf_id");
    }
    public function getEditViewUrl()
    {
        return "index.php?module=ControlLayoutFields&parent=Settings&view=EditTask&task_id=" . $this->getId() . "&for_clf=" . $this->getControlLayoutFieldId();
    }
    public function getDeleteActionUrl()
    {
        return "index.php?module=ControlLayoutFields&parent=Settings&action=TaskAjax&mode=Delete&task_id=" . $this->getId();
    }
    public function getChangeStatusUrl()
    {
        return "index.php?module=ControlLayoutFields&parent=Settings&action=TaskAjax&mode=ChangeStatus&task_id=" . $this->getId();
    }
    /**
     * Function deletes clf task
     */
    public function delete($recordId)
    {
        $adb = PearDatabase::getInstance();
        if (!empty($recordId)) {
            $sql = "DELETE FROM `vte_control_layout_fields_task` WHERE `id`=?";
            $adb->pquery($sql, array($recordId));
        }
        return true;
    }
    /**
     * Function saves clf task
     */
    public function save($request)
    {
        $adb = PearDatabase::getInstance();
        $json = new Zend_Json();
        $recordId = $request->get("task_id");
        $options = $json->encode($request->get("options"));
        if (empty($recordId)) {
            $sql = "INSERT INTO `vte_control_layout_fields_task` (`clf_id`, `name`,`active`,`actions`) VALUES (?, ?, ?,?)";
            $adb->pquery($sql, array($request->get("for_clf"), $request->get("name"), $request->get("active") == "true" ? 1 : 0, $options));
            $recordId = $adb->getLastInsertID();
        } else {
            $sql = "UPDATE `vte_control_layout_fields_task` SET `clf_id`=?, `name`=?,`active`=?,`actions` =? WHERE `id`=?";
            $adb->pquery($sql, array($request->get("for_clf"), $request->get("name"), $request->get("active") == "true" ? 1 : 0, $options, $recordId));
        }
        return $recordId;
    }
    public function active($request)
    {
        $adb = PearDatabase::getInstance();
        $recordId = $request->get("task_id");
        $active = $request->get("active") == "1" ? 0 : 1;
        if (!empty($recordId)) {
            $sql = "UPDATE `vte_control_layout_fields_task` SET `active`=? WHERE `id`=?";
            $adb->pquery($sql, array($active, $recordId));
        }
        return $recordId;
    }
}

?>