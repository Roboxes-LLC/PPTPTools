<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/version.php';
require_once ROOT.'/core/common/maintenanceTicketDefs.php';
require_once ROOT.'/core/common/role.php';
require_once ROOT.'/core/manager/jobManager.php';
require_once ROOT.'/core/manager/userManager.php';

function getParams()
{
   static $params = null;
   
   if (!$params)
   {
      $params = Params::parse();
   }
   
   return ($params);
}

function getHeading()
{
   $heading = "Create a new Maintenance Ticket";
   
   return ($heading);
}

function getDescription()
{
   $description = "Create a new Maintenance Ticket.";
   
   return ($description);
}

function getWcNumber()
{
   $params = getParams();

   $wcNumber = $params->keyExists("wcNumber") ? $params->getInt("wcNumber") : JobInfo::UNKNOWN_WC_NUMBER;

   return ($wcNumber);
}

function getJobNumber()
{
   $params = getParams();

   $wcNumber = $params->keyExists("jobNumber") ? $params->getInt("jobNumber") : JobInfo::UNKNOWN_JOB_NUMBER;

   return ($wcNumber);
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

function getForm()
{   
   $posted = Time::toJavascriptDate(Time::now());
   $author = Authentication::getAuthenticatedUser()->employeeNumber;
   $authorName = getAuthorName();
   $jobNumberOptions = JobManager::getJobNumberOptions(getJobNumber(), true);
   $wcNumberOptions = $wcNumberOptions = JobInfo::getWcNumberOptions(getJobNumber(), getWcNumber());
   $assignedOptions = UserManager::getOptions([Role::MAINTENANCE], [], null);
   $machineStateOptions = MachineState::getOptions(MachineState::UNKNOWN);
   
   $html = 
<<< HEREDOC
   <form id="input-form" style="display: block">
      <input type="hidden" name="request" value="save_ticket"/>
      <input type="hidden" name="ticketId"/>

      <div class="flex-horizontal" style="justify-content: space-evenly">
         <div class="flex-vertical" style="margin-right: 20px;"> 

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
               <input type="text" style="width:500px;" name="description" value="" required>
            </div>               

            <div class="form-item">
               <div class="form-label">Details</div>
               <textarea class="details-input" type="text" name="details" rows="4" maxlength="256" style="width:300px"></textarea>
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
$javascriptFile = "maintenanceTicket.js";
$javascriptClass = "MaintenanceTicket";
$appPageId = AppPage::MAINTENANCE_TICKET;
$heading = getHeading();
$description = getDescription();
$formId = "input-form";
$form = getForm();

include ROOT.'/templates/formPageTemplate.php'
      
?>
