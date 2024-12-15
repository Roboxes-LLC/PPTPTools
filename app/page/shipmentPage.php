<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/app/page/page.php';
require_once ROOT.'/common/panTicket.php';
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
            if ($this->authenticate([Permission::EDIT_SHIPMENT]))
            {
               if (Page::requireParams($params, ["shipmentId", "quantity", "packingListNumber", "location"]))
               {
                  $shipmentId = $params->getInt("shipmentId");
                  $newShipment = ($shipmentId == Shipment::UNKNOWN_SHIPMENT_ID);
                  
                  $shipment = null;
                  if ($newShipment && Page::requireParams($params, ["jobNumber"]))  // New entries specify the jobNumber manually.
                  {
                     $shipment = new Shipment();
                     $shipment->author = Authentication::getAuthenticatedUser()->employeeNumber;
                     $shipment->dateTime = Time::now();
                     $shipment->location = ShipmentLocation::PPTP;
                  }
                  else
                  {
                     $shipment = Shipment::load($shipmentId);
                     
                     if (!$shipment)
                     {
                        $shipment = null;
                        $this->error("Invalid shipment id [$shipmentId]");
                     }
                  }
                  
                  if ($shipment)
                  {
                     ShipmentPage::getShipmentParams($shipment, $params);
                     
                     if (Shipment::save($shipment))
                     {
                        $this->result->shipmentId = $shipment->shipmentId;
                        $this->result->shipment = $shipment;
                        $this->result->success = true;
                        
                        //
                        // Process uploaded packing list.
                        //
                        
                        if (isset($_FILES["packingList"]) && ($_FILES["packingList"]["name"] != ""))
                        {
                           $uploadStatus = Upload::uploadPackingList($_FILES["packingList"]);
                           
                           if ($uploadStatus == UploadStatus::UPLOADED)
                           {
                              $filename = basename($_FILES["packingList"]["name"]);
                              
                              $shipment->packingList = $filename;
                              
                              if (!Shipment::save($shipment))
                              {
                                 $this->error("Database error");
                              }
                           }
                           else
                           {
                              $this->error("File upload failed! " . UploadStatus::toString($uploadStatus));
                           }
                        }
                        
                        /*
                         ActivityLog::logComponentActivity(
                            Authentication::getAuthenticatedUser()->employeeNumber,
                            ($newShipment ? ActivityType::ADD_SHIPMENT : ActivityType::EDIT_SHIPMENT),
                            $shipment->shipmentId,
                            $shipment->???);
                         */
                     }
                     else
                     {
                        $this->error("Database error");
                     }
                     
                  }
               }
            }
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
                     $this->error("Invalid shipment id [$shipmentId]");
                  }
               }
               // Fetch active shipments for part.
               else if (isset($params["pptpNumber"]) || isset($params["customerNumber"]))
               {
                  $partNumber = null;
                  if (isset($params["pptpNumber"]))
                  {
                     $partNumber = $params["pptpNumber"];
                  }
                  else
                  {
                     $partNumber = PartManager::getPPTPPartNumber($params["customerNumber"]);
                  }

                  $this->result->success = true;
                  $this->result->shipments = ShipmentManager::getActiveShipmentsByPart($partNumber);
                  
                  // Augment shipment.
                  foreach ($this->result->shipments as $shipment)
                  {
                     ShipmentPage::augmentShipment($shipment);
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
         
         case "fetch_time_cards":
         {
            if ($this->authenticate([Permission::VIEW_SHIPMENT]))
            {
               if (Page::requireParams($params, ["shipmentId"]))
               {
                  $shipmentId = $params->getInt("shipmentId");
                  
                  $shipment = Shipment::load($shipmentId);
                  
                  if ($shipment)
                  {
                     $this->result->success = true;
                     $this->result->shipmentId = $shipmentId;
                     $this->result->timeCards = ShipmentManager::getTimeCardsForShipment($shipmentId);
                     
                     // Augment data.
                     foreach ($this->result->timeCards as $timeCardInfo)
                     {
                        ShipmentPage::augmentTimeCard($timeCardInfo);
                     }
                  }
                  else
                  {
                     $this->error("Invalid shipment id [$shipmentId]");
                  }
               }
            }
            break;
         }
         
         case "fetch_heats":
         {
            if ($this->authenticate([Permission::VIEW_SHIPMENT]))
            {
               if (Page::requireParams($params, ["shipmentId"]))
               {
                  $shipmentId = $params->getInt("shipmentId");
                  
                  $shipment = Shipment::load($shipmentId);
                  
                  if ($shipment)
                  {
                     $this->result->success = true;
                     $this->result->shipmentId = $shipmentId;
                     $this->result->heats = ShipmentManager::getHeatsForShipment($shipmentId);
                     
                     // Augment data.
                     foreach ($this->result->heats as $materialHeatInfo)
                     {
                        ShipmentPage::augmentHeat($materialHeatInfo);
                     }
                  }
                  else
                  {
                     $this->error("Invalid shipment id [$shipmentId]");
                  }
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
   
   private function getShipmentParams(&$shipment, $params)
   {
      $shipment->quantity = $params->getInt("quantity");
      $shipment->packingListNumber = $params->get("packingListNumber");
      $shipment->location = $params->getInt("location");
      
      // New entries specify the jobNumber manually.
      if ($params->keyExists("jobNumber"))
      {
         $shipment->jobNumber = $params->get("jobNumber");
      }
   }
   
   private static function augmentShipment(&$shipment)
   {
      global $PACKING_LISTS_DIR;
      
      $shipment->shipmentTicketCode = ShipmentTicket::getShipmentTicketCode($shipment->shipmentId);
      $shipment->locationLabel = ShipmentLocation::getLabel($shipment->location);
      $shipment->formattedDateTime = ($shipment->dateTime) ? Time::dateTimeObject($shipment->dateTime)->format("n/j/Y h:i A") : null;
      $shipment->packingListUrl = $shipment->packingList ? $PACKING_LISTS_DIR . $shipment->packingList : null;
   }
   
   private static function augmentTimeCard(&$timeCardInfo)
   {
      $timeCardInfo->panTicketCode = PanTicket::getPanTicketCode($timeCardInfo->timeCardId);
      
      $userInfo = UserInfo::load($timeCardInfo->employeeNumber);
      if ($userInfo)
      {
         $timeCardInfo->operator = $userInfo->getFullName() . " (" . $timeCardInfo->employeeNumber . ")";
      }
      
      $jobInfo = JobInfo::load($timeCardInfo->jobId);
      if ($jobInfo)
      {
         $timeCardInfo->jobNumber = $jobInfo->jobNumber;
         $timeCardInfo->wcNumber = $jobInfo->wcNumber;
         $timeCardInfo->wcLabel = JobInfo::getWcLabel($jobInfo->wcNumber);
      }
      $timeCardInfo->formattedMfgDate = ($timeCardInfo->manufactureDate) ? Time::dateTimeObject($timeCardInfo->manufactureDate)->format("n/j/Y") : null;
   }
   
   private static function augmentHeat(&$materialHeatInfo)
   {
      if ($materialHeatInfo->materialInfo)
      {
         $vendors = MaterialVendor::getMaterialVendors();
         
         $materialHeatInfo->vendorName = $vendors[$materialHeatInfo->vendorId];
         $materialHeatInfo->materialInfo->typeLabel = MaterialType::getLabel($materialHeatInfo->materialInfo->type);
      }
      
      $materialHeatInfo->materialTicketCode = "???";  // TODO: Should these be material entries?
      
   }
}

?>