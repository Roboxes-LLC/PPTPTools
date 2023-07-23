<?php
require_once 'database.php';

class PrinterInfo
{
   const UNKNOWN_PRINTER_NAME = "";
   
   const UNKNOWN_MODEL = "";
   
   const ONLINE_THRESHOLD = 20;  // seconds
   
   public $printerName = PrinterInfo::UNKNOWN_PRINTER_NAME;
   public $model = PrinterInfo::UNKNOWN_MODEL;
   public $isConnected = false;
   public $lastContact = null;
   
   public static function load($printerName)
   {
      $printerInfo = null;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getPrinter($printerName);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $printerInfo = new PrinterInfo();
            
            $printerInfo->initialize($row);
         }
      }
      
      return ($printerInfo);
   }
   
   public function initialize($row)
   {
      $this->printerName = $row['printerName'];
      $this->model = $row['model'];
      $this->isConnected = boolval($row['isConnected']);
      $this->lastContact = Time::fromMySqlDate($row['lastContact'], "Y-m-d H:i:s");
   }
   
   public function isCurrent()
   {
      $diffSeconds = Time::differenceSeconds($this->lastContact, Time::now("Y-m-d H:i:s"));

      return ($diffSeconds <= PrinterInfo::ONLINE_THRESHOLD);
   }
   
   public function getDisplayName()
   {
      return (getPrinterDisplayName($this->printerName));
   }
}

/*
if (isset($_GET["printerName"]))
{
   $printerName = $_GET["printerName"];
   $printerInfo = PrinterInfo::load($printerName);
   
   if ($printerInfo)
   {
      echo "printerName: " . $printerInfo->printerName . "<br/>";
      echo "model: " .       $printerInfo->model .       "<br/>";
      echo "isConnected: " . $printerInfo->isConnected . "<br/>";
      echo "lastContact: " . $printerInfo->lastContact . "<br/>";
      
      echo "isCurrent: " .   ($printerInfo->isCurrent() ? "true" : "false") . "<br/>"; 
   }
   else
   {
      echo "No printer info found.";
   }
}
*/
?>