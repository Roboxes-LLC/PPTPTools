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
                  if (Page::requireParams($params, ["ticketId", "occured", "wcNumber", "jobNumber", "machineState", "description", "details", "assigned"]))
                  {
                     $ticketId = $params->getInt("ticketId");
                     $isNew = ($ticketId == MaintenanceTicket::UNKNOWN_TICKET_ID);
                     
                     $maintenanceTicket = null;
                     if ($isNew)
                     {
                        $maintenanceTicket = new MaintenanceTicket();
                        $maintenanceTicket->author = Authentication::getAuthenticatedUser()->employeeNumber;
                        $maintenanceTicket->posted = Time::now();
                        $maintenanceTicket->status = MaintenanceTicketStatus::REPORTED;
                        $maintenanceTicket->priority = MaintenanceTicketManager::getNextPriority();
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

                           if ($isNew)
                           {
                              $maintenanceTicket->report($maintenanceTicket->author, null);
                           }
                           
                           ActivityLog::logComponentActivity(
                              Authentication::getAuthenticatedUser()->employeeNumber,
                              ($isNew ? ActivityType::ADD_MAINTENANCE_TICKET : ActivityType::EDIT_MAINTENANCE_TICKET),
                              $maintenanceTicket->ticketId);
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
                        
                        ActivityLog::logComponentActivity(
                           Authentication::getAuthenticatedUser()->employeeNumber,
                           ActivityType::DELETE_MAINTENANCE_TICKET,
                           $ticketId);
                     }
                     else
                     {
                        $this->error("Invalid ticket id [$ticketId]");
                     }
                  }
               }
               break;
            }

            case "set_priority":
            {
               if (Page::authenticate([Permission::EDIT_MAINTENANCE_TICKET]))
               {
                  if (Page::requireParams($params, ["ticketId", "priority"]))
                  {
                     $ticketId = $params->getInt("ticketId");
                     $priority = $params->getInt("priority");
                     
                     $maintenanceTicket = MaintenanceTicket::load($ticketId);
                     
                     if ($maintenanceTicket)
                     {
                        $maintenanceTicket->priority = $priority;
                        $this->result->success = MaintenanceTicket::save($maintenanceTicket);
                        $this->result->ticketId = $ticketId;                        
                     }
                     else
                     {
                        $this->error("Invalid ticket id [$ticketId]");
                     }
                  }
               }
               break;
            }

            case "assign":
            {
               if (Page::authenticate([Permission::EDIT_MAINTENANCE_TICKET]))
               {
                  //if (Page::requireParams($params, ["ticketId", "assigned", "notes"]))
                  {
                     $ticketId = $params->getInt("ticketId");
                     $assigned = 1975;//$params->getInt("assigned");
                     $notes = $params->getInt("notes");
                     
                     $maintenanceTicket = MaintenanceTicket::load($ticketId);
                     
                     if ($maintenanceTicket)
                     {
                        $maintenanceTicket->assign(Authentication::getAuthenticatedUser()->employeeNumber, $assigned, $notes);

                        $this->result->success = true;
                        $this->result->ticketId = $ticketId;  

                        ActivityLog::logComponentActivity(
                           Authentication::getAuthenticatedUser()->employeeNumber,
                           ActivityType::ASSIGN_MAINTENANCE_TICKET,
                           $ticketId);
                     }
                     else
                     {
                        $this->error("Invalid ticket id [$ticketId]");
                     }
                  }
               }
               break;
            }

            case "acknowledge":
            {
               if (Page::authenticate([Permission::EDIT_MAINTENANCE_TICKET]))
               {
                  if (Page::requireParams($params, ["ticketId", "notes"]))
                  {
                     $ticketId = $params->getInt("ticketId");
                     $notes = $params->getInt("notes");
                     
                     $maintenanceTicket = MaintenanceTicket::load($ticketId);
                     
                     if ($maintenanceTicket)
                     {
                        $maintenanceTicket->acknowledge(Authentication::getAuthenticatedUser()->employeeNumber, $notes);

                        $this->result->success = true;
                        $this->result->ticketId = $ticketId;  

                        ActivityLog::logComponentActivity(
                           Authentication::getAuthenticatedUser()->employeeNumber,
                           ActivityType::ACKNOWLEDGE_MAINTENANCE_TICKET,
                           $ticketId);
                     }
                     else
                     {
                        $this->error("Invalid ticket id [$ticketId]");
                     }
                  }
               }
               break;
            }

            case "begin_repair":
            {
               if (Page::authenticate([Permission::EDIT_MAINTENANCE_TICKET]))
               {
                  if (Page::requireParams($params, ["ticketId", "notes"]))
                  {
                     $ticketId = $params->getInt("ticketId");
                     $notes = $params->getInt("notes");
                     
                     $maintenanceTicket = MaintenanceTicket::load($ticketId);
                     
                     if ($maintenanceTicket)
                     {
                        $maintenanceTicket->beginRepair(Authentication::getAuthenticatedUser()->employeeNumber, $notes);

                        $this->result->success = true;
                        $this->result->ticketId = $ticketId;

                        ActivityLog::logComponentActivity(
                           Authentication::getAuthenticatedUser()->employeeNumber,
                           ActivityType::BEGIN_REPAIR,
                           $ticketId);
                     }
                     else
                     {
                        $this->error("Invalid ticket id [$ticketId]");
                     }
                  }
               }
               break;
            }

            case "complete_repair":
            {
               if (Page::authenticate([Permission::EDIT_MAINTENANCE_TICKET]))
               {
                  if (Page::requireParams($params, ["ticketId", "notes"]))
                  {
                     $ticketId = $params->getInt("ticketId");
                     $notes = $params->getInt("notes");
                     
                     $maintenanceTicket = MaintenanceTicket::load($ticketId);
                     
                     if ($maintenanceTicket)
                     {
                        $maintenanceTicket->completeRepair(Authentication::getAuthenticatedUser()->employeeNumber, $notes);

                        $this->result->success = true;
                        $this->result->ticketId = $ticketId;  

                         ActivityLog::logComponentActivity(
                           Authentication::getAuthenticatedUser()->employeeNumber,
                           ActivityType::COMPLETE_REPAIR,
                           $ticketId);
                     }
                     else
                     {
                        $this->error("Invalid ticket id [$ticketId]");
                     }
                  }
               }
               break;
            }

            case "confirm":
            {
               if (Page::authenticate([Permission::EDIT_MAINTENANCE_TICKET]))
               {
                  if (Page::requireParams($params, ["ticketId", "notes"]))
                  {
                     $ticketId = $params->getInt("ticketId");
                     $notes = $params->getInt("notes");
                     
                     $maintenanceTicket = MaintenanceTicket::load($ticketId);
                     
                     if ($maintenanceTicket)
                     {
                        $maintenanceTicket->confirm(Authentication::getAuthenticatedUser()->employeeNumber, $notes);

                        $this->result->success = true;
                        $this->result->ticketId = $ticketId;

                        ActivityLog::logComponentActivity(
                           Authentication::getAuthenticatedUser()->employeeNumber,
                           ActivityType::CONFIRM_REPAIR,
                           $ticketId);
                     }
                     else
                     {
                        $this->error("Invalid ticket id [$ticketId]");
                     }
                  }
               }
               break;
            }

            case "close":
            {
               if (Page::authenticate([Permission::EDIT_MAINTENANCE_TICKET]))
               {
                  if (Page::requireParams($params, ["ticketId", "notes"]))
                  {
                     $ticketId = $params->getInt("ticketId");
                     $notes = $params->getInt("notes");
                     
                     $maintenanceTicket = MaintenanceTicket::load($ticketId);
                     
                     if ($maintenanceTicket)
                     {
                        $maintenanceTicket->close(Authentication::getAuthenticatedUser()->employeeNumber, $notes);

                        $this->result->success = true;
                        $this->result->ticketId = $ticketId;

                        ActivityLog::logComponentActivity(
                           Authentication::getAuthenticatedUser()->employeeNumber,
                           ActivityType::CLOSE_MAINTENANCE_TICKET,
                           $ticketId);
                     }
                     else
                     {
                        $this->error("Invalid ticket id [$ticketId]");
                     }
                  }
               }
               break;
            }

            case "add_comment":
            {
               if (Page::requireParams($params, ["ticketId", "comments"]))
               {
                  $ticketId = $params->getInt("ticketId");
                  $comments = $params->get("comments");
                  
                  $maintenanceTicket = MaintenanceTicket::load($ticketId);
                  
                  if ($maintenanceTicket)
                  {
                     ActivityLog::logComponentActivity(
                        Authentication::getAuthenticatedUser()->employeeNumber,
                        ActivityType::ANNOTATE_MAINTENANCE_TICKET,
                        $maintenanceTicket->ticketId,
                        $comments);
                     
                     $this->result->ticketId = $maintenanceTicket->ticketId;
                     $this->result->success = true;
                  }
                  else
                  {
                     $this->error("Invalid ticket id [$ticketId]");
                  }
               }
               break;
            }

            case "attach_file":
            {
               if (Page::requireParams($params, ["ticketId", "filename", "description"]))
               {
                  if (isset($_FILES["attachment"]) &&
                           ($_FILES["attachment"]["name"] != ""))
                  {
                     $ticketId = $params->getInt("ticketId");
                     $file = $_FILES["attachment"];
                     $filename = $params->get("filename");
                     $description = $params->get("description");
                     
                     // Use the actual filename if an alternate wasn't provided.
                     if (empty($filename))
                     {
                        $filename = $_FILES["attachment"]["name"];
                     }
                     
                     // Constrain the filename to an appropriate size.
                     $filename = Upload::shortenFilename($filename, Attachment::MAX_FILENAME_SIZE);
                     
                     $storedFilename = null;
                     $uploadStatus = Upload::uploadAttachment($file, $storedFilename);
                     
                     switch ($uploadStatus)
                     {
                        case UploadStatus::UPLOADED:
                        {
                           $attachment = new Attachment();
                           $attachment->componentId = $ticketId;
                           $attachment->componentType = ComponentType::MAINTENANCE_TICKET;
                           $attachment->filename = $filename;
                           $attachment->storedFilename = $storedFilename;
                           $attachment->description = $description;
                           
                           if (Attachment::save($attachment))
                           {
                              $this->result->success = true;
                              $this->result->ticketId = $ticketId;
                              $this->result->attachment = $attachment;
                              
                              ActivityLog::logAddCorrectiveActionAttachment(
                                 Authentication::getAuthenticatedUser()->employeeNumber,
                                 $attachment->componentId,
                                 $attachment->attachmentId,
                                 $attachment->filename);
                           }
                           else
                           {
                              $this->error("Database error");
                           }
                           break;
                        }
                           
                        default:
                        {
                           $this->error("Upload error [" . UploadStatus::toString($uploadStatus) . "]");
                        }
                     }
                  }
                  else
                  {
                     $this->error("Failed to upload file");
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
                  $dateType = FilterDateType::POSTED_DATE;
                  $activeTickets = false;
                  $prioritySort = false;

                  if (isset($params["startDate"]))
                  {
                     $startDate = Time::startOfDay($params["startDate"]);
                  }

                  if (isset($params["endDate"]))
                  {
                     $endDate = Time::endOfDay($params["endDate"]);
                  }

                  if (isset($params["dateType"]))
                  {
                     $dateType = $params->getInt("dateType");
                  }

                  if (isset($params["activeTickets"]))
                  {
                     $activeTickets = $params->getBool("activeTickets");
                  }      

                  if (isset($params["prioritySort"]))
                  {
                     $prioritySort = $params->getBool("prioritySort");
                  } 

                  $this->result->success = true;
                  $this->result->maintenanceTickets = MaintenanceTicketManager::getMaintenanceTickets($dateType, $startDate, $endDate, $activeTickets);

                  if ($prioritySort)
                  {
                  usort($this->result->maintenanceTickets, [MaintenanceTicketManager::class, "priorityComparator"]);
                  }

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
      $maintenanceTicket->occured = $params->get("occured");
      $maintenanceTicket->wcNumber = $params->getInt("wcNumber");
      $maintenanceTicket->jobNumber = $params->get("jobNumber");
      $maintenanceTicket->machineState = $params->getInt("machineState");
      $maintenanceTicket->description = $params->get("description");
      $maintenanceTicket->details = $params->get("details");
      $maintenanceTicket->assigned = $params->getInt("assigned");
   }

   private static function augmentTicket(&$maintenanceTicket)
   {
      $maintenanceTicket->ticketNumber = $maintenanceTicket->getMaintenanceTicketNumber();
      
      $maintenanceTicket->formattedDate = ($maintenanceTicket->posted) ? Time::dateTimeObject($maintenanceTicket->posted)->format("n/j/Y") : null;
      $maintenanceTicket->formattedTime = ($maintenanceTicket->posted) ? Time::dateTimeObject($maintenanceTicket->posted)->format("h:i A") : null;

      $maintenanceTicket->formattedOccured = ($maintenanceTicket->occured) ? Time::dateTimeObject($maintenanceTicket->occured)->format("n/j/Y") : null;

      $userInfo = UserInfo::load($maintenanceTicket->author);
      $maintenanceTicket->authorName = $userInfo ? $userInfo->getFullName() : "";

      $maintenanceTicket->statusLabel = MaintenanceTicketStatus::getLabel($maintenanceTicket->status);

      $maintenanceTicket->wcLabel = JobInfo::getWcLabel($maintenanceTicket->wcNumber);

      $maintenanceTicket->machineStateLabel = MachineState::getLabel($maintenanceTicket->machineState);
      $maintenanceTicket->machineStateClass = MachineState::getClass($maintenanceTicket->machineState);

      $userInfo = UserInfo::load($maintenanceTicket->assigned);
      $maintenanceTicket->assignedName = $userInfo ? $userInfo->getFullName() : "";

      $updateTime = $maintenanceTicket->getUpdateTime();
      $maintenanceTicket->updateTime = $updateTime ? Time::toJavascriptDateTime($updateTime, true) : null;

      $resolveTime = $maintenanceTicket->getResolveTime();
      $maintenanceTicket->formattedResolveTime = MaintenanceTicketPage::formatResolveTime($resolveTime);

      $maintenanceTicket->nextAction = MaintenanceTicketAction::getNextAction($maintenanceTicket->status);
      $maintenanceTicket->nextActionLabel = MaintenanceTicketAction::getLabel($maintenanceTicket->nextAction);
      $maintenanceTicket->nextActionApiCommand = MaintenanceTicketAction::getApiCommand($maintenanceTicket->nextAction);
   }

   private static function formatResolveTime($resolveTime)
   {
      $resolveTimeString = null;

      $secondsInADay = (60 * 60 * 24);
      $secondsInAnHour = (60 * 60);
      $secondsInAMinute = 60;

      if ($resolveTime > 0)
      {
         $days = floor($resolveTime / $secondsInADay);

         $hourSeconds = $resolveTime % $secondsInADay;
         $hours = floor($hourSeconds / $secondsInAnHour);

         $minuteSeconds = $hourSeconds % $secondsInAnHour;
         $minutes = floor($minuteSeconds / $secondsInAMinute);

         if ($days > 0)
         {
            $resolveTimeString = $days . " days, " . $hours . " hours";
         }
         else if ($hours > 0)
         {
            $resolveTimeString = $hours . " hours, " . $minutes . " minutes";
         }
         else
         {
            $resolveTimeString = $minutes . " minutes";
         }
      }

      return ($resolveTimeString);
   }

}