<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/materialInfo.php';

class MaterialInfoTest
{
   public static function run()
   {
      echo "Running MaterialInfoTest ...<br>";
      
      $test = new MaterialInfoTest();
      
      $test->testMaterialPartNumbers();
   }
   
   private function testMaterialPartNumbers()
   {
      echo "MaterialPartNumber::getOptions()<br>";
      
      $options = MaterialPartNumber::getOptions(null);
      
      echo
<<<HEREDOC
      <select>$options</select>
      </br>
HEREDOC;
   }
}

MaterialInfoTest::run();