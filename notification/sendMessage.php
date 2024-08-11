<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/core/common/notificationDefs.php';
require_once ROOT.'/core/component/contact.php';
require_once ROOT.'/core/manager/userManager.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/version.php';

abstract class InputField
{
   const FIRST = 0;
   const TO = InputField::FIRST;
   const PRIORITY = 2;
   const SUBJECT = 2;
   const MESSAGE = 3;
   const LAST = 4;
   const COUNT = InputField::LAST - InputField::FIRST;
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

function getToInputs()
{
   $html = "";
   
   $users = UserManager::getUsers();
   
   foreach ($users as $user)
   {
      if (Permission::getPermission(Permission::NOTIFICATIONS)->isSetIn($user->permissions))
      {
         $html .=
<<< HEREDOC
         <div class="flex-horizontal flex-v-center">
            <input type="checkbox" class="target-input" name="target[]" value="{$user->employeeNumber}"/>
            &nbsp;&nbsp;
            <div>{$user->getFullName()}</div>
         </div>
HEREDOC;
      }
   }
   
   return ($html);
}

function getForm()
{   
   $priorityOptions = NotificationPriority::getOptions(NotificationPriority::INFORMATIONAL);
   $toInputs = getToInputs();
   
   $html = 
<<< HEREDOC
   <form id="input-form" style="display: block">
      <input type="hidden" name="request" value="send_message"/>

      <div class="form-item">
         <div class="form-label">To</div>
         <div class="flex-vertical" style="maxHeight: 300px; overflow:auto">
            $toInputs
         </div>  
      </div>
   
      <div class="form-item">
         <div class="form-label">Priority</div>
         <div class="flex-horizontal">
            <select id="priority-input" name="priority">
               $priorityOptions
            </select>
         </div>
      </div>

      <div class="form-item">
         <div class="form-label">Subject</div>
         <input id="subject-input" type="text" name="subject" maxlength="256" style="width:350px;"/>
      </div>

      <div class="form-item">
         <div class="form-label">Message</div>         
         <textarea class="comments-input" type="text" form="input-form" name="message" rows="4" maxlength="256" style="width:350px"></textarea>
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
$javascriptFile = "notification.js";
$javascriptClass = "Notification";
$appPageId = AppPage::NOTIFICATION;
$heading = "Send Message";
$description = "";
$formId = "input-form";
$form = getForm();
$saveButtonLabel = "Send";

include ROOT.'/templates/formPageTemplate.php'
      
?>
