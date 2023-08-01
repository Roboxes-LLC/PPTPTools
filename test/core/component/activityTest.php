<?php

if (!defined('ROOT')) require_once '../../../root.php';
require_once ROOT.'/core/component/activity.php';

class ActivityTest
{
   public static function run()
   {
      echo "Running ActivityTest ...<br>";
      
      $test = new ActivityTest();      
      
      $test->testSave_Add();
      
      if (ActivityTest::$newActivityId != 0)
      {      
         $test->testLoad();
         
         $test->testSave_Update();
                  
         $test->testDelete();
         
         $test->testGetDescription();
      }
   }
   
   public function testLoad()
   {
      echo "Activity::load()<br>";
      
      $activity = Activity::load(ActivityTest::$newActivityId);
      
      var_dump($activity);
   }
   
   public function testSave_Add()
   {
      echo "Activity::save(newActivity)<br>";
      
      $activity = new Activity();
      
      $activity->dateTime = Time::now();
      $activity->author = 1;
      $activity->activityType = ActivityType::ADD_USER;
      $activity->objects = [1, 2, 3];
      
      Activity::save($activity);
      
      var_dump($activity);
      
      ActivityTest::$newActivityId = $activity->activityId;
   }
   
   public function testSave_Update()
   {
      echo "Activity::save(existingActivity)<br>";
      
      $activity = Activity::load(ActivityTest::$newActivityId);
      
      $dt = Time::getDateTime(null);
      $dt->add(new DateInterval("P1D"));  // 1 day
      
      $activity->dateTime = $dt->format(Time::STANDARD_FORMAT);
      $activity->author = 2;
      $activity->activityType = ActivityType::ADD_SITE;
      $activity->objects = [4, 5, 6];
      
      Activity::save($activity);
      
      var_dump($activity);
   }
   
   public function testDelete()
   {
      echo "Activity::delete()<br>";
      
      Activity::delete(ActivityTest::$newActivityId);
   }
   
   public function testGetDescription()
   {
      $activity = new Activity();
      
      $dt = Time::getDateTime(null);
      
      $activity->siteId = 1;
      $activity->dateTime = $dt->format(Time::STANDARD_FORMAT);
      $activity->author = 1;
      $activity->activityType = ActivityType::LOG_IN;
      
      echo "Description: {$activity->getDescription()}<br>";
   }
   
   private static $newActivityId = 0;
}

ActivityTest::run();

?>