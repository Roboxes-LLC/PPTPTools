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

function getHeading()
{
   $heading = "Create a new quote";
   
   return ($heading);
}

function getDescription()
{
   $description = "Create a new quote.";
   
   return ($description);
}

function getForm()
{   
   $unknownQuoteId = Quote::UNKNOWN_QUOTE_ID;
   $customerOptions = Customer::getOptions();
   $contactOptions = Contact::getOptions();
   
   $html = 
<<< HEREDOC
   <form id="input-form" style="display: block">
      <input type="hidden" name="request" value="save_quote"/>
      <input type="hidden" name="quoteId" value="$unknownQuoteId"/>

      <div class="form-item">
         <div class="form-label-long">Customer</div>
         <div class="flex-horizontal">
            <select id="customer-id-input" name="customerId">
               $customerOptions
            </select>
         </div>
      </div>
   
      <div class="form-item">
         <div class="form-label-long">Primary Contact</div>
         <div class="flex-horizontal">
            <select id="contact-id-input" name="contactId">
               $contactOptions
            </select>
         </div>
      </div>

      <div class="form-item">
         <div class="form-label-long">Customer Part #</div>
         <input id="customer-part-number-input" type="text" name="customerPartNumber" maxlength="16" style="width:150px;"/>
      </div>

      <div class="form-item">
         <div class="form-label-long">PPTP Part #</div>
         <input id="pptp-part-number-input" type="text" name="pptpPartNumber" maxlength="16" style="width:150px;"/>
      </div>

      <div class="form-item">
         <div class="form-label-long">Quantity</div>
         <input id="quantity-input" type="number" name="quantity" style="width:75px;"/>
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
$javascriptFile = "quote.js";
$javascriptClass = "Quote";
$appPageId = AppPage::QUOTE;
$heading = getHeading();
$description = getDescription();
$formId = "input-form";
$form = getForm();

include ROOT.'/templates/formPageTemplate.php'
      
?>
