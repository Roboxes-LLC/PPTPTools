<?php

if (!defined('ROOT')) require_once '../../../root.php';
require_once ROOT.'/core/component/skid.php';

class SkidTest
{
   const JOB_ID = 1;
   
   const OTHER_JOB_ID = 2;
   
   const AUTHOR_ID = 1975;
   
   const INVALID_SKID_ID = 99999;
   
   public static function run()
   {
      echo "Running SkidTest ...<br>";
      
      $test = new SkidTest();
      
      $test->testSave_Add();
      
      if (SkidTest::$newSkidId != Skid::UNKNOWN_SKID_ID)
      {
         $test->testLoad();
         
         $test->testSave_Update();
         
         //$test->testSubmit();
         
         //$test->testConfirm();
         
         //$test->testReceive();
         
         //$test->testCancel();
         
         $test->testSkidExists();
         
         $test->testGetCreatedAction();
         
         $test->testDelete();
      }
   }
   
   public function testLoad()
   {
      echo "Skid::load()<br>";
      
      $skid = Skid::load(SkidTest::$newSkidId);
      
      var_dump($skid);
   }
   
   public function testSave_Add()
   {
      echo "Skid::save(newSkid)<br>";
      
      $skid = new Skid();
      
      $skid->jobId = SkidTest::JOB_ID;
      
      if (Skid::save($skid))
      {
         SkidTest::$newSkidId = $skid->skidId;
         
         $skid = Skid::load(SkidTest::$newSkidId);
         
         $skid->create(Time::now("Y-m-d H:i:s"), SkidTest::AUTHOR_ID, "Created");
      }
      
      var_dump($skid);      
   }
   
   public function testSave_Update()
   {
      echo "Skid::save(existingSkid)<br>";
      
      $skid = Skid::load(SkidTest::$newSkidId);
      
      $skid->jobId = SkidTest::OTHER_JOB_ID;
      
      Skid::save($skid);
      
      var_dump($skid);
   }
      
   public function testSkidExists()
   {
      echo "Skid::skidExists()<br>";
      
      echo SkidTest::$newSkidId . " exists = " . (Skid::skidExists(SkidTest::$newSkidId) ? "true" : "false") . "<br>";
      
      echo SkidTest::INVALID_SKID_ID . " exists = " . (Skid::skidExists(SkidTest::INVALID_SKID_ID) ? "true" : "false") . "<br>";
   }
   
   public function testGetCreatedAction()
   {
      echo "Skid::testGetCreatedAction()<br>";
      
      $skid = Skid::load(SkidTest::$newSkidId);
      
      echo SkidTest::$newSkidId . " is created = " . ($skid->isCreated() ? "true" : "false") . "<br>";
      
      $skidAction = $skid->getCreatedAction();
      
      // Author name.
      $authorName = "<unknown>";
      $userInfo = UserInfo::load($skidAction->author);
      if ($userInfo)
      {
         $authorName = $userInfo->username;
      }
      
      // Formated date/time
      $dateTime = new DateTime($skidAction->dateTime, new DateTimeZone('America/New_York'));
      $formattedCreatedDate = $dateTime->format("n/j/Y g:i A");
      
      echo SkidTest::$newSkidId . " created by $authorName on $formattedCreatedDate<br>";
   }
   
   public function testDelete()
   {
      echo "Skid::delete()<br>";
      
      Skid::delete(SkidTest::$newSkidId);
   }
   
   private static $newSkidId = 0;
}

SkidTest::run();

?>