<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/app/page/page.php';
require_once ROOT.'/core/component/customer.php';
require_once ROOT.'/core/component/part.php';
require_once ROOT.'/core/manager/partManager.php';

class PartPage extends Page
{
   public function handleRequest($params)
   {
      if (Page::authenticate([Permission::VIEW_JOB]))
      {
         $request = $this->getRequest($params);
         
         switch ($request)
         {
            case "save_part":
            {
               if (Page::authenticate([Permission::EDIT_JOB]))
               {
                  if (Page::requireParams($params, ["isNew", "pptpNumber", "customerNumber", "customerId", "sampleWeight", "firstPartTemplateId", "inProcessTemplateId", "lineTemplateId", "qcpTemplateId", "qcpTemplateId"]))
                  {
                     $pptpNumber = $params->get("pptpNumber");
                     $customerNumber = $params->get("customerNumber");
                     $part = null;
                     
                     if ($params->getBool("isNew"))
                     {
                        if (Part::load($pptpNumber, Part::USE_PPTP_NUMBER))
                        {
                           $this->error("Duplicate PPTP part number");
                        }
                        else if (Part::load($customerNumber, Part::USE_CUSTOMER_NUMBER))
                        {
                           $this->error("Duplicate customer part number");
                        }
                        else
                        {
                           $part = new Part();
                           $part->pptpNumber = $pptpNumber;
                        }
                     }
                     else
                     {
                        $part = Part::load($pptpNumber, Part::USE_PPTP_NUMBER);
                        
                        if (!$part)
                        {
                           $this->error("Invalid PPTP part number [$pptpNumber]");
                        }
                     }
                     
                     if ($part)
                     {
                        $part->customerNumber = $customerNumber;
                        $part->customerId = $params->getInt("customerId");
                        $part->sampleWeight = $params->getFloat("sampleWeight");
                        $part->inspectionTemplateIds[InspectionType::FIRST_PART] = $params->getInt("firstPartTemplateId");
                        $part->inspectionTemplateIds[InspectionType::IN_PROCESS] = $params->getInt("inProcessTemplateId");
                        $part->inspectionTemplateIds[InspectionType::LINE] = $params->getInt("lineTemplateId");
                        $part->inspectionTemplateIds[InspectionType::QCP] = $params->getInt("qcpTemplateId");
                        $part->inspectionTemplateIds[InspectionType::FINAL] = $params->getInt("finalTemplateId");
                        
                        if (Part::save($part))
                        {
                           $this->result->success = true;
                           $this->result->partNumber = $pptpNumber;
                           $this->result->part = $part;
                        
                           //
                           // Process uploaded customer print.
                           //
                           
                           if (isset($_FILES["customerPrint"]) && ($_FILES["customerPrint"]["name"] != ""))
                           {
                              $uploadStatus = Upload::uploadCustomerPrint($_FILES["customerPrint"]);
                              
                              if ($uploadStatus == UploadStatus::UPLOADED)
                              {
                                 $filename = basename($_FILES["customerPrint"]["name"]);
                                 
                                 $part->customerPrint = $filename;
                                 
                                 if (!Part::save($part))
                                 {
                                    $this->error("Database error");
                                 }
                              }
                              else
                              {
                                 $this->error("File upload failed! " . UploadStatus::toString($uploadStatus));
                              }
                           }
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
            
            case "delete_part":
            {
               if (Page::authenticate([Permission::EDIT_JOB]))
               {
                  if (Page::requireParams($params, ["pptpNumber"]))
                  {
                     $pptpNumber = $params->get("pptpNumber");
                     
                     $part = Part::load($pptpNumber, Part::USE_PPTP_NUMBER);
                     
                     if ($part)
                     {
                        Part::delete($pptpNumber);
                        
                        $this->result->success = true;
                        $this->result->partNumber = $pptpNumber;
                     }
                     else
                     {
                        $this->error("Invalid PPTP part number [$pptpNumber]");
                     }
                  }
               }
               break;
            }
                     
                  
            case "fetch":
            {
               // Fetch single component.
               if (isset($params["partNumber"]))
               {
                  $partNumber = $params->get("partNumber");
                  
                  $part = Part::load($partNumber, Part::USE_PPTP_NUMBER);
                  
                  if ($part)
                  {
                     $this->result->part = $part;
                     $this->result->success = true;
                  }
                  else
                  {
                     $this->error("Invalid part number [$partNumber]");
                  }
               }
               // Fetch all components.
               else
               {
                  $this->result->success = true;
                  $this->result->parts = PartManager::getParts();
                  
                  // Augment data.
                  foreach ($this->result->parts as $part)
                  {
                     PartPage::augmentPart($part);
                  }
               }
               break;
            }
            
            default:
            {
               $this->error("Unsupported command [$request]");
            }
         }
      }
      
      echo json_encode($this->result);
   }
   
   private function augmentPart(&$part)
   {
      $customer = Customer::load($part->customerId);
      $part->customerName = ($customer ? $customer->customerName : "");
   }
}