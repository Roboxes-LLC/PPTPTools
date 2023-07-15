<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/printerInfo.php';
require_once ROOT.'/core/common/notification.php';
require_once ROOT.'/core/common/printerAlertEmail.php';
require_once ROOT.'/core/job/job.php';
require_once ROOT.'/core/manager/userManager.php';

// *****************************************************************************
//                               PrinterMonitorStatus

class PrinterMonitorStatus
{
   public $printers;
   
   public function __construct()
   {
      $this->printers = array();
   }
   
   public function initialize($jsonString)
   {
      $parsedStatus = json_decode($jsonString);
      if ($parsedStatus)
      {
         if (property_exists($parsedStatus, "printers"))
         {
            foreach ($parsedStatus->printers as $row)
            {
               $printer = new PrinterInfo();
               $printer->initialize((array)$row);  // Convert back into an array for PrinterInfo::initialize().
               
               $this->printers[] = $printer;
            }
         }
      }
   }
}

// *****************************************************************************
//                               PrinterMonitorJob

class PrinterMonitorJob extends Job
{
   public function __construct()
   {
      parent::__construct();
      
      $this->jobClass = "PrinterMonitorJob";
      $this->status = new PrinterMonitorStatus();
   }
   
   public function initialize($row)
   {
      parent::initialize($row); 
      
      if (isset($row["status"]))
      {
         $this->status->initialize($row["status"]);
      }
   }
   
   public function run()
   {
      echo "Running PrinterMonitorJob!<br>";
      
      $printers = PrinterMonitorJob::getPrinters();
      
      $addedPrinters = PrinterMonitorJob::getAddedPrinters($printers);
      $deletedPrinters = PrinterMonitorJob::getDeletedPrinters($printers);
      
      // Check for a change.
      if ((count($addedPrinters) + count($deletedPrinters)) > 0)
      {
         $email = new PrinterAlertEmail($printers, $addedPrinters, $deletedPrinters);
         
         $users = UserManager::getUsersForNotification(Notification::PRINTER_ALERT);
         
         foreach ($users as $user)
         {
            $email->send($user->employeeNumber);
         }
      }
      else
      {
         // echo "No change to printers.<br>";
      }
      
      // Update the job status.
      $this->status->printers = $printers;
   }
   
   // **************************************************************************
   
   private static function getPrinters()
   {
      $printers = array();
      
      $result = PPTPDatabase::getInstance()->getPrinters();
      
      while ($result && ($row = $result->fetch_assoc()))
      {
         $printer = new PrinterInfo();
         $printer->initialize($row);
         
         $printers[] = $printer;
      }
      
      return ($printers);
   }
   
   private function getAddedPrinters($printers)
   {
      $addedPrinters = array();
      
      foreach ($printers as $checkPrinter)
      {
         $filtered = array_filter($this->status->printers, function($printer) use ($checkPrinter) {
            return ($printer->printerName == $checkPrinter->printerName);
         });
         
         if (empty($filtered))
         {
            array_push($addedPrinters, $checkPrinter);
         }
      }
      
      return ($addedPrinters);
   }
   
   private function getDeletedPrinters($printers)
   {
      $deletedPrinters = array();
      
      foreach ($this->status->printers as $checkPrinter)
      {
         $filtered = array_filter($printers, function($printer) use ($checkPrinter) {
            return ($printer->printerName == $checkPrinter->printerName);
         });
         
         if (empty($filtered))
         {
            array_push($deletedPrinters, $checkPrinter);
         }
      }
      
      return ($deletedPrinters);
   }
   
   public static function test()
   {
      $job = Job::load(1);
      if ($job)
      {
         $printers = PrinterMonitorJob::getPrinters();
         
         $addedPrinters = $job->getAddedPrinters($printers);
         $deletedPrinters = $job->getDeletedPrinters($printers);
         
         $email = new PrinterAlertEmail($printers, $addedPrinters, $deletedPrinters);
         
         echo $email->getHtml();
         
         $users = UserManager::getUsersForNotification(Notification::PRINTER_ALERT);

         foreach ($users as $user)
         {
            var_dump($email->send($user->employeeNumber));
         }
      }
      else
      {
         echo "Failed to load job";
      }
   }
}

// Testing
//PrinterMonitorJob::test();

?>