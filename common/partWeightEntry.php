<?php
require_once 'database.php';
require_once 'time.php';

class PartWeightEntry
{
   const UNKNOWN_ENTRY_ID = 0;
   const UNKNOWN_TIME_CARD_ID = 0;
   const UNKNOWN_JOB_ID = 0;
   const UNKNOWN_OPERATOR = 0;
   const UNKNOWN_PAN_WEIGHT = 0;
   const UNKNOWN_PALLET_WEIGHT = 0;
   
   const STANDARD_PAN_WEIGHT = 7.1;  // lbs
   const STANDARD_PALLET_WEIGHT = 20.0;  // lbs   
   
   public $partWeightEntryId;
   public $dateTime;
   public $employeeNumber;
   public $timeCardId = PartWeightEntry::UNKNOWN_TIME_CARD_ID;
   public $panCount = 0;
   public $weight;
   public $panWeight = PartWeightEntry::STANDARD_PAN_WEIGHT;
   public $palletWeight = PartWeightEntry::STANDARD_PALLET_WEIGHT;   
   
   // These attributes were added for manual entry when no time card is available.
   public $jobId = PartWeightEntry::UNKNOWN_JOB_ID;
   public $operator = PartWeightEntry::UNKNOWN_OPERATOR;
   public $manufactureDate = null;
   
   public function getJobId()
   {
      $jobId = $this->jobId;
      
      if ($this->timeCardId != PartWeightEntry::UNKNOWN_TIME_CARD_ID)
      {
         $timeCardInfo = TimeCardInfo::load($this->timeCardId);
         
         if ($timeCardInfo)
         {
            $jobId = $timeCardInfo->jobId;
         }
      }
      
      return ($jobId);
   }
   
   public function getOperator()
   {
      $operator = $this->operator;
      
      if ($this->timeCardId != PartWeightEntry::UNKNOWN_TIME_CARD_ID)
      {
         $timeCardInfo = TimeCardInfo::load($this->timeCardId);
         
         if ($timeCardInfo)
         {
            $operator = $timeCardInfo->employeeNumber;
         }
      }
      
      return ($operator);
   }
   
   public function calculatePartCount()
   {
      $partCount = 0;
      
      $jobId = $this->getJobId();
      
      $jobInfo = JobInfo::load($jobId);
      
      if ($jobInfo && $jobInfo->part && ($jobInfo->part->sampleWeight > JobInfo::UNKNOWN_SAMPLE_WEIGHT))
      {
         $partCount = 
            ($this->weight - ($this->palletWeight + ($this->panCount * $this->panWeight))) / ($jobInfo->part->sampleWeight);
         
         $partCount = round($partCount, 0);
      }
      
      return ($partCount);
   }

   public function initialize($row)
   {
      $this->partWeightEntryId = intval($row['partWeightEntryId']);
      $this->dateTime = Time::fromMySqlDate($row['dateTime'], "Y-m-d H:i:s");
      $this->employeeNumber = intval($row['employeeNumber']);
      $this->timeCardId = intval($row['timeCardId']);
      $this->panCount = intval($row['panCount']);
      $this->weight = doubleval($row['weight']);
      
      // These attributes were added for manual entry when no time card is available.
      $this->jobId = intval($row['jobId']);
      $this->operator = intval($row['operator']);
      if ($row['manufactureDate'])
      {
         $this->manufactureDate = Time::fromMySqlDate($row['manufactureDate'], "Y-m-d H:i:s");
      }
   }
   
   public static function load($partWeightEntryId)
   {
      $partWeightEntry = null;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && ($database->isConnected()))
      {
         $result = $database->getPartWeightEntry($partWeightEntryId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $partWeightEntry = new PartWeightEntry();
            
            $partWeightEntry->initialize($row);
         }
      }
      
      return ($partWeightEntry);
   }
   
   public static function getPartWeightEntryForTimeCard($timeCardId)
   {
      $partWeightEntry = null;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && ($database->isConnected()))
      {
         $result = $database->getPartWeightEntriesByTimeCard($timeCardId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $partWeightEntry = PartWeightEntry::load(intval($row['partWeightEntryId']));
         }
      }
      
      return ($partWeightEntry);
   }
   
   public static function getPanCountForTimeCard($timeCardId)
   {
      $panCount = 0;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && ($database->isConnected()))
      {
         $result = $database->getPartWeightEntriesByTimeCard($timeCardId);
         
         while ($result && ($row = $result->fetch_assoc()))
         {
            $panCount += intval($row["panCount"]);
         }
      }
      
      return ($panCount);
   }
   
   public function validatePartCount()
   {
      $isValid = true;
      
      $grossPartsPerHour = 0;
      $runTime = 0;  // hours
      
      if ($this->timeCardId != TimeCardInfo::UNKNOWN_TIME_CARD_ID)
      {
         // Perform validation based on run time and the job's gross parts per hour.
         
         $timeCardInfo = TimeCardInfo::load($this->timeCardId);
         if ($timeCardInfo)
         {
            $jobInfo = JobInfo::load($timeCardInfo->jobId);
            if ($jobInfo)
            {
               $runTime = ($timeCardInfo->runTime / TimeCardInfo::MINUTES_PER_HOUR);
               $grossPartsPerHour = $jobInfo->grossPartsPerHour;
            }
         }
      }
      else if ($this->jobId != JobInfo::UNKNOWN_JOB_ID)
      {
         // Perform validation based on a MAX_SHIFT_TIME and the job's gross parts per hour.
         
         $jobInfo = JobInfo::load($this->jobId);
         if ($jobInfo)
         {
            $runTime = TimeCardInfo::MAX_SHIFT_HOURS;
            $grossPartsPerHour = $jobInfo->grossPartsPerHour;
         }
      }
      
      if ($grossPartsPerHour > 0)
      {
         $partCount = $this->calculatePartCount();
         
         $grossParts = Calculations::calculateGrossParts($runTime, $grossPartsPerHour);
         
         $isValid = Calculations::isReasonablePartCount($partCount, $grossParts);
      }
      
      //return ($isValid);
      return (true);  // Removed at customer request on 4/9/2021.
   }
}

/*
if (isset($_GET["id"]))
{
   $partWeightEntryId = $_GET["id"];
   $partWeightEntry = PartWeightEntry::load($partWeightEntryId);
   
   if ($partWeightEntry)
   {
      echo "partWeightEntryId: " . $partWeightEntry->partWeightEntryId . "<br/>";
      echo "dateTime: " .          $partWeightEntry->dateTime .          "<br/>";
      echo "employeeNumber: " .    $partWeightEntry->employeeNumber .    "<br/>";
      echo "timeCardId: " .        $partWeightEntry->timeCardId .        "<br/>";
      echo "panCount: " .          $partWasherEntry->panCount .          "<br/>";
      echo "weight: " .            $partWeightEntry->weight .            "<br/>";
      echo "jobId: " .             $partWasherEntry->jobId .             "<br/>";
      echo "operator: " .          $partWasherEntry->operator .          "<br/>";
      echo "manufactureDate: " .   $partWasherEntry->manufactureDate .   "<br/>";
   }
   else
   {
      echo "No part weight found.";
   }
}
*/
?>