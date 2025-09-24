<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/core/component/shipment.php';
require_once ROOT.'/core/manager/customerManager.php';
require_once ROOT.'/core/manager/shipmentManager.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/version.php';

abstract class InputField
{
   const FIRST = 0;
   const CUSTOMER_PART_NUMBER = 1;
   const PARENT_QUANTITY = 2;
   const CHILD_QUANTITY = 3;
   const PPTP_PART_NUMBER = 4;
   const CUSTOMER_NAME = 5;
   const PARENT_LOCATION = 6;
   const CHILD_LOCATION = 7;
   const LAST = 8;
   const COUNT = InputField::LAST - InputField::FIRST;
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
      $shipment = Shipment::load(getShipmentId());
   }
   
   return ($shipment);
}

function isEditable($field)
{
   $isEditable = false;
   
   switch ($field)
   {
      case InputField::CHILD_QUANTITY:
      case InputField::CHILD_LOCATION:
      {
         $isEditable = true;
         break;
      }
         
      default:
      {
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

function getForm()
{
   global $getDisabled;
   
   $shipmentId = getShipmentId();
   $shipment = getShipment();
   
   $parentTicketCode = ShipmentManager::getShipmentTicketCode($shipmentId);
   $childTicketCode = ShipmentManager::getNextChildTicketCode($shipmentId);
   
   $pptpPartNumber = JobInfo::getJobPrefix($shipment->jobNumber);
   $locationOptions = ShipmentLocation::getOptions($shipment->location);
   $quantity = $shipment->quantity;
   
   $part = Part::load($pptpPartNumber, Part::USE_PPTP_NUMBER);
   
   $customerName = "";
   $customerPartNumber = "";
   if ($part)
   {
      $customerPartNumber = $part->customerNumber;
      $customerName = CustomerManager::getCustomerName($part->customerId);
   }
   
   $quantity = $shipment->quantity;   
   
   $html =
<<< HEREDOC
   <form id="input-form" style="display: block">
      <input id="shipment-id-input" type="hidden" name="shipmentId" value="$shipmentId"/>
      <input type="hidden" name="request" value="split_shipment"/>
      <input id="quantity-input" type="hidden" value="$quantity"/>

      <div class="flex-horizontal">

         <div class="flex-vertical" style="margin-right:50px">
            <div class="form-section-header">$parentTicketCode</div>
   
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
               <input id="parent-quantity-input" type="number" style="width:100px;" value="$quantity" min="1" max="1000000" {$getDisabled(InputField::PARENT_QUANTITY)} required/>
            </div>
      
            <div class="form-item">
               <div class="form-label-long">Location</div>
               <div class="flex-horizontal">
                  <select {$getDisabled(InputField::PARENT_LOCATION)} required>
                     $locationOptions
                  </select>
               </div>
            </div>
         </div>

         <div class="flex-vertical flex-v-center" style="margin-right:50px; height:auto">
            <i class="material-icons split-icon">arrow_forward</i>
         </div>

   
         <div class="flex-vertical" >
            <div class="form-section-header">$childTicketCode</div>

            <div class="form-item">
               <div class="form-label-long">PPTP Part #</div>
               <input type="text" value="$pptpPartNumber" {$getDisabled(InputField::PPTP_PART_NUMBER)}/>
            </div>
      
            <div class="form-item">
               <div class="form-label-long">Customer</div>
               <input type="text" value="$customerName" {$getDisabled(InputField::CUSTOMER_NAME)}/>            
            </div>
      
            <div class="form-item">
               <div class="form-label-long">Customer Part #</div>
               <input type="text" value="$customerPartNumber" {$getDisabled(InputField::CUSTOMER_PART_NUMBER)}/>            
            </div>
      
            <div class="form-item">
               <div class="form-label-long">Quantity</div>
               <input id="child-quantity-input" type="number" name="childQuantity" style="width:100px;" value="0" min="1" max="1000000" {$getDisabled(InputField::CHILD_QUANTITY)} required/>
            </div>
      
            <div class="form-item">
               <div class="form-label-long">Location</div>
               <div class="flex-horizontal">
                  <select name="childLocation" {$getDisabled(InputField::CHILD_LOCATION)} required>
                     $locationOptions
                  </select>
               </div>
            </div>
         </div>

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
$heading = "Split Shipment";
$description = "Divide a shipment into two distinct inventory entries";
$formId = "input-form";
$form = getForm();

include ROOT.'/templates/formPageTemplate.php'
      
?>
