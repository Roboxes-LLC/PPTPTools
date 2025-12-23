<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/pptpDatabase.php';
require_once ROOT.'/core/component/prospiraDoc.php';

class ProspiraDocManager
{
   public static function getProspiraDocs($startDate = null, $endDate = null)
   {
      $prospiraDocs = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getProspiraDocs($startDate, $endDate);
      
      foreach ($result as $row)
      {
         $prospiraDoc = new ProspiraDoc();
         $prospiraDoc->initialize($row);
         
         $prospiraDocs[] = $prospiraDoc;
      }
      
      return ($prospiraDocs);
   }
   
   public static function printProspiraLabel($docId, $printerName, $copies)
   {
      $success = false;
      
      $label = new ProspiraLabel($docId);
      
      if ($label)
      {
         $printJob = new PrintJob();
         $printJob->owner = Authentication::getAuthenticatedUser()->employeeNumber;
         $printJob->dateTime = Time::now("Y-m-d H:i:s");
         $printJob->description = $label->printDescription;
         $printJob->printerName = $printerName;
         $printJob->copies = $copies;
         $printJob->status = PrintJobStatus::QUEUED;
         $printJob->xml = $label->labelXML;
         
         $success = (PPTPDatabase::getInstance()->newPrintJob($printJob) != false);
      }
      
      return ($success);
   }
}

?>