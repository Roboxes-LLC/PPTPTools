<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/app/page/page.php';
require_once ROOT.'/core/manager/scheduleManager.php';

class SchedulePage extends Page
{
   public function handleRequest($params)
   {
      switch ($this->getRequest($params))
      {
         case "save_entry":
         {
            if ($this->authenticate([Permission::EDIT_SCHEDULE]))
            {
               if (Page::requireParams($params, ["jobId", "startDate"]))
               {
                  $jobId = $params->getInt("jobId");
                  $startDate = $params->get("startDate");
                  $endDate = $params->keyExists("endDate") ? $params->get("endDate") : null;

                  
                  $scheduleEntry = new ScheduleEntry();
                  $scheduleEntry->jobId = $jobId;
                  $scheduleEntry->startDate = $startDate;
                  $scheduleEntry->endDate = $endDate;
                  $scheduleEntry->employeeNumber = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
                  
                  if (ScheduleEntry::save($scheduleEntry))
                  {
                     $this->result->success = true;
                     $this->result->scheduleEntry = ScheduleEntry::load($scheduleEntry->entryId);
                  }
                  else
                  {
                     $this->error("Database error");
                  }
               }
            }
            break;
         }
         
         case "delete_entry":
         {
            if ($this->authenticate([Permission::EDIT_SCHEDULE]))
            {
               if (Page::requireParams($params, ["entryId"]))
               {
                  $entryId = $params->getInt("entryId");
                  
                  if (ScheduleEntry::delete($entryId))
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
         
         case "assign_operator":
         {
            if ($this->authenticate([Permission::VIEW_SCHEDULE]) &&
                Page::requireParams($params, ["entryId", "employeeNumber"]))
            {
               $entryId = intval($params["entryId"]);
               $employeeNumber = intval($params["employeeNumber"]);
               
               $scheduleEntry = ScheduleEntry::load($entryId);
               
               if ($scheduleEntry)
               {
                  $scheduleEntry->employeeNumber = $employeeNumber;
                  
                  if (ScheduleEntry::save($scheduleEntry))
                  {
                     $this->result->scheduleEntry = $scheduleEntry;
                     $this->result->success = true;
                  }
                  else
                  {
                     $this->error("Database error");
                  }
               }
               else
               {
                  $this->error("Invalid schedule entry [$entryId]");
               }
            }          
            break;
         }
         
         case "fetchUnassigned":
         {
            if ($this->authenticate([Permission::VIEW_SCHEDULE]) &&
                Page::requireParams($params, ["mfgDate"]))
            {
               $this->result->success = true;
               $this->result->unscheduled = ScheduleManager::getUnscheduledJobs($params["mfgDate"]);
            }
            break;
         }
         
         case "fetch":
         {
            if ($this->authenticate([Permission::VIEW_SCHEDULE]))
            {
               // Fetch single component.
               if (isset($params["entryId"]))
               {
                  $entryId = $params->getInt("customerId");
                  
                  $scheduleEntry = ScheduleEntry::load($entryId);
                  
                  if ($scheduleEntry)
                  {
                     // Augment schedule entry.
                     SchedulePage::augmentScheduleEntry($scheduleEntry);
                     
                     $this->result->scheduleEntry = $scheduleEntry;
                     $this->result->success = true;
                  }
                  else
                  {
                     $this->error("Invalid entry id [$entryId]");
                  }
               }
               // Fetch all components.
               else if (Page::requireParams($params, ["mfgDate"]))
               {
                  $this->result->success = true;
                  $this->result->schedule = ScheduleManager::getScheduledJobs($params["mfgDate"]);
                  
                  // Augment schedule entry.
                  foreach ($this->result->schedule as $scheduleEntry)
                  {
                     SchedulePage::augmentScheduleEntry($scheduleEntry);
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
   
   private static function augmentScheduleEntry(&$scheduleEntry)
   {
      $scheduleEntry->formattedStartDate = Time::dateTimeObject($scheduleEntry->startDate)->format("n-j-Y");
      $scheduleEntry->formattedEndDate = $scheduleEntry->endDate ? Time::dateTimeObject($scheduleEntry->endDate)->format("n-j-Y") : null;
      
   }
}

?>