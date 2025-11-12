<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/time.php';

class TimeTest
{
   public static function run()
   {
      echo "Running TimeTest ...<br>";
      
      $test = new TimeTest();
      
      $test->testIsWeekend();
   }
   
   private function testIsWeekend()
   {
      echo "Time::isWeekend()<br>";
      
      $dateStrings = ["2025-11-7", "2025-11-8", "2025-11-9", "2025-11-10"];
      
      foreach ($dateStrings as $dateString)
      {
         $isWeekend = Time::isWeekend($dateString);
         $not = $isWeekend ? "" : "NOT ";
         
         echo "$dateString does {$not}fall on a weekend<br>";
      }
   }
}

TimeTest::run();

?>