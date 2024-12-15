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
   
   public static function onFinalInspectionCompleted($inspectionId)
   {
      $inspection = Inspection::load($inspectionId, true);
      
      if ($inspection)
      {
         $shipment = new Shipment();
         $shipment->dateTime = $inspection->dateTime;
         $shipment->author = $inspection->author;
         $shipment->jobNumber = $inspection->jobNumber;
         $shipment->inspectionId = $inspectionId;
         $shipment->quantity = $inspection->quantity;
         $shipment->location = ShipmentLocation::PPTP;
         
         Shipment::save($shipment);
      }
   }
   
   public static function getShipmentFromInspection($inspectionId)
   {
      $shipment = null;
      
      $result = PPTPDatabaseAlt::getInstance()->getShipmentFromInspection($inspectionId);
      
      if ($result && ($row = $result[0]))
      {
         $shipment = new Shipment();
         
         $shipment->initialize($row);
         
         $shipment->inspection = Inspection::load($shipment->inspectionId, false);
      }
      
      return ($shipment);
   }
   
   public static function getTimeCardsForShipment($shipmentId)
   {
      $timeCards = [];
      
      $shipment = Shipment::load($shipmentId);
      
      if ($shipment)
      {
         $inspection = Inspection::load($shipment->inspectionId, false);
         
         if ($inspection && $inspection->startMfgDate)
         {
            $startDate = Time::startOfDay($inspection->startMfgDate);
            $endDate = Time::endOfDay($inspection->dateTime);
            
            $result = PPTPDatabase::getInstance()->getTimeCardsForJob($shipment->jobNumber, $startDate, $endDate);
            
            while ($result && ($row = $result->fetch_assoc()))
            {
               $timeCard = TimeCardInfo::load($row["timeCardId"]);
               
               if ($timeCard)
               {
                  $timeCards[] = $timeCard;
               }
            }
         }
      }
      
      return ($timeCards);
   }
   
   public static function getHeatsForShipment($shipmentId)
   {
      $heats = [];
      
      $timeCards = ShipmentManager::getTimeCardsForShipment($shipmentId);
      
      foreach ($timeCards as $timeCard)
      {
         $result = PPTPDatabase::getInstance()->getMaterialHeat($timeCard->materialNumber, true);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $heat = MaterialHeatInfo::load($row["internalHeatNumber"], true);
            
            if ($heat)
            {
               $heats[$heat->internalHeatNumber] = $heat;
            }
         }
      }
      
      return (array_values($heats));  // Simple trick to use a PHP map as a set.
   }
   
   public static function getActiveShipmentsByPart($partNumber)
   {
      $shipments = [];
      
      $result = PPTPDatabaseAlt::getInstance()->getActiveShipmentsByPart($partNumber);
      
      foreach ($result as $row)
      {
         $shipment = new Shipment();
         $shipment->initialize($row);
         
         $shipments[] = $shipment;
      }
      
      return ($shipments);
   }
}

?>