<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once '../common/commentCodes.php';
require_once '../common/header.php';
require_once '../common/isoInfo.php';
require_once '../common/jobInfo.php';
require_once '../common/params.php';
require_once '../common/timeCardInfo.php';
require_once '../common/userInfo.php';
require_once '../common/version.php';

const ONLY_ACTIVE = true;

abstract class TimeCardInputField
{
   const FIRST = 0;
   const ENTRY_DATE = TimeCardInputField::FIRST;   
   const MANUFACTURE_DATE = 1;
   const OPERATOR = 2;
   const JOB_NUMBER = 3;
   const WC_NUMBER = 4;
   const MATERIAL_NUMBER = 5;
   const SHIFT_TIME = 6;   
   const RUN_TIME = 7;
   const SETUP_TIME = 8;
   const PAN_COUNT = 9;
   const PART_COUNT = 10;
   const SCRAP_COUNT = 11;
   const COMMENTS = 12;
   const LAST = 13;
   const COUNT = TimeCardInputField::LAST - TimeCardInputField::FIRST;
}

abstract class View
{
   const NEW_TIME_CARD = 0;
   const VIEW_TIME_CARD = 1;
   const EDIT_TIME_CARD = 2;
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
   $view = View::VIEW_TIME_CARD;
   
   if (getTimeCardId() == TimeCardInfo::UNKNOWN_TIME_CARD_ID)
   {
      $view = View::NEW_TIME_CARD;
   }
   else if (Authentication::checkPermissions(Permission::EDIT_TIME_CARD))
   {
      $view = View::EDIT_TIME_CARD;
   }
   
   return ($view);
}

function isEditable($field)
{
   $view = getView();
   
   // Start with the edit mode, as dictated by the view.
   $isEditable = (($view == View::NEW_TIME_CARD) ||
                  ($view == View::EDIT_TIME_CARD));
   
   switch ($field)
   {
      case TimeCardInputField::ENTRY_DATE:
      {
         $isEditable = false;
         break;
      }
      
      case TimeCardInputField::OPERATOR:
      case TimeCardInputField::MANUFACTURE_DATE:
      {
         $isEditable = ((Authentication::getAuthenticatedUser()->roles == Role::ADMIN) ||
                        (Authentication::getAuthenticatedUser()->roles == Role::SUPER_USER));
         break;
      }
         
      case TimeCardInputField::WC_NUMBER:
      {
         // Edit status determined by job number selection.
         $isEditable &= (getJobNumber() != JobInfo::UNKNOWN_JOB_NUMBER);
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

function getTimeCardId()
{
   $timeCardId = TimeCardInfo::UNKNOWN_TIME_CARD_ID;
   
   $params = getParams();
   
   if ($params->keyExists("timeCardId"))
   {
      $timeCardId = $params->getInt("timeCardId");
   }
   
   return ($timeCardId);
}

function getTimeCardInfo()
{
   static $timeCardInfo = null;
   
   if ($timeCardInfo == null)
   {
      $params = Params::parse();
      
      if ($params->keyExists("timeCardId"))
      {
         $timeCardInfo = TimeCardInfo::load($params->get("timeCardId"));
      }
      else
      {
         $timeCardInfo = new TimeCardInfo();
         
         $timeCardInfo->employeeNumber = Authentication::getAuthenticatedUser()->employeeNumber;
         $timeCardInfo->shiftTime = TimeCardInfo::DEFAULT_SHIFT_TIME;
         $timeCardInfo->runTime = TimeCardInfo::DEFAULT_RUN_TIME;
      }
   }
   
   return ($timeCardInfo);
}

function getOperator()
{
   $operator = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
   
   $timeCardInfo = getTimeCardInfo();
   
   if ($timeCardInfo)
   {
      $operator = $timeCardInfo->employeeNumber;
   }
   
   return ($operator);
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
   
   // Add selected operator, if not already in the array.
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

function getCommentCodesDiv()
{
   $timeCardInfo = getTimeCardInfo();
   
   $disabled = !isEditable(TimeCardInputField::COMMENTS);
   
   $commentCodes = CommentCode::getCommentCodes();
   
   $leftColumn = "";
   $rightColumn = "";
   $index = 0;
   foreach ($commentCodes as $commentCode)
   {
      $id = "code-" . $commentCode->code . "-input";
      $name = "code-" . $commentCode->code;
      $checked = ($timeCardInfo->hasCommentCode($commentCode->code) ? "checked" : "");
      $description = $commentCode->description;
      
      $codeDiv =
<<< HEREDOC
      <div class="form-item">
         <input id="$id" type="checkbox" class="comment-checkbox" form="input-form" name="$name" $checked $disabled/>
         &nbsp;
         <label for="$id" class="form-input-medium">$description</label>
      </div>
HEREDOC;
      
      if (($index % 2) == 0)
      {
         $leftColumn .= $codeDiv;
      }
      else
      {
         $rightColumn .= $codeDiv;
      }
      
      $index++;
   }
   
   $html =
<<<HEREDOC
   <input type="hidden" form="input-form" name="commentCodes" value="true"/>
   <div class="form-col">
      <div class="form-section-header">Codes</div>
      <div class="form-row">
         <div class="form-col" style="margin-right: 10px;">
            $leftColumn
         </div>
         <div class="form-col">
            $rightColumn
         </div>
      </div>
   </div>
HEREDOC;
   
   return ($html);
}

function canApprove()
{
   return (Authentication::checkPermissions(Permission::APPROVE_TIME_CARDS));
}

function getApprovalButton($buttonId, $function)
{
   $html = "";
   
   $approvingUser = Authentication::getAuthenticatedUser();
      
   $html =
<<<HEREDOC
   <button id="$buttonId" class="small-button accent-button" onclick="$function($approvingUser->employeeNumber)">Approve</button>
HEREDOC;
      
   return ($html);
}

function getUnapprovalButton($buttonId, $function)
{
   $html = "";
   
   $approvingUser = Authentication::getAuthenticatedUser();
      
   $html =
<<<HEREDOC
   <button id="$buttonId" class="small-button accent-button" onclick="$function($approvingUser->employeeNumber)">Unapprove</button>
HEREDOC;
   
   return ($html);
}

function getRunTimeApprovalText()
{
   $approvalText = "";
   
   $timeCardInfo = getTimeCardInfo();
   
   if (($timeCardInfo->requiresRunTimeApproval()) &&
         ($timeCardInfo->isRunTimeApproved()))
   {
      $approvalText = "Approved by supervisor";
      
      $userInfo = UserInfo::load($timeCardInfo->runTimeApprovedBy);
      
      if ($userInfo)
      {
         $approvalText = "Approved by " . $userInfo->getFullName();
      }
   }
}

function getSetupTimeApprovalText()
{
   $approvalText = "";
   
   $timeCardInfo = getTimeCardInfo();
   
   if (($timeCardInfo->requiresSetupTimeApproval()) &&
       ($timeCardInfo->isSetupTimeApproved()))
   {
      $approvalText = "Approved by supervisor";

      $userInfo = UserInfo::load($timeCardInfo->setupTimeApprovedBy);
      
      if ($userInfo)
      {
         $approvalText = "Approved by " . $userInfo->getFullName();
      }
   }
}

function getJobNumber()
{
   $jobNumber = JobInfo::UNKNOWN_JOB_NUMBER;
   
   $timeCardInfo = getTimeCardInfo();
   
   if ($timeCardInfo)
   {
      $jobId = $timeCardInfo->jobId;
      
      $jobInfo = JobInfo::load($jobId);
      
      if ($jobInfo)
      {
         $jobNumber = $jobInfo->jobNumber;
      }
   }
   
   return ($jobNumber);
}

function getWcNumber()
{
   $wcNumber = 0;
   
   $timeCardInfo = getTimeCardInfo();
   
   if ($timeCardInfo)
   {
      $jobId = $timeCardInfo->jobId;
      
      $jobInfo = JobInfo::load($jobId);
      
      if ($jobInfo)
      {
         $wcNumber = $jobInfo->wcNumber;
      }
   }
   
   return ($wcNumber);
}

function getMaterialNumber()
{
   $materialNumber = null;
   
   $timeCardInfo = getTimeCardInfo();
   
   if ($timeCardInfo && 
       ($timeCardInfo->materialNumber != MaterialHeatInfo::UNKNOWN_INTERNAL_HEAT_NUMBER))
   {
      $materialNumber = $timeCardInfo->materialNumber;
   }
   
   return ($materialNumber);
}

function getGrossPartsPerHour()
{
   $grossPartsPerHour = 0;
   
   $timeCardInfo = getTimeCardInfo();
   
   if ($timeCardInfo)
   {
      $jobId = $timeCardInfo->jobId;
      
      $jobInfo = JobInfo::load($jobId);
      
      if ($jobInfo)
      {
         $grossPartsPerHour = $jobInfo->grossPartsPerHour;
      }
   }
   
   return ($grossPartsPerHour);
}

function getHeading()
{
   $heading = "";
   
   switch (getView())
   {
      case View::NEW_TIME_CARD:
      {
         $heading = "Create a New Time Card";
         break;
      }
      
      case View::EDIT_TIME_CARD:
      {
         $heading = "Update a Time Card";
         break;
      }
      
      case View::VIEW_TIME_CARD:
      default:
      {
         $heading = "View a Time Card";
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
      case View::NEW_TIME_CARD:
      {
         $description = "Enter all required fields for your time card.  Once you're satisfied, click Save below to add this time card to the system.";
         break;
      }
         
      case View::EDIT_TIME_CARD:
      {
         $description = "You may revise any of the fields for this time card and then select save when you're satisfied with the changes.";
         break;
      }
         
      case View::VIEW_TIME_CARD:
      default:
      {
         $description = "View a previously saved time card in detail.";
         break;
      }
   }
   
   return ($description);
}

function getEntryDateTime()
{
   $dateTime = new DateTime();  // now
   
   if (getView() != View::NEW_TIME_CARD)
   {
      $timeCardInfo = getTimeCardInfo();
      
      if ($timeCardInfo)
      {
         $dateTime = new DateTime($timeCardInfo->dateTime, new DateTimeZone('America/New_York'));
      }
   }

   $entryDate = $dateTime->format(Time::$javascriptDateFormat) . "T" . $dateTime->format(Time::$javascriptTimeFormat);   
   
   return ($entryDate);
}

function getManufactureDate()
{
   $mfgDate = Time::now(Time::$javascriptDateFormat);
   
   if (getView() != View::NEW_TIME_CARD)
   {
      $timeCardInfo = getTimeCardInfo();
      
      if ($timeCardInfo)
      {
         $dateTime = new DateTime($timeCardInfo->manufactureDate, new DateTimeZone('America/New_York'));
         $mfgDate = $dateTime->format(Time::$javascriptDateFormat);
      }
   }
   
   return ($mfgDate);
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
   <link rel="stylesheet" type="text/css" href="../thirdParty/tabulator/css/tabulator.min.css<?php echo versionQuery();?>"/>
   
   <link rel="stylesheet" type="text/css" href="../common/theme.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="../common/common.css<?php echo versionQuery();?>"/>
   
   <script src="../thirdParty/tabulator/js/tabulator.min.js<?php echo versionQuery();?>"></script>
   <script src="../thirdParty/moment/moment.min.js<?php echo versionQuery();?>"></script>
   
   <script src="/common/common.js<?php echo versionQuery();?>"></script>
   <script src="/common/validate.js<?php echo versionQuery();?>"></script>
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script>
   <script src="timeCard.js<?php echo versionQuery();?>"></script>

</head>

<body class="flex-vertical flex-top flex-left">
        
   <form id="input-form" action="" method="POST">
      <input id="time-card-id-input" type="hidden" name="timeCardId" value="<?php echo getTimeCardId(); ?>">
      <input type="hidden" name="manufactureDate" value="<?php echo getManufactureDate(); ?>">
      <input type="hidden" name="operator" value="<?php echo getOperator(); ?>">
      <input id="run-time-approved-by-input" type="hidden" form="input-form" name="runTimeApprovedBy" value="<?php echo getTimeCardInfo()->runTimeApprovedBy; ?>" />
      <input id="run-time-approved-date-time-input" type="hidden" form="input-form" name="runTimeApprovedDateTime" value="<?php echo getTimeCardInfo()->runTimeApprovedDateTime; ?>" />
      <input id="setup-time-approved-by-input" type="hidden" form="input-form" name="setupTimeApprovedBy" value="<?php echo getTimeCardInfo()->setupTimeApprovedBy; ?>" />
      <input id="seteup-time-approved-date-time-input" type="hidden" form="input-form" name="setupTimeApprovedDateTime" value="<?php echo getTimeCardInfo()->setupTimeApprovedDateTime; ?>" />
      <input id="shift-time-input" type="hidden" name="shiftTime" value="<?php echo getTimeCardInfo()->shiftTime; ?>">
      <input id="run-time-input" type="hidden" name="runTime" value="<?php echo getTimeCardInfo()->runTime; ?>">
      <input id="setup-time-input" type="hidden" name="setupTime" value="<?php echo getTimeCardInfo()->setupTime; ?>">
      <input id="gross-parts-per-hour-input" type="hidden" value="<?php echo getGrossPartsPerHour(); ?>">
   </form>

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading-with-iso"><?php echo getHeading(); ?></div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div class="iso-number">ISO <?php echo IsoInfo::getIsoNumber(IsoDoc::TIME_CARD); ?></div>
         
         <div id="description" class="description"><?php echo getDescription(); ?></div>
         
         <br>
         
         <div class="flex-horizontal flex-left flex-wrap">

            <div class="flex-vertical flex-left" style="margin-right: 50px;">

               <div class="form-item">
                  <div class="form-label">Entry Date</div>
                  <input type="datetime-local" class="form-input-medium" value="<?php echo getEntryDateTime(); ?>" <?php echo getDisabled(TimeCardInputField::ENTRY_DATE); ?>>
               </div>
               
               <div class="form-item">
                  <div class="form-label">Mfg. Date</div>
                  <input type="date" class="form-input-medium" name="manufactureDate" form="input-form" value="<?php echo getManufactureDate(); ?>" <?php echo getDisabled(TimeCardInputField::MANUFACTURE_DATE); ?>>
               </div>
               
               <div class="form-item">
                  <div class="form-label">Operator</div>
                  <select id="operator-input" class="form-input-medium" name="operator" form="input-form" oninput="this.validator.validate(); onOperatorChange();" <?php echo getDisabled(TimeCardInputField::OPERATOR); ?>>
                     <?php echo getOperatorOptions(); ?>
                  </select>
               </div>
         
               <div class="form-col">         
                  <div class="form-section-header">Job</div>         
                  <div class="form-item">
                     <div class="form-label">Job Number</div>
                     <select id="job-number-input" class="form-input-medium" name="jobNumber" form="input-form" oninput="this.validator.validate(); onJobNumberChange();" <?php echo getDisabled(TimeCardInputField::JOB_NUMBER); ?>>
                        <?php echo getJobNumberOptions(); ?>
                     </select>
                  </div>       
                  <div class="form-item">
                     <div class="form-label">Work Center</div>
                     <select id="wc-number-input" class="form-input-medium" name="wcNumber" form="input-form" oninput="this.validator.validate(); onWcNumberChange();" <?php echo getDisabled(TimeCardInputField::WC_NUMBER); ?>>
                        <?php echo JobInfo::getWcNumberOptions(getJobNumber(), getWcNumber()); ?>
                     </select>
                  </div>       
                  <div class="form-item">
                     <div class="form-label">Heat #</div>
                     <input id="material-number-input" type="number" class="form-input-medium" form="input-form" name="materialNumber" style="width:100px;" oninput="this.validator.validate()" value="<?php echo getMaterialNumber(); ?>" <?php echo getDisabled(TimeCardInputField::MATERIAL_NUMBER); ?>>
                  </div>         
               </div>
      
            </div>
            
            <div class="flex-vertical flex-top" style="margin-right: 50px;">
            
               <div class="form-col">
               
                  <div class="form-section-header">Time</div>
                  
                  <div class="form-item">
                     <div class="form-label">Shift time</div>
                     <input id="shift-time-hour-input" type="number" class="form-input-medium" form="input-form" name="shiftTimeHours" style="width:50px;" oninput="this.validator.validate(); onShiftTimeChange();" value="<?php echo getTimeCardInfo()->getShiftTimeHours(); ?>" <?php echo getDisabled(TimeCardInputField::SHIFT_TIME); ?> />
                     <div style="padding: 5px;">:</div>
                     <input id="shift-time-minute-input" type="number" class="form-input-medium" form="input-form" name="shiftTimeMinutes" style="width:50px;" oninput="this.validator.validate(); onShiftTimeChange();" value="<?php echo getTimeCardInfo()->getShiftTimeMinutes(); ?>" step="15" <?php echo getDisabled(TimeCardInputField::SHIFT_TIME); ?> />
                  </div>
                  
                  <div class="form-item">
                     <div class="form-label">Run time</div>
                     <div class="form-col">
                        <div class="form-row flex-left">
                           <input id="run-time-hour-input" type="number" class="form-input-medium" form="input-form" name="runTimeHours" style="width:50px;" oninput="this.validator.validate(); onRunTimeChange();" value="<?php echo getTimeCardInfo()->getRunTimeHours(); ?>" <?php echo getDisabled(TimeCardInputField::RUN_TIME); ?> />
                           <div style="padding: 5px;">:</div>
                           <input id="run-time-minute-input" type="number" class="form-input-medium" form="input-form" name="runTimeMinutes" style="width:50px;" oninput="this.validator.validate(); onRunTimeChange();" value="<?php echo getTimeCardInfo()->getRunTimeMinutes(); ?>" step="15" <?php echo getDisabled(TimeCardInputField::RUN_TIME); ?> />
                           &nbsp;&nbsp;
                           <?php echo getApprovalButton('run-time-approve-button', 'approveRunTime'); ?>
                           <?php echo getUnapprovalButton('run-time-unapprove-button', 'unapproveRunTime'); ?>
                        </div>
                        <div id="run-time-approved-text" class="approved-indicator">Approved by supervisor.</div>
                        <div id="run-time-unapproved-text" class="unapproved-indicator">Requires approval by supervisor.</div>
                     </div>
                  </div>
         
                  <div class="form-item">
                     <div class="form-label">Setup time</div>
                     <div class="form-col">
                        <div class="form-row flex-left">
                           <input id="setup-time-hour-input" type="number" class="form-input-medium $approval" form="input-form" name="setupTimeHours" style="width:50px;" oninput="this.validator.validate(); onSetupTimeChange();" value="<?php echo getTimeCardInfo()->getSetupTimeHours(); ?>" <?php echo getDisabled(TimeCardInputField::SETUP_TIME); ?> />
                           <div style="padding: 5px;">:</div>
                           <input id="setup-time-minute-input" type="number" class="form-input-medium $approval" form="input-form" name="setupTimeMinutes" style="width:50px;" oninput="this.validator.validate(); onSetupTimeChange();" value="<?php echo getTimeCardInfo()->getSetupTimeMinutes(); ?>" step="15" <?php echo getDisabled(TimeCardInputField::SETUP_TIME); ?> />
                           &nbsp;&nbsp;
                           <?php echo getApprovalButton('setup-time-approve-button', 'approveSetupTime'); ?>
                           <?php echo getUnapprovalButton('setup-time-unapprove-button', 'unapproveSetupTime'); ?>
                        </div>
                        <div id="setup-time-approved-text" class="approved-indicator">Approved by supervisor.</div>
                        <div id="setup-time-unapproved-text" class="unapproved-indicator">Requires approval by supervisor.</div>
                     </div>
                  </div>
                  
               </div>
               
               <div class="form-col">
                  
                  <div class="form-section-header">Part Counts</div>
                  
                  <div class="form-item">
                     <div class="form-label">Basket count</div>
                     <input id="pan-count-input" type="number" class="form-input-medium" form="input-form" name="panCount" style="width:100px;" oninput="panCountValidator.validate()" value="<?php echo getTimeCardInfo()->panCount; ?>" <?php echo getDisabled(TimeCardInputField::PAN_COUNT); ?> />
                  </div>
            
                  <div class="form-item">
                     <div class="form-label">Good count</div>
                     <input id="part-count-input" type="number" class="form-input-medium" form="input-form" name="partCount" style="width:100px;" oninput="partsCountValidator.validate(); onPartCountChange();" value="<?php echo getTimeCardInfo()->partCount; ?>" <?php echo getDisabled(TimeCardInputField::PART_COUNT); ?> />
                  </div>
            
                  <div class="form-item">
                     <div class="form-label">Scrap count</div>
                     <input id="scrap-count-input" type="number" class="form-input-medium" form="input-form" name="scrapCount" style="width:100px;" oninput="scrapCountValidator.validate()" value="<?php echo getTimeCardInfo()->scrapCount; ?>" <?php echo getDisabled(TimeCardInputField::SCRAP_COUNT); ?> />
                  </div>
            
                  <div class="form-item">
                     <div class="form-label">Efficiency</div>
                     <input id="efficiency-input" type="number" class="form-input-medium" style="width:100px;" value="" disabled />
                     <div>&nbsp%</div>
                  </div>
            
               </div>
               
            </div>
            
            <div class="flex-vertical flex-top" style="margin-right: 50px;">
            
               <?php echo getCommentCodesDiv(); ?>
               
               <div class="form-col">
                  <div class="form-section-header">Comments</div>
                  <div class="form-item">
                     <textarea form="input-form" id="comments-input" class="comments-input" type="text" form="input-form" name="comments" rows="4" maxlength="256" style="width:300px" <?php echo getDisabled(TimeCardInputField::COMMENTS); ?>><?php echo getTimeCardInfo()->comments; ?></textarea>
                  </div>
               </div>
               
               <div class="form-col">
                  <div class="form-section-header">Maintenance Log</div>
                  <div class="form-item">
                     <div id="maintenance-log-table"></div>
                  </div>
               </div>
               
            </div>

         </div>
         
         <div class="flex-horizontal flex-h-center">
            <button id="cancel-button">Cancel</button>&nbsp;&nbsp;&nbsp;
            <button id="save-button" class="accent-button">Save</button>            
         </div>
      
      </div> <!-- content -->
     
   </div> <!-- main -->   
         
   <script>
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::TIME_CARD ?>); 
      
      preserveSession();
      
      function getTableQuery()
      {
         return ("<?php echo $ROOT ?>/api/maintenanceLogData/");
      }

      function getTableQueryParams()
      {
         var params = new Object();
         
         params.wcNumber =  document.getElementById("wc-number-input").value;
         
         params.endDate =  new Date().toLocaleDateString();        // Now
         
         params.startDate = new Date();
         params.startDate.setDate(params.startDate.getDate() - 30);
         params.startDate = params.startDate.toLocaleDateString();  // A month earlier.

         return (params);
      }
      
      function getTableQuery()
      {
         return ("<?php echo $ROOT ?>/api/maintenanceLogData/");
      }
      
      function updateMaintenanceLogTable()
      {
         var url = getTableQuery();
         var params = getTableQueryParams();

         table.setData(url, params)
         .then(function(){
            // Run code after table has been successfuly updated
         })
         .catch(function(error){
            // Handle error loading data
         });
      }

      var url = getTableQuery();
      var params = getTableQueryParams();
      
      // Create Tabulator on DOM element maintenance-log-table.
      var table = new Tabulator("#maintenance-log-table", {
         layout:"fitDataTable",
         ajaxURL:url,
         ajaxParams:params,
         //Define Table Columns
         columns:[
            {title:"",            field:"maintenanceEntryId", hozAlign:"center",
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">build</i>");
               }
            },
            {title:"Date",        field:"maintenanceDate\Time",        hozAlign:"left",
               formatter:"datetime",  // Requires moment.js 
               formatterParams:{
                  outputFormat:"MM/DD/YYYY",
                  invalidPlaceholder:"---"
               }

            },
            {title:"Technician",  field:"technicianName", hozAlign:"left"},
            {title:"Job",         field:"jobNumber",      hozAlign:"left"},
            {title:"Type",        field:"typeLabel",      hozAlign:"left"},
            {title:"Category",    field:"categoryLabel",  hozAlign:"left"},
            {title:"Comments",    field:"comments",
               formatter:function(cell, formatterParams, onRendered){
                  var cellValue = cell.getValue();
                  if (cellValue.length > 16)
                  {
                     cellValue = cellValue.substring(0, 15) + " ...";
                  }
                  return (cellValue);
               }
            }
         ],
         cellClick:function(e, cell){
            var entryId = parseInt(cell.getRow().getData().maintenanceEntryId);

            if (cell.getColumn().getField() == "maintenanceEntryId")
            {
               document.location = "<?php echo $ROOT?>/maintenanceLog/maintenanceLogEntry.php?entryId=" + entryId;
            }
         },
         rowClick:function(e, row){
            // No row click function needed.
         },
      });
      
      function userCanApprove()
      {
         return (<?php echo canApprove() ? "true" : "false"; ?>);
      }
      
      var operatorValidator = new SelectValidator("operator-input");
      var jobNumberValidator = new SelectValidator("job-number-input");
      var wcNumberValidator = new SelectValidator("wc-number-input");
      var materialNumberValidator = new IntValidator("material-number-input", 4, 1, 9999, false);
      var shiftTimeHourValidator = new IntValidator("shift-time-hour-input", 2, 0, 16, true);
      var shiftTimeMinuteValidator = new IntValidator("shift-time-minute-input", 2, 0, 59, true);      
      var runTimeHourValidator = new IntValidator("run-time-hour-input", 2, 0, 16, true);
      var runTimeMinuteValidator = new IntValidator("run-time-minute-input", 2, 0, 59, true);
      var setupTimeHourValidator = new IntValidator("setup-time-hour-input", 2, 0, 16, true);
      var setupTimeMinuteValidator = new IntValidator("setup-time-minute-input", 2, 0, 59, true);
      var panCountValidator = new IntValidator("pan-count-input", 2, 0, 40, true);
      var partsCountValidator = new IntValidator("part-count-input", 6, 0, 100000, true);
      var scrapCountValidator = new IntValidator("scrap-count-input", 6, 0, 100000, true);

      operatorValidator.init();
      jobNumberValidator.init();
      wcNumberValidator.init();
      materialNumberValidator.init();
      shiftTimeHourValidator.init();
      shiftTimeMinuteValidator.init();
      runTimeHourValidator.init();
      runTimeMinuteValidator.init();
      setupTimeHourValidator.init();
      setupTimeMinuteValidator.init();
      panCountValidator.init();
      partsCountValidator.init();
      scrapCountValidator.init();

      updateEfficiency();
      updateApproval(RUN_TIME);
      updateApproval(SETUP_TIME);

      // Setup event handling on all DOM elements.
      document.getElementById("cancel-button").onclick = function(){onCancel();};
      document.getElementById("save-button").onclick = function(){onSaveTimeCard();};      
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};

      // Store the initial state of the form, for change detection.
      setInitialFormState("input-form");
            
   </script>

</body>

</html>