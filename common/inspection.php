<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/inspectionDefs.php';
require_once ROOT.'/common/inspectionTemplate.php';
require_once ROOT.'/common/timeCardInfo.php';

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
   public $author;
   public $inspector;
   public $comments;
   
   // Properties for job-based inspections (LINE, QCP, IN_PROCESS).
   public $timeCardId;
   public $jobNumber;
   public $wcNumber;
   public $operator;
   public $mfgDate;
   
   // Properties for In Process inspections.
   public $inspectionNumber;  // 1 or 2
   
   // Properties for Final inspections.
   public $quantity;
   public $isPriority;
   
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
      $this->author = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->inspector = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->comments = "";
      $this->timeCardId = TimeCardInfo::UNKNOWN_TIME_CARD_ID;
      $this->jobNumber = JobInfo::UNKNOWN_JOB_NUMBER;
      $this->wcNumber = JobInfo::UNKNOWN_WC_NUMBER;
      $this->operator = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->mfgDate = null;
      $this->inspectionNumber = 0;
      $this->quantity = 0;
      $this->isPriority = false;
      $this->samples = 0;
      $this->naCount = 0;
      $this->passCount = 0;
      $this->warningCount = 0;
      $this->failCount = 0;
      $this->dataFile = null;
      $this->inspectionResults = null;  // 2D array, indexed as [propertyId][sampleIndex]
   }
      
   public function initializeFromOasisReport($oasisReport)
   {
      $this->templateId = InspectionTemplate::OASIS_TEMPLATE_ID;
      $this->dateTime = $oasisReport->getDate();
      $this->author = $oasisReport->getEmployeeNumber();
      $this->inspector = $oasisReport->getEmployeeNumber();
      $this->comments = $oasisReport->getComments();
      $this->timeCardId = TimeCardInfo::UNKNOWN_TIME_CARD_ID;
      $this->jobNumber = $oasisReport->getPartNumber();
      $this->wcNumber = $oasisReport->getMachineNumber();
      $this->operator = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->mfgDate = $this->dateTime;  // Assume same as creation date.
      $this->inspectionNumber = 0;
      $this->quantity = 0;
      $this->isPriority = false;
      
      // Inspection summary.
      $this->samples = $oasisReport->getCount();
      $this->naCount = 0;
      $this->passCount = $oasisReport->getCountByStatus(InspectionStatus::PASS);
      $this->warningCount = $oasisReport->getCountByStatus(InspectionStatus::WARNING);
      $this->failCount = $oasisReport->getCountByStatus(InspectionStatus::FAIL);

      // Source data file for Oasis reports.
      $this->dataFile = $oasisReport->getDataFile();
      
      $this->inspectionResults = null;  // 2D array, indexed as [propertyId][sampleIndex]
   }
   
   public function initializeFromDatabaseRow($row)
   {
      $this->inspectionId = intval($row['inspectionId']);
      $this->templateId = intval($row['templateId']);
      $this->dateTime = $row['dateTime'] ? Time::fromMySqlDate($row['dateTime'], "Y-m-d H:i:s") : null;
      $this->author = intval($row['author']);
      $this->inspector = intval($row['inspector']);
      $this->comments = $row['comments'];
      $this->timeCardId = $row["timeCardId"];
      $this->jobNumber = $row['jobNumber'];
      $this->wcNumber = intval($row['wcNumber']);
      $this->operator = intval($row['operator']);
      $this->mfgDate = $row['mfgDate'] ? Time::fromMySqlDate($row['mfgDate'], "Y-m-d") : null;
      $this->inspectionNumber = intval($row['inspectionNumber']);
      $this->quantity = intval($row['quantity']);
      $this->isPriority = filter_var($row["isPriority"], FILTER_VALIDATE_BOOLEAN);
      
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
   
   public function getJobId()
   {
      $jobId = JobInfo::UNKNOWN_JOB_ID;
      
      // Specified by linked time card.
      if ($this->timeCardId != TimeCardInfo::UNKNOWN_TIME_CARD_ID)
      {
         $timeCardInfo = TimeCardInfo::load($this->timeCardId);
         
         $jobId = $timeCardInfo->jobId;
      }
      // Specified by components.
      else
      {
         $jobId = JobInfo::getJobIdByComponents($this->jobNumber, $this->wcNumber);
      }
      
      return ($jobId);
   }
   
   public function getJobNumber()
   {
      $jobNumber = JobInfo::UNKNOWN_JOB_NUMBER;
      
      $jobId = $this->getJobId();
      
      if ($jobId != JobInfo::UNKNOWN_JOB_ID)
      {
         $jobInfo = JobInfo::load($jobId);
         
         if ($jobInfo)
         {
            $jobNumber = $jobInfo->jobNumber;
         }
      }
      else
      {
         $jobNumber = $this->jobNumber;
      }
      
      return ($jobNumber);
   }
   
   public function getWcNumber()
   {
      $wcNumber = JobInfo::UNKNOWN_WC_NUMBER;
      
      $jobId = $this->getJobId();
      
      if ($jobId != JobInfo::UNKNOWN_JOB_ID)
      {
         $jobInfo = JobInfo::load($jobId);
         
         if ($jobInfo)
         {
            $wcNumber = $jobInfo->wcNumber;
         }
      }
      else
      {
         $wcNumber = $this->wcNumber;
      }
      
      return ($wcNumber);
   }
   
   public function getOperator()
   {
      $employeeNumber = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      
      // Specified by linked time card.
      if ($this->timeCardId != TimeCardInfo::UNKNOWN_TIME_CARD_ID)
      {
         $timeCardInfo = TimeCardInfo::load($this->timeCardId);
         
         if ($timeCardInfo)
         {
            $employeeNumber = $timeCardInfo->employeeNumber;
         }
      }
      // Specified explicitly.
      else
      {
         $employeeNumber = $this->operator;
      }
      
      return ($employeeNumber);
   }
   
   public function getManufactureDate()
   {
      $mfgDate = null;
      
      // Specified by linked time card.
      if ($this->timeCardId != TimeCardInfo::UNKNOWN_TIME_CARD_ID)
      {
         $timeCardInfo = TimeCardInfo::load($this->timeCardId);
         
         if ($timeCardInfo)
         {
            $mfgDate = $timeCardInfo->manufactureDate;
         }
      }
      // Specified explicitly.
      else
      {
         $mfgDate = $this->mfgDate;
      }
      
      return ($mfgDate);
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
   
   public function getMeasurementCount()
   {
      $count = ($this->getCount() - $this->getCountByStatus(InspectionStatus::NON_APPLICABLE));
      
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
      return (!$this->fail() && 
              !$this->warning() && 
              ($this->getCountByStatus(InspectionStatus::PASS) > 0));
   }
   
   public function warning()
   {
      return (!$this->fail() && ($this->getCountByStatus(InspectionStatus::WARNING) > 0));
   }
   
   public function fail()
   {
      return ($this->getCountByStatus(InspectionStatus::FAIL) > 0);
   }
   
   public function incomplete()
   {
      return (!$this->fail() && !$this->warning() && !$this->pass());
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
      else if ($this->pass())
      {
         $inspectionStatus = InspectionStatus::PASS;
      }
      else
      {
         $inspectionStatus = InspectionStatus::INCOMPLETE;
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
   
   function getSampleSize($quantity = null)
   {
      $sampleSize = SamplingPlan::$minSamples;
      
      $inspectionTemplate = InspectionTemplate::load($this->templateId);
      
      if ($inspectionTemplate)
      {
         // For Final inspections, sample size is based on the part quantity.
         if ($inspectionTemplate->inspectionType == InspectionType::FINAL)
         {
            // Optionally, specify a check quantity as a parameter.
            $checkQuantity = ($quantity != null) ? $quantity : $this->quantity;
            
            $sampleSize = SamplingPlan::getSampleCount($checkQuantity);
         }
         // For all others, consult the template.
         else
         {
            $sampleSize = $inspectionTemplate->sampleSize;
         }
      }
      
      return ($sampleSize);
   }
   
   public static function getInspectionsForTimeCard($timeCardId, $inspectionTypes = null)
   {
      $inspections = array();
      
      $result = PPTPDatabase::getInstance()->getInspectionsForTimeCard($timeCardId, $inspectionTypes);
      
      while ($result && ($row = $result->fetch_assoc()))
      {
         $inspection = new Inspection();
         $inspection->initializeFromDatabaseRow($row);
         
         $inspections[] = $inspection;
      }
      
      return ($inspections);
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
      echo "timeCardId: " .   $inspection->timeCardId .   "<br/>";
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