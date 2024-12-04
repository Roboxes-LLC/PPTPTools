<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/core/component/salesOrder.php';
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
   const CUSTOMER_ID = 3;
   const CUSTOMER_PART_NUMBER = 4;
   const PO_NUMBER = 5;
   const ORDER_DATE = 6;
   const QUANTITY = 7;
   const UNIT_PRICE = 8;
   const DUE_DATE = 9;
   const ORDER_STATUS = 10;
   const COMMENTS = 11;
   const LAST = 12;
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

function getForm()
{
   global $getDisabled;
   
   $salesOrderId = getSalesOrderId();
   $salesOrder = getSalesOrder();
   $authorName = getAuthorName();
   $enteredDate = $salesOrder->dateTime ? Time::toJavascriptDateTime($salesOrder->dateTime) : null;
   $orderDate = $salesOrder->orderDate ? Time::toJavascriptDate($salesOrder->orderDate) : null;
   $dueDate = $salesOrder->dueDate ? Time::toJavascriptDate($salesOrder->dueDate) : null;
   $customerOptions = Customer::getOptions($salesOrder->customerId);
   $orderStatusOptions = SalesOrderStatus::getOptions($salesOrder->orderStatus);
   $unitPrice = ($salesOrder->unitPrice > 0) ? number_format($salesOrder->unitPrice, 4) : null;
   $quantity = ($salesOrder->quantity > 0) ? $salesOrder->quantity : null;
   
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
               <input type="text" name="orderNumber" form="input-form" value="$salesOrder->orderNumber" {$getDisabled(InputField::ORDER_NUMBER)}/>
            </div>
      
            <div class="form-item">
               <div class="form-label">PO #</div>
               <input type="text" name="poNumber" form="input-form" value="$salesOrder->poNumber" {$getDisabled(InputField::PO_NUMBER)}/>
            </div>
      
            <div class="form-item">
               <div class="form-label">Order Date</div>
               <input type="date" name="orderDate" value="$orderDate" {$getDisabled(InputField::ORDER_DATE)}/>
            </div>
      
            <div class="form-item">
               <div class="form-label">Due Date</div>
               <input type="date" name="dueDate" value="$dueDate" {$getDisabled(InputField::DUE_DATE)}/>
            </div>

         </div>

         <div class="flex-vertical">

            <div class="form-section-header">Part Info</div>

            <div class="form-item">
               <div class="form-label-long">Customer</div>
               <div class="flex-horizontal">
                  <select name="customerId" {$getDisabled(InputField::CUSTOMER_ID)}>
                     $customerOptions
                  </select>
               </div>
            </div>
      
            <div class="form-item">
               <div class="form-label-long">Customer Part #</div>
               <input id="customer-part-number-input" type="text" name="customerPartNumber" form="input-form" value="$salesOrder->customerPartNumber" {$getDisabled(InputField::CUSTOMER_PART_NUMBER)}/>
            </div>

            <div class="form-item">
               <div class="form-label-long">Unit Price</div>
               <input type="number" name="unitPrice" form="input-form" value="$unitPrice" {$getDisabled(InputField::UNIT_PRICE)}/>
            </div>
      
            <div class="form-item">
               <div class="form-label-long">Quantity</div>
               <input type="number" name="quantity" form="input-form" value="$quantity" {$getDisabled(InputField::QUANTITY)}/>
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
         <textarea class="comments-input" type="text" name="comments" rows="6" maxlength="256" style="width:500px" {$getDisabled(InputField::COMMENTS)}></textarea>
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
