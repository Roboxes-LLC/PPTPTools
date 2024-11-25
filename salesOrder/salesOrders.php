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
$javascriptFile = "salesOrder.js";
$javascriptClass = "SalesOrder";
$appPageId = AppPage::SALES_ORDER;
$heading = "Sales Orders";
$description = "Nom nom nom";
$filterTemplate = "salesOrderFilter.php";
$filterActiveOrders = filter_var(Session::getVar(Session::SALES_ORDER_ACTIVE_ORDERS), FILTER_VALIDATE_BOOLEAN);
$filterStartDate = getFilterDate(Session::SALES_ORDER_START_DATE);
$filterEndDate = getFilterDate(Session::SALES_ORDER_END_DATE);
$newButtonLabel = "Add order";
$reportFileName = "salesOrders.csv";

include ROOT.'/templates/tablePageTemplate.php'
      
?>
