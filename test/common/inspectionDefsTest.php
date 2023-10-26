<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/inspectionDefs.php';

class InspectionDefsTest
{
   const SELECTED_SPECIFICATION = Specification::CONCENTRICITY;

   const UNDEFINED_SELECTED_SPECIFICATION_LABEL = "Fudginess";
   
   public static function run()
   {
      echo "Running InspectionDefsTest ...<br>";
      
      $test = new InspectionDefsTest();
      
      $test->testSpecificationOptions();
   }
   
   private function testSpecificationOptions()
   {
      echo "Specification::getOptions() (defined specification)<br>";
      
      $options = Specification::getOptions(Specification::getLabel(InspectionDefsTest::SELECTED_SPECIFICATION));
      
      echo
<<<HEREDOC
      <select>$options</select>
      </br>
HEREDOC;
      
      echo "Specification::getOptions() (undefined specification)<br>";
      
      $options = Specification::getOptions(InspectionDefsTest::UNDEFINED_SELECTED_SPECIFICATION_LABEL);
      
      echo
<<<HEREDOC
      <select>$options</select>
HEREDOC;
   }
}

InspectionDefsTest::run();

?>