<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/core/manager/jobManager.php';
require_once ROOT.'/core/manager/partManager.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/jobInfo.php';
require_once ROOT.'/common/params.php';

abstract class JobInputField
{
   const FIRST = 0;
   const CREATOR = JobInputField::FIRST;
   const DATE = 1;
   const JOB_NUMBER_PREFIX = 2;
   const JOB_NUMBER_SUFFIX = 3;
   const PART_NUMBER = 4;
   const WC_NUMBER = 5;
   const SAMPLE_WEIGHT = 6;
   const CYCLE_TIME = 7;
   const GROSS_PIECES = 8;
   const NET_PERCENTAGE = 9;
   const NET_PIECES = 10;
   const STATUS = 11;
   const FIRST_PART_TEMPLATE = 12;
   const IN_PROCESS_TEMPLATE = 13;
   const LINE_TEMPLATE = 14;
   const QCP_TEMPLATE = 15;
   const FINAL_TEMPLATE = 16;
   const CUSTOMER_PRINT = 17;
   const CUSTOMER_PART_NUMBER = 18;
   const CUSTOMER_NAME = 19;
   const LAST = 20;
   const COUNT = JobInputField::LAST - JobInputField::FIRST;
}

abstract class View
{
   const NEW_JOB = 0;
   const VIEW_JOB = 1;
   const EDIT_JOB = 2;
}

function getParams()
{
   static $params = null;
   
   if (!$params)
   {
      $params = Params::parse();
   }
   
   return ($params);
}

function getView()
{
   $view = View::VIEW_JOB;
   
   if (getJobId() == JobInfo::UNKNOWN_JOB_ID)
   {
      $view = View::NEW_JOB;
   }
   else if (Authentication::checkPermissions(Permission::EDIT_JOB))
   {
      $view = View::EDIT_JOB;
   }
   
   return ($view);
}

function isEditable($field)
{
   $view = getView();
   
   // Start with the edit mode, as dictated by the view.
   $isEditable = (($view == View::NEW_JOB) ||
                  ($view == View::EDIT_JOB));
   
   switch ($field)
   {
      case JobInputField::CREATOR:
      case JobInputField::DATE:
      case JobInputField::JOB_NUMBER_PREFIX:
      case JobInputField::CUSTOMER_PART_NUMBER:
      case JobInputField::CUSTOMER_NAME:
      case JobInputField::CYCLE_TIME:
      case JobInputField::NET_PERCENTAGE:
      {
         $isEditable = false;
         break;
      }
      
      case JobInputField::PART_NUMBER:
      case JobInputField::JOB_NUMBER_SUFFIX:
      {
         $isEditable = ($view == View::NEW_JOB);
         break;
      }

      default:
      {
         // Edit status based solely on view.
         break;
      }
   }
   
   return ($isEditable);
}

function getDisabled($field)
{
   return (isEditable($field) ? "" : "disabled");
}
$getDisabled = "getDisabled";  // For calling in HEREDOC.

function getJobId()
{
   $jobId = JobInfo::UNKNOWN_JOB_ID;
   
   $params = getParams();
   
   if ($params->keyExists("jobId"))
   {
      $jobId = $params->getInt("jobId");
   }
   
   return ($jobId);
}

function getCopyFromJobId()
{
   $jobId = JobInfo::UNKNOWN_JOB_ID;
   
   $params = getParams();
   
   if ($params->keyExists("copyFromJobId"))
   {
      $jobId = $params->getInt("copyFromJobId");
   }
   
   return ($jobId);
}

function getJobInfo()
{
   static $jobInfo = null;
   
   if ($jobInfo == null)
   {
      $jobId = getJobId();
      
      $copyFromJobId = getCopyFromJobId();
      
      if ($jobId != JobInfo::UNKNOWN_JOB_ID)
      {
         $jobInfo = JobInfo::load($jobId);
      }
      else if ($copyFromJobId != JobInfo::UNKNOWN_JOB_ID)
      {
         // Start with the copy-from job.
         $jobInfo = JobInfo::load($copyFromJobId);
         
         if ($jobInfo)
         {
            // Clear out certain values.
            $jobInfo->jobId = JobInfo::UNKNOWN_JOB_ID;
         }
         else
         {
            $jobInfo = new JobInfo();
         }
      }
      else
      {
         $jobInfo = new JobInfo();
      }
      
      // Set properties for new jobs.
      if ($jobInfo->jobId == JobInfo::UNKNOWN_JOB_ID)
      {
         $jobInfo->creator = Authentication::getAuthenticatedUser()->employeeNumber;
         $jobInfo->dateTime = Time::now();
         $jobInfo->status = JobStatus::PENDING;
      }
   }
   
   return ($jobInfo);
}


function getHeading()
{
   $heading = "";
   
   switch (getView())
   {
      case View::NEW_JOB:
      {
         $heading = "Add a New Job";
         break;
      }
      
      case View::EDIT_JOB:
      {
         $heading = "Edit an Existing Job";
         break;
      }
      
      case View::VIEW_JOB:
      default:
      {
         $heading = "View Job Details";
         break;
      }
   }

   return ($heading);
}

function getDescription()
{
   $description = "";
   
   switch (getView())
   {
      case View::NEW_JOB:
      {
         $description = "Start with a job number and work center.  Gross/net parts per hour can be found in the JobBOSS database for your part.<br/><br/>Once you're satisfied, click Save below to add this job to the system.";
         break;
      }
         
      case View::EDIT_JOB:
      {
         $description = "You may revise any of the fields for this job and then select save when you're satisfied with the changes.";
         break;
      }
         
      case View::VIEW_JOB:
      default:
      {
         $description = "View a previously saved job in detail.";
         break;
      }
   }
   
   return ($description);
}

function getStatusOptions()
{
   $options = "";
   
   $selectedJobStatus = getJobInfo()->status;
   
   for ($jobStatus = JobStatus::FIRST; $jobStatus < JobStatus::LAST; $jobStatus++)
   {
      $selected = ($jobStatus == $selectedJobStatus) ? "selected" : "";
      
      $name = JobStatus::getName($jobStatus);
      
      $options .= "<option value=\"$jobStatus\" $selected>$name</option>";
   }
   
   return ($options);
}

function getPPTPPartNumber()
{
   $pptpPartNumber = Part::UNKNOWN_PPTP_NUMBER;
   
   $jobInfo = getJobInfo();
   
   if ($jobInfo->part)
   {
      $pptpPartNumber = $jobInfo->part->pptpNumber;
   }
   
   return ($pptpPartNumber);
}

function getPPTPPartNumberOptions($selectedPartNumber)
{
   $html = "<option style=\"display:none\">";
   
   $parts = PartManager::getParts();
   
   foreach ($parts as $part)
   {
      $value = $part->pptpNumber;
      $label = $part->pptpNumber;
      $selected = ($part->pptpNumber == $selectedPartNumber) ? "selected" : "";
      
      $html .= "<option value=\"$value\" $selected>$label</option>";
   }
   
   return ($html);
}

function getCustomerPartNumber()
{
   $customerPartNumber = Part::UNKNOWN_CUSTOMER_NUMBER;
   
   $jobInfo = getJobInfo();
   
   if ($jobInfo->part)
   {
      $customerPartNumber = $jobInfo->part->customerNumber;
   }
   
   return ($customerPartNumber);
}

function getCustomerName()
{
   $customerName = null;
   
   $jobInfo = getJobInfo();
   
   if ($jobInfo->part)
   {
      $customer = Customer::load($jobInfo->part->customerId);
      
      if ($customer)
      {
         $customerName = $customer->customerName;
      }
   }
   
   return ($customerName);
}

function getForm()
{
   global $getDisabled;
   
   $jobInfo = getJobInfo();
   $JobNumberSuffix = JobInfo::getJobSuffix($jobInfo->jobNumber);
   $authorName = $jobInfo->creator ? UserInfo::load($jobInfo->creator)->getFullName() : null;
   $creationDate = Time::toJavascriptDate($jobInfo->dateTime);
   $partNumberOptions = getPPTPPartNumberOptions($jobInfo->partNumber);
   $customerName = getCustomerName();
   $customerPartNumber = getCustomerPartNumber();
   $wcNumberOptions = JobInfo::getWcNumberOptions(JobInfo::UNKNOWN_JOB_NUMBER, $jobInfo->wcNumber);
   $statusOptions = getStatusOptions();
   
   $html =
<<< HEREDOC
   <form id="input-form" action="" method="POST" style="display:block">
      <input type="hidden" name="request" value="save_job">      
      <input type="hidden" name="jobId" value="$jobInfo->jobId">
      <input type="hidden" name="creator" value="$jobInfo->creator">
      <input type="hidden" name="dateTime" value="$jobInfo->dateTime">
      <input id="job-number-input" type="hidden" name="jobNumber" value="$jobInfo->jobNumber">
      <input type="hidden" name="partNumber" value="$jobInfo->partNumber">
      
      <div class="flex-horizontal flex-left flex-wrap">
      
         <div class="flex-vertical flex-left" style="margin-right: 50px;">
         
            <div class="form-item">
               <div class="form-label">Creator</div>
               <input type="text" style="width:180px;" value="$authorName" {$getDisabled(JobInputField::CREATOR)}/>
            </div>
            <div class="form-item">
               <div class="form-label">Date</div>
               <input type="date" name="date" style="width:180px;" value="$creationDate" {$getDisabled(JobInputField::DATE)}/>
            </div>
            
         </div>
         
         <div class="flex-vertical flex-left">
         
            <div class="form-item">
               <div class="form-label-long">PPTP Part #</div>
               <select id="pptp-part-number-input" name="partNumber" {$getDisabled(JobInputField::PART_NUMBER)} required>
                  $partNumberOptions
               </select>
            </div>
            
            <div class="form-item">
               <div class="form-label-long">Job #</div>
               <div class="flex-horizontal flex-v-center flex-left">
                  <input id="job-number-prefix-input" type="text" name="jobNumberPrefix" style="width:150px;" value="$jobInfo->partNumber" autocomplete="off" {$getDisabled(JobInputField::JOB_NUMBER_PREFIX)}/>
                  <div>&nbsp-&nbsp</div>
                  <input id="job-number-suffix-input" type="text" name="jobNumberSuffix" style="width:150px;" value="$JobNumberSuffix" autocomplete="off" {$getDisabled(JobInputField::JOB_NUMBER_SUFFIX)} required/>
               </div>
            </div>
            
            <div class="form-item">
               <div class="form-label-long">Customer</div>
               <input id="customer-name-input" type="text" style="width:150px;" value="$customerName" {$getDisabled(JobInputField::CUSTOMER_NAME)}/>
            </div>
            
            <div class="form-item">
               <div class="form-label-long">Customer Part #</div>
               <input id="customer-part-number-input" type="text" style="width:150px;" value="$customerPartNumber" {$getDisabled(JobInputField::CUSTOMER_PART_NUMBER)}/>
            </div>
            
            <div class="form-item">
               <div class="form-label-long">Work center #</div>
               <div><select id="work-center-input" name="wcNumber" {$getDisabled(JobInputField::WC_NUMBER)} required>$wcNumberOptions</select></div>
            </div>
            
            <div class="form-item">
               <div class="form-label-long">Gross Pieces/Hour</div>
               <input id="gross-parts-per-hour-input" type="number" name="grossPartsPerHour" style="width:150px;" value="$jobInfo->grossPartsPerHour" {$getDisabled(JobInputField::GROSS_PIECES)} required/>
            </div>

            <div class="form-item">
               <div class="form-label-long">Cycle Time</div>
               <input id="cycle-time-input" type="number" name="cycleTime" style="width:150px;" {$getDisabled(JobInputField::CYCLE_TIME)}/>
            </div>
            
            <div class="form-item">
               <div class="form-label-long">Net Pieces/Hour</div>
               <input id="net-parts-per-hour-input" type="number" name="netPartsPerHour" style="width:150px;" value="$jobInfo->netPartsPerHour" {$getDisabled(JobInputField::NET_PIECES)} required/>
            </div>
            
            <div class="form-item">
               <div class="form-label-long">Net Percentage</div>
               <input id="net-percentage-input" type="number" name="netPercentage" style="width:150px;" {$getDisabled(JobInputField::NET_PERCENTAGE)}/>
               <div class="form-label">&nbsp%</div>
            </div>
            
            <div class="form-item">
               <div class="form-label-long">Job status</div>
               <div><select id="status-input" name="status" {$getDisabled(JobInputField::STATUS)} required>$statusOptions</select></div>
            </div>
            
         </div>
         
      </div>
   </form>
HEREDOC;
                  
   return ($html);
}

// ********************************** BEGIN ************************************

Time::init();

session_start();

if (!Authentication::isAuthenticated())
{
   header('Location: /login.php');
   exit;
}

$versionQuery = versionQuery();
$javascriptFile = "job.js";
$javascriptClass = "Job";
$appPageId = AppPage::JOBS;
$heading = getHeading();
$description = getDescription();
$formId = "input-form";
$form = getForm();

include ROOT.'/templates/formPageTemplate.php'
?>
