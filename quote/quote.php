<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/core/common/notification.php';
require_once ROOT.'/core/component/quote.php';
require_once ROOT.'/core/manager/activityLog.php';
require_once ROOT.'/core/manager/userManager.php';
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
   const UNIT_PRICE = 5;
   const COST_PER_HOUR = 6;
   const ADDITIONAL_CHARGE = 7;
   const CHARGE_CODE = 8;
   const TOTAL_COST = 9;
   const LEAD_TIME = 10;
   const TO_EMAIL = 10;
   const CC_EMAIL = 11;
   const FROM_EMAIL = 12;
   const EMAIL_BODY = 13;
   const LAST = 14;
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

function getToEmail()
{
   $toEmail = "";
   
   $quote = getQuote();
   
   if ($quote)
   {
      $contact = Contact::load($quote->contactId);
      if ($contact)
      {
         $toEmail = $contact->email;
      }
   }
   
   return ($toEmail);
}

function getCCEmail()
{
   $ccEmail = "";
   
   $users = UserManager::getUsersForNotification(Notification::QUOTE_SENT);
   
   $emailAddresses = array();
   
   foreach ($users as $user)
   {
      if (!empty($user->email))
      {
         $emailAddresses[] = $user->email;
      }
   }
   
   if (count($emailAddresses) > 0)
   {
      $ccEmail = implode("; ", $emailAddresses);
   }

   
   return ($ccEmail);
}

function getFromEmail()
{
   $fromEmail = "";
   
   $userInfo = Authentication::getAuthenticatedUser();  
   if ($userInfo)
   {
      $fromEmail = $userInfo->email;
   }
   
   return ($fromEmail);
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

function getEstimateProperty($estimateIndex, $property)
{
   $value = null;
   
   $quote = getQuote();
   
   if ($quote && $quote->hasEstimate($estimateIndex))
   {
      $value = $quote->getEstimate($estimateIndex)->$property;
   }
   
   return ($value);
}

function getRequestPanel()
{
   global $getDisabled;
   
   $quote = getQuote();
   
   $customerOptions = Customer::getOptions($quote->customerId);
   $contactOptions = Contact::getOptions($quote->contactId, $quote->customerId);
   
   $html = 
<<< HEREDOC
   <div id="request-panel" class="collapsible-panel">
      <form id="request-form" style="display:block">
         <input type="hidden" name="quoteId" value="$quote->quoteId"/>
         <input type="hidden" name="request" value="save_quote"/>

         <div class="flex-horizontal flex-v-center collapsible-panel-header">
            <i class="material-icons icon-button expanded-icon">arrow_drop_down</i>
            <i class="material-icons icon-button collapsed-icon">arrow_right</i>
            Request for Quote
         </div>

         <div class="collapsible-panel-content">

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
               <button id="update-button">Update</button>            
            </div>

         </div>

      </form>
   </div>
HEREDOC;
   
   return ($html);
}

function getEstimatesPanel()
{
   global $getDisabled;
   
   $quote = getQuote();
   
   // TODO: Multiple quotes
   $unitPrice = getEstimateProperty(0, "unitPrice");
   $costPerHour = getEstimateProperty(0, "costPerHour");
   $additionalCharge = getEstimateProperty(0, "additionalCharge");
   $chargeCode = getEstimateProperty(0, "chargeCode");
   $totalCost = getEstimateProperty(0, "totalCost");
   $leadTime = getEstimateProperty(0, "leadTime");
   
   $chargeCodeOptions = ChargeCode::getOptions($chargeCode);
   
   $html =
<<< HEREDOC
   <div id="estimates-panel" class="collapsible-panel">
      <form id="quote-form" style="display:block">

         <input type="hidden" name="quoteId" value="$quote->quoteId"/>
         <input type="hidden" name="request" value="estimate_quote"/>

         <div class="flex-horizontal flex-v-center collapsible-panel-header">
            <i class="material-icons icon-button expanded-icon">arrow_drop_down</i>
            <i class="material-icons icon-button collapsed-icon">arrow_right</i>
            Estimates
         </div>

         <div class="collapsible-panel-content">

            <div class="form-item">
               <div class="form-label">Selling Price</div>
               <input id="unit-price-input" type="number" name="unitPrice" style="width:75px;" value="$unitPrice" {$getDisabled(InputField::UNIT_PRICE)} />
            </div>
      
            <div class="form-item">
               <div class="form-label">Dollars Per Hour</div>
               <input id="cost-per-hour-input" type="number" name="costPerHour" style="width:75px;" value="$costPerHour" {$getDisabled(InputField::COST_PER_HOUR)} />
            </div>

            <div class="form-item">
               <div class="form-label">Profit/Markup</div>
               <input id="additional-charge-input" type="number" name="markup" style="width:75px;" value="" {$getDisabled(InputField::ADDITIONAL_CHARGE)} />
               %
            </div>
      
            <div class="form-item">
               <div class="form-label">Additional Charge</div>
               <input id="additional-charge-input" type="number" name="additionalCharge" style="width:75px;" value="$additionalCharge" {$getDisabled(InputField::ADDITIONAL_CHARGE)} />
            </div>
            
            <div class="form-item">
               <div class="form-label">Charge Code</div>
               <div class="flex-horizontal">
                  <select id="charge-code-input" name="chargeCode" {$getDisabled(InputField::CHARGE_CODE)}>
                     $chargeCodeOptions
                  </select>
               </div>
            </div>
      
            <div class="form-item">
               <div class="form-label">Total Value</div>
               <input id="total-cost-input" type="number" name="totalCost" style="width:75px;" value="$totalCost" {$getDisabled(InputField::TOTAL_COST)} />
            </div>
            
            <div class="form-item">
               <div class="form-label">Lead Time (weeks)</div>
               <input id="lead-time-input" type="number" name="leadTime" style="width:75px;" value="$leadTime" {$getDisabled(InputField::LEAD_TIME)} />
            </div>
            
            <br>
            
            <div class="flex-horizontal flex-h-center">
               <button id="quote-button" class="accent-button">Quote</button>
               <button id="revise-button">Revise</button>
            </div>
         
         </div>

      </form>      
   </div>
HEREDOC;
               
   return ($html);
}

function getApprovePanel()
{
   global $getDisabled;
   
   $quote = getQuote();
   
   $html =
<<< HEREDOC
   <div id="approve-panel" class="collapsible-panel" style="margin-right:50px;">
      <form id="approve-form" style="display:block">

         <input type="hidden" name="quoteId" value="{$quote->quoteId}"/>
         <input type="hidden" name="request" value="approve_quote"/>
         <input id="is-approved-input" type="hidden" name="isApproved" value="false"/>

         <div class="flex-horizontal flex-v-center collapsible-panel-header">
            <i class="material-icons icon-button expanded-icon">arrow_drop_down</i>
            <i class="material-icons icon-button collapsed-icon">arrow_right</i>
            Quote Approval
         </div>

         <div class="collapsible-panel-content">
      
            <div class="form-item">
               <textarea id="approve-notes-input" class="comments-input" type="text" name="approveNotes" rows="4" maxlength="256" style="width:300px" <?php echo getDisabled(InputField::APPROVAL_NOTES) ?></textarea>
            </div>
            
            <br>
            
            <div class="flex-horizontal flex-h-center">
               <button id="approve-button" type="button" class="accent-button" style="margin-right:20px">Approve</button>
               <button id="unapprove-button" type="button">Unapprove</button>
            </div>

         </div>
      
      </form>
   </div>
HEREDOC;
   
   return ($html);
}

function getAcceptPanel()
{
   global $getDisabled;
   
   $quote = getQuote();
   
   $html =
<<< HEREDOC
   <div id="accept-panel" class="collapsible-panel" style="margin-right:50px;">
      <form id="accept-form" style="display:block">
      
         <input type="hidden" name="quoteId" value="{$quote->quoteId}"/>
         <input type="hidden" name="request" value="accept_quote"/>
         <input id="is-accepted-input" type="hidden" name="isAccepted" value="false"/>
         
         <div class="flex-horizontal flex-v-center collapsible-panel-header">
            <i class="material-icons icon-button expanded-icon">arrow_drop_down</i>
            <i class="material-icons icon-button collapsed-icon">arrow_right</i>
            Accepted by Customer
         </div>

         <div class="collapsible-panel-content">

            <div class="form-item">
               <textarea id="accept-notes-input" class="comments-input" type="text" name="acceptNotes" rows="4" maxlength="256" style="width:300px" <?php echo getDisabled(InputField::APPROVAL_NOTES) ?></textarea>
            </div>
            
            <br>
            
            <div class="flex-horizontal flex-h-center">
               <button id="accept-button" type="button" class="accent-button" style="margin-right:20px">Mark Accepted</button>
               <button id="reject-button" type="button">Mark Rejected</button>
            </div>

         </div>
         
      </form>
   </div>
HEREDOC;
   
   return ($html);
}

function getSendPanel()
{
   global $getDisabled;
   
   $quote = getQuote();
   
   $contact = Contact::load($quote->contactId);
   
   $toEmail = getToEmail();
   $ccEmail = getCCEmail();
   $fromEmail = getFromEmail();
   $emailBody = "";
   
   $html =
<<< HEREDOC
   <div id="send-panel" class="collapsible-panel" style="margin-right:50px;">
      <form id="send-form" style="display:block">
      
         <input type="hidden" name="quoteId" value="{$quote->quoteId}"/>
         <input type="hidden" name="request" value="send_quote"/>

         <div class="flex-horizontal flex-v-center collapsible-panel-header">
            <i class="material-icons icon-button expanded-icon">arrow_drop_down</i>
            <i class="material-icons icon-button collapsed-icon">arrow_right</i>
            Send Quote
         </div>

         <div class="collapsible-panel-content">
         
            <div class="form-item">
               <div class="form-label">To</div>
               <input type="text" name="toEmail" style="width:300px;" value="$toEmail" {$getDisabled(InputField::TO_EMAIL)} />
            </div>
      
            <div class="form-item">
               <div class="form-label">CC</div>
               <input type="text" name="ccEmail" style="width:300px;" value="$ccEmail" {$getDisabled(InputField::TO_EMAIL)} />
            </div>
      
            <div class="form-item">
               <div class="form-label">From</div>
               <input type="text" name="fromEmail" style="width:300px;" value="$fromEmail" {$getDisabled(InputField::FROM_EMAIL)} />
            </div>
   
            <div class="form-item">
               <div class="form-label">Body</div>
               <textarea class="comments-input" type="text" name="emailBody" rows="8" maxlength="512" style="width:300px" <?php echo getDisabled(InputField::EMAIL_BODY) ?>$emailBody</textarea>
            </div>
            
            <br>
            
            <div class="flex-horizontal flex-h-center">
               <button id="send-button" type="button" class="accent-button">Send</button>
               <button id="resend-button" type="button">Resend</button>
            </div>

         </div>
         
      </form>
   </div>
HEREDOC;
   
   return ($html);
}

function getHistoryPanel()
{
   $quote = getQuote();
   
   $html = 
<<<HEREDOC
   <div class="history-panel flex-vertical">
      <form id="history-form" style="display:block">
         <input type="hidden" name="quoteId" value="{$quote->quoteId}"/>
         <input type="hidden" name="request" value="add_comment"/>

         <div class="form-section-header">History</div>
HEREDOC;
   
   $activities = ActivityLog::getActivitiesForQuote(getQuoteId(), false);  // Order descending
   
   foreach ($activities as $activity)
   {
      $dateTime = Time::dateTimeObject($activity->dateTime);
      $activityDate = $dateTime->format("n/j g:i A");
      
      $icon = ActivityType::getIcon($activity->activityType);
      $description = $activity->getDescription();
      
      $comments = "";
      if ($activity->objects[2] != null)
      {
         $comments = "\"{$activity->objects[2]}\"";
      }
      
      $deleteIcon = "";
      if (($activity->activityType == ActivityType::ANNOTATE_QUOTE) &&
          ($activity->author == Authentication::getAuthenticatedUser()->employeeNumber))
      {
         $deleteIcon = 
<<<HEREDOC
         <div class="material-icons-outlined delete-comment-icon" data-activity-id="$activity->activityId" style="width:20px;">delete</div>
HEREDOC;
      }
      
      $html .=
<<<HEREDOC
      <div class="history-item">
         <div class="material-icons-outlined" style="width:20px;">$icon</div>
         <div style="flex-direction:column">
            <div class="flex-horizontal">
               <div>$description</div>
               $deleteIcon
            </div>
            <div>$comments</div>
            <div class="history-date">$activityDate</div>
         </div>
      </div>
HEREDOC;
   }
   
   $html .=
<<<HEREDOC
      <div class="flex-horizontal flex-v-center flex-h-center" style="width:100%; margin-top:30px">
         <div class="form-item" style="margin-right: 20px">
            <textarea class="comments-input" type="text" name="comments" rows="4" maxlength="512" style="width:300px"></textarea>
         </div>
         <button id="comment-button" type="button">Comment</button>
      </form>
   </div>
HEREDOC;
   
   return ($html);
}

function getAttachPanel()
{
   $html = "";
   
   return ($html);
}

function getTimeline()
{
   $html = "";
   
   $standardHappyPath = [QuoteStatus::REQUESTED, QuoteStatus::ESTIMATED, QuoteStatus::APPROVED, QuoteStatus::SENT, QuoteStatus::ACCEPTED];
   $revisedHappyPath = [QuoteStatus::UNAPPROVED, QuoteStatus::REJECTED, QuoteStatus::REVISED, QuoteStatus::APPROVED, QuoteStatus::SENT, QuoteStatus::ACCEPTED];
   $unhappyPath = [QuoteStatus::UNAPPROVED, QuoteStatus::REJECTED];
   $requiresRevision = [QuoteStatus::UNAPPROVED, QuoteStatus::REJECTED, QuoteStatus::REVISED];
   
   $quote = getQuote();
   
   $html = "<ul class=\"timeline\">";
   
   $lastQuoteAction = null;
   foreach ($quote->actions as $quoteAction)
   {
      // Avoid duplicate consecutive actions on the same date.
      if (!$lastQuoteAction ||
          !(($quoteAction->quoteStatus == $lastQuoteAction->quoteStatus) &&
            (Time::dateTimeObject($quoteAction->dateTime)->format("Y-m-d") == Time::dateTimeObject($lastQuoteAction->dateTime)->format("Y-m-d"))))
      {
         $html .= getTimelinePoint($quoteAction);
      }
      
      $lastQuoteAction = $quoteAction;
   }
   
   // Add the remainder of the happy path.
   $lastQuoteStatus = end($quote->actions)->quoteStatus;
   
   $happyPath = $standardHappyPath;
   if (in_array($lastQuoteStatus, $requiresRevision))
   {
      $happyPath = $revisedHappyPath;
   }
   
   $addPoints = false;
   foreach ($happyPath as $quoteStatus)
   {
      if (($addPoints) &&
          !in_array($quoteStatus, $unhappyPath))
      {
         // Create a dummy PO action.
         $quoteAction = new QuoteAction();
         $quoteAction->quoteStatus = $quoteStatus;
         
         $html .= getTimelinePoint($quoteAction);
      }
      else if ($quoteStatus == $lastQuoteStatus)
      {
         $addPoints = true;
      }
   }
   
   $html .= "</ul>";
   
   return ($html);
}

function getTimelinePoint($quoteAction)
{
   $html = "";
   
   $state = QuoteStatus::getLabel($quoteAction->quoteStatus);
   $dateTime = "";
   $username = "";
   $notes = "";
   
   $achieved = ($quoteAction->dateTime != null);
   
   if ($achieved)
   {
      $dateTime = Time::dateTimeObject($quoteAction->dateTime)->format("m/d/Y");
      $username = UserInfo::getUsername($quoteAction->userId);
      $notes = htmlspecialchars($quoteAction->notes);
   }
   
   $achievedStr = $achieved ? "true" : "false";
   
   $html =
<<<HEREDOC
   <li data-status="$state" data-achieved="$achievedStr" data-timestamp="$dateTime" data-user="$username" data-notes="$notes"></li>
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
$timeline = getTimeline();
$historyPanel = getHistoryPanel();
$requestPanel = getRequestPanel();
$estimatesPanel = getEstimatesPanel();
$approvePanel = getApprovePanel();
$acceptPanel = getAcceptPanel();
$sendPanel = getSendPanel();
$quoteStatus = getQuote()->quoteStatus;

include ROOT.'/templates/quotePageTemplate.php'
      
?>
