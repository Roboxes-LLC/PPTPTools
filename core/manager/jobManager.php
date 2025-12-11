<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/pptpDatabase.php';
require_once ROOT.'/core/common/pptpDatabase.php';
require_once ROOT.'/common/database.php';

class JobManager
{
   // Constants for use in calling JobManager::getJobNumberOptions();
   const ALL_JOBS = true;
   const ACTIVE_JOBS = true;
   
   public static function getCustomerPartNumber($pptpPartNumber)
   {
      return (PPTPDatabaseAlt::getInstance()->getCustomerPartNumber($pptpPartNumber));
   }
   
   public static function getCustomer($jobId)
   {
      $customer = null;
      
      $job = JobInfo::load($jobId);
      
      if ($job)
      {
         $part = Part::load($job->partNumber, Part::USE_PPTP_NUMBER);
         
         if ($part)
         {
            $customer = Customer::load($part->customerId);
         }
      }
      
      return ($customer);
   }
   
   public static function getCustomerFromJobNumber($jobNumber)
   {
      $customer = null;
      
      $partNumber = JobInfo::getJobPrefix($jobNumber);

      $part = Part::load($partNumber, Part::USE_PPTP_NUMBER);
         
      if ($part)
      {
         $customer = Customer::load($part->customerId);
      }
      
      return ($customer);
   }
   
   public static function saveCustomerPartNumber($pptpPartNumber, $customerPartNumber)
   {
      return (PPTPDatabaseAlt::getInstance()->saveCustomerPartNumber($pptpPartNumber, $customerPartNumber));
   }
   
   public static function getMostRecentJob($jobNumber)
   {
      $jobInfo = null;
      
      $result = PPTPDatabase::getInstance()->getJobs($jobNumber, null);
      
      if ($result && ($row = $result->fetch_assoc()))
      {
         $jobInfo = new JobInfo();
         $jobInfo->initialize($row);
      }
      
      return ($jobInfo);
   }
   
   public static function getJobs($jobNumber, $jobStatuses)
   {
      $jobs = [];
      
      $result = PPTPDatabase::getInstance()->getJobs($jobNumber, $jobStatuses);
      
      while ($result && ($row = $result->fetch_assoc()))
      {
         $job = new JobInfo();
         $job->initialize($row);
         $job->part = Part::load($job->partNumber, Part::USE_PPTP_NUMBER);
         
         $jobs[] = $job;
      }
      
      return ($jobs);
   }
   
   public static function getJobNumberOptions($selectedJobNumber, $activeJobs)
   {
      $html = "<option style=\"display:none\">";
      
      $jobStatuses = $activeJobs ? [JobStatus::ACTIVE] : [];
      
      $jobs = JobManager::getJobs(JobInfo::UNKNOWN_JOB_NUMBER, $jobStatuses);
      
      $foundIt = false;
      
      foreach ($jobs as $job)
      {
         $label = $job->jobNumber;
         $value = $job->jobNumber;
         $selected = ($job->jobNumber == $selectedJobNumber) ? "selected" : "";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
         
         $foundIt |= ($job->jobNumber == $selectedJobNumber);
      }
      
      // Guarantee the selected job is in the options, even if not found in the database.
      if (!$foundIt)
      {
         $label = $selectedJobNumber;
         $value = $selectedJobNumber;
         $selected = "selected";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
      
      return ($html);
   }
   
   public static function jobHasData($jobId)
   {
      return (PPTPDatabase::getInstance()->jobHasData($jobId));
   }
}
   