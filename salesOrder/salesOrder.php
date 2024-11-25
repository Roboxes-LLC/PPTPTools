<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/core/component/salesOrder.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/version.php';

abstract class InputField
{
   const FIRST = 0;
   const DATE = InputField::FIRST;
   const AUTHOR = 1;
   const JOB_ID = 2;
   const CUSTOMER_PART_NUMBER = 3;
   const QUANTITY = 4;
   const PO_NUMBER = 5;
   const LAST = 6;
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
         $salesOrder->orderDate = Time::now();
         $salesOrder->author = Authentication::getAuthenticatedUser()->employeeNumber;
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
   $orderDate = $salesOrder->orderDate ? Time::dateTimeObject($salesOrder->orderDate)->format("n/j/Y h:i A") : null;
   $dueDate = $salesOrder->dueDate ? Time::dateTimeObject($salesOrder->dueDate)->format("n/j/Y h:i A") : null;
   
   $html =
<<< HEREDOC
   <form id="input-form" style="display: block">
      <input id="sales-order-id-input" type="hidden" name="salesOrderId" value="$salesOrderId"/>
      <input type="hidden" name="request" value="save_sales_order"/>
      
      <div class="form-item">
         <div class="form-label">Author</div>
         <input type="text" name="author" style="width:200px;" value="$authorName" {$getDisabled(InputField::AUTHOR)} />
      </div>
      
      <div class="form-item">
         <div class="form-label">Order Date</div>
         <input type="text" style="width:200px;" value="$orderDate" disabled/>
      </div>
      
      <div class="form-item">
         <div class="form-label">Quantity</div>
         <input id="quantity-input" type="number" name="quantity" style="width:100px;" value="{$salesOrder->quantity}" {$getDisabled(InputField::QUANTITY)} />
      </div>
      
      <div class="form-item">
         <div class="form-label">PO #</div>
         <input id="po-number-input" type="text" name="poNumber" maxlength="32" style="width:150px;" value="{$salesOrder->poNumber}" {$getDisabled(InputField::PO_NUMBER)} />
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

include ROOT.'/templates/formPageTemplate.php'
      
?>
