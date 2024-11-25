<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/database.php';

abstract class JobPeriod
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const HOURLY = JobPeriod::FIRST;  // Run every hour
   const DAILY = 2;                  // Run once a day, at a certain hour
   const WEEKLY = 3;                 // Run on a certain day of the week, at a certain hour
   const MONTHLY = 4;                // Run on a certain day of the month, at a certain hour
   const ANNUALLY = 5;               // Run on a certtain day of the year, at a certain hour
   const LAST = 6;
   const COUNT = JobPeriod::LAST - JobPeriod::FIRST;
   
   public static $values = array(JobPeriod::HOURLY, JobPeriod::DAILY, JobPeriod::WEEKLY, JobPeriod::MONTHLY, JobPeriod::ANNUALLY);
   
   public static function getLabel($jobPeriod)
   {
      $labels = array("", "Hourly", "Daily", "Weekly", "Monthly", "Annually");
      
      return ($labels[$jobPeriod]);
   }
}

abstract class Job
{
   const UNKNOWN_JOB_ID = 0;
   
   // Last day of the week
   const SATURDAY = 7;
   
   public $jobId;
   public $jobClass;
   public $jobName;
   public $description;
   public $isEnabled;
   public $lastRun;
   
   // Scheduling variables
   public $jobPeriod;
   public $hour;   // (1-24)
   public $day;    // (1-7, 1-31)
   public $month;  // (1-12)
   
   public function __construct()
   {
      $this->jobId = Job::UNKNOWN_JOB_ID;
      $this->jobClass = null;
      $this->jobName = null;
      $this->description = null;
      $this->isEnabled = false;
      $this->lastRun = null;
      $this->jobPeriod = JobPeriod::UNKNOWN;
      $this->hour = 0;
      $this->day = 0;
      $this->month = 0;
      $this->config = null;
      $this->status = null;
   }
   
   // **************************************************************************
   // Component interface
   
   public function initialize($row)
   {
      $this->jobId = intval($row['jobId']);
      $this->jobClass =  $row['jobClass'];
      $this->jobName =  $row['jobName'];
      $this->description = $row['description'];
      $this->isEnabled = filter_var($row["isEnabled"], FILTER_VALIDATE_BOOLEAN);
      $this->lastRun =  $row['lastRun'] ? Time::fromMySqlDate($row['lastRun'] , "Y-m-d H:i:s") : null;
      $this->jobPeriod = intval($row['jobPeriod']);
      $this->hour = $row['hour'];
      $this->day = $row['day'];
      $this->month = $row['month'];
      
      // $config and $status should be initialized in derived classes.
   }
   
   public static function load($jobId)
   {
      $job = null;
      
      $result = PPTPDatabase::getInstance()->getCronJob($jobId);
      
      if ($result && ($row = $result->fetch_assoc()))
      {         
         $jobClass = $row["jobClass"];
         
         if (class_exists($jobClass))
         {         
            $job = new $jobClass;
            
            $job->initialize($row);
         }
      }
      
      return ($job);
   }
   
   public static function save($job)
   {
      $success = false;
      
      if ($job->jobId == Job::UNKNOWN_JOB_ID)
      {
         $success = PPTPDatabase::getInstance()->addCronJob($job);
         
         $job->jobId = intval(PPTPDatabase::getInstance()->lastInsertId());
      }
      else
      {
         $success = PPTPDatabase::getInstance()->updateCronJob($job);
      }
      
      return ($success);
   }
   
   public static function delete($jobId)
   {
      return (PPTPDatabase::getInstance()->deleteCronJob($jobId));
   }
   
   // **************************************************************************
     
   public function isTime($currentTime)
   {
      $isTime = false;
      
      $thisPeriod = $this->getThisPeriod($currentTime);
      
      $lastRun = $this->lastRun ?
                    Time::dateTimeObject($this->lastRun) :
                    null;
      
      $isTime = (($lastRun == null) ||        // Never been run before
                 ($lastRun < $thisPeriod));   // Hasn't been run this period
   
      return ($isTime);
   }
   
   public function update()
   {
      $currentTime = Time::now("Y-m-d H:i:s");
      
      if ($this->isEnabled && $this->isTime($currentTime))
      {
         $this->run();
         
         // Store last run.
         $this->lastRun = $currentTime;
         Job::save($this);    
      }
   }
   
   abstract public function run();
      
   // **************************************************************************
   
   public function getThisPeriod($currentTime)
   {
      $thisPeriod = null;
      
      switch ($this->jobPeriod)
      {
         case JobPeriod::HOURLY:
         {
            $thisPeriod = Time::dateTimeObject($currentTime);
            $thisPeriod->setTime($thisPeriod->format("H"), 0, 0);  // Clear minutes, seconds
            break;
         }
         
         case JobPeriod::DAILY:
         {
            // Algorithm:  Set this period to the start of $this->hour.  If the current time is less than that time, decrement by a day.
            $now = Time::dateTimeObject($currentTime);
            $currentHour = intval($now->format("H"));
            $thisPeriod = $now;
            $thisPeriod->setTime($this->hour, 0, 0);
            if ($currentHour < $this->hour)
            {
               $thisPeriod->sub(new DateInterval("P1D"));
            }
            break;
         }

         case JobPeriod::WEEKLY:
         {
            $thisPeriod = Time::dateTimeObject($currentTime);
            $dayOfTheWeek = intval($thisPeriod->format("w"));
            $diff = (($this->day - 1) - $dayOfTheWeek);  // Note: Subtract 1 as PHP day-of-the-week is zero based.
            $dateInterval = new DateInterval("P" . abs($diff) . "D");
            $dateInterval->invert = ($diff < 0);
            $thisPeriod->add($dateInterval);
            $thisPeriod->setTime($this->hour, 0, 0);  // Clear minutes, seconds
            break;
         }
         
         case JobPeriod::MONTHLY:
         {
            $thisPeriod = Time::dateTimeObject($currentTime);
            $thisPeriod->setDate($thisPeriod->format("Y"), $thisPeriod->format("m"), $this->day);
            $thisPeriod->setTime($this->hour, 0, 0);
            break;
         }
         
         case JobPeriod::ANNUALLY:
         {
            $thisPeriod = Time::dateTimeObject($currentTime);
            $thisPeriod->setDate($thisPeriod->format("Y"), $this->month, $this->day);
            $thisPeriod->setTime($this->hour, 0, 0);
            break;
         }
            
         default:
         {
            break;
         }
      }
      
      return ($thisPeriod);
   }
}