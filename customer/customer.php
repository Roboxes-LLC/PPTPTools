<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/core/component/customer.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/version.php';

abstract class InputField
{
   const FIRST = 0;
   const CUSTOMER_NAME = InputField::FIRST;
   const ADDRESS_LINE_1 = 2;
   const ADDRESS_LINE_2 = 3;
   const CITY= 4;
   const STATE = 5;
   const ZIPCODE = 6;
   const PRIMARY_CONTACT_ID = 7;
   const COUNT = InputField::LAST - InputField::FIRST;
}

abstract class View
{
   const NEW_CUSTOMER = 0;
   const VIEW_CUSTOMER = 1;
   const EDIT_CUSTOMER = 2;
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
   $view = View::VIEW_CUSTOMER;
   
   if (getCustomerId() == Customer::UNKNOWN_CUSTOMER_ID)
   {
      $view = View::NEW_CUSTOMER;
   }
   else if (Authentication::checkPermissions(Permission::EDIT_CUSTOMER))
   {
      $view = View::EDIT_CUSTOMER;
   }
   
   return ($view);
}

function getCustomerId()
{
   $customerId = Customer::UNKNOWN_CUSTOMER_ID;
   
   $params = getParams();
   
   if ($params->keyExists("customerId"))
   {
      $customerId = $params->getInt("customerId");
   }
   
   return ($customerId);
}

function getCustomer()
{
   static $customer = null;
   
   if ($customer == null)
   {
      $customerId = getCustomerId();
      
      if ($customerId != Customer::UNKNOWN_CUSTOMER_ID)
      {
         $customer =  Customer::load($customerId);
      }
      else
      {
         $customer = new Customer();
      }
   }
   
   return ($customer);
}

function isEditable($field)
{
   $view = getView();
   
   // Start with the edit mode, as dictated by the view.
   $isEditable = (($view == View::NEW_CUSTOMER) ||
                  ($view == View::EDIT_CUSTOMER));
   
   switch ($field)
   {
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
   
   if ($view == View::NEW_CUSTOMER)
   {
      $heading = "Add a new customer";
   }
   else if ($view == View::EDIT_CUSTOMER)
   {
      $heading = "Update a customer";
   }
   else if ($view == View::VIEW_CUSTOMER)
   {
      $heading = "View a customer";
   }
   
   return ($heading);
}

function getDescription()
{
   $description = "";
   
   $view = getView();
   
   if ($view == View::NEW_CUSTOMER)
   {
      $description = "Create a new customer.";
   }
   else if ($view == View::EDIT_CUSTOMER)
   {
      $description = "You may revise any of the fields for this customer and then select save when you're satisfied with the changes.";
   }
   else if ($view == View::VIEW__CUSTOMER)
   {
      $description = "View a previously saved customer in detail.";
   }
   
   return ($description);
}

function getForm()
{
   global $getDisabled;
   
   $customerId = getCustomerId();
   $customer = getCustomer();
   $primaryContactOptions = Contact::getOptions($customer->primaryContactId, $customer->customerId);
   $stateOptions = UsaStates::getOptions($customer->address->state);
   
   $html = 
<<< HEREDOC
   <form id="input-form" style="display: block">
      <input type="hidden" name="customerId" value="$customerId"/>
      <input type="hidden" name="request" value="save_customer"/>

      <div class="form-item">
         <div class="form-label-long">Customer Name</div>
         <input id="first-name-input" type="text" name="customerName" maxlength="32" style="width:150px;" value="$customer->customerName" {$getDisabled(InputField::CUSTOMER_NAME)} />
      </div>
   
      <div class="form-item">
         <div class="form-label-long">Primary Contact</div>
         <div class="flex-horizontal">
            <select id="primary-contact-id-input" name="primaryContactId" {$getDisabled(InputField::PRIMARY_CONTACT_ID)}>
               $primaryContactOptions
            </select>
         </div>
      </div>

      <div class="form-item">
         <div class="form-label-long">Address Line 1</div>
         <input id="address-line-1-input" type="text" name="addressLine1" maxlength="32" style="width:150px;" value="{$customer->address->addressLine1}" {$getDisabled(InputField::ADDRESS_LINE_1)} />
      </div>

      <div class="form-item">
         <div class="form-label-long">Address Line 2</div>
         <input id="address-line-1-input" type="text" name="addressLine2" maxlength="32" style="width:150px;" value="{$customer->address->addressLine2}" {$getDisabled(InputField::ADDRESS_LINE_2)} />
      </div>

      <div class="form-item">
         <div class="form-label">City</div>
         <input id="city-input" type="text" name="city" maxlength="16" style="width:150px;" value="{$customer->address->city}" {$getDisabled(InputField::CITY)} />
      </div>

      <div class="form-item">
         <div class="form-label">State</div>
         <div class="flex-horizontal">
            <select id="state-input" name="state" {$getDisabled(InputField::STATE)}>
               $stateOptions
            </select>
         </div>
      </div>

      <div class="form-item">
         <div class="form-label">Zipcode</div>
         <input id="zipcode-input" type="text" name="zipcode" maxlength="16" style="width:150px;" value="{$customer->address->zipcode}" {$getDisabled(InputField::ZIPCODE)} />
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
$javascriptFile = "customer.js";
$javascriptClass = "Customer";
$appPageId = AppPage::CUSTOMER;
$heading = getHeading();
$description = getDescription();
$formId = "input-form";
$form = getForm();

include ROOT.'/templates/formPageTemplate.php'
      
?>
