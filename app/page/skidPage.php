<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/app/page/page.php';
require_once ROOT.'/common/panTicket.php';
require_once ROOT.'/common/userInfo.php';
require_once ROOT.'/core/manager/skidManager.php';

class SkidPage extends Page
{
    public function handleRequest($params)
    {
       if ($this->authenticate([Permission::VIEW_SKID]))
       {
          switch ($this->getRequest($params))
          {
             case "generate_skid":
             {
                $jobId = JobInfo::UNKNOWN_JOB_ID;
                
                // Generate skid from job id.
                if ($params->keyExists("jobId"))
                {
                   $jobId = $params->getInt("jobId");
                }
                // Generate skid from pan ticket code.
                else if ($params->keyExists("panTicketCode"))
                {
                   $timeCardId = PanTicket::getPanTicketId($params->get("panTicketCode"));
                   
                   if ($timeCardId != TimeCardInfo::UNKNOWN_TIME_CARD_ID)
                   {
                      $timeCardInfo = TimeCardInfo::load($timeCardId);
                      if ($timeCardInfo)
                      {
                         $jobId = $timeCardInfo->jobId;
                      }
                   }
                }
                // Generate skid from job number and WC number pair.
                else if ($params->keyExists("jobNumber") &&
                         $params->keyExists("wcNumber"))
                {
                   $jobNumber = $params->get("jobNumber");
                   $wcNumber = $params->get("wcNumber");
                   
                   $jobId = JobInfo::getJobIdByComponents($jobNumber, $wcNumber);
                }
                else
                {
                   $this->error("Missing parameters.");
                }
                
                if ($jobId != JobInfo::UNKNOWN_JOB_ID)
                {
                   $skid = new Skid();
                   
                   $skid->jobId = $jobId;
                   $skid->skidState = SkidState::CREATED;
                   $skid->dateTime = Time::now(Time::STANDARD_FORMAT);
                   $skid->author = Authentication::getAuthenticatedUser()->employeeNumber;
                   
                   if (Skid::save($skid))
                   {
                      // CREATED skid action.
                      $skid->create(Time::now(Time::STANDARD_FORMAT), Authentication::getAuthenticatedUser()->employeeNumber, null);
                      
                      SkidPage::augmentSkidData($skid);
                      
                      $this->result->skidId = $skid->skidId;
                      $this->result->skid = $skid;
                      $this->result->success = true;
                   }
                   else
                   {
                      $this->error("Database error");
                   }
                }
                break;
             }
             
             case "delete_skid":
             {
                if (Page::requireParams($params, ["skidId"]))
                {
                   $skidId = $params->getInt("skidId");
                   
                   $skid = Skid::load($skidId);
                   
                   if ($skid)
                   {
                      Skid::delete($skidId);
                      
                      $this->result->skidId = $skidId;
                      $this->result->success = true;
                   }
                   else
                   {
                      $this->error("Invalid skid id [$skidId]");
                   }
                }
                break;
             }
             
             case "fetch":
             default:
             {
                // Fetch single component.
                if (isset($params["skidId"]))
                {
                   $skidId = $params->getInt("skidId");
                   
                   $skid = Skid::load($skidId);
                   
                   if ($skid)
                   {
                      SkidPage::augmentSkidData($skid);
                      
                      $this->result->skid = $skid;
                      $this->result->success = true;
                   }
                   else
                   {
                      $this->error("Invalid skid id [$skidId]");
                   }
                }
                // Fetch all components by pan ticket code.
                else if ($params->keyExists("skidTicketCode"))
                {
                   $skidTicketCode = $params->get("skidTicketCode");
                   
                   $skid = SkidManager::getSkidBySkidTicketCode($skidTicketCode);

                   if ($skid)
                   {
                      SkidPage::augmentSkidData($skid);
                      
                      $this->result->skid = $skid;
                      $this->result->success = true;
                      
                   }
                   else
                   {
                      $this->error("Invalid skid ticket [$skidTicketCode]");
                   }
                }
                // Fetch all components by job.
                else if (isset($params["jobNumber"]) &&
                         isset($params["wcNumber"]))
                {
                   $jobNumber = $params->get("jobNumber");
                   $wcNumber = $params->getInt("wcNumber");
                   
                   $jobId = JobInfo::getJobIdByComponents($jobNumber, $wcNumber);
                   
                   if ($jobId != JobInfo::UNKNOWN_JOB_ID)
                   {
                      $this->result->skids = SkidManager::getSkidsByJob($jobId);
                      $this->result->success = true;
                      
                      // Augment data.
                      foreach ($this->result->skids as $skid)
                      {
                         SkidPage::augmentSkidData($skid);
                      }
                   }
                   else
                   {
                      $this->error("Invalid job/WC number [$jobNumber/$wcNumber]");
                   }
                }
                // Fetch all components.
                else 
                {
                   $dateTime = Time::getDateTime(null);
                   
                   $endDate = Time::endOfDay($dateTime->format(Time::STANDARD_FORMAT));
                   $startDate = Time::startofDay($dateTime->modify("-1 month")->format(Time::STANDARD_FORMAT));
                   $activeSkids = false;
                   
                   if (isset($params["startDate"]))
                   {
                      $startDate = Time::startOfDay($params["startDate"]);
                   }
                   
                   if (isset($params["endDate"]))
                   {
                      $endDate = Time::endOfDay($params["endDate"]);
                   }
                   
                   if (isset($params["activeSkids"]))
                   {
                      $activeSkids = $params->getBool("activeSkids");
                   }
                   
                   $this->result->success = true;
                   
                   if ($activeSkids)
                   {
                      $this->result->skids = SkidManager::getSkidsByState(SkidState::$activeStates);
                   }
                   else
                   {
                      $this->result->skids = SkidManager::getSkids($startDate, $endDate);
                   }
                   
                   // Augment data.
                   foreach ($this->result->skids as $skid)
                   {
                      SkidPage::augmentSkidData($skid);
                   }
                }
                break;
             }
          }
       }
       
       echo json_encode($this->result);
    }
    
    private static function getSkidParams(&$skid, $params)
    {
       $skid->jobId = $params->getInt("jobId");
       
       if ($params->keyExists("notes"))
       {
          $skid->notes = $params->get("notes");
       }
    }
    
    private static function augmentSkidData(&$skid)
    {
       $dateTime = Time::getDateTime($skid->getCreatedAction()->dateTime);
       $skid->formattedDateTime = $dateTime->format("n/j/Y g:i A");
       
       $authorName = null;
       $userInfo = UserInfo::load($skid->getCreatedAction()->author);
       if ($userInfo)
       {
          $authorName = $userInfo->employeeNumber . " - " . $userInfo->getFullName();
       }
       $skid->authorName = $authorName;
       
       
       $jobInfo = JobInfo::load($skid->jobId);
       $skid->jobNumber = $jobInfo ? $jobInfo->jobNumber : "";
       
       $skid->skidTicketCode = $skid->getSkidTicketCode();
       $skid->skidStateLabel = SkidState::getLabel($skid->skidState);
    }
}
 
 ?>