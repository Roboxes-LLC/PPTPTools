<?php

require_once '../common/activity.php';
require_once '../common/header2.php';
require_once '../common/jobInfo.php';
require_once '../common/menu.php';
require_once '../common/params.php';

const ACTIVITY = Activity::JOBS;
$activity = Activity::getActivity(ACTIVITY);

abstract class JobInputField
{
   const FIRST = 0;
   const CREATOR = JobInputField::FIRST;
   const DATE = 1;
   const JOB_NUMBER = 2;
   const PART_NUMBER = 3;
   const WC_NUMBER = 4;
   const SAMPLE_WEIGHT = 5;
   const CYCLE_TIME = 6;
   const GROSS_PIECES = 7;
   const NET_PERCENTAGE = 8;
   const NET_PIECES = 9;
   const STATUS = 10;
   const IN_PROCESS_TEMPLATE = 11;
   const LINE_TEMPLATE = 12;
   const QCP_TEMPLATE = 13;
   const CUSTOMER_PRINT = 14;
   const LAST = 15;
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
   $view = View::NEW_JOB;
   
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
      case JobInputField::PART_NUMBER:
      case JobInputField::GROSS_PIECES:
      case JobInputField::NET_PIECES:
      {
         $isEditable = false;
         break;
      }
      
      case JobInputField::JOB_NUMBER:
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
            
            // Set new fields.
            $jobInfo->jobNumber = JobInfo::getJobPrefix($jobInfo->jobNumber);
            $jobInfo->dateTime = Time::now("Y-m-d h:i:s A");
            $jobInfo->status = JobStatus::PENDING;
            
            if ($user = Authentication::getAuthenticatedUser())
            {
               $jobInfo->creator = $user->employeeNumber;
            }
         }
      }
      
      if ($jobInfo == null)
      {
         $jobInfo = new JobInfo();
         
         if ($user = Authentication::getAuthenticatedUser())
         {
            $jobInfo->creator = $user->employeeNumber;
         }
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
         $description = "Start with a job number and work center.  Cyle time and net percentage can be found in the JobBOSS database for your part.<br/><br/>Once you're satisfied, click Save below to add this time card to the system.";
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

function getCreator()
{
   $creator = "";

   $userInfo = UserInfo::load(getJobInfo()->creator);
   
   if ($userInfo)
   {
      $creator = $userInfo->getFullName();
   }
   
   return ($creator);
}

function getCreationDate()
{
   return(date_format(new DateTime(getJobInfo()->dateTime), "Y-m-d"));
}

function getWcNumberOptions()
{
   $options = "";
   
   $database = PPTPDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getWorkCenters();
      
      if ($result)
      {
         $selectedWcNumber = getJobInfo()->wcNumber;
         
         while ($row = $result->fetch_assoc())
         {
            $wcNumber = $row["wcNumber"];
            
            $selected = ($wcNumber == $selectedWcNumber) ? "selected" : "";
            
            $options .= "<option value=\"$wcNumber\" $selected>$wcNumber</option>";
         }
      }
   }
   
   return ($options);
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

function getInspectionTemplateOptions($inspectionType, $selectedTemplateId)
{
   $options = "<option value=\"" . InspectionTemplate::UNKNOWN_TEMPLATE_ID . "\"></option>";

   $inspectionTemplates = InspectionTemplate::getInspectionTemplates($inspectionType);
   
   foreach ($inspectionTemplates as $templateId)
   {
      $inspectionTemplate = InspectionTemplate::load($templateId);
      
      if ($inspectionTemplate)
      {
         $selected = ($inspectionTemplate->templateId == $selectedTemplateId) ? "selected" : "";
         
         $options .= "<option value=\"$inspectionTemplate->templateId\" $selected>$inspectionTemplate->name</option>";
      }
   }
   
   return ($options);
}

function getCustomerPrintInput()
{
   global $ROOT;
   
   $customerPrintInput = "";
   
   $customerPrint = getJobInfo()->customerPrint;
   
   $disabled = getDisabled(JobInputField::CUSTOMER_PRINT);
   
   if ($customerPrint != "")
   {
      $customerPrintInput =
<<<HEREDOC
         <div class="flex-vertical flex-top">
            <a href="$ROOT/uploads/$customerPrint" style="margin-bottom: 10px;" target="_blank">$customerPrint</a>
            <input type="file" name="customerPrint" form="input-form" $disabled>
         </div>
HEREDOC;
   }
   else
   {
      $customerPrintInput =
<<<HEREDOC
         <input type="file" name="customerPrint" form="input-form" $disabled>
HEREDOC;
   }
   
   return ($customerPrintInput);
}

// ********************************** BEGIN ************************************

Time::init();

session_start();

if (!Authentication::isAuthenticated())
{
   header('Location: ../home.php');
   exit;
}

?>

<!DOCTYPE html>
<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">

   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons"/>
   
   <link rel="stylesheet" type="text/css" href="../common/theme.css"/>
   <link rel="stylesheet" type="text/css" href="../common/common2.css"/>
   
   <script src="../common/common.js"></script>
   <script src="../common/validate.js"></script>
   <script src="jobs.js"></script>

</head>

<body class="flex-vertical flex-top flex-left">
        
   <form id="input-form" action="" method="POST">
         <input type="hidden" name="jobId" value="<?php echo getJobInfo()->jobId; ?>">
         <input type="hidden" name="creator" value="<?php echo getJobInfo()->creator; ?>">
         <input type="hidden" name="dateTime" value="<?php echo getJobInfo()->dateTime; ?>">
         <input id="job-number-input" type="hidden" name="jobNumber" value="<?php echo getJobInfo()->partNumber; ?>">   
         <input id="part-number-input" type="hidden" name="partNumber" value="<?php echo getJobInfo()->partNumber; ?>">
   </form>

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(ACTIVITY); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading"><?php echo getHeading(); ?></div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <br>
         
         <div id="description" class="description"><?php echo getDescription(); ?></div>
         
         <br>
         
         <div class="flex-horizontal flex-left flex-wrap">
         
            <div class="flex-vertical flex-left" style="margin-right: 50px;">
            
               <div class="form-item">
                  <div class="form-label">Creator</div>
                  <input type="text" style="width:180px;" value="<?php echo getCreator(); ?>" <?php echo getDisabled(JobInputField::CREATOR); ?> />
               </div>
               <div class="form-item">
                  <div class="form-label">Date</div>
                  <input type="date" name="date" style="width:180px;" value="<?php echo getCreationDate() ?>" <?php echo getDisabled(JobInputField::DATE); ?> />
               </div>
               
            </div>
            
            <div class="flex-vertical flex-left">

               <div class="form-item">
                  <div class="form-label-long">Job #</div>
                  <div class="flex-horizontal flex-v-center flex-left">
                     <input id="job-number-prefix-input" type="text" name="jobNumberPrefix" form="input-form" style="width:150px;" value="<?php echo JobInfo::getJobPrefix(getJobInfo()->jobNumber); ?>" oninput="{this.validator.validate(); autoFillPartNumber();}" autocomplete="off" <?php echo getDisabled(JobInputField::JOB_NUMBER); ?> />
                     <div>&nbsp-&nbsp</div>
                     <input id="job-number-suffix-input" type="text" name="jobNumberSuffix" form="input-form" style="width:150px;" value="<?php echo JobInfo::getJobSuffix(getJobInfo()->jobNumber); ?>" oninput="{this.validator.validate(); autoFillJobNumber();}" autocomplete="off" <?php echo getDisabled(JobInputField::JOB_NUMBER); ?> />
                  </div>
               </div>
         
               <div class="form-item">
                  <div class="form-label-long">Part #</div>
                  <input id="part-number-display-input" type="text" style="width:150px;" value="<?php echo getJobInfo()->partNumber; ?>" <?php echo getDisabled(JobInputField::PART_NUMBER); ?> />
               </div>
         
               <div class="form-item">
                  <div class="form-label-long">Work center #</div>
                  <div><select id="work-center-input" name="wcNumber" form="input-form" <?php echo getDisabled(JobInputField::WC_NUMBER); ?>><?php echo getWcNumberOptions() ?></select></div>
               </div>
      
               <div class="form-item">
                  <div class="form-label-long">Sample weight</div>
                  <input id="sample-weight-input" type="number" name="sampleWeight" form="input-form" style="width:150px;" value="<?php echo getJobInfo()->sampleWeight; ?>" oninput="this.validator.validate();" <?php echo getDisabled(JobInputField::SAMPLE_WEIGHT); ?> />
               </div>
         
               <div class="form-item">
                  <div class="form-label-long">Cycle Time</div>
                  <input id="cycle-time-input" type="number" name="cycleTime" form="input-form" style="width:150px;" value="<?php echo getJobInfo()->cycleTime; ?>" oninput="this.validator.validate(); autoFillPartStats();" <?php echo getDisabled(JobInputField::CYCLE_TIME); ?> />
               </div>
         
               <div class="form-item">
                  <div class="form-label-long">Gross Pieces/Hour</div>
                  <input id="gross-parts-per-hour-input" type="number" style="width:150px;" <?php echo getDisabled(JobInputField::GROSS_PIECES); ?> />
               </div>
         
               <div class="form-item">
                  <div class="form-label-long">Net Percentage</div>
                  <input id="net-percentage-input" type="number" name="netPercentage" form="input-form" style="width:150px;" value="<?php echo getJobInfo()->netPercentage; ?>" oninput="this.validator.validate(); autoFillPartStats();" <?php echo getDisabled(JobInputField::NET_PERCENTAGE); ?> "/>
                  <div class="form-label">&nbsp%</div>
               </div>
         
               <div class="form-item">
                  <div class="form-label-long">Net Pieces/Hour</div>
                  <input id="net-parts-per-hour-input" type="number" style="width:150px;" <?php echo getDisabled(JobInputField::NET_PIECES); ?> />
               </div>
         
               <div class="form-item">
                  <div class="form-label-long">Job status</div>
                  <div><select id="status-input" name="status" form="input-form" <?php echo getDisabled(JobInputField::STATUS); ?>><?php echo getStatusOptions(); ?></select></div>
               </div>
      
               <div class="form-item">
                  <div class="form-label-long">In Process Template</div>
                  <div><select name="inProcessTemplateId" form="input-form" <?php echo getDisabled(JobInputField::IN_PROCESS_TEMPLATE); ?>><?php echo getInspectionTemplateOptions(InspectionType::IN_PROCESS, getJobInfo()->inProcessTemplateId); ?></select></div>
               </div>
      
               <div class="form-item">
                  <div class="form-label-long">Line Template</div>
                  <div><select name="lineTemplateId" form="input-form" <?php echo getDisabled(JobInputField::LINE_TEMPLATE); ?>><?php echo getInspectionTemplateOptions(InspectionType::LINE, getJobInfo()->lineTemplateId); ?></select></div>
               </div>
      
               <div class="form-item">
                  <div class="form-label-long">QCP Template</div>
                  <div><select name="qcpTemplateId" form="input-form" <?php echo getDisabled(JobInputField::QCP_TEMPLATE); ?>><?php echo getInspectionTemplateOptions(InspectionType::QCP, getJobInfo()->qcpTemplateId); ?></select></div>
               </div>
      
               <div class="form-item">
                  <div class="form-label-long">Customer print</div>
                  <?php echo getCustomerPrintInput() ?>
               </div>
         
            </div>
         
         </div>
         
         <br>
         
         <div class="flex-horizontal flex-h-center">
            <button id="cancel-button">Cancel</button>&nbsp;&nbsp;&nbsp;
            <button id="save-button" class="accent-button">Save</button>            
         </div>
      
      </div> <!-- content -->
     
   </div> <!-- main -->   
         
   <script>
   
      preserveSession();
      
      var jobNumberPrefixValidator = new PartNumberPrefixValidator("job-number-prefix-input", 5, 1, 9999, false);
      var jobNumberSuffixValidator = new PartNumberSuffixValidator("job-number-suffix-input", 3, 1, 99, false);
      var sampleWeightValidator = new DecimalValidator("sample-weight-input", 6, 0.001, 10, 3, false);         
      var cycleTimeValidator = new DecimalValidator("cycle-time-input", 5, 1, 60, 2, false);
      var netPercentageValidator = new DecimalValidator("net-percentage-input", 5, 0, 100, 2, false);

      jobNumberPrefixValidator.init();
      sampleWeightValidator.init();
      jobNumberSuffixValidator.init();
      cycleTimeValidator.init();
      netPercentageValidator.init();

      autoFillPartNumber();
      autoFillPartStats();

      // Setup event handling on all DOM elements.
      document.getElementById("cancel-button").onclick = function(){window.history.back();};
      document.getElementById("save-button").onclick = function(){onSaveJob();};      
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
      document.getElementById("menu-button").onclick = function(){document.getElementById("menu").classList.toggle('shown');};
            
   </script>

</body>

</html>
