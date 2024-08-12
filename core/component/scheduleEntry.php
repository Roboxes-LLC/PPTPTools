<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/jobInfo.php';
require_once ROOT.'/common/userInfo.php';
require_once ROOT.'/core/common/pptpDatabase.php';

class ScheduleEntry
{
   const UNKNOWN_ENTRY_ID = 0;
   
   public $entryId;
   public $jobId;
   public $employeeNumber;
   public $mfgDate;
   
   public $jobInfo;
   public $userInfo;
   
   public function __construct()
   {
      $this->entryId = ScheduleEntry::UNKNOWN_ENTRY_ID;
      $this->jobId = JobInfo::UNKNOWN_JOB_ID;
      $this->employeeNumber = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->mfgDate = null;
      
      $this->jobInfo = null;
      $this->userInfo = null;
   }
   
   public function initialize($row)
   {
      $this->entryId = intval($row["entryId"]);
      $this->jobId = intval($row["jobId"]);
      $this->employeeNumber = intval($row["employeeNumber"]);
      $this->mfgDate = $row["mfgDate"] ?
                            Time::fromMySqlDate($row["mfgDate"]) :
                            null;
      
      $this->jobInfo = null;
      $this->userInfo = null;
   }
   
   // **************************************************************************
   // Component interface
   
   public static function load($entryId)
   {
      $scheduleEntry = null;
      
      $result = PPTPDatabaseAlt::getInstance()->getScheduleEntry($entryId);
      
      if ($result && ($row = $result[0]))
      {
         $scheduleEntry = new ScheduleEntry();
         
         $scheduleEntry->initialize($row);
         
         $scheduleEntry->jobInfo = JobInfo::load($scheduleEntry->jobId);
         $scheduleEntry->userInfo = UserInfo::load($scheduleEntry->employeeNumber);
      }
      
      return ($scheduleEntry);
   }
   
   public static function save($scheduleEntry)
   {
      $success = false;
      
      if ($scheduleEntry->entryId == ScheduleEntry::UNKNOWN_ENTRY_ID)
      {
         $success = PPTPDatabaseAlt::getInstance()->addScheduleEntry($scheduleEntry);
      }
      else
      {
         $success = PPTPDatabaseAlt::getInstance()->updateScheduleEntry($scheduleEntry);
      }
      
      return ($success);
   }
   
   public static function delete($entryId)
   {
      return (PPTPDatabaseAlt::getInstance()->deleteScheduleEntry($entryId));
   }
}