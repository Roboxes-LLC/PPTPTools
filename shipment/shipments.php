<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/version.php';
require_once ROOT.'/core/component/shipment.php';

function getFilterShipmentLocation()
{
   $shipmentLocation = ShipmentLocation::PPTP;
   
   if (Session::isset(Session::SHIPMENT_SHIPMENT_LOCATION))
   {
      $shipmentLocation = intval(Session::getVar(Session::SHIPMENT_SHIPMENT_LOCATION));
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
$javascriptFile = "shipment.js";
$javascriptClass = "Shipment";
$appPageId = AppPage::SHIPMENT;
$heading = "Parts Inventory";
$description = "Nom nom nom";
$filterTemplate = "partsInventoryFilter.php";
$filterLocation = getFilterShipmentLocation();
$filterStartDate = getFilterDate(Session::SHIPMENT_START_DATE);
$filterEndDate = getFilterDate(Session::SHIPMENT_END_DATE);
$newButtonLabel = "Add parts";
$reportFileName = "shipments.csv";

include ROOT.'/templates/tablePageTemplate.php'
      
?>
