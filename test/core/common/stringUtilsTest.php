<?php

if (!defined('ROOT')) require_once '../../../root.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/common/materialHeatInfo.php';
require_once ROOT.'/core/common/stringUtils.php';

class StringUtilsTest
{
   public static function run()
   {
      echo "Running StringUtilsTest ...<br>";
      
      $test = new StringUtilsTest();
      
      $test->testDecimalToFraction();
   }
   
   public function testDecimalToFraction()
   {
      $result = PPTPDatabase::getInstance()->query("SELECT * from materialheat");
      
      echo "<table>";
      
      while ($result && ($row = $result->fetch_assoc()))
      {
         $materialHeatInfo = MaterialHeatInfo::load(intval($row["vendorHeatNumber"]));
         
         if ($materialHeatInfo)
         {
            $decimal = $materialHeatInfo->materialInfo->size;
            $fraction = StringUtils::decimalToFraction($decimal);

            echo "<tr><td>$decimal</td><td>&#x2192;</td><td>$fraction</td></tr>";
         }
      }
      
      echo "</table>";
   }
}

StringUtilsTest::run();

?>