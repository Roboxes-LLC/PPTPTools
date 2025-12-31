<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/core/common/pptpDatabase.php';
require_once ROOT.'/core/component/shipment.php';
require_once ROOT.'/core/manager/prospiraDocManager.php';
require_once ROOT.'/printer/printJob.php';

class ShipmentManager
{
   public static function getShipments($shipmentLocation, $startDate = null, $endDate = null)
   {
      $shipments = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getShipments($shipmentLocation, $startDate, $endDate);
      
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
   
   public static function onFinalInspectionCreated($inspectionId)
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
         $shipment->location = ShipmentLocation::WIP;
         
         Shipment::save($shipment);
         
         ProspiraDocManager::onShipmentCreated($shipment->shipmentId);
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
   
   public static function getShipmentsByPart($location, $partNumber)
   {
      $shipments = [];
      
      $result = PPTPDatabaseAlt::getInstance()->getShipmentsByPart($location, $partNumber);
      
      foreach ($result as $row)
      {
         $shipment = new Shipment();
         $shipment->initialize($row);
         
         $shipments[] = $shipment;
      }
      
      return ($shipments);
   }
   
   public static function getShipmentTicketCode($shipmentId)
   {
      $shipmentTicketCode = null;
      
      $shipment = Shipment::load($shipmentId);
      
      if ($shipment)
      {
         if ($shipment->parentShipmentId != Shipment::UNKNOWN_SHIPMENT_ID)
         {
            // <hex ancestorId>.<childIndex>
            $shipmentTicketCode = sprintf('%04X', $shipment->parentShipmentId) . "." . ($shipment->childIndex + 1);
         }
         else
         {
            // <hex shipmentId>
            $shipmentTicketCode = sprintf('%04X', $shipment->shipmentId);
         }
      }
      
      return ($shipmentTicketCode);
   }
   
   public static function getNextChildTicketCode($shipmentId)
   {
      $shipmentTicketCode = null;
      
      $shipment = Shipment::load($shipmentId);
      
      if ($shipment)
      {
         $parentShipmentId = ($shipment->parentShipmentId != Shipment::UNKNOWN_SHIPMENT_ID) ?
                                $shipment->parentShipmentId :
                                $shipment->shipmentId;
         
         $nextChildIndex = 0;

         $children = Shipment::getChildren($parentShipmentId);
         $nextChildIndex = (count($children) > 0) ? (end($children)->childIndex + 1) : 0;

         $shipmentTicketCode .=  ShipmentManager::getShipmentTicketCode($parentShipmentId) . "." . ($nextChildIndex + 1);
      }
      
      return ($shipmentTicketCode);
   }
   
   public static function split($shipmentId, $author, $childQuantity, $childLocation)
   {
      $childShipmentId = Shipment::UNKNOWN_SHIPMENT_ID;
      
      $shipment = Shipment::load($shipmentId);
      
      if ($shipment)
      {
         $childShipment = new Shipment();
         $childShipment->parentShipmentId = ($shipment->parentShipmentId != Shipment::UNKNOWN_SHIPMENT_ID) ?
                                               $shipment->parentShipmentId :
                                               $shipment->shipmentId;
         
         $siblings = Shipment::getChildren($childShipment->parentShipmentId);
         $childShipment->childIndex = (count($siblings) > 0) ? (end($siblings)->childIndex + 1) : 0;
         
         $childShipment->dateTime = Time::now();
         $childShipment->author = $author;
         $childShipment->jobNumber = $shipment->jobNumber;
         $childShipment->inspectionId = $shipment->inspectionId;
         $childShipment->quantity =  $childQuantity;
         $childShipment->location = $childLocation;
         $childShipment->vendorPackingList = $shipment->vendorPackingList;
         $childShipment->customerPackingList = $shipment->customerPackingList;
         $childShipment->vendorShippedDate = $shipment->vendorShippedDate;
         $childShipment->customerShippedDate = $shipment->customerShippedDate;
         
         Shipment::save($childShipment);
         $childShipmentId = $childShipment->shipmentId;
         
         $shipment->quantity = ($shipment->quantity - $childQuantity);
         Shipment::save($shipment);
      }
      
      return ($childShipmentId);
   }
   
   public static function applyAudit($auditId)
   {
      $success = false;
      
      $audit = Audit::load($auditId);
      
      if ($audit)
      {
         $success = true;
         
         foreach ($audit->lineItems as $auditLine)
         {
            $shipment = Shipment::load($auditLine->shipmentId);
            
            if ($shipment)
            {
               if ($auditLine->adjustedLocation != ShipmentLocation::UNKNOWN)
               {
                  $shipment->location = $auditLine->adjustedLocation;
               }
               
               if (!is_null($auditLine->adjustedCount))
               {
                  $shipment->quantity = $auditLine->adjustedCount;
               }
               
               Shipment::save($shipment);
            }
         }
      }
      
      return ($success);
   }
}

?>