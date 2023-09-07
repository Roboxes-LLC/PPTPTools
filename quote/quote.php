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
require_once ROOT.'/quote/quoteEmail.php';

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
   const MARKUP = 7;
   const ADDITIONAL_CHARGE = 8;
   const CHARGE_CODE = 9;
   const TOTAL_COST = 10;
   const LEAD_TIME = 11;
   const TO_EMAIL = 12;
   const CC_EMAIL = 13;
   const FROM_EMAIL = 14;
   const EMAIL_BODY = 15;
   const LAST = 16;
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

function getAttachmentsPanel()
{
   global $getDisabled;
   
   $quote = getQuote();
   
   $html =
<<<HEREDOC
   <div id="attachments-panel" class="collapsible-panel">
      <form id="attachments-form" style="display:block">
         <input type="hidden" name="quoteId" value="{$quote->quoteId}"/>
         <input type="hidden" name="request" value="attach_file"/>

         <div class="flex-horizontal flex-v-center collapsible-panel-header">
            <i class="material-icons icon-button expanded-icon">arrow_drop_down</i>
            <i class="material-icons icon-button collapsed-icon">arrow_right</i>
            Attachments
         </div>

         <div class="collapsible-panel-content">
HEREDOC;
   
   foreach ($quote->attachments as $attachment)
   {
      $downloadFilename = !empty($attachment->filename) ? 
                             $attachment->filename : 
                             $attachment->storedFilename;
      
      $descriptionTitle = !empty($attachment->description) ? 
                             "title=\"$attachment->description\"" : 
                             "title=\"No description\"";
      
      $html .=
<<<HEREDOC
      <div class="flex-horizontal attachment">
         <a href="/uploads/{$attachment->storedFilename}" target="_blank">$attachment->filename</a>
         &nbsp;&nbsp;
         <i class="material-icons icon-button" $descriptionTitle>info</i>
         &nbsp;
         <a href="/uploads/{$attachment->storedFilename}" download="$downloadFilename">
            <i class="material-icons icon-button" title="Download">download</i>
         </a>
         &nbsp;
         <i class="material-icons icon-button delete-quote-attachment-button" title="Delete" data-attachmentid="$attachment->attachmentId">delete</i>
      </div>
HEREDOC;
   }
   
   $html .=
   <<<HEREDOC
            <div class="form-item">
               <div class="form-label">File</div>
               <input type="file" name="quoteAttachment" {$getDisabled(InputField::CUSTOMER_PART_NUMBER)}>               
            </div>

            <div class="form-item">
               <div class="form-label">Filename</div>
               <input type="text" name="filename" maxlength="32" style="width:150px;" {$getDisabled(InputField::CUSTOMER_PART_NUMBER)} />
            </div>

            <div class="form-item">
               <div class="form-label">Description</div>
               <input type="text" name="description" maxlength="64" style="width:300px;" {$getDisabled(InputField::CUSTOMER_PART_NUMBER)} />
            </div>

            <div class="flex-horizontal flex-v-center flex-h-center" style="width:100%; margin-top:30px">
               <button id="attach-button" type="button">Upload</button>
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
         
         <div class="collapsible-panel-content flex-vertical">

            <div class="estimate-table flex-horizontal">

               <div class="estimate-table-column estimate-table-label-column flex-vertical">
                  <div class="estimate-table-cell"></div>
                  <div class="estimate-table-cell estimate-table-label">Selling Price</div>
                  <div class="estimate-table-cell estimate-table-label">Dollars Per Hour</div>
                  <div class="estimate-table-cell estimate-table-label">Profit/Markup</div>
                  <div class="estimate-table-cell estimate-table-label">Additional Charge</div>
                  <div class="estimate-table-cell estimate-table-label">Charge Code</div>
                  <div class="estimate-table-cell estimate-table-label">Total Value</div>
                  <div class="estimate-table-cell estimate-table-label">Lead Time (weeks)</div>
               </div>
HEREDOC;
   
   for ($estimateIndex = 0; $estimateIndex < Estimate::MAX_ESTIMATES; $estimateIndex++)
   {
      $html .= getEstimatePanel($estimateIndex);
   }
   
   $html .=
<<< HEREDOC
            </div>

            <br>
            
            <div class="flex-horizontal flex-h-center">
               <button id="quote-button" type="button" class="accent-button">Save</button>
               <button id="revise-button" type="button">Revise</button>
            </div>
            
         </div>
         
      </form>
   </div>
HEREDOC;
                     
   return ($html);
}

function getEstimatePanel($estimateIndex)
{   
   global $getDisabled;
   
   $estimateId = $estimateIndex + 1;
   
   $unitPrice = getEstimateProperty($estimateIndex, "unitPrice");
   $costPerHour = getEstimateProperty($estimateIndex, "costPerHour");
   $markup = getEstimateProperty($estimateIndex, "markup");
   $additionalCharge = getEstimateProperty($estimateIndex, "additionalCharge");
   $chargeCode = getEstimateProperty($estimateIndex, "chargeCode");
   $totalCost = getEstimateProperty($estimateIndex, "totalCost");
   $leadTime = getEstimateProperty($estimateIndex, "leadTime");
   
   $chargeCodeOptions = ChargeCode::getOptions($chargeCode);
   
   $quote = getQuote();
   $checked = ($quote && ($quote->selectedEstimate == $estimateIndex)) ? "checked" : "";
   
   $html =
<<< HEREDOC
   <div class="estimate-table-column flex-vertical">

      <div class="estimate-table-cell estimate-heading">Estimate $estimateId</div>

      <div class="estimate-table-cell estimate-table-input">
         <input type="number" name="unitPrice_$estimateIndex" style="width:75px;" value="$unitPrice" {$getDisabled(InputField::UNIT_PRICE)} />
      </div>
      
      <div class="estimate-table-cell estimate-table-input">
         <input type="number" name="costPerHour_$estimateIndex" style="width:75px;" value="$costPerHour" {$getDisabled(InputField::COST_PER_HOUR)} />
      </div>
      
      <div class="estimate-table-cell estimate-table-input">
         <input type="number" name="markup_$estimateIndex" style="width:50px;" value="$markup" {$getDisabled(InputField::MARKUP)} />
         &nbsp;%
      </div>
      
      <div class="estimate-table-cell estimate-table-input">
         <input type="number" name="additionalCharge_$estimateIndex" style="width:75px;" value="$additionalCharge" {$getDisabled(InputField::ADDITIONAL_CHARGE)} />
      </div>
      
      <div class="estimate-table-cell estimate-table-input">
         <select name="chargeCode_$estimateIndex" {$getDisabled(InputField::CHARGE_CODE)}>
            $chargeCodeOptions
         </select>
      </div>
      
      <div class="estimate-table-cell estimate-table-input">
         <input type="number" name="totalCost_$estimateIndex" style="width:75px;" value="$totalCost" {$getDisabled(InputField::TOTAL_COST)} />
      </div>
      
      <div class="estimate-table-cell estimate-table-input">
         <input type="number" name="leadTime_$estimateIndex" style="width:75px;" value="$leadTime" {$getDisabled(InputField::LEAD_TIME)} />
      </div>

      <div class="estimate-table-cell estimate-table-input flex-horizontal flex-h-center">
         <input class="estimate-selection-input" type="radio" name="selectedEstimate" value="$estimateIndex" $checked>
      </div>

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
   <div id="approve-panel" class="collapsible-panel">
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
   <div id="accept-panel" class="collapsible-panel">
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
   $notes = $quote->getSentNotes();
   
   $html =
<<< HEREDOC
   <div id="send-panel" class="collapsible-panel">
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
               <input type="text" name="ccEmails" style="width:300px;" value="$ccEmail" {$getDisabled(InputField::TO_EMAIL)} />
            </div>
      
            <div class="form-item">
               <div class="form-label">From</div>
               <input type="text" name="fromEmail" style="width:300px;" value="$fromEmail" {$getDisabled(InputField::FROM_EMAIL)} />
            </div>
   
            <div class="form-item">
               <div class="form-label">Notes</div>
               <textarea class="comments-input" type="text" name="notes" rows="8" maxlength="512" style="width:300px" <?php echo getDisabled(InputField::EMAIL_BODY) ?>$notes</textarea>
            </div>
            
            <br>
            
            <div class="flex-horizontal flex-h-center flex-v-center">
               <button id="send-button" type="button" class="accent-button">Send</button>
               <button id="resend-button" type="button">Resend</button>
               &nbsp;&nbsp;&nbsp;
               <div id="preview-button" class="download-link">Preview</div>
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
      if (in_array($activity->activityType, ActivityType::$activitiesWithNotes) && 
          ($activity->objects[2] != null))
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
$attachmentsPanel = getAttachmentsPanel(); 
$estimatesPanel = getEstimatesPanel();
$approvePanel = getApprovePanel();
$acceptPanel = getAcceptPanel();
$sendPanel = getSendPanel();
$quoteStatus = getQuote()->quoteStatus;

include ROOT.'/templates/quotePageTemplate.php'
      
?>
