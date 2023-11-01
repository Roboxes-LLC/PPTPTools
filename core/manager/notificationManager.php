<?php
if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/notificationEmail.php';

class NotificationManager
{
   // **************************************************************************
   //                          System event handling
   
   public static function onFinalInspectionCreated($inspectionId,$isPriority)
   {
      NotificationManager::sendNotification(
         Notification::FINAL_INSPECTION,
         $isPriority ? NotificationPriority::PRIORITY : NotificationPriority::INFORMATIONAL,
         (object)array("inspectionId" => $inspectionId));
   }
   
   public static function onFirstPartInspectionComplete($inspectionId)
   {
      NotificationManager::sendNotification(
         Notification::FIRST_PART_INSPECTION,
         NotificationPriority::INFORMATIONAL,
         (object)array("inspectionId" => $inspectionId));
   }
   
   // **************************************************************************
   
   private static function sendNotification($notificationType, $priority, $details)
   {
      // TODO: Augment to match FlexScreen Inventory.
      
      $users = UserManager::getUsersForNotification($notificationType);
      
      $userIds = [];
      foreach ($users as $user)
      {
         $userIds[] = $user->employeeNumber;
      }
      
      if (!empty($userIds))
      {
         $result = NotificationManager::sendEmailNotification($userIds, $notificationType, $priority, $details);
         
         // Debug
         //var_dump($result);
      }
   }
   
   private static function sendEmailNotification($userIds, $notificationType, $priority, $details)
   {
      $result = new EmailResult();
      
      $email = new NotificationEmail($notificationType, $priority, $details);
      
      if ($email->validate())
      {
         $result = $email->send(UserInfo::UNKNOWN_EMPLOYEE_NUMBER, $userIds);
      }
      
      return ($result);
   }
}

?>