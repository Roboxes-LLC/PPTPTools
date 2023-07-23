<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/core/job/job.php';
require_once ROOT.'/core/job/printerMonitorJob.php';

class JobManager
{
   public static function getJobs()
   {
      $jobs = array();
      
      $result = PPTPDatabase::getInstance()->getCronJobs();
      
      foreach ($result as $row)
      {
         $jobClass = $row["jobClass"];
         
         if (class_exists($jobClass))
         {
            $job = new $jobClass;
            
            if ($job)
            {
               $job->initialize($row);
               
               $jobs[] = $job;
            }
         }
      }
      
      return ($jobs);
   }
   
   public static function update()
   {
      $jobs = JobManager::getJobs();
      
      foreach ($jobs as $job)
      {
         $job->update();
      }
   }
}