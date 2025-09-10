<?php

if (!defined('ROOT')) require_once '../../../root.php';
require_once ROOT.'/core/manager/correctiveActionManager.php';

class CorrectiveActionManagerTest
{
   public static function run()
   {
      echo "Running CorrectiveActionManagerTest ...<br>";
      
      $test = new CorrectiveActionManagerTest();
      
      $test->setup();
      
      $test->testGetCorrectiveActions();
      
      $test->testGetCorrectiveActions_AllActive();
      
      $test->teardown();
   }
   
   private static function testGetCorrectiveActions()
   {
      echo "CorrectiveActionManager::gGetCorrectiveActions()<br>";
      
      $dateTime = Time::dateTimeObject(null);
      
      $endDate = $dateTime->format(Time::STANDARD_FORMAT);
      $startDate = $dateTime->modify("-1 month")->format(Time::STANDARD_FORMAT); 
      
      $correctiveActions = CorrectiveActionManager::getCorrectiveActions(FilterDateType::OCCURANCE_DATE, $startDate, $endDate, false);
      
      var_dump($correctiveActions);
   }
   
   private static function testGetCorrectiveActions_AllActive()
   {
      echo "CorrectiveActionManager::gGetCorrectiveActions(allActive)<br>";
      
      $correctiveActions = CorrectiveActionManager::getCorrectiveActions(FilterDateType::OCCURANCE_DATE, null, null, true);
      
      var_dump($correctiveActions);
   }
         
   private function setup()
   {
   }
   
   private function teardown()
   {
   }
}
      
CorrectiveActionManagerTest::run();
      
?>