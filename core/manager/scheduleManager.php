<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/core/common/pptpdatabase.php';
require_once ROOT.'/core/component/scheduleEntry.php';

class ScheduleManager
{
   public static function getScheduledJobs($dateTime)
   {
      $entries = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getSchedule($dateTime);
      
      foreach ($result as $row)
      {
         $scheduleEntry = ScheduleEntry::load(intval($row["entryId"]));
         
         $entries[] = $scheduleEntry;
      }
      
      return ($entries);
   }
   
   public static function getUnscheduledJobs($dateTime)
   {
      $jobs = array();
      
      $result = PPTPDatabase::getInstance()->getActiveJobs(null);  // Search all work centers.
      
      foreach ($result as $row)
      {
         $jobInfo = new JobInfo();
         $jobInfo->initialize($row);
         
         if (!ScheduleManager::isScheduled($jobInfo->jobId, $dateTime))
         {
            $jobs[] = $jobInfo;
         }
      }
      
      return ($jobs);
   }
   
   public static function isScheduled($jobId, $date)
   {
      $result = PPTPDatabaseAlt::getInstance()->getScheduleForJob($jobId, $date);
      
      return ($result && (count($result) > 0));
   }
}

?>