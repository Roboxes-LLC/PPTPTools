<?php

if (!defined('ROOT')) require_once '../../../root.php';
require_once ROOT.'/core/manager/userManager.php';

class UserManagerTest
{
   const SELECTED_USER = 1975;
   
   public static function run()
   {
      echo "Running UserManager ...<br>";
      
      $test = new UserManagerTest();
      
      $test->testGetOptions();
   }
   
   private static function testGetOptions()
   {
      echo "UserManager::testGetOptions()<br>";
      
      $options = UserManager::getOptions([Role::OPERATOR, Role::SHIPPER], [UserManagerTest::SELECTED_USER], UserManagerTest::SELECTED_USER);
      
      echo
<<<HEREDOC
      <select>
         $options
      </select>
HEREDOC;
   }
}
      
UserManagerTest::run();
      
?>