<?php

class Notification
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const PRINTER_ALERT = Notification::FIRST;
   const LAST = 9;
   
   const NO_NOTIFICATIONS = 0x00000000;
   const ALL_NOTIFICATIONS = 0xFFFFFFFF;
   
   public $notificationId;
   
   public $notificationName;
   
   public $bits;
   
   public static function getNotifications()
   {
      if (Notification::$notifications == null)
      {
         Notification::$notifications =
            array(
               new Notification(Notification::PRINTER_ALERT, "Printer Alert"),
            );
      }
      
      return (Notification::$notifications);
   }
   
   public static function getNotification($notificationId)
   {
      $notification = null;
      
      if (($notificationId>= Notification::FIRST) && ($notificationId <= Notification::LAST))
      {
         $notification = Notification::getNotifications()[$notificationId - Notification::FIRST];
      }
      
      return ($notification);
   }
   
   public function isSetIn($mask)
   {
      return (($this->bits & $mask) > 0);
   }
   
   public static function getBits(...$notificationIds)
   {
      $bits = Notification::NO_NOTIFICATIONS;
      
      foreach ($notificationIds as $notificationId)
      {
         $bits |=  Notification::getNotification($notificationId)->bits;
      }
      
      return ($bits);
   }
   
   public function getInputName()
   {
      $inputName = "notification" . $this->notificationName;
      $inputName = str_replace(' ', '', $inputName);  // Strip out spaces.
      
      return ($inputName);
   }
   
   private static $notifications = null;
   
   private function __construct($notificationId, $notificationName)
   {
      $this->notificationId = $notificationId;
      $this->notificationName = $notificationName;
      
      if ($notificationId > Notification::UNKNOWN)
      {
         $this->bits = (1 << ($notificationId - Notification::FIRST));
      }
      else
      {
         $this->bits = 0;
      }
   }
}

?>