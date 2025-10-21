<?php

if (!defined('ROOT')) require_once '../../../root.php';
require_once ROOT.'/core/common/role.php';

class RoleTest
{
   public static function run()
   {
      echo "Running RoleTest ...<br>";
      
      $test = new RoleTest();
      
      $test->testGetOptions();
      
      $test->testGetRolesFromBitset();
   }
   
   public function testGetOptions()
   {
      $options = Role::getOptions([Role::LABORER, Role::PART_WASHER]);
      
      echo
<<<HEREDOC
      <select multiple style="height:160px">
         $options
      </select>
HEREDOC;
   }
   
   public function testGetRolesFromBitset()
   {
      $roleIds = Role::getRolesFromBitset(Role::ALL_ROLES);
      
      var_dump($roleIds);
   }
}

RoleTest::run();

?>