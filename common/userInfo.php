<?php

require_once 'database.php';
require_once 'permissions.php';
require_once 'roles.php';

class UserInfo
{
   const UNKNOWN_EMPLOYEE_NUMBER = 0;
   
   const SYSTEM_EMPLOYEE_NUMBER = 1000000;
   
   public $employeeNumber;
   public $username;
   public $password;
   public $firstName;
   public $lastName;
   public $roles;
   public $permissions;
   public $email;
   public $authToken;
   public $defaultShiftHours;
   public $notifications;
   
   public function __construct()
   {
      $this->employeeNumber = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->username = null;
      $this->password = null;
      $this->firstName = null;
      $this->roles = Role::UNKNOWN;
      $this->permissions = Permission::NO_PERMISSIONS;
      $this->email = null;
      $this->authToken = null;
      $this->defaultShiftHours = 0;
      $this->notifications = 0;  // Notification::NO_NOTIFICATIONS;
   }
   
   public static function load($employeeNumber)
   {
      $userInfo = null;
      
      if ($employeeNumber == UserInfo::SYSTEM_EMPLOYEE_NUMBER)
      {
         $userInfo = UserInfo::getSystemUser();
      }
      else
      {
         $result = PPTPDatabase::getInstance()->getUser($employeeNumber);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $userInfo = new UserInfo();
            
            $userInfo->initialize($row);
         }
      }
      
      return ($userInfo);
   }
   
   public function initialize($row)
   {
      $this->employeeNumber = intval($row['employeeNumber']);
      $this->username = $row['username'];
      $this->password = $row['password'];
      $this->roles = intval($row['roles']);
      $this->permissions = intval($row['permissions']);
      $this->firstName = $row['firstName'];
      $this->lastName = $row['lastName'];
      $this->email = $row['email'];
      $this->authToken = $row['authToken'];
      $this->defaultShiftHours = intval($row['defaultShiftHours']);
      $this->notifications = intval($row['notifications']);
   }
   
   public static function getSystemUser()
   {
      static $systemUser = null;
      
      if ($systemUser == null)
      {
         $systemUser = new UserInfo();
         $systemUser->employeeNumber = UserInfo::SYSTEM_EMPLOYEE_NUMBER;
         $systemUser->username = "System";
         $systemUser->firstName = "System";
         $systemUser->lastNme = "";
         $systemUser->permissions = Permission::ALL_PERMISSIONS;
         $systemUser->roles = Role::SUPER_USER;
      }
      
      return ($systemUser);
   }
   
   static public function loadByName($username)
   {
      $userInfo = null;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getUserByName($username);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $userInfo = new UserInfo();
            
            $userInfo->initialize($row);
         }
      }
      
      return ($userInfo);
   }
   
   static public function getUsersByRole($role)
   {
      $users = array();
      
      $database = PPTPDatabase::getInstance();
           
      if ($database && $database->isConnected())
      {
         $result = $database->getUsersByRole($role);

         if ($result)
         {
            while ($row = $result->fetch_assoc())
            {
               $userInfo = new UserInfo();
               
               $userInfo->initialize($row);
               
               $users[] = $userInfo;
            }
         }
      }
      
      return ($users);
   }
   
   static public function getUsersByRoles($roles)
   {
      $users = array();
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getUsersByRoles($roles);
         
         if ($result)
         {
            while ($row = $result->fetch_assoc())
            {
               $userInfo = new UserInfo();
               
               $userInfo->initialize($row);
               
               $users[] = $userInfo;
            }
         }
      }
      
      return ($users);
   }
   
   public static function getUsername($employeeeNumber)
   {
      $username = "";
      
      if ($employeeeNumber == UserInfo::SYSTEM_EMPLOYEE_NUMBER)
      {
         $username = UserInfo::getSystemUser()->username;
      }
      else
      {
         $userInfo = UserInfo::load($employeeeNumber);
         
         if ($userInfo)
         {
            $username = $userInfo->username;
         }
      }
      
      return ($username);
   }
   
   public function getFullName()
   {
      return ($this->firstName . " " . $this->lastName);
   }
   
   
   public static function getLink($employeeNumber)
   {
      $html = "";
      
      $userInfo = UserInfo::load($employeeNumber);
      if ($userInfo)
      {
         $label = $userInfo->username;
         
         $html = "<a href=\"/user/viewUser.php?employeeNumber=$employeeNumber\">$label</a>";
      }
      
      return ($html);
   }
   
   public static function getOptions($roles, $includeUsers, $selectedEmployeeNumber)
   {
      $html = "<option style=\"display:none\">";
      
      $users = [];
      
      // Manually add any included users not covered under the specified roles.
      foreach ($includeUsers as $employeeNumber)
      {
         $userInfo = UserInfo::load($employeeNumber);
         if ($userInfo && !in_array($userInfo->roles, $roles))
         {
            $users[] = $userInfo;
         }
      }

      // Merge that with all  users that match the roles.
      $users = array_merge($users, UserInfo::getUsersByRoles($roles));

      // Build the options.
      foreach ($users as $userInfo)
      {
         $fullName = $userInfo->getFullName();
         $selected = ($userInfo->employeeNumber == $selectedEmployeeNumber) ? "selected" : "";
         
         $html .= "<option value=\"$userInfo->employeeNumber\" $selected>$fullName</option>";
      }
      
      return ($html);
   }
}

/*
$userInfo = null;

if (isset($_GET["employeeNumber"]))
{
   $employeeNumber = $_GET["employeeNumber"];
   $userInfo = UserInfo::load($employeeNumber);
}
else if (isset($_GET["username"]))
{
   $username = $_GET["username"];
   $userInfo = UserInfo::loadByName($username);
}
    
if ($userInfo)
{
   echo "employeeNumber: " . $userInfo->employeeNumber . "<br/>";
   echo "username: " .       $userInfo->username .       "<br/>";
   echo "password: " .       $userInfo->password .       "<br/>";
   echo "roles: " .          $userInfo->roles .          "<br/>";
   echo "permissions: " .    $userInfo->permissions .    "<br/>";
   echo "firstName: " .      $userInfo->firstName .      "<br/>";
   echo "lastName: " .       $userInfo->lastName .       "<br/>";
   echo "email: " .          $userInfo->email .          "<br/>";
   echo "authToken: " .      $userInfo->authToken .      "<br/>";
   echo "defaultShiftHours: " . $userInfo->defaultShiftHours . "<br/>";
   
   echo "fullName: " . $userInfo->getFullName() . "<br/>";
}
else
{
   echo "No user found.";
}
*/
?>