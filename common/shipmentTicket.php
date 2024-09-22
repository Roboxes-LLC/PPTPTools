<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/isoInfo.php';
require_once ROOT.'/core/component/shipment.php';
require_once ROOT.'/printer/printJob.php';

abstract class ShipmentTicketLabelFields
{
   const FIRST = 0;
   const SHIPMENT_TICKET_CODE = ShipmentTicketLabelFields::FIRST;
   const DATE = 1;
   const CUSTOMER = 2;
   const PART_NUMBER = 3;
   const JOB_NUMBER = 4;
   const HEAT_NUMBER = 5;
   const QUANTITY = 6;
   const LAST = 7;
   const COUNT = ShipmentTicketLabelFields::LAST - ShipmentTicketLabelFields::FIRST;
   
   public static function getKeyword($shipmentTicketLabelField)
   {
      $keywords = array("%id", 
                        "%date",
                        "%customer",
                        "%partNumber",
                        "%jobNumber", 
                        "%heat", 
                        "%quantity", 
      );
      
      return ($keywords[$shipmentTicketLabelField]);
   }
}

class ShipmentTicket
{
   const UNKNOWN_SHIPMENT_TICKET_ID = Shipment::UNKNOWN_SHIPMENT_ID;
   
   const LABEL_TEMPLATE_FILENAME = ROOT."/shipment/ShipmentTicket_Template_8.6.2.label";
   
   public $shipmentTicketId = ShipmentTicket::UNKNOWN_SHIPMENT_TICKET_ID;
   
   public $printDescription = "";
   
   public $labelXML = "";
   
   public function __construct($shipmentId)
   {
      // A shipment ticket id is the same as a shipment id.
      $this->shipmentTicketId = $shipmentId;
      
      $this->labelXML = ShipmentTicket::generateLabelXml($this->shipmentTicketId);
      
      $this->printDescription = ShipmentTicket::generatePrintDescription($this->shipmentTicketId);
   }
   
   /*
   public function render()
   {
      $shipmentEntry = MaterialEntry::load($this->materialTicketId);
      
      if ($shipmentEntry)
      {      
         $shipmentTicketCode = ShipmentTicket::getShipmentTicketCode($this->materialTicketId);
         
         $shipmentDescription = "";
         if ($shipmentEntry->materialHeatInfo)
         {
            $shipmentDescription = $shipmentEntry->materialHeatInfo->materialInfo->getMaterialDescription();
         }
         
         $dateTime = new DateTime($shipmentEntry->receivedDateTime, new DateTimeZone('America/New_York'));
         $receivedDate = $dateTime->format("m-d-Y");
                  
         $vendor = "";
         if ($shipmentEntry->materialHeatInfo)
         {
            $vendor = MaterialVendor::getMaterialVendor($shipmentEntry->materialHeatInfo->vendorId);
         }
         
         $issuedDate = "";
         $employeeNumber = "";
         $jobNumber = "";
         $wcNumber = "";
         
         if ($shipmentEntry->isIssued())
         {
            $jobInfo = JobInfo::load($shipmentEntry->issuedJobId);
            if ($jobInfo)
            {
               $jobNumber = $jobInfo->jobNumber;
               $wcNumber = $jobInfo->wcNumber;
            }
            
            $userInfo = UserInfo::load($shipmentEntry->issuedUserId);
            if ($userInfo)
            {
               $employeeNumber = $userInfo->employeeNumber;
            }
            
            $dateTime = new DateTime($shipmentEntry->issuedDateTime, new DateTimeZone('America/New_York'));
            $issuedDate = $dateTime->format("m-d-Y");
         }
         
         $isoNumber = IsoInfo::getIsoNumber(IsoDoc::MATERIAL_TICKET);
         
         echo
<<<HEREDOC
         <div class="material-ticket flex-vertical">
            <div>
               $shipmentDescription
            </div>
            <div class="flex-horizontal">
               <div class="flex-vertical">
                  <div><b>Received: </b>$receivedDate</div>                  
                  <div><b>Vendor: </b>$vendor</div>               
                  <div><b>Tag: </b>$shipmentEntry->tagNumber</div> 
                  <div><b>Heat: </b>{$shipmentEntry->materialHeatInfo->internalHeatNumber}</div>
                  <div><b>Quantity: </b>{$shipmentEntry->getQuantity()}</div>
                  <div><b>Pieces: </b>$shipmentEntry->pieces</div> 
               </div>
               <div class="flex-vertical">
                  <div><b>Job</b></div>
                  <div class="flex-vertical">
                     <div>$jobNumber</div>
                     <div><b>WC: </b>$wcNumber</div> 
                     <div><b>Inspector: </b>$employeeNumber</div> 
                     <div><b>Issued: </b>$issuedDate</div>
                  </div> 
               </div>
            </div>
            <div>$shipmentTicketCode</div>
            <div><i>ISO $isoNumber</i></div>
         </div>
HEREDOC;
      }
   }
   */
   
   public static function getShipmentTicketCode($shipmentTicketId)
   {
      return (sprintf('%04X', $shipmentTicketId));
   }
   
   public static function getShipmentTicketId($shipmentTicketCode)
   {
      return (ctype_xdigit($shipmentTicketCode) ? hexdec($shipmentTicketCode) : ShipmentTicket::UNKNOWN_MATERIAL_TICKET_ID);
   }
   
   private static function generatePrintDescription($shipmentTicketCode)
   {
      $description = "ShipmentTicket_" . ShipmentTicket::getShipmentTicketCode($shipmentTicketCode) . ".label";
      
      return ($description);
   }
   
   private static function generateLabelXml($shipmentId)
   {
      $xml = "";
      
      $shipment = Shipment::load($shipmentId);
            
      if ($shipment)
      {
         $shipmentTicketCode = ShipmentTicket::getShipmentTicketCode($shipmentId);
         
         $jobInfo = JobInfo::load($shipment->jobId);
         $partNumber = JobManager::getCustomerPartNumber($jobInfo->partNumber);
         $customer = JobManager::getCustomer($shipment->jobId);
         
         $date = Time::dateTimeObject($shipment->dateTime)->format("n-j-Y");
         
         $customerName = "";
         if ($customer)
         {
            $customerName = $customer->customerName;
         }

         $partNumber = JobManager::getCustomerPartNumber($jobInfo->partNumber);
         
         $jobNumber = $jobInfo->jobNumber;
         
         $heatNumber = "";  //  How to link heat?
         
         $quantity = $shipment->quantity;
         
         $file = fopen(ShipmentTicket::LABEL_TEMPLATE_FILENAME, "r");
         
         if ($file)
         {
            $xml = fread($file, filesize(ShipmentTicket::LABEL_TEMPLATE_FILENAME));
            $xml = substr($xml, 3);  // Three odd characters at beginning when reading from file.
            
            fclose($file);            
            
            for ($field = ShipmentTicketLabelFields::FIRST; $field < ShipmentTicketLabelFields::LAST; $field++)
            {
               switch ($field)
               {
                  case ShipmentTicketLabelFields::SHIPMENT_TICKET_CODE:
                  {
                     $xml = str_replace(ShipmentTicketLabelFields::getKeyword($field), $shipmentTicketCode, $xml);
                     break;
                  }
                     
                  case ShipmentTicketLabelFields::DATE:
                  {
                     $xml = str_replace(ShipmentTicketLabelFields::getKeyword($field), $date, $xml);
                     break;
                  }
                  
                  case ShipmentTicketLabelFields::CUSTOMER:
                  {
                     $xml = str_replace(ShipmentTicketLabelFields::getKeyword($field), $customerName, $xml);
                     break;
                  }
                  
                  case ShipmentTicketLabelFields::PART_NUMBER:
                  {
                     $xml = str_replace(ShipmentTicketLabelFields::getKeyword($field), $partNumber, $xml);
                     break;
                  }
                     
                  case ShipmentTicketLabelFields::JOB_NUMBER:
                  {
                     $xml = str_replace(ShipmentTicketLabelFields::getKeyword($field), $jobNumber, $xml);
                     break;
                  }
                     
                  case ShipmentTicketLabelFields::HEAT_NUMBER:
                  {
                     $xml = str_replace(ShipmentTicketLabelFields::getKeyword($field), $heatNumber, $xml);
                     break;
                  }
                     
                  case ShipmentTicketLabelFields::QUANTITY:
                  {
                     $xml = str_replace(ShipmentTicketLabelFields::getKeyword($field), $quantity, $xml);
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
}


if (isset($_GET["preview"]) &&
    isset($_GET["shipmentTicketId"]))
{
   $shipmentTicketId = $_GET["shipmentTicketId"];
   
   $shipmentTicket = new ShipmentTicket($shipmentTicketId);
   
   echo
<<<HEREDOC
   <html>
      <head>
         <link rel="stylesheet" type="text/css" href="common.css"/>
         <link rel="stylesheet" type="text/css" href="shipmentTicket.css"/>

         <!--script src="http://www.labelwriter.com/software/dls/sdk/js/DYMO.Label.Framework.3.0.js" type="text/javascript" charset="UTF-8"></script-->
         <script src="../thirdParty/dymo/DYMO.Label.Framework.3.0.js" type="text/javascript" charset="UTF-8"></script>
         <script src="shipmentTicket.js"></script>
      </head>
      <body>
         <img id="shipment-ticket-image" src="" alt="pan ticket"/>
HEREDOC;
   
   $shipmentTicket->render();
   
   echo 
<<<HEREDOC
      </body>
      <script>
         dymo.label.framework.init(function() {
            var label = new ShipmentTicket($shipmentTicketId, "shipment-ticket-image");
         });
      </script>
   </html>
HEREDOC;
}

?>