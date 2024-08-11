<?php
if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/notificationEmail.php';
require_once ROOT.'/core/component/appNotification.php';

class NotificationManager
{
   // **************************************************************************
   //                          System event handling
   
   public static function onFinalInspectionCreated($inspectionId, $isPriority)
   {
      NotificationManager::sendNotification(
         Notification::FINAL_INSPECTION,
         $isPriority ? NotificationPriority::PRIORITY : NotificationPriority::INFORMATIONAL,
         (object)array("inspectionId" => $inspectionId));
   }
   
   public static function onFirstPartInspectionCreated($inspectionId)
   {
      NotificationManager::sendNotification(
            Notification::FIRST_PART_INSPECTION,
            NotificationPriority::INFORMATIONAL,
            (object)array("inspectionId" => $inspectionId));
   }
   
   public static function onFirstPartInspectionComplete($inspectionId)
   {
      NotificationManager::sendNotification(
         Notification::FIRST_PART_INSPECTION_COMPLETE,
         NotificationPriority::INFORMATIONAL,
         (object)array("inspectionId" => $inspectionId));
   }
   
   public static function sendAppNotification($from, $to, $priority, $subject, $message)
   {
      $returnStatus = false;
      
      if (count($to) > 0)
      {
         $notification = new AppNotification();
         $notification->dateTime = Time::now();
         $notification->author = $from;
         $notification->priority = $priority;
         $notification->subject = $subject;
         $notification->message = $message;
         
         if (AppNotification::save($notification))
         {
            foreach ($to as $employeeNumber)
            {
               PPTPDatabaseAlt::getInstance()->addUserToAppNotification($employeeNumber, $notification->notificationId);
            }
         
            $returnStatus = true;
         }
      }
      
      return ($returnStatus);
   }
   
   public static function getAppNotificationsForUser($employeeNumber, $startDateTime, $endDateTime)
   {
      $notifications = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getAppNotificationsForUser($employeeNumber, $startDateTime, $endDateTime);
      
      foreach ($result as $row)
      {
         $notification = new AppNotification();
         $notification->initialize($row);
         
         $notifications[] = $notification;
      }
      
      return ($notifications);
   }
   
   public static function getUnacknowledgedAppNotificationsForUser($employeeNumber)
   {
      $notifications = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getUnacknowledgedAppNotificationsForUser($employeeNumber);
      
      foreach ($result as $row)
      {
         $notification = new AppNotification();
         $notification->initialize($row);
         
         $notifications[] = $notification;
      }
      
      return ($notifications);
   }
   
   public static function acknowledgeAppNotification($notificationId, $employeeNumber, $acknowledged = true)
   {
      return (PPTPDatabaseAlt::getInstance()->acknowledgeAppNotification($notificationId, $employeeNumber, $acknowledged));
   }
   
   public static function getAppNotificationAcknowledgement($notificationId, $employeeNumber)
   {
      $acknowledgement = (object)["acknowledged" => false, "dateTime" => null];
      
      $result = PPTPDatabaseAlt::getInstance()->getAppNotificationAcknowledgement($notificationId, $employeeNumber);
      
      if ($result)
      {
         $acknowledgement->acknowledged = filter_var($result[0]["acknowledged"], FILTER_VALIDATE_BOOLEAN);
         $acknowledgement->dateTime = $result[0]["dateTime"];
      }
      
      return ($acknowledgement);
   }
   
   public static function getUnacknowledgedAppNotificationCount($employeeNumber)
   {
      $count = count(NotificationManager::getUnacknowledgedAppNotificationsForUser($employeeNumber));
      
      return ($count);
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