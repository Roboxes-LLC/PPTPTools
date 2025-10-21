<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once '../common/database.php';
require_once '../common/header.php';
require_once '../common/jobInfo.php';
require_once '../common/panTicket.php';
require_once '../common/params.php';
require_once '../common/partWeightEntry.php';
require_once '../common/timeCardInfo.php';
require_once '../common/userInfo.php';
require_once '../common/version.php';

const ONLY_ACTIVE = true;

abstract class PartWeightLogInputField
{
   const FIRST = 0;
   const TIME_CARD_ID = PartWeightLogInputField::FIRST;
   const JOB_NUMBER = 1;
   const WC_NUMBER = 2;
   const MANUFACTURE_DATE = 3;
   const OPERATOR = 4;
   const WEIGH_DATE = 5;
   const LABORER = 6;
   const PAN_COUNT = 7;
   const PART_WEIGHT = 8;
   const PAN_WEIGHT = 9;
   const PALLET_WEIGHT = 10;
   const PART_COUNT = 11;
   const SAMPLE_WEIGHT = 12;
   const LAST = 13;
   const COUNT = PartWeightLogInputField::LAST - PartWeightLogInputField::FIRST;
}

abstract class View
{
   const NEW_PART_WEIGHT_ENTRY = 0;
   const VIEW_PART_WEIGHT_ENTRY = 1;
   const EDIT_PART_WEIGHT_ENTRY = 2;
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
   $view = View::VIEW_PART_WEIGHT_ENTRY;
   
   if (getEntryId() == PartWeightEntry::UNKNOWN_ENTRY_ID)
   {
      $view = View::NEW_PART_WEIGHT_ENTRY;
   }
   else if (Authentication::checkPermissions(Permission::EDIT_PART_WEIGHT_LOG))
   {
      $view = View::EDIT_PART_WEIGHT_ENTRY;
   }
   
   return ($view);
}

function getPartWeightEntry()
{
   static $partWeightEntry = null;
   
   if ($partWeightEntry == null)
   {
      $params = Params::parse();
      
      if ($params->keyExists("entryId"))
      {
         $partWeightEntry = PartWeightEntry::load($params->get("entryId"));
      }
   }
   
   return ($partWeightEntry);
}

function getEntryId()
{
   $entryId = PartWeightEntry::UNKNOWN_ENTRY_ID;
   
   $partWeightEntry = getPartWeightEntry();
   
   if ($partWeightEntry)
   {
      $entryId = $partWeightEntry->partWeightEntryId;
   }
   
   return ($entryId);
}

function isEditable($field)
{
   $view = getView();
   
   // Start with the edit mode, as dictated by the view.
   $isEditable = (($view == View::NEW_PART_WEIGHT_ENTRY) ||
                  ($view == View::EDIT_PART_WEIGHT_ENTRY));
   
   switch ($field)
   {
      case PartWeightLogInputField::JOB_NUMBER:
      case PartWeightLogInputField::OPERATOR:
      case PartWeightLogInputField::MANUFACTURE_DATE:
      {
         // Edit status disabled by time card ID.
         $isEditable &= (getTimeCardId() == TimeCardInfo::UNKNOWN_TIME_CARD_ID);
         break;
      }
         
      case PartWeightLogInputField::WC_NUMBER:
      {
         // Edit status determined by both time card ID and job number selection.
         $isEditable &= ((getTimeCardId() == TimeCardInfo::UNKNOWN_TIME_CARD_ID) &&
            (getJobNumber() != JobInfo::UNKNOWN_JOB_NUMBER));
         break;
      }
         
      case PartWeightLogInputField::WEIGH_DATE:
      {
         // Weigh date is restricted to current date/time.
         // Note: Customer request to enable this field on 8/25/2025.
         //$isEditable = false;
         break;
      }
         
      case PartWeightLogInputField::LABORER:
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
         
      case PartWeightLogInputField::PAN_WEIGHT:
      case PartWeightLogInputField::PALLET_WEIGHT:
      case PartWeightLogInputField::PART_COUNT:
      case PartWeightLogInputField::SAMPLE_WEIGHT:
      {
         $isEditable = false;
      }
         
      case PartWeightLogInputField::TIME_CARD_ID:
      case PartWeightLogInputField::PART_WEIGHT:
      case PartWeightLogInputField::PAN_COUNT:
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
   
   if ($view == View::NEW_PART_WEIGHT_ENTRY)
   {
      $heading = "Add to the Part Weight Log";
   }
   else if ($view == View::EDIT_PART_WEIGHT_ENTRY)
   {
      $heading = "Update the Part Weight Log";
   }
   else if ($view == View::VIEW_PART_WEIGHT_ENTRY)
   {
      $heading = "View a Part Weight Log Entry";
   }
   
   return ($heading);
}

function getDescription()
{
   $description = "";
   
   $view = getView();
   
   if ($view == View::NEW_PART_WEIGHT_ENTRY)
   {
      $description = "Create a new entry in the part weight log.  Starting with the time card ID is the fastest and most accurate way of entering the required job information, or simply enter the information manually if a time card is not available.";
   }
   else if ($view == View::EDIT_PART_WEIGHT_ENTRY)
   {
      $description = "You may revise any of the fields for this log entry and then select save when you're satisfied with the changes.";
   }
   else if ($view == View::VIEW_PART_WEIGHT_ENTRY)
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
      $manufactureDate = Time::toJavascriptDate($timeCardInfo->manufactureDate);
   }
   else
   {
      $partWeightEntry = getPartWeightEntry();
      
      if ($partWeightEntry)
      {
         $manufactureDate = Time::toJavascriptDate($partWeightEntry->manufactureDate);
      }
   }
   
   return ($manufactureDate);
}

function getWeighDate()
{
   $weighDate = Time::toJavascriptDate(Time::now());
   
   $partWeightEntry = getPartWeightEntry();
   
   if ($partWeightEntry)
   {
      $weighDate = Time::toJavascriptDate($partWeightEntry->dateTime);
   }
   
   return ($weighDate);
}

function getOperatorOptions()
{
   return (UserManager::getOptions([Role::OPERATOR], [], getOperator()));
}

function getLaborerOptions()
{
   return (UserManager::getOptions([Role::LABORER], [], getOperator()));
}

function getTimeCardId()
{
   $timeCardId = TimeCardInfo::UNKNOWN_TIME_CARD_ID;
   
   $partWeightEntry = getPartWeightEntry();
   
   if ($partWeightEntry)
   {
      $timeCardId = $partWeightEntry->timeCardId;
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
      $partWeightEntry = getPartWeightEntry();
      
      if ($partWeightEntry)
      {
         $jobId = $partWeightEntry->jobId;
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

function getSampleWeight()
{
   $sampleWeight = 0.0;
   
   $jobId = getJobId();
   
   $jobInfo = JobInfo::load($jobId);
   
   if ($jobInfo)
   {
      $sampleWeight = $jobInfo->part->sampleWeight;
   }
   
   return ($sampleWeight);
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
      $partWeightEntry = getPartWeightEntry();
      
      if ($partWeightEntry)
      {
         $operator = $partWeightEntry->operator;
      }
   }
   
   return ($operator);
}

function getLaborer()
{
   $laborer = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
   
   $partWeightEntry = getPartWeightEntry();
   
   if ($partWeightEntry)
   {
      $laborer = $partWeightEntry->employeeNumber;
   }
   else
   {
      $userInfo = Authentication::getAuthenticatedUser();
      
      if ($userInfo)
      {
         $laborer = $userInfo->employeeNumber;
      }
   }
   
   return ($laborer);
}

function getPanCount()
{
   $panCount = 0;
   
   $partWeightEntry = getPartWeightEntry();
   
   if ($partWeightEntry)
   {
      $panCount = $partWeightEntry->panCount;
   }
   
   return ($panCount);
}

function getPartWeight()
{
   $partWeight = 0;
   
   $partWeightEntry = getPartWeightEntry();
   
   if ($partWeightEntry)
   {
      $partWeight = $partWeightEntry->weight;
   }
   
   return ($partWeight);
}

function getPanWeight()
{
   $panWeight = PartWeightEntry::STANDARD_PAN_WEIGHT;
   
   $partWeightEntry = getPartWeightEntry();
   
   if ($partWeightEntry)
   {
      $panWeight = $partWeightEntry->panWeight;
   }
   
   return ($panWeight);
}

function getPalletWeight()
{
   $palletWeight = PartWeightEntry::STANDARD_PALLET_WEIGHT;
   
   $partWeightEntry = getPartWeightEntry();
   
   if ($partWeightEntry)
   {
      $palletWeight = $partWeightEntry->palletWeight;
   }
   
   return ($palletWeight);
}

function getCalculatedPartCount()
{
   $partCount = "";
   
   $partWeightEntry = getPartWeightEntry();
   
   if ($partWeightEntry)
   {
      $partCount = $partWeightEntry->calculatePartCount();
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
   
   <script src="/common/common.js<?php echo versionQuery();?>"></script>
   <script src="/common/validate.js<?php echo versionQuery();?>"></script>
   <script src="/script/common/common.js<?php echo versionQuery();?>"></script>  
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script>  
   <script src="partWeightLog.js<?php echo versionQuery();?>"></script>

</head>

<body class="flex-vertical flex-top flex-left">
        
   <form id="input-form" action="" method="POST">
      <!-- Hidden inputs make sure disabled fields below get posted. -->
         <input id="entry-id-input" type="hidden" name="entryId" value="<?php echo getEntryId(); ?>">
         <input type="hidden" name="laborer" value="<?php echo getLaborer(); ?>">
         <input type="hidden" name="weighDate" value="<?php echo getWeighDate(); ?>">
   </form>

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading-with-iso"><?php echo getHeading(); ?></div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div class="iso-number">ISO <?php echo IsoInfo::getIsoNumber(IsoDoc::PART_WEIGHT_LOG); ?></div>
         
         <div id="description" class="description"><?php echo getDescription(); ?></div>
         
         <br>
         
         <div class="flex-horizontal" style="justify-content: space-evenly">
         
            <div class="flex-vertical" style="margin-right: 20px;">
              
               <div class="form-section-header">Pan Ticket Entry</div>               
               <div class="form-item">
                  <div class="form-label">Pan Ticket #</div>
                  <input id="pan-ticket-code-input" type="text" style="width:50px;" name="panTicketCode" form="input-form" oninput="this.validator.validate(); onPanTicketCodeChange();" value="<?php $timeCardId = getTimeCardId(); echo ($timeCardId == 0) ? "" : PanTicket::getPanTicketCode($timeCardId);?>" <?php echo getDisabled(PartWeightLogInputField::TIME_CARD_ID); ?>>
                  &nbsp;&nbsp;
                  <button id="link-button" class="small-button" onclick="onLinkButton()"><i class="material-icons">link</i></button>
               </div>               
            
               <div class="form-section-header">Manual Entry</div>
               <div class="form-item">
                  <div class="form-label">Job Number</div>
                  <select id="job-number-input" name="jobNumber" form="input-form" oninput="this.validator.validate(); onJobNumberChange();" <?php echo getDisabled(PartWeightLogInputField::JOB_NUMBER); ?>>
                     <?php echo getJobNumberOptions(); ?>
                  </select>
               </div>
               
               <div class="form-item">
                  <div class="form-label">Work Center</div>
                  <select id="wc-number-input" name="wcNumber" form="input-form" oninput="this.validator.validate(); onWcNumberChange();" <?php echo getDisabled(PartWeightLogInputField::WC_NUMBER); ?>>
                     <?php echo JobInfo::getWcNumberOptions(getJobNumber(), getWcNumber()); ?>
                  </select>
               </div>
               
               <div class="flex-horizontal">
                  <div class="form-item">
                     <div class="form-label">Manufacture Date</div>
                     <div class="flex-horizontal">
                        <input id="manufacture-date-input" type="date" name="manufactureDate" form="input-form" value="<?php echo getManufactureDate(); ?>" <?php echo getDisabled(PartWeightLogInputField::MANUFACTURE_DATE); ?>>
                        &nbsp<button id="today-button" class="small-button" <?php echo getDisabled(PartWeightLogInputField::MANUFACTURE_DATE); ?>>Today</button>
                        &nbsp<button id="yesterday-button" class="small-button" <?php echo getDisabled(PartWeightLogInputField::MANUFACTURE_DATE); ?>>Yesterday</button>
                     </div>
                  </div>
               </div>
               
               <div class="form-item">
                  <div class="form-label">Operator</div>
                  <select id="operator-input" name="operator" form="input-form" oninput="this.validator.validate();" <?php echo getDisabled(PartWeightLogInputField::OPERATOR); ?>>
                     <?php echo getOperatorOptions(); ?>
                  </select>
               </div>
               
            </div> <!-- column -->
            
            <div class="flex-vertical">
            
               <div class="form-item">
                  <div class="form-label">Weigh Date</div>
                  <input type="date" name="weighDate" form="input-form" value="<?php echo getWeighDate(); ?>" <?php echo getDisabled(PartWeightLogInputField::WEIGH_DATE); ?>>
               </div>
               
               <div class="form-item">
                  <div class="form-label">Laborer</div>
                  <select id="laborer-input" name="laborer" form="input-form" oninput="this.validator.validate();" <?php echo getDisabled(PartWeightLogInputField::LABORER); ?>>
                     <?php echo getLaborerOptions(); ?>
                  </select>
               </div>               
                              
               <div class="form-item">
                  <div class="form-label">Basket Count</div>
                  <input id="pan-count-input" type="number" name="panCount" form="input-form" oninput="this.validator.validate(); updateCalculatedPartCount();" value="<?php echo getPanCount(); ?>" <?php echo getDisabled(PartWeightLogInputField::PAN_COUNT); ?>>
               </div>
                           
               <div class="form-item">
                  <div class="form-label">Part Weight</div>
                  <input id="part-weight-input" type="number" name="partWeight" form="input-form" oninput="this.validator.validate(); updateCalculatedPartCount();" value="<?php echo getPartWeight(); ?>" <?php echo getDisabled(PartWeightLogInputField::PART_WEIGHT); ?>>&nbsp;lbs
               </div>
               
               <div class="form-item">
                  <div class="form-label">Pan Weight</div>
                  <input id="pan-weight-input" type="number" style="width:50px;" name="panWeight" form="input-form" value="<?php echo getPanWeight(); ?>" <?php echo getDisabled(PartWeightLogInputField::PAN_WEIGHT); ?>>&nbsp;lbs
               </div>
               
               <div class="form-item">
                  <div class="form-label">Pallet Weight</div>
                  <input id="pallet-weight-input" type="number" style="width:50px;" name="palletWeight" form="input-form" value="<?php echo getPalletWeight(); ?>" <?php echo getDisabled(PartWeightLogInputField::PALLET_WEIGHT); ?>>&nbsp;lbs
               </div>
               
               <div class="form-item">
                  <div class="form-label">Sample Weight</div>
                  <input id="sample-weight-input" type="number" style="width:75px;" value="<?php echo getSampleWeight() ?>" step="0.0001" <?php echo getDisabled(PartWeightLogInputField::SAMPLE_WEIGHT); ?>>&nbsp;lbs
               </div>
               
               <div class="form-item">
                  <div class="form-label">Estimated Part Count</div>
                  <input id="part-count-input" type="number" style="width:100px;" value="<?php echo getCalculatedPartCount(); ?>" <?php echo getDisabled(PartWeightLogInputField::PART_COUNT); ?>>
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
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::PART_WEIGHT ?>); 
      
      preserveSession();
      
      var panTicketCodeValidator = new HexValidator("pan-ticket-code-input", 4, 1, 65536, true);
      var jobNumberValidator = new SelectValidator("job-number-input");
      var wcNumberValidator = new SelectValidator("wc-number-input");
      var operatorValidator = new SelectValidator("operator-input");
      var laborerValidator = new SelectValidator("laborer-input");
      var panCountValidator = new IntValidator("pan-count-input", 2, 1, 40, false);
      var partWeightValidator = new DecimalValidator("part-weight-input", 7, 1, 10000, 2, false);

      panTicketCodeValidator.init();
      jobNumberValidator.init();
      wcNumberValidator.init();
      operatorValidator.init();
      laborerValidator.init();
      panCountValidator.init();
      partWeightValidator.init();

      var sampleWeight = <?php echo getSampleWeight(); ?>;
      
      // Setup event handling on all DOM elements.
      document.getElementById("today-button").onclick = onTodayButton;
      document.getElementById("yesterday-button").onclick = onYesterdayButton;
      document.getElementById("cancel-button").onclick = function(){onCancel();};
      document.getElementById("save-button").onclick = function(){onSavePartWeightEntry();};      
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};

      // Store the initial state of the form, for change detection.
      setInitialFormState("input-form");
      
      // Initial focus on pan ticket input, for bar code scanning.
      document.getElementById("pan-ticket-code-input").focus();
            
   </script>

</body>

</html>
