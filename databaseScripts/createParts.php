<?php 

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/jobInfo.php';
require_once ROOT.'/common/params.php';
require_once ROOT.'/core/component/part.php';
require_once ROOT.'/core/manager/jobManager.php';

$params = Params::parse();
$commit = $params->getBool("commit");

function checkEqual($left, $right)
{
   return (($left->pptpNumber == $right->pptpNumber) &&
           ($left->customerNumber == $right->customerNumber) &&
           ($left->customerId == $right->customerId) &&
           ($left->sampleWeight == $right->sampleWeight) &&
           ($left->inspectionTemplateIds == $right->inspectionTemplateIds) &&
           ($left->customerPrint == $right->customerPrint));
}

function reconcile(&$part, $existingPart)
{
   if ($part->pptpNumber != $existingPart->pptpNumber)
   {
      echo "PPTP number inconsistency: $part->pptpNumber vs $existingPart->pptpNumber<br>";
      return (false);
   }
   
   if ($part->customerNumber != $existingPart->customerNumber)
   {
      echo "Customer number inconsistency: $part->customerNumber vs $existingPart->customerNumber<br>";
      return (false);
   }
   
   if ($part->customerId != $existingPart->customerId)
   {
      if ($part->customerId == Customer::UNKNOWN_CUSTOMER_ID)
      {
         $part->customerId = $existingPart->customerId;
      }
      else
      {
         echo "Customer id inconsistency: $part->customerId vs $existingPart->customerId<br>";
         return (false);
      }
   }
   
   if ($part->sampleWeight != $existingPart->sampleWeight)
   {
      if ($part->sampleWeight == 0.0)
      {
         $part->sampleWeight = $existingPart->sampleWeight;
      }
      else if ($existingPart->sampleWeight != 0.0)
      {
         echo "Sample weight inconsistency: $part->sampleWeight vs $existingPart->sampleWeight<br>";
         //return (false);
      }
   }
   
   foreach (InspectionType::$VALUES as $inspectionType)
   {
      if ($part->inspectionTemplateIds[$inspectionType] != $existingPart->inspectionTemplateIds[$inspectionType])
      {
         if ($part->inspectionTemplateIds[$inspectionType] == 0)
         {
            $part->inspectionTemplateIds[$inspectionType] = $existingPart->inspectionTemplateIds[$inspectionType];
         }
         else if ($existingPart->inspectionTemplateIds[$inspectionType] != 0)
         {
            echo "Inspection template inconsistency: {$part->inspectionTemplateIds[$inspectionType]} vs {$existingPart->inspectionTemplateIds[$inspectionType]}<br>";
            return (false);
         }
      }
   }
   
   if ($part->customerPrint != $existingPart->customerPrint)
   {
      if ($part->customerPrint == null)
      {
         $part->customerPrint = $existingPart->customerPrint;
      }
      else if ($existingPart->customerPrint != null)
      {
         echo "Customer print inconsistency: $part->customerPrint vs $existingPart->customerPrint<br>";
         return (false);
      }
   }
   
   return (true);
}

$entriesUpdated = 0;

$result = PPTPDatabase::getInstance()->query("SELECT * from job");

while ($result && ($row = $result->fetch_assoc()))
{
   $jobInfo = new JobInfo();
   $jobInfo->initialize($row);
      
   $part = new Part();
   $part->pptpNumber = $jobInfo->partNumber;
   $part->customerNumber = null;
   $part->customerId = Customer::UNKNOWN_CUSTOMER_ID;
   $part->sampleWeight = $jobInfo->sampleWeight;
   $part->inspectionTemplateIds[InspectionType::FIRST_PART] = $jobInfo->finalTemplateId;
   $part->inspectionTemplateIds[InspectionType::LINE] = $jobInfo->lineTemplateId;
   $part->inspectionTemplateIds[InspectionType::QCP] = $jobInfo->qcpTemplateId;
   $part->inspectionTemplateIds[InspectionType::FINAL] = $jobInfo->finalTemplateId;
   $part->customerPrint = $jobInfo->customerPrint;
   
   $existingPart = Part::load($part->pptpNumber, Part::USE_PPTP_NUMBER);
   
   if (!$existingPart)
   {
      echo "New part: $part->pptpNumber<br>";
      
      if ($commit)
      {
         Part::save($part);  
         $entriesUpdated++;
      }
   }
   else
   {
      $part->customerNumber = $existingPart->customerNumber;
      $part->customerId = $existingPart->customerId;
      
      if (checkEqual($part, $existingPart))
      {
         echo "No updated required from job $jobInfo->jobNumber:<br>";
      }
      else if (reconcile($part, $existingPart))
      {
         echo "Updating part $part->pptpNumber from job $jobInfo->jobNumber<br>";
         
         if ($commit)
         {
            Part::save($part);
         }
      }
      else
      {
         echo "Could not reconcile part properties in job $jobInfo->jobNumber:<br>";
         echo "New: <br>";
         var_dump($part);
         echo "Existing: <br>";
         var_dump($existingPart);
      }
   }
}

echo "Updated $entriesUpdated entries<br>";