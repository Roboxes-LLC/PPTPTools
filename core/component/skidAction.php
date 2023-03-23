<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/time.php';
require_once ROOT.'/common/userInfo.php';
require_once ROOT.'/core/component/skid.php';

class SkidAction
{
   const UNKNOWN_ACTION_ID = 0;

   public $skidActionId;
   public $skidId;
   public $skidState;
   public $dateTime;
   public $author;
   public $notes;
   
   public function __construct()
   {
      $this->skidActionId = SkidAction::UNKNOWN_ACTION_ID;
      $this->skidId = Skid::UNKNOWN_SKID_ID;
      $this->skidState = SkidState::UNKNOWN;
      $this->dateTime = null;
      $this->author = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->notes = null;
      $this->attachment = null;
   }
   
   public function initialize($row)
   {
      $this->skidActionId = intval($row["skidActionId"]);
      $this->skidId = intval($row["skidId"]);
      $this->skidState = intval($row["skidState"]);
      $this->dateTime = $row["dateTime"] ?
                           Time::fromMySqlDate($row["dateTime"], "Y-m-d H:i:s") :
                           null;
      $this->author = intval($row["author"]);
      $this->notes = $row["notes"];      
   }
   
   // **************************************************************************
   // Component interface
   
   public static function load($skidActionId)
   {
      $skidAction = null;
      
      $result = PPTPDatabase::getInstance()->getSkidAction($skidActionId);
      
      if ($result && ($row = $result[0]))
      {
         $skidAction = new SkidAction();
         
         $skidAction->initialize($row);
      }
      
      return ($skidAction);
   }
   
   public static function save($skidAction)
   {
      $success = false;
      
      if ($skidAction->skidActionId == SkidAction::UNKNOWN_ACTION_ID)
      {
         $success = PPTPDatabase::getInstance()->newSkidAction($skidAction);
      }
      else
      {
         $success = PPTPDatabase::getInstance()->updateSkidAction($skidAction);
      }
     
      return ($success);
   }
   
   public static function delete($skidActionId)
   {
      return (PPTPDatabase::getInstance()->deleteSkidAction($skidActionId));
   }
}