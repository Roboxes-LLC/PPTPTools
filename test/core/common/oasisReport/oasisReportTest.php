<?php

if (!defined('ROOT')) require_once '../../../../root.php';
require_once ROOT.'/common/oasisReport/oasisReport.php';

class OasisReportTest
{
   // Test constants.
   private const OASIS_FILE = "M6459B Rev 18(12-06-2023 at 09-34 AM).rpt";
   
   public static function run()
   {
      echo "Running OasisReportTest ...<br>";
      
      $test = new OasisReportTest();
      
      $test->testParseFile();
   }
   
   private function testParseFile()
   {
      global $OASIS_REPORTS_DIR;
      
      echo "OasisReport::parseFile()<br>";
      
      $oasisReport = OasisReport::parseFile(ROOT.$OASIS_REPORTS_DIR.OasisReportTest::OASIS_FILE);
      
      if ($oasisReport)
      {
         var_dump($oasisReport);
      }
   }
}

OasisReportTest::run();

?>