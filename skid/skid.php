<?php

if (!defined('ROOT')) require_once '../root.php';
require_once '../common/activity.php';
require_once '../common/header.php';
require_once '../common/jobInfo.php';
require_once '../common/menu.php';
require_once '../common/params.php';
require_once '../common/userInfo.php';
require_once '../common/version.php';
require_once ROOT.'/core/component/skid.php';

const ACTIVITY = Activity::SKID;

const ONLY_ACTIVE = true;

abstract class SkidInputField
{
   const FIRST = 0;
   const SKID_TICKET_CODE = SkidInputField::FIRST;
   const CREATION_DATE = 1;
   const AUTHOR = 2;
   const JOB_NUMBER = 3;
   const WC_NUMBER = 4;
   const LAST = 3;
   const COUNT = SkidInputField::LAST - SkidInputField::FIRST;
}

abstract class View
{
   const NEW_SKID = 0;
   const VIEW_SKID = 1;
   const EDIT_SKID = 2;
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
   $view = View::VIEW_SKID;
   
   if (getSkidId() == Skid::UNKNOWN_SKID_ID)
   {
      $view = View::NEW_SKID;
   }
   else if (Authentication::checkPermissions(Permission::EDIT_SKID))
   {
      $view = View::EDIT_SKID;
   }
   
   return ($view);
}

function getSkid()
{
   static $skid = null;
   
   if ($skid == null)
   {
      $params = Params::parse();
      
      if ($params->keyExists("skidId"))
      {
         $skid = Skid::load($params->getInt("skidId"));
      }
   }
   
   return ($skid);
}

function getSkidId()
{
   $skidId = Skid::UNKNOWN_SKID_ID;
   
   $skid = getSkid();
   
   if ($skid)
   {
      $skidId = $skid->skidId;
   }
   
   return ($skidId);
}

function isEditable($field)
{
   $view = getView();
   
   // Start with the edit mode, as dictated by the view.
   $isEditable = (($view == View::NEW_SKID) ||
                  ($view == View::EDIT_SKID));
   
   switch ($field)
   {
      case SkidInputField::SKID_TICKET_CODE:
      case SkidInputField::AUTHOR:
      case SkidInputField::CREATION_DATE:
      {
         // Always view-only.
         $isEditable = false;
         break;
      }
         
      case SkidInputField::JOB_NUMBER:
      case SkidInputField::WC_NUMBER:
      {
         // Job and WC numbers are only editable on new skids.
         $isEditable = ($view == View::NEW_SKID);
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

function getHeading()
{
   $heading = "";
   
   $view = getView();
   
   if ($view == View::NEW_SKID)
   {
      $heading = "Add a new skid";
   }
   else if ($view == View::EDIT_SKID)
   {
      $heading = "Update a skid";
   }
   else if ($view == View::VIEW_SKID)
   {
      $heading = "View a skid";
   }
   
   return ($heading);
}

function getDescription()
{
   $description = "";
   
   $view = getView();
   
   if ($view == View::NEW_SKID)
   {
      $description = "Create a new skid for tracking parts through the production process.";
   }
   else if ($view == View::EDIT_SKID)
   {
      $description = "View the details of a skid and update its location in the production process.";
   }
   else if ($view == View::VIEW_SKID)
   {
      $description = "View the details of a skid and its location in the production process.";
   }
   
   return ($description);
}

function getSkidTicketCode()
{
   $skidTicketCode = null;
   
   $skid = getSkid();
   
   if ($skid)
   {
      $skidTicketCode = $skid->getSkidTicketCode();
   }
   
   return ($skidTicketCode);
}

function getJobNumberOptions()
{
   $options = "<option style=\"display:none\">";
   
   $jobNumbers = JobInfo::getJobNumbers(ONLY_ACTIVE);
   
   $selectedJobNumber = getJobNumber();
   
   // Add selected job number, if not already in the array.
   // Note: This handles the case of viewing an entry that references a non-active job.
   if (($selectedJobNumber != "") &&
      (!in_array($selectedJobNumber, $jobNumbers)))
   {
      $jobNumbers[] = $selectedJobNumber;
      sort($jobNumbers);
   }
   
   foreach ($jobNumbers as $jobNumber)
   {
      $selected = ($jobNumber == $selectedJobNumber) ? "selected" : "";
      
      $options .= "<option value=\"{$jobNumber}\" $selected>{$jobNumber}</option>";
   }
   
   return ($options);
}

function getAuthor()
{
   $author = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
   
   $skid = getSkid();
   
   if ($skid)
   {
      $createdAction = $skid->getCreatedAction();
      if ($createdAction)
      {
         $author = $createdAction->author;
      }
   }
   
   return ($author);
}

function getAuthorName()
{
   $authorName = "";
   
   $employeeNumber = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
   
   if (getView() == View::NEW_SKID)
   {
      $employeeNumber = Authentication::getAuthenticatedUser()->employeeNumber;
   }
   else
   {
      $employeeNumber = getAuthor();
   }
   
   if ($employeeNumber != UserInfo::UNKNOWN_EMPLOYEE_NUMBER)
   {
      $userInfo = UserInfo::load($employeeNumber);
      if ($userInfo)
      {
         $authorName = $userInfo->employeeNumber . " - " . $userInfo->getFullName();
      }
   }
   
   return ($authorName);
}

function getCreationDate()
{
   $creationDate = Time::now(Time::$javascriptDateFormat);
   
   $skid = getSkid();
   
   if ($skid)
   {
      $createdAction = $skid->getCreatedAction();
      if ($createdAction)
      {
         $dateTime = new DateTime($createdAction->dateTime, new DateTimeZone('America/New_York'));
         $creationDate = $dateTime->format(Time::$javascriptDateFormat);
      }
   }
   
   return ($creationDate);
}

function getJobId()
{
   $jobId = JobInfo::UNKNOWN_JOB_ID;
   
   $skid = getSkid();
   
   if ($skid)
   {
      $jobId = $skid->jobId;
   }
   
   return ($jobId);
}

function getJobNumber()
{
   $jobNumber = JobInfo::UNKNOWN_JOB_NUMBER;
   
   $jobId = getJobId();
   
   $jobInfo = JobInfo::load($jobId);
   
   if ($jobInfo)
   {
      $jobNumber = $jobInfo->jobNumber;
   }
   
   return ($jobNumber);
}

function getWcNumber()
{
   $wcNumber = JobInfo::UNKNOWN_WC_NUMBER;
   
   $jobId = getJobId();
   
   $jobInfo = JobInfo::load($jobId);
   
   if ($jobInfo)
   {
      $wcNumber = $jobInfo->wcNumber;
   }
   
   return ($wcNumber);
}

// ********************************** BEGIN ************************************

Time::init();

session_start();

if (!Authentication::isAuthenticated())
{
   header('Location: ../login.php');
   exit;
}

?>

<!DOCTYPE html>
<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">

   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons"/>
   
   <link rel="stylesheet" type="text/css" href="../common/theme.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="../common/common.css<?php echo versionQuery();?>"/>
   
   <script src="../common/common.js<?php echo versionQuery();?>"></script>
   <script src="../common/validate.js<?php echo versionQuery();?>"></script>
   <script src="../script/pages/skid.js <?php echo versionQuery();?>"></script>

</head>

<body class="flex-vertical flex-top flex-left">
        
   <form id="input-form" action="" method="POST">
      <input id="skid-id-input" type="hidden" name="skidId" value="<?php echo getSkidId(); ?>">
      <input id="action-input" type="hidden" name="request" value="generate_skid"/>
   </form>

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(ACTIVITY); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading-with-iso"><?php echo getHeading(); ?></div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div id="description" class="description"><?php echo getDescription(); ?></div>
         
         <br>
         
         <div class="flex-horizontal" style="justify-content: space-evenly">
         
            <div class="flex-vertical" style="margin-right: 20px;">
                         
               <div class="form-item">
                  <div class="form-label">Skid Ticket #</div>
                  <input id="skid-ticket-code-input" type="text" style="width:50px;" name="skidTicketCode" form="input-form" value="<?php echo getSkidTicketCode() ?>" <?php echo getDisabled(SkidInputField::SKID_TICKET_CODE) ?>>
               </div>  
               
               <div class="flex-horizontal">
                  <div class="form-item">
                     <div class="form-label">Creation Date</div>
                     <div class="flex-horizontal">
                        <input id="creation-date-input" type="date" name="creationDate" form="input-form" oninput="" value="<?php echo getCreationDate(); ?>" <?php echo getDisabled(SkidInputField::CREATION_DATE); ?>>
                     </div>
                  </div>
               </div>
               
               <div class="form-item">
                  <div class="form-label">Author</div>
                  <input id="author-input" type="text" value="<?php echo getAuthorName() ?>" <?php echo getDisabled(SkidInputField::AUTHOR); ?>>
               </div>
            
               <div class="form-item">
                  <div class="form-label">Job Number</div>
                  <select id="job-number-input" name="jobNumber" form="input-form" <?php echo getDisabled(SkidInputField::JOB_NUMBER); ?>>
                     <?php echo getJobNumberOptions(); ?>
                  </select>
               </div>
               
               <div class="form-item">
                  <div class="form-label">Work Center</div>
                  <select id="wc-number-input" name="wcNumber" form="input-form" oninput="this.validator.validate();" <?php echo getDisabled(SkidInputField::WC_NUMBER); ?>>
                     <?php echo JobInfo::getWcNumberOptions(getJobNumber(), getWcNumber()); ?>
                  </select>
               </div>
                              
            </div> <!-- column -->
            
            <div class="flex-vertical">
               
            </div> <!-- column -->
         
         </div>
         
         <br>
         
         <div class="flex-horizontal flex-h-center">
            <button id="cancel-button">Cancel</button>&nbsp;&nbsp;&nbsp;
            <button id="save-button" class="accent-button" <?php echo (getView() == View::NEW_SKID) ? "" : "hidden" ?>>Save</button>            
         </div>
      
      </div> <!-- content -->
     
   </div> <!-- main -->   
         
   <script>
   
      preserveSession();
      
      var PAGE = new Skid();
      
      var jobNumberValidator = new SelectValidator("job-number-input");
      var wcNumberValidator = new SelectValidator("wc-number-input");

      jobNumberValidator.init();
      wcNumberValidator.init();

      // Setup event handling on all DOM elements.
      document.getElementById("cancel-button").onclick = function(){PAGE.onCancelButton();};
      document.getElementById("save-button").onclick = function(){PAGE.onSaveButton();};      
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
      document.getElementById("menu-button").onclick = function(){document.getElementById("menu").classList.toggle('shown');};

      // Store the initial state of the form, for change detection.
      setInitialFormState("input-form");
            
   </script>

</body>

</html>
