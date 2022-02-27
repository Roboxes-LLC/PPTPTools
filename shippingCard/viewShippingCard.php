<?php

require_once '../common/activity.php';
require_once '../common/commentCodes.php';
require_once '../common/header.php';
require_once '../common/isoInfo.php';
require_once '../common/jobInfo.php';
require_once '../common/menu.php';
require_once '../common/params.php';
require_once '../common/shippingCardInfo.php';
require_once '../common/userInfo.php';
require_once '../common/version.php';

const ACTIVITY = Activity::SHIPPING_CARD;

const ONLY_ACTIVE = true;

abstract class ShippingCardInputField
{
   const FIRST = 0;
   const ENTRY_DATE = ShippingCardInputField::FIRST;   
   const MANUFACTURE_DATE = 1;
   const SHIPPER = 2;
   const JOB_NUMBER = 3;
   const SHIFT_TIME = 4;   
   const SHIPPING_TIME = 5;
   const PART_COUNT = 6;
   const SCRAP_COUNT = 7;
   const COMMENTS = 8;
   const LAST = 9;
   const COUNT = ShippingCardInputField::LAST - ShippingCardInputField::FIRST;
}

abstract class View
{
   const NEW_SHIPPING_CARD = 0;
   const VIEW_SHIPPING_CARD = 1;
   const EDIT_SHIPPING_CARD = 2;
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
   $view = View::VIEW_SHIPPING_CARD;
   
   if (getShippingCardId() == ShippingCardInfo::UNKNOWN_SHIPPING_CARD_ID)
   {
      $view = View::NEW_SHIPPING_CARD;
   }
   else if (Authentication::checkPermissions(Permission::EDIT_SHIPPING_CARD))
   {
      $view = View::EDIT_SHIPPING_CARD;
   }
   
   return ($view);
}

function isEditable($field)
{
   $view = getView();
   
   // Start with the edit mode, as dictated by the view.
   $isEditable = (($view == View::NEW_SHIPPING_CARD) ||
                  ($view == View::EDIT_SHIPPING_CARD));
   
   switch ($field)
   {
      case ShippingCardInputField::ENTRY_DATE:
      {
         $isEditable = false;
         break;
      }
      
      case ShippingCardInputField::SHIPPER:
      case ShippingCardInputField::MANUFACTURE_DATE:
      {
         $isEditable = ((Authentication::getAuthenticatedUser()->roles == Role::ADMIN) ||
                        (Authentication::getAuthenticatedUser()->roles == Role::SUPER_USER));
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

function getShippingCardId()
{
   $timeCardId = ShippingCardInfo::UNKNOWN_SHIPPING_CARD_ID;
   
   $params = getParams();
   
   if ($params->keyExists("shippingCardId"))
   {
      $timeCardId = $params->getInt("shippingCardId");
   }
   
   return ($timeCardId);
}

function getShippingCardInfo()
{
   static $shippingCardInfo = null;
   
   if ($shippingCardInfo == null)
   {
      $params = Params::parse();
      
      if ($params->keyExists("shippingCardId"))
      {
         $shippingCardInfo = ShippingCardInfo::load($params->get("shippingCardId"));
      }
      else
      {
         $shippingCardInfo = new ShippingCardInfo();
         
         $shippingCardInfo->employeeNumber = Authentication::getAuthenticatedUser()->employeeNumber;
         $shippingCardInfo->shiftTime = ShippingCardInfo::DEFAULT_SHIFT_TIME;
      }
   }
   
   return ($shippingCardInfo);
}

function getShipper()
{
   $shipper = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
   
   $shippingCardInfo = getShippingCardInfo();
   
   if ($shippingCardInfo)
   {
      $shipper = $shippingCardInfo->employeeNumber;
   }
   
   return ($shipper);
}

function getShipperOptions()
{
   $options = "<option style=\"display:none\">";
   
   $shippers = PPTPDatabase::getInstance()->getUsersByRole(Role::SHIPPER);
   
   // Create an array of employee numbers.
   $employeeNumbers = array();
   foreach ($shippers as $shipper)
   {
      $employeeNumbers[] = intval($shipper["employeeNumber"]);
   }
   
   $selectedShipper = getShipper();
   
   // Add selected shipper, if not already in the array.
   // Note: This handles the case of viewing an entry with an shipper that is not assigned to the OPERATOR role.
   if (($selectedShipper != UserInfo::UNKNOWN_EMPLOYEE_NUMBER) &&
       (!in_array($selectedShipper, $employeeNumbers)))
   {
      $employeeNumbers[] = $selectedShipper;
      sort($employeeNumbers);
   }
   
   foreach ($employeeNumbers as $employeeNumber)
   {
      $userInfo = UserInfo::load($employeeNumber);
      if ($userInfo)
      {
         $selected = ($employeeNumber == $selectedShipper) ? "selected" : "";
         
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
   $shippingCardInfo = getShippingCardInfo();
   
   $disabled = !isEditable(ShippingCardInputField::COMMENTS);
   
   $commentCodes = CommentCode::getCommentCodes();
   
   $leftColumn = "";
   $rightColumn = "";
   $index = 0;
   foreach ($commentCodes as $commentCode)
   {
      $id = "code-" . $commentCode->code . "-input";
      $name = "code-" . $commentCode->code;
      $checked = ($shippingCardInfo->hasCommentCode($commentCode->code) ? "checked" : "");
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

function getJobNumber()
{
   $jobNumber = JobInfo::UNKNOWN_JOB_NUMBER;
   
   $shippingCardInfo = getShippingCardInfo();
   
   if ($shippingCardInfo)
   {
      $jobId = $shippingCardInfo->jobId;
      
      $jobInfo = JobInfo::load($jobId);
      
      if ($jobInfo)
      {
         $jobNumber = $jobInfo->jobNumber;
      }
   }
   
   return ($jobNumber);
}

function getHeading()
{
   $heading = "";
   
   switch (getView())
   {
      case View::NEW_SHIPPING_CARD:
      {
         $heading = "Create a New Shipping Card";
         break;
      }
      
      case View::EDIT_SHIPPING_CARD:
      {
         $heading = "Update a Shipping Card";
         break;
      }
      
      case View::VIEW_SHIPPING_CARD:
      default:
      {
         $heading = "View a Shipping Card";
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
      case View::NEW_SHIPPING_CARD:
      {
         $description = "Enter all required fields for your shipping card.  Once you're satisfied, click Save below to add this shipping card to the system.";
         break;
      }
         
      case View::EDIT_SHIPPING_CARD:
      {
         $description = "You may revise any of the fields for this shipping card and then select save when you're satisfied with the changes.";
         break;
      }
         
      case View::VIEW_SHIPPING_CARD:
      default:
      {
         $description = "View a previously saved shipping card in detail.";
         break;
      }
   }
   
   return ($description);
}

function getEntryDateTime()
{
   $dateTime = new DateTime();  // now
   
   if (getView() != View::NEW_SHIPPING_CARD)
   {
      $shippingCardInfo = getShippingCardInfo();
      
      if ($shippingCardInfo)
      {
         $dateTime = new DateTime($shippingCardInfo->dateTime, new DateTimeZone('America/New_York'));
      }
   }

   $entryDate = $dateTime->format(Time::$javascriptDateFormat) . "T" . $dateTime->format(Time::$javascriptTimeFormat);   
   
   return ($entryDate);
}

function getManufactureDate()
{
   $mfgDate = Time::now(Time::$javascriptDateFormat);
   
   if (getView() != View::NEW_SHIPPING_CARD)
   {
      $shippingCardInfo = getShippingCardInfo();
      
      if ($shippingCardInfo)
      {
         $dateTime = new DateTime($shippingCardInfo->manufactureDate, new DateTimeZone('America/New_York'));
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
   
   <script src="../common/common.js<?php echo versionQuery();?>"></script>
   <script src="../common/validate.js<?php echo versionQuery();?>"></script>
   <script src="shippingCard.js<?php echo versionQuery();?>"></script>

</head>

<body class="flex-vertical flex-top flex-left">
        
   <form id="input-form" action="" method="POST">
      <input id="shipping-card-id-input" type="hidden" name="shippingCardId" value="<?php echo getShippingCardId(); ?>">
      <input type="hidden" name="manufactureDate" value="<?php echo getManufactureDate(); ?>">
      <input type="hidden" name="shipper" value="<?php echo getShipper(); ?>">
      <input id="shift-time-input" type="hidden" name="shiftTime" value="<?php echo getShippingCardInfo()->shiftTime; ?>">
      <input id="shipping-time-input" type="hidden" name="shippingTime" value="<?php echo getShippingCardInfo()->shipTime; ?>">
   </form>

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(ACTIVITY); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading-with-iso"><?php echo getHeading(); ?></div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div class="iso-number">ISO <?php echo IsoInfo::getIsoNumber(IsoDoc::SHIPPING_CARD); ?></div>
         
         <div id="description" class="description"><?php echo getDescription(); ?></div>
         
         <br>
         
         <div class="flex-horizontal flex-left flex-wrap">

            <div class="flex-vertical flex-left" style="margin-right: 50px;">

               <div class="form-item">
                  <div class="form-label">Entry Date</div>
                  <input type="datetime-local" class="form-input-medium" value="<?php echo getEntryDateTime(); ?>" <?php echo getDisabled(ShippingCardInputField::ENTRY_DATE); ?>>
               </div>
               
               <div class="form-item">
                  <div class="form-label">Mfg. Date</div>
                  <input type="date" class="form-input-medium" name="manufactureDate" form="input-form" value="<?php echo getManufactureDate(); ?>" <?php echo getDisabled(ShippingCardInputField::MANUFACTURE_DATE); ?>>
               </div>
               
               <div class="form-item">
                  <div class="form-label">Shipper</div>
                  <select id="shipper-input" class="form-input-medium" name="shipper" form="input-form" oninput="this.validator.validate();" <?php echo getDisabled(ShippingCardInputField::SHIPPER); ?>>
                     <?php echo getShipperOptions(); ?>
                  </select>
               </div>
         
               <div class="form-section-header">Job</div>         
               <div class="form-item">
                  <div class="form-label">Job Number</div>
                  <select id="job-number-input" class="form-input-medium" name="jobNumber" form="input-form" oninput="this.validator.validate();" <?php echo getDisabled(ShippingCardInputField::JOB_NUMBER); ?>>
                     <?php echo getJobNumberOptions(); ?>
                  </select>
               </div>       
      
            </div>
            
            <div class="flex-vertical flex-top" style="margin-right: 50px;">
            
               <div class="form-col">
               
                  <div class="form-section-header">Time</div>
                  
                  <div class="form-item">
                     <div class="form-label">Shift time</div>
                     <input id="shift-time-hour-input" type="number" class="form-input-medium" form="input-form" name="shiftTimeHours" style="width:50px;" oninput="this.validator.validate(); onShiftTimeChange();" value="<?php echo getShippingCardInfo()->getShiftTimeHours(); ?>" <?php echo getDisabled(ShippingCardInputField::SHIFT_TIME); ?> />
                     <div style="padding: 5px;">:</div>
                     <input id="shift-time-minute-input" type="number" class="form-input-medium" form="input-form" name="shiftTimeMinutes" style="width:50px;" oninput="this.validator.validate(); onShiftTimeChange();" value="<?php echo getShippingCardInfo()->getShiftTimeMinutes(); ?>" step="15" <?php echo getDisabled(ShippingCardInputField::SHIFT_TIME); ?> />
                  </div>
                  
                  <div class="form-item">
                     <div class="form-label">Ship time</div>
                     <div class="form-row flex-left">
                        <input id="shipping-time-hour-input" type="number" class="form-input-medium" form="input-form" name="shippingTimeHours" style="width:50px;" oninput="this.validator.validate(); onShippingTimeChange();" value="<?php echo getShippingCardInfo()->getShippingTimeHours(); ?>" <?php echo getDisabled(ShippingCardInputField::SHIPPING_TIME); ?> />
                        <div style="padding: 5px;">:</div>
                        <input id="shipping-time-minute-input" type="number" class="form-input-medium" form="input-form" name="shippingTimeMinutes" style="width:50px;" oninput="this.validator.validate(); onShippingTimeChange();" value="<?php echo getShippingCardInfo()->getShippingTimeMinutes(); ?>" step="15" <?php echo getDisabled(ShippingCardInputField::SHIPPING_TIME); ?> />
                     </div>
                  </div>
                  
               </div>
               
               <div class="form-col">
                  
                  <div class="form-section-header">Part Counts</div>
                              
                  <div class="form-item">
                     <div class="form-label">Good count</div>
                     <input id="part-count-input" type="number" class="form-input-medium" form="input-form" name="partCount" style="width:100px;" oninput="partsCountValidator.validate();" value="<?php echo getShippingCardInfo()->partCount; ?>" <?php echo getDisabled(ShippingCardInputField::PART_COUNT); ?> />
                  </div>
                  
                  <div class="form-item">
                     <div class="form-label">Scrap count</div>
                     <input id="scrap-count-input" type="number" class="form-input-medium" form="input-form" name="scrapCount" style="width:100px;" oninput="scrapCountValidator.validate();" value="<?php echo getShippingCardInfo()->scrapCount; ?>" <?php echo getDisabled(ShippingCardInputField::SCRAP_COUNT); ?> />
                  </div>
                        
               </div>
               
            </div>
            
            <div class="flex-vertical flex-top" style="margin-right: 50px;">
            
               <?php echo getCommentCodesDiv(); ?>
               
               <div class="form-col">
                  <div class="form-section-header">Comments</div>
                  <div class="form-item">
                     <textarea form="input-form" id="comments-input" class="comments-input" type="text" form="input-form" name="comments" rows="4" maxlength="256" style="width:300px" <?php echo getDisabled(ShippingCardInputField::COMMENTS); ?>><?php echo getShippingCardInfo()->comments; ?></textarea>
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
   
      preserveSession();
      
      var shipperValidator = new SelectValidator("shipper-input");
      var jobNumberValidator = new SelectValidator("job-number-input");
      var shiftTimeHourValidator = new IntValidator("shift-time-hour-input", 2, 0, 16, true);
      var shiftTimeMinuteValidator = new IntValidator("shift-time-minute-input", 2, 0, 59, true);      
      var shippingTimeHourValidator = new IntValidator("shipping-time-hour-input", 2, 0, 16, true);
      var shippingTimeMinuteValidator = new IntValidator("shipping-time-minute-input", 2, 0, 59, true);
      var partsCountValidator = new IntValidator("part-count-input", 6, 0, 100000, true);
      var scrapCountValidator = new IntValidator("scrap-count-input", 6, 0, 100000, true);

      shipperValidator.init();
      jobNumberValidator.init();
      shiftTimeHourValidator.init();
      shiftTimeMinuteValidator.init();
      shippingTimeHourValidator.init();
      shippingTimeMinuteValidator.init();
      partsCountValidator.init();
      scrapCountValidator.init();

      // Setup event handling on all DOM elements.
      document.getElementById("cancel-button").onclick = function(){onCancel();};
      document.getElementById("save-button").onclick = function(){onSaveShippingCard();};      
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
      document.getElementById("menu-button").onclick = function(){document.getElementById("menu").classList.toggle('shown');};

      // Store the initial state of the form, for change detection.
      setInitialFormState("input-form");
            
   </script>

</body>

</html>