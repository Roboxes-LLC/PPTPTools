<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/timeCardInfo.php';
require_once ROOT.'/core/job/job.php';
require_once ROOT.'/core/manager/scheduleManager.php';

class SchedulerJob extends Job
{
   public function __construct()
   {
      parent::__construct();
      
      $this->jobClass = "SchedulerJob";
   }
   
   public function initialize($row)
   {
      parent::initialize($row); 
   }
   
   public function run()
   {
      echo "Running SchedulerJob!<br>";
      
      $schedule = ScheduleManager::getScheduledJobs(Time::now());
      $scheduledCount = 0;
      $timeCardCount = 0;
      
      foreach ($schedule as $scheduleEntry)
      {
         if ($scheduleEntry->employeeNumber != UserInfo::UNKNOWN_EMPLOYEE_NUMBER)
         {
            $scheduledCount++;
         
            if (ScheduleManager::createTimeCard($scheduleEntry->entryId))
            {
               $timeCardCount++;
            }
         }
      }
      
      if ($scheduledCount == 0)
      {
         echo "No scheduled jobs.<br>";
      }
      else
      {
         echo "Created time cards for $timeCardCount/$scheduledCount scheduled jobs.<br>";
      }
      
      //ActivityLog::
   }
   
   // **************************************************************************
   
   public static function test()
   {
      $job = new SchedulerJob();
      $job->run();
   }
}

// Testing
//SchedulerJob::test();

?>