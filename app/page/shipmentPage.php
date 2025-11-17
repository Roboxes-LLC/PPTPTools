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
               if (Page::requireParams($params, ["shipmentId", "quantity", "location", "vendorShippedDate", "customerShippedDate"]))
               {
                  $shipmentId = $params->getInt("shipmentId");
                  $newShipment = ($shipmentId == Shipment::UNKNOWN_SHIPMENT_ID);
                  
                  $shipment = null;
                  if ($newShipment && Page::requireParams($params, ["jobNumber"]))  // New entries specify the jobNumber manually.
                  {
                     $shipment = new Shipment();
                     $shipment->author = Authentication::getAuthenticatedUser()->employeeNumber;
                     $shipment->dateTime = Time::now();
                     $shipment->location = ShipmentLocation::WIP;
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
                        // Process uploaded packing lists.
                        //
                        
                        if (isset($_FILES["vendorPackingList"]) && ($_FILES["vendorPackingList"]["name"] != ""))
                        {
                           $uploadStatus = Upload::uploadPackingList($_FILES["vendorPackingList"]);
                           
                           if ($uploadStatus == UploadStatus::UPLOADED)
                           {
                              $filename = basename($_FILES["vendorPackingList"]["name"]);
                              
                              $shipment->vendorPackingList = $filename;
                              
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
                        else if ($shipment->vendorPackingList)
                        {
                           Upload::deletePackingList($shipment->vendorPackingList);
                           
                           $shipment->vendorPackingList = null;
                           
                           if (!Shipment::save($shipment))
                           {
                              $this->error("Database error");
                           }
                        }
                        
                        if (isset($_FILES["customerPackingList"]) && ($_FILES["customerPackingList"]["name"] != ""))
                        {
                           $uploadStatus = Upload::uploadPackingList($_FILES["customerPackingList"]);
                           
                           if ($uploadStatus == UploadStatus::UPLOADED)
                           {
                              $filename = basename($_FILES["customerPackingList"]["name"]);
                              
                              $shipment->customerPackingList = $filename;
                              
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
                        else if ($shipment->customerPackingList)
                        {
                           Upload::deletePackingList($shipment->customerPackingList);
                           
                           $shipment->customerPackingList = null;
                           
                           if (!Shipment::save($shipment))
                           {
                              $this->error("Database error");
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
               if (isset($params["shipmentId"]) || 
                   isset($params["shipmentTicketCode"]))
               {
                  $shipmentId = isset($params["shipmentId"]) ?
                                   $params->getInt("shipmentId") :
                                   hexdec($params->get("shipmentTicketCode"));
                  
                  $shipment = Shipment::load($shipmentId);
                  
                  if ($shipment)
                  {
                     // Augment shipment.
                     ShipmentPage::augmentShipment($shipment);
                     
                     $this->result->shipment = $shipment;
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
                  $shipmentLocation = ShipmentLocation::ALL_ACTIVE;
                  $startDate = null;
                  $endDate = null;
                  
                  if (isset($params["shipmentLocation"]))
                  {
                     $shipmentLocation = $params->getInt("shipmentLocation");
                  }
                  
                  if ($shipmentLocation == ShipmentLocation::CUSTOMER)
                  {
                     $dateTime = Time::dateTimeObject(null);
                     
                     $endDate = Time::endOfDay($dateTime->format(Time::STANDARD_FORMAT));
                     $startDate = Time::startofDay($dateTime->modify("-1 month")->format(Time::STANDARD_FORMAT));
                     
                     if (isset($params["startDate"]))
                     {
                        $startDate = Time::startOfDay($params["startDate"]);
                     }
                     
                     if (isset($params["endDate"]))
                     {
                        $endDate = Time::endOfDay($params["endDate"]);
                     }
                  }
                  
                  $this->result->success = true;
                  $this->result->shipments = ShipmentManager::getShipments($shipmentLocation, $startDate, $endDate);
                  
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
         
         case "split_shipment":
         {
            if ($this->authenticate([Permission::EDIT_SHIPMENT]))
            {
               if (Page::requireParams($params, ["shipmentId", "childQuantity", "childLocation"]))
               {
                  $shipmentId = $params->getInt("shipmentId");
                  $childQuantity = $params->getInt("childQuantity");
                  $childLocation = $params->getInt("childLocation");
                  $author = Authentication::getAuthenticatedUser()->employeeNumber;
                  
                  $shipment = Shipment::load($shipmentId);
                  if ($shipment)
                  {
                     $childShipmentId = ShipmentManager::split($shipmentId, $author, $childQuantity, $childLocation);
                     
                     if ($childShipmentId != Shipment::UNKNOWN_SHIPMENT_ID)
                     {
                        $this->result->success = true;
                        $this->result->shipmentId = $shipmentId;
                        $this->result->childShipmentId = $childShipmentId;
                     }
                     else
                     {
                        $this->error("Database error");
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
      $shipment->location = $params->getInt("location");
      $shipment->vendorShippedDate = $params->get("vendorShippedDate");
      $shipment->customerShippedDate = $params->get("customerShippedDate");
      
      // New entries specify the jobNumber manually.
      if ($params->keyExists("jobNumber"))
      {
         $shipment->jobNumber = $params->get("jobNumber");
      }
   }
   
   private static function augmentShipment(&$shipment)
   {
      global $PACKING_LISTS_DIR;
      
      $shipment->shipmentTicketCode = ShipmentManager::getShipmentTicketCode($shipment->shipmentId);
      $shipment->locationLabel = ShipmentLocation::getLabel($shipment->location);
      $shipment->formattedDateTime = ($shipment->dateTime) ? Time::dateTimeObject($shipment->dateTime)->format("n/j/Y h:i A") : null;
      $shipment->vendorPackingListUrl = $shipment->vendorPackingList ? $PACKING_LISTS_DIR . $shipment->vendorPackingList : null;
      $shipment->customerPackingListUrl = $shipment->customerPackingList ? $PACKING_LISTS_DIR . $shipment->customerPackingList : null;
      $shipment->formattedVendorShippedDate = ($shipment->vendorShippedDate) ? Time::dateTimeObject($shipment->vendorShippedDate)->format("n/j/Y") : null;
      $shipment->formattedCustomerShippedDate = ($shipment->customerShippedDate) ? Time::dateTimeObject($shipment->customerShippedDate)->format("n/j/Y") : null;
      
      // Inspection status.
      $shipment->inspectionStatus = InspectionStatus::UNKNOWN;
      if ($shipment->inspectionId != Inspection::UNKNOWN_INSPECTION_ID)
      {
         $inspection = Inspection::load($shipment->inspectionId, false);  // Don't load inspection results.
         if ($inspection)
         {
            $shipment->inspectionStatus = $inspection->getInspectionStatus();
         }
      }
      $shipment->inspectionStatusLabel = InspectionStatus::getLabel($shipment->inspectionStatus);
      $shipment->inspectionStatusClass = InspectionStatus::getClass($shipment->inspectionStatus);
      
      // Part
      $pptpPartNumber = JobInfo::getJobPrefix($shipment->jobNumber);
      $part = Part::load($pptpPartNumber, false);  // Use PPTP number.
      if ($part)
      {
         $shipment->part = $part;
         
         $customer = Customer::load($part->customerId);
         if ($customer)
         {
            $shipment->part->customerName = $customer->customerName;
         }
      }
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