<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/version.php';

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
$javascriptFile = "correctiveAction.js";
$javascriptClass = "CorrectiveAction";
$appPageId = AppPage::CORRECTIVE_ACTION;
$heading = "Corrective Action Requests";
$description = "Nom nom nom";
$filterTemplate = "correctiveActionFilter.php";
$filterActiveActions = filter_var(Session::getVar(Session::CA_ACTIVE_ACTIONS), FILTER_VALIDATE_BOOLEAN);
$filterStartDate = getFilterDate(Session::CA_START_DATE);
$filterEndDate = getFilterDate(Session::CA_END_DATE);
$newButtonLabel = "Add CAR";
$reportFileName = "correctiveActions.csv";

include ROOT.'/templates/tablePageTemplate.php'
      
?>
