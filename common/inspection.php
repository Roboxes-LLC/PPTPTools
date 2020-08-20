<?php

require_once 'inspectionDefs.php';
require_once 'inspectionTemplate.php';

class InspectionResult
{
   const UNKNOWN_INSPECTION_ID = 0;
   
   const UNKNOWN_PROPERTY_ID = 0;
   
   const COMMENT_SAMPLE_INDEX = 99;
   
   public $inspectionId;
   public $propertyId;
   public $dateTime;
   public $status;
   public $data;
   
   public function __construct()
   {
      $this->inspectionId = InspectionResult::UNKNOWN_INSPECTION_ID;
      $this->propertyId = InspectionResult::UNKNOWN_PROPERTY_ID;
      $this->sampleIndex = 0;
      $this->dateTime = null;
      $this->status = InspectionStatus::UNKNOWN;
      $this->data = null;
   }
   
   public static function load($row)
   {
      $inspectionResult = null;
      
      if ($row)
      {
         $inspectionResult = new InspectionResult();
         
         $inspectionResult->inspectionId = intval($row['inspectionId']);
         $inspectionResult->propertyId = intval($row['propertyId']);
         $inspectionResult->sampleIndex = intval($row['sampleIndex']);
         $inspectionResult->dateTime = $row['dateTime'] ? Time::fromMySqlDate($row['dateTime'], "Y-m-d H:i:s") : null;
         $inspectionResult->status = intval($row['status']);
         $inspectionResult->data = $row['data'];
      }
      
      return ($inspectionResult);
   }
   
   public function pass()
   {
      return ($this->status == InspectionStatus::PASS);
   }
   
   public function warning()
   {
      return ($this->status == InspectionStatus::WARNING);
   }
   
   public function fail()
   {
      return ($this->status == InspectionStatus::FAIL);
   }
   
   public function nonApplicable()
   {
      return ($this->status == InspectionStatus::NON_APPLICABLE);
   }
   
   public static function getInputName($propertyId, $sampleIndex)
   {
      return ("property" . $propertyId . "_sample" . $sampleIndex);
   }
}

class Inspection
{
   const UNKNOWN_INSPECTION_ID = 0;
   
   public $inspectionId;
   public $dateTime;
   public $templateId;
   public $inspector;
   public $comments;
   
   // Properties for job-based inspections (LINE, QCP, IN_PROCESS).
   public $jobId;
   public $operator;
   
   // Optional properties for GENERIC inspections.
   public $jobNumber;
   public $wcNumber;
   
   // Inspection results summary properties.
   // Note: By storing these directly in the database, we can more quickly build the inspection table.
   public $samples;
   public $naCount;
   public $passCount;
   public $warningCount;
   public $failCount;
   
   // Source data file for Oasis reports.
   public $dataFile;
   
   // The actual inspection results.
   public $inspectionResults;
   
   public function __construct()
   {
      $this->inspectionId = Inspection::UNKNOWN_INSPECTION_ID;
      $this->templateId = InspectionTemplate::UNKNOWN_TEMPLATE_ID;
      $this->inspector = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->comments = "";
      $this->jobId = JobInfo::UNKNOWN_JOB_ID;
      $this->operator = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->jobNumber = JobInfo::UNKNOWN_JOB_NUMBER;
      $this->wcNumber = JobInfo::UNKNOWN_WC_NUMBER;
      $this->samples = 0;
      $this->naCount = 0;
      $this->passCount = 0;
      $this->warningCount = 0;
      $this->failCount = 0;
      $this->dataFile = null;
      $this->inspectionResults = null;  // 2D array, indexed as [propertyId][sampleIndex]
   }
   
   public function initialize($inspectionTemplate)
   {
      if ($inspectionTemplate)
      {
         $this->inspectionResults = array();
         
         foreach ($inspectionTemplate->inspectionProperties as $inspectionProperty)
         {
            $this->inspectionResults[$inspectionProperty->propertyId] = array($inspectionTemplate->sampleSize);
            
            for ($sampleSize = 0; $sampleSize < $inspectionTemplate->sampleSize; $sampleSize++)
            {
               $this->inspectionResults[$inspectionProperty->propertyId][$sampleSize] = null; 
            }
         }
      }
   }
   
   public function initializeFromOasisReport($oasisReport)
   {
      $this->templateId = InspectionTemplate::OASIS_TEMPLATE_ID;
      $this->inspector = $oasisReport->getEmployeeNumber();
      $this->comments = $oasisReport->getComments();
      $this->jobId = JobInfo::UNKNOWN_JOB_ID;
      $this->operator = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->jobNumber = $oasisReport->getPartNumber();
      $this->wcNumber = $oasisReport->getMachineNumber();
      $this->samples = $oasisReport->getPartInspectionCount();
      $this->naCount = 0;
      $this->warningCount = 0;  // TODO
      $this->failCount = $oasisReport->getFailureCount();
      $this->passCount = ($this->samples - $this->failCount);
      $this->dataFile = $oasisReport->getDataFile();
      $this->inspectionResults = null;  // 2D array, indexed as [propertyId][sampleIndex]
   }
   
   public function initializeFromDatabaseRow($row)
   {
      $this->inspectionId = intval($row['inspectionId']);
      $this->templateId = intval($row['templateId']);
      $this->dateTime = Time::fromMySqlDate($row['dateTime'], "Y-m-d H:i:s");
      $this->inspector = intval($row['inspector']);
      $this->comments = $row['comments'];
      $this->jobId = $row['jobId'];
      $this->operator = intval($row['operator']);
      $this->jobNumber = $row['jobNumber'];
      $this->wcNumber = intval($row['wcNumber']);
      
      // Inspection summary.
      $this->samples = intval($row['samples']);
      $this->naCount = intval($row['naCount']);
      $this->passCount = intval($row['passCount']);
      $this->warningCount = intval($row['warningCount']);
      $this->failCount = intval($row['failCount']);
      
      // Source data file for Oasis reports.
      $this->dataFile = $row['dataFile'];
   }
   
   public static function load($inspectionId, $loadInspectionResults)
   {
      $inspection = null;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getInspection($inspectionId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $inspection = new Inspection();
            
            $inspection->initializeFromDatabaseRow($row);
            
            // Optionally load actual inspection results.
            if ($loadInspectionResults)
            {
               $inspection->loadInspectionResults();
               
               $inspection->updateSummary();
            }
         }
      }
      
      return ($inspection);
   }
   
   public function loadInspectionResults()
   {
      $database = PPTPDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getInspectionResults($this->inspectionId);
         
         while ($result && ($row = $result->fetch_assoc()))
         {
            $inspectionResult = InspectionResult::load($row);
            
            if ($inspectionResult)
            {
               if (!isset($this->inspectionResults[$inspectionResult->propertyId]))
               {
                  $this->inspectionResults[$inspectionResult->propertyId] = array();
               }
               
               $this->inspectionResults[$inspectionResult->propertyId][$inspectionResult->sampleIndex] = $inspectionResult;
            }
         }
      }
   }
   
   public function hasSummary()
   {
      return (!(($this->samples == 0) &&
                ($this->naCount == 0) &&
                ($this->passCount == 0) &&
                ($this->warningCount == 0) &&
                ($this->failCount == 0)));
   }
   
   public function updateSummary()
   {
      if ($this->inspectionResults)
      {
         $this->samples = $this->getCount(true);
         $this->naCount = $this->getCountByStatus(InspectionStatus::NON_APPLICABLE, true);
         $this->passCount = $this->getCountByStatus(InspectionStatus::PASS, true);
         $this->warningCount = $this->getCountByStatus(InspectionStatus::WARNING, true);
         $this->failCount = $this->getCountByStatus(InspectionStatus::FAIL, true);
      }
   }
   
   public function getCount($forceCalculation = false)
   {
      $count = 0;
      
      if ($this->hasSummary() && !$forceCalculation)
      {
         $count = $this->samples;
      }
      else if ($this->inspectionResults)
      {
         foreach ($this->inspectionResults as $inspectionRow)
         {
            foreach ($inspectionRow as $inspectionResult)
            {
               if ($inspectionResult->sampleIndex != InspectionResult::COMMENT_SAMPLE_INDEX)
               {
                  $count++;
               }
            }
         }
      }
      
      return ($count);
   }
   
   public function getCountByStatus($inspectionStatus, $forceCalculation = false)
   {
      $count = 0;
      
      if ($this->hasSummary() && !$forceCalculation)
      {
         switch ($inspectionStatus)
         {
            case InspectionStatus::NON_APPLICABLE:
            {
               $count = $this->naCount;
               break;
            }
            
            case InspectionStatus::PASS:
            {
               $count = $this->passCount;
               break;
            }
            
            case InspectionStatus::WARNING:
            {
               $count = $this->warningCount;
               break;
            }
            
            case InspectionStatus::FAIL:
            {
               $count = $this->failCount;
               break;
            }
            
            default:
            {
               break;
            }
         }
      }
      else if ($this->inspectionResults)
      {
         foreach ($this->inspectionResults as $inspectionRow)
         {
            foreach ($inspectionRow as $inspectionResult)
            {
               if (($inspectionResult->sampleIndex != InspectionResult::COMMENT_SAMPLE_INDEX) &&
                   ($inspectionResult->status == $inspectionStatus))
               {
                  $count++;
               }
            }
         }
      }
      
      return ($count);
   }
   
   public function pass()
   {
      return (!$this->fail() && !$this->warning());
   }
   
   public function warning()
   {
      return (!$this->fail() && ($this->getCountByStatus(InspectionStatus::WARNING) > 0));
   }
   
   public function fail()
   {
      return ($this->getCountByStatus(InspectionStatus::FAIL) > 0);
   }
   
   public function getMeasurementCount()
   {
      $count = ($this->getCount() - $this->getCountByStatus(InspectionStatus::NON_APPLICABLE));
      
      return ($count);
   }
   
   public function getPassCount()
   {
      $count = $this->getCountByStatus(InspectionStatus::PASS);
      
      return ($count);
   }
   
   public function getInspectionStatus()
   {
      $inspectionStatus = InspectionStatus::UNKNOWN;

      if ($this->fail())
      {
         $inspectionStatus = InspectionStatus::FAIL;
      }
      else if ($this->warning())
      {
         $inspectionStatus = InspectionStatus::WARNING;
      }
      else
      {
         $inspectionStatus = InspectionStatus::PASS;
      }
      
      return ($inspectionStatus);
   }
   
   public function getSampleDateTime($sampleIndex, $useUpdateTime)
   {
      $dateTimeStr = null;
      $dateTime = null;
      
      if ($this->inspectionResults)
      {
         foreach ($this->inspectionResults as $inspectionRow)
         {
            foreach ($inspectionRow as $inspectionResult)
            {
               if (($inspectionResult->sampleIndex == $sampleIndex) &&
                   !$inspectionResult->nonApplicable() &&
                   ($inspectionResult->dateTime != null))
               {
                  $compareDateTime = Time::dateTimeObject($inspectionResult->dateTime);
                  
                  if ($dateTime == null)
                  {
                     $dateTime = $compareDateTime;
                  }
                  // Select the most recent time.
                  else if ($useUpdateTime && ($compareDateTime > $dateTime))
                  {
                     $dateTime = $compareDateTime;
                  }
                  // Select the initial time.               
                  else if (!$useUpdateTime && ($compareDateTime < $dateTime))
                  {
                     $dateTime = $compareDateTime;
                  }
               }
            }
         }
      }
      
      if ($dateTime != null)
      {
         $dateTimeStr = $dateTime->format("Y-m-d H:i:s");
      }
      
      return ($dateTimeStr);
   }
}

/*
if (isset($_GET["inspectionId"]))
{
   $inspectionId = $_GET["inspectionId"];
   $inspection = Inspection::load($inspectionId, true);
   $inspectionTemplate = InspectionTemplate::load($inspection->templateId);
 
   if ($inspection && $inspectionTemplate)
   {
      echo "inspectionId: " . $inspection->inspectionId . "<br/>";
      echo "templateId: " .   $inspection->templateId .   "<br/>";
      echo "dateTime: " .     $inspection->dateTime .     "<br/>";
      echo "inspector: " .    $inspection->inspector .    "<br/>";
      echo "jobId: " .        $inspection->jobId .        "<br/>";
      echo "operator: " .     $inspection->operator .     "<br/>";
      echo "jobNumber: " .    $inspection->jobNumber .    "<br/>";
      echo "wcNumber: " .     $inspection->wcNumber .     "<br/>";
      
      echo "inspections: " .  count($inspection->inspectionResults) . "<br/>";
 
      foreach ($inspection->inspectionResults as $inspectionRow)
      {
         foreach ($inspectionRow as $inspectionResult)
         {
            echo "[$inspectionResult->propertyId][$inspectionResult->sampleIndex] : " . InspectionStatus::getLabel($inspectionResult->status) . "<br/>";
         }
      }
      
      echo "comments: " .  $inspection->comments .         "<br/>";
            
      $inspection->updateSummary();
      
      echo "samples: " .      $inspection->samples .      "<br/>";
      echo "naCount: " .      $inspection->naCount .      "<br/>";
      echo "passCount: " .    $inspection->passCount .    "<br/>";
      echo "warningCount: " . $inspection->warningCount . "<br/>";
      echo "failCount: " .    $inspection->failCount .    "<br/>";
   }
   else
   {
      echo "No inspection found.";
   }
}
*/

?>