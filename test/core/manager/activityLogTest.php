<?php

if (!defined('ROOT')) require_once '../../../root.php';
require_once ROOT.'/core/manager/activityLog.php';

class ActvityLogTest
{
   const USER_ID = 51;
   const MAX_HISTORY_ENTRIES = 10;
   
   public static function run()
   {
      echo "Running ActvityLogTest ...<br>";
      
      $test = new ActvityLogTest();
      
      $test->testGetActivitiesForUser();
   }
   
   private static function testGetActivitiesForUser()
   {
      $dateTime = Time::dateTimeObject(null);
      
      $endDateTime = $dateTime->format(Time::STANDARD_FORMAT);
      $startDateTime = $dateTime->modify("-1 month")->format(Time::STANDARD_FORMAT); 
      
      $activities = ActivityLog::getActivitiesForUser($startDateTime, $endDateTime, ActvityLogTest::USER_ID);
      
      $entryCount = 0;
      
      foreach ($activities as $activity)
      {
         if ($entryCount < ActvityLogTest::MAX_HISTORY_ENTRIES)
         {
            echo "{$activity->getDescription()}<br>";
            
            $entryCount++;
         }
      }
   }
         
   private function setup()
   {
   }
   
   private function teardown()
   {
   }
   
   private $purchaseOrders = array();
}
      
ActvityLogTest::run();
      
?>