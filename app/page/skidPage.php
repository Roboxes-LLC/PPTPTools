<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/app/page/page.php';
require_once ROOT.'/core/manager/skidManager.php';

class SkidPage extends Page
{
    public function handleRequest($params)
    {
       if ($this->authenticate([Permission::VIEW_SKID]))
       {
          switch ($this->getRequest($params))
          {
             /*
             case "save_defect":
             {
                if (Page::requireParams($params, ["category", "severity", "appPage", "title", "description"]))
                {
                   $defectId = $params->getInt("defectId");
                   $newDefect = ($defectId == Defect::UNKNOWN_DEFECT_ID);
                   
                   $defect = null;
                   if ($newDefect)
                   {
                      $defect = new Defect();
                      
                      $defect->dateTime = Time::now();
                      $defect->author = Authentication::getAuthenticatedUser()->userId;
                      $defect->siteId = Authentication::getSite();
                      $defect->status = DefectStatus::NEW;
                   }
                   else
                   {
                      $defect = Defect::load($defectId);
                      
                      if (!$defect)
                      {
                         $defect = null;
                         $this->error("Invalid defect id [$defectId]");
                      }
                   }
                   
                   if ($defect)
                   {
                      SkidPage::getDefectParams($defect, $params);
                      
                      if (Defect::save($defect))
                      {
                         $this->result->defectId = $defect->defectId;
                         $this->result->defect = $defect;
                         $this->result->success = true;
                         
                         ActivityLog::logComponentActivity(
                            Authentication::getSite(),
                            Authentication::getAuthenticatedUser()->userId,
                            ($newDefect ? ActivityType::ADD_DEFECT : ActivityType::EDIT_DEFECT),
                            $defect->defectId);
                      }
                      else
                      {
                         $this->error("Database error");
                      }
                   }
                }
                break;
             }
             
             case "delete_defect":
             {
                if (Page::requireParams($params, ["defectId"]))
                {
                   $defectId = $params->getInt("defectId");
                   
                   $defect = Defect::load($defectId);
                   
                   if ($defect)
                   {
                      Defect::delete($defectId);
                      
                      $this->result->defectId = $defectId;
                      $this->result->success = true;
                      
                      ActivityLog::logComponentActivity(
                         Authentication::getSite(),
                         Authentication::getAuthenticatedUser()->userId,
                         ActivityType::DELETE_DEFECT,
                         $defectId);
                   }
                   else
                   {
                      $this->error("Invalid defect id [$defectId]");
                   }
                }
                break;
             }
             */
             
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
                      $this->result->skid = $skid;
                      $this->result->success = true;
                   }
                   else
                   {
                      $this->error("Invalid skid id [$skidId]");
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
                      $dateTime = Time::getDateTime($skid->getCreatedAction()->dateTime);
                      $skid->formattedDateTime = $dateTime->format("n/j/Y g:i A");
                      
                      $jobInfo = JobInfo::load($skid->jobId);
                      $skid->jobNumber = $jobInfo ? $jobInfo->jobNumber : "";
                      
                      $skid->skidCode = $skid->getSkidCode();
                      $skid->skidStateLabel = SkidState::getLabel($skid->skidState);
                   }
                }
                break;
             }
          }
       }
       
       echo json_encode($this->result);
    }
    
    /*
    private function getDefectParams(&$defect, $params)
    {
       $defect->category = $params->getInt("category");
       $defect->severity = $params->getInt("severity");
       $defect->appPage = $params->getInt("appPage");
       $defect->title = $params->get("title");
       $defect->description = $params->get("description");
       
       if ($params->keyExists("status"))
       {
          $defect->status = $params->getInt("status");
       }
    }
    */
}
 
 ?>