<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/app/page/page.php';
require_once ROOT.'/core/manager/correctiveActionManager.php';

class CorrectiveActionPage extends Page
{
    public function handleRequest($params)
    {
       switch ($this->getRequest($params))
       {
          case "save_corrective_action":
          {
             if (Page::requireParams($params, ["correctiveActionId", "jobId", "employeeNumber", "occuranceDate", "description"]))
             {
                $correctiveActionId = $params->getInt("correctiveActionId");
                $newCorrectiveAction = ($correctiveActionId == CorrectiveAction::UNKNOWN_CA_ID);

                $correctiveAction = null;
                if ($newCorrectiveAction)
                {
                   $correctiveAction = new CorrectiveAction();
                }
                else
                {
                   $correctiveAction = CorrectiveAction::load($correctiveActionId);
                   
                   if (!$correctiveAction)
                   {
                      $correctiveAction = null;
                      $this->error("Invalid corrective action id [$correctiveActionId]");
                   }
                }
                
                if ($correctiveAction)
                {
                   CorrectiveActionPage::getCorrectiveActionParams($correctiveAction, $params);
                   
                   if (CorrectiveAction::save($correctiveAction))
                   {
                      $this->result->correctiveActionId = $correctiveAction->correctiveActionId;
                      $this->result->correctiveAction = $correctiveAction;
                      $this->result->success = true;
                      
                      if ($newCorrectiveAction)
                      {
                         $correctiveAction->create(Time::now(), Authentication::getAuthenticatedUser()->employeeNumber, null);
                      }
                      
                      ActivityLog::logComponentActivity(
                         Authentication::getAuthenticatedUser()->employeeNumber,
                         ($newCorrectiveAction ? ActivityType::ADD_CORRECTIVE_ACTION : ActivityType::EDIT_CORRECTIVE_ACTION),
                         $correctiveAction->correctiveActionId,
                         $correctiveAction->getCorrectiveActionNumber());
                   }
                   else
                   {
                      $this->error("Database error");
                   }
                }
             }
             break;
          }
          
          case "delete_corrective_action":
          {
             if ($this->authenticate([Permission::EDIT_CORRECTIVE_ACTIONS]))
             {
                if (Page::requireParams($params, ["correctiveActionId"]))
                {
                   $correctiveActionId = $params->getInt("correctiveActionId");
                   
                   $correctiveAction = CorrectiveAction::load($correctiveActionId);
                   
                   if ($correctiveAction)
                   {
                      CorrectiveAction::delete($correctiveActionId);
                      
                      $this->result->correctiveActionId = $correctiveActionId;
                      $this->result->success = true;
                      
                      ActivityLog::logComponentActivity(
                         Authentication::getAuthenticatedUser()->employeeNumber,
                         ActivityType::DELETE_CORRECTIVE_ACTION,
                         $correctiveAction->correctiveActionId,
                         $correctiveAction->getCorrectiveActionNumber());
                   }
                   else
                   {
                      $this->error("Invalid corrective action id [$correctiveActionId]");
                   }                
                }
             }
             break;
          }                   
             
          case "fetch":
          {
             if ($this->authenticate([Permission::VIEW_CORRECTIVE_ACTIONS]))
             {
                // Fetch single component.
                if (isset($params["correctiveActionId"]))
                {
                   $correctiveActionId = $params->getInt("correctiveActionId");
                   
                   $correctiveAction = CorrectiveAction::load($correctiveActionId);
                   
                   if ($correctiveAction)
                   {
                      $this->result->success = true;
                      $this->result->correctiveAction = $correctiveAction;
                      
                      CorrectiveActionPage::augmentCorrectiveAction($correctiveAction);
                   }
                   else
                   {
                      $this->error("Invalid corrective action id [$correctiveActionId]");
                   }
                }
                // Fetch all components.
                else
                {
                   $dateTime = Time::dateTimeObject(null);
                   
                   $dateType = FilterDateType::OCCURANCE_DATE;
                   $endDate = Time::endOfDay($dateTime->format(Time::STANDARD_FORMAT));
                   $startDate = Time::startofDay($dateTime->modify("-1 month")->format(Time::STANDARD_FORMAT));
                   $allActive = false;
                   
                   if (isset($params["dateType"]))
                   {
                      $dateType = $params->getInt("dateType");
                   }
                   
                   if (isset($params["startDate"]))
                   {
                      $startDate = Time::startOfDay($params["startDate"]);
                   }
                   
                   if (isset($params["endDate"]))
                   {
                      $endDate = Time::endOfDay($params["endDate"]);
                   }
                   
                   if (isset($params["activeActions"]))
                   {
                      $allActive = $params->getBool("activeActions");
                   }
                   
                   $this->result->success = true;
                   $this->result->correctiveActions = CorrectiveActionManager::getCorrectiveActions($dateType, $startDate, $endDate, $allActive);
                   
                   // Augment data.
                   foreach ($this->result->correctiveActions as $correctiveAction)
                   {
                      CorrectiveActionPage::augmentCorrectiveAction($correctiveAction);
                   }
                }
             }
             break;
             
          }
          
          default:
          {
             $this->error("Unsupported command [{$this->getRequest($params)}]");
             break;
          }
       }
       
       echo json_encode($this->result);
    }
    
    private function getCorrectiveActionParams(&$correctiveAction, $params)
    {
       $correctiveAction->jobId = $params->getInt("jobId");
       $correctiveAction->employee = $params->getInt("employeeNumber");
       $correctiveAction->occuranceDate = $params->get("occuranceDate");
       $correctiveAction->description = $params->get("description");
    }
    
    private static function augmentCorrectiveAction(&$correctiveAction)
    {
       $correctiveAction->correctiveActionNumber = $correctiveAction->getCorrectiveActionNumber();
       
       $correctiveAction->formattedOccuranceDate = $correctiveAction->occuranceDate ? Time::dateTimeObject($correctiveAction->occuranceDate)->format("n/j/Y") : "";
       
       // customerName
       $customer = JobManager::getCustomer($correctiveAction->jobId);
       if ($customer)
       {
          $correctiveAction->customerName = $customer->customerName;
          $correctiveAction->customerName = $customer->customerName;
       }
       
       // pptpPartNumber, customerPartNumber
       $job = JobInfo::load($correctiveAction->jobId);
       if ($job)
       {
          $pptpPartNumber = JobInfo::getJobPrefix($job->jobNumber);
          $part = Part::load($pptpPartNumber, false);  // Use PPTP number.
          if ($part)
          {
             $correctiveAction->pptpPartNumber = $part->pptpNumber;
             $correctiveAction->customerPartNumber = $part->customerNumber;
          }
       }
       
       $correctiveAction->locationLabel = 
          ($correctiveAction->location != ShipmentLocation::UNKNOWN) ? 
             ShipmentLocation::getLabel($correctiveAction->location) : 
             null;
       
       $correctiveAction->initiatorLabel =
          ($correctiveAction->initiator != CorrectiveActionInitiator::UNKNOWN) ?
             CorrectiveActionInitiator::getLabel($correctiveAction->initiator) :
             null;
    }
 }
 
 ?>