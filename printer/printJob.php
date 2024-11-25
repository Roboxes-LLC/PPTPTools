<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/printer/printDefs.php';

class PrintJob
{
   const UNKNOWN_PRINT_JOB_ID = 0;
   
   const UNKNOWN_OWNER_ID = 0;
   
   const UNKNOWN_PRINTER_NAME = "";
   
   const MIN_COPIES = 1;
   
   public $printJobId;
   public $owner;
   public $dateTime;
   public $description;
   public $printerName;
   public $copies;
   public $status;
   public $xml;
   
   public function __construct()
   {
      $this->printJobId = PrintJob::UNKNOWN_PRINT_JOB_ID;
      $this->owner = PrintJob::UNKNOWN_OWNER_ID;
      $this->dateTime = null;
      $this->description = "";
      $this->printerName = PrintJob::UNKNOWN_PRINTER_NAME;
      $this->copies = PrintJob::MIN_COPIES;
      $this->status = PrintJobStatus::UNKNOWN;
      $this->xml = "";
   }
   
   public static function load($printJobId)
   {
      $printJob = null;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getPrintJob($printJobId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $printJob = new PrintJob();
            
            $printJob->printJobId = intval($row['printJobId']);
            $printJob->owner = intval($row['owner']);
            $printJob->dateTime = Time::fromMySqlDate($row['dateTime'], "Y-m-d H:i:s");
            $printJob->description = $row['description'];
            $printJob->printerName = $row['printerName'];
            $printJob->copies = intval($row['copies']);
            $printJob->status = intval($row['status']);
            $printJob->xml = $row['xml'];
         }
      }
      
      return ($printJob);
   }
}

/*
if (isset($_GET["printJobId"]))
{
   $printJobId = $_GET["printJobId"];
    
   $printJob = PrintJob::load($printJobId);
 
   if ($printJob)
   {
      echo "printJobId: " .  $printJob->printJobId .                       "<br/>";
      echo "owner: " .       $printJob->owner .                            "<br/>";
      echo "dateTime: " .    $printJob->dateTime .                         "<br/>";
      echo "description: " . $printJob->description .                      "<br/>";
      echo "printerName: " . $printJob->printerName .                      "<br/>"; 
      echo "copies: " .      $printJob->copies .                           "<br/>";     
      echo "status: " .      PrintJobStatus::getLabel($printJob->status) . "<br/>";
      echo "xml: " .         htmlspecialchars($printJob->xml) .            "<br/>";
   }
   else
   {
     echo "No print job found.";
   }
}
*/

?>