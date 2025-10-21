<?php 

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/inspection.php';
require_once ROOT.'/common/isoInfo.php';
require_once ROOT.'/common/panTicket.php';
require_once ROOT.'/common/params.php';
require_once ROOT.'/common/timeCardInfo.php';
require_once ROOT.'/common/version.php';
require_once ROOT.'/inspection/inspectionTable.php';

abstract class InspectionInputField
{
   const FIRST = 0;
   const INSPECTION_TYPE = InspectionInputField::FIRST;
   const INSPECTION_TEMPLATE = 1;
   const INSPECTION_NUMBER = 2;
   const JOB_NUMBER = 3;
   const WC_NUMBER = 4;
   const INSPECTOR = 5;
   const OPERATOR = 6;
   const INSPECTION = 7;
   const COMMENTS = 8;
   const START_MFG_DATE = 9;
   const MFG_DATE = 10;
   const QUANTITY = 11;
   const AUTHOR = 12;
   const IS_PRIORITY = 13;
   const LAST = 14;
   const COUNT = InspectionInputField::LAST - InspectionInputField::FIRST;
}

abstract class View
{
   const NEW_INSPECTION = 0;
   const VIEW_INSPECTION = 1;
   const EDIT_INSPECTION = 2;
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
   $view = View::VIEW_INSPECTION;
   
   $inspection = getInspection();
   
   if (getInspectionId() == Inspection::UNKNOWN_INSPECTION_ID)
   {
      $view = View::NEW_INSPECTION;
   }
   else if (Authentication::checkPermissions(Permission::EDIT_INSPECTION) &&
            // Only allow editing of your own inspections, or if the VIEW_OTHERS permission is enabled.
            (($inspection->inspector == Authentication::getAuthenticatedUser()->employeeNumber) ||
             Authentication::checkPermissions(Permission::VIEW_OTHER_USERS)))
   {
      $view = View::EDIT_INSPECTION;
   }
   
   return ($view);
}

function isEditable($field)
{
   $view = getView();
   
   // Start with the edit mode, as dictated by the view.
   $isEditable = (($view == View::NEW_INSPECTION) ||
                  ($view == View::EDIT_INSPECTION));
   
   // Disabled editing jobNumber, wcNumber, operator, and mfgDate if the inspection has
   // a linked timecard.
   $hasLinkedTimeCard = (getTimeCardId() != TimeCardInfo::UNKNOWN_TIME_CARD_ID);
   
   switch ($field)
   {
      case InspectionInputField::INSPECTION_TYPE:
      case InspectionInputField::INSPECTION_TEMPLATE:
      {
         $isEditable = false;
         break;
      }
      
      case InspectionInputField::INSPECTION_NUMBER:
      {
         $isEditable &= showOptionalProperty(OptionalInspectionProperties::INSPECTION_NUMBER);
         break;
      }
      
      case InspectionInputField::JOB_NUMBER:
      {
         $isSpecified = ($hasLinkedTimeCard || (getJobNumber() != JobInfo::UNKNOWN_JOB_NUMBER));
         
         $isEditable &= (!$isSpecified && showOptionalProperty(OptionalInspectionProperties::JOB_NUMBER));
         break;
      }
      
      case InspectionInputField::WC_NUMBER:
      {
         $isSpecified = ($hasLinkedTimeCard || (getWcNumber() != JobInfo::UNKNOWN_WC_NUMBER));
         
         $isEditable &= (!$isSpecified && showOptionalProperty(OptionalInspectionProperties::WC_NUMBER));
         break;
      }
      
      case InspectionInputField::OPERATOR:
      {
         $isSpecified = ($hasLinkedTimeCard || (getOperator() != UserInfo::UNKNOWN_EMPLOYEE_NUMBER));
         
         $isEditable &= (!$isSpecified && showOptionalProperty(OptionalInspectionProperties::OPERATOR));
         break;
      }
      
      case InspectionInputField::START_MFG_DATE:
      {
         $isSpecified = (getStartMfgDate() != null);
         
         $isFinalInspection = (getInspectionType() == InspectionType::FINAL);
         
         $isEditable &= ($isFinalInspection || (!$isSpecified && showOptionalProperty(OptionalInspectionProperties::START_MFG_DATE)));
         break;
      }
      
      case InspectionInputField::MFG_DATE:
      {
         $isSpecified = ($hasLinkedTimeCard || (getMfgDate() != null));
         
         $isFinalInspection = (getInspectionType() == InspectionType::FINAL);
         
         $isEditable &= ($isFinalInspection || (!$isSpecified && showOptionalProperty(OptionalInspectionProperties::MFG_DATE)));
         break;
      }
      
      case InspectionInputField::QUANTITY:
      {
         $isEditable &= showOptionalProperty(OptionalInspectionProperties::QUANTITY);
         break;
      }
      
      case InspectionInputField::IS_PRIORITY:
      {
         $isEditable &= showOptionalProperty(OptionalInspectionProperties::IS_PRIORITY);
         break;
      }
      
      case InspectionInputField::AUTHOR:
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

function getHidden($optionalProperty)
{
   return (showOptionalProperty($optionalProperty) ? "" : "hidden");
}

function getDisabled($field)
{
   return (isEditable($field) ? "" : "disabled");
}

function getInspectionId()
{
   $params = getParams();
   
   return ($params->keyExists("inspectionId") ? $params->get("inspectionId") : Inspection::UNKNOWN_INSPECTION_ID);
}

function getInspection()
{
   static $inspection = null;
   
   if ($inspection == null)
   {
      $inspectionId = getInspectionId();
      
      if ($inspectionId != Inspection::UNKNOWN_INSPECTION_ID)
      {
         $inspection = Inspection::load($inspectionId, true);  // Load actual results.
      }
      else
      {
         $inspection = getNewInspection();
      }
   }
   
   return ($inspection);
}

function getNewInspection()
{
   $inspection = new Inspection();
   
   $params = getParams();
      
   $inspection->templateId = 
      ($params->keyExists("templateId") ? $params->get("templateId") : Inspection::UNKNOWN_INSPECTION_ID);
      
   $userInfo = Authentication::getAuthenticatedUser();
   if ($userInfo)
   {
      $inspection->author = $userInfo->employeeNumber;
      $inspection->inspector = $userInfo->employeeNumber;
   }
   
   $timeCardId = getTimeCardId();
   
   if (getTimeCardId() != TimeCardInfo::UNKNOWN_TIME_CARD_ID)
   {
      $inspection->timeCardId = $timeCardId;
   }
   else
   {
      $jobNumber =
         ($params->keyExists("jobNumber") ? $params->get("jobNumber") : JobInfo::UNKNOWN_JOB_NUMBER);
      
      $wcNumber =
         ($params->keyExists("wcNumber") ? $params->get("wcNumber") : 0);
   }
   
   if ($inspection->templateId != InspectionTemplate::UNKNOWN_TEMPLATE_ID)
   {
      $inspectionTemplate = InspectionTemplate::load($inspection->templateId, true);  // Load properties.
      
      if ($inspectionTemplate)
      {
         $sampleSize = $inspection->getSampleSize();
         
         foreach ($inspectionTemplate->inspectionProperties as $inspectionProperty)
         {
            for ($sampleIndex = 0; $sampleIndex < $sampleSize; $sampleIndex++)
            {
               $inspectionResult = new InspectionResult();
               $inspectionResult->propertyId = $inspectionProperty->propertyId;
               $inspectionResult->status = InspectionStatus::NON_APPLICABLE;
               
               if (!isset($inspection->inspectionResults[$inspectionResult->propertyId]))
               {
                  $inspection->inspectionResults[$inspectionResult->propertyId] = array();
               }
               
               $inspection->inspectionResults[$inspectionProperty->propertyId][$sampleIndex] = $inspectionResult;
            }
         }
      }
   }
   
   // Final inspections don't really have a manufacture date (they span multiple dates) but for filtering purposes, initialize it
   // to the inspection creation date.
   if ($inspectionTemplate->inspectionType == InspectionType::FINAL)
   {
      $inspection->mfgDate = Time::startOfDay(Time::now());
   }
 
   return ($inspection);
}

function getTemplateId()
{
   $templateId = InspectionTemplate::UNKNOWN_TEMPLATE_ID;
   
   $inspectionId = getInspectionId();
   
   if ($inspectionId != Inspection::UNKNOWN_INSPECTION_ID)
   {
      $inspection = getInspection();
      
      if ($inspection)
      {
         $templateId = $inspection->templateId;
      }
   }
   else
   {
      $params = getParams();
      
      $templateId = ($params->keyExists("templateId") ? $params->get("templateId") : Inspection::UNKNOWN_INSPECTION_ID);
   }

   return ($templateId);
}

function getInspectionTemplate()
{
   static $inspectionTemplate = null;
   
   if ($inspectionTemplate == null)
   {
      $templateId = getTemplateId();
      
      if ($templateId != Inspection::UNKNOWN_INSPECTION_ID)
      {
         $inspectionTemplate = InspectionTemplate::load($templateId, true);  // Load properties.
      }
   }
   
   return ($inspectionTemplate);
}

function getInspectionTemplateName()
{
   $name = "";
   
   $inspectionTemplate = getInspectionTemplate();
   
   if ($inspectionTemplate)
   {
      $name = $inspectionTemplate->name;
   }
   
   return ($name);
}

function getInspectionType()
{
   $inspectionType = InspectionType::UNKNOWN;
   
   $inspectionTemplate = getInspectionTemplate();
   
   if ($inspectionTemplate)
   {
      $inspectionType = $inspectionTemplate->inspectionType;
   }
   else
   {
      $params = getParams();
      
      $inspectionType =
         ($params->keyExists("inspectionType") ? $params->getInt("inspectionType") : InspectionType::UNKNOWN);
   }
   
   return ($inspectionType);
}

function showOptionalProperty($optionalProperty)
{
   $showOptionalProperty = false;
   
   $inspectionTemplate = getInspectionTemplate();
   
   if ($inspectionTemplate)
   {
      $showOptionalProperty = $inspectionTemplate->isOptionalPropertySet($optionalProperty);
   }
   
   return ($showOptionalProperty);
}

function getInspectionNumber()
{
   $inspectionNumber = 0;
   
   $inspection = getInspection();
   
   if ($inspection)
   {
      $inspectionNumber = $inspection->inspectionNumber;
   }
   
   return ($inspectionNumber);
}

function getTimeCardId()
{
   $timeCardId = TimeCardInfo::UNKNOWN_TIME_CARD_ID;
   
   $params = getParams();
   
   if ($params->keyExists("panTicketCode"))
   {
      $panTicketCode = $params->get("panTicketCode");

      $timeCardId = PanTicket::getPanTicketId($panTicketCode);
   }
   
   return ($timeCardId);
}

function getJobNumber()
{
   $jobNumber = JobInfo::UNKNOWN_JOB_NUMBER;
   
   if (getInspectionId() != Inspection::UNKNOWN_INSPECTION_ID)
   {
      $inspection = getInspection();

      if ($inspection)
      {
         $jobInfo = JobInfo::load($inspection->getJobId());
         
         if ($jobInfo)
         {
            $jobNumber = $jobInfo->jobNumber;
         }
         else
         {
            $jobNumber = $inspection->jobNumber;
         }
      }
   }
   else
   {
      $params = getParams();
      
      if ($params->keyExists("jobNumber"))
      {
         $jobNumber = $params->get("jobNumber");
      }
      else
      {
         $timeCardId = getTimeCardId();
         
         if ($timeCardId != TimeCardInfo::UNKNOWN_TIME_CARD_ID)
         {
            $timeCardInfo = TimeCardInfo::load($timeCardId);
            
            if ($timeCardInfo)
            {
               $job = JobInfo::load($timeCardInfo->jobId);
               
               if ($job)
               {
                  $jobNumber = $job->jobNumber;
               }
            }
         }
      }
   }
   
   return ($jobNumber);
}

function getWcNumber()
{
   $wcNumber = JobInfo::UNKNOWN_WC_NUMBER;
   
   if (getInspectionId() != Inspection::UNKNOWN_INSPECTION_ID)
   {
      $inspection = getInspection();
      
      if ($inspection)
      {
         $jobInfo = JobInfo::load($inspection->getJobId());
         
         if ($jobInfo)
         {
            $wcNumber = $jobInfo->wcNumber;
         }
         else
         {
            $wcNumber = $inspection->wcNumber;
         }
      }
   }
   else
   {
      $params = getParams();
      
      if ($params->keyExists("wcNumber"))
      {
         $wcNumber = $params->get("wcNumber");
      }
      else
      {
         $timeCardId = getTimeCardId();
         
         if ($timeCardId != TimeCardInfo::UNKNOWN_TIME_CARD_ID)
         {
            $timeCardInfo = TimeCardInfo::load($timeCardId);
            
            if ($timeCardInfo)
            {
               $job = JobInfo::load($timeCardInfo->jobId);
               
               if ($job)
               {
                  $wcNumber = $job->wcNumber;
               }
            }
         }
      }
   }
   
   return ($wcNumber);
}

function getStartMfgDate()
{
   $startMfgDate = null;
   
   $inspection = getInspection();
   
   if ($inspection && $inspection->startMfgDate)
   {
      
      // Convert to Javascript date format.
      $startMfgDate = Time::toJavascriptDate($inspection->startMfgDate);
   }
   
   return ($startMfgDate);
}

function getMfgDate()
{
   $mfgDate = null;
   
   $inspection = getInspection();
   
   if ($inspection && $inspection->getManufactureDate())
   {
      // Convert to Javascript date format.
      $mfgDate = Time::toJavascriptDate($inspection->getManufactureDate());
   }
   else if ($timeCardInfo = TimeCardInfo::load(getTimeCardId()))
   {
      // Convert to Javascript date format.
      $mfgDate = Time::toJavascriptDate($timeCardInfo->manufactureDate);
   }
   
   return ($mfgDate);
}

function getQuantity()
{
   $quantity = null;
   
   $inspection = getInspection();
   
   if ($inspection && $inspection->quantity > 0)
   {
      $quantity = $inspection->quantity;
   }
   
   return ($quantity);
}

function getCustomerPrint()
{
   $customerPrint = "";
   
   $jobId = getInspection()->getJobId();
   $jobNumber = getJobNumber();
   
   if ($jobId != JobInfo::UNKNOWN_JOB_ID)
   {
      $jobInfo = JobInfo::load($jobId);
      
      if ($jobInfo)
      {
         $customerPrint = $jobInfo->part->customerPrint;
      }
   }
   else if ($jobNumber != JobInfo::UNKNOWN_JOB_NUMBER)
   {
      $customerPrint = JobInfo::getCustomerPrint($jobNumber);
   }
   
   return ($customerPrint);
}

function getInspector()
{
   $inspector = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
   
   $inspection = getInspection();
   
   if ($inspection)
   {
      $inspector = $inspection->inspector;
   }
   
   return ($inspector);
}

function getOperator()
{
   $operator = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
   
   $inspection = getInspection();

   if (getInspectionId() != Inspection::UNKNOWN_INSPECTION_ID)
   {
      $inspection = getInspection();
      
      if ($inspection)
      {
         $operator = $inspection->getOperator();
      }
   }
   else
   {
      $timeCardId = getTimeCardId();
      
      if ($timeCardId != TimeCardInfo::UNKNOWN_TIME_CARD_ID)
      {
         $timeCardInfo = TimeCardInfo::load($timeCardId);
         
         if ($timeCardInfo)
         {
            $operator = $timeCardInfo->employeeNumber;
         }
      }
   }
   
   return ($operator);
}

function getNotes()
{
   $notes = "";
   
   $inspectionTemplate = getInspectionTemplate();
   
   if ($inspectionTemplate)
   {
      $notes = $inspectionTemplate->notes;
   }
   
   return ($notes);
}

function getComments()
{
   $comments = "";
   
   $inspection = getInspection();
   
   if ($inspection)
   {
      $comments = $inspection->comments;
   }
   
   return ($comments);
}

function getHeading()
{
   $heading = "";
   
   switch (getView())
   {
      case View::NEW_INSPECTION:
      {
         $heading = "Add a New Inspection";
         break;
      }
         
      case View::EDIT_INSPECTION:
      {
         $heading = "Update an Inspection";
         break;
      }
         
      case View::VIEW_INSPECTION:
      {
         $heading = "View an Inspection";
         break;
      }
         
      default:
      {
         break;
      }
   }
   
   return ($heading);
}

function getIso()
{
   $iso = "";
   
   switch (getInspectionType())
   {
      case InspectionType::FIRST_PART:
      {
         $iso = IsoInfo::getIsoNumber(IsoDoc::FIRST_PART_INSPECTION);
         break;
      }
      
      case InspectionType::IN_PROCESS:
      {
         $iso = IsoInfo::getIsoNumber(IsoDoc::IN_PROCESS_INSPECTION);
         break;
      }
      
      case InspectionType::QCP:
      {
         $iso = IsoInfo::getIsoNumber(IsoDoc::QCP_INSPECTION);
         break;
      }
      
      case InspectionType::LINE:
      {
         $iso = IsoInfo::getIsoNumber(IsoDoc::LINE_INSPECTION);
         break;
      }
      
      case InspectionType::FINAL:
      {
         $iso = IsoInfo::getIsoNumber(IsoDoc::FINAL_INSPECTION);
         break;
      }
      
      case InspectionType::OASIS:
      default:
      {
         $iso = "undefined";
         break;
      }
   }
   
   return ($iso);
}

function getDescription()
{
   $description = "";
   
   switch (getView())
   {
      case View::NEW_INSPECTION:
         {
            $description = "Next, select the operator responsible for the targeted part inspection.  If any of the categories are not relevant to the part you're inspecting, just leave it set to \"N/A\"";
            break;
         }
         
      case View::EDIT_INSPECTION:
         {
            $description = "You may revise any of the fields for this inspection and then select save when you're satisfied with the changes.";
            break;
         }
         
      case View::VIEW_INSPECTION:
         {
            $description = "View a previously saved inspection in detail.";
            break;
         }
         
      default:
         {
            break;
         }
   }
   
   return ($description);
}

function getJobNumberOptions()
{
   $options = "<option style=\"display:none\">";
   
   $jobNumbers = JobInfo::getJobNumbers(true);  // only active
   
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

function getAuthorOptions()
{
   $options = "<option style=\"display:none\">";
   
   $userInfo = UserInfo::load(getInspection()->author);
   
   if ($userInfo)
   {
      $label = $userInfo->getFullName();
      $value = $userInfo->employeeNumber;
      $selected = "selected";
      
      $options .= "<option value=\"$value\" $selected>$label</option>";
   }
   
   return ($options);
}

function getInspectorOptions()
{
   // Multiple roles requested by customer in 10/2023.
   $inspectorRoles = [Role::INSPECTOR, Role::ADMIN, Role::SUPER_USER];
   
   $options = UserManager::getOptions($inspectorRoles, [], getInspector());
   
   return ($options);
}

function getOperatorOptions()
{
   return (UserManager::getOptions([Role::OPERATOR], [], getOperator()));
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
   <link rel="stylesheet" type="text/css" href="inspection.css<?php echo versionQuery();?>"/>
   
   <script src="/common/common.js<?php echo versionQuery();?>"></script>
   <script src="/common/validate.js<?php echo versionQuery();?>"></script>
   <script src="/script/common/common.js<?php echo versionQuery();?>"></script> 
   <script src="/script/common/commonDefs.php<?php echo versionQuery();?>"></script> 
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script> 
   <script src="inspection.js<?php echo versionQuery();?>"></script>

</head>

<body class="flex-vertical flex-top flex-left">
        
   <form id="input-form" action="" method="POST">
      <input id="inspection-id-input" type="hidden" name="inspectionId" value="<?php echo getInspectionId(); ?>">
      <!-- Hidden inputs make sure disabled fields below get posted. -->
      <input id="template-id-input" type="hidden" name="templateId" value="<?php echo getTemplateId(); ?>">
      <input type="hidden" name="author" value="<?php echo getInspection()->author; ?>">
      <input type="hidden" name="timeCardId" value="<?php echo getTimeCardId(); ?>">
      <input type="hidden" name="jobNumber" value="<?php echo getJobNumber(); ?>">
      <input type="hidden" name="wcNumber" value="<?php echo getWcNumber(); ?>">
   </form>

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading-with-iso"><?php echo getHeading(); ?></div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div class="iso-number">ISO <?php echo getIso(); ?></div>
         
         <div id="description" class="description"><?php echo getDescription(); ?></div>
         
         <br>
         
         <div class="flex-column">
         
            <div class="flex-row" style="justify-content: space-evenly;">

               <div class="flex-column">
               
                  <div class="form-item">
                     <div class="form-label">Inspection Type</div>
                     <select id="inspection-type-input" class="form-input-medium" name="inspectionType" form="input-form" oninput="" <?php echo getDisabled(InspectionInputField::INSPECTION_TYPE) ?>>
                         <?php echo getInspectionTypeOptions(getInspectionType(), false, [InspectionType::OASIS]); ?>
                     </select>
                  </div>
                  
                  <div class="form-item optional-property-container <?php echo getHidden(OptionalInspectionProperties::IS_PRIORITY) ?>">
                     <div class="form-label">Priority</div>
                     <input id="is-priority-input" name="isPriority" form="input-form" type="checkbox" <?php echo getInspection()->isPriority ? "checked" : "" ?> <?php echo getDisabled(InspectionInputField::IS_PRIORITY) ?>>
                  </div>
                  
                  <div class="form-item">
                     <div class="form-label">Template</div>
                     <select class="form-input-medium" name="inspectionType" form="input-form" oninput="" <?php echo getDisabled(InspectionInputField::INSPECTION_TEMPLATE) ?>>
                         <option><?php echo getInspectionTemplateName(); ?></option>
                     </select>
                  </div>
                  
                  <div class="form-item optional-property-container <?php echo getHidden(OptionalInspectionProperties::INSPECTION_NUMBER) ?>">
                     <div class="form-label">Inspection #</div>
                     <select id="inspection-number-input" class="form-input-medium" name="inspectionNumber" form="input-form" <?php echo getDisabled(InspectionInputField::INSPECTION_NUMBER) ?>>
                        <?php echo getInspectionNumberOptions(getInspectionNumber()); ?>
                     </select>
                  </div>
                  
                  <div class="form-item">
                     <div class="form-label">Created By</div>
                     <select id="author-input" class="form-input-medium" name="author" form="input-form" <?php echo getDisabled(InspectionInputField::AUTHOR) ?>>
                        <?php echo getAuthorOptions(); ?>
                     </select>
                  </div>
                  
                  <div class="form-item">
                     <div class="form-label">Inspector</div>
                     <select id="inspector-input" class="form-input-medium" name="inspector" form="input-form" <?php echo getDisabled(InspectionInputField::INSPECTOR) ?>>
                        <?php echo getInspectorOptions(); ?>
                     </select>
                  </div>
         
                  <div class="form-item optional-property-container <?php echo getHidden(OptionalInspectionProperties::JOB_NUMBER) ?>">
                     <div class="form-label">Job Number</div>
                     <select id="job-number-input" class="form-input-medium" name="jobNumber" form="input-form" oninput="onJobNumberChange();" <?php echo getDisabled(InspectionInputField::JOB_NUMBER) ?>>
                         <?php echo getJobNumberOptions(); ?>
                     </select>
                     &nbsp;&nbsp;
                     <div id="customer-print-div"><a href="<?php $ROOT ?>/uploads/<?php echo getCustomerPrint(); ?>" target="_blank"><?php echo getCustomerPrint(); ?></a></div>
                  </div>
         
                  <div class="form-item optional-property-container <?php echo getHidden(OptionalInspectionProperties::WC_NUMBER) ?>">
                     <div class="form-label">WC Number</div>
                     <select id="wc-number-input" class="form-input-medium" name="wcNumber" form="input-form" <?php echo getDisabled(InspectionInputField::WC_NUMBER) ?>>
                        <?php echo JobInfo::getWcNumberOptions(getJobNumber(), getWcNumber()); ?>
                     </select>
                  </div>
                  
                  <div class="form-item optional-property-container <?php echo getHidden(OptionalInspectionProperties::OPERATOR) ?>">
                     <div class="form-label">Operator</div>
                     <select id="operator-input" class="form-input-medium" name="operator" form="input-form" <?php echo getDisabled(InspectionInputField::OPERATOR) ?>>
                        <?php echo getOperatorOptions(); ?>
                     </select>
                  </div>
                  
                  <div class="form-item optional-property-container <?php echo getHidden(OptionalInspectionProperties::START_MFG_DATE) ?>">
                     <div class="form-label">Start Mfg Date</div>
                     <input id="start-mfg-date-input" type="date" name="startMfgDate" form="input-form" value="<?php echo getStartMfgDate() ?>" <?php echo getDisabled(InspectionInputField::START_MFG_DATE) ?>>
                     &nbsp;&nbsp;
                     <button class="small-button today-button" data-inputfield="start-mfg-date-input">Today</button>
                     &nbsp;&nbsp;
                     <button class="small-button yesterday-button" data-inputfield="start-mfg-date-input">Yesterday</button>
                  </div>
                  
                  <div class="form-item optional-property-container <?php echo getHidden(OptionalInspectionProperties::MFG_DATE) ?>">
                     <div class="form-label"><?php echo getInspectionType() == InspectionType::FINAL ? "End Mfg Date" : "Mfg Date" ?></div>
                     <input id="mfg-date-input" type="date" name="mfgDate" form="input-form" value="<?php echo getMfgDate() ?>" <?php echo getDisabled(InspectionInputField::MFG_DATE) ?>>
                     &nbsp;&nbsp;
                     <button class="small-button today-button" data-inputfield="mfg-date-input">Today</button>
                     &nbsp;&nbsp;
                     <button class="small-button yesterday-button" data-inputfield="mfg-date-input">Yesterday</button>
                  </div>
                                    
                  <div class="form-item optional-property-container <?php echo getHidden(OptionalInspectionProperties::QUANTITY) ?>">
                     <div class="form-label">Quantity</div>
                     <input id="quantity-input" type="number" class="form-input-small" name="quantity" form="input-form" style="width:75px" value="<?php echo getQuantity() ?>" <?php echo getDisabled(InspectionInputField::QUANTITY) ?>>
                  </div>
                  
                  <div class="form-item" style="display: <?php echo (getNotes() == "") ? "none" : "flex"; ?>">
                     <div class="form-label">Notes</div>
                     <textarea id="notes-input" style="width: 250px" disabled><?php echo getNotes(); ?></textarea>
                  </div>
                  
                  <div class="form-item">
                     <?php echo InspectionTable::getHtml(getInspection()->inspectionId, getInspection()->templateId, getInspection()->quantity, Authentication::checkPermissions(Permission::QUICK_INSPECTION), isEditable(InspectionInputField::INSPECTION)) ?>
                  </div>
            
                  <div class="form-item">
                     <textarea form="input-form" class="comments-input" type="text" name="comments" placeholder="Enter comments ..." <?php echo getDisabled(InspectionInputField::COMMENTS) ?>><?php echo getComments(); ?></textarea>
                  </div>
         
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
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::INSPECTION ?>); 
      
      preserveSession();
      
      // Resize notes text area to fit text.
      var notes = document.getElementById('notes-input');
      notes.style.height = notes.scrollHeight + "px";
   
      var jobNumberValidator = new SelectValidator("job-number-input");
      var wcNumberValidator = new SelectValidator("wc-number-input");
      var operatorValidator = new SelectValidator("operator-input");
      var mfgDateValidator = new DateValidator("mfg-date-input");
      var startMfgDateValidator = new DateValidator("start-mfg-date-input");
      var quantityValidator = new IntValidator("quantity-input", 6, 1, 999999, false);
      var inspectionNumberValidator = new SelectValidator("inspection-number-input");

      jobNumberValidator.init();
      wcNumberValidator.init();
      operatorValidator.init();
      mfgDateValidator.init();
      startMfgDateValidator.init();
      quantityValidator.init();
      inspectionNumberValidator.init();

      // Setup event handling on all DOM elements.
      document.getElementById("cancel-button").onclick = function(){location.href = "viewInspections.php";};
      document.getElementById("save-button").onclick = function(){onSaveInspection();};      
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
      document.getElementById("quantity-input").onchange = function(){onQuantityChanged();};
      for (let button of document.getElementsByClassName("today-button"))
      {
         button.onclick = function(event){onTodayButton(event.target);};
      }
      for (let button of document.getElementsByClassName("yesterday-button"))
      {
         button.onclick = function(event){onYesterdayButton(event.target);};
      }      
                        
   </script>

</body>

</html>