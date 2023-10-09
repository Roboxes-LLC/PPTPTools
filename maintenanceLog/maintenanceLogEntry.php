<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once '../common/database.php';
require_once '../common/header.php';
require_once '../common/maintenanceEntry.php';
require_once '../common/params.php';
require_once '../common/timeCardInfo.php';
require_once '../common/userInfo.php';
require_once '../common/version.php';

const ONLY_ACTIVE = true;

abstract class MaintenanceLogInputField
{
   const FIRST = 0;
   const ENTRY_DATE = MaintenanceLogInputField::FIRST;
   const MAINTENANCE_DATE = 1;
   const SHIFT_TIME = 2;
   const MAINTENANCE_TIME = 3;
   const MAINTENANCE_TYPE = 4;
   const MAINTENANCE_CATEGORY = 5;
   const MAINTENANCE_SUBCATEGORY = 6;
   const EMPLOYEE_NUMBER = 7;
   const JOB_NUMBER = 8;
   const WC_NUMBER = 9;
   const PART_NUMBER = 10;
   const COMMENTS = 11;
   const LAST = 12;
   const COUNT = MaintenanceLogInputField::LAST - MaintenanceLogInputField::FIRST;
}

abstract class View
{
   const NEW_MAINTENANCE_ENTRY = 0;
   const VIEW_MAINTENANCE_ENTRY = 1;
   const EDIT_MAINTENANCE_ENTRY = 2;
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
   $view = View::VIEW_MAINTENANCE_ENTRY;
   
   if (getEntryId() == MaintenanceEntry::UNKNOWN_ENTRY_ID)
   {
      $view = View::NEW_MAINTENANCE_ENTRY;
   }
   else if (Authentication::checkPermissions(Permission::EDIT_MAINTENANCE_LOG))
   {
      $view = View::EDIT_MAINTENANCE_ENTRY;
   }
   
   return ($view);
}

function getEntryId()
{
   $entryId = MaintenanceEntry::UNKNOWN_ENTRY_ID;
   
   $params = getParams();
   
   if ($params->keyExists("entryId"))
   {
      $entryId = $params->getInt("entryId");
   }
   
   return ($entryId);
}

function getMaintenanceEntry()
{
   static $maintenanceEntry = null;
   
   if ($maintenanceEntry == null)
   {
      $entryId = getEntryId();
      
      if ($entryId != MaintenanceEntry::UNKNOWN_ENTRY_ID)
      {
         $maintenanceEntry = MaintenanceEntry::load($entryId);
      }
      else
      {
         $maintenanceEntry = new MaintenanceEntry();
         $maintenanceEntry->shiftTime = TimeCardInfo::DEFAULT_SHIFT_TIME;
      }
   }
   
   return ($maintenanceEntry);
}

function isEditable($field)
{
   $view = getView();
   
   // Start with the edit mode, as dictated by the view.
   $isEditable = (($view == View::NEW_MAINTENANCE_ENTRY) ||
                  ($view == View::EDIT_MAINTENANCE_ENTRY));
   
   switch ($field)
   {
      case MaintenanceLogInputField::ENTRY_DATE:
      {
         $isEditable = false;
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
   
   if ($view == View::NEW_MAINTENANCE_ENTRY)
   {
      $heading = "Add to the Maintenance Log";
   }
   else if ($view == View::EDIT_MAINTENANCE_ENTRY)
   {
      $heading = "Update the Maintenance Log";
   }
   else if ($view == View::VIEW_MAINTENANCE_ENTRY)
   {
      $heading = "View a Maintenance Log Entry";
   }
   
   return ($heading);
}

function getDescription()
{
   $description = "";
   
   $view = getView();
   
   if ($view == View::NEW_MAINTENANCE_ENTRY)
   {
      $description = "Create a new entry in the maintenace log.";
   }
   else if ($view == View::EDIT_MAINTENANCE_ENTRY)
   {
      $description = "You may revise any of the fields for this log entry and then select save when you're satisfied with the changes.";
   }
   else if ($view == View::VIEW_MAINTENANCE_ENTRY)
   {
      $description = "View a previously saved log entry in detail.";
   }
   
   return ($description);
}

function getEntryDate()
{
   $entryDate = Time::now(Time::$javascriptDateFormat);
   
   $maintenanceEntry = getMaintenanceEntry();
   
   if ($maintenanceEntry)
   {
      $dateTime = new DateTime($maintenanceEntry->dateTime, new DateTimeZone('America/New_York'));
      $entryDate = $dateTime->format(Time::$javascriptDateFormat);
   }
   
   return ($entryDate);
}

function getMaintenanceDate()
{
   $maintenanceDate = Time::now(Time::$javascriptDateFormat);
   
   $maintenanceEntry = getMaintenanceEntry();
   
   if ($maintenanceEntry)
   {
      $dateTime = new DateTime($maintenanceEntry->maintenanceDateTime, new DateTimeZone('America/New_York'));
      $maintenanceDate = $dateTime->format(Time::$javascriptDateFormat);
   }
   
   return ($maintenanceDate);
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
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script>   
   <script src="maintenanceLog.js<?php echo versionQuery();?>"></script>

</head>

<body class="flex-vertical flex-top flex-left">
        
   <form id="input-form" action="" method="POST">
      <!-- Hidden inputs make sure disabled fields below get posted. -->
      <input id="entry-id-input" type="hidden" name="entryId" value="<?php echo getEntryId(); ?>">
      <input id="shift-time-input" type="hidden" name="shiftTime" value="<?php echo getMaintenanceEntry()->shiftTime; ?>">
      <input id="maintenance-time-input" type="hidden" name="maintenanceTime" value="<?php echo getMaintenanceEntry()->maintenanceTime; ?>">
   </form>

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading"><?php echo getHeading(); ?></div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div id="description" class="description"><?php echo getDescription(); ?></div>
         
         <br>
         
         <div class="form-item">
            <div class="form-label-long">Entry Date</div>
            <div class="flex-horizontal">
               <input id="entry-date-input" type="date" name="entryDate" form="input-form" oninput="" value="<?php echo getMaintenanceDate(); ?>" <?php echo getDisabled(MaintenanceLogInputField::ENTRY_DATE); ?>>
            </div>
         </div>
         
         <div class="form-item">
            <div class="form-label-long">Maintenance Date</div>
            <div class="flex-horizontal">
               <input id="maintenance-date-input" type="date" name="maintenanceDate" form="input-form" oninput="" value="<?php echo getMaintenanceDate(); ?>" <?php echo getDisabled(MaintenanceLogInputField::MAINTENANCE_DATE); ?>>
               &nbsp<button id="today-button" class="small-button" <?php echo getDisabled(MaintenanceLogInputField::MAINTENANCE_DATE); ?>>Today</button>
               &nbsp<button id="yesterday-button"  class="small-button" <?php echo getDisabled(MaintenanceLogInputField::MAINTENANCE_DATE); ?>>Yesterday</button>
            </div>
         </div>
         
         <div class="form-item">
            <div class="form-label-long">Shift Time</div>
            <div class="form-col">
               <div class="form-row flex-left">
                  <input id="shift-time-hour-input" type="number" class="form-input-medium" form="input-form" name="shiftTimeHours" style="width:50px;" oninput="this.validator.validate(); onShiftTimeChange();" value="<?php echo getMaintenanceEntry()->getShiftTimeHours(); ?>" <?php echo getDisabled(MaintenanceLogInputField::SHIFT_TIME); ?> />
                  <div style="padding: 5px;">:</div>
                  <input id="shift-time-minute-input" type="number" class="form-input-medium" form="input-form" name="shiftTimeMinutes" style="width:50px;" oninput="this.validator.validate(); onShiftTimeChange();" value="<?php echo getMaintenanceEntry()->getShiftTimeMinutes(); ?>" step="15" <?php echo getDisabled(MaintenanceLogInputField::SHIFT_TIME); ?> />
               </div>
            </div>
         </div>
         
         <div class="form-item">
            <div class="form-label-long">Maintenance Time</div>
            <div class="form-col">
               <div class="form-row flex-left">
                  <input id="maintenance-time-hour-input" type="number" class="form-input-medium" form="input-form" name="maintenanceTimeHours" style="width:50px;" oninput="this.validator.validate(); onMaintenanceTimeChange();" value="<?php echo getMaintenanceEntry()->getMaintenanceTimeHours(); ?>" <?php echo getDisabled(MaintenanceLogInputField::MAINTENANCE_TIME); ?> />
                  <div style="padding: 5px;">:</div>
                  <input id="maintenance-time-minute-input" type="number" class="form-input-medium" form="input-form" name="maintenanceTimeMinutes" style="width:50px;" oninput="this.validator.validate(); onMaintenanceTimeChange();" value="<?php echo getMaintenanceEntry()->getMaintenanceTimeMinutes(); ?>" step="15" <?php echo getDisabled(MaintenanceLogInputField::MAINTENANCE_TIME); ?> />
               </div>
            </div>
         </div>
         
         <div class="form-item">
            <div class="form-label-long">Technician</div>
            <div class="flex-horizontal">
               <select id="employee-number-input" name="employeeNumber" form="input-form" oninput="" <?php echo getDisabled(MaintenanceLogInputField::EMPLOYEE_NUMBER); ?>>
                  <?php echo UserInfo::getOptions([Role::OPERATOR], [Authentication::getAuthenticatedUser()->employeeNumber], getMaintenanceEntry()->employeeNumber); ?>
               </select>
            </div>
         </div>
         
         <div class="flex-horizontal flex-top">
         
            <div class="flex-vertical flex-top"  style="padding-right: 25px;">
         
               <div class="form-item">
                  <div class="form-label-long">Job # &nbsp; <div class="incomplete-indicator">(optional)</div></div>
                  <div class="flex-horizontal">
                     <select id="job-number-input" name="jobNumber" form="input-form" oninput="onJobNumberChange()" <?php echo getDisabled(MaintenanceLogInputField::JOB_NUMBER); ?>>
                        <?php echo JobInfo::getJobNumberOptions(getMaintenanceEntry()->jobNumber, true, true); ?>
                     </select>
                  </div>
               </div>
         
               <div class="form-item">
                  <div class="form-label-long">WC #</div>
                  <div class="flex-horizontal">
                     <select id="wc-number-input" name="wcNumber" form="input-form" oninput="onWCNumberChange()" <?php echo getDisabled(MaintenanceLogInputField::WC_NUMBER); ?>>
                        <?php echo JobInfo::getWcNumberOptions(JobInfo::UNKNOWN_JOB_ID, getMaintenanceEntry()->wcNumber); ?>
                     </select>
                  </div>
               </div>
               
            </div>
            
            <!--  Vertical line -->
            <div style="border-left: 1px solid black; padding-right: 25px; margin-bottom:20px; align-self: stretch"></div>
            
            <div class="form-item">
               <div class="form-label-long">Support equipment</div>
               <div class="flex-horizontal">
                  <select id="equipment-input" name="equipmentId" form="input-form" oninput="onEquipmentChange()" <?php echo getDisabled(MaintenanceLogInputField::WC_NUMBER); ?>>
                     <?php echo EquipmentInfo::getEquipmentOptions(getMaintenanceEntry()->equipmentId); ?>
                  </select>
               </div>
            </div>
            
         </div>
         
         <div class="form-item">
            <div class="form-label-long">Maintenance Type</div>
            <div class="flex-horizontal">
               <select id="maintenance-type-input" name="typeId" form="input-form" oninput="onMaintenanceTypeChange()" <?php echo getDisabled(MaintenanceLogInputField::MAINTENANCE_TYPE); ?>>
                  <?php echo MaintenanceEntry::getTypeOptions(getMaintenanceEntry()->typeId); ?>
               </select>
               
               &nbsp;&nbsp;
               
               <select id="maintenance-category-input" name="categoryId" form="input-form" oninput="onMaintenanceCategoryChange()" <?php echo getDisabled(MaintenanceLogInputField::MAINTENANCE_CATEGORY); ?>>
               </select>
               
               &nbsp;&nbsp;
               
               <select id="maintenance-subcategory-input" name="subcategoryId" form="input-form" <?php echo getDisabled(MaintenanceLogInputField::MAINTENANCE_SUBCATEGORY); ?>>
               </select>
               
            </div>
         </div>
         
         <div class="flex-horizontal flex-top">
         
         <div id="part-number-block" class="form-item" style="padding-right: 25px;">
            <div class="form-label-long">Part # &nbsp; <div class="incomplete-indicator">(optional)</div></div>
            <div class="flex-vertical">
               <div class="flex-horizontal">
                  <select id="part-number-input" name="partId" form="input-form" oninput="onPartNumberChange()" <?php echo getDisabled(MaintenanceLogInputField::PART_NUMBER); ?>>
                     <?php echo MachinePartInfo::getOptions(getMaintenanceEntry()->partId, true); ?>
                  </select>
               </div>
            </div>
         </div>
         
         <!--  Vertical line -->
         <div style="border-left: 1px solid black; padding-right: 25px; margin-bottom:20px; align-self: stretch"></div>

         <div id="new-part-number-block" class="flex-vertical">
         
         <div class="form-item">
            <div class="form-label-long">New Part #</div>
            <div class="flex-vertical">
               <div class="flex-horizontal">
                  <input id="new-part-number-input" type="text" name="newPartNumber" form="input-form" oninput="onNewPartNumberChange()" <?php echo getDisabled(MaintenanceLogInputField::PART_NUMBER); ?> />
               </div>
            </div>
         </div>
         
         <div class="form-item">
            <div class="form-label-long">Part Description</div>
            <input id="new-part-description-input" type="text" name="newPartDescription" form="input-form" oninput="onNewPartNumberChange()" <?php echo getDisabled(MaintenanceLogInputField::PART_NUMBER); ?> />
         </div>
         
         </div>
         
         </div>         
         
         <div class="form-item">
            <div class="form-label-long">Comments</div>
            <textarea class="comments-input" type="text" form="input-form" name="comments" rows="4" maxlength="256" style="width:300px" <?php echo getDisabled(MaintenanceLogInputField::COMMENTS); ?>><?php echo getMaintenanceEntry()->comments; ?></textarea>
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
      menu.setMenuItemSelected(<?php echo AppPage::MAINTENANCE_LOG ?>);  
      
      preserveSession();
      
      var shiftTimeHourValidator = new IntValidator("shift-time-hour-input", 2, 0, 16, true);
      var shiftTimeMinuteValidator = new IntValidator("shift-time-minute-input", 2, 0, 59, true);
      var maintentanceTimeHourValidator = new IntValidator("maintenance-time-hour-input", 2, 0, 16, true);
      var maintenanceTimeMinuteValidator = new IntValidator("maintenance-time-minute-input", 2, 0, 59, true);  
      var employeeNumberValidator = new SelectValidator("employee-number-input");
      var wcNumberValidator = new SelectValidator("wc-number-input");
      var equipmentValidator = new SelectValidator("equipment-input");
      var maintenanceTypeValidator = new SelectValidator("maintenance-type-input");
      var maintenanceCategoryValidator = new SelectValidator("maintenance-category-input");
      var maintenanceSubcategoryValidator = new SelectValidator("maintenance-subcategory-input");
      var partNumberValidator = new SelectValidator("part-number-input");

      shiftTimeHourValidator.init();
      shiftTimeMinuteValidator.init();
      maintentanceTimeHourValidator.init();
      maintenanceTimeMinuteValidator.init();
      employeeNumberValidator.init();
      wcNumberValidator.init();
      equipmentValidator.init();
      maintenanceTypeValidator.init();
      maintenanceCategoryValidator.init();
      maintenanceSubcategoryValidator.init();      
      partNumberValidator.init();
      
      // Setup event handling on all DOM elements.
      document.getElementById("today-button").onclick = onTodayButton;
      document.getElementById("yesterday-button").onclick = onYesterdayButton;
      document.getElementById("cancel-button").onclick = function(){onCancel();};
      document.getElementById("save-button").onclick = function(){onSaveMaintenanceEntry();};      
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};

      // Show/hide context sensitive inputs.            
      //onExistingPartButton();
      onMaintenanceTypeChange();
      
      // Store the initial state of the form, for change detection.
      setInitialFormState("input-form");

   </script>

</body>

</html>
