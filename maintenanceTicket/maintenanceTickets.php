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
$javascriptFile = "maintenanceTicket.js";
$javascriptClass = "MaintenanceTicket";
$appPageId = AppPage::MAINTENANCE_TICKET;
$heading = "Maintenance Tickets";
$description = "Nom nom nom";
$filterTemplate = "maintenanceTicketFilter.php";
$filterActiveTickets = filter_var(Session::getVar(Session::MAINTENANCE_TICKET_ACTIVE_TICKETS), FILTER_VALIDATE_BOOLEAN);
$filterStartDate = getFilterDate(Session::MAINTENANCE_TICKET_START_DATE);
$filterEndDate = getFilterDate(Session::MAINTENANCE_TICKET_END_DATE);
$newButtonLabel = "New ticket";
$reportFileName = "maintenanceTickets.csv";

include ROOT.'/templates/tablePageTemplate.php'
      
?>
