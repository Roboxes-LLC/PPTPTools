<?php

require_once '../common/activity.php';
require_once '../common/database.php';
require_once '../common/header.php';
require_once '../common/isoInfo.php';
require_once '../common/jobInfo.php';
require_once '../common/menu.php';
require_once '../common/panTicket.php';
require_once '../common/params.php';
require_once '../common/partWasherEntry.php';
require_once '../common/timeCardInfo.php';
require_once '../common/userInfo.php';
require_once '../common/version.php';

const ACTIVITY = Activity::PART_WASH;

const ONLY_ACTIVE = true;

abstract class PartWasherLogInputField
{
   const FIRST = 0;
   const TIME_CARD_ID = PartWasherLogInputField::FIRST;
   const JOB_NUMBER = 1;
   const WC_NUMBER = 2;
   const MANUFACTURE_DATE = 3;
   const OPERATOR = 4;
   const WASH_DATE = 5;
   const WASHER = 6;
   const PAN_COUNT = 7;
   const PART_COUNT = 8;
   const LAST = 9;
   const COUNT = PartWasherLogInputField::LAST - PartWasherLogInputField::FIRST;
}

abstract class View
{
   const NEW_PART_WASHER_ENTRY = 0;
   const VIEW_PART_WASHER_ENTRY = 1;
   const EDIT_PART_WASHER_ENTRY = 2;
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
   $view = View::VIEW_PART_WASHER_ENTRY;
   
   if (getEntryId() == PartWasherEntry::UNKNOWN_ENTRY_ID)
   {
      $view = View::NEW_PART_WASHER_ENTRY;
   }
   else if (Authentication::checkPermissions(Permission::EDIT_PART_WASHER_LOG))
   {
      $view = View::EDIT_PART_WASHER_ENTRY;
   }
   
   return ($view);
}

function getPartWasherEntry()
{
   static $partWasherEntry = null;
   
   if ($partWasherEntry == null)
   {
      $params = Params::parse();
      
      if ($params->keyExists("entryId"))
      {
         $partWasherEntry = PartWasherEntry::load($params->get("entryId"));
      }
   }
   
   return ($partWasherEntry);
}

function getEntryId()
{
   $entryId = PartWasherEntry::UNKNOWN_ENTRY_ID;
   
   $partWasherEntry = getPartWasherEntry();
   
   if ($partWasherEntry)
   {
      $entryId = $partWasherEntry->partWasherEntryId;
   }
   
   return ($entryId);
}

function isEditable($field)
{
   $view = getView();
   
   // Start with the edit mode, as dictated by the view.
   $isEditable = (($view == View::NEW_PART_WASHER_ENTRY) ||
      ($view == View::EDIT_PART_WASHER_ENTRY));
   
   switch ($field)
   {
      case PartWasherLogInputField::JOB_NUMBER:
      case PartWasherLogInputField::OPERATOR:
      case PartWasherLogInputField::MANUFACTURE_DATE:
      {
         // Edit status disabled by time card ID.
         $isEditable &= (getTimeCardId() == TimeCardInfo::UNKNOWN_TIME_CARD_ID);
         break;
      }
         
      case PartWasherLogInputField::WC_NUMBER:
      {
         // Edit status determined by both time card ID and job number selection.
         $isEditable &= ((getTimeCardId() == TimeCardInfo::UNKNOWN_TIME_CARD_ID) &&
            (getJobNumber() != JobInfo::UNKNOWN_JOB_NUMBER));
         break;
      }
         
      case PartWasherLogInputField::WASH_DATE:
      {
         // Wash date is restricted to current date/time.
         $isEditable = false;
         break;
      }
         
      case PartWasherLogInputField::WASHER:
      {
         // Only administrative users can make an entry under another user's name.
         $userInfo = Authentication::getAuthenticatedUser();
         if ($userInfo)
         {
            $isEditable &= (($userInfo->roles == Role::SUPER_USER) ||
               ($userInfo->roles == Role::ADMIN));
         }
         break;
      }
         
      case PartWasherLogInputField::TIME_CARD_ID:
      case PartWasherLogInputField::PAN_COUNT:
      case PartWasherLogInputField::PART_COUNT:
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
   
   if ($view == View::NEW_PART_WASHER_ENTRY)
   {
      $heading = "Add to the Part Washer Log";
   }
   else if ($view == View::EDIT_PART_WASHER_ENTRY)
   {
      $heading = "Update the Part Washer Log";
   }
   else if ($view == View::VIEW_PART_WASHER_ENTRY)
   {
      $heading = "View a Part Washer Log Entry";
   }
   
   return ($heading);
}

function getDescription()
{
   $description = "";
   
   $view = getView();
   
   if ($view == View::NEW_PART_WASHER_ENTRY)
   {
      $description = "Create a new entry in the part washer log.  Starting with the time card ID is the fastest and most accurate way of entering the required job information, or simply enter the information manually if a time card is not available.";
   }
   else if ($view == View::EDIT_PART_WASHER_ENTRY)
   {
      $description = "You may revise any of the fields for this log entry and then select save when you're satisfied with the changes.";
   }
   else if ($view == View::VIEW_PART_WASHER_ENTRY)
   {
      $description = "View a previously saved log entry in detail.";
   }
   
   return ($description);
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

function getManufactureDate()
{
   $manufactureDate = null;
   
   $timeCardInfo = getTimeCardInfo();
   
   if ($timeCardInfo)
   {
      $dateTime = new DateTime($timeCardInfo->manufactureDate, new DateTimeZone('America/New_York'));
      $manufactureDate = $dateTime->format(Time::$javascriptDateFormat);
   }
   else
   {
      $partWasherEntry = getPartWasherEntry();
      
      if ($partWasherEntry)
      {
         $dateTime = new DateTime($partWasherEntry->manufactureDate, new DateTimeZone('America/New_York'));
         $manufactureDate = $dateTime->format(Time::$javascriptDateFormat);
      }
   }
   
   return ($manufactureDate);
}

function getWashDate()
{
   $washDate = Time::now(Time::$javascriptDateFormat);
   
   $partWasherEntry = getPartWasherEntry();
   
   if ($partWasherEntry)
   {
      $dateTime = new DateTime($partWasherEntry->dateTime, new DateTimeZone('America/New_York'));
      $washDate = $dateTime->format(Time::$javascriptDateFormat);
   }
   
   return ($washDate);
}

function getOperatorOptions()
{
   $options = "<option style=\"display:none\">";
   
   $operators = PPTPDatabase::getInstance()->getUsersByRole(Role::OPERATOR);
   
   // Create an array of employee numbers.
   $employeeNumbers = array();
   foreach ($operators as $operator)
   {
      $employeeNumbers[] = intval($operator["employeeNumber"]);
   }
   
   $selectedOperator = getOperator();
   
   // Add selected job number, if not already in the array.
   // Note: This handles the case of viewing an entry with an operator that is not assigned to the OPERATOR role.
   if (($selectedOperator != UserInfo::UNKNOWN_EMPLOYEE_NUMBER) &&
      (!in_array($selectedOperator, $employeeNumbers)))
   {
      $employeeNumbers[] = $selectedOperator;
      sort($employeeNumbers);
   }
   
   foreach ($employeeNumbers as $employeeNumber)
   {
      $userInfo = UserInfo::load($employeeNumber);
      if ($userInfo)
      {
         $selected = ($employeeNumber == $selectedOperator) ? "selected" : "";
         
         $name = $employeeNumber . " - " . $userInfo->getFullName();
         
         $options .= "<option value=\"$employeeNumber\" $selected>$name</option>";
      }
   }
   
   return ($options);
}

function getWasherOptions()
{
   $options = "<option style=\"display:none\">";
   
   $washers = PPTPDatabase::getInstance()->getUsersByRole(Role::PART_WASHER);
   
   // Create an array of employee numbers.
   $employeeNumbers = array();
   foreach ($washers as $washer)
   {
      $employeeNumbers[] = intval($washer["employeeNumber"]);
   }
   
   $selectedWasher = getWasher();
   
   // Add selected washer, if not already in the array.
   // Note: This handles the case of viewing an entry with a washer that is not assigned to the PART_WASHER role.
   if (($selectedWasher != UserInfo::UNKNOWN_EMPLOYEE_NUMBER) &&
      (!in_array($selectedWasher, $employeeNumbers)))
   {
      $employeeNumbers[] = $selectedWasher;
      sort($employeeNumbers);
   }
   
   foreach ($employeeNumbers as $employeeNumber)
   {
      $userInfo = UserInfo::load($employeeNumber);
      if ($userInfo)
      {
         $selected = ($employeeNumber == $selectedWasher) ? "selected" : "";
         
         $name = $employeeNumber . " - " . $userInfo->getFullName();
         
         $options .= "<option value=\"$employeeNumber\" $selected>$name</option>";
      }
   }
   
   return ($options);
}

function getTimeCardId()
{
   $timeCardId = TimeCardInfo::UNKNOWN_TIME_CARD_ID;
   
   $partWasherEntry = getPartWasherEntry();
   
   if ($partWasherEntry)
   {
      $timeCardId = $partWasherEntry->timeCardId;
   }
   else
   {
      $params = getParams();
      
      if ($params->keyExists("timeCardId"))
      {
         $timeCardId = $params->getInt("timeCardId");
      }
   }
   
   return ($timeCardId);
}

function getTimeCardInfo()
{
   $timeCardInfo = null;
   
   $timeCardId = getTimeCardId();
   
   if ($timeCardId != TimeCardInfo::UNKNOWN_TIME_CARD_ID)
   {
      $timeCardInfo = TimeCardInfo::load($timeCardId);
   }
   
   return ($timeCardInfo);
}

function getJobId()
{
   $jobId = JobInfo::UNKNOWN_JOB_ID;
   
   $timeCardInfo = getTimeCardInfo();
   
   if ($timeCardInfo)
   {
      $jobId = $timeCardInfo->jobId;
   }
   else
   {
      $partWasherEntry = getPartWasherEntry();
      
      if ($partWasherEntry)
      {
         $jobId = $partWasherEntry->jobId;
      }
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

function getOperator()
{
   $operator = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
   
   $timeCardInfo = getTimeCardInfo();
   
   if ($timeCardInfo)
   {
      $operator = $timeCardInfo->employeeNumber;
   }
   else
   {
      $partWasherEntry = getPartWasherEntry();
      
      if ($partWasherEntry)
      {
         $operator = $partWasherEntry->operator;
      }
   }
   
   return ($operator);
}

function getWasher()
{
   $washer = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
   
   $partWasherEntry = getPartWasherEntry();
   
   if ($partWasherEntry)
   {
      $washer = $partWasherEntry->employeeNumber;
   }
   else
   {
      $userInfo = Authentication::getAuthenticatedUser();
      
      if ($userInfo)
      {
         $washer = $userInfo->employeeNumber;
      }
   }
   
   return ($washer);
}

function getPanCount()
{
   $panCount = null;
   
   $partWasherEntry = getPartWasherEntry();
   
   if ($partWasherEntry)
   {
      $panCount = $partWasherEntry->panCount;
   }
   
   return ($panCount);
}

function getPartCount()
{
   $partCount = null;
   
   $partWasherEntry = getPartWasherEntry();
   
   if ($partWasherEntry)
   {
      $partCount = $partWasherEntry->partCount;
   }
   
   return ($partCount);
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
   <script src="partWasherLog.js<?php echo versionQuery();?>"></script>

</head>

<body class="flex-vertical flex-top flex-left">
        
   <form id="input-form" action="" method="POST">
      <!-- Hidden inputs make sure disabled fields below get posted. -->
      <input id="entry-id-input" type="hidden" name="entryId" value="<?php echo getEntryId(); ?>">
      <input type="hidden" name="washer" value="<?php echo getWasher(); ?>">
      <input type="hidden" name="washDate" value="<?php echo getWashDate(); ?>">
   </form>

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(ACTIVITY); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading-with-iso"><?php echo getHeading(); ?></div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div class="iso-number">ISO <?php echo IsoInfo::getIsoNumber(IsoDoc::PART_WASHER_LOG); ?></div>
         
         <div id="description" class="description"><?php echo getDescription(); ?></div>
         
         <br>
         
         <div class="flex-horizontal" style="justify-content: space-evenly">
         
            <div class="flex-vertical" style="margin-right: 20px;">
              
               <div class="form-section-header">Pan Ticket Entry</div>               
               <div class="form-item">
                  <div class="form-label">Pan Ticket #</div>
                  <input id="pan-ticket-code-input" type="text" style="width:50px;" name="panTicketCode" form="input-form" oninput="this.validator.validate(); onPanTicketCodeChange()" value="<?php $timeCardId = getTimeCardId(); echo ($timeCardId == 0) ? "" : PanTicket::getPanTicketCode($timeCardId);?>" <?php echo getDisabled(PartWasherLogInputField::TIME_CARD_ID); ?>>
                  &nbsp;&nbsp;
                  <button id="link-button" class="small-button" onclick="onLinkButton()"><i class="material-icons">link</i></button>               
               </div>               
            
               <div class="form-section-header">Manual Entry</div>
               <div class="form-item">
                  <div class="form-label">Job Number</div>
                  <select id="job-number-input" name="jobNumber" form="input-form" oninput="this.validator.validate(); onJobNumberChange();" <?php echo getDisabled(PartWasherLogInputField::JOB_NUMBER); ?>>
                     <?php echo getJobNumberOptions(); ?>
                  </select>
               </div>
               
               <div class="form-item">
                  <div class="form-label">Work Center</div>
                  <select id="wc-number-input" name="wcNumber" form="input-form" oninput="this.validator.validate();" <?php echo getDisabled(PartWasherLogInputField::WC_NUMBER); ?>>
                     <?php echo JobInfo::getWcNumberOptions(getJobNumber(), getWcNumber()); ?>
                  </select>
               </div>
               
               <div class="flex-horizontal">
                  <div class="form-item">
                     <div class="form-label">Manufacture Date</div>
                     <div class="flex-horizontal">
                        <input id="manufacture-date-input" type="date" name="manufactureDate" form="input-form" oninput="" value="<?php echo getManufactureDate(); ?>" <?php echo getDisabled(PartWasherLogInputField::MANUFACTURE_DATE); ?>>
                        &nbsp<button id="today-button" class="small-button" <?php echo getDisabled(PartWasherLogInputField::MANUFACTURE_DATE); ?>>Today</button>
                        &nbsp<button id="yesterday-button"  class="small-button" <?php echo getDisabled(PartWasherLogInputField::MANUFACTURE_DATE); ?>>Yesterday</button>
                     </div>
                  </div>
               </div>
               
               <div class="form-item">
                  <div class="form-label">Operator</div>
                  <select id="operator-input" name="operator" form="input-form" oninput="this.validator.validate();" <?php echo getDisabled(PartWasherLogInputField::OPERATOR); ?>>
                     <?php echo getOperatorOptions(); ?>
                  </select>
               </div>
               
            </div> <!-- column -->
            
            <div class="flex-vertical">
            
               <!--  Purely for display -->
               <div class="form-item">
                  <div class="form-label">Wash Date</div>
                  <input type="date" value="<?php echo getWashDate(); ?>" <?php echo getDisabled(PartWasherLogInputField::WASH_DATE); ?>>
               </div>
                           
               <div class="form-item">
                  <div class="form-label">Part Washer</div>
                  <select id="part-washer-input" name="washer" form="input-form" oninput="this.validator.validate();" <?php echo getDisabled(PartWasherLogInputField::WASHER); ?>>
                     <?php echo getWasherOptions(); ?>
                  </select>
               </div>
               
               <div class="form-item">
                  <div class="form-label">Basket Count</div>
                  <input id="pan-count-input" type="number" name="panCount" form="input-form" oninput="this.validator.validate();" value="<?php echo getPanCount(); ?>" <?php echo getDisabled(PartWasherLogInputField::PAN_COUNT); ?>>
               </div>
               
               <div class="form-item">
                  <div class="form-label">Part Count</div>
                  <input id="part-count-input" type="number" name="partCount" form="input-form" oninput="this.validator.validate();" value="<?php echo getPartCount(); ?>" <?php echo getDisabled(PartWasherLogInputField::PART_COUNT); ?>>
               </div>
               
            </div> <!-- column -->
         
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
      
      var panTicketCodeValidator = new HexValidator("pan-ticket-code-input", 4, 1, 65536, true);
      var jobNumberValidator = new SelectValidator("job-number-input");
      var wcNumberValidator = new SelectValidator("wc-number-input");
      var operatorValidator = new SelectValidator("operator-input");
      var partWasherValidator = new SelectValidator("part-washer-input");
      var panCountValidator = new IntValidator("pan-count-input", 2, 1, 40, false);
      var partCountValidator = new IntValidator("part-count-input", 6, 1, 100000, false);

      panTicketCodeValidator.init();
      jobNumberValidator.init();
      wcNumberValidator.init();
      operatorValidator.init();
      partWasherValidator.init();
      panCountValidator.init();
      partCountValidator.init();

      // Setup event handling on all DOM elements.
      document.getElementById("today-button").onclick = onTodayButton;
      document.getElementById("yesterday-button").onclick = onYesterdayButton;
      document.getElementById("cancel-button").onclick = function(){onCancel();};
      document.getElementById("save-button").onclick = function(){onSavePartWasherEntry();};      
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
      document.getElementById("menu-button").onclick = function(){document.getElementById("menu").classList.toggle('shown');};

      // Store the initial state of the form, for change detection.
      setInitialFormState("input-form");
      
      // Initial focus on pan ticket input, for bar code scanning.
      document.getElementById("pan-ticket-code-input").focus();
            
   </script>

</body>

</html>
