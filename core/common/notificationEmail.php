<?php

if (!defined('ROOT')) require_once 'root.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/params.php';
require_once ROOT.'/core/common/notification.php';
require_once ROOT.'/common/inspection.php';
require_once ROOT.'/core/manager/emailManager.php';

class NotificationEmail
{
   const FROM_EMAIL = "noreply@pittsburghprecision.com";

   const FROM_NAME = "PPTP Tools";
   
   // Notification types supported by this class.
   public static $supportedNotificationTypes = [Notification::FINAL_INSPECTION];
   
   private $notificationType;
   
   // An object containing notification specific details.
   private $details;
   
   public function __construct($notificationType, $details = null)
   {
      $this->notificationType = $notificationType;
      $this->details = $details;
   }
   
   public function validate()
   {
      return (in_array($this->notificationType, NotificationEmail::$supportedNotificationTypes));
   }
   
   public function getHtml()
   {
      $template = ROOT.'/templates/email/notificationEmailTemplate.php';
      
      $templateParams = $this->getTemplateParams();
      
      ob_start();
      include $template;
      $html = ob_get_clean();
      
      return ($html);
   }
   
   public function send($toUserId, $ccUserIds = [])
   {
      $result = new EmailResult();
      
      if (!$this->validate())
      {
         $result->status = false;
         $result->message = "Malformed message";
      }
      else
      {
         $result = EmailManager::sendEmail($this->getEmailParams($toUserId, $ccUserIds));
      }
      
      return ($result);
   }
   
   private function getTemplateParams()
   {
      global $IMAGES_DIR;
      
      $templateParams = new stdClass();
      
      $path = $IMAGES_DIR.'/pptp-logo-192x192.png';
      //$type = pathinfo($path, PATHINFO_EXTENSION);
      //$data = file_get_contents($path);
      //$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
      $templateParams->logoSrc = $path;
      
      $templateParams->siteName = "Pittsburgh Precision Turned Products";
      
      $templateParams->notificationTitle = NotificationEmail::getTitle();
      
      $templateParams->notificationText = $this->getText();
      
      return ($templateParams);
   }
   
   private function getEmailParams($userId, $ccUserIds)
   {
      $params = new EmailParams();
      
      $params->fromEmail = NotificationEmail::FROM_EMAIL;
      $params->fromName = NotificationEmail::FROM_NAME;
 
      $user = UserInfo::load($userId);
      if ($user)
      {
         $params->toEmail = $user->email;
         $params->toName = $user->getFullName();
      }
      else
      {
         $params->toEmail = NotificationEmail::FROM_EMAIL;
         $params->toName = NotificationEmail::FROM_NAME;
      }
         
      if (!empty($ccUserIds))
      {
         $params->bcc = array();
         
         foreach ($ccUserIds as $userId)
         {
            $user = UserInfo::load($userId);
            if ($user && $user->email)
            {
               $bcc = new stdClass();
               $bcc->email = $user->email;
               $bcc->name = $user->getFullName();
               
               $params->bcc[] = $bcc;
            }
         }
      }
         
      $params->subject = "PPTP Tools Alert";
         
      $params->message = $this->getHtml();
      
      return ($params);
   }
   
   private function getTitle()
   {
      $titles =
      [
        "",  // UNKNOWN
        "",  // PRINTER_ALERT (Has its own email object)
        "",  // QUOTE_REQUESTED
        "",  // QUOTE_SENT
        "A new final part inspection has been created"  // FINAL_INSPECTION
      ];
      
      return ($titles[$this->notificationType]);
   }
   
   private function getText()
   {
      $html = "";
      
      switch ($this->notificationType)
      {
         case Notification::FINAL_INSPECTION:
         {
            if ($this->details && isset($this->details->inspectionId))
            {
               // Template variables.
               $inspectionId = "[unknown]";
               $creationDateTime = "";
               $inspectorName = "";
               $jobNumber = "";
               $link = "";
               $quantity = "";
               
               $inspectionId = $this->details->inspectionId;
               $inspection = Inspection::load($inspectionId, true);
               
               if ($inspection)
               {
                  $creationDateTime = Time::dateTimeObject($inspection->dateTime)->format("n/j/Y g:i A");
                  
                  if ($inspection->jobId != JobInfo::UNKNOWN_JOB_ID)
                  {
                     $job = JobInfo::load(jobId);
                     if ($job)
                     {
                        $jobNumber = $job->jobNumber;
                     }
                  }
                  else if ($inspection->jobNumber != JobInfo::UNKNOWN_JOB_NUMBER)
                  {
                     $jobNumber = $inspection->jobNumber;
                  }

                  $userInfo = UserInfo::load($inspection->inspector);
                  if ($userInfo)
                  {
                     $inspectorName = $userInfo->getFullName();
                  }
                  
                  $quantity = $inspection->quantity;

                  $link = "https://tools.pittsburghprecision.com/inspection/viewInspection.php?inspectionId=$inspectionId";
               }
               
               $html =
<<<HEREDOC
               <p>A new final parts inspection has been generated for job <b>$jobNumber</b>.</p>
 
               <style>
                  th {
                     font-weight: bold;
                  }
                  th, td {
                     text-align: left;
                  }
                  tr {
                     height: 25px;
                  }
               </style>
               <table>
                  <tr>
                     <th>Created:</th>
                     <td>$creationDateTime</td></tr>
                  <tr>
                     <th>Inspector:</th>
                     <td>$inspectorName</td>
                  </tr>
                  <tr>
                     <th>Job:</th>
                     <td>$jobNumber</td>
                  </tr>
                  <tr>
                     <th>Quantity:</th>
                     <td>$quantity</td>
                  </tr>
               </table>

               <p>Visit to <a href="$link">tools.pittsburghprecision.com</a> for more details.</p> 
HEREDOC;
            }
            break;
         }
         
         case Notification::PRINTER_ALERT:    // Has its own email object.
         case Notification::QUOTE_REQUESTED:  // Has its own email object.
         case Notification::QUOTE_SENT:       // Has its own email object.
         default:
         {
            break;
         }
      }
      
      return ($html);
   }
}

