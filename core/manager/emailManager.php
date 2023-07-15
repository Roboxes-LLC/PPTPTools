<?php 

if (!defined('ROOT')) require_once 'root.php';
require_once ROOT.'/core/manager/emailKey.php';
require_once ROOT.'/thirdparty/mailin-api-php/src/Sendinblue/Mailin.php';

use Sendinblue\Mailin;

class EmailParams
{
   public $fromEmail;
   public $fromName;
   public $toEmail;
   public $toName;
   public $cc;
   public $bcc;
   public $subject;
   public $message;
   public $attachment;
   
   public function __construct()
   {
      $this->fromEmail = null;
      $this->fromName = null;
      $this->toEmail = null;
      $this->toName = null;
      $this->cc = array();
      $this->bcc = array();
      $this->subject = null;
      $this->message = null;
      $this->attachment = array();
   }
   
   public function validate()
   {
      return (EmailParams::validateParam($this->fromEmail) &&
              EmailParams::validateParam($this->fromName) &&
              EmailParams::validateParam($this->toEmail) &&
              EmailParams::validateParam($this->subject) &&
              EmailParams::validateParam($this->message) &&
              isset($this->attachment) && is_array($this->attachment));
   }
   
   public function getSendInBlueParams()
   {
      $params = 
         array(
            "to" => array(array("name" => $this->toName, "email" => $this->toEmail)),
            "sender" => array("name" => $this->fromName, "email" => $this->fromEmail),
            "subject" => $this->subject,
            "htmlContent" => $this->message);
         
      // cc
      if (!empty($this->cc))
      {
         $ccParams = array();
         
         foreach ($this->cc as $cc)
         {
            $ccParams[] = array("name" => $cc->name, "email" => $cc->email);
         }
         
         $params["cc"] = $ccParams;
      }
      
      // bcc
      if (!empty($this->bcc))
      {
         $bccParams = array();
         
         foreach ($this->bcc as $bcc)
         {
            $bccParams[] = array("name" => $bcc->name, "email" => $bcc->email);
         }
         
         $params["bcc"] = $bccParams;
      }
      
      // attachment
      if (!empty($this->attachment))
      {
         $attachmentParams = array();
         
         foreach ($this->attachment as $filename => $content)
         {
            $attachmentParams[] = array("name" => $filename, "content" => $content);
         }
         
         $params["attachment"] = $attachmentParams;
      }      
      
      return ($params);
   }
   
   private static function validateParam($param)
   {
      return ($param && ($param != ""));
   }
}

class EmailResult
{
   public $status;
   public $message;
   
   public function __construct()
   {
      $this->status = false;
      $this->message = "";
   }
}

class EmailManager
{
   const SEND_IN_BLUE_URL = "https://api.sendinblue.com/v3/smtp";
   
   const CURL_TIMEOUT = 45000;  // 45 seconds
   
   public static function sendEmail($emailParams)
   {
      var_dump($emailParams);
      $result = new EmailResult();
      
      if (!$emailParams->validate())
      {
         $result->status = false;
         $result->message = "Malformed message";
      }
      else 
      {
         $result = EmailManager::sendViaSendInBlue($emailParams);
      }
      
      return ($result);
   }
   
   private static function sendViaSendInBlue($emailParams)
   {
      global $SEND_IN_BLUE_API_KEY;
      
      $result = new EmailResult();
            
      $mailin = new Mailin(EmailManager::SEND_IN_BLUE_URL, $SEND_IN_BLUE_API_KEY, EmailManager::CURL_TIMEOUT);
      
      try
      {
         $sendInBlueResult = $mailin->send_email($emailParams->getSendInBlueParams());
         
         // See return value description defined in:
         // https://developers.sendinblue.com/reference/sendtransacemail
         
         if (isset($sendInBlueResult["messageId"]))
         {
            // Success case.
            $result->status = true;
            $result->messageId = $sendInBlueResult["messageId"];
            $result->message = "Sent successfully";
         }
         else if (isset($sendInBlueResult["code"]))
         {
            // Failure case.
            $result->status = false;
            $result->code = $sendInBlueResult["code"];
            $result->message = $sendInBlueResult["message"];
         }
         else
         {
            // Unexpected case.
            $result->status = false;
            $result->message = "API error";
         }
      }
      catch (Exception $e)
      {
         $result->status = false;
         $result->message = $e->getMessage();
      }
      
      return ($result);
   }
}

?>