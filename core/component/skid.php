<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/skidState.php';
require_once ROOT.'/core/component/skidAction.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/common/jobInfo.php';

class Skid
{
   const UNKNOWN_SKID_ID = 0;
   
   public $skidId;
   public $jobId;
   public $skidState;
   
   public $contents;
   
   public $actions;
   
   public function __construct()
   {
      $this->skidId = Skid::UNKNOWN_SKID_ID;
      $this->jobId = JobInfo::UNKNOWN_JOB_ID;
      $this->skidState = SkidState::UNKNOWN;
      
      $this->contents = array();
      $this->actions = array();
   }
   
   // **************************************************************************
   // Component interface
   
   public function initialize($row)
   {
      $this->skidId = intval($row['skidId']);
      $this->jobId = intval($row['jobId']);
      $this->skidState =  intval($row['skidState']);
   }
   
   public static function load($skidId)
   {
      $skid = null;
      
      $result = PPTPDatabase::getInstance()->getSkid($skidId);
      
      if ($result && ($row = $result->fetch_assoc()))
      {
         $skid = new Skid();
         
         $skid->initialize($row);
         
         $skid->contents = Skid::getContents($skid->skidId);
         $skid->actions = Skid::getActions($skid->skidId);
      }
      
      return ($skid);
   }
   
   public static function save($skid)
   {
      $success = false;
      
      if ($skid->skidId == Skid::UNKNOWN_SKID_ID)
      {
         $success = PPTPDatabase::getInstance()->newSkid($skid);
         
         $skid->skidId = intval(PPTPDatabase::getInstance()->lastInsertId());
      }
      else
      {
         $success = PPTPDatabase::getInstance()->updateSkid($skid);
      }
      
      return ($success);
   }
   
   public static function delete($skidId)
   {
      return(PPTPDatabase::getInstance()->deleteSkid($skidId));
   }
   
   // **************************************************************************
   
   public function getSkidCode()
   {
      return (sprintf('%04X', $this->skidId));
   }
   
   public function create($dateTime, $employeeNumber, $notes)
   {
      return ($this->addSkidAction(SkidState::CREATED, $dateTime, $employeeNumber, $notes));
   }
   
   public function isCreated()
   {
      return (count($this->findActions(SkidState::CREATED)) > 0);
   }
   
   public function getCreatedAction()
   {
      $skidActions = $this->findActions(SkidState::CREATED);
      
      return ((count($skidActions) > 0) ? $skidActions[0] : null);
   }
   
   public static function getContents($skidId)
   {
      $actions = array();
      
      if ($skidId != Skid::UNKNOWN_SKID_ID)
      {
         $result = PPTPDatabase::getInstance()->getPartWasherEntriesBySkid($skidId);
         
         while ($row = $result->fetch_assoc())
         {
            $partWasherEntry = new PartWasherEntry();
            $partWasherEntry->initialize($row);
            $actions[] = $partWasherEntry;
         }
      }
      
      return ($actions);
   }
   
   public static function getActions($skidId)
   {
      $actions = array();
      
      if ($skidId != Skid::UNKNOWN_SKID_ID)
      {
         $result = PPTPDatabase::getInstance()->getSkidActions($skidId);
         
         while ($row = $result->fetch_assoc())
         {
            $skidAction = new SkidAction();
            $skidAction->initialize($row);
            $actions[] = $skidAction;
         }
      }
      
      return ($actions);
   }
   
   public function findActions($skidState)
   {
      $foundActions = array();
      
      foreach ($this->actions as $skidAction)
      {
         if ($skidAction->skidState == $skidState)
         {
            $foundActions[] = $skidAction;
         }
      }
      
      return ($foundActions);
   }
   
   public static function skidExists($skidId)
   {
      $result = PPTPDatabase::getInstance()->getSkid($skidId);
      
      return ($result && (PPTPDatabase::countResults($result) > 0));
   }
   
   public static function getOptions($selectedSkidId, $skidStates)
   {
      $html = "<option style=\"display:none\">";
      
      $result = PPTPDatabase::getInstance()->getSkidsByState($siteId, $skidStates);
      
      $skid = new Skid();
      
      foreach ($result as $row)
      {
         $skid->initialize($row);
         
         $selected = ($skid->skidId == $selectedSkidId) ? "selected" : "";
            
         $html .= "<option value=\"$skid->skidId\" $selected>$skid->skidId</option>";
      }
      
      return ($html);
   }
   
   // **************************************************************************
   
   private function addSkidAction($skidState, $dateTime, $employeeNumber, $notes = null)
   {
      $skidAction = new SkidAction();
      $skidAction->skidId = $this->skidId;
      $skidAction->skidState = $skidState;
      $skidAction->dateTime = $dateTime;
      $skidAction->author = $employeeNumber;
      $skidAction->notes = $notes;
      
      $success = SkidAction::save($skidAction);
      
      if ($success)
      {
         $this->actions[] = $skidAction;
         
         //$this->recalculateState();
         
         //$success &= PPTPDatabase::getInstance()->updateSkid($this);
      }
      
      return ($success);
   }
   
   private function removeSkidAction($skidActionId)
   {
      $success = SkidAction::delete($skidActionId);
      
      if ($success)
      {
         $this->actions = Skid::getActions($this->skidId);
         
         //$this->recalculateState();
         
         //$success &= PPTPDatabase::getInstance()->updateSkid($this);
      }
      
      return ($success);
   }
}

?>