<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/params.php';
require_once ROOT.'/core/manager/userManager.php';

abstract class AuthenticationResult
{
   const AUTHENTICATED = 0;
   const INVALID_USERNAME = 1;
   const INVALID_PASSWORD = 2;
   const INVALID_AUTH_TOKEN = 3;
}

class Authentication
{
   const AUTH_TOKEN_LENGTH = 32;
   
   static public function isAuthenticated()
   {
      return (isset($_SESSION["authenticated"]) && ($_SESSION["authenticated"] == true));
   }
   
   static public function getAuthenticatedUser()
   {
      $authenticatedUser = null;
      
      if (isset($_SESSION["authenticatedUser"]) && ($_SESSION["authenticatedUser"] == true))
      {
         $authenticatedUser = UserInfo::loadByName($_SESSION["authenticatedUser"]);
      }
      
      return ($authenticatedUser);
   }
   
   static public function getPermissions()
   {
      $permissions = 0;
      
      if (isset($_SESSION["permissions"]))
      {
         $permissions= $_SESSION["permissions"];
      }
      
      return ($permissions);
   }
   
   static public function authenticate()
   {
      $result = AuthenticationResult::INVALID_USERNAME;
      
      $params = Params::parse();
      
      if ($params->keyExists("username") && $params->keyExists("password"))
      {
         $result = Authentication::authenticateUser($params->get("username"), $params->get("password"));
      }
      else if ($params->keyExists("authToken"))
      {
         $result = Authentication::authenticateToken($params->get("authToken"));
      }
      
      return ($result);
   }
   
   static public function authenticateUser($username, $password)
   {
      $result = AuthenticationResult::INVALID_USERNAME;
      
      $user = UserInfo::loadByName($username);
      
      if ($user == null)
      {
         $result = AuthenticationResult::INVALID_USERNAME;
      }
      else if ($password != $user->password)
      {
         $result = AuthenticationResult::INVALID_PASSWORD;
      }
      else
      {
         $result = AuthenticationResult::AUTHENTICATED;
         
         // Record authentication status and user name.
         $_SESSION['authenticated'] = true;
         $_SESSION['authenticatedUser'] = $username;
         $_SESSION["permissions"] = $user->permissions;
      }
      
      return ($result);
   }
   
   static public function authenticateToken($authToken)
   {
      $result = AuthenticationResult::INVALID_AUTH_TOKEN;
      
      $users = UserManager::getUsers();
      
      foreach ($users as $user)
      {
         if (($user->authToken != "") &&
             ($authToken == $user->authToken))
         {
            $result = AuthenticationResult::AUTHENTICATED;
            
            // Record authentication status and user name.
            $_SESSION['authenticated'] = true;
            $_SESSION['authenticatedUser'] = $user->username;
            $_SESSION["permissions"] = $user->permissions;
         }
      }
      
      return ($result);
   }
   
   static public function deauthenticate()
   {
      $_SESSION['authenticated'] = false;
      unset($_SESSION['authenticatedUser']);
   }
   
   static public function checkPermissions($permissionId)
   {
      $permission = Permission::getPermission($permissionId)->bits;
      $userPermissions = Authentication::getPermissions();
      
      return (($userPermissions & $permission) > 0);
   }
}
?>