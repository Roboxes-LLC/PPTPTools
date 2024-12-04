<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/pptpDatabase.php';
require_once ROOT.'/core/common/pptpDatabase.php';
require_once ROOT.'/common/database.php';

class JobManager
{
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
}
   