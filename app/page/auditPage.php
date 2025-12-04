<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/app/page/page.php';
require_once ROOT.'/core/manager/auditManager.php';
require_once ROOT.'/core/manager/notificationManager.php';

class AuditPage extends Page
{
   public function handleRequest($params)
   {
      switch ($this->getRequest($params))
      {
         case "save_audit":
         {
            if ($this->authenticate([Permission::EDIT_AUDIT]))
            {
               if (Page::requireParams($params, ["auditName", "notes"]))
               {
                  $auditId = $params->getInt("auditId");
                  $newAudit = ($auditId == Audit::UNKNOWN_AUDIT_ID);
                   
                  $audit = null;
                  if ($newAudit)
                  {
                     $audit = new Audit();
                      
                     $audit->created = Time::now();
                     $audit->author = Authentication::getAuthenticatedUser()->employeeNumber;
                     $audit->status = AuditStatus::SCHEDULED;
                      
                     $audit->scheduled = Time::now("Y-m-d");  // TODO: Remove.
                  }
                  else
                  {
                     $audit = Audit::load($auditId);
                  }
                   
                  if ($audit)
                  {
                     // Remember the previous viewId to detect a change.
                     $previousLocation = $audit->location;
                     $previousPartNumber = $audit->partNumber;
                     $previousIsAdHoc = $audit->isAdHoc;
                     
                     AuditPage::getAuditParams($audit, $params);
                     
                     // Create initial audit lines.
                     if ($newAudit || 
                         ($audit->location != $previousLocation) || 
                         ($audit->partNumber != $previousPartNumber) ||
                         ($audit->isAdHoc != $previousIsAdHoc))
                     {
                        // Wipe out any old audit lines.
                        $audit->lineItems = [];
                        
                        // Create the audit lines defined in the specified view.
                        $audit->createLineItems();
                     }
                     
                     if (Audit::save($audit))
                     {
                        /*
                        // Log activity.
                        ActivityLog::logComponentActivity(
                           Authentication::getSite(),
                           Authentication::getAuthenticatedUser()->userId,
                           ($newAudit ? ActivityType::ADD_AUDIT : ActivityType::EDIT_AUDIT),
                           $audit->auditId,
                           $audit->auditName);
                        
                        // Notify participants.
                        if ($newAudit)
                        {
                           NotificationManager::onAuditGenerated($audit);
                        }
                        */
   
                        $this->result->auditId = $audit->auditId;
                        $this->result->audit = $audit;
                        $this->result->success = true;
                     }
                     else
                     {
                        $this->error("Database error");
                     }
                  }
                  else 
                  {
                     $this->error("Invalid audit id [$auditId]");
                  }
               }
            }
            break;
         }
          
         case "delete_audit":
         {
            if ($this->authenticate([Permission::EDIT_AUDIT]))
            {
               if (Page::requireParams($params, ["auditId"]))
               {
                  $auditId = $params->getInt("auditId");
                   
                  $audit = Audit::load($auditId);
                   
                  if ($audit)
                  {
                     Audit::delete($auditId);
                     
                     $this->result->auditId = $auditId;
                     $this->result->success = true;
                      
                     /*
                     ActivityLog::logComponentActivity(
                        Authentication::getAuthenticatedUser()->userId,
                        ActivityType::DELETE_AUDIT,
                        $auditId,
                        $audit->auditName);
                     */
                  }
                  else
                  {
                     $this->error("Invalid audit id [$auditId]");
                  }
               }
            }
            break;
         }
         
         case "perform_audit":
         {
            if ($this->authenticate([Permission::PERFORM_AUDIT]))
            {
               if (Page::requireParams($params, ["auditId"]))
               {
                  $auditId = $params->getInt("auditId");
                  
                  $audit = Audit::load($auditId);
                  
                  if ($audit)
                  {
                     $this->getAuditLineParams($audit, $params);
                     
                     $audit->status = AuditStatus::IN_PROGRESS;

                     $completeAudit = $params->getBool("complete");
                     if ($completeAudit)
                     {
                        $this->completeAudit($audit);   
                     }                        
                     
                     if (Audit::save($audit))
                     {
                        $this->result->auditId = $audit->auditId;
                        $this->result->audit = $audit;
                        $this->result->success = true;
                        
                        /*
                        ActivityLog::logComponentActivity(
                           Authentication::getAuthenticatedUser()->userId,
                           ($completeAudit ? ActivityType::COMPLETE_AUDIT : ActivityType::PERFORM_AUDIT),
                           $audit->auditId,
                           $audit->auditName);
                        */
                     }
                     else
                     {
                        $this->error("Database error");
                     }
                  }
                  else
                  {
                     $this->error("Invalid audit id [$auditId]");
                  }
               }
            }
            break;
         }

         case "update_status":
         {  
            if (Page::requireParams($params, ["auditId", "status"]))
            {
               $auditId = $params->getInt("auditId");
               $auditStatus = $params->getInt("status");
                
               $audit = Audit::load($auditId);
             
               if ($audit)
               {
                  if (array_search($auditStatus, AuditStatus::$values) == false)
                  {
                     $this->error("Invalid status [$auditStatus]");
                  }
                  else
                  {
                     $audit->status = $auditStatus;
                     
                     if (Audit::save($audit))
                     {
                        $this->result->auditId = $audit->auditId;
                        $this->result->status = $auditStatus;
                        $this->result->success = true;
                        
                        /*
                        ActivityLog::logComponentActivity(
                           Authentication::getAuthenticatedUser()->userId,
                           ActivityType::COMPLETE_AUDIT,
                           $audit->auditId,
                           $audit->auditName);
                        */
                     }
                     else
                     {
                        $this->error("Database error");
                     }
                  }
               }
               else
               {
                  $this->error("Invalid audit id [$auditId]");
               }
            }               
            break;
         }
         
         /*
         case "apply_audit":
         {
            if ($this->authenticate([Permission::APPLY_AUDIT]))
            {
               if (Page::requireParams($params, ["auditId"]))
               {
                  $auditId = $params->getInt("auditId");
                  
                  $audit = Audit::load($auditId);
                  
                  if ($audit)
                  {
                     if ($audit->status == AuditStatus::COMPLETE)
                     {
                        if (InventoryManager::applyAudit(
                               Authentication::getSite(), 
                               Authentication::getAuthenticatedUser()->userId, 
                               $auditId))
                        {
                           $audit->status = AuditStatus::APPLIED;
                           Audit::save($audit);

                           $this->result->auditId = $audit->auditId;
                           $this->result->success = true;
                           
                           ActivityLog::logApplyAudit(
                              Authentication::getSite(),
                              Authentication::getAuthenticatedUser()->userId,
                              $audit->auditId,
                              $audit->auditName,
                              count($audit->lineItems));
                        }
                        else
                        {
                           $this->error("Inventory error");
                        }
                     }
                     else 
                     {
                        $this->error("Improper audit status");
                     }
                  }
                  else
                  {
                     $this->error("Invalid audit id [$auditId]");
                  }
               }
            }
            break;
         }
         */
         
         case "fetch_audit_line":
         {
            if (Page::requireParams($params, ["auditId", "shipmentId"]))
            {
               $auditId = $params->getInt("auditId");
               $shipmentId = $params->getInt("shipmentId");
               
               $audit = Audit::load($auditId);
               $shipment = Shipment::load($shipmentId);
               
               if ($audit && $shipment)
               {
                  $auditLine = new AuditLine();
                  $auditLine->auditId = $audit->auditId;
                  $auditLine->shipmentId = $shipmentId;
                  $auditLine->recordedCount = $shipment->quantity;
                  $auditLine->shipment = $shipment;
                  
                  AuditPage::augmentAuditLine($auditLine);
                  
                  $this->result->auditLine = $auditLine;
                  $this->result->success = true;
               }
               else
               {
                  $this->error("Invalid shipment id [$shipmentId]");
               }
            }
            break;
         }
          
         case "fetch_audit_lines":
         {
            if (Page::requireParams($params, ["auditId"]))
            {
               $auditId = $params->getInt("auditId");
                
               $audit = Audit::load($auditId);
                
               if ($audit)
               {
                  $this->result->auditLines = $audit->lineItems;
                  $this->result->success = true;
                   
                  // Augment data.
                  foreach ($this->result->auditLines as $auditLine)
                  {
                     $auditLine->shipment = Shipment::load($auditLine->shipmentId);
                      
                     AuditPage::augmentAuditLine($auditLine);
                  }
               }
               else
               {
                  $this->error("Invalid audit id [$auditId]");
               }
            }
            break;
         }
         
         /*
         case "fetch_inventory_items":
         {
            $vendorId = isset($params["vendorId"]) ?
                           $params->getInt("vendorId") :
                           Vendor::UNKNOWN_VENDOR_ID;
                           
            // Fetch items for vendor.
            if ($vendorId != Vendor::UNKNOWN_VENDOR_ID)
            {
               $vendorId = $params->getInt("vendorId");
               $this->result->inventoryItems = InventoryManager::getInventoryItemsForVendor($vendorId, Authentication::getSite(), InventoryItemFilter::DEFAULT_FILTER);
               $this->result->success = true;
            }
            // Fetch items for all vendors (for this site).
            else
            {
               $this->result->inventoryItems = InventoryManager::getInventoryItems(Authentication::getSite(), InventoryItemFilter::DEFAULT_FILTER);
               $this->result->success = true;
            }
            break;
         }
         */
          
         case "fetch":
         default:
         {
            if ($this->authenticate([Permission::VIEW_AUDIT]))
            {
               // Fetch single component.
               if (isset($params["auditId"]))
               {
                  $auditId = $params->getInt("auditId");
                   
                  $audit = Audit::load($auditId);
                   
                  if ($audit)
                  {
                     AuditPage::augmentAudit($audit);
                     
                     $this->result->audit = $audit;
                     $this->result->success = true;
                  }
                  else
                  {
                     $this->error("Invalid audit id [$auditId]");
                  }
               }
               // Fetch all components.
               else 
               {
                  $dateTime = Time::dateTimeObject(null);
                   
                  $endDate = Time::endOfDay($dateTime->format(Time::STANDARD_FORMAT));
                  $startDate = Time::startofDay($dateTime->modify("-1 month")->format(Time::STANDARD_FORMAT));
                  $activeAudits = false;
                   
                  if (isset($params["startDate"]))
                  {
                     $startDate = Time::startOfDay($params["startDate"]);
                  }
                   
                  if (isset($params["endDate"]))
                  {
                     $endDate = Time::endOfDay($params["endDate"]);
                  }
                   
                  if (isset($params["activeAudits"]))
                  {
                     $activeAudits = $params->getBool("activeAudits");
                  }
                   
                  $this->result->success = true;
   
                  if ($activeAudits)
                  {
                     $this->result->audits = AuditManager::getAuditsByStatus(AuditStatus::$activeStatuses);
                  }
                  else
                  {
                     $this->result->audits = AuditManager::getAudits($startDate, $endDate);
                  }
                   
                  // Augment data.
                  foreach ($this->result->audits as $audit)
                  {
                     AuditPage::augmentAudit($audit);
                  }
               }
            }
            break;
         }
      }
       
      echo json_encode($this->result);
   }
    
   private function getAuditParams(&$audit, $params)
   {
       $audit->auditName = $params->get("auditName");
       //$audit->scheduled = $params->get("scheduled");  TODO
       $audit->notes = $params->get("notes");

       if ($params->keyExists("assigned"))
       {
          $audit->assigned = $params->getInt("assigned");
       }
       
       if ($params->keyExists("location"))
       {
          $audit->location = $params->getInt("location");
       }
       
       if ($params->keyExists("isAdHoc"))
       {
          $audit->isAdHoc = $params->keyExists("isAdHoc");
       }
       
       if ($params->keyExists("partNumber"))
       {
          $audit->partNumber = $params->get("partNumber");
       }
   }
   
   private static function getAuditLineParams(&$audit, $params)
   {
      $tableData = $params->get("data");
            
      $audit->lineItems = [];
      
      foreach ($tableData as $tableRow)
      {
         $auditLine = new AuditLine();
         
         $auditLine->auditLineId = intval($tableRow->auditLineId);
         $auditLine->auditId = $audit->auditId;
         $auditLine->shipmentId = intval($tableRow->shipmentId);
         $auditLine->confirmed = filter_var($tableRow->confirmed, FILTER_VALIDATE_BOOLEAN);
         //$auditLine->recordedCount = intval($tableRow->recordedCount);
         $auditLine->adjustedCount = intval($tableRow->adjustedCount);
         $auditLine->adjustedLocation = intval($tableRow->adjustedLocation);
         
         $audit->lineItems[] = $auditLine;
      }
   }
   
   private static function augmentAudit(&$audit)
   {
      // Formated date/time
      $dateTime = Time::dateTimeObject($audit->created);
      $audit->formattedCreated = $dateTime->format("n/j/Y");
      $dateTime = Time::dateTimeObject($audit->scheduled);
      $audit->formattedScheduled = $dateTime->format("n/j/Y");
      
      $audit->authorFullName = ($user = UserInfo::load($audit->author)) ? $user->getFullName() : "";
      $audit->assignedFullName = ($user = UserInfo::load($audit->assigned)) ? $user->getFullName() : "";
      $audit->progress = $audit->getProgress();
      $audit->accuracy = AuditManager::getAuditAccuracy($audit->auditId);
      $audit->statusLabel = AuditStatus::getLabel($audit->status);
      $audit->locationLabel =ShipmentLocation::getLabel($audit->location);
   }
    
   private static function augmentAuditLine(&$auditLine)
   {
      $shipment = $auditLine->shipment;
      
      $shipment->shipmentTicketCode = ShipmentManager::getShipmentTicketCode($shipment->shipmentId);
      $shipment->formattedDateTime = ($shipment->dateTime) ? Time::dateTimeObject($shipment->dateTime)->format("n/j/Y") : null;
      $shipment->locationLabel = ShipmentLocation::getLabel($shipment->location);
      
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
      
      $auditLine->isExpected = false;
      $audit = Audit::load($auditLine->auditId);
      if ($audit)
      {
         $auditLine->isExpected = ($shipment->location == $audit->location);  // TODO: Part number validation
      }
      /*
      // vendorName
      $auditLine->inventoryItem->vendorName = Vendor::getVendorName($auditLine->inventoryItem->vendorId);
      
      // categoryLabel
      $auditLine->inventoryItem->categoryLabel = InventoryCategory::getCategoryName($auditLine->inventoryItem->categoryId);
       
      // unitsLabel
      $auditLine->inventoryItem->unitsLabel = Units::getLabel($auditLine->inventoryItem->units);
       
      // count
      $auditLine->count = InventoryManager::getInventoryCount($siteId, $auditLine->itemId);
      */
   }
   
   private static function completeAudit($audit)
   {
      if ($audit)
      {
         $audit->status = AuditStatus::COMPLETE;
       
         /*
         // Record the current inventory counts for each item, freezing it in time.
         foreach ($audit->lineItems as $auditLine)
         {
            $auditLine->recordedCount = InventoryManager::getInventoryCount($audit->siteId, $auditLine->itemId);
         }
         */
      }
   }
}
 
 ?>