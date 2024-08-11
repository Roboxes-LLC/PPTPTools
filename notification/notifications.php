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

function getFilterShowAllUnacknowledged()
{
   $showAllUnacknowledged = true;
   
   if (Session::isset(Session::NOTIFICATION_SHOW_ALL_UNACKNOWLEDGED))
   {
      $showAllUnacknowledged = filter_var(Session::getVar(Session::NOTIFICATION_SHOW_ALL_UNACKNOWLEDGED), FILTER_VALIDATE_BOOLEAN);
   }
   
   return ($showAllUnacknowledged);
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
$javascriptFile = "notification.js";
$javascriptClass = "Notification";
$appPageId = AppPage::NOTIFICATION;
$heading = "Messages";
$description = "Nom nom nom";
$filterTemplate = "notificationFilter.php";
$filterShowAllUnacknowledged = getFilterShowAllUnacknowledged();
$filterStartDate = getFilterDate(Session::NOTIFICATION_START_DATE);
$filterEndDate = getFilterDate(Session::NOTIFICATION_END_DATE);
$newButtonLabel = "Send message";
$reportFileName = "notifications.csv";

include ROOT.'/templates/tablePageTemplate.php'
      
?>
