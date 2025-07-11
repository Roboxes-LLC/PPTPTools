<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/time.php';
require_once ROOT.'/common/userInfo.php';
require_once ROOT.'/core/common/componentType.php';

class Action
{
   const UNKNOWN_ACTION_ID = 0;
   
   const UNKNOWN_COMPONENT_ID = 0;
   
   const UNKNOWN_COMPONENT_STATUS = 0;

   public $actionId;
   public $componentType;
   public $componenetId;
   public $status;
   public $dateTime;
   public $userId;
   public $notes;
   
   public function __construct()
   {
      $this->actionId = Action::UNKNOWN_ACTION_ID;
      $this->componentType = ComponentType::UNKNOWN;
      $this->componentId = Action::UNKNOWN_COMPONENT_ID;
      $this->status = Action::UNKNOWN_COMPONENT_STATUS;
      $this->dateTime = null;
      $this->userId = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->notes = null;
   }
   public function initialize($row)
   {
      $this->actionId = intval($row["actionId"]);
      $this->componentId = intval($row["componentId"]);
      $this->componentType = intval($row["componentType"]);
      $this->status = intval($row["status"]);
      $this->dateTime = $row["dateTime"] ?
                           Time::fromMySqlDate($row["dateTime"]) :
                           null;
      $this->userId = intval($row["userId"]);
      $this->notes = $row["notes"];      
   }
   
   // **************************************************************************
   // Component interface
   
   public static function load($actionId)
   {
      $action = null;
      
      $result = PPTPDatabaseAlt::getInstance()->getAction($actionId);
      
      if ($result && ($row = $result[0]))
      {
         $action = new Action();
         
         $action->initialize($row);
      }
      
      return ($action);
   }
   
   public static function save($action)
   {
      $success = false;
      
      if ($action->actionId == Action::UNKNOWN_ACTION_ID)
      {
         $success = PPTPDatabaseAlt::getInstance()->addAction($action);
      }
      else
      {
         $success = PPTPDatabaseAlt::getInstance()->updateAction($action);
      }
     
      return ($success);
   }
   
   public static function delete($actionId)
   {
      return (PPTPDatabaseAlt::getInstance()->deleteAction($actionId));
   }
}