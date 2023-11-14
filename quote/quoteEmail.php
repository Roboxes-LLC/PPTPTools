<?php

if (!defined('ROOT')) require_once 'root.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/core/component/company.php';
require_once ROOT.'/core/component/quote.php';
require_once ROOT.'/core/manager/userManager.php';
require_once ROOT.'/core/manager/emailManager.php';

require ROOT.'/vendor/autoload.php';

// Reference the Dompdf namespace.
use Dompdf\Dompdf;

class QuoteEmail
{
   const DEFAULT_PREFACE = 
      "<p>Thank you for considering Pittsburgh Precision Turned Products as your preferred manufacturing services provider.  " .
      "We are pleased to present you with a quotation for the production of your product. " .
      "Our team is excited to work with you and deliver a high-quality solution that meets your specific requirements</p>" .
      "<p>Please find the breakdown of costs and services in the table below.</p>";
   
   const DEFAULT_COVER_TEXT = 
      "Please review the attached quotatation for the production of your product.";

   private $quoteNumber;
   private $quote;
   private $notes;
   
   public function __construct($quoteId)
   {
      $this->quote = Quote::load($quoteId);
      
      if ($this->quote)
      {
         $this->quoteNumber = $this->quote->getQuoteNumber();
      }
      
      $this->notes = null;
   }
   
   public function validate()
   {
      return ($this->quote != null);  // TODO
   }
   
   public function setNotes($notes)
   {
      $this->notes = $notes;
   }
   
   public function getCoverHtml()
   {
      $template = ROOT.'/templates/email/quoteEmailCoverTemplate.php';
      
      $templateParams = $this->getCoverTemplateParams();
      
      ob_start();
      include $template;
      $html = ob_get_clean();
      
      return ($html);
   }
   
   public function getHtml()
   {
      $template = ROOT.'/templates/quoteTemplate.php';
      
      $templateParams = $this->getTemplateParams();
      
      ob_start();
      include $template;
      $html = ob_get_clean();
      
      return ($html);
   }
   
   public function send($fromUserId, $toEmail, $ccEmails = [])
   {
      $result = new EmailResult();
      
      if (!$this->validate())
      {
         $result->status = false;
         $result->message = "Malformed message";
      }
      else
      {
         $result = EmailManager::sendEmail($this->getEmailParams($fromUserId, $toEmail, $ccEmails));
      }
      
      return ($result);
   }
   
   public function getPdf()
   {
      // PDF file and path.
      $pdfName = $this->getPdfName();
      $path = ROOT."/temp/$pdfName";
      
      $dompdf = new Dompdf();
      
      $dompdf->getOptions()->setChroot(ROOT);
      
      $dompdf->loadHtml($this->getHtml());
      
      // (Optional) Setup the paper size and orientation
      $dompdf->setPaper('A4', 'portrait');
      
      // Render the HTML as PDF
      $dompdf->render();
      
      // Save to file.
      $output = $dompdf->output();
      file_put_contents($path, $output);
      
      // Output the generated PDF to Browser
      //$dompdf->stream();
      
      return ($path);
   }

   private function getCoverTemplateParams()
   {
      $templateParams = new stdClass();
      $templateParams->quoteNumber = "";
      $templateParams->date = "";
      $templateParams->coverText = "";
      
      if ($this->quote)
      {         
         // quoteNumber
         $templateParams->quoteNumber = $this->quote->getQuoteNumber();
       
         // company
         $templateParams->company = Company::load(Company::PPTP_ID);
         
         // date
         $templateParams->date = Time::now("F d, Y");
                  
         // coverText
         $templateParams->coverText = $this->getCoverText();
      }
      
      return ($templateParams);
   }
   
   private function getCoverText()
   {
      $html = QuoteEmail::DEFAULT_COVER_TEXT;
      
      return ($html);
   }
   
   private function getTemplateParams()
   {
      global $IMAGES_DIR;
      
      $templateParams = new stdClass();
      
      /*
      $path = ROOT.$IMAGES_DIR.'/pptp-logo-256x256.png';
      $type = pathinfo($path, PATHINFO_EXTENSION);
      $data = file_get_contents($path);
      $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
      $templateParams->logoSrc = $base64;
      */
      
      if ($this->quote)
      {
         $templateParams->quoteNumber = $this->quoteNumber;
         
         $templateParams->quote = $this->quote;
         
         //$templateParams->logo = "$IMAGES_DIR/pptp-logo-192x192.png";
         
         $path = ROOT.$IMAGES_DIR.'/pptp-logo-192x192.png';
         $type = pathinfo($path, PATHINFO_EXTENSION);
         $data = file_get_contents($path);
         $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
         $templateParams->logo = $base64;
         
         $templateParams->company = Company::load(Company::PPTP_ID);
                  
         // quoteDate
         if ($this->quote->isSent())
         {
            $templateParams->quoteDate = Time::dateTimeObject($this->quote->getSentAction()->dateTime)->format("n/j/Y");
         }
         else
         {
            $templateParams->quoteDate = Time::now("n/j/Y");
         }
         
         $templateParams->customer = Customer::load($this->quote->customerId);
         
         $templateParams->contact = Contact::load($this->quote->contactId);
         
         $templateParams->preface = QuoteEmail::DEFAULT_PREFACE;
         
         $templateParams->notes = $this->notes;
         
         $templateParams->author = Authentication::getAuthenticatedUser();
      }
      
      return ($templateParams);
   }
   
   private function getEmailParams($userId, $toEmail, $ccEmails)
   {
      $params = new EmailParams();
      
      if ($this->quote)
      {
         // fromEmail
         // fromName
         $user = UserInfo::load($userId);
         if ($user)
         {
            $params->fromEmail = $user->email;
            $params->fromName = $user->getFullName();
         }
         
         // toEmail
         // toName
         $params->toEmail = $toEmail;
         $params->toName = $this->getContactName($toEmail);
         
         // bcc
         if (!empty($ccEmails))
         {
            $params->bcc = array();
            
            foreach ($ccEmails as $ccEmail)
            {
               $bcc = new stdClass();
               $bcc->email = $ccEmail;
               $bcc->name = $ccEmail;
               
               $params->bcc[] = $bcc;
            }
         }

         // subject
         $company = Company::load(Company::PPTP_ID);
         if ($company)
         {
            $params->subject = "$company->companyName - Quote $this->quoteNumber";
         }
         else
         {
            $params->subject = "Quote $this->quoteNumber";
         }
         
         $params->message = $this->getCoverHtml();
         
         $params->attachment = array();
         $pdfFile = $this->getPdf();
         if ($pdfFile)
         {
            $content = chunk_split(base64_encode(file_get_contents($pdfFile)));
            
            $params->attachment = array($this->getPdfName() => $content);            
         }
      }
      
      return ($params);
   }
   
   private function getPdfName()
   {
      $pdfName = "";
      
      if ($this->quote)
      {
         $company = Company::load(Company::PPTP_ID);
         if ($company)
         {
            $companyName = str_replace(' ', '', $company->companyName);
            $pdfName = "{$companyName}_Quote_{$this->quote->getQuoteNumber()}.pdf";
         }
      }
      
      return ($pdfName);
   }
   
   private function getContactName($email)
   {
      $contactName = $email;
      
      // If the provided email is that of the quote contact, use his/her name.
      if ($this->quote)
      {
         $contact = Contact::load($this->quote->contactId);
         if ($contact && ($contact->email == $email))
         {
            $contactName = $contact->getFullName();
         }
      }
      
      return ($contactName);
   }
}

