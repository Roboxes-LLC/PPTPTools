<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/isoInfo.php';
require_once ROOT.'/common/jobInfo.php';
require_once ROOT.'/common/qrCode.php';
require_once ROOT.'/common/timeCardInfo.php';
require_once ROOT.'/common/userInfo.php';
require_once ROOT.'/printer/printJob.php';
require_once ROOT.'/thirdParty/phpqrcode/phpqrcode.php';

abstract class PanTicketLabelFields
{
   const FIRST = 0;
   const PAN_TICKET_CODE = PanTicketLabelFields::FIRST;
   const JOB_NUMBER = 1;
   const WC_NUMBER = 2;
   const OPERATOR = 3;
   const MFG_DATE = 4;
   const HEAT_NUMBER = 5;
   const PAN_COUNT = 6;
   const URL = 7;
   const BARCODE = 8;
   const ISO = 9;
   const LAST = 10;
   const COUNT = PanTicketLabelFields::LAST - PanTicketLabelFields::FIRST;
   
   public static function getKeyword($panTicketLabelField)
   {
      $keywords = array("%id", 
                        "%jobNumber", 
                        "%wcNumber", 
                        "%operator", 
                        "%mfgDate",
                        "%heatNumber",
                        "%panCount",
                        "%url",
                        "%BAR",
                        "%iso");
      
      return ($keywords[$panTicketLabelField]);
   }
}

class PanTicket
{
   const UNKNOWN_PAN_TICKET_ID = TimeCardInfo::UNKNOWN_TIME_CARD_ID;
   
   //const LABEL_TEMPLATE_FILENAME = "../panTicket/PanTicketTemplate_8.6.2.label";
   //const LABEL_TEMPLATE_FILENAME = "../panTicket/PanTicketTemplate_8.7.4.label";
   const LABEL_TEMPLATE_FILENAME = "../panTicket/PanTicketTemplate_8.7.4_modified.label";
   
   public $panTicketId = PanTicket::UNKNOWN_PAN_TICKET_ID;
   
   public $printDescription = "";
   
   public $labelXML = "";
   
   public function __construct($panTicketId)
   {
      // A pan ticket id is the same as a time card id.
      $this->panTicketId = $panTicketId;
      
      $this->labelXML = PanTicket::generateLabelXml($this->panTicketId);
      
      $this->printDescription = PanTicket::generatePrintDescription($this->panTicketId);
   }
   
   public function render()
   {
      $jobNumber = "";
      $wcNumber = "";
      $operator = "";
      $mfgDate = "";
      $materialNumber = "";
      $panCount = "";      
      
      $timeCardInfo = TimeCardInfo::load($this->panTicketId);
      
      if ($timeCardInfo)
      {
         $panTicketCode = PanTicket::getPanTicketCode($this->panTicketId);
         $operator = $timeCardInfo->employeeNumber;
         $dateTime = new DateTime($timeCardInfo->manufactureDate, new DateTimeZone('America/New_York'));
         $mfgDate = $dateTime->format("m-d-Y");
         $materialNumber = $timeCardInfo->materialNumber;
         $panCount = $timeCardInfo->panCount;
         
         $jobInfo = JobInfo::load($timeCardInfo->jobId);
         
         if ($jobInfo)
         {
            $jobNumber = $jobInfo->jobNumber;
            $wcNumber = $jobInfo->wcNumber;
         }
      }
      
      $qrCodeSrc = "../common/qrCode.php?qrCodeContent=" . $this->getQRCodeURL($this->panTicketId);
      
      $isoNumber = IsoInfo::getIsoNumber(IsoDoc::PAN_TICKET);
      
      echo
<<<HEREDOC
      <div class="pan-ticket">
         <div class="top-panel">
            <div>----- attach here -----</div>
         </div>
         <div class="middle-panel"> 
            <div class="content-panel">
               <div><b>Job:</b>&nbsp;$jobNumber</div>
               <div><b>WC:</b>&nbsp;$wcNumber</div>
               <div><b>Operator:</b>&nbsp;$operator</div>
               <div><b>Date:</b>&nbsp;$mfgDate</div>
               <div><b>Heat:</b>&nbsp;$materialNumber</div>
               <div><b>Baskets:</b>&nbsp;$panCount</div>
            </div>
         </div>
         <div class="bottom-panel">
            <div><img src="$qrCodeSrc" width="100px"></div>
            <div>$panTicketCode</div>
            <div class="iso-number">ISO $isoNumber</div>
         </div>
      </div>
HEREDOC;
   }
   
   public static function getPanTicketCode($panTicketId)
   {
      return (sprintf('%04X', $panTicketId));
   }
   
   public static function getPanTicketId($panTicketCode)
   {
      return (ctype_xdigit($panTicketCode) ? hexdec($panTicketCode) : PanTicket::UNKNOWN_PAN_TICKET_ID);
   }
   
   private static function generatePrintDescription($timeCardId)
   {
      $description = "PanTicket";
      
      $timeCardInfo = TimeCardInfo::load($timeCardId);
      
      if ($timeCardInfo)
      {
         $jobInfo = JobInfo::load($timeCardInfo->jobId);
         
         if ($jobInfo)
         {
            $description .= "_" . $jobInfo->jobNumber;
         }
      }
      
      $description .= ".label";
      
      return ($description);
   }
   
   private static function generateLabelXml($timeCardId)
   {
      $xml = "";
      
      $timeCardInfo = TimeCardInfo::load($timeCardId);
      
      $panTicketCode = PanTicket::getPanTicketCode($timeCardId);
      
      $jobNumber = "";
      $wcNumber = "";
      $jobInfo = JobInfo::load($timeCardInfo->jobId);
      if ($jobInfo)
      {
         $jobNumber = $jobInfo->jobNumber;
         $wcNumber = $jobInfo->wcNumber;
      }
      
      $dateTime = new DateTime($timeCardInfo->manufactureDate, new DateTimeZone('America/New_York'));
      $mfgDate = $dateTime->format("m-d-Y");
      
      $isoNumber = IsoInfo::getIsoNumber(IsoDoc::PAN_TICKET);
      
      $file = fopen(PanTicket::LABEL_TEMPLATE_FILENAME, "r");
      
      if ($file)
      {
         $xml = fread($file, filesize(PanTicket::LABEL_TEMPLATE_FILENAME));
         $xml = substr($xml, 3);  // Three odd characters at beginning when reading from file.
         
         fclose($file);
         
         for ($field = PanTicketLabelFields::FIRST; $field < PanTicketLabelFields::LAST; $field++)
         {
            switch ($field)
            {
               case PanTicketLabelFields::PAN_TICKET_CODE:
               {
                  $xml = str_replace(PanTicketLabelFields::getKeyword($field), $panTicketCode, $xml);
                  break;
               }
                  
               case PanTicketLabelFields::JOB_NUMBER:
               {
                  $xml = str_replace(PanTicketLabelFields::getKeyword($field), $jobNumber, $xml);
                  break;
               }
                  
               case PanTicketLabelFields::WC_NUMBER:
               {
                  $xml = str_replace(PanTicketLabelFields::getKeyword($field), $wcNumber, $xml);
                  break;
               }
                  
               case PanTicketLabelFields::OPERATOR:
               {
                  $xml = str_replace(PanTicketLabelFields::getKeyword($field), $timeCardInfo->employeeNumber, $xml);
                  break;
               }
                  
               case PanTicketLabelFields::MFG_DATE:
               {
                  $xml = str_replace(PanTicketLabelFields::getKeyword($field), $mfgDate, $xml);
                  break;
               }
                  
               case PanTicketLabelFields::HEAT_NUMBER:
               {
                  $xml = str_replace(PanTicketLabelFields::getKeyword($field), $timeCardInfo->materialNumber, $xml);
                  break;
               }
                  
               case PanTicketLabelFields::PAN_COUNT:
               {
                  $xml = str_replace(PanTicketLabelFields::getKeyword($field), $timeCardInfo->panCount, $xml);
                  break;
               }
                  
               case PanTicketLabelFields::URL:
               {
                  $xml = str_replace(PanTicketLabelFields::getKeyword($field), PanTicket::getQRCodeURL($timeCardId), $xml);
                  break;
               }
               
               case PanTicketLabelFields::BARCODE:
               {
                  $xml = str_replace(PanTicketLabelFields::getKeyword($field), $panTicketCode, $xml);
                  break;
               }
               
               case PanTicketLabelFields::ISO:
               {
                  $xml = str_replace(PanTicketLabelFields::getKeyword($field), $isoNumber, $xml);
                  break;
               }
                  
               default:
               {
                  break;
               }
            }
         }
      }
      
      return ($xml);
   }
   
   private static function getQRCodeURL($panTicketId)
   {
      return (urlencode("http://tools.pittsburghprecision.com/panticket/viewPanTicket.php?panTicketId=" . $panTicketId));
   }
}

/*
if (isset($_GET["preview"]) &&
    isset($_GET["panTicketId"]))
{
   $panTicketId = $_GET["panTicketId"];
   
   $panTicket = new PanTicket($panTicketId);
   
   echo
<<<HEREDOC
   <html>
      <head>
         <link rel="stylesheet" type="text/css" href="panTicket.css"/>

         <script src="http://www.labelwriter.com/software/dls/sdk/js/DYMO.Label.Framework.3.0.js" type="text/javascript" charset="UTF-8"></script>
         <script src="panTicket.js"></script>
      </head>
      <body>
         <img id="pan-ticket-image" src="" alt="pan ticket"/>
HEREDOC;
   
   $panTicket->render();
   
   echo 
<<<HEREDOC
      </body>
      <script>
         dymo.label.framework.init(function() {
            var label = new PanTicket($panTicketId, "pan-ticket-image");
         });
      </script>
   </html>
HEREDOC;
}
*/
?>