<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/common/userInfo.php';

class UserManager
{
   public static function getUsers($includeDeleted = false)
   {
      $users = array();

      $result = PPTPDatabase::getInstance()->getUsers($includeDeleted);
      
      while ($result && ($row = $result->fetch_assoc()))
      {
         $user = new UserInfo();
         $user->initialize($row);

         $users[] = $user;
      }

      return ($users);
   }
   
   public static function getUsersByRoles($roleIds, $includeDeleted = false)
   {
      $users = array();
      
      $result = PPTPDatabase::getInstance()->getUsers($includeDeleted);
      
      while ($result && ($row = $result->fetch_assoc()))
      {
         $user = new UserInfo();
         $user->initialize($row);
         
         foreach ($roleIds as $roleId)
         {
            if (Role::hasRole($user->roles, $roleId))
            {
               $users[] = $user;
               break;
            }
         }
      }
      
      return ($users);
   }
   
   public static function getUsersForNotification($notificationId)
   {
      $users = array();
      
      $notification = Notification::getNotification($notificationId);
      
      if ($notification)
      {
         $result = PPTPDatabase::getInstance()->getUsers();
         
         while ($result && ($row = $result->fetch_assoc()))
         {
            $user = new UserInfo();
            $user->initialize($row);
            
            if ($notification->isSetIn($user->notifications))
            {
               $users[] = $user;
            }
         }
      }
      
      return ($users);
   }
   
   public static function getOperators()
   {
      return (getUsersByRoles([Role::OPERATOR]));
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
      $users = array_merge($users, UserManager::getUsersByRoles($roles));
      
      // Build the options.
      foreach ($users as $userInfo)
      {
         $label = $userInfo->employeeNumber . " - " . $userInfo->getFullName();
         $selected = ($userInfo->employeeNumber == $selectedEmployeeNumber) ? "selected" : "";
         
         $html .= "<option value=\"$userInfo->employeeNumber\" $selected>$label</option>";
      }
      
      return ($html);
   }
}