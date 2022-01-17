<?php

require_once '../common/activity.php';
require_once '../common/database.php';
require_once '../common/header.php';
require_once '../common/materialEntry.php';
require_once '../common/menu.php';
require_once '../common/params.php';
require_once '../common/userInfo.php';
require_once '../common/version.php';

const ACTIVITY = Activity::MATERIAL;

const ONLY_ACTIVE = true;

abstract class MaterialInputField
{
   const FIRST = 0;
   const ENTRY_DATE = MaterialInputField::FIRST;
   const ENTRY_USER = 1;
   const TAG = 2;
   const MATERIAL = 2;
   const VENDOR = 3;
   const VENDOR_HEAT = 4;
   const INTERNAL_HEAT = 6;
   const QUANTITY = 7;
   const PIECES = 8;
   const ISSUED_USER = 9;
   const ISSUED_DATE = 10;
   const RECEIVED_DATE = 11;
   const WC_NUMBER = 12;
   const JOB_NUMBER = 13;
   const ACKNOWLEDGED_USER = 13;
   const ACKNOWLEDGED_DATE = 15;
   const LAST = 16;
   const COUNT = MaterialInputField::LAST - MaterialInputField::FIRST;
}

abstract class View
{
   const NEW_MATERIAL = 0;
   const VIEW_MATERIAL = 1;
   const EDIT_MATERIAL = 2;
   const ISSUE_MATERIAL = 3;
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

function isIssueAction()
{
   $params = getParams();

   return ($params->keyExists("issue"));
}

function getView()
{
   $view = View::VIEW_MATERIAL;
   
   if (getEntryId() == MaterialEntry::UNKNOWN_ENTRY_ID)
   {
      $view = View::NEW_MATERIAL;
   }
   else if (Authentication::checkPermissions(Permission::EDIT_MATERIAL))
   {
      if (isIssueAction())
      {
         $view = View::ISSUE_MATERIAL;
      }
      else
      {
         $view = View::EDIT_MATERIAL;
      }
   }
   
   return ($view);
}

function getEntryId()
{
   $entryId = MaterialEntry::UNKNOWN_ENTRY_ID;
   
   $params = getParams();
   
   if ($params->keyExists("entryId"))
   {
      $entryId = $params->getInt("entryId");
   }
   
   return ($entryId);
}

function getMaterialEntry()
{
   static $materialEntry = null;
   
   if ($materialEntry == null)
   {
      $entryId = getEntryId();
      
      if ($entryId != MaterialEntry::UNKNOWN_ENTRY_ID)
      {
         $materialEntry = MaterialEntry::load($entryId);
         
         if (isIssueAction() && !$materialEntry->isIssued())
         {
            $materialEntry->issuedDateTime = Time::now("Y-m-d H:i:s");
            $materialEntry->issuedUserId = Authentication::getAuthenticatedUser()->employeeNumber;
         }            
      }
      else
      {
         $materialEntry = new MaterialEntry();
         
         $materialEntry->enteredDateTime = Time::now("Y-m-d H:i:s");
         $materialEntry->enteredUserId = Authentication::getAuthenticatedUser()->employeeNumber;
      }
   }
   
   return ($materialEntry);
}

function isEditable($field)
{
   $view = getView();
   
   $isEditable = false;
   
   switch ($field)
   {
      case MaterialInputField::ENTRY_DATE:
      case MaterialInputField::ENTRY_USER:
      case MaterialInputField::ISSUED_DATE:
      case MaterialInputField::ISSUED_USER:
      case MaterialInputField::ACKNOWLEDGED_DATE:
      case MaterialInputField::ACKNOWLEDGED_USER:
      case MaterialInputField::INTERNAL_HEAT:
      case MaterialInputField::QUANTITY:         
      {
         $isEditable = false;
         break;
      }
      
      case MaterialInputField::MATERIAL:
      case MaterialInputField::VENDOR:
      case MaterialInputField::TAG:
      case MaterialInputField::VENDOR_HEAT:
      case MaterialInputField::PIECES:
      case MaterialInputField::RECEIVED_DATE:
      {
         $isEditable = (($view == View::NEW_MATERIAL) ||
                        ($view == View::EDIT_MATERIAL));
         break;
      }
      
      case MaterialInputField::WC_NUMBER:
      {
         $isEditable = ($view == View::ISSUE_MATERIAL);
         break;
      }
      
      case MaterialInputField::JOB_NUMBER:
      {
         $isEditable = (($view == View::ISSUE_MATERIAL) &&
                        (getIssuedJobNumber() != JobInfo::UNKNOWN_JOB_NUMBER));
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
   
   if ($view == View::NEW_MATERIAL)
   {
      $heading = "Receive material";
   }
   else if ($view == View::EDIT_MATERIAL)
   {
      $heading = "Edit material details";
   }
   else if ($view == View::VIEW_MATERIAL)
   {
      $heading = "View material details";
   }
   else if ($view == View::ISSUE_MATERIAL)
   {
      $heading = "Issue material";
   }
   
   return ($heading);
}

function getDescription()
{
   $description = "";
   
   $view = getView();
   
   if ($view == View::NEW_MATERIAL)
   {
      $description = "Greek greek greek.";
   }
   else if ($view == View::EDIT_MATERIAL)
   {
      $description = "Greek greek greek.";
   }
   else if ($view == View::VIEW_MATERIAL)
   {
      $description = "Greek greek greek.";
   }
   else if ($view == View::ISSUE_MATERIAL)
   {
      $description = "Greek greek greek.";
   }
   
   return ($description);
}

function getInternalHeatNumber()
{
   $internalHeatNumber = null;
   
   $materialEntry = getMaterialEntry();
   
   if ($materialEntry && $materialEntry->materialHeatInfo)   
   {
      $internalHeatNumber = $materialEntry->materialHeatInfo->internalHeatNumber;
   }
   
   return ($internalHeatNumber);
}

function getEntryDate()
{
   $entryDate = Time::now(Time::$javascriptDateFormat);
   
   $materialEntry = getMaterialEntry();
   
   if ($materialEntry)
   {
      $dateTime = new DateTime($materialEntry->enteredDateTime, new DateTimeZone('America/New_York'));
      $entryDate = $dateTime->format(Time::$javascriptDateFormat);
   }
   
   return ($entryDate);
}

function getReceivedDate()
{
   $receivedDate = Time::now(Time::$javascriptDateFormat);
   
   $materialEntry = getMaterialEntry();
   
   if ($materialEntry)
   {
      $dateTime = new DateTime($materialEntry->receivedDateTime, new DateTimeZone('America/New_York'));
      $receivedDate = $dateTime->format(Time::$javascriptDateFormat);
   }
   
   return ($receivedDate);
}

function getIssuedDate()
{
   $issuedDate = null;
   
   $materialEntry = getMaterialEntry();
   
   if ($materialEntry && $materialEntry->issuedDateTime)
   {
      $dateTime = new DateTime($materialEntry->issuedDateTime, new DateTimeZone('America/New_York'));
      $issuedDate = $dateTime->format(Time::$javascriptDateFormat);
   }
   
   return ($issuedDate);
}

function getIssuedJobNumber()
{
   $jobNumber = JobInfo::UNKNOWN_JOB_NUMBER;
   
   $materialEntry = getMaterialEntry();
   
   if ($materialEntry->issuedJobId != JobInfo::UNKNOWN_JOB_ID)
   {
      $jobInfo = JobInfo::load($materialEntry->issuedJobId);
      
      if ($jobInfo)
      {
         $jobNumber = $jobInfo->jobNumber;
      }
   }
   
   return ($jobNumber);
}

function getIssuedWcNumber()
{
   $wcNumber = JobInfo::UNKNOWN_WC_NUMBER;
   
   $materialEntry = getMaterialEntry();
   
   if ($materialEntry->issuedJobId != JobInfo::UNKNOWN_JOB_ID)
   {
      $jobInfo = JobInfo::load($materialEntry->issuedJobId);
      
      if ($jobInfo)
      {
         $wcNumber = $jobInfo->wcNumber;
      }
   }
   
   return ($wcNumber);
}

function getAcknowledgedDate()
{
   $acknowledgedDate = null;
   
   $materialEntry = getMaterialEntry();
   
   if ($materialEntry && $materialEntry->acknowledgedDateTime)
   {
      $dateTime = new DateTime($materialEntry->acknowledgedDateTime, new DateTimeZone('America/New_York'));
      $acknowledgedDate = $dateTime->format(Time::$javascriptDateFormat);
   }
   
   return ($acknowledgedDate);
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
   <script src="material.js<?php echo versionQuery();?>"></script>

</head>

<body class="flex-vertical flex-top flex-left">
        
   <form id="input-form" action="" method="POST">
      <!-- Hidden inputs make sure disabled fields below get posted. -->
      <input id="entry-id-input" type="hidden" name="entryId" value="<?php echo getEntryId(); ?>">
      <input id="entered-user-id-input" type="hidden" name="enteredUserId" value="<?php echo getMaterialEntry()->enteredUserId; ?>">
      <input id="issued-user-id-input" type="hidden" name="issuedUserId" value="<?php echo getMaterialEntry()->issuedUserId; ?>">
      <input id="hidden-internal-heat-number-input" type="hidden" name="internalHeatNumber">
   </form>

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(ACTIVITY); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading"><?php echo getHeading(); ?></div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div id="description" class="description"><?php echo getDescription(); ?></div>
         
         <br>
      
         <div class="flex-horizontal flex-left">
         
            <div class="content flex-vertical flex-top flex-left" style="margin-right: 50px;">
                           
               <div class="form-item" style="padding-right: 25px;">
                  <div class="form-label">Entry Date</div>
                   <input id="entry-date-input" type="date" name="entryDate" form="input-form" oninput="" value="<?php echo getEntryDate(); ?>" <?php echo getDisabled(MaterialInputField::ENTRY_DATE); ?>/>
               </div>
               
               <div class="form-item">
                  <div class="form-label">Entered By</div>
                  <select id="employee-number-input" name="employeeNumber" form="input-form" oninput="" <?php echo getDisabled(MaterialInputField::ENTRY_USER); ?>>
                     <?php echo UserInfo::getOptions([Role::OPERATOR], [Authentication::getAuthenticatedUser()->employeeNumber, getMaterialEntry()->enteredUserId], getMaterialEntry()->enteredUserId); ?>
                  </select>
               </div>
               
               <div class="form-section-header">Heat</div>
               
               <div class="form-item">
                  <div class="form-label">Vendor Heat</div>
                  <input id="vendor-heat-number-input" type="text" name="vendorHeatNumber" form="input-form" oninput="this.validator.validate(); onVendorHeatNumberChange()" value="<?php echo getMaterialEntry()->vendorHeatNumber; ?>" <?php echo getDisabled(MaterialInputField::VENDOR_HEAT); ?> />
               </div>  
               
               <div class="form-item">
                  <div class="form-label">Vendor</div>
                  <select id="vendor-id-input" name="vendorId" form="input-form" oninput="this.validator.validate();" <?php echo getDisabled(MaterialInputField::VENDOR); ?>>
                     <?php echo MaterialVendor::getOptions(getMaterialEntry()->materialHeatInfo->vendorId); ?>
                  </select>
               </div>
               
               <div class="form-item">
                  <div class="form-label">PPTP Heat</div>
                  <input id="internal-heat-number-input" type="number" style="width:100px;" name="internalHeatNumber" form="input-form" oninput="this.validator.validate();" value="<?php echo (getView() == View::NEW_MATERIAL) ? "" : getInternalHeatNumber(); ?>" <?php echo getDisabled(MaterialInputField::INTERNAL_HEAT); ?> />
                  &nbsp;&nbsp;
                  <button id="edit-internal-heat-number-button" class="small-button accent-button" onclick="onEditInternalHeatButton()">Custom</button>
               </div>
               
               <div class="form-section-header">Material</div>
                                 
               <div class="form-item">
                  <div class="form-label">Material type</div>
                  <select id="material-id-input" name="materialId" form="input-form" oninput="this.validator.validate(); recalculateQuantity()" <?php echo getDisabled(MaterialInputField::MATERIAL); ?>>
                     <?php echo MaterialInfo::getOptions(getMaterialEntry()->materialHeatInfo->materialId); ?>
                  </select>
               </div>
               
               <div class="form-item">
                  <div class="form-label">Tag #</div>
                  <input id="tag-number-input" type="text" name="tagNumber" form="input-form" oninput="this.validator.validate();" value="<?php echo getMaterialEntry()->tagNumber; ?>" <?php echo getDisabled(MaterialInputField::TAG); ?> />
               </div>         
               
               <div class="form-item">
                  <div class="form-label">Pieces</div>
                  <input id="pieces-input" type="number" style="width:50px;" name="pieces" form="input-form" oninput="this.validator.validate(); recalculateQuantity()" value="<?php echo (getView() == View::NEW_MATERIAL) ? "" : getMaterialEntry()->pieces; ?>" <?php echo getDisabled(MaterialInputField::PIECES); ?> />
               </div>
               
               <div class="form-item">
                  <div class="form-label">Quantity</div>
                  <input id="quantity-input" type="number" style="width:50px;" form="input-form" value="<?php echo getMaterialEntry()->getQuantity() ?>" <?php echo getDisabled(MaterialInputField::QUANTITY); ?> />
               </div>                    
               
            </div>
            
            <div class="content flex-vertical flex-top flex-left" style="margin-right: 50px;">
            
               <div class="form-section-header">Received</div>
                           
               <div class="form-item" style="padding-right: 25px;">
                  <div class="form-label">Received Date</div>
                  <input id="received-date-input" type="date" name="receivedDate" form="input-form" oninput="" value="<?php echo getReceivedDate(); ?>" <?php echo getDisabled(MaterialInputField::RECEIVED_DATE); ?>>
               </div>            
            
               <div class="form-section-header">Issued</div>
                           
               <div class="form-item" style="padding-right: 25px;">
                  <div class="form-label">Issued Date</div>
                  <input id="issued-date-input" type="date" name="issuedDate" form="input-form" oninput="" value="<?php echo getIssuedDate(); ?>" <?php echo getDisabled(MaterialInputField::ISSUED_DATE); ?>>
               </div>
               
               <div class="form-item">
                  <div class="form-label">Issued By</div>
                  <select id="issued-user-id-input" name="issuedUserId" form="input-form" oninput="" <?php echo getDisabled(MaterialInputField::ISSUED_USER); ?>>
                     <?php echo UserInfo::getOptions([Role::OPERATOR], [Authentication::getAuthenticatedUser()->employeeNumber, getMaterialEntry()->issuedUserId], getMaterialEntry()->issuedUserId); ?>
                  </select>
               </div>
               
               <div class="form-item">
                  <div class="form-label">WC #</div>
                  <select id="wc-number-input" name="wcNumber" form="input-form" oninput="this.validator.validate(); onWCNumberChange()" <?php echo getDisabled(MaterialInputField::WC_NUMBER); ?>>
                     <?php echo JobInfo::getWcNumberOptions(JobInfo::UNKNOWN_JOB_ID, getIssuedWcNumber()); ?>
                  </select>
               </div>               
                                 
               <div class="form-item">
                  <div class="form-label">Job #</div>
                  <select id="job-number-input" name="jobNumber" form="input-form" oninput="this.validator.validate();" <?php echo getDisabled(MaterialInputField::JOB_NUMBER); ?>>
                     <?php echo JobInfo::getJobNumberOptions(getIssuedJobNumber(), true, true); ?>
                  </select>
               </div>
               
               <div class="form-section-header">Acknowledged</div>
                           
               <div class="form-item" style="padding-right: 25px;">
                  <div class="form-label-long">Acknowledged Date</div>
                  <input id="acknowledged-date-input" type="date" name="acknowledgedDate" form="input-form" oninput="" value="<?php echo getAcknowledgedDate(); ?>" <?php echo getDisabled(MaterialInputField::ACKNOWLEDGED_DATE); ?>>
               </div>
               
               <div class="form-item">
                  <div class="form-label-long">Acknowledged By</div>
                  <select id="acknowleged-user-id-input" name="acknowledgedUserId" form="input-form" oninput="" <?php echo getDisabled(MaterialInputField::ACKNOWLEDGED_USER); ?>>
                     <?php echo UserInfo::getOptions([Role::OPERATOR], [Authentication::getAuthenticatedUser()->employeeNumber, getMaterialEntry()->acknowledgedUserId], getMaterialEntry()->acknowledgedUserId); ?>
                  </select>
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
      var vendorHeatValidator = new RegExpressionValidator("vendor-heat-number-input", /^[a-zA-Z0-9]+$/, true, 16);
      var vendorValidator = new SelectValidator("vendor-id-input");
      var internalHeatValidator = new IntValidator("internal-heat-number-input", 5, 1, 99999, false);
      var materialValidator = new SelectValidator("material-id-input");
      var tagNumberValidator = new RegExpressionValidator("tag-number-input", /^[0-9-]+$/, true, 16);
      var piecesValidator = new IntValidator("pieces-input", 4, 1, 1000, false);
      var jobNumberValidator = new SelectValidator("job-number-input");
      var wcValidator = new SelectValidator("wc-number-input");

      vendorHeatValidator.init();
      vendorValidator.init();
      internalHeatValidator.init();
      materialValidator.init();
      tagNumberValidator.init();
      piecesValidator.init();
      jobNumberValidator.init();
      wcValidator.init();
      
      // Setup event handling on all DOM elements.
      document.getElementById("cancel-button").onclick = function(){onCancel();};
      document.getElementById("save-button").onclick = function(){<?php echo isIssueAction() ? "onIssueMaterialEntry()" : "onSaveMaterialEntry()" ?>;};      
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
      document.getElementById("menu-button").onclick = function(){document.getElementById("menu").classList.toggle('shown');};

      // Store the initial state of the form, for change detection.
      setInitialFormState("input-form");
      
      // Store an array of material lengths.
      var materialLengths = <?php echo MaterialInfo::getJavascriptLengthArray() ?>;
      
      var nextInternalHeatNumber = <?php echo MaterialHeatInfo::getNextInternalHeatNumber(); ?>;
      
      onVendorHeatNumberChange();

   </script>

</body>

</html>
