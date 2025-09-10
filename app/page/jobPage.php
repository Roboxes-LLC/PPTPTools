<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/app/page/page.php';
require_once ROOT.'/common/jobInfo.php';
require_once ROOT.'/core/manager/partManager.php';

class JobPage extends Page
{
   public function handleRequest($params)
   {
      if (Page::authenticate([Permission::VIEW_JOB]))
      {
         $request = $this->getRequest($params);
         
         switch ($request)
         {
            case "get_customer_number":
            {
               if (Page::requireParams($params, ["pptpPartNumber"]))
               {
                  $pptpPartNumber = $params->get("pptpPartNumber");
                  
                  $customerPartNumber = PPTPDatabaseAlt::getInstance()->getCustomerPartNumber($pptpPartNumber);
                  
                  if ($customerPartNumber !== null)
                  {
                     $this->result->success = true;
                     $this->result->pptpPartNumber = $pptpPartNumber;
                     $this->result->customerPartNumber = $customerPartNumber;
                  }
                  else
                  {
                     $this->error("Invalid part [$pptpPartNumber]");
                  }
               }
               break;
            }
            
            case "fetch_parts":
            {
               if (Page::requireParams($params, ["customerId"]))
               {
                  $customerId = $params->getInt("customerId");
                  
                  $customer = Customer::load($customerId);
                  
                  if ($customer)
                  {
                     $this->result->success = true;
                     $this->result->parts = PartManager::getPartsForCustomer($customerId);
                  }
                  else
                  {
                     $this->error("Invalid customer id [$customerId]");
                  }
               }
               break;
            }
            
            case "save_job":
            {
               if (Page::authenticate([Permission::EDIT_JOB]))
               {
                  if (Page::requireParams($params, ["jobId", "partNumber", "jobNumber", "wcNumber", "grossPartsPerHour", "netPartsPerHour", "status"]))
                  {
                     $jobId = $params->getInt("jobId");
                     $isNew = ($jobId == JobInfo::UNKNOWN_JOB_ID);
                     
                     $job = null;
                     if ($isNew)
                     {
                        $job = new JobInfo();
                        $job->creator = Authentication::getAuthenticatedUser()->employeeNumber;
                        $job->dateTime = Time::now();
                     }
                     else
                     {
                        $job = JobInfo::load($jobId);
                        
                        if (!$job)
                        {
                           $job = null;
                           $this->error("Invalid job id [$jobId]");
                        }
                     }
                     
                     if ($job)
                     {
                        JobPage::getJobParams($job, $params);
                        
                        if (JobPage::checkUnique($job))
                        {
                           if (JobInfo::save($job))
                           {
                              $this->result->jobId = $job->jobId;
                              $this->result->job = $job;
                              $this->result->success = true;
                              
                              /*
                               ActivityLog::logComponentActivity(
                               Authentication::getAuthenticatedUser()->employeeNumber,
                               ($newOrder ? ActivityType::ADD_JOB : ActivityType::EDIT_JOB),
                               $job->jobId,
                               $salesOrder->jobNumber);
                               */
                           }
                           else
                           {
                              $this->error("Database error");
                           }
                        }
                        else
                        {
                           $this->error("Duplicate job [job# $job->jobNumber, wc# $job->wcNumber]");
                        }
                     }
                  }
               }
               break;
            }
            
            case "delete_job":
            {  
               if (Page::authenticate([Permission::EDIT_JOB]))
               {
                  if (Page::requireParams($params, ["jobId"]))
                  {
                     $jobId = $params->getInt("jobId");
                     
                     $job = JobInfo::load($jobId);
                     
                     if ($jobId)
                     {
                        JobInfo::delete($jobId);
                        
                        $this->result->jobId = $jobId;
                        $this->result->success = true;
                        
                        /*
                         ActivityLog::logComponentActivity(
                         Authentication::getAuthenticatedUser()->employeeNumber,
                         ActivityType::DELETE_JOB,
                         $job->jobId,
                         $job->jobNumber);
                         */
                     }
                     else
                     {
                        $this->error("Invalid job id [$jobId]");
                     }
                  }
               }
               break;
            }
            
            case "fetch":
            {
               // Fetch single component.
               if (isset($params["jobId"]))
               {
                  $jobId = $params->getInt("jobId");
                  
                  $jobInfo = JobInfo::load($jobId);
                  
                  if ($jobInfo)
                  {
                     $this->result->success = true;
                     $this->result->$jobInfo = $jobInfo;
                  }
                  else
                  {
                     $this->error("Invalid job id [$jobId]");
                  }
               }
               // Fetch single component by components.
               else if (isset($params["jobNumber"]) && isset($params["wcNumber"]))
               {
                  $jobNumber = $params["jobNumber"];
                  $wcNumber = $params["wcNumber"];
                  
                  $jobInfo = JobInfo::load(JobInfo::getJobIdByComponents($jobNumber, $wcNumber));
                  
                  if ($jobInfo)
                  {
                     $this->result->success = true;
                     $this->result->jobInfo = $jobInfo;
                  }
                  else
                  {
                     $this->error("Invalid job [$jobNumber, $wcNumber]");
                  }
               }
               // Fetch all components.
               else
               {
                  $jobStatuses = array();
                  
                  for ($jobStatus = JobStatus::FIRST; $jobStatus < JobStatus::LAST; $jobStatus++)
                  {
                     $name = strtolower(JobStatus::getName($jobStatus));
                     
                     if (isset($params[$name]) && $params->getBool($name))
                     {
                        $jobStatuses[] = $jobStatus;
                     }
                  }
                  
                  $this->result->success = true;
                  $this->result->jobs = JobManager::getJobs(JobInfo::UNKNOWN_JOB_NUMBER, $jobStatuses);
                  
                  foreach ($this->result->jobs as $jobInfo)
                  {
                     $jobInfo->wcLabel = JobInfo::getWcLabel($jobInfo->wcNumber);
                     $jobInfo->statusLabel = JobStatus::getName($jobInfo->status);
                     $jobInfo->cycleTime = $jobInfo->getCycleTime();
                     $jobInfo->netPercentage = $jobInfo->getNetPercentage();
                     
                     $jobInfo->customerName = null;
                     if ($jobInfo->part)
                     {
                        $customer = Customer::load($jobInfo->part->customerId);
                        if ($customer)
                        {
                           $jobInfo->customerName = $customer->customerName;
                        }
                     }
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
   
   private function getJobParams(&$job, $params)
   {
      $job->partNumber = $params->get("partNumber");
      $job->jobNumber = $params->get("jobNumber");
      $job->wcNumber = $params->getInt("wcNumber");
      $job->grossPartsPerHour = $params->getInt("grossPartsPerHour");
      $job->netPartsPerHour = $params->getInt("netPartsPerHour");
      $job->status = $params->getInt("status");
   }
   
   private function checkUnique($job)
   {
      $existingJobId = JobInfo::getJobIdByComponents($job->jobNumber, $job->wcNumber);
      
      return ((($job->jobId == JobInfo::UNKNOWN_JOB_ID) && ($existingJobId == JobInfo::UNKNOWN_JOB_ID)) ||  // New job
              (($job->jobId != JobInfo::UNKNOWN_JOB_ID) && ($existingJobId == $job->jobId)));               // Updated job
   }
}