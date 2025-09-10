<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/core/component/part.php';
require_once ROOT.'/core/component/shipment.php';
require_once ROOT.'/core/manager/customerManager.php';
require_once ROOT.'/core/manager/jobManager.php';
require_once ROOT.'/core/manager/partManager.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/userInfo.php';
require_once ROOT.'/common/version.php';

abstract class InputField
{
   const FIRST = 0;
   const DATE = InputField::FIRST;
   const AUTHOR = 1;
   const JOB_ID = 2;
   const CUSTOMER_PART_NUMBER = 3;
   const QUANTITY = 4;
   const PACKING_LIST_NUMBER = 5;
   const PACKING_LIST = 6;
   const PPTP_PART_NUMBER = 7;
   const CUSTOMER_NAME = 8;
   const LOCATION = 9;
   const JOB_NUMBER = 10;
   const SHIPPED_DATE = 11;
   const LAST = 12;
   const COUNT = InputField::LAST - InputField::FIRST;
}

abstract class View
{
   const NEW_SHIPMENT = 0;
   const VIEW_SHIPMENT = 1;
   const EDIT_SHIPMENT = 2;
}

abstract class PackingList
{
   const VENDOR = 0;
   const CUSTOMER = 1;
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
   $view = View::VIEW_SHIPMENT;
   
   if (getShipmentId() == Shipment::UNKNOWN_SHIPMENT_ID)
   {
      $view = View::NEW_SHIPMENT;
   }
   else if (Authentication::checkPermissions(Permission::EDIT_SHIPMENT))
   {
      $view = View::EDIT_SHIPMENT;
   }
   
   return ($view);
}

function getShipmentId()
{
   $shipmentId = Shipment::UNKNOWN_SHIPMENT_ID;
   
   $params = getParams();
   
   if ($params->keyExists("shipmentId"))
   {
      $shipmentId = $params->getInt("shipmentId");
   }
   
   return ($shipmentId);
}

function getShipment()
{
   static $shipment = null;
   
   if ($shipment == null)
   {
      $shipmentId = getShipmentId();
      
      if ($shipmentId != Shipment::UNKNOWN_SHIPMENT_ID)
      {
         $shipment =  Shipment::load($shipmentId);
      }
      else
      {
         $shipment = new Shipment();
         $shipment->dateTime = Time::now();
         $shipment->author = Authentication::getAuthenticatedUser()->employeeNumber;
         $shipment->location = ShipmentLocation::WIP;
      }
   }
   
   return ($shipment);
}

function getAuthorName()
{
   $authorName = "";
   
   $userInfo = UserInfo::load(getShipment()->author);
   
   if ($userInfo)
   {
      $authorName = $userInfo->getFullName();
   }
   
   return  ($authorName);
}

function isEditable($field)
{
   $view = getView();
   
   // Start with the edit mode, as dictated by the view.
   $isEditable = (($view == View::NEW_SHIPMENT) ||
                  ($view == View::EDIT_SHIPMENT));
   
   switch ($field)
   {
      case InputField::JOB_NUMBER:
      {
         $isEditable = ($view == View::NEW_SHIPMENT);
         break;
      }
         
      case InputField::DATE:
      case InputField::AUTHOR:
      case InputField::PPTP_PART_NUMBER:
      case InputField::CUSTOMER_NAME:
      case InputField::CUSTOMER_PART_NUMBER:
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
$getDisabled = "getDisabled";  // For calling in HEREDOC.

function getHeading()
{
   $heading = "";
   
   $view = getView();
   
   if ($view == View::NEW_SHIPMENT)
   {
      $heading = "Create a new shipment";
   }
   else if ($view == View::EDIT_SHIPMENT)
   {
      $heading = "Update a shipment";
   }
   else if ($view == View::VIEW_SHIPMENT)
   {
      $heading = "View a shipment";
   }
   
   return ($heading);
}

function getDescription()
{
   $description = "";
   
   $view = getView();
   
   if ($view == View::NEW_SHIPMENT)
   {
      $description = "Create a new shipment.";
   }
   else if ($view == View::EDIT_SHIPMENT)
   {
      $description = "You may revise any of the fields for this shipment and then select save when you're satisfied with the changes.";
   }
   else if ($view == View::VIEW_SHIPMENT)
   {
      $description = "View a previously saved shipment in detail.";
   }
   
   return ($description);
}

function getPackingListInput($packingList)
{
   global $PACKING_LISTS_DIR;
      
   $packingListInput = "";
   
   $shipment = getShipment();
   
   $names = ["vendorPackingList", "customerPackingList"];
   $values = [$shipment->vendorPackingList, $shipment->customerPackingList];
   
   $disabled = getDisabled(InputField::PACKING_LIST);
   
   if ($values[$packingList])
   {
      $packingListInput =
<<<HEREDOC
         <div class="flex-vertical flex-top">
            <a href="{$PACKING_LISTS_DIR}{$values[$packingList]}" style="margin-bottom: 10px;" target="_blank">$values[$packingList]</a>
            <input type="file" name="{$names[$packingList]}}" form="input-form" $disabled>
         </div>
HEREDOC;
   }
   else
   {
      $packingListInput =
<<<HEREDOC
         <input type="file" name="{$names[$packingList]}" form="input-form" $disabled>
HEREDOC;
   }
   
   return ($packingListInput);
}

function getForm()
{
   global $getDisabled;
   
   $shipmentId = getShipmentId();
   $shipment = getShipment();
   $authorName = getAuthorName();
   $entryDate = $shipment->dateTime ? Time::dateTimeObject($shipment->dateTime)->format("n/j/Y h:i A") : null;
   $packingListInputs = [getPackingListInput(PackingList::VENDOR), getPackingListInput(PackingList::CUSTOMER)];
   $jobNumberOptions = JobManager::getJobNumberOptions($shipment->jobNumber, JobManager::ACTIVE_JOBS);
   $pptpPartNumber = JobInfo::getJobPrefix($shipment->jobNumber);
   $locationOptions = ShipmentLocation::getOptions($shipment->location);
   $quantity = ($shipment->quantity > 0) ? $shipment->quantity : null;
   $shippedDate = $shipment->shippedDate ? Time::toJavascriptDate($shipment->shippedDate) : null;
   
   $part = Part::load($pptpPartNumber, Part::USE_PPTP_NUMBER);
   
   $customerName = "";
   $customerPartNumber = "";
   if ($part)
   {
      $customerPartNumber = $part->customerNumber;
      $customerName = CustomerManager::getCustomerName($part->customerId);
   }
   
   $html = 
<<< HEREDOC
   <form id="input-form" style="display: block">
      <input id="shipment-id-input" type="hidden" name="shipmentId" value="$shipmentId"/>
      <input type="hidden" name="request" value="save_shipment"/>
   
      <div class="form-item">
         <div class="form-label">Author</div>
         <input type="text" name="author" style="width:200px;" value="$authorName" {$getDisabled(InputField::AUTHOR)} />
      </div>

      <div class="form-item">
         <div class="form-label">Entry Date</div>
         <input type="text" style="width:200px;" value="$entryDate" disabled/>
      </div>

      <div class="form-item">
         <div class="form-label-long">Job #</div>
         <select id="job-number-input" name="jobNumber" {$getDisabled(InputField::JOB_NUMBER)} required>
            $jobNumberOptions
         </select>
      </div>

      <div class="form-section-header">Part Info</div>

      <div class="form-item">
         <div class="form-label-long">PPTP Part #</div>
         <input id="pptp-part-number-input" type="text" value="$pptpPartNumber" {$getDisabled(InputField::PPTP_PART_NUMBER)}/>
      </div>

      <div class="form-item">
         <div class="form-label-long">Customer</div>
         <input id="customer-name-input" type="text" value="$customerName" {$getDisabled(InputField::CUSTOMER_NAME)}/>            
      </div>

      <div class="form-item">
         <div class="form-label-long">Customer Part #</div>
         <input id="customer-part-number-input" type="text" value="$customerPartNumber" {$getDisabled(InputField::CUSTOMER_PART_NUMBER)}/>            
      </div>

      <div class="form-item">
         <div class="form-label-long">Quantity</div>
         <input id="address-line-1-input" type="number" name="quantity" style="width:100px;" value="$quantity" min="1" max="1000000" {$getDisabled(InputField::QUANTITY)} required/>
      </div>

      <div class="form-section-header">Tracking</div>

      <div class="form-item">
         <div class="form-label">Location</div>
         <div class="flex-horizontal">
            <select name="location" {$getDisabled(InputField::LOCATION)} required>
               $locationOptions
            </select>
         </div>
      </div>

      <div class="form-item">
         <div class="form-label">Packing #</div>
         <input id="address-line-1-input" type="text" name="packingListNumber" maxlength="32" style="width:150px;" value="{$shipment->packingListNumber}" {$getDisabled(InputField::PACKING_LIST_NUMBER)} />
      </div>

      <div class="form-item">
         <div class="form-label-long">Vendor Packing List</div>
         {$packingListInputs[PackingList::VENDOR]}
      </div>

      <div class="form-item">
         <div class="form-label-long">Customer Packing List</div>
         {$packingListInputs[PackingList::CUSTOMER]}
      </div>

      <div class="form-item">
         <div class="form-label">Shipped Date</div>
         <input id="shipped-date-input" type="date" name="shippedDate" value="$shippedDate" {$getDisabled(InputField::SHIPPED_DATE)} />
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
$javascriptFile = "shipment.js";
$javascriptClass = "Shipment";
$appPageId = AppPage::SHIPMENT;
$heading = getHeading();
$description = getDescription();
$formId = "input-form";
$form = getForm();

include ROOT.'/templates/shipmentPageTemplate.php'
      
?>
