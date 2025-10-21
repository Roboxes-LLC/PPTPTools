<?php

if (!defined('ROOT')) require_once '../../../root.php';
require_once ROOT.'/common/permissions.php';

class Role
{
   const UNKNOWN     = 0;
   const FIRST       = 1;
   const SUPER_USER = Role::FIRST;
   const ADMIN       = 2;
   const OPERATOR    = 3;
   const LABORER     = 4;
   const PART_WASHER = 5;
   const SHIPPER     = 6;
   const INSPECTOR   = 7;
   const MAINTENANCE = 8;
   const VENDOR      = 9;
   const LAST        = 10;
   
   const NO_ROLES = 0x0;
   const ALL_ROLES = 0xFFF;
   
   const BASIC_PERMISSIONS =       [Permission::VIEW_USER];
   const OPERATOR_PERMISSIONS =    [Permission::VIEW_TIME_CARD, Permission::EDIT_TIME_CARD];
   const LABORER_PERMISSIONS =     [Permission::VIEW_PART_WEIGHT_LOG, Permission::EDIT_PART_WEIGHT_LOG];
   const PART_WASHER_PERMISSIONS = [Permission::VIEW_PART_WASHER_LOG, Permission::EDIT_PART_WASHER_LOG];
   const SHIPPER_PERMISSIONS =     [Permission::VIEW_SHIPPING_CARD, Permission::EDIT_SHIPPING_CARD];
   const INSPECTOR_PERMISSIONS =   [Permission::VIEW_INSPECTION, Permission::EDIT_INSPECTION];
   const MAINTENANCE_PERMISSIONS = [Permission::VIEW_MAINTENANCE_LOG, Permission::EDIT_MAINTENANCE_LOG];
   const VENDOR_PERMISSIONS =      [];
      
   public static $values = 
      array(
         Role::SUPER_USER, 
         Role::ADMIN, 
         Role::OPERATOR, 
         Role::LABORER, 
         Role::PART_WASHER, 
         Role::SHIPPER, 
         Role::INSPECTOR,
         Role::MAINTENANCE,
         Role::VENDOR,
      );   
   
   public $roleId;
   
   public $label;
   
   public $defaultPermissions;
   
   public $bits;
      
   public static function getRoles()
   {
      if (Role::$roles == null)
      {
         Role::$roles = 
            array(
               Role::SUPER_USER =>  new Role(Role::SUPER_USER,  "Super User",  Permission::ALL_PERMISSIONS),
               Role::ADMIN =>       new Role(Role::ADMIN,       "Admin",       Permission::ALL_PERMISSIONS),
               Role::OPERATOR =>    new Role(Role::OPERATOR,    "Operator",    Permission::getBits(...Role::BASIC_PERMISSIONS) | Permission::getBits(...Role::OPERATOR_PERMISSIONS)),
               Role::LABORER =>     new Role(Role::LABORER,     "Laborer",     Permission::getBits(...Role::BASIC_PERMISSIONS) | Permission::getBits(...Role::LABORER_PERMISSIONS)),
               Role::PART_WASHER => new Role(Role::PART_WASHER, "Part Washer", Permission::getBits(...Role::BASIC_PERMISSIONS) | Permission::getBits(...Role::PART_WASHER_PERMISSIONS)),
               Role::SHIPPER =>     new Role(Role::SHIPPER,     "Shipper",     Permission::getBits(...Role::BASIC_PERMISSIONS) | Permission::getBits(...Role::SHIPPER_PERMISSIONS)),
               Role::INSPECTOR =>   new Role(Role::INSPECTOR,   "Inspector",   Permission::getBits(...Role::BASIC_PERMISSIONS) | Permission::getBits(...Role::INSPECTOR_PERMISSIONS)),
               Role::MAINTENANCE => new Role(Role::MAINTENANCE, "Maintenance", Permission::getBits(...Role::BASIC_PERMISSIONS) | Permission::getBits(...Role::MAINTENANCE_PERMISSIONS)),
               Role::VENDOR =>      new Role(Role::VENDOR,      "Vendor",      Permission::getBits(...Role::BASIC_PERMISSIONS) | Permission::getBits(...Role::VENDOR_PERMISSIONS))
            );
      }
      
      return (Role::$roles);
   }
   
   public static function getRole($roleId)
   {
      $role = null;
      
      if (($roleId >= Role::FIRST) && ($roleId < Role::LAST))
      {
         $role = Role::getRoles()[$roleId];
      }
      
      return ($role);
   }
   
   public function isSetIn($mask)
   {
      return (($this->bits & $mask) > 0);
   }
   
   public static function getBits(...$roleIds)
   {
      $bits = Role::NO_ROLES;
      
      foreach ($roleIds as $roleId)
      {
         $bits |= Role::getRole($roleId)->bits;
      }
      
      return ($bits);
   }
   
   public static function getRolesFromBitset($roleBits)
   {
      $roleIds = [];
      
      foreach (Role::$values as $roleId)
      {
         if (Role::hasRole($roleBits, $roleId))
         {
            $roleIds[] = $roleId;
         }
      }
      
      return ($roleIds);
   }
   
   public static function setRoleInBitset($roleId, &$roleBits)
   {
      $roleBits |= (1 << ($roleId - 1));
   }
   
   public static function hasRole($roleBits, $roleId)
   {
      return (($roleBits & (1 << ($roleId - 1))) > 0);
   }
   
   public function hasPermission($permissionId)
   {
      $permission = Permission::getPermission($permissionId);
      
      return ($permission->isSetIn($this->defaultPermissions));
   }
   
   public static function getLabel($roleId)
   {
      $label = "";
      
      $role = Role::getRole($roleId);
      
      if ($role)
      {
         $label = $role->label;
      }
      
      return ($label);
   }
   
   public static function getOptions($selectedRoles)
   {
      $html = "<option style=\"display:none\">";
      
      foreach (Role::getRoles() as $role)
      {
         $selected = in_array($role->roleId, $selectedRoles) ? "selected" : "";
         
         $html .= "<option value=\"$role->roleId\" $selected>$role->label</option>";
      }
      
      return ($html);
   }
   
   public function getInputName()
   {
      $inputName = "role" . $this->label;
      $inputName = str_replace(' ', '', $inputName);  // Strip out spaces.
      
      return ($inputName);
   }
   
   public static function getJavascriptDefaultPermissions($varName)
   {
      $html = "$varName = [";
      
      for ($roleId = Role::FIRST; $roleId < Role::LAST; $roleId++)
      {
         $role = Role::getRole($roleId);
         
         $html .= $role->defaultPermissions;
         $html .= ($roleId < (Role::LAST - 1)) ? ", " : "";
      }
      
      $html .= "];";
      
      return ($html);
   }
   
   // ********************************************************************************
   
   private static $roles = null;
      
   private function __construct($roleId, $label, $defaultPermissions)
   {
      $this->roleId = $roleId;
      $this->label = $label;
      $this->defaultPermissions = $defaultPermissions;
      
      if ($roleId > ROLE::UNKNOWN)
      {
         $this->bits = (1 << ($roleId - Role::FIRST));
      }
      else
      {
         $this->bits = 0;
      }
   }
}

?>
