<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/app/page/page.php';
require_once ROOT.'/core/manager/notificationManager.php';

class NotificationPage extends Page
{
   public function handleRequest($params)
   {
      switch ($this->getRequest($params))
      {
         case "send_message":
         {
            if ($this->authenticate([Permission::SEND_NOTIFICATIONS]))
            {
               if (Page::requireParams($params, ["target", "priority", "subject", "message"]))
               {
                  $from = Authentication::getAuthenticatedUser()->employeeNumber;
                  $to = [];
                  $priority = $params->getInt("priority");
                  $subject = $params->get("subject");
                  $message = $params->get("message");
                  
                  if (is_array($params->get("target")))
                  {
                     foreach ($params->get("target") as $employeeNumber)
                     {
                        $to[] = intval($employeeNumber);
                     }
                  }
                           
                  if (NotificationManager::sendAppNotification($from, $to, $priority, $subject, $message))
                  {
                     $this->result->success = true;
                  }
                  else
                  {
                     $this->error("Send error");
                  }
               }
            }
            break;
         }
         
         case "acknowledge_notification":
         {
            if (Page::requireParams($params, ["notificationId"]))
            {
               $notificationId = $params->getInt("notificationId");
               $acknowledged = $params->keyExists("acknowledged") ? $params->getBool("acknowledged") : true;

               if (NotificationManager::acknowledgeAppNotification($notificationId, Authentication::getAuthenticatedUser()->employeeNumber, $acknowledged))
               {
                  $this->result->success = true;
                  $this->result->notificationId = $notificationId;
                  $this->result->acknowledged = $acknowledged;
               }
               else
               {
                  $this->error("Database error");
               }
            }
            break;
         }
         
         case "delete_notification":
         {
            if ($this->authenticate([Permission::SEND_APP_NOTIFICATIONS]))
            {
               if (Page::requireParams($params, ["notificationId"]))
               {
                  $notificationId = $params->getInt("notificationId");
                  
                  if (AppNotification::delete($notificationId))
                  {
                     $this->result->success = true;
                  }
                  else
                  {
                     $this->error("Database error");
                  }
               }
            }
            break;
         }
         
         case "fetch_app_notifcations_count":
         {
            if ($this->authenticate([Permission::NOTIFICATIONS]))
            {
               $this->result->success = true;
               $this->result->count = NotificationManager::getUnacknowledgedAppNotificationCount(Authentication::getAuthenticatedUser()->employeeNumber);
            }
            break;
         }
         
         case "fetch_app_notfications":
         {
            if ($this->authenticate([Permission::NOTIFICATIONS]))
            {
               $notifications = [];
               
               if ($params->keyExists("showAllUnacknowledged") && $params->getBool("showAllUnacknowledged"))
               {
                  $notifications = 
                     NotificationManager::getUnacknowledgedAppNotificationsForUser(
                        Authentication::getAuthenticatedUser()->employeeNumber);
               
                  $this->result->success = true;
               }
               else if (Page::requireParams($params, ["startDate", "endDate"]))
               {
                  $startDate = $params->get("startDate");
                  $endDate = $params->get("endDate");
                  
                  $notifications =
                     NotificationManager::getAppNotificationsForUser(
                        Authentication::getAuthenticatedUser()->employeeNumber,
                        $startDate,
                        $endDate);
                  
                  $this->result->success = true;
               }
               
               if ($this->result->success)
               {
                  foreach ($notifications as $notification)
                  {
                     NotificationPage::augmentNotification($notification, Authentication::getAuthenticatedUser()->employeeNumber);
                  }
                  
                  $this->result->notifications = $notifications;
               }
            }
            break;
         }
      
         default:
         {
            $this->error("Unsupported command: " . $this->getRequest($params));
            break;
         }
      }
      
      echo json_encode($this->result);
   }
   
   // **************************************************************************
   
   private function augmentNotification(&$notification, $employeeNumber)
   {
      $notification->formattedDateTime = $notification->dateTime ? Time::dateTimeObject($notification->dateTime)->format("n/j/Y g:i A") : "";
      $notification->priorityLabel = NotificationPriority::getLabel($notification->priority);
      $notification->priorityClass = NotificationPriority::getClass($notification->priority);
      
      $userInfo = UserInfo::load($notification->author);
      $notification->fromLabel = $userInfo ? $userInfo->getFullName() : "";
      
      $acknowledgement = NotificationManager::getAppNotificationAcknowledgement($notification->notificationId, $employeeNumber);
      $notification->acknowledged = ($acknowledgement && $acknowledgement->acknowledged);
   }
}

?>