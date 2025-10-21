<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/core/common/notification.php';
require_once ROOT.'/core/component/correctiveAction.php';
require_once ROOT.'/core/manager/activityLog.php';
require_once ROOT.'/core/manager/partManager.php';
require_once ROOT.'/core/manager/userManager.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/isoInfo.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/version.php';

abstract class InputField
{
   const FIRST = 0;
   const CUSTOMER_ID = InputField::FIRST;
   const CUSTOMER_PART_NUMBER = 1;
   const PPTP_PART_NUMBER = 2;
   const DESCRIPTION = 3;
   const OCCURANCE_DATE = 4;
   const EMPLOYEE = 5;
   const DEFECT_COUNT = 6;
   const TOTAL_DEFECT_COUNT = 7;
   const DISPOSITION = 8;
   const ROOT_CAUSE = 9;
   const DMR_NUMBER = 10;
   const INITIATOR = 11;
   const LOCATION = 12;
   const CAR_NUMBER = 12;
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
   const NEW_CORRECTIVE_ACTION = 0;
   const EDIT_CORRECTIVE_ACTION = 1;
   const VIEW_CORRECTIVE_ACTION = 2;
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
   $view = View::VIEW_CORRECTIVE_ACTION;
   
   if (getCorrectiveActionId() == CorrectiveAction::UNKNOWN_CA_ID)
   {
      $view = View::NEW_CORRECTIVE_ACTION;
   }
   else if (Authentication::checkPermissions(Permission::EDIT_CORRECTIVE_ACTION))
   {
      $view = View::EDIT_CORRECTIVE_ACTION;
   }
   
   return ($view);
}

function getCorrectiveActionId()
{
   $correctiveActionId = CorrectiveAction::UNKNOWN_CA_ID;
   
   $params = getParams();
   
   if ($params->keyExists("correctiveActionId"))
   {
      $correctiveActionId = $params->getInt("correctiveActionId");
   }
   
   return ($correctiveActionId);
}

function getCorrectiveAction()
{
   static $correctiveAction = null;
   
   if ($correctiveAction == null)
   {
      $correctiveActionId = getCorrectiveActionId();
      
      if ($correctiveActionId != CorrectiveAction::UNKNOWN_CA_ID)
      {
         $correctiveAction = CorrectiveAction::load($correctiveActionId);
      }
      else
      {
         $correctiveAction = new CorrectiveAction();
      }
   }
   
   return ($correctiveAction);
}

function isEditable($field)
{
   $view = getView();
   
   // Start with the edit mode, as dictated by the view.
   $isEditable = ((($view == View::NEW_CORRECTIVE_ACTION) ||
                  ($view == View::EDIT_CORRECTIVE_ACTION)) &&
                  // Disable all inputs when the status is CLOSED.
                  (getCorrectiveAction()->status != CorrectiveActionStatus::CLOSED));
   
   switch ($field)
   {  
      case InputField::CAR_NUMBER:
      case InputField::TOTAL_DEFECT_COUNT:
      case InputField::CUSTOMER_PART_NUMBER:
      case InputField::PPTP_PART_NUMBER:
      {
         $isEditable = false;
         break;
      }
      
      case InputField::CUSTOMER_ID:
      {
         $isEditable &= (getCorrectiveAction()->jobId == JobInfo::UNKNOWN_JOB_ID);
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
   $correctiveActionNumber = getCorrectiveAction()->getCorrectiveActionNumber();
   
   $heading = "Corrective Action Request: $correctiveActionNumber";
   
   $view = getView();
   
   if ($view == View::VIEW_CORRECTIVE_ACTION)
   {
      $heading .= " (view only)";
   }
   
   return ($heading);
}

function getDescription()
{
   $description = "";
   
   $view = getView();
   
   if ($view == View::NEW_CORRECTIVE_ACTION)
   {
      $description = "Create a new corrective action request.";
   }
   else if ($view == View::EDIT_CORRECTIVE_ACTION)
   {
      $description = "Work on a corrective action request.";
   }
   else if ($view == View::VIEW_CORRECTIVE_ACTION)
   {
      $description = "View the status of a corrective action request.";
   }
   
   return ($description);
}

function getRequestPanel()
{
   global $getDisabled;
   
   $correctiveAction = getCorrectiveAction();
   
   $occuranceDate = $correctiveAction->occuranceDate ? Time::toJavascriptDate($correctiveAction->occuranceDate) : null;
   
   $ispectionLink = ($correctiveAction->inspectionId != Inspection::UNKNOWN_INSPECTION_ID) ?
                       "<a href=\"/inspection/viewInspection.php?inspectionId=$correctiveAction->inspectionId\">Inspection</a>" :
                       "";
   
   $employeeOptions = UserManager::getOptions(Role::$values, [], $correctiveAction->employee);
   
   $customerOptions = Customer::getOptions($correctiveAction->getCustomerId());
   
   $batchSize = $correctiveAction->batchSize ? $correctiveAction->batchSize : null;
   $dimensionalDefectCount = $correctiveAction->dimensionalDefectCount ? $correctiveAction->dimensionalDefectCount : null;
   $platingDefectCount = $correctiveAction->platingDefectCount ? $correctiveAction->platingDefectCount : null;
   $otherDefectCount = $correctiveAction->otherDefectCount ? $correctiveAction->otherDefectCount : null;
   
   $pptpPartNumber = null;
   $customerPartNumber = null;
   $job = JobInfo::load($correctiveAction->jobId);
   if ($job)
   {
      $pptpPartNumber = JobInfo::getJobPrefix($job->jobNumber);
      $part = Part::load($pptpPartNumber, false);  // Use PPTP number.
      if ($part)
      {
         $customerPartNumber = $part->customerNumber;
      }
   }
   
   $dispositionOptions = Disposition::getOptions(Disposition::getDispositions($correctiveAction->disposition));
   
   $initiatorOptions = CorrectiveActionInitiator::getOptions($correctiveAction->initiator);
   
   $locationOptions = CorrectiveActionLocation::getOptions($correctiveAction->location);
   
   $html = 
<<< HEREDOC
   <div id="request-panel" class="collapsible-panel">
      <form id="request-form" style="display:block">
         <input id="corrective-action-id-input" type="hidden" name="correctiveActionId" value="$correctiveAction->correctiveActionId"/>
         <input type="hidden" name="jobId" value="$correctiveAction->jobId"/>
         <input type="hidden" name="request" value="save_corrective_action"/>

         <div class="flex-horizontal flex-v-center collapsible-panel-header">
            <i class="material-icons icon-button expanded-icon">arrow_drop_down</i>
            <i class="material-icons icon-button collapsed-icon">arrow_right</i>
            Summary
         </div>

         <div class="collapsible-panel-content">

            <div class="form-item">
               <div class="form-label">CAR#</div>
               <input type="text" value="{$correctiveAction->getCorrectiveActionNumber()}" {$getDisabled(InputField::CAR_NUMBER)}>
            </div>

            <div class="flex-horizontal">
               <div class="form-item" style="margin-right: 25px">
                  <div class="form-label">Occurance</div>
                  <input type="date" class="form-input-medium" name="occuranceDate" value="$occuranceDate" {$getDisabled(InputField::OCCURANCE_DATE)}>
               </div>
               <div class="form-item" style="margin-right: 25px">
                  $ispectionLink
               </div>
            </div>

            <div class="form-item">
               <div class="form-label">Description</div>
               <textarea class="comments-input" type="text" name="description" rows="5" maxlength="512" style="width:500px" {$getDisabled(InputField::DESCRIPTION)}>$correctiveAction->description</textarea>            
            </div>

            <div class="form-item">
               <div class="form-label">Customer</div>
               <div class="flex-horizontal">
                  <select name="customerId" {$getDisabled(InputField::CUSTOMER_ID)}>
                     $customerOptions
                  </select>
               </div>
            </div>
      
            <div class="form-item">
               <div class="form-label-long">Customer Part #</div>
               <input type="text" maxlength="16" style="width:150px;" value="{$customerPartNumber}" {$getDisabled(InputField::CUSTOMER_PART_NUMBER)} />
            </div>
      
            <div class="form-item">
               <div class="form-label-long">PPTP Part #</div>
               <input type="text" maxlength="16" style="width:150px;" value="{$pptpPartNumber}" {$getDisabled(InputField::PPTP_PART_NUMBER)} />
            </div>

            <div class="form-item">
               <div class="form-label">Employee</div>
               <div class="flex-horizontal">
                  <select id="employee-number-input" name="employeeNumber" {$getDisabled(InputField::EMPLOYEE)}>
                     $employeeOptions
                  </select>
               </div>
            </div>

            <div class="flex-horizontal">
               <div class="form-item" style="margin-right: 25px">
                  <div class="form-label">Batch Size</div>
                  <input type="number" name="batchSize" style="width:50px" value="$batchSize" {$getDisabled(InputField::DEFECT_COUNT)} />            
               </div>
            
               <div class="form-item">
                  <div class="form-label">Total Defects</div>
                  <input id="total-defects-input" type="number" style="width:50px" value="{$correctiveAction->getTotalDefectCount()}" {$getDisabled(InputField::TOTAL_DEFECT_COUNT)} />            
               </div>
            </div>

            <div class="form-item">
               <div class="form-label">Dimensional Defects</div>
               <input id="dimensional-defects-input" type="number" name="dimensionalDefectCount" style="width:50px" value="$dimensionalDefectCount" {$getDisabled(InputField::DEFECT_COUNT)} />            
            </div>

            <div class="form-item">
               <div class="form-label">Plating Defects</div>
               <input id="plating-defects-input" type="number" name="platingDefectCount" style="width:50px" value="$platingDefectCount" {$getDisabled(InputField::DEFECT_COUNT)} />            
            </div>

            <div class="form-item">
               <div class="form-label">Other Defects</div>
               <input id="other-defects-input" type="number" name="otherDefectCount" style="width:50px" value="$otherDefectCount" {$getDisabled(InputField::DEFECT_COUNT)} />            
            </div>

            <div class="form-item">
               <div class="form-label">Disposition</div>
               <div class="flex-horizontal">
                  <select name="disposition[]" style="height:150" multiple {$getDisabled(InputField::DISPOSITION)}>
                     $dispositionOptions
                  </select>
               </div>
            </div>

            <div class="form-item">
               <div class="form-label">Root Cause</div>
               <input type="text" name="rootCause" maxlength="256" style="width:400px;" value="{$correctiveAction->rootCause}" {$getDisabled(InputField::ROOT_CAUSE)} />
            </div>

            <div class="form-item">
               <div class="form-label">Customer DMR</div>
               <input type="text" name="dmrNumber" maxlength="32" style="width:150px;" value="{$correctiveAction->dmrNumber}" {$getDisabled(InputField::DMR_NUMBER)} />
            </div>

            <div class="form-item">
               <div class="form-label">Initiated By</div>
               <div class="flex-horizontal">
                  <select name="initiator" {$getDisabled(InputField::INITIATOR)}>
                     $initiatorOptions
                  </select>
               </div>
            </div>

            <div class="form-item">
               <div class="form-label">Product Location</div>
               <div class="flex-horizontal">
                  <select name="location" {$getDisabled(InputField::INITIATOR)}>
                     $locationOptions
                  </select>
               </div>
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

function getCorrectionPanel($correctionType)
{
   global $getDisabled;
   
   $correctiveAction = getCorrectiveAction();

   $correction = null;
   $heading = CorrectionType::getLabel($correctionType) . " Correction";
   
   switch ($correctionType)
   {
      case CorrectionType::SHORT_TERM:
      {
         $correction = $correctiveAction->shortTermCorrection;
         break;
      }
      
      case CorrectionType::LONG_TERM:
      {
         $correction = $correctiveAction->longTermCorrection;
         break;
      }
      
      default:
      {
         // Should never get here.
         $correction = new Correction();
         break;
      }
   }
   
   $formId = CorrectionType::getInputPrefix($correctionType)."-form";
   
   $panelId = CorrectionType::getClassPrefix($correctionType)."-correction-panel";
   
   $updateButtonId = CorrectionType::getClassPrefix($correctionType)."-correction-update-button";
   
   $dueDate = $correction->dueDate ? Time::toJavascriptDate($correction->dueDate) : null;
   
   $employeeOptions = UserManager::getOptions(Role::$values, [], $correction->employee);
   
   $html =
<<< HEREDOC
   <div id="$panelId" class="collapsible-panel">
      <form id="$formId" style="display:block">
         <input id="corrective-action-id-input" type="hidden" name="correctiveActionId" value="$correctiveAction->correctiveActionId"/>
         <input type="hidden" name="correctionType" value="$correctionType"/>
         <input type="hidden" name="request" value="save_correction"/>
         
         <div class="flex-horizontal flex-v-center collapsible-panel-header">
            <i class="material-icons icon-button expanded-icon">arrow_drop_down</i>
            <i class="material-icons icon-button collapsed-icon">arrow_right</i>
            $heading
         </div>
         
         <div class="collapsible-panel-content">
                                
            <div class="form-item">
               <div class="form-label">Correction Plan</div>
               <textarea class="comments-input" type="text" name="description" rows="5" maxlength="512" style="width:500px" {$getDisabled(InputField::CORRECTION_DESCRIPTION)}>$correction->description</textarea>
            </div>

            <div class="form-item">
               <div class="form-label">Due By</div>
               <input type="date" class="form-input-medium" name="dueDate" value="$dueDate" {$getDisabled(InputField::DUE_DATE)}>
            </div>

            <div class="form-section-header">Delegation</div>  

            <div class="form-item" style="margin-right: 25px">
               <div class="form-label">Responsible Party</div>
               <div class="flex-horizontal">
                  <select id="employee-number-input" name="employee" {$getDisabled(InputField::RESPONSIBLE_EMPLOYEE)}>
                     $employeeOptions
                  </select>
               </div>
            </div>

            <div class="form-item">
               <div class="form-label">Details</div>
               <input type="text" name="responsibleDetails" maxlength="64" style="width:200px;" value="{$correction->responsibleDetails}" {$getDisabled(InputField::RESPONSIBLE_DETAILS)} />
            </div>
            
            <br>
            
            <div class="flex-horizontal flex-h-center">
               <button id="$updateButtonId" class="update-correction-button" type="button">Update</button>
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
   
   $correctiveAction = getCorrectiveAction();
   
   $html =
<<< HEREDOC
   <div id="approve-panel" class="collapsible-panel">
      <form id="approve-form" style="display:block">
      
         <input id="corrective-action-id-input" type="hidden" name="correctiveActionId" value="$correctiveAction->correctiveActionId"/>
         <input type="hidden" name="request" value="approve_corrective_action"/>
         <input id="is-approved-input" type="hidden" name="isApproved" value="false"/>
         
         <div class="flex-horizontal flex-v-center collapsible-panel-header">
            <i class="material-icons icon-button expanded-icon">arrow_drop_down</i>
            <i class="material-icons icon-button collapsed-icon">arrow_right</i>
            Approval
         </div>
         
         <div class="collapsible-panel-content">
         
            <div class="form-item">
               <div class="form-label">Notes</div>
               <textarea id="approve-notes-input" class="comments-input" type="text" name="approveNotes" rows="4" maxlength="256" style="width:300px" {$getDisabled(InputField::APPROVE_BUTTON)}></textarea>
            </div>
            
            <br>
            
            <div class="flex-horizontal flex-h-center">
               <button id="approve-button" type="button" class="accent-button" style="margin-right:20px" {$getDisabled(InputField::APPROVE_BUTTON)} >Approve</button>
               <button id="unapprove-button" type="button" {$getDisabled(InputField::APPROVE_BUTTON)} >Unapprove</button>
            </div>
            
         </div>
         
      </form>
   </div>
HEREDOC;
   
   return ($html);
}

function getReviewPanel()
{
   global $getDisabled;
   
   $correctiveAction = getCorrectiveAction();
   
   $review = getCorrectiveAction()->review;
   
   $reviewDate = $review->reviewDate ? Time::toJavascriptDate($review->reviewDate) : Time::toJavascriptDate(Time::now());
   
   $reviewer = ($review->reviewer != UserInfo::UNKNOWN_EMPLOYEE_NUMBER) ?
                  $review->reviewer :
                  Authentication::getAuthenticatedUser()->employeeNumber;
                          
   
   $employeeOptions = UserManager::getOptions([Role::ADMIN], [$reviewer], $reviewer);
   
   $html =
<<< HEREDOC
   <div id="review-panel" class="collapsible-panel">
      <form id="review-form" style="display:block">      
         <input id="corrective-action-id-input" type="hidden" name="correctiveActionId" value="$correctiveAction->correctiveActionId"/>
         <input type="hidden" name="request" value="review_corrective_action"/>

         <div class="flex-horizontal flex-v-center collapsible-panel-header">
            <i class="material-icons icon-button expanded-icon">arrow_drop_down</i>
            <i class="material-icons icon-button collapsed-icon">arrow_right</i>
            Management Review
         </div>

         <div class="collapsible-panel-content">

            <div class="form-item">
               <div class="form-label">Review Date</div>
               <input type="date" class="form-input-medium" name="reviewDate" value="$reviewDate" {$getDisabled(InputField::REVIEW_DATE)}>
            </div>
   
            <div class="form-item" style="margin-right: 25px">
               <div class="form-label">Reviewed By</div>
               <div class="flex-horizontal">
                  <select id="employee-number-input" name="reviewer" {$getDisabled(InputField::REVIEWER)}>
                     $employeeOptions
                  </select>
               </div>
            </div>
   
            <div class="form-item">
               <div class="form-label">Effectiveness</div>
               <input type="text" name="effectiveness" maxlength="256" style="width:400px;" value="{$review->effectiveness}" {$getDisabled(InputField::EFFECTIVENESS)} />
            </div>
   
            <div class="form-item">
               <div class="form-label">Comments</div>
               <textarea class="comments-input" type="text" name="comments" rows="5" maxlength="512" style="width:500px" {$getDisabled(InputField::REVIEW_COMMENTS)}>$review->comments</textarea>
            </div>

            <br>

            <div class="flex-horizontal flex-h-center">
               <button id="complete-button" type="button" class="accent-button" style="margin-right:20px" {$getDisabled(InputField::ACCEPT_BUTTON)}>Complete</button>
               <button id="close-button" type="button"  class="accent-button"   style="margin-right:20px" {$getDisabled(InputField::CLOSE_BUTTON)}>Close</button>
               <button id="reopen-button" type="button"                         style="margin-right:20px" {$getDisabled(InputField::REOPEN_BUTTON)}>Reopen</button>
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
   
   $correctiveAction = getCorrectiveAction();
   
   $html =
<<<HEREDOC
   <div id="attachments-panel" class="collapsible-panel">
      <form id="attachments-form" style="display:block">
         <input type="hidden" name="correctiveActionId" value="{$correctiveAction->correctiveActionId}"/>
         <input type="hidden" name="request" value="attach_file"/>

         <div class="flex-horizontal flex-v-center collapsible-panel-header">
            <i class="material-icons icon-button expanded-icon">arrow_drop_down</i>
            <i class="material-icons icon-button collapsed-icon">arrow_right</i>
            Attachments
         </div>

         <div class="collapsible-panel-content">
HEREDOC;
   
   foreach ($correctiveAction->attachments as $attachment)
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
   $correctiveAction = getCorrectiveAction();
   
   $html = 
<<<HEREDOC
   <div class="history-panel flex-vertical">
      <form id="history-form" style="display:block">
         <input type="hidden" name="correctiveActionId" value="{$correctiveAction->correctiveActionId}"/>
         <input type="hidden" name="request" value="add_comment"/>

         <div class="form-section-header">History</div>
HEREDOC;
   
   $activities = ActivityLog::getActivitiesForCorrectiveAction(getCorrectiveActionId(), false);  // Order descending
   
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
      if (($activity->activityType == ActivityType::ANNOTATE_CORRECTIVE_ACTION) &&
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
   
   $happyPath = [CorrectiveActionStatus::OPEN, CorrectiveActionStatus::APPROVED, CorrectiveActionStatus::REVIEWED, CorrectiveActionStatus::CLOSED];
   
   $correctiveAction = getCorrectiveAction();
   
   $html = "<ul class=\"timeline\">";
   
   $lastAction = null;
   foreach ($correctiveAction->actions as $action)
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
   $lastStatus = end($correctiveAction->actions)->status;
   
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
   
   $status = CorrectiveActionStatus::getLabel($action->status);
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
$javascriptFile = "correctiveAction.js";
$javascriptClass = "CorrectiveAction";
$appPageId = AppPage::CORRECTIVE_ACTION;
$heading = getHeading();
$iso = IsoInfo::getIsoNumber(IsoDoc::CORRECTIVE_ACTION_REQUEST);
$description = getDescription();
$formId = "input-form";
$timeline = getTimeline();
$historyPanel = getHistoryPanel();
$requestPanel = getRequestPanel();
$shortTermCorrectionPanel = getCorrectionPanel(CorrectionType::SHORT_TERM);
$longTermCorrectionPanel = getCorrectionPanel(CorrectionType::LONG_TERM);
$approvePanel = getApprovePanel();
$reviewPanel = getReviewPanel();
$attachmentsPanel = getAttachmentsPanel(); 
$status = getCorrectiveAction()->status;

include ROOT.'/templates/correctiveActionPageTemplate.php'
      
?>
