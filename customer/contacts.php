<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/core/component/contact.php';
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
$javascriptFile = "contact.js";
$javascriptClass = "Contact";
$appPageId = AppPage::CONTACT;
$heading = "Contacts";
$description = "Nom nom nom";
$newButtonLabel = "Add contact";
$reportFileName = "contacts.csv";

include ROOT.'/templates/tablePageTemplate.php'
      
?>