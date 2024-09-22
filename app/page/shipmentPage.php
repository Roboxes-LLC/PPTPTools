<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/app/page/page.php';
require_once ROOT.'/common/shipmentTicket.php';
require_once ROOT.'/core/manager/shipmentManager.php';

class ShipmentPage extends Page
{
   public function handleRequest($params)
   {
      switch ($this->getRequest($params))
      {
         case "save_shipment":
         {
            /*
            if ($this->authenticate([Permission::EDIT_SCHEDULE]))
            {
               if (Page::requireParams($params, ["jobId", "startDate"]))
               {
                  $jobId = $params->getInt("jobId");
                  $startDate = $params->get("startDate");
                  $endDate = $params->keyExists("endDate") ? $params->get("endDate") : null;

                  
                  $shipment = new ShipmentEntry();
                  $shipment->jobId = $jobId;
                  $shipment->startDate = $startDate;
                  $shipment->endDate = $endDate;
                  $shipment->employeeNumber = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
                  
                  if (ShipmentEntry::save($shipment))
                  {
                     $this->result->success = true;
                     $this->result->scheduleEntry = ShipmentEntry::load($shipment->entryId);
                  }
                  else
                  {
                     $this->error("Database error");
                  }
               }
            }
            */
            break;
         }
         
         case "delete_shipment":
         {
            if ($this->authenticate([Permission::EDIT_SHIPMENT]))
            {
               if (Page::requireParams($params, ["shipmentId"]))
               {
                  $shipmentId = $params->getInt("shipmentId");
                  
                  if (Shipment::delete($shipmentId))
                  {
                     $this->result->success = true;
                  }
                  else
                  {
                     $this->error("Database error");
                  }
               }
            }
            break;
         }
         
         case "fetch":
         {
            if ($this->authenticate([Permission::VIEW_SHIPMENT]))
            {
               // Fetch single component.
               if (isset($params["shipmentId"]))
               {
                  $shipmentId = $params->getInt("shipmentId");
                  
                  $shipment = Shipment::load($shipmentId);
                  
                  if ($shipment)
                  {
                     // Augment shipment.
                     ShipmentPage::augmentShipment($shipment);
                     
                     $this->result->scheduleEntry = $shipment;
                     $this->result->success = true;
                  }
                  else
                  {
                     $this->error("Invalid entry id [$shipmentId]");
                  }
               }
               // Fetch all components.
               else
               {
                  $this->result->success = true;
                  $this->result->shipments = ShipmentManager::getShipments();
                  
                  // Augment shipment.
                  foreach ($this->result->shipments as $shipment)
                  {
                     ShipmentPage::augmentShipment($shipment);
                  }
               }
            }
            break;
         }
            
         case "fetch_ticket":
         {
            if ($this->authenticate([Permission::VIEW_SHIPMENT]))
            {
               if (Page::requireParams($params, ["shipmentTicketId"]))
               {
                  $ticket = new ShipmentTicket($params->getInt("shipmentTicketId"));
                  
                  if ($ticket)
                  {
                     $this->result->success = true;
                     $this->result->shipmentTicketId = $ticket->shipmentTicketId;
                     $this->result->labelXML = $ticket->labelXML;
                  }
                  else
                  {
                     $this->error("Failed to create shipment ticket.");
                  }
               }
            }
            break;
         }
         
         case "print_ticket":
         {
            if ($this->authenticate([Permission::VIEW_SHIPMENT]))
            {
               if (Page::requireParams($params, ["shipmentTicketId", "printerName", "copies"]))
               {
                  $ticketId = $params->getInt("shipmentTicketId");
                  $printerName = $params->get("printerName");
                  $copies = $params->getInt("copies");
                  
                  $ticket = new ShipmentTicket($ticketId);
               
                  if ($ticket)
                  {
                     if (ShipmentManager::printShipmentTicket($ticketId, $printerName, $copies))
                     {
                        $this->result->success = true;
                     }
                     else 
                     {
                        $this->error("Print error");
                     }
                  }
               
                  // Store preferred printer for session.
                  $_SESSION["preferredPrinter"] = $printerName;
               }
            }
            break;
         }
      
         default:
         {
            $this->error("Unsupported command: " . $this->getRequest($params));
            break;
         }
      }
      
      echo json_encode($this->result);
   }
   
   private static function augmentShipment(&$shipment)
   {
      $shipment->shipmentTicketCode = ShipmentTicket::getShipmentTicketCode($shipment->shipmentId);
      $shipment->formattedDateTime = ($shipment->dateTime) ? Time::dateTimeObject($shipment->dateTime)->format("n-j-Y") : null;
   }
}

?>