<?php

if (!defined('ROOT')) require_once 'root.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/params.php';
require_once ROOT.'/common/userInfo.php';
require_once ROOT.'/core/manager/emailManager.php';
require_once ROOT.'/core/manager/userManager.php';

class PrinterAlertEmail
{
   private $printers;
   
   private $addedPrinters;
   
   private $removedPrinters;
   
   public function __construct($printers, $addedPrinters, $removedPrinters)
   {
      $this->printers = $printers;
      $this->addedPrinters = $addedPrinters;
      $this->removedPrinters = $removedPrinters;
   }
   
   public function validate()
   {
      return (true);
   }
   
   public function getHtml()
   {
      $template = ROOT.'/templates/email/printerAlertEmailTemplate.php';
      
      $templateParams = $this->getTemplateParams();
      
      ob_start();
      include $template;
      $html = ob_get_clean();
      
      return ($html);
   }
   
   public function getSubject()
   {
      $subject = "PPTP Tools - Label Printer Alert";

      return ($subject);
   }
   
   public function send($toUserId)
   {
      $result = new EmailResult();
      
      if (!$this->validate())
      {
         $result->status = false;
         $result->message = "Malformed message";
      }
      else
      {
         $result = EmailManager::sendEmail($this->getEmailParams($toUserId));
      }
      
      return ($result);
   }   
   
   private function getTemplateParams()
   {
      global $IMAGES_DIR;
      
      $templateParams = new stdClass();
      
      $templateParams->logoSrc = $IMAGES_DIR.'/pptp_logo.jpg';
      
      // date
      $templateParams->date = (new DateTime())->format("m/d/Y");
      
      // coverText
      $templateParams->coverText = "";
      
      // printers
      $templateParams->printers = $this->getPrintersList();
      
      // removed printers
      $templateParams->removedPrinters = $this->getRemovedPrintersList();
      
      return ($templateParams);
   }
   
   private function getEmailParams($toUserId)
   {
      $params = new EmailParams();
      
      $params->fromEmail = "noreply@pptptools.com";
      $params->fromName = "PPTP Tools";
      
      $userInfo = UserInfo::load($toUserId);
      var_dump($toUserId);
      if ($userInfo)
      {
         $params->toEmail = $userInfo->email;
         $params->toName = $userInfo->getFullName();
      }
      
      $params->subject = $this->getSubject();
         
      $params->message = $this->getHtml();
      
      return ($params);
   }
   
   private static function getFormattedDate()
   {
      $dateTime = Time::getDateTime(Time::now());
      $formattedDate = $dateTime->format("F d");
      
      return ($formattedDate);
   }
   
   private function getPrintersList()
   {
      $html = "<ul>";
      
      foreach ($this->printers as $printer)
      {
         $isNew = !empty(array_filter($this->addedPrinters, function($checkPrinter) use ($printer) {
            return ($printer->printerName == $checkPrinter->printerName);
         }));
         
         $new = $isNew ? "<span class=\"new-indicator\">(new)</span>" : "";
         
         $html .= "<li class=\"printer\">$printer->printerName $new</li>";
      }
      
      $html .= "</ul>";
      
      return ($html);
   }
   
   private function getRemovedPrintersList()
   {
      $html = "<ul>";
      
      foreach ($this->removedPrinters as $printer)
      {
         $html .= "<li class=\"printer removed\">$printer->printerName</li>";
      }
      
      $html .= "</ul>";
      
      return ($html);
   }
}

?>