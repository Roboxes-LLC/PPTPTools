<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/version.php';

// ********************************** BEGIN ************************************

Time::init();

session_start();

if (!Authentication::isAuthenticated())
{
   header('Location: /login.php');
   exit;
}

$versionQuery = versionQuery();
$javascriptFile = "customer.js";
$javascriptClass = "Customer";
$appPageId = AppPage::CUSTOMER;
$heading = "Customers";
$description = "Nom nom nom";
$newButtonLabel = "Add customer";
$reportFileName = "customers.csv";

include ROOT.'/templates/tablePageTemplate.php'
      
?>
