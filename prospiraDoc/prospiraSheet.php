<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/version.php';
require_once ROOT.'/core/component/prospiraDoc.php';
require_once ROOT.'/core/manager/jobManager.php';

function getParams()
{
   static $params = null;
   
   if (!$params)
   {
      $params = Params::parse();
   }
   
   return ($params);
}

function getProspiraDocId()
{
   $docId = ProspiraDoc::UNKNOWN_DOC_ID;
   
   $params = getParams();
   
   if ($params->keyExists("docId"))
   {
      $docId = $params->getInt("docId");
   }
   
   return ($docId);
}

// ********************************** BEGIN ************************************

Time::init();

session_start();

if (!Authentication::isAuthenticated())
{
   header('Location: /login.php');
   exit;
}

$partNumber = null;
$formattedDate = null;
$quantity = null;
$lotNumber = null;

$prospiraDoc = ProspiraDoc::load(getProspiraDocId());

if ($prospiraDoc)
{
   $jobInfo = JobManager::getMostRecentJob($prospiraDoc->shipment->jobNumber);
   if ($jobInfo)
   {
      $partNumber = JobManager::getCustomerPartNumber($jobInfo->partNumber);
   }
   
   $formattedDate = Time::dateTimeObject($prospiraDoc->shipment->dateTime)->format("n/j/y");
   
   $quantity = $prospiraDoc->shipment->quantity;
   
   $lotNumber = $prospiraDoc->getLotNumber();
}

include ROOT.'/templates/prospiraSheetTemplate.php'
      
?>
