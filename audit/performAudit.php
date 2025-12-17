<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/version.php';
require_once ROOT.'/core/component/audit.php';
require_once ROOT.'/core/manager/partManager.php';

function getParams()
{
   static $params = null;
   
   if (!$params)
   {
      $params = Params::parse();
   }
   
   return ($params);
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

function getForm()
{
   $auditId = getAuditId();
   $audit = getAudit();
   $locationOptions = ShipmentLocation::getOptions($audit->location);
   $partNumberOptions = PartManager::getPptpPartNumberOptions($audit->partNumber, true);
   
   $html =
<<< HEREDOC
   <form id="input-form" style="display: block">
      <input id="audit-id-input" type="hidden" name="auditId" value="$auditId"/>
      <input type="hidden" name="request" value="perform_audit"/>
      <input id="complete-input" type="hidden" name="complete" value="false"/>

      <div class="flex-horizontal">
      
         <div class="form-item" style="margin-right:25px">
            <div class="form-label">Audit Name</div>
            <input type="text" name="auditName" maxlength="64" style="width:150px;" value="$audit->auditName" disabled />
         </div>
   
         <div class="form-item" style="margin-right:25px">
            <div class="form-label">Location</div>
            <select name="location" disabled>
               $locationOptions
            </select>
         </div>
         
         <div class="form-item">
            <div class="form-label">Part Number</div>
            <select name="partNumber" disabled>
               $partNumberOptions
            </select>
         </div>

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

$root = ROOT;
$versionQuery = versionQuery();
$javascriptFile = "audit.js";
$javascriptClass = "Audit";
$auditStatus = getAudit()->status;
$isAdHoc = getAudit()->isAdHoc ? "true" : "false";
$hasCorrections = getAudit()->hasCorrections() ? "true" : "false";
$appPageId = AppPage::AUDIT;
$heading = "Inventory Audit";
$formId = "input-form";
$form = getForm();

include ROOT.'/templates/performAuditPageTemplate.php'
      
?>
