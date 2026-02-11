<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/app/page/page.php';
require_once ROOT.'/common/jobInfo.php';
require_once ROOT.'/core/manager/maintenanceTicketManager.php';

class MaintenanceTicketPage extends Page
{
   public function handleRequest($params)
   {
      if (Page::authenticate([Permission::VIEW_MAINTENANCE_TICKET]))
      {
         $request = $this->getRequest($params);
         
         switch ($request)
         {            
            case "save_ticket":
            {
               if (Page::authenticate([Permission::EDIT_MAINTENANCE_TICKET]))
               {
                  if (Page::requireParams($params, ["ticketId", "wcNumber", "jobNumber", "machineState", "description", "details", "assigned"]))
                  {
                     $ticketId = $params->getInt("ticketId");
                     $isNew = ($ticketId == MaintenanceTicket::UNKNOWN_TICKET_ID);
                     
                     $maintenanceTicket = null;
                     if ($isNew)
                     {
                        $maintenanceTicket = new MaintenanceTicket();
                        $maintenanceTicket->author = Authentication::getAuthenticatedUser()->employeeNumber;
                        $maintenanceTicket->posted = Time::now();
                     }
                     else
                     {
                        $maintenanceTicket = MaintenanceTicket::load($ticketId);
                        
                        if (!$maintenanceTicket)
                        {
                           $maintenanceTicket = null;
                           $this->error("Invalid ticket id [$ticketId]");
                        }
                     }
                     
                     if ($maintenanceTicket)
                     {
                        MaintenanceTicketPage::getTicketParams($maintenanceTicket, $params);
                        
                        if (MaintenanceTicket::save($maintenanceTicket))
                        {
                           $this->result->ticketId = $maintenanceTicket->ticketId;
                           $this->result->maintenanceTicket = $maintenanceTicket;
                           $this->result->success = true;
                           
                           /*
                           ActivityLog::logComponentActivity(
                           Authentication::getAuthenticatedUser()->employeeNumber,
                           ($newOrder ? ActivityType::ADD_JOB : ActivityType::EDIT_JOB),
                           $maintenanceTicket->jobId,
                           $salesOrder->jobNumber);
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
            
            case "delete_ticket":
            {  
               if (Page::authenticate([Permission::EDIT_MAINTENANCE_TICKET]))
               {
                  if (Page::requireParams($params, ["ticketId"]))
                  {
                     $ticketId = $params->getInt("ticketId");
                     
                     $maintenanceTicket = MaintenanceTicket::load($ticketId);
                     
                     if ($maintenanceTicket)
                     {
                        MaintenanceTicket::delete($ticketId);
                        
                        $this->result->ticketId = $ticketId;
                        $this->result->success = true;
                        
                        /*
                        ActivityLog::logComponentActivity(
                        Authentication::getAuthenticatedUser()->employeeNumber,
                        ActivityType::DELETE_JOB,
                        $maintenanceTicket->jobId,
                        $maintenanceTicket->jobNumber);
                        */
                     }
                     else
                     {
                        $this->error("Invalid ticket id [$maintenanceTicketId]");
                     }
                  }
               }
               break;
            }
            
            case "fetch":
            {
               // Fetch single component.
               if (isset($params["ticketId"]))
               {
                  $ticketId = $params->getInt("ticketId");
                  
                  $maintenanceTicket = MaintenanceTicket::load($ticketId);
                  
                  if ($maintenanceTicket)
                  {
                     $this->result->success = true;
                     $this->result->maintenanceTicket = $maintenanceTicket;
                  }
                  else
                  {
                     $this->error("Invalid ticket id [$ticketId]");
                  }
               }
               // Fetch all components.
               else
               {
                   $dateTime = Time::dateTimeObject(null);
                   
                   $endDate = Time::endOfDay($dateTime->format(Time::STANDARD_FORMAT));
                   $startDate = Time::startofDay($dateTime->modify("-1 month")->format(Time::STANDARD_FORMAT));
                   $activeTickets = false;
                   
                   if (isset($params["startDate"]))
                   {
                      $startDate = Time::startOfDay($params["startDate"]);
                   }
                   
                   if (isset($params["endDate"]))
                   {
                      $endDate = Time::endOfDay($params["endDate"]);
                   }
                   
                   if (isset($params["activeTickets"]))
                   {
                      $activeTickets = $params->getBool("activeTickets");
                   }                   
                   
                   $this->result->success = true;
                   $this->result->maintenanceTickets = MaintenanceTicketManager::getMaintenanceTickets($startDate, $endDate, $activeTickets);
                   
                   // Augment data.
                   foreach ($this->result->maintenanceTickets as $maintenanceTicket)
                   {
                      MaintenanceTicketPage::augmentTicket($maintenanceTicket);
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
   
   // **************************************************************************
   
   private function getTicketParams(&$maintenanceTicket, $params)
   {
      $maintenanceTicket->wcNumber = $params->getInt("wcNumber");
      $maintenanceTicket->jobNumber = $params->get("jobNumber");
      $maintenanceTicket->machineState = $params->getInt("machineState");
      $maintenanceTicket->description = $params->get("description");
      $maintenanceTicket->details = $params->get("details");
      $maintenanceTicket->assigned = $params->getInt("assigned");
   }

   private static function augmentTicket(&$maintenanceTicket)
   {
      $maintenanceTicket->formattedDate = ($maintenanceTicket->posted) ? Time::dateTimeObject($maintenanceTicket->posted)->format("n/j/Y") : null;
      $maintenanceTicket->formattedTime = ($maintenanceTicket->posted) ? Time::dateTimeObject($maintenanceTicket->posted)->format("h:i A") : null;

      $userInfo = UserInfo::load($maintenanceTicket->author);
      $maintenanceTicket->authorName = $userInfo ? $userInfo->getFullName() : "";

      $maintenanceTicket->statusLabel = MaintenanceTicketStatus::getLabel($maintenanceTicket->status);

      $maintenanceTicket->wcLabel = JobInfo::getWcLabel($maintenanceTicket->wcNumber);

      $maintenanceTicket->machineStateLabel = MachineState::getLabel($maintenanceTicket->machineState);
      $maintenanceTicket->machineStateClass = MachineState::getClass($maintenanceTicket->machineState);

      $userInfo = UserInfo::load($maintenanceTicket->assigned);
      $maintenanceTicket->assignedName = $userInfo ? $userInfo->getFullName() : "";

      /*
      // quoteNumber
      $quote->quoteNumber = $quote->getQuoteNumber();
      
      // customerName
      $customer = Customer::load($quote->customerId);
      if ($customer)
      {
         $quote->customerName = $customer->customerName;
      }
      
      // contactName
      $contact = Contact::load($quote->contactId);
      if ($contact)
      {
         $quote->contactName = $contact->getFullName();
      }       
      
      // estimateCount
      $quote->estimateCount = 0;
      for ($estimateIndex = 0; $estimateIndex < Quote::MAX_ESTIMATES; $estimateIndex++)
      {
         if ($quote->hasEstimate($estimateIndex))
         {
            $quote->estimateCount++;
         }
      }       
      
      // quoteStatusLabel
      $quote->quoteStatusLabel = QuoteStatus::getLabel($quote->quoteStatus);
      */
   }

}