<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/permissions.php';

class PermissionsTest
{
   public static function run()
   {
      echo "Running PermissionsTest ...<br>";
      
      $test = new PermissionsTest();      
   }
}

session_start();

PermissionsTest::run();

?>
  