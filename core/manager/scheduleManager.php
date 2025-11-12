<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/core/common/pptpDatabase.php';
require_once ROOT.'/core/component/scheduleEntry.php';

class ScheduleManager
{
   public static function getScheduledJobs($mfgDate)
   {
      $entries = array();
      
      if (!Time::isWeekend($mfgDate))  // TODO: Add weekend configuration to job.
      {
         $result = PPTPDatabaseAlt::getInstance()->getSchedule($mfgDate);
         
         foreach ($result as $row)
         {
            $scheduleEntry = ScheduleEntry::load(intval($row["entryId"]));
            
            $entries[] = $scheduleEntry;
         }
      }
      
      return ($entries);
   }
   
   public static function getUnscheduledJobs($mfgDate)
   {
      $jobs = array();
      
      $result = PPTPDatabase::getInstance()->getActiveJobs(null);  // Search all work centers.
      
      foreach ($result as $row)
      {
         $jobInfo = new JobInfo();
         $jobInfo->initialize($row);
         
         if (!ScheduleManager::isScheduled($jobInfo->jobId, $mfgDate))
         {
            $jobs[] = $jobInfo;
         }
      }
      
      return ($jobs);
   }
   
   public static function isScheduled($jobId, $mfgDate)
   {
      $result = PPTPDatabaseAlt::getInstance()->getScheduleForJob($jobId, $mfgDate);
      
      return ($result && (count($result) > 0) && !Time::isWeekend($mfgDate));  // TODO: Add weekend configuration to job.
   }
   
   public static function createTimeCard($scheduleEntryId)
   {
      $success = false;
      
      $scheduleEntry = ScheduleEntry::load($scheduleEntryId);
      
      if ($scheduleEntry)
      {
         $timeCardInfo = new TimeCardInfo();
         $timeCardInfo->dateTime = Time::now();
         $timeCardInfo->manufactureDate = Time::startOfDay(Time::now());
         $timeCardInfo->employeeNumber = $scheduleEntry->employeeNumber;
         $timeCardInfo->jobId = $scheduleEntry->jobId;
         $timeCardInfo->shiftTime = TimeCardInfo::DEFAULT_SHIFT_TIME;
         $timeCardInfo->runTime = TimeCardInfo::DEFAULT_RUN_TIME;
         
         $success = TimeCardInfo::save($timeCardInfo);
      }
      
      return ($success);
   }
}

?>