<?php

if (!defined('ROOT')) require_once '../../../root.php';
require_once ROOT.'/core/common/correctiveActionDefs.php';

class CorrectiveActionDefsTest
{
   public static function run()
   {
      echo "Running CorrectiveActionDefsTest ...<br>";
      
      $test = new CorrectiveActionDefsTest();
      
      $test->testDisposition();
   }
   
   public function testDisposition()
   {
      echo "Disposition::getDispositions()<br>";
      
      $bitset = 0;
      
      Disposition::setDisposition(Disposition::USE_AS_IS, $bitset);
      Disposition::setDisposition(Disposition::RETURN_TO_VENDOR, $bitset);
      Disposition::setDisposition(Disposition::ISSUE_CREDIT, $bitset);
      
      $dispositions = Disposition::getDispositions($bitset);
      
      var_dump($dispositions);
      
      $options = Disposition::getOptions($dispositions);
      
      echo
 <<<HEREDOC
      <select style="height:150px" multiple>$options</select>
HEREDOC;
   }
}

CorrectiveActionDefsTest::run();

?>