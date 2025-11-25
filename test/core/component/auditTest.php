<?php

if (!defined('ROOT')) require_once '../../../root.php';
require_once ROOT.'/core/component/audit.php';

class AuditTest
{
   public static function run()
   {
      echo "Running AuditTest ...<br>";
      
      $test = new AuditTest();
      
      $test->testCreateLineItems();
   }
   
   public function testCreateLineItems()
   {
      echo "Audit::createLineItems()<br>";
      
      $audit = new Audit();
      $audit->auditId = 5;
      $audit->location = ShipmentLocation::WIP;
      $audit->partNumber = "M6487";
      
      $audit->createLineItems();
      
      var_dump($audit);
   }
   
   private static $newAudit = Audit::UNKNOWN_AUDIT_ID;
}

AuditTest::run();

?>