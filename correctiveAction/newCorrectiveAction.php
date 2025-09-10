<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/core/component/correctiveAction.php';
require_once ROOT.'/core/manager/jobManager.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/roles.php';
require_once ROOT.'/common/version.php';

abstract class InputField
{
   const FIRST = 0;
   const OCCURANCE_DATE = InputField::FIRST;
   const CUSTOMER_ID = 1;
   const JOB_NUMBER = 2;
   const WC_NUMBER = 3;
   const PAN_TICKET_NUMBER = 4;
   const EMPLOYEE = 5;
   const DESCRIPTION = 6;
   const LAST = 7;
   const COUNT = InputField::LAST - InputField::FIRST;
}

function getHeading()
{
   $heading = "Create a new Corrective Action Request";
   
   return ($heading);
}

function getDescription()
{
   $description = "Create a new Corrective Action Request.";
   
   return ($description);
}

function getForm()
{   
   $unknownCorrectiveActionId = CorrectiveAction::UNKNOWN_CA_ID;
   $occuranceDate = Time::toJavascriptDate(Time::now());
   $customerOptions = Customer::getOptions();
   $jobNumberOptions = JobManager::getJobNumberOptions(null, true);
   $employeeOptions = UserInfo::getOptions(Role::$allRoles, [], null);
   
   $html = 
<<< HEREDOC
   <form id="input-form" style="display: block">
      <input type="hidden" name="request" value="save_corrective_action"/>
      <input type="hidden" name="correctiveActionId"/>
      <input id="job-id-input" type="hidden" name="jobId" data-validator="JobIdValidator"/>

      <div class="flex-horizontal" style="justify-content: space-evenly">
         <div class="flex-vertical" style="margin-right: 20px;">
           
            <div class="form-section-header">Pan Ticket Entry</div>               
            <div class="form-item">
               <div class="form-label">Pan Ticket #</div>
               <input id="pan-ticket-code-input" type="text" style="width:50px;" name="panTicketCode" value="">
            </div>               
         
            <div class="form-section-header">Manual Entry</div>
            <div class="form-item">
               <div class="form-label">Job Number</div>
               <select id="job-number-input" name="jobNumber">
                  $jobNumberOptions
               </select>
            </div>
            
            <div class="form-item">
               <div class="form-label">Work Center</div>
               <select id="wc-number-input" name="wcNumber" disabled>
               </select>
            </div>

            <div class="form-item">
               <div class="form-label">Employee</div>
               <div class="flex-horizontal">
                  <select id="employee-number-input" name="employeeNumber" required>
                     $employeeOptions
                  </select>
               </div>
            </div>

         </div>

         <div>
            <div class="form-item">
               <div class="form-label">Occurance</div>
               <input id="occurance-date-input" type="date" class="form-input-medium" name="occuranceDate" value="$occuranceDate" required>
            </div>

            <div class="form-item">
               <div class="form-label">Description</div>
               <textarea id="description-input" class="description-input" type="text" name="description" rows="4" maxlength="256" style="width:300px" required></textarea>
            </div>
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

$versionQuery = versionQuery();
$javascriptFile = "correctiveAction.js";
$javascriptClass = "CorrectiveAction";
$appPageId = AppPage::CORRECTIVE_ACTION;
$heading = getHeading();
$description = getDescription();
$formId = "input-form";
$form = getForm();

include ROOT.'/templates/formPageTemplate.php'
      
?>
