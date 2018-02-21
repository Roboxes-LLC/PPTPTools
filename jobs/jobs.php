<?php

require_once '../common/jobInfo.php';
require_once '../authentication.php';
require_once '../database.php';
require_once '../header.php';

require 'viewJobs.php';

function getAction()
{
   $action = '';
   
   if (isset($_POST['action']))
   {
      $action = $_POST['action'];
   }
   else if (isset($_GET['action']))
   {
      $action = $_GET['action'];
   }
   
   return ($action);
}

function getView()
{
   $view = '';
   
   if (isset($_POST['view']))
   {
      $view = $_POST['view'];
   }
   else if (isset($_GET['view']))
   {
      $view = $_GET['view'];
   }
   
   return ($view);
}

function processAction($action)
{
   switch ($action)
   {
      case 'update_job_info':
      {
         updateJobInfo();
         break;
      }
      
      case 'cancel_job':
      {
         unset($_SESSION["jobInfo"]);
         break;
      }
      
      case 'new_job':
      {
         $_SESSION["jobInfo"] = new jobInfo();
         $_SESSION["jobInfo"]->creator = Authentication::getAuthenticatedUser().employeeNumber;
         $_SESSION["jobInfo"]->dateTime = Time::now("Y-m-d h:i:s A");
         break;
      }
      
      case 'edit_job':
      {
         if (isset($_POST['jobNumber']))
         {
            $_SESSION["jobInfo"] = JobInfo::load($_POST['jobNumber']);
         }
         break;
      }
      
      case 'save_job':
      {
         updateJobInfo();
         
         updateJob($_SESSION['jobInfo']);
         
         $_SESSION["jobInfo"] = new JobInfo();
         break;
      }
      
      case 'delete_job':
      {
         if (isset($_POST['jobNumber']))
         {
            deleteJob($_POST['jobNumber']);
         }
         break;
      }
      
      default:
      {
         // Unhandled action.
      }
   }
}

function processView($view)
{
   switch ($view)
   {  
      case 'view_jobs':
      default:
      {
         $page = new ViewJobs();
         $page->render();
         break;
      }
   }
}

function updateJobInfo()
{
   if (isset($_POST['jobNumber']))
   {
      $_SESSION["jobInfo"]->panTicketId = $_POST['panTicketId'];
   }
   
   if (isset($_POST['creator']))
   {
      $_SESSION["jobInfo"]->creator = $_POST['creator'];
   }
   
   if (isset($_POST['dateTime']))
   {
      $dateTime = new DateTime($_POST['dateTime']);
      $_SESSION["jobInfo"]->dateTime = $dateTime->format("Y-m-d h:i:s");
   }
   
   if (isset($_POST['partNumber']))
   {
      $_SESSION["jobInfo"]->partNumber= $_POST['partNumber'];
   }
   
   if (isset($_POST['wcNumber']))
   {
      $_SESSION["jobInfo"]->wcNumber= $_POST['wcNumber'];
   }
   
   if (isset($_POST['isActive']))
   {
      $_SESSION["jobInfo"]->isActive= $_POST['isActive'];
   }
}

function deleteJob($jobNumber)
{
   $result = false;
   
   $database = new PPTPDatabase();
   
   $database->connect();
   
   if ($database->isConnected())
   {
      $result = $database->deletePanTicket($jobNumber);
   }
   
   return ($result);
}

function updateJob($jobInfo)
{
   $success = false;
   
   $database = new PPTPDatabase();
   
   $database->connect();
   
   if ($database->isConnected())
   {
      if ($jobInfo->jobNumber != JobInfo::UNKNOWN_JOB_NUMBER)
      {
         $database->updateJob($jobInfo->jobNumber, $jobInfo);
      }
      else
      {
         $database->newJob($jobInfo);
      }
      
      $success = true;
   }
   
   return ($success);
}
?>

<!-- ********************************** BEGIN ********************************************* -->

<?php 
Time::init();

session_start();

if (!Authentication::isAuthenticated())
{
   header('Location: ../pptpTools.php');
   exit;
}

processAction(getAction());
?>

<html>
<head>
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons"/>
<link rel="stylesheet" href="https://code.getmdl.io/1.3.0/material.indigo-blue.min.css"/>
<link rel="stylesheet" type="text/css" href="../common/flex.css"/>
<link rel="stylesheet" type="text/css" href="../common/common.css"/>

<script defer src="https://code.getmdl.io/1.3.0/material.min.js"></script>
<script src="jobs.js"></script>
<script src="../validate.js"></script>
</head>

<body>

<?php Header::render("Job Summary"); ?>

<div class="flex-horizontal" style="height: 700px;">

   <?php processView(getView())?>

</div>

</body>
</html>