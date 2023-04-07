<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/session.php';
require_once ROOT.'/common/activity.php';
require_once ROOT.'/common/filterDateType.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/menu.php';
require_once ROOT.'/common/authentication.php';
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
   header('Location: ../login.php');
   exit;
}

$root = ROOT;
$versionQuery = versionQuery();
$javascriptFile = "skid.js";
$javascriptClass = "Skid";
$activity = Activity::SKID;
$heading = "Product Tracking";
$description = "Something something something";
$newButtonLabel = "Add Skid";
$filterTemplate = "skidFilter.php";
$filterDateType = FilterDateType::SKID_CREATION_DATE;
$filterStartDate = getFilterDate(Session::SKID_START_DATE);
$filterEndDate = getFilterDate(Session::SKID_END_DATE);
$filterActiveSkids = filter_var(Session::getVar(Session::SKID_ACTIVE_SKIDS), FILTER_VALIDATE_BOOLEAN);

$customScript =
<<<HEREDOC
   // Listen for barcodes.
   var barcodeScanner = new BarcodeScanner();
   barcodeScanner.onBarcode = PAGE.onBarcode;
HEREDOC;

include ROOT.'/templates/tablePageTemplate.php'
      
?>
