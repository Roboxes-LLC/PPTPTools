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
$javascriptFile = "audit.js";
$javascriptClass = "Audit";
$appPageId = AppPage::AUDIT;
$heading = "Inventory Audits";
$description = "Nom nom nom";
$filterTemplate = "auditFilter.php";
$filterActiveAudits = filter_var(Session::getVar(Session::AUDIT_ACTIVE_AUDITS), FILTER_VALIDATE_BOOLEAN);
$filterStartDate = getFilterDate(Session::AUDIT_START_DATE);
$filterEndDate = getFilterDate(Session::AUDIT_END_DATE);
$newButtonLabel = "New Audit";
$reportFileName = "inventoryAudits.csv";

include ROOT.'/templates/tablePageTemplate.php'
      
?>
