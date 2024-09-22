<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/version.php';
require_once ROOT.'/core/manager/userManager.php';

function getFilterDate($filterVar)
{
   $dateTime = Time::now(Time::now("Y-m-d"));
   
   if (Session::isset($filterVar))
   {
      $dateTime = Session::getVar($filterVar);
   }
   
   // Convert to Javascript date format.
   $dateTime = Time::toJavascriptDate($dateTime);
   
   return ($dateTime);
}

function getOperatorOptions()
{
   $options = new stdClass();
   
   $operators = UserManager::getOperators();
   
   foreach ($operators as $operator)
   {
      $options->{$operator->employeeNumber} = $operator->getFullName();
   }
   
   return ($options);
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
$javascriptFile = "schedule.js";
$javascriptClass = "Schedule";
$appPageId = AppPage::SCHEDULE;
$heading = "Production Schedule";
$description = "Nom nom nom";
$filterTemplate = "scheduleFilter.php";
$filterMfgDate = getFilterDate(Session::SCHEDULE_MFG_DATE);
$operatorOptions = json_encode(getOperatorOptions());

include ROOT.'/templates/schedulePageTemplate.php'
      
?>
