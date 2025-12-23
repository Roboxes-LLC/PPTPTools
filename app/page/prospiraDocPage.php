<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/app/page/page.php';
require_once ROOT.'/common/prospiraLabel.php';
require_once ROOT.'/core/manager/prospiraDocManager.php';
require_once ROOT.'/core/manager/shipmentManager.php';

class ProspiraDocPage extends Page
{
   public function handleRequest($params)
   {
      switch ($this->getRequest($params))
      {
         case "save_doc":
         {
            if ($this->authenticate([Permission::EDIT_SHIPMENT]))
            {
               if (Page::requireParams($params, ["docId", "clockNumber", "lotNumber", "serialNumber"]))
               {
                  $docId = $params->getInt("docId");
                  $newDoc = ($docId == ProspiraDoc::UNKNOWN_DOC_ID);
                  
                  $doc = null;
                  if ($newDoc && Page::requireParams($params, ["shipmentId"]))  // New entries specify the jobNumber manually.
                  {
                     $prospiraDoc = new ProspiraDoc();
                     $prospiraDoc->shipmentId = $params->getInt("shipmentId");
                  }
                  else
                  {
                     $prospiraDoc = ProspiraDoc::load($docId);
                     
                     if (!$prospiraDoc)
                     {
                        $prospiraDoc = null;
                        $this->error("Invalid doc id [$docId]");
                     }
                  }
                  
                  if ($prospiraDoc)
                  {
                     ProspiraDocPage::getDocParams($prospiraDoc, $params);
                     
                     if (ProspiraDoc::save($prospiraDoc))
                     {
                        $this->result->docId = $prospiraDoc->docId;
                        $this->result->prospiraDoc = $prospiraDoc;
                        $this->result->success = true;
                        
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
         
         case "delete_doc":
         {
            if ($this->authenticate([Permission::EDIT_SHIPMENT]))
            {
               if (Page::requireParams($params, ["docId"]))
               {
                  $docId = $params->getInt("docId");
                  
                  if (ProspiraDoc::delete($docId))
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
               if (isset($params["docId"]))
               {
                  $docId = $params->getInt("docId");
                  
                  $prospiraDoc = ProspiraDoc::load($docId);
                  
                  if ($prospiraDoc)
                  {
                     // Augment shipment.
                     ProspiraDocPage::augmentDoc($prospiraDoc);
                     
                     $this->result->prospiraDoc = $prospiraDoc;
                     $this->result->success = true;
                  }
                  else
                  {
                     $this->error("Invalid doc id [$docId]");
                  }
               }
               // Fetch all components.
               else
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
                  
                  $this->result->success = true;
                  $this->result->prospiraDocs = ProspiraDocManager::getProspiraDocs($startDate, $endDate);
                  
                  // Augment shipment.
                  foreach ($this->result->prospiraDocs as $prospiraDoc)
                  {
                     ProspiraDocPage::augmentDoc($prospiraDoc);
                  }
               }
            }
            break;
         }
         
         case "fetch_label":
         {
            if ($this->authenticate([Permission::VIEW_SHIPMENT]))
            {
               if (Page::requireParams($params, ["docId"]))
               {
                  $label = new ProspiraLabel($params->getInt("docId"));
                  
                  if ($label)
                  {
                     $this->result->success = true;
                     $this->result->docId = $label->docId;
                     $this->result->labelXML = $label->labelXML;
                  }
                  else
                  {
                     $this->error("Failed to create Prospira label.");
                  }
               }
            }
            break;
         }

         case "print_label":
         {
            if ($this->authenticate([Permission::VIEW_SHIPMENT]))
            {
               if (Page::requireParams($params, ["docId", "printerName", "copies"]))
               {
                  $docId = $params->getInt("docId");
                  $printerName = $params->get("printerName");
                  $copies = $params->getInt("copies");

                  if (ProspiraDocManager::printProspiraLabel($docId, $printerName, $copies))
                  {
                     $this->result->success = true;
                  }
                  else 
                  {
                     $this->error("Print error");
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
   
   private function getDocParams(&$prospiraDoc, $params)
   {
      $prospiraDoc->clockNumber = $params->get("clockNumber");
      $prospiraDoc->lotNumber = $params->get("lotNumber");
      $prospiraDoc->serialNumber = $params->get("serialNumber");
   }
   
   private static function augmentDoc(&$prospiraDoc)
   {
      $prospiraDoc->shipment->shipmentTicketCode = ShipmentManager::getShipmentTicketCode($prospiraDoc->shipment->shipmentId);
      $prospiraDoc->shipment->locationLabel = ShipmentLocation::getLabel($prospiraDoc->shipment->location);
      $prospiraDoc->shipment->formattedDate = ($prospiraDoc->shipment->dateTime) ? Time::dateTimeObject($prospiraDoc->shipment->dateTime)->format("n/j/Y") : null;
      $prospiraDoc->shipment->formattedTime = ($prospiraDoc->shipment->dateTime) ? Time::dateTimeObject($prospiraDoc->shipment->dateTime)->format("h:i A") : null;
      
      // Part
      $pptpPartNumber = JobInfo::getJobPrefix($prospiraDoc->shipment->jobNumber);
      $part = Part::load($pptpPartNumber, false);  // Use PPTP number.
      if ($part)
      {
         $prospiraDoc->shipment->part = $part;
         
         $customer = Customer::load($part->customerId);
         if ($customer)
         {
            $prospiraDoc->shipment->part->customerName = $customer->customerName;
         }
      }
   }
}

?>