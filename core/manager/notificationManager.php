<?php
if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/notificationEmail.php';

class NotificationManager
{
   // **************************************************************************
   //                          System event handling
   
   public static function onFinalInspectionCreated($inspectionId)
   {
      NotificationManager::sendNotification(
         Notification::FINAL_INSPECTION,
         (object)array("inspectionId" => $inspectionId));
   }
   
   // **************************************************************************
   
   private static function sendNotification($notificationType, $details)
   {
      // TODO: Augment to match FlexScreen Inventory.
      
      $users = UserManager::getUsersForNotification(Notification::PRINTER_ALERT);
      
      $userIds = [];
      foreach ($users as $user)
      {
         $userIds[] = $user->employeeNumber;
      }
      
      if (!empty($userIds))
      {
         $result = NotificationManager::sendEmailNotification($userIds, $notificationType, $details);
         
         // Debug
         //var_dump($result);
      }
   }
   
   private static function sendEmailNotification($userIds, $notificationType, $details)
   {
      $result = new EmailResult();
      
      $email = new NotificationEmail($notificationType, $details);
      
      if ($email->validate())
      {
         $result = $email->send(UserInfo::UNKNOWN_EMPLOYEE_NUMBER, $userIds);
      }
      
      return ($result);
   }
}

?>