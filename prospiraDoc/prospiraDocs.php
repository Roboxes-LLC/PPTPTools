<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/filterDateType.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/version.php';
require_once ROOT.'/core/common/shipmentDefs.php';

function getFilterShipmentLocation()
{
   $shipmentLocation = ShipmentLocation::WIP;
   
   if (Session::isset(Session::PROSPIRA_DOC_LOCATION))
   {
      $shipmentLocation = intval(Session::getVar(Session::PROSPIRA_DOC_LOCATION));
   }
   
   return ($shipmentLocation);
}

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
$javascriptFile = "prospiraDoc.js";
$javascriptClass = "ProspiraDoc";
$appPageId = AppPage::PROSPIRA_DOC;
$heading = "Prospira Documentation";
$description = "Parts made for the Prospira customer require specific labels and documentation.  Make and edit them here.";
$filterTemplate = "partsInventoryFilter.php";
$filterLocation = getFilterShipmentLocation();
$filterStartDate = getFilterDate(Session::PROSPIRA_DOC_START_DATE);
$filterEndDate = getFilterDate(Session::PROSPIRA_DOC_END_DATE);
$newButtonLabel = "Add documentation";
$reportFileName = "prospiraDocs.csv";

include ROOT.'/templates/tablePageTemplate.php'
      
?>
