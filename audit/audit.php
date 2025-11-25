<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/core/component/audit.php';
require_once ROOT.'/core/manager/partManager.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/version.php';

abstract class InputField
{
   const FIRST = 0;
   const AUDIT_NAME = InputField::FIRST;
   const LOCATION = 2;
   const CREATED = 3;
   const AUTHOR = 4;
   const ASSIGNED = 5;
   const SCHEDULED = 6;
   const PART_NUMBER = 7;
   const NOTES = 8;
   const AD_HOC = 9;
   const LAST = 10;
   const COUNT = InputField::LAST - InputField::FIRST;
}

abstract class View
{
   const NEW_AUDIT = 0;
   const VIEW_AUDIT = 1;
   const EDIT_AUDIT = 2;
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
   $view = View::VIEW_AUDIT;
   
   if (getAuditId() == Audit::UNKNOWN_AUDIT_ID)
   {
      $view = View::NEW_AUDIT;
   }
   else if (Authentication::checkPermissions(Permission::EDIT_AUDIT))
   {
      $view = View::EDIT_AUDIT;
   }
   
   return ($view);
}

function getAuditId()
{
   $auditId = Audit::UNKNOWN_AUDIT_ID;
   
   $params = getParams();
   
   if ($params->keyExists("auditId"))
   {
      $auditId = $params->getInt("auditId");
   }
   
   return ($auditId);
}

function getAudit()
{
   static $audit = null;
   
   if ($audit == null)
   {
      $auditId = getAuditId();
      
      if ($auditId != Audit::UNKNOWN_AUDIT_ID)
      {
         $audit =  Audit::load($auditId);
      }
      else
      {
         $audit = new Audit();
         $audit->created = Time::now();
         $audit->author = Authentication::getAuthenticatedUser()->employeeNumber;
      }
   }
   
   return ($audit);
}

function getAuthorName()
{
   $authorName = "";
   
   $userInfo = UserInfo::load(getAudit()->author);
   
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
   $isEditable = (($view == View::NEW_AUDIT) ||
                  ($view == View::EDIT_AUDIT));
   
   switch ($field)
   {
      case InputField::CREATED:
      case InputField::AUTHOR:
      {
         $isEditable = false;
         break;
      }
      
      case InputField::ASSIGNED:
      {
         // Restrict editing once the audit has begun.
         $audit = getAudit();
         $isEditable = ($audit &&
                        ($audit->status != AuditStatus::COMPLETE));
         break;
      }
      
      case InputField::LOCATION:
      case InputField::AD_HOC:
      case InputField::PART_NUMBER:
      {
         // Restrict editing once the audit has begun.
         $audit = getAudit();
         $isEditable = ($audit && 
                        (($audit->status == AuditStatus::UNKNOWN) ||
                         ($audit->status == AuditStatus::SCHEDULED)));
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
   
   if ($view == View::NEW_AUDIT)
   {
      $heading = "Schedule a new audit";
   }
   else if ($view == View::EDIT_AUDIT)
   {
      $heading = "Update an audit";
   }
   else if ($view == View::VIEW_AUDIT)
   {
      $heading = "View an audit";
   }
   
   return ($heading);
}

function getDescription()
{
   $description = "";
   
   $view = getView();
   
   if ($view == View::NEW_AUDIT)
   {
      $description = "Create a new inventory audit.";
   }
   else if ($view == View::EDIT_AUDIT)
   {
      $description = "Update the filters for this audit and start scanning your inventory tickets.";
   }
   else if ($view == View::VIEW_AUDIT)
   {
      $description = "View a previously saved audit in detail.";
   }
   
   return ($description);
}

function getForm()
{
   global $getDisabled;
   
   $auditId = getAuditId();
   $audit = getAudit();
   $createdDate = $audit->created ? Time::dateTimeObject($audit->created)->format("n/j/Y h:i A") : null;
   $authorName = getAuthorName();
   $assignedOptions = UserManager::getOptions([Role::SHIPPER], [], $audit->assigned);
   $locationOptions = ShipmentLocation::getOptions($audit->location);
   $partNumberOptions = PartManager::getPptpPartNumberOptions($audit->partNumber, true);
   $isAdHoc = $audit->isAdHoc ? "checked" : "";
   
   $html = 
<<< HEREDOC
   <form id="input-form" style="display: block">
      <input id="audit-id-input" type="hidden" name="auditId" value="$auditId"/>
      <input type="hidden" name="request" value="save_audit"/>

      <div class="form-item">
         <div class="form-label">Audit Name</div>
         <input id="audit-name-input" type="text" name="auditName" maxlength="64" style="width:200px;" value="$audit->auditName" {$getDisabled(InputField::AUDIT_NAME)} required/>
      </div>

      <div class="form-item">
         <div class="form-label">Created</div>
         <input type="text" style="width:200px;" value="$createdDate" {$getDisabled(InputField::CREATED)}/>
      </div>

      <div class="form-item">
         <div class="form-label">Author</div>
         <input type="text" name="author" style="width:200px;" value="$authorName" {$getDisabled(InputField::AUTHOR)} />
      </div>

      <div class="form-item">
         <div class="form-label">Assigned</div>
         <select name="assigned" {$getDisabled(InputField::ASSIGNED)} required>
            $assignedOptions
         </select>
      </div>

      <div class="flex-horizontal">
         <div class="form-item" style="margin-right:20px">
            <div class="form-label">Location</div>
            <select id="location-input" name="location" {$getDisabled(InputField::LOCATION)} required>
               $locationOptions
            </select>
         </div>

         <div class="form-item">
            <input id="ad-hoc-input" type="checkbox" name="isAdHoc" $isAdHoc {$getDisabled(InputField::AD_HOC)} style="margin-right:10px"/>
            <div class="form-label">Ad-hoc</div>
         </div>
      </div>

      <div class="form-item">
         <div class="form-label">Part Number</div>
         <select id="part-number-input" name="partNumber" {$getDisabled(InputField::PART_NUMBER)}>
            $partNumberOptions
         </select>
      </div>

      <div class="form-item">
         <div class="form-label">Notes</div>
         <textarea class="notes-input" type="text" name="notes" rows="4" maxlength="256" style="width:300px" {$getDisabled(InputField::NOTES)}>$audit->notes</textarea>
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
$javascriptFile = "audit.js";
$javascriptClass = "Audit";
$appPageId = AppPage::AUDIT;
$heading = getHeading();
$description = getDescription();
$formId = "input-form";
$form = getForm();

include ROOT.'/templates/formPageTemplate.php'
      
?>
