<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/core/common/pptpDatabase.php';
require_once ROOT.'/core/component/shipment.php';
require_once ROOT.'/printer/printJob.php';

class ShipmentManager
{
   public static function getShipments()
   {
      $shipments = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getShipments();
      
      foreach ($result as $row)
      {
         $shipment = Shipment::load(intval($row["shipmentId"]));
         
         $shipments[] = $shipment;
      }
      
      return ($shipments);
   }
   
   public static function printShipmentTicket($ticketId, $printerName, $copies)
   {
      $success = false;
      
      $ticket = new ShipmentTicket($ticketId);
      
      if ($ticket)
      {
         $printJob = new PrintJob();
         $printJob->owner = Authentication::getAuthenticatedUser()->employeeNumber;
         $printJob->dateTime = Time::now("Y-m-d H:i:s");
         $printJob->description = $ticket->printDescription;
         $printJob->printerName = $printerName;
         $printJob->copies = $copies;
         $printJob->status = PrintJobStatus::QUEUED;
         $printJob->xml = $ticket->labelXML;
         
         $success = (PPTPDatabase::getInstance()->newPrintJob($printJob) != false);
      }
      
      return ($success);
   }
}

?>