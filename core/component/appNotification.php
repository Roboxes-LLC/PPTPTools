<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/notificationDefs.php';

class AppNotification
{
   const UNKNOWN_NOTIFICATION_ID = 0;

   public $notificationId;
   public $dateTime;
   public $author;  // userId
   public $priority;
   public $subject;
   public $message;
   
   public function __construct()
   {
      $this->notificationId = AppNotification::UNKNOWN_NOTIFICATION_ID;
      $this->dateTime = null;
      $this->author = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->priority = NotificationPriority::UNKNOWN;
      $this->subject = null;
      $this->message = null;
   }
   
   public function initialize($row)
   {
      $this->notificationId = intval($row["notificationId"]);
      $this->dateTime = $row["dateTime"] ? Time::fromMySqlDate($row["dateTime"]) : null;
      $this->author = intval($row["author"]);
      $this->priority = intval($row["priority"]);
      $this->subject = $row["subject"];
      $this->message = $row["message"];
   }
   
   // **************************************************************************
   // Component interface
   
   public static function load($notificationId)
   {
      $notification = null;
      
      $result = PPTPDatabaseAlt::getInstance()->getAppNotification($notificationId);
      
      if ($result && ($row = $result[0]))
      {
         $notification = new AppNotification();
         
         $notification->initialize($row);
      }
      
      return ($notification);
   }
   
   public static function save($notification)
   {
      $success = false;
      
      if ($notification->notificationId == AppNotification::UNKNOWN_NOTIFICATION_ID)
      {
         $success = PPTPDatabaseAlt::getInstance()->addAppNotification($notification);
         
         $notification->notificationId = intval(PPTPDatabaseAlt::getInstance()->lastInsertId());
      }
      else
      {
         $success = PPTPDatabaseAlt::getInstance()->updateAppNotification($notification);
      }
      
      return ($success);
   }
   
   public static function delete($notificationId)
   {
      return (PPTPDatabaseAlt::getInstance()->deleteAppNotification($notificationId));
   }
   
   // **************************************************************************
   
   public function isAcknowledged($employeeNumber)
   {
      return (PPTPDatabaseAlt::getInstance()->isAppNotificationAcknowledged($this->notificationId, $employeeNumber));
   }
}