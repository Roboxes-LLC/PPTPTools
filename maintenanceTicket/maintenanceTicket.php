<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/core/common/notification.php';
require_once ROOT.'/core/component/maintenanceTicket.php';
require_once ROOT.'/core/manager/activityLog.php';
require_once ROOT.'/core/manager/jobManager.php';
require_once ROOT.'/core/manager/userManager.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/version.php';

abstract class InputField
{
   const FIRST = 0;
   const DESCRIPTION = 3;
   const POSTED_DATE = 4;
   const OCCURED_DATE = 4;
   const EMPLOYEE = 5;
   const DEFECT_COUNT = 6;
   const TOTAL_DEFECT_COUNT = 7;
   const DISPOSITION = 8;
   const ROOT_CAUSE = 9;
   const DMR_NUMBER = 10;
   const INITIATOR = 11;
   const LOCATION = 12;
   const MAINTENANCE_TICKET_NUMBER = 12;
   const ATTACHMENT = 13;   
   const CORRECTION_DESCRIPTION = 14;
   const DUE_DATE = 15;
   const RESPONSIBLE_EMPLOYEE = 16;
   const RESPONSIBLE_DETAILS = 17;
   const APPROVE_BUTTON = 18;
   const REVIEW_DATE = 19;
   const REVIEWER = 20;
   const EFFECTIVENESS = 21;
   const REVIEW_COMMENTS = 22;
   const ACCEPT_BUTTON = 23;
   const REJECT_BUTTON = 24;
   const CLOSE_BUTTON = 25;
   const REOPEN_BUTTON = 26;
   const LAST = 27;
   const COUNT = InputField::LAST - InputField::FIRST;
}

abstract class View
{
   const NEW_MAINTENANCE_TICKET = 0;
   const EDIT_MAINTENANCE_TICKET = 1;
   const VIEW_MAINTENANCE_TICKET = 2;
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
   $view = View::VIEW_MAINTENANCE_TICKET;
   
   if (getMaintenanceTicketId() == MaintenanceTicket::UNKNOWN_TICKET_ID)
   {
      $view = View::NEW_MAINTENANCE_TICKET;
   }
   else if (Authentication::checkPermissions(Permission::EDIT_MAINTENANCE_TICKET))
   {
      $view = View::EDIT_MAINTENANCE_TICKET;
   }
   
   return ($view);
}

function getMaintenanceTicketId()
{
   $ticketId = MaintenanceTicket::UNKNOWN_TICKET_ID;
   
   $params = getParams();
   
   if ($params->keyExists("ticketId"))
   {
      $ticketId = $params->getInt("ticketId");
   }
   
   return ($ticketId);
}

function getMaintenanceTicket()
{
   static $maintenanceTicket = null;
   
   if ($maintenanceTicket == null)
   {
      $ticketId = getMaintenanceTicketId();
      
      if ($ticketId != MaintenanceTicket::UNKNOWN_TICKET_ID)
      {
         $maintenanceTicket = MaintenanceTicket::load($ticketId);
      }
      else
      {
         $maintenanceTicket = new MaintenanceTicket();
      }
   }
   
   return ($maintenanceTicket);
}

function getAuthor()
{
   return (Authentication::getAuthenticatedUser()->employeeNumber);
}

function getAuthorName()
{
   $userInfo = UserInfo::load(getAuthor());
   $authorName = $userInfo ? $userInfo->getFullName() : null;

   return ($authorName);
}

function isEditable($field)
{
   $view = getView();
   
   // Start with the edit mode, as dictated by the view.
   $isEditable = ((($view == View::NEW_MAINTENANCE_TICKET) ||
                  ($view == View::EDIT_MAINTENANCE_TICKET)) &&
                  // Disable all inputs when the status is CLOSED.
                  (getMaintenanceTicket()->status != MaintenanceTicketStatus::CLOSED));
   
   switch ($field)
   {  
      case InputField::MAINTENANCE_TICKET_NUMBER:
      {
         $isEditable = false;
         break;
      }
      
      /*
      case InputField::CUSTOMER_ID:
      {
         //$isEditable &= (getMaintenanceTicket()->jobId == JobInfo::UNKNOWN_JOB_ID);
         break;
      }
         */
      
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
   $maintenanceTicketNumber = getMaintenanceTicket()->getMaintenanceTicketNumber();
   
   $heading = "Maintenance Ticket: $maintenanceTicketNumber";
   
   $view = getView();
   
   if ($view == View::VIEW_MAINTENANCE_TICKET)
   {
      $heading .= " (view only)";
   }
   
   return ($heading);
}

function getDescription()
{
   $description = "";
   
   $view = getView();
   
   if ($view == View::NEW_MAINTENANCE_TICKET)
   {
      $description = "Create a new maintenance ticket.";
   }
   else if ($view == View::EDIT_MAINTENANCE_TICKET)
   {
      $description = "Update a maintenance ticket.";
   }
   else if ($view == View::VIEW_MAINTENANCE_TICKET)
   {
      $description = "View the status of a maintenance ticket.";
   }
   
   return ($description);
}

function getRequestPanel()
{
   $html = "";

   global $getDisabled;
   
   $maintenanceTicket = getMaintenanceTicket();
   
   $occuredDate = $maintenanceTicket->occured ? Time::toJavascriptDate($maintenanceTicket->occured) : null;
   $postedDateTime = $maintenanceTicket->posted ? Time::toJavascriptDateTime($maintenanceTicket->posted) : null;
   $authorName = getAuthorName();
   $assignedOptions = UserManager::getOptions([Role::MAINTENANCE, Role::OPERATOR], [$maintenanceTicket->assigned], $maintenanceTicket->assigned);

   $jobNumberOptions = JobManager::getJobNumberOptions($maintenanceTicket->jobNumber, true);
   $wcNumberOptions = $wcNumberOptions = JobInfo::getWcNumberOptions(JobInfo::UNKNOWN_JOB_NUMBER, $maintenanceTicket->wcNumber);
   $assignedOptions = UserManager::getOptions([Role::MAINTENANCE], [], $maintenanceTicket->assigned);
   $machineStateOptions = MachineState::getOptions($maintenanceTicket->machineState);
   
   $html = 
<<< HEREDOC
   <div id="request-panel" class="collapsible-panel">
      <form id="update-form" style="display:block">
         <input id="ticket-id-input" type="hidden" name="ticketId" value="$maintenanceTicket->ticketId"/>
         <input type="hidden" name="request" value="save_ticket"/>

         <div class="flex-horizontal flex-v-center collapsible-panel-header">
            <i class="material-icons icon-button expanded-icon">arrow_drop_down</i>
            <i class="material-icons icon-button collapsed-icon">arrow_right</i>
            Summary
         </div>

         <div class="collapsible-panel-content">

            <div class="form-item">
               <div class="form-label">Ticket #</div>
               <input type="text" value="{$maintenanceTicket->getMaintenanceTicketNumber()}" {$getDisabled(InputField::MAINTENANCE_TICKET_NUMBER)}>
            </div>

            <div class="form-item" style="margin-right: 25px">
               <div class="form-label">Occured</div>
               <input type="date" class="form-input-medium" name="occured" value="$occuredDate" {$getDisabled(InputField::OCCURED_DATE)}>
            </div>

            <div class="form-item" style="margin-right: 25px">
               <div class="form-label">Posted</div>
               <input type="datetime-local" class="form-input-medium" name="posted" value="$postedDateTime" {$getDisabled(InputField::POSTED_DATE)}>
            </div>

            <div class="form-item">
               <div class="form-label">Posted By</div>
               <input type="text" value="$authorName" disabled>
            </div>

            <div class="form-item">
               <div class="form-label">Work Center</div>
               <select name="wcNumber" required>
                  $wcNumberOptions
               </select>
            </div>

            <div class="form-item">
               <div class="form-label">Job</div>
               <select name="jobNumber">
                  $jobNumberOptions
               </select>
            </div>

            <div class="form-item">
               <div class="form-label">Machine State</div>
               <select name="machineState" required>
                  $machineStateOptions
               </select>
            </div>

            <div class="form-item">
               <div class="form-label">Assigned To</div>
               <select name="assigned">
                  $assignedOptions
               </select>
            </div>

            <div class="form-item">
               <div class="form-label">Description</div>
               <input id="description-input" type="text" style="width:400px;" name="description" value="$maintenanceTicket->description" required>
            </div>

            <div class="form-item">
               <div class="form-label">Details</div>
               <textarea class="details-input" type="text" name="details" rows="4" maxlength="256" style="width:400px">$maintenanceTicket->details</textarea>
            </div>

            <br>
            
            <div class="flex-horizontal flex-h-center">
               <button id="update-button" type="button">Update</button>            
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
   
   $maintenanceTicket = getMaintenanceTicket();
   
   $html =
<<<HEREDOC
   <div id="attachments-panel" class="collapsible-panel">
      <form id="attachments-form" style="display:block">
         <input type="hidden" name="ticketId" value="{$maintenanceTicket->ticketId}"/>
         <input type="hidden" name="request" value="attach_file"/>

         <div class="flex-horizontal flex-v-center collapsible-panel-header">
            <i class="material-icons icon-button expanded-icon">arrow_drop_down</i>
            <i class="material-icons icon-button collapsed-icon">arrow_right</i>
            Attachments
         </div>

         <div class="collapsible-panel-content">
HEREDOC;
   
   foreach ($maintenanceTicket->attachments as $attachment)
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
               <input type="file" name="attachment" {$getDisabled(InputField::ATTACHMENT)}>               
            </div>

            <div class="form-item">
               <div class="form-label">Filename</div>
               <input type="text" name="filename" maxlength="32" style="width:150px;" {$getDisabled(InputField::ATTACHMENT)} />
            </div>

            <div class="form-item">
               <div class="form-label">Description</div>
               <input type="text" name="description" maxlength="64" style="width:300px;" {$getDisabled(InputField::ATTACHMENT)} />
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

function getHistoryPanel()
{
   $maintenanceTicket = getMaintenanceTicket();
   
   $html = 
<<<HEREDOC
   <div class="history-panel flex-vertical">
      <form id="history-form" style="display:block">
         <input type="hidden" name="ticketId" value="{$maintenanceTicket->ticketId}"/>
         <input type="hidden" name="request" value="add_comment"/>

         <div class="form-section-header">History</div>
HEREDOC;
   
   $activities = ActivityLog::getActivitiesForMaintenanceTicket(getMaintenanceTicketId(), false);  // Order descending
   
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
      if (($activity->activityType == ActivityType::ANNOTATE_MAINTENANCE_TICKET) &&
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
         </div>
      </form>
   </div>
HEREDOC;
   
   return ($html);
}

function getTimeline()
{
   $html = "";
   
   $happyPath = [MaintenanceTicketStatus::REPORTED, MaintenanceTicketStatus::ASSIGNED, MaintenanceTicketStatus::ACKNOWLEDGED, MaintenanceTicketStatus::UNDER_REPAIR, MaintenanceTicketStatus::REPAIRED, MaintenanceTicketStatus::CONFIRMED, MaintenanceTicketStatus::CLOSED];
   
   $maintenanceTicket = getMaintenanceTicket();
   
   $html = "<ul class=\"timeline\">";
   
   $lastAction = null;
   foreach ($maintenanceTicket->actions as $action)
   {
      // Avoid duplicate consecutive actions on the same date.
      if (!$lastAction ||
          !(($action->status == $lastAction->status) &&
            (Time::dateTimeObject($action->dateTime)->format("Y-m-d") == Time::dateTimeObject($lastAction->dateTime)->format("Y-m-d"))))
      {
         $html .= getTimelinePoint($action);
      }
      
      $lastAction = $action;
   }
   
   // Add the remainder of the happy path.
   $lastStatus = end($maintenanceTicket->actions)->status;
   
   $addPoints = false;
   foreach ($happyPath as $status)
   {
      if ($addPoints)
      {
         // Create a dummy PO action.
         $action = new Action();
         $action->status = $status;
         
         $html .= getTimelinePoint($action);
      }
      else if ($status == $lastStatus)
      {
         $addPoints = true;
      }
   }
   
   $html .= "</ul>";
   
   return ($html);
}

function getTimelinePoint($action)
{
   $html = "";
   
   $status = MaintenanceTicketStatus::getLabel($action->status);
   $dateTime = "";
   $username = "";
   $notes = "";
   
   $achieved = ($action->dateTime != null);
   
   if ($achieved)
   {
      $dateTime = Time::dateTimeObject($action->dateTime)->format("m/d/Y");
      $username = UserInfo::getUsername($action->userId);
      $notes = htmlspecialchars($action->notes);
   }
   
   $achievedStr = $achieved ? "true" : "false";
   
   $html =
<<<HEREDOC
   <li data-status="$status" data-achieved="$achievedStr" data-timestamp="$dateTime" data-user="$username" data-notes="$notes"></li>
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
$javascriptFile = "maintenanceTicket.js";
$javascriptClass = "MaintenanceTicket";
$appPageId = AppPage::MAINTENANCE_TICKET;
$heading = getHeading();
$description = getDescription();
$formId = "input-form";
$timeline = getTimeline();
$historyPanel = getHistoryPanel();
$requestPanel = getRequestPanel();
/*
$shortTermCorrectionPanel = getCorrectionPanel(CorrectionType::SHORT_TERM);
$longTermCorrectionPanel = getCorrectionPanel(CorrectionType::LONG_TERM);
$approvePanel = getApprovePanel();
$reviewPanel = getReviewPanel();
*/
$attachmentsPanel = getAttachmentsPanel(); 
$status = getMaintenanceTicket()->status;

include ROOT.'/templates/maintenanceTicketPageTemplate.php'
      
?>
