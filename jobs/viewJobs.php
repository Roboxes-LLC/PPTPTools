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
$javascriptFile = "job.js";
$javascriptClass = "Job";
$appPageId = AppPage::JOBS;
$heading = "Jobs";
$description = "Tracking production all starts with the creation of Job.  Your active jobs are the ones available to your operators for creating Time Sheets.";
$filterTemplate = "jobFilter.php";
$newButtonLabel = "Add job";
$reportFileName = "jobs.csv";

include ROOT.'/templates/tablePageTemplate.php'
      
?>

