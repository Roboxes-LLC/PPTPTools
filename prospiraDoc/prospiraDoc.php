<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/version.php';
require_once ROOT.'/core/component/prospiraDoc.php';
require_once ROOT.'/core/manager/jobManager.php';
require_once ROOT.'/core/manager/prospiraDocManager.php';
require_once ROOT.'/core/manager/shipmentManager.php';

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

function getProspiraDoc()
{
   static $prospiraDoc = null;
   
   if ($prospiraDoc == null)
   {
      $docId = getProspiraDocId();
      
      if ($docId != ProspiraDoc::UNKNOWN_DOC_ID)
      {
         $prospiraDoc =  ProspiraDoc::load($docId);
      }
      else
      {
         $prospiraDoc = new ProspiraDoc();
      }
   }
   
   return ($prospiraDoc);
}

function getJob()
{
   $jobInfo = null;
   
   $prospiraDoc = getProspiraDoc();
   
   if ($prospiraDoc->shipment)
   {
      $jobInfo = JobManager::getMostRecentJob($prospiraDoc->shipment->jobNumber);
   }
   
   return ($jobInfo);
}

function getCustomerName()
{
   $customerName = "";
   
   $jobInfo = getJob();
   
   if ($jobInfo)
   {
      $customer = JobManager::getCustomer($jobInfo->jobId);
      if ($customer)
      {
         $customerName = $customer->customerName;
      }
   }
   
   return ($customerName);
}

function getCustomerPartNumber()
{
   $partNumber = "";
   
   $jobInfo = getJob();
   
   if ($jobInfo)
   {
      $partNumber = JobManager::getCustomerPartNumber($jobInfo->partNumber);
   }
   
   return ($partNumber);
}

function getPPTPPartNumber()
{
   $partNumber = "";
   
   $jobInfo = getJob();
   
   if ($jobInfo)
   {
      $partNumber = $jobInfo->partNumber;
   }
   
   return ($partNumber);
}

function getJobNumber()
{
   $jobNumber = "";
   
   $jobInfo = getJob();
   
   if ($jobInfo)
   {
      $jobNumber = $jobInfo->jobNumber;
   }
   
   return ($jobNumber);
}

function getForm()
{
   $docId = getProspiraDocId();
   $prospiraDoc = getProspiraDoc();
   
   $shipmentTicketCode = ShipmentManager::getShipmentTicketCode($prospiraDoc->shipmentId);
   $customerName = getCustomerName();
   $customerPartNumber = getCustomerPartNumber();
   $pptpPartNumber = getPPTPPartNumber();
   $jobNumber = getJobNumber();
   $formattedMfgDate = Time::toJavascriptDate($prospiraDoc->shipment->dateTime);
   $quantity = $prospiraDoc->shipment->quantity;
   
   $html =
<<< HEREDOC
   <form id="input-form" style="display: block">
      <input id="doc-id-input" type="hidden" name="docId" value="$docId"/>
      <input type="hidden" name="request" value="save_doc"/>
      
      <div class="form-item" style="margin-right:25px">
         <div class="form-label">Shipment</div>
         <input type="text" maxlength="64" style="width:150px;" value="$shipmentTicketCode" disabled />
      </div>

      <div class="form-item" style="margin-right:25px">
         <div class="form-label">Customer</div>
         <input type="text" maxlength="64" style="width:150px;" value="$customerName" disabled />
      </div>

      <div class="form-item" style="margin-right:25px">
         <div class="form-label">Part #</div>
         <input type="text" maxlength="64" style="width:150px;" value="$customerPartNumber" disabled />
      </div>

      <div class="form-item" style="margin-right:25px">
         <div class="form-label">Job #</div>
         <input type="text" maxlength="64" style="width:150px;" value="$jobNumber" disabled />
      </div>

      <div class="form-item" style="margin-right:25px">
         <div class="form-label">Quantity</div>
         <input type="number" style="width:150px;" value="$quantity" disabled />
      </div>

      <div class="form-item" style="margin-right:25px">
         <div class="form-label">Clock #</div>
         <input type="text" name="clockNumber" maxlength="9" style="width:150px;" value="$prospiraDoc->clockNumber"/>
      </div>

      <div class="form-item" style="margin-right:25px">
         <div class="form-label">Lot #</div>
         <input type="text" name="lotNumber" maxlength="12" style="width:150px;" value="{$prospiraDoc->getLotNumber()}" disabled />
      </div>

      <div class="form-item" style="margin-right:25px">
         <div class="form-label">Serial #</div>
         <input type="text" name="serialNumber" maxlength="10" style="width:150px;" value="$prospiraDoc->serialNumber"/>
      </div>
      
   </form>
HEREDOC;
            
   return ($html);
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
$javascriptFile = "prospiraDoc.js";
$javascriptClass = "ProspiraDoc";
$appPageId = AppPage::PROSPIRA_DOC;
$heading = "Prospira Documentation";
$description = "";
$formId = "input-form";
$form = getForm();

include ROOT.'/templates/formPageTemplate.php'
      
?>
