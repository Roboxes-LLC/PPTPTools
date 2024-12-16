<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/version.php';
require_once ROOT.'/core/component/part.php';
require_once ROOT.'/core/manager/customerManager.php';

abstract class InputField
{
   const FIRST = 0;
   const PPTP_NUMBER = InputField::FIRST;
   const CUSTOMER_NUMBER = 1;
   const CUSTOMER_ID = 2;
   const SAMPLE_WEIGHT = 3;
   const FIRST_PIECE_INSPECTION_TEMPLATE_ID = 4;
   const IN_PROCESS_INSPECTION_TEMPLATE_ID = 5;
   const LINE_INSPECTION_TEMPLATE_ID = 6;
   const QCP_INSPECTION_TEMPLATE_ID = 7;
   const FINAL_INSPECTION_TEMPLATE_ID = 8;
   const CUSTOMER_PRINT = 9;
   const UNIT_PRICE = 10;
   const LAST = 11;
   const COUNT = InputField::LAST - InputField::FIRST;
}

abstract class PageView
{
   const NEW = 0;
   const VIEW = 1;
   const EDIT = 2;
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
   $view = PageView::VIEW;
   
   if (getPPTPNumber() == Part::UNKNOWN_PPTP_NUMBER)
   {
      $view = PageView::NEW;
   }
   else if (Authentication::checkPermissions(Permission::EDIT_JOB))
   {
      $view = PageView::EDIT;
   }
   
   return ($view);
}

function getPPTPNumber()
{
   $pptpNumber = Part::UNKNOWN_PPTP_NUMBER;
   
   $params = getParams();
   
   if ($params->keyExists("partNumber"))
   {
      $pptpNumber = $params->get("partNumber");
   }
   
   return ($pptpNumber);
}

function getPart()
{
   static $part = null;
   
   if ($part == null)
   {
      $pptpNumber = getPPTPNumber();
      
      if ($pptpNumber != Part::UNKNOWN_PPTP_NUMBER)
      {
         $part =  Part::load($pptpNumber, Part::USE_PPTP_NUMBER);
      }
      else
      {
         $part = new Part();
      }
   }
   
   return ($part);
}

function isEditable($field)
{
   $view = getView();
   
   // Start with the edit mode, as dictated by the view.
   $isEditable = (($view == PageView::NEW) ||
                 ($view == PageView::EDIT));
   
   switch ($field)
   {
      case InputField::PPTP_NUMBER:
      {
         $isEditable = ($view == PageView::NEW);
         break;   
      }
      
      case InputField::UNIT_PRICE:
      {
         $isEditable &= Authentication::checkPermissions(Permission::VIEW_PRICES);
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

function getHeading()
{
   $heading = "";
   
   $view = getView();
   
   if ($view == PageView::NEW)
   {
      $heading = "Add a new part";
   }
   else if ($view == PageView::EDIT)
   {
      $heading = "Update an existing part";
   }
   else if ($view == PageView::VIEW)
   {
      $heading = "View a part";
   }
   
   return ($heading);
}

function getDescription()
{
   $description = "";
   
   $view = getView();
   
   if ($view == PageView::NEW)
   {
      $description = "Create a new part.";
   }
   else if ($view == PageView::EDIT)
   {
      $description = "You may revise any of the fields for this part and then select save when you're satisfied with the changes.";
   }
   else if ($view == PageView::VIEW)
   {
      $description = "View a previously saved part in detail.";
   }
   
   return ($description);
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
   
   $customerPrint = getPart()->customerPrint;
   
   $disabled = getDisabled(InputField::CUSTOMER_PRINT);
   
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

function getForm()
{
   global $getDisabled;
   
   $part = getPart();
   
   $formattedSampleWeight = $part->sampleWeight ? number_format($part->sampleWeight, 4) : null;
   
   $unitPrice = null;
   if (Authentication::checkPermissions(Permission::VIEW_PRICES))
   {
      $unitPrice = ($part->unitPrice > 0) ? number_format($part->unitPrice, 4) : null;
   }
   
   $templateOptions = [];
   foreach (InspectionType::$VALUES as $inspectionType)
   {
      $templateOptions[$inspectionType] = getInspectionTemplateOptions($inspectionType, $part->inspectionTemplateIds[$inspectionType]);
   }
   
   $customerOptions = CustomerManager::getCustomerOptions($part->customerId);
   
   $customerPrintInput = getCustomerPrintInput();
   
   $isNew = (getView() == PageView::NEW) ? "true" : "false";
   
   $path = ROOT."/uploads/$part->customerPrint";
   
   $html = 
<<< HEREDOC
   <form id="input-form" style="display: block">
      <input type="hidden" name="request" value="save_part"/>
      <input id="is-new-input" type="hidden" name="isNew" value="$isNew"/>
      <input type="hidden" name="pptpNumber" value="{$part->pptpNumber}"/>

      <div class="form-section-header">Part Info</div>

      <div class="form-item">
         <div class="form-label-long">PPTP Part #</div>
         <input id="pptp-number-input" type="text" name="pptpNumber" style="width:150px;" value="$part->pptpNumber" minLength="1" maxLength="16" required {$getDisabled(InputField::PPTP_NUMBER)}/>
      </div>

      <div class="form-item">
         <div class="form-label-long">Customer</div>
         <select id="customer-input" name="customerId" required {$getDisabled(InputField::CUSTOMER_ID)}>
            $customerOptions
         </select>
      </div>
      
      <div class="form-item">
         <div class="form-label-long">Customer Part #</div>
         <input id="customer-part-number-input" type="text" name="customerNumber" style="width:150px;" value="$part->customerNumber" minLength="1" maxLength="16" required {$getDisabled(InputField::CUSTOMER_NUMBER)}/>
      </div>

      <div class="form-item">
         <div class="form-label-long">Sample weight</div>
         <input id="sample-weight-input" type="number" name="sampleWeight" style="width:150px;" value="$formattedSampleWeight" min="0.001" max="5.000" step="0.0001"required {$getDisabled(InputField::SAMPLE_WEIGHT)}/>
      </div>

      <div class="form-item">
         <div class="form-label-long">Customer print</div>
         $customerPrintInput
      </div>

      <div class="form-section-header">Sales</div>

      <div class="form-item">
         <div class="form-label-long">Unit Price</div>
         <input id="unit-price-input" type="number" name="unitPrice" style="width:100px;" value="$unitPrice" min="0.0" step="0.0001" {$getDisabled(InputField::UNIT_PRICE)} required/>
      </div>

      <div class="form-section-header">Inspections</div>

      <div class="form-item">
         <div class="form-label-long">First Piece Template</div>
         <div><select name="firstPartTemplateId" {$getDisabled(InputField::FIRST_PIECE_INSPECTION_TEMPLATE_ID)}>{$templateOptions[InspectionType::FIRST_PART]}</select></div>
      </div>

      <div class="form-item">
         <div class="form-label-long">In Process Template</div>
         <div><select name="inProcessTemplateId" {$getDisabled(InputField::IN_PROCESS_INSPECTION_TEMPLATE_ID)}>{$templateOptions[InspectionType::IN_PROCESS]}</select></div>
      </div>

      <div class="form-item">
         <div class="form-label-long">Line Template</div>
         <div><select name="lineTemplateId" {$getDisabled(InputField::LINE_INSPECTION_TEMPLATE_ID)}>{$templateOptions[InspectionType::LINE]}</select></div>
      </div>

      <div class="form-item">
         <div class="form-label-long">QCP Template</div>
         <div><select name="qcpTemplateId" {$getDisabled(InputField::QCP_INSPECTION_TEMPLATE_ID)}>{$templateOptions[InspectionType::QCP]}</select></div>
      </div>

      <div class="form-item">
         <div class="form-label-long">Final Template</div>
         <div><select name="finalTemplateId" {$getDisabled(InputField::FINAL_INSPECTION_TEMPLATE_ID)}>{$templateOptions[InspectionType::FINAL]}</select></div>
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
$javascriptFile = "part.js";
$javascriptClass = "Part";
$appPageId = AppPage::PART;
$heading = getHeading();
$description = getDescription();
$formId = "input-form";
$form = getForm();
$customerPrintLink =  getPart()->customerPrint ? "/uploads/" . getPart()->customerPrint : null;

include ROOT.'/templates/partPageTemplate.php'
      
?>
