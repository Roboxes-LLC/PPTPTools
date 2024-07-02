<?php

if (!defined('ROOT')) require_once '../../../root.php';
require_once ROOT.'/core/manager/scheduleManager.php';

class ScheduleManagerTest
{
   public static function run()
   {
      echo "Running ScheduleManagerTest ...<br>";
      
      $test = new ScheduleManagerTest();
      
      $test->testGetScheduledJobs();
   
      $test->testGetUnscheduledJobs();
   }
   
   private static function testGetScheduledJobs()
   {
      echo "ScheduleManager::getScheduledJobs()<br>";
      
      $schedule = ScheduleManager::getScheduledJobs(Time::now());
      
      var_dump($schedule);
   }
   
   private static function testGetUnscheduledJobs()
   {
      echo "ScheduleManager::getUnscheduledJobs()<br>";
      
      $schedule = ScheduleManager::getUnscheduledJobs(Time::now());
      
      var_dump($schedule);
   }
}
      
ScheduleManagerTest::run();
      
?>