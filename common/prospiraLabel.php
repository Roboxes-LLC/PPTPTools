<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/isoInfo.php';
require_once ROOT.'/core/component/prospiraDoc.php';
require_once ROOT.'/core/manager/shipmentManager.php';
require_once ROOT.'/printer/printJob.php';

abstract class ProspiraLabelField
{
   const FIRST = 0;
   const PART_NUMBER = ProspiraLabelField::FIRST;
   const MFG_DATE = 1;
   const QUANTITY = 2;
   const CLOCK_NUMBER = 3;
   const SUPPLIER = 4;
   const MFG_TIME = 5;
   const PPTP_NUMBER = 6;
   const LOT_NUMBER = 7;
   const SERIAL_NUMBER = 8;
   const PROSPIRA_BARCODE = 9;
   const LAST = 10;
   const COUNT = ProspiraLabelField::LAST - ProspiraLabelField::FIRST;
   
   public static function getKeyword($prospiraLabelField)
   {
      $keywords = array("%PARTNUMBER", 
                        "%MFGDATE",
                        "%QTY",
                        "%CLOCKNUMBER",
                        "%SUPPLIER", 
                        "%MFGTIME", 
                        "%PPTPNUMBER",
                        "%LOTNUMBER",
                        "%SERIALNUMBER",
                        "%PROSPIRABARCODE"
      );
      
      return ($keywords[$prospiraLabelField]);
   }
}

class ProspiraLabel
{
   const UNKNOWN_SHIPMENT_TICKET_ID = Shipment::UNKNOWN_SHIPMENT_ID;
   
   const LABEL_TEMPLATE_FILENAME = ROOT."/prospiraDoc/ProspiraLabelTemplate_8.6.label";
   
   const SUPPLIER_NAME = "PGH PRECISION";
   
   public $docId = ProspiraDoc::UNKNOWN_DOC_ID;
   
   public $printDescription = "";
   
   public $labelXML = "";
   
   public function __construct($docId)
   {
      $this->docId = $docId;
      
      $this->labelXML = ProspiraLabel::generateLabelXml($this->docId);
      
      $this->printDescription = ProspiraLabel::generatePrintDescription($this->docId);
   }
   
   private static function generatePrintDescription($shipmentTicketCode)
   {
      $description = "ProspiraLabel_" . ShipmentManager::getShipmentTicketCode($shipmentTicketCode) . ".label";
      
      return ($description);
   }
   
   private static function generateLabelXml($docId)
   {
      $xml = "";
      
      $prospiraDoc = ProspiraDoc::load($docId);
            
      if ($prospiraDoc)
      {
         $shipment = $prospiraDoc->shipment;

         $partNumber = "";
         $jobInfo = JobManager::getMostRecentJob($shipment->jobNumber);
         if ($jobInfo)
         {
            $partNumber = JobManager::getCustomerPartNumber($jobInfo->partNumber);
         }
         
         $mfgDate = Time::dateTimeObject($shipment->dateTime)->format("n/j/y");
         $mfgTime = Time::dateTimeObject($shipment->dateTime)->format("h:i A");
         
         $quantity = $shipment->quantity;
         
         $barcode = 
            ProspiraLabel::generateProspiraBarcode(
               $partNumber, 
               $shipment->dateTime, 
               $prospiraDoc->clockNumber, 
               ProspiraLabel::SUPPLIER_NAME, 
               $shipment->dateTime, 
               $jobInfo->partNumber, 
               $prospiraDoc->getLotNumber(),
               $prospiraDoc->serialNumber);
         
         $file = fopen(ProspiraLabel::LABEL_TEMPLATE_FILENAME, "r");
         
         if ($file)
         {
            $xml = fread($file, filesize(ProspiraLabel::LABEL_TEMPLATE_FILENAME));
            $xml = substr($xml, 3);  // Three odd characters at beginning when reading from file.
            
            fclose($file);            
            
            for ($field = ProspiraLabelField::FIRST; $field < ProspiraLabelField::LAST; $field++)
            {
               switch ($field)
               {
                  case ProspiraLabelField::PART_NUMBER:
                  {
                     $xml = str_replace(ProspiraLabelField::getKeyword($field), $partNumber, $xml);
                     break;
                  }
                     
                  case ProspiraLabelField::MFG_DATE:
                  {
                     $xml = str_replace(ProspiraLabelField::getKeyword($field), $mfgDate, $xml);
                     break;
                  }
                  
                  case ProspiraLabelField::QUANTITY:
                  {
                     $xml = str_replace(ProspiraLabelField::getKeyword($field), $quantity, $xml);
                     break;
                  }
                  
                  case ProspiraLabelField::CLOCK_NUMBER:
                  {
                     $xml = str_replace(ProspiraLabelField::getKeyword($field), $prospiraDoc->clockNumber, $xml);
                     break;
                  }
                  
                  case ProspiraLabelField::SUPPLIER:
                  {
                     $xml = str_replace(ProspiraLabelField::getKeyword($field), ProspiraLabel::SUPPLIER_NAME, $xml);
                     break;
                  }
               
                  case ProspiraLabelField::MFG_TIME:
                  {
                     $xml = str_replace(ProspiraLabelField::getKeyword($field), $mfgTime, $xml);
                     break;
                  }
                  
                  case ProspiraLabelField::PPTP_NUMBER:
                  {
                     $xml = str_replace(ProspiraLabelField::getKeyword($field), $jobInfo->partNumber, $xml);
                     break;
                  }
                  
                  case ProspiraLabelField::LOT_NUMBER:
                  {
                     $xml = str_replace(ProspiraLabelField::getKeyword($field), $prospiraDoc->getLotNumber(), $xml);
                     break;
                  }
                  
                  case ProspiraLabelField::LOT_NUMBER:
                  {
                     $xml = str_replace(ProspiraLabelField::getKeyword($field), $prospiraDoc->lotNumber, $xml);
                     break;
                  }
                  
                  case ProspiraLabelField::SERIAL_NUMBER:
                  {
                     $xml = str_replace(ProspiraLabelField::getKeyword($field), $prospiraDoc->serialNumber, $xml);
                     break;
                  }
                  
                  case ProspiraLabelField::PROSPIRA_BARCODE:
                  {
                     $xml = str_replace(ProspiraLabelField::getKeyword($field), $barcode, $xml);
                     break;
                  }
                     
                  default:
                  {
                     break;
                  }
               }
            }
         }
      }
      
      return ($xml);
   }
   
   static function generateProspiraBarcode(
      $partNumber,
      $mfgDate,
      $clockNumber,
      $supplierName,
      $mfgTime,
      $pptpPartNumber,
      $lotNumber,
      $serialNumber)
   {
      // <PSAM part #><mfg date><quantity><clock #><supplier name><time><supplier part #><supplier lot #><serial #>
      $barcode = "";
         
      // PSAM Part number (8 characters, left padded with spaces)
      $formattedPartNumber = str_pad(substr($partNumber, 0, 8), 8);
      
      // MFG. date (M/D/Y format)
      $formattedMfgDate = Time::dateTimeObject($mfgDate)->format("m/d/y");
      
      // Clock Number is an internal field, pad with 9 0s.
      $formattedClockNumber = str_pad(substr($clockNumber, 0, 9), 9);
      
      // Supplier name (13 characters, left padded spaces)
      $formattedClockNumber = str_pad(substr($clockNumber, 0, 13), 13);
      
      // Time the label was produced (HHMM) 24hour format. 6:00 PM would be 1800.
      $formattedMfgTime = Time::dateTimeObject($mfgTime)->format("hm");
      
      // Supplier part number (15 characters, left padded with spaces)
      $formattedPartNumber = str_pad(substr($pptpPartNumber, 0, 15), 15);
      
      // Supplier lot number (MMDDYYS 12 characters, left padded with spaces, S being shift)
      $formattedLotNumber = str_pad(substr($lotNumber, 0, 12), 12);
      
      // Serial Number (10 characters, prepended with an S) must be unique
      $formattedSerialNumber = str_pad(substr($serialNumber, 0, 10), 10);
      
      $barcode = $formattedPartNumber.$formattedMfgDate.$formattedClockNumber.$formattedMfgTime.$formattedPartNumber.$formattedLotNumber.$formattedSerialNumber;
      
      return ($barcode);
   }
}


if (isset($_GET["preview"]) &&
    isset($_GET["docId"]))
{
   $docId = intval($_GET["docId"]);
   
   echo
<<<HEREDOC
   <html>
      <head>
         <link rel="stylesheet" type="text/css" href="common.css"/>

         <script src="../thirdParty/dymo/DYMO.Label.Framework.3.0.js" type="text/javascript" charset="UTF-8"></script>
         <script src="prospiraLabel.js"></script>
      </head>
      <body>
         <img id="prospira-label-image" src="" alt="Prospira label"/>
      </body>
      <script>
         dymo.label.framework.init(function() {
            var label = new ProspiraLabel($docId, "prospira-label-image");
         });
      </script>
   </html>
HEREDOC;
}

?>