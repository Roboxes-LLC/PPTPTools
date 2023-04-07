<?php

if (!defined('ROOT')) require_once '../root.php';
require_once 'database.php';
require_once 'time.php';
require_once 'time.php';
require_once ROOT.'/core/component/skid.php';

class PartWasherEntry
{
   const UNKNOWN_ENTRY_ID = 0;
   const UNKNOWN_TIME_CARD_ID = 0;
   const UNKNOWN_JOB_ID = 0;
   const UNKNOWN_OPERATOR = 0;
   
   public $partWasherEntryId = PartWasherEntry::UNKNOWN_ENTRY_ID;
   public $dateTime;
   public $employeeNumber;
   public $timeCardId = PartWasherEntry::UNKNOWN_TIME_CARD_ID;
   public $panCount;
   public $partCount;
   public $skidId = Skid::UNKNOWN_SKID_ID;
   
   // These attributes were added for manual entry when no time card is available.
   public $jobId = PartWasherEntry::UNKNOWN_JOB_ID;
   public $operator = PartWasherEntry::UNKNOWN_OPERATOR;
   public $manufactureDate = null;
   
   public function getJobId()
   {
      $jobId = $this->jobId;
      
      if ($this->timeCardId != PartWasherEntry::UNKNOWN_TIME_CARD_ID)
      {
         $timeCardInfo = TimeCardInfo::load($this->timeCardId);
         
         if ($timeCardInfo)
         {
            $jobId= $timeCardInfo->jobId;
         }
      }
      
      return ($jobId);
   }
   
   public function getOperator()
   {
      $operator = $this->operator;
      
      if ($this->timeCardId != PartWasherEntry::UNKNOWN_TIME_CARD_ID)
      {
         $timeCardInfo = TimeCardInfo::load($this->timeCardId);
         
         if ($timeCardInfo)
         {
            $operator= $timeCardInfo->employeeNumber;
         }
      }
      
      return ($operator);
   }
   
   public function getPanCount()
   {
      $panCount = $this->panCount;
      
      if ($this->timeCardId != PartWasherEntry::UNKNOWN_TIME_CARD_ID)
      {
         $timeCardInfo = TimeCardInfo::load($this->timeCardId);
         
         if ($timeCardInfo)
         {
            $panCount = $timeCardInfo->panCount;
         }
      }
      
      return ($panCount);
   }
   
   public function initializeFromDatabaseRow($row)
   {
      $this->partWasherEntryId = intval($row['partWasherEntryId']);
      $this->dateTime = Time::fromMySqlDate($row['dateTime'], "Y-m-d H:i:s");
      $this->employeeNumber = intval($row['employeeNumber']);
      $this->timeCardId = intval($row['timeCardId']);
      $this->panCount = intval($row['panCount']);
      $this->partCount = intval($row['partCount']);
      $this->skidId = intval($row['skidId']);
      
      // These attributes were added for manual entry when no time card is available.
      $this->jobId = intval($row['jobId']);
      $this->operator = intval($row['operator']);
      if ($row['manufactureDate'])
      {
         $this->manufactureDate = Time::fromMySqlDate($row['manufactureDate'], "Y-m-d H:i:s");
      }
   }

   public static function load($partWasherEntryId)
   {
      $partWasherEntry = null;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && ($database->isConnected()))
      {
         $result = $database->getPartWasherEntry($partWasherEntryId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $partWasherEntry = new PartWasherEntry();
            
            $partWasherEntry->initializeFromDatabaseRow($row);
         }
      }
      
      return ($partWasherEntry);
   }
   
   public static function getPartWasherEntryForTimeCard($timeCardId)
   {
      $partWasherEntry = null;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && ($database->isConnected()))
      {
         $result = $database->getPartWasherEntriesByTimeCard($timeCardId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $partWasherEntry = PartWasherEntry::load(intval($row['partWasherEntryId']));
         }
      }
      
      return ($partWasherEntry);
   }
   
   public static function getPanCountForTimeCard($timeCardId)
   {
      $panCount = 0;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && ($database->isConnected()))
      {
         $result = $database->getPartWasherEntriesByTimeCard($timeCardId);
         
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
         $grossParts = Calculations::calculateGrossParts($runTime, $grossPartsPerHour);
         
         $isValid = Calculations::isReasonablePartCount($this->partCount, $grossParts);
      }
      
      //return ($isValid);
      return (true);  // Removed at customer request on 4/9/2021.
   }
}

/*
 if (isset($_GET["partWasherEntryId"]))
 {
    $partWasherEntryId = $_GET["partWasherEntryId"];
    $partWasherEntry = PartWasherEntry::load($partWasherEntryId);
    
    if ($partWasherEntry)
    {
       echo "partWasherEntryId: " . $partWasherEntry->partWasherEntryId . "<br/>";
       echo "dateTime: " .          $partWasherEntry->dateTime .          "<br/>";
       echo "employeeNumber: " .    $partWasherEntry->employeeNumber .    "<br/>";
       echo "timeCardId: " .        $partWasherEntry->timeCardId .        "<br/>";
       echo "panCount: " .          $partWasherEntry->panCount .          "<br/>";
       echo "partCount: " .         $partWasherEntry->partCount .         "<br/>";
       echo "jobId: " .             $partWasherEntry->jobId .             "<br/>";
       echo "operator: " .          $partWasherEntry->operator .          "<br/>";
       echo "manufactureDate: " .   $partWasherEntry->manufactureDate .   "<br/>";
    }
    else
    {
       echo "No part washer entry found.";
    }
 }
 */
?>