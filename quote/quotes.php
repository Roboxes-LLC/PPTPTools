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
$javascriptFile = "quote.js";
$javascriptClass = "Quote";
$appPageId = AppPage::QUOTE;
$heading = "Quotes";
$description = "Nom nom nom";
$filterTemplate = "quoteFilter.php";
$filterActiveQuotes = filter_var(Session::getVar(Session::QUOTE_ACTIVE_QUOTES), FILTER_VALIDATE_BOOLEAN);
$filterStartDate = getFilterDate(Session::QUOTE_START_DATE);
$filterEndDate = getFilterDate(Session::QUOTE_END_DATE);
$newButtonLabel = "Add quote";
$reportFileName = "quotes.csv";

include ROOT.'/templates/tablePageTemplate.php'
      
?>
