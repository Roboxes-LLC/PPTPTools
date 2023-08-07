<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/core/component/quote.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/version.php';

abstract class InputField
{
   const FIRST = 0;
   const CUSTOMER_ID = InputField::FIRST;
   const CONTACT_ID = 1;
   const CUSTOMER_PART_NUMBER = 2;
   const PPTP_PART_NUMBER = 3;
   const QUANTITY = 4;
   const LAST = 5;
   const COUNT = InputField::LAST - InputField::FIRST;
}

abstract class View
{
   const NEW_QUOTE = 0;
   const EDIT_QUOTE = 1;
   const VIEW_QUOTE = 2;
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
   $view = View::VIEW_QUOTE;
   
   if (getQuoteId() == Quote::UNKNOWN_QUOTE_ID)
   {
      $view = View::NEW_QUOTE;
   }
   else if (Authentication::checkPermissions(Permission::EDIT_QUOTE))
   {
      $view = View::EDIT_QUOTE;
   }
   
   return ($view);
}

function getQuoteId()
{
   $quoteId = Quote::UNKNOWN_QUOTE_ID;
   
   $params = getParams();
   
   if ($params->keyExists("quoteId"))
   {
      $quoteId = $params->getInt("quoteId");
   }
   
   return ($quoteId);
}

function getQuote()
{
   static $quote = null;
   
   if ($quote == null)
   {
      $quoteId = getQuoteId();
      
      if ($quoteId != Quote::UNKNOWN_QUOTE_ID)
      {
         $quote = Quote::load($quoteId);
      }
      else
      {
         $quote = new Quote();
      }
   }
   
   return ($quote);
}

function isEditable($field)
{
   $view = getView();
   
   // Start with the edit mode, as dictated by the view.
   $isEditable = (($view == View::NEW_QUOTE) ||
                  ($view == View::EDIT_QUOTE));
   
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
   
   if ($view == View::NEW_QUOTE)
   {
      $heading = "Create a new quote";
   }
   else if ($view == View::EDIT_QUOTE)
   {
      $heading = "Work on a quote";
   }
   else if ($view == View::VIEW_QUOTE)
   {
      $heading = "View quote";
   }
   
   return ($heading);
}

function getDescription()
{
   $description = "";
   
   $view = getView();
   
   if ($view == View::NEW_QUOTE)
   {
      $description = "Create a new quote.";
   }
   else if ($view == View::EDIT_QUOTE)
   {
      $description = "Blah blah blah.";
   }
   else if ($view == View::VIEW__QUOTE)
   {
      $description = "View the status of a quote in progress.";
   }
   
   return ($description);
}

function getRequestPanel()
{
   global $getDisabled;
   
   $quote = getQuote();
   
   $customerOptions = Customer::getOptions($quote->customerId);
   $contactOptions = Contact::getOptions($quote->contactId, $quote->customerId);
   
   $html = 
<<< HEREDOC
   <div id="request-panel">
      <div class="form-item">
         <div class="form-label-long">Customer</div>
         <div class="flex-horizontal">
            <select id="customer-id-input" name="customerId" {$getDisabled(InputField::CUSTOMER_ID)}>
               $customerOptions
            </select>
         </div>
      </div>
   
      <div class="form-item">
         <div class="form-label-long">Primary Contact</div>
         <div class="flex-horizontal">
            <select id="contact-id-input" name="contactId" {$getDisabled(InputField::CONTACT_ID)}>
               $contactOptions
            </select>
         </div>
      </div>

      <div class="form-item">
         <div class="form-label-long">Customer Part #</div>
         <input id="customer-part-number-input" type="text" name="customerPartNumber" maxlength="16" style="width:150px;" value="{$quote->customerPartNumber}" {$getDisabled(InputField::CUSTOMER_PART_NUMBER)} />
      </div>

      <div class="form-item">
         <div class="form-label-long">PPTP Part #</div>
         <input id="pptp-part-number-input" type="text" name="pptpPartNumber" maxlength="16" style="width:150px;" value="{$quote->pptpPartNumber}" {$getDisabled(InputField::PPTP_PART_NUMBER)} />
      </div>

      <div class="form-item">
         <div class="form-label-long">Quantity</div>
         <input id="quantity-input" type="number" name="quantity" style="width:75px;" value="{$quote->quantity}" {$getDisabled(InputField::QUANTITY)} />
      </div>

      <br>
      
      <div class="flex-horizontal flex-h-center">
         <button id="cancel-button">Cancel</button>&nbsp;&nbsp;&nbsp;
         <button id="save-button" class="accent-button">Save</button>            
      </div>

   </div>
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
$javascriptFile = "quote.js";
$javascriptClass = "Quote";
$appPageId = AppPage::QUOTE;
$heading = getHeading();
$description = getDescription();
$formId = "input-form";
$requestPanel = getRequestPanel();

include ROOT.'/templates/quotePageTemplate.php'
      
?>
