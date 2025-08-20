<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/common/permissions.php';

class Role
{
   const UNKNOWN     = 0;
   const FIRST       = 1;
   const SUPER_USER  = Role::FIRST;
   const ADMIN       = 2;
   const OPERATOR    = 3;
   const LABORER     = 4;
   const PART_WASHER = 5;
   const SHIPPER     = 6;
   const INSPECTOR   = 7;
   const LAST        = Role::INSPECTOR;
   
   public $roleId;
   
   public $roleName;
   
   public $defaultPermissions;
   
   public $defaultAppPage;
   
   public static $allRoles = [Role::SUPER_USER, Role::ADMIN, Role::OPERATOR, Role::LABORER, Role::PART_WASHER, Role::SHIPPER, Role::INSPECTOR];
      
   public static function getRoles()
   {
      if (Role::$roles == null)
      {
         Role::$roles = 
            array(new Role(Role::SUPER_USER,  "Super User",  Permission::ALL_PERMISSIONS,                                                                                                                             AppPage::REPORT),
                  new Role(Role::ADMIN,       "Admin",       Permission::ALL_PERMISSIONS,                                                                                                                             AppPage::REPORT),
                  new Role(Role::OPERATOR,    "Operator",    Permission::getBits(Permission::VIEW_TIME_CARD, Permission::EDIT_TIME_CARD, Permission::VIEW_PART_INSPECTION),                                           AppPage::TIME_CARD),
                  new Role(Role::LABORER,     "Laborer",     Permission::getBits(Permission::VIEW_PART_WEIGHT_LOG, Permission::EDIT_PART_WEIGHT_LOG),                                                                 AppPage::PART_WEIGHT),
                  new Role(Role::PART_WASHER, "Part Washer", Permission::getBits(Permission::VIEW_PART_WASHER_LOG, Permission::EDIT_PART_WASHER_LOG),                                                                 AppPage::PART_WASH),
                  new Role(Role::SHIPPER,     "Shipper",     Permission::getBits(Permission::VIEW_PART_WASHER_LOG, Permission::EDIT_PART_WASHER_LOG, Permission::VIEW_SHIPPING_CARD, Permission::EDIT_SHIPPING_CARD), AppPage::SHIPPING_CARD),
                  new Role(Role::INSPECTOR,   "Inspector",   Permission::getBits(Permission::VIEW_PART_INSPECTION, Permission::VIEW_INSPECTION, Permission::EDIT_INSPECTION),                                         AppPage::INSPECTION)
            );
      }
      
      return (Role::$roles);
   }
   
   public static function getRole($roleId)
   {
      $role = new Role(Role::UNKNOWN, "", Permission::NO_PERMISSIONS, AppPage::UNKNOWN);
      
      if (($roleId >= Role::FIRST) && ($roleId <= Role::LAST))
      {
         $role = Role::getRoles()[$roleId - Role::FIRST];
      }
      
      return ($role);
   }
   
   public function hasPermission($permissionId)
   {
      $permission = Permission::getPermission($permissionId);
      
      return ($permission->isSetIn($this->defaultPermissions));
   }
   
   private static $roles = null;
      
   private function __construct($roleId, $roleName, $defaultPermissions, $defaultAppPage)
   {
      $this->roleId = $roleId;
      $this->roleName = $roleName;
      $this->defaultPermissions = $defaultPermissions;
      $this->defaultAppPage = $defaultAppPage;
   }
}

/*
$role = Role::getRole(Role::PART_WASHER);

foreach (Permission::getPermissions() as $permission)
{
   $isSet = $permission->isSetIn($role->defaultPermissions) ? "set" : "";
   echo "{$permission->permissionName}: $isSet<br/>";
}

$isEditPartWasherLogSet = $role->hasPermission(Permission::EDIT_PART_WASHER_LOG) ? "is set" : "is not set";
$isEditPartWeightLogSet = $role->hasPermission(Permission::EDIT_PART_WEIGHT_LOG) ? "is set" : "is not set";

echo ("EDIT_PART_WASHER_LOG = $isEditPartWasherLogSet<br/>");
echo ("EDIT_PART_WEIGHT_LOG = $isEditPartWeightLogSet<br/>");
*/