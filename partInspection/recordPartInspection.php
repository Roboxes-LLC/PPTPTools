<?php

require_once '../common/database.php';
require_once '../common/partInspectionInfo.php';

function recordPartInspection()
{
   $partInspectionInfo = parsePartInspectionInfo();
   
   $database = new PPTPDatabase();
   
   $database->connect();
   
   if ($database->isConnected())
   {
      if ($database->newPartInspection($partInspectionInfo))
      {
         echo ("Successfully recorded part inspection.");
      }
      else 
      {
         echo ("Failed to record part inspection.");
      }
   }
}

function parsePartInspectionInfo()
{
   $partInspectionInfo = new PartInspectionInfo();
   
   if (isset($_GET['$partInspectionId']))
   {
      $partInspectionInfo = getPartInspectionInfo($_GET['$partInspectionId']);
   }
   else
   {
      if (isset($_GET['dateTime']))
      {
         $partInspectionInfo->dateTime = $_GET['dateTime'];
      }
      
      if (isset($_GET['employeeNumber']))
      {
         $partInspectionInfo->employeeNumber = $_GET['employeeNumber'];
         
         if (!is_numeric($partInspectionInfo->employeeNumber))
         {
            $partInspectionInfo->employeeNumber = 0;
         }
      }
      
      if (isset($_GET['wcNumber']))
      {
         $partInspectionInfo->wcNumber = $_GET['wcNumber'];
         
         if (!is_numeric($partInspectionInfo->wcNumber))
         {
            $partInspectionInfo->wcNumber = 0;
         }
      }
      
      if (isset($_GET['partNumber']))
      {
         $partInspectionInfo->partNumber = $_GET['partNumber'];
      }
      
      if (isset($_GET['partCount']))
      {
         $partInspectionInfo->partCount = $_GET['partCount'];
         
         if (!is_numeric($partInspectionInfo->partCount))
         {
            $partInspectionInfo->partCount = 0;
         }
      }
      
      if (isset($_GET['failures']))
      {
         $partInspectionInfo->failures = $_GET['failures'];
         
         if (!is_numeric($partInspectionInfo->failures))
         {
            $partInspectionInfo->failures= 0;
         }
      }
      
      if (isset($_GET['efficiency']))
      {
         $partInspectionInfo->efficiency = $_GET['efficiency'];
         
         if (!is_numeric($partInspectionInfo->efficiency))
         {
            $partInspectionInfo->efficiency = 0.0;
         }
      }
   }
   
   return ($partInspectionInfo);
}

?>

<?php 
recordPartInspection();
?>