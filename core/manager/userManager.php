<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/common/userInfo.php';

class UserManager
{
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
      $users = array();
      
      $result = PPTPDatabase::getInstance()->getUsersByRoles([Role::OPERATOR]);
         
      while ($result && ($row = $result->fetch_assoc()))
      {
         $user = new UserInfo();
         $user->initialize($row);

         $users[] = $user;
      }
      
      return ($users);
   }
}