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
               if (Page::requireParams($params, ["jobId", "mfgDate"]))
               {
                  $jobId = $params->getInt("jobId");
                  $mfgDate = $params->get("mfgDate");
                  
                  $scheduleEntry = new ScheduleEntry();
                  $scheduleEntry->jobId = $jobId;
                  $scheduleEntry->mfgDate = $mfgDate;
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
                Page::requireParams($params, ["startDate"]))
            {
               $this->result->success = true;
               $this->result->unscheduled = ScheduleManager::getUnscheduledJobs($params["startDate"]);
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
                     $this->result->scheduleEntry = $scheduleEntry;
                     $this->result->success = true;
                  }
                  else
                  {
                     $this->error("Invalid entry id [$entryId]");
                  }
               }
               // Fetch all components.
               else if (Page::requireParams($params, ["startDate"]))
               {
                  $this->result->success = true;
                  $this->result->schedule = ScheduleManager::getScheduledJobs($params["startDate"]);
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
}

?>