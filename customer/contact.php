<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/core/component/contact.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/version.php';

abstract class InputField
{
   const FIRST = 0;
   const FIRST_NAME = InputField::FIRST;
   const LAST_NAME = 2;
   const CUSTOMER_ID = 3;
   const EMAIL = 4;
   const PHONE = 5;
   const COUNT = InputField::LAST - InputField::FIRST;
}

abstract class View
{
   const NEW_CONTACT = 0;
   const VIEW_CONTACT = 1;
   const EDIT_CONTACT = 2;
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
   $view = View::VIEW_CONTACT;
   
   if (getContactId() == Contact::UNKNOWN_CONTACT_ID)
   {
      $view = View::NEW_CONTACT;
   }
   else if (Authentication::checkPermissions(Permission::EDIT_CUSTOMER))
   {
      $view = View::EDIT_CONTACT;
   }
   
   return ($view);
}

function getContactId()
{
   $contactId = Contact::UNKNOWN_CONTACT_ID;
   
   $params = getParams();
   
   if ($params->keyExists("contactId"))
   {
      $contactId = $params->getInt("contactId");
   }
   
   return ($contactId);
}

function getContact()
{
   static $contact = null;
   
   if ($contact == null)
   {
      $contactId = getContactId();
      
      if ($contactId != Contact::UNKNOWN_CONTACT_ID)
      {
         $contact = Contact::load($contactId);
      }
      else
      {
         $contact = new Contact();
      }
   }
   
   return ($contact);
}

function isEditable($field)
{
   $view = getView();
   
   // Start with the edit mode, as dictated by the view.
   $isEditable = (($view == View::NEW_CONTACT) ||
                  ($view == View::EDIT_CONTACT));
   
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
   
   if ($view == View::NEW_CONTACT)
   {
      $heading = "Add a new contact";
   }
   else if ($view == View::EDIT_CONTACT)
   {
      $heading = "Update a contact";
   }
   else if ($view == View::VIEW_CONTACT)
   {
      $heading = "View a contact";
   }
   
   return ($heading);
}

function getDescription()
{
   $description = "";
   
   $view = getView();
   
   if ($view == View::NEW_CONTACT)
   {
      $description = "Create a new new contact associated with an existing customer.";
   }
   else if ($view == View::EDIT_CONTACT)
   {
      $description = "You may revise any of the fields for this contact and then select save when you're satisfied with the changes.";
   }
   else if ($view == View::VIEW__CONTACT)
   {
      $description = "View a previously saved contact in detail.";
   }
   
   return ($description);
}

function getForm()
{
   global $getDisabled;
   
   $contactId = getContactId();
   $contact = getContact();
   $customerOptions = Customer::getOptions($contact->customerId);
   
   $html = 
<<< HEREDOC
   <form id="input-form" style="display: block">
      <input type="hidden" name="contactId" value="$contactId"/>
      <input type="hidden" name="request" value="save_contact"/>

      <div class="form-item">
         <div class="form-label">First Name</div>
         <input id="first-name-input" type="text" name="firstName" maxlength="16" style="width:150px;" value="$contact->firstName" {$getDisabled(InputField::FIRST_NAME)} />
      </div>
   
      <div class="form-item">
         <div class="form-label">Last Name</div>
         <input id="last-name-input" type="text" name="lastName" maxlength="16" style="width:150px;" value="$contact->lastName" {$getDisabled(InputField::LAST_NAME)} />
      </div>

      <div class="form-item">
         <div class="form-label">Customer</div>
         <div class="flex-horizontal">
            <select id="customer-id-input" name="customerId" {$getDisabled(InputField::CUSTOMER_ID)}>
               $customerOptions
            </select>
         </div>
      </div>
   
      <div class="form-item">
         <div class="form-label">Email</div>
         <input id="email-input" type="text" name="email" form="input-form" maxlength="32" style="width:300px;" value="$contact->email" {$getDisabled(InputField::EMAIL)} />
      </div>

      <div class="form-item">
         <div class="form-label">Phone</div>
         <input id="phone-input" type="text" name="phone" form="input-form" maxlength="16" style="width:150px;" value="$contact->phone" {$getDisabled(InputField::PHONE)} />
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
$javascriptFile = "contact.js";
$javascriptClass = "Contact";
$appPageId = AppPage::CONTACT;
$heading = getHeading();
$description = getDescription();
$formId = "input-form";
$form = getForm();

include ROOT.'/templates/formPageTemplate.php'
      
?>
