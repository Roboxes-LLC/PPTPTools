<?php

require_once 'isoInfo.php';
require_once 'jobInfo.php';
require_once 'materialEntry.php';
require_once 'userInfo.php';
require_once '../printer/printJob.php';

abstract class MaterialTicketLabelFields
{
   const FIRST = 0;
   const MATERIAL_TICKET_CODE = MaterialTicketLabelFields::FIRST;
   const MATERIAL = 1;
   const VENDOR = 2;
   const TAG_NUMBER = 3;
   const HEAT_NUMBER = 4;
   const QUANTITY = 5;
   const PIECES = 6;
   const JOB_NUMBER = 7;
   const WC_NUMBER = 8;
   const ISSUED_DATE = 9;
   const ISSUED_USER_ID = 10;
   const ISO = 11;
   const LAST = 12;
   const COUNT = MaterialTicketLabelFields::LAST - MaterialTicketLabelFields::FIRST;
   
   public static function getKeyword($materialTicketLabelField)
   {
      $keywords = array("%id", 
                        "%material",
                        "%vendor",
                        "%tag", 
                        "%heat", 
                        "%quantity", 
                        "%pieces",
                        "%job",
                        "%wc",
                        "%date",
                        "%user",
                        "%iso");
      
      return ($keywords[$materialTicketLabelField]);
   }
}

class MaterialTicket
{
   const UNKNOWN_MATERIAL_TICKET_ID = MaterialEntry::UNKNOWN_ENTRY_ID;
   
   const LABEL_TEMPLATE_FILENAME = "../material/MaterialTicket_Template_8.6.2.label";
   
   public $materialTicketId = MaterialTicket::UNKNOWN_MATERIAL_TICKET_ID;
   
   public $printDescription = "";
   
   public $labelXML = "";
   
   public function __construct($materialEntryId)
   {
      // A material ticket id is the same as a material entry id.
      $this->materialTicketId = $materialEntryId;
      
      $this->labelXML = MaterialTicket::generateLabelXml($this->materialTicketId);
      
      $this->printDescription = MaterialTicket::generatePrintDescription($this->materialTicketId);
   }
   
   public function render()
   {
      $materialEntry = MaterialEntry::load($this->materialTicketId);
      
      if ($materialEntry)
      {      
         $materialTicketCode = MaterialTicket::getMaterialTicketCode($this->materialTicketId);
         
         $materialDescription = "";
         if ($materialEntry->materialInfo)
         {
            $materialDescription = $materialEntry->materialInfo->description;
         }
         
         $vendor = "";
         if ($materialEntry->materialHeatInfo)
         {
            $vendor = MaterialVendor::getMaterialVendor($materialEntry->materialHeatInfo->vendorId);
         }
         
         $issuedDate = "";
         $employeeNumber = "";
         $jobNumber = "";
         $wcNumber = "";
         
         if ($materialEntry->isIssued())
         {
            $jobInfo = JobInfo::load($materialEntry->issuedJobId);
            if ($jobInfo)
            {
               $jobNumber = $jobInfo->jobNumber;
               $wcNumber = $jobInfo->wcNumber;
            }
            
            $userInfo = UserInfo::load($materialEntry->issuedUserId);
            if ($userInfo)
            {
               $employeeNumber = $userInfo->employeeNumber;
            }
            
            $dateTime = new DateTime($materialEntry->issuedDateTime, new DateTimeZone('America/New_York'));
            $issuedDate = $dateTime->format("m-d-Y");
         }
         
         $isoNumber = IsoInfo::getIsoNumber(IsoDoc::MATERIAL_TICKET);
         
         echo
<<<HEREDOC
         <div class="material-ticket flex-vertical">
            <div>
               $materialDescription
            </div>
            <div class="flex-horizontal">
               <div class="flex-vertical">
                  <div><b>Vendor: </b>$vendor</div>               
                  <div><b>Tag: </b>$materialEntry->tagNumber</div> 
                  <div><b>Heat: </b>{$materialEntry->materialHeatInfo->internalHeatNumber}</div>
                  <div><b>Quantity: </b>{$materialEntry->getQuantity()}</div>
                  <div><b>Pieces: </b>$materialEntry->pieces</div> 
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
            <div>$materialTicketCode</div>
            <div><i>ISO $isoNumber</i></div>
         </div>
HEREDOC;
      }
   }
   
   public static function getMaterialTicketCode($materialTicketId)
   {
      return (sprintf('%04X', $materialTicketId));
   }
   
   public static function getMaterialTicketId($materialTicketCode)
   {
      return (ctype_xdigit($materialTicketCode) ? hexdec($materialTicketCode) : MaterialTicket::UNKNOWN_MATERIAL_TICKET_ID);
   }
   
   private static function generatePrintDescription($materialTicketCode)
   {
      $description = "MaterialTicket_" . MaterialTicket::getMaterialTicketCode($materialTicketCode) . ".label";
      
      return ($description);
   }
   
   private static function generateLabelXml($materialEntryId)
   {
      $xml = "";
      
      $materialEntry = MaterialEntry::load($materialEntryId);
      
      if ($materialEntry)
      {
         $materialTicketCode = MaterialTicket::getMaterialTicketCode($materialEntryId);
         
         $materialDescription = "";
         if ($materialEntry->materialInfo)
         {
            $materialDescription = $materialEntry->materialInfo->description;
         }
         
         $vendor = "";
         if ($materialEntry->materialHeatInfo)
         {
            $vendor = MaterialVendor::getMaterialVendor($materialEntry->materialHeatInfo->vendorId);
         }
   
         $issuedDate = "";
         $employeeNumber = "";
         $jobNumber = "";
         $wcNumber = "";
         
         if ($materialEntry->isIssued())
         {
            $jobInfo = JobInfo::load($materialEntry->issuedJobId);
            if ($jobInfo)
            {
               $jobNumber = $jobInfo->jobNumber;
               $wcNumber = $jobInfo->wcNumber;
            }
            
            $userInfo = UserInfo::load($materialEntry->issuedUserId);
            if ($userInfo)
            {
               $employeeNumber = $userInfo->employeeNumber;
            }
            
            $dateTime = new DateTime($materialEntry->issuedDateTime, new DateTimeZone('America/New_York'));
            $issuedDate = $dateTime->format("m-d-Y");
         }
         
         $isoNumber = IsoInfo::getIsoNumber(IsoDoc::MATERIAL_TICKET);
         
         $file = fopen(MaterialTicket::LABEL_TEMPLATE_FILENAME, "r");
         
         if ($file)
         {
            $xml = fread($file, filesize(MaterialTicket::LABEL_TEMPLATE_FILENAME));
            $xml = substr($xml, 3);  // Three odd characters at beginning when reading from file.
            
            fclose($file);
            
            for ($field = MaterialTicketLabelFields::FIRST; $field < MaterialTicketLabelFields::LAST; $field++)
            {
               switch ($field)
               {
                  case MaterialTicketLabelFields::MATERIAL_TICKET_CODE:
                  {
                     $xml = str_replace(MaterialTicketLabelFields::getKeyword($field), $materialTicketCode, $xml);
                     break;
                  }
                     
                  case MaterialTicketLabelFields::MATERIAL:
                  {
                     $xml = str_replace(MaterialTicketLabelFields::getKeyword($field), $materialDescription, $xml);
                     break;
                  }
                  
                  case MaterialTicketLabelFields::VENDOR:
                  {
                     $xml = str_replace(MaterialTicketLabelFields::getKeyword($field), $vendor, $xml);
                     break;
                  }
                     
                  case MaterialTicketLabelFields::TAG_NUMBER:
                  {
                     $xml = str_replace(MaterialTicketLabelFields::getKeyword($field), $materialEntry->tagNumber, $xml);
                     break;
                  }
                     
                  case MaterialTicketLabelFields::HEAT_NUMBER:
                  {
                     $xml = str_replace(MaterialTicketLabelFields::getKeyword($field), $materialEntry->materialHeatInfo->internalHeatNumber, $xml);
                     break;
                  }
                     
                  case MaterialTicketLabelFields::QUANTITY:
                  {
                     $xml = str_replace(MaterialTicketLabelFields::getKeyword($field), $materialEntry->getQuantity(), $xml);
                     break;
                  }
                     
                  case MaterialTicketLabelFields::PIECES:
                  {
                     $xml = str_replace(MaterialTicketLabelFields::getKeyword($field), $materialEntry->pieces, $xml);
                     break;
                  }
                     
                  case MaterialTicketLabelFields::JOB_NUMBER:
                  {
                     $xml = str_replace(MaterialTicketLabelFields::getKeyword($field), $jobNumber, $xml);
                     break;
                  }
                     
                  case MaterialTicketLabelFields::WC_NUMBER:
                  {
                     $xml = str_replace(MaterialTicketLabelFields::getKeyword($field), $wcNumber, $xml);
                     break;
                  }
                  
                  case MaterialTicketLabelFields::ISSUED_DATE:
                  {
                     $xml = str_replace(MaterialTicketLabelFields::getKeyword($field), $issuedDate, $xml);
                     break;
                  }
                  
                  case MaterialTicketLabelFields::ISSUED_USER_ID:
                  {
                     $xml = str_replace(MaterialTicketLabelFields::getKeyword($field), $employeeNumber, $xml);
                     break;
                  }
                  
                  case MaterialTicketLabelFields::ISO:
                  {
                     $xml = str_replace(MaterialTicketLabelFields::getKeyword($field), $isoNumber, $xml);
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
    isset($_GET["materialTicketId"]))
{
   $materialTicketId = $_GET["materialTicketId"];
   
   $materialTicket = new MaterialTicket($materialTicketId);
   
   echo
<<<HEREDOC
   <html>
      <head>
         <link rel="stylesheet" type="text/css" href="common.css"/>
         <link rel="stylesheet" type="text/css" href="materialTicket.css"/>

         <!--script src="http://www.labelwriter.com/software/dls/sdk/js/DYMO.Label.Framework.3.0.js" type="text/javascript" charset="UTF-8"></script-->
         <script src="../thirdParty/dymo/DYMO.Label.Framework.3.0.js" type="text/javascript" charset="UTF-8"></script>
         <script src="materialTicket.js"></script>
      </head>
      <body>
         <img id="material-ticket-image" src="" alt="pan ticket"/>
HEREDOC;
   
   $materialTicket->render();
   
   echo 
<<<HEREDOC
      </body>
      <script>
         dymo.label.framework.init(function() {
            var label = new MaterialTicket($materialTicketId, "material-ticket-image");
         });
      </script>
   </html>
HEREDOC;
}

?>