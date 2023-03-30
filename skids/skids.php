<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/activity.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/menu.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/version.php';

// ********************************** BEGIN ************************************

Time::init();

session_start();

if (!Authentication::isAuthenticated())
{
   header('Location: ../login.php');
   exit;
}

$versionQuery = versionQuery();
$javascriptFile = "skid.js";
$javascriptClass = "Skid";
$activity = Activity::SKID;
$heading = "Product Tracking";
$description = "Something something something";
$newButtonLabel = "Add Skid";
$filterTemplate = null;  // TODO

include ROOT.'/templates/tablePageTemplate.php'
      
?>
