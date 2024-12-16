<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/core/component/salesOrder.php';
require_once ROOT.'/core/manager/customerManager.php';
require_once ROOT.'/core/manager/partManager.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/version.php';

abstract class InputField
{
   const FIRST = 0;
   const AUTHOR = InputField::FIRST;
   const DATE = 1;
   const ORDER_NUMBER = 2;
   const CUSTOMER_NAME = 3;
   const CUSTOMER_PART_NUMBER = 4;
   const PO_NUMBER = 5;
   const ORDER_DATE = 6;
   const QUANTITY = 7;
   const UNIT_PRICE = 8;
   const DUE_DATE = 9;
   const ORDER_STATUS = 10;
   const COMMENTS = 11;
   const PPTP_PART_NUMBER = 12;
   const TOTAL = 13;
   const PACKING_LIST = 14;
   const LAST = 15;
   const COUNT = InputField::LAST - InputField::FIRST;
}

abstract class View
{
   const NEW_SALES_ORDER = 0;
   const VIEW_SALES_ORDER = 1;
   const EDIT_SALES_ORDER = 2;
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
   $view = View::VIEW_SALES_ORDER;
   
   if (getSalesOrderId() == SalesOrder::UNKNOWN_SALES_ORDER_ID)
   {
      $view = View::NEW_SALES_ORDER;
   }
   else if (Authentication::checkPermissions(Permission::EDIT_SALES_ORDER))
   {
      $view = View::EDIT_SALES_ORDER;
   }
   
   return ($view);
}

function getSalesOrderId()
{
   $salesOrderId = SalesOrder::UNKNOWN_SALES_ORDER_ID;
   
   $params = getParams();
   
   if ($params->keyExists("salesOrderId"))
   {
      $salesOrderId = $params->getInt("salesOrderId");
   }
   
   return ($salesOrderId);
}

function getSalesOrder()
{
   static $salesOrder = null;
   
   if ($salesOrder == null)
   {
      $salesOrderId = getSalesOrderId();
      
      if ($salesOrderId != SalesOrder::UNKNOWN_SALES_ORDER_ID)
      {
         $salesOrder =  SalesOrder::load($salesOrderId);
      }
      else
      {
         $salesOrder = new SalesOrder();
         $salesOrder->dateTime = Time::now();
         $salesOrder->orderDate = Time::now();
         $salesOrder->author = Authentication::getAuthenticatedUser()->employeeNumber;
         $salesOrder->orderStatus = SalesOrderStatus::OPEN;
      }
   }
   
   return ($salesOrder);
}

function getAuthorName()
{
   $authorName = "";
   
   $userInfo = UserInfo::load(getSalesOrder()->author);
   
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
   $isEditable = (($view == View::NEW_SALES_ORDER) ||
                  ($view == View::EDIT_SALES_ORDER));
   
   switch ($field)
   {
      case InputField::DATE:
      case InputField::AUTHOR:
      case InputField::CUSTOMER_NAME:
      case InputField::CUSTOMER_PART_NUMBER:
      case InputField::TOTAL:
      {
         $isEditable = false;
         break;
      }

      case InputField::UNIT_PRICE:
      case InputField::TOTAL:
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
   
   if ($view == View::NEW_SALES_ORDER)
   {
      $heading = "Create a new sales order";
   }
   else if ($view == View::EDIT_SALES_ORDER)
   {
      $heading = "Update a sales order";
   }
   else if ($view == View::VIEW_SALES_ORDER)
   {
      $heading = "View a sales order";
   }
   
   return ($heading);
}

function getDescription()
{
   $description = "";
   
   $view = getView();
   
   if ($view == View::NEW_SALES_ORDER)
   {
      $description = "Create a new shipment.";
   }
   else if ($view == View::EDIT_SALES_ORDER)
   {
      $description = "You may revise any of the fields for this sales order and then select save when you're satisfied with the changes.";
   }
   else if ($view == View::VIEW_SALES_ORDER)
   {
      $description = "View a previously saved sales order in detail.";
   }
   
   return ($description);
}

function getPackingListInput()
{
   global $PACKING_LISTS_DIR;
   
   $packingListInput = "";
   
   $packingList = getSalesOrder()->packingList;
   
   $disabled = getDisabled(InputField::PACKING_LIST);
   
   if ($packingList)
   {
      $packingListInput =
<<<HEREDOC
         <div class="flex-vertical flex-top">
            <a href="{$PACKING_LISTS_DIR}{$packingList}" style="margin-bottom: 10px;" target="_blank">$packingList</a>
            <input type="file" name="packingList" form="input-form" $disabled>
         </div>
HEREDOC;
   }
   else
   {
      $packingListInput =
<<<HEREDOC
         <input type="file" name="packingList" form="input-form" $disabled>
HEREDOC;
   }
   
   return ($packingListInput);
}

function getForm()
{
   global $getDisabled;
   
   $salesOrderId = getSalesOrderId();
   $salesOrder = getSalesOrder();
   $authorName = getAuthorName();
   $enteredDate = $salesOrder->dateTime ? Time::toJavascriptDateTime($salesOrder->dateTime): null;
   $orderDate = $salesOrder->orderDate ? Time::toJavascriptDate($salesOrder->orderDate) : null;
   $dueDate = $salesOrder->dueDate ? Time::toJavascriptDate($salesOrder->dueDate) : null;
   $orderStatusOptions = SalesOrderStatus::getOptions($salesOrder->orderStatus);
   $quantity = ($salesOrder->quantity > 0) ? $salesOrder->quantity : null;
   $pptpPartNumber = PartManager::getPPTPPartNumber($salesOrder->customerPartNumber);
   $pptpPartNumberOptions = PartManager::getPptpPartNumberOptions($pptpPartNumber);
   $customerName = CustomerManager::getCustomerName($salesOrder->customerId);
   $packingListInput = getPackingListInput();
   
   $unitPrice = null;
   if (Authentication::checkPermissions(Permission::VIEW_PRICES))
   {
      $unitPrice = ($salesOrder->unitPrice > 0) ? number_format($salesOrder->unitPrice, 4) : null;
   }
   
   $html =
<<< HEREDOC
   <form id="input-form" style="display: block">
      <input id="sales-order-id-input" type="hidden" name="salesOrderId" value="$salesOrderId"/>
      <input type="hidden" name="request" value="save_sales_order"/>
      <input id="pptp-number" type="hidden" name="pptp-number" value="save_sales_order"/>
      
      <div class="form-item">
         <div class="form-label">Author</div>
         <input type="text" name="author" style="width:200px;" value="$authorName" {$getDisabled(InputField::AUTHOR)} />
      </div>
      
      <div class="form-item">
         <div class="form-label">Entered</div>
         <input type="datetime-local" style="width:200px;" value="$enteredDate" {$getDisabled(InputField::DATE)}/>
      </div>

      <div class="flex-horizontal">

         <div class="flex-vertical" style="margin-right: 50px">

            <div class="form-section-header">Tracking</div>
            
            <div class="form-item">
               <div class="form-label">Order #</div>
               <input type="text" name="orderNumber" value="$salesOrder->orderNumber" {$getDisabled(InputField::ORDER_NUMBER)} required/>
            </div>
      
            <div class="form-item">
               <div class="form-label">PO #</div>
               <input type="text" name="poNumber" value="$salesOrder->poNumber" {$getDisabled(InputField::PO_NUMBER)} required/>
            </div>
      
            <div class="form-item">
               <div class="form-label">Order Date</div>
               <input type="date" name="orderDate" value="$orderDate" {$getDisabled(InputField::ORDER_DATE)} required/>
            </div>
      
            <div class="form-item">
               <div class="form-label">Due Date</div>
               <input type="date" name="dueDate" value="$dueDate" {$getDisabled(InputField::DUE_DATE)} required/>
            </div>

            <div class="form-item">
               <div class="form-label">Packing List</div>
               $packingListInput
            </div>

         </div>

         <div class="flex-vertical">

            <div class="form-section-header">Part Info</div>

            <div class="form-item">
               <div class="form-label-long">PPTP Part #</div>
               <select id="pptp-part-number-input" name="pptpPartNumber" {$getDisabled(InputField::PPTP_PART_NUMBER)} required>
                  $pptpPartNumberOptions
               </select>
            </div>

            <div class="form-item">
               <div class="form-label-long">Customer</div>
               <input id="customer-name-input" type="text" value="$customerName" {$getDisabled(InputField::CUSTOMER_NAME)}/>
            </div>

            <div class="form-item">
               <div class="form-label-long">Customer Part #</div>
               <input id="customer-part-number-input" type="text" value="$salesOrder->customerPartNumber" {$getDisabled(InputField::CUSTOMER_PART_NUMBER)}/>
            </div>

            <div class="form-item">
               <div class="form-label-long">Unit Price</div>
               <div class="flex-vertical">
                  <input id="unit-price-input" type="number" name="unitPrice" value="$unitPrice" min="0.0" step="0.0001" {$getDisabled(InputField::UNIT_PRICE)} required/>
                  <div class="flex-horizontal flex-v-center">
                     <input type="checkbox" name="updateUnitPrice" style="margin-right: 10px" {$getDisabled(InputField::UNIT_PRICE)}/>
                     Update part price
                  </div>
               </div>
            </div>

            <div class="form-item">
               <div class="form-label-long">Quantity</div>
               <input id="quantity-input" type="number" name="quantity" value="$quantity" {$getDisabled(InputField::QUANTITY)} required/>
            </div>

            <div class="form-item">
               <div class="form-label-long">Total</div>
               <input id="total-input" type="number" value="" {$getDisabled(InputField::TOTAL)}/>
            </div>

         </div>

      </div>

      <div class="form-section-header">Order Status</div>

      <div class="form-item">
         <div class="form-label">Status</div>
         <div class="flex-horizontal">
            <select name="orderStatus" {$getDisabled(InputField::ORDER_STATUS)}>
               $orderStatusOptions
            </select>
         </div>
      </div>

      <div class="form-item">
         <div class="form-label">Comments</div>
         <textarea class="comments-input" type="text" name="comments" rows="6" maxlength="256" style="width:500px" {$getDisabled(InputField::COMMENTS)}>$salesOrder->comments</textarea>
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
$javascriptFile = "salesOrder.js";
$javascriptClass = "SalesOrder";
$appPageId = AppPage::SALES_ORDER;
$heading = getHeading();
$description = getDescription();
$formId = "input-form";
$form = getForm();

include ROOT.'/templates/salesOrderPageTemplate.php'
      
?>
