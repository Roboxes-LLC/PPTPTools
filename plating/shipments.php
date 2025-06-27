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
$javascriptFile = "plating.js";
$javascriptClass = "Plating";
$appPageId = AppPage::PLATING;
$heading = "Plating";
$description = "Nom nom nom";
$newButtonLabel = null;
$reportFileName = "plating.csv";

include ROOT.'/templates/tablePageTemplate.php'
      
?>
