<?php

if (!defined('ROOT')) require_once '../../../root.php';
require_once ROOT.'/core/component/part.php';

class PartTest
{
   const PART_NUMBER = "M9999";
   
   const CUSTOMER_ID = 1;
   
   const CUSTOMER_NUMBER = "1234-567";
   
   const OTHER_CUSTOMER_ID = 2;
   
   const OTHER_CUSTOMER_NUMBER = "8901-123";
   
   public static function run()
   {
      echo "Running PartTest ...<br>";
      
      $test = new PartTest();
      
      $test->testSave_Add();
      
      if (PartTest::$newPartNumber != Part::UNKNOWN_PART_NUMBER)
      {
         $test->testLoad();
         
         $test->testSave_Update();
         
         $test->testDelete();
      }
   }
   
   public function testLoad()
   {
      echo "Part::load()<br>";
      
      $part = Part::load(PartTest::$newPartNumber);
      
      var_dump($part);
   }
   
   public function testSave_Add()
   {
      echo "Part::save(newPart)<br>";
      
      $part = new Part();
      
      $part->partNumber = PartTest::PART_NUMBER;      
      $part->customerId = PartTest::CUSTOMER_ID;
      $part->customerNumber = PartTest::CUSTOMER_NUMBER;
      
      if (Part::save($part))
      {
         PartTest::$newPartNumber = $part->partNumber;
         
         $part = Part::load(PartTest::$newPartNumber);
      }
      
      var_dump($part);      
   }
   
   public function testSave_Update()
   {
      echo "Part::save(existingPart)<br>";
      
      $part = Part::load(PartTest::$newPartNumber);
      
      $part->customerId = PartTest::OTHER_CUSTOMER_ID;
      $part->customerNumber = PartTest::OTHER_CUSTOMER_NUMBER;
      
      if (Part::save($part))
      {
         $part = Part::load(PartTest::$newPartNumber);
      }
            
      var_dump($part);
   }
   
   public function testDelete()
   {
      echo "Part::delete()<br>";
      
      Part::delete(PartTest::$newPartNumber);
   }
   
   private static $newPartNumber = 0;
}

PartTest::run();

?>