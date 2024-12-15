<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/core/component/part.php';

abstract class JobStatus
{
   const FIRST = 0;
   const PENDING = JobStatus::FIRST;
   const ACTIVE = 1;
   const COMPLETE = 2;
   const CLOSED = 3;
   const DELETED = 4;
   const LAST = 5;
   const COUNT = JobStatus::LAST - JobStatus::FIRST;
   
   public static $VALUES = array(
      JobStatus::PENDING,
      JobStatus::ACTIVE,
      JobStatus::COMPLETE,
      JobStatus::CLOSED,
      JobStatus::DELETED);
   
   private static $names = array("Pending", "Active", "Complete", "Closed", "Deleted");
   
   public static function getName($status)
   {
      return (JobStatus::$names[$status]);
   }
}

class JobInfo
{
   const UNKNOWN_JOB_ID = 0;
   
   const UNKNOWN_JOB_NUMBER = "";
   
   const PLACEHOLDER_JOB_NUMBER = "M0001";
   
   const UNKNOWN_PART_NUMBER = "";
   
   const MAINTENANCE_JOB_ID = "9999";
   
   const UNKNOWN_WC_NUMBER = 0;
   
   const OUTSIDE_WC_NUMBER = 999;
   
   const OUTSIDE_WC_LABEL = "OUT";
   
   const SORTING_MACHINE_WC_NUMBER = 998;
   
   const SORTING_MACHINE_WC_LABEL = "SORT";
   
   const ROTARY_WC_NUMBER = 997;
   
   const ROTARY_WC_LABEL = "ROTARY";
   
   const SECONDS_PER_MINUTE = 60;
   
   const SECONDS_PER_HOUR = 3600;
   
   const UNKNOWN_SAMPLE_WEIGHT = 0.0;
      
   public $jobId = JobInfo::UNKNOWN_JOB_ID;
   public $jobNumber = JobInfo::UNKNOWN_JOB_NUMBER;
   public $partNumber = JobInfo::UNKNOWN_PART_NUMBER;
   public $creator;
   public $dateTime;
   public $wcNumber;
   public $grossPartsPerHour;
   public $netPartsPerHour;
   public $status = JobStatus::PENDING;
   
   public $part;
   
   public function initialize($row)
   {
      $this->jobId =               intval($row['jobId']);
      $this->jobNumber =           $row['jobNumber'];
      $this->partNumber =          $row['partNumber'];
      $this->creator =             $row['creator'];
      $this->dateTime =            Time::fromMySqlDate($row['dateTime'], "Y-m-d H:i:s");
      $this->wcNumber =            $row['wcNumber'];
      $this->grossPartsPerHour =   intval($row['grossPartsPerHour']);
      $this->netPartsPerHour =     intval($row['netPartsPerHour']);
      $this->status =              $row['status'];
   }
   
   public function isActive()
   {
      return ($this->status = JobStatus::ACTIVE);
   }
   
   public function isPlaceholder()
   {
      return (strpos($this->jobNumber, JobInfo::PLACEHOLDER_JOB_NUMBER) !== false);
   }
   
   public static function load($jobId)
   {
      $jobInfo = null;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getJob($jobId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $jobInfo = new JobInfo();
            $jobInfo->initialize($row);
            
            $jobInfo->part = Part::load($jobInfo->partNumber, Part::USE_PPTP_NUMBER);
         }
      }
      
      return ($jobInfo);
   }
   
   public static function save($jobInfo)
   {
      $success = false;
      
      if ($jobInfo->jobId == JobInfo::UNKNOWN_JOB_ID)
      {
         $success = PPTPDatabase::getInstance()->newJob($jobInfo);
         $jobInfo->jobId = PPTPDatabase::getInstance()->lastInsertId();
      }
      else
      {
         $success = PPTPDatabase::getInstance()->updateJob($jobInfo);
      }
      
      return ($success);
   }
   
   public static function delete($jobId)
   {
      return (PPTPDatabase::getInstance()->deleteJob($jobId));
   }
   
   public static function getJobPrefix($jobNumber)
   {
      $dashpos = strpos($jobNumber, "-");
      
      $prefix = $jobNumber;
      if ($dashpos)
      {
         $prefix = substr($jobNumber, 0, $dashpos);
      }
      
      return ($prefix);
   }
   
   public static function getJobSuffix($jobNumber)
   {
      $dashpos = strpos($jobNumber, "-");
      
      $suffix = "";
      if ($dashpos)
      {
         $suffix = substr($jobNumber, ($dashpos + 1));
      }

      return ($suffix);
   }
   
   public function getCycleTime()
   {
      $cycleTime = 0.0;
      
      if ($this->grossPartsPerHour > 0)
      {
         $cycleTime = round((JobInfo::SECONDS_PER_HOUR / $this->grossPartsPerHour), 2);
      }
      
      return ($cycleTime);
   }
   
   public function getNetPercentage()
   {
      $netPercentage = 0.0;
      
      if ($this->grossPartsPerHour > 0)
      {
         $netPercentage = round((($this->netPartsPerHour / $this->grossPartsPerHour) * 100.0), 2);
      }
      
      return ($netPercentage);
   }
   
   // TODO: Move to JobManager
   public static function getJobNumbers($onlyActive)
   {
      $jobNumbers = array();
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getJobNumbers($onlyActive);
         
         if ($result)
         {
            while ($result && ($row = $result->fetch_assoc()))
            {
               $jobNumbers[] = $row["jobNumber"];
            }
         }
      }
      
      return ($jobNumbers);
   }
   
   // TODO: Move to JobManager
   public static function getJobIdByComponents($jobNumber, $wcNumber)
   {
      $jobId = JobInfo::UNKNOWN_JOB_ID;
      
      $database = PPTPDatabase::getInstance();
      
      $result = $database->getJobByComponents($jobNumber, $wcNumber);

      if ($result && ($row = $result->fetch_assoc()))
      {
         $jobId = intval($row["jobId"]);
      }
      
      return ($jobId);
   }
   
   // TODO: Move to JobManager
   static function getJobNumberOptions($selectedJobNumber, $onlyActive, $allowNull)
   {
      $html = "";
      
      if ($allowNull == true)
      {
         $html = "<option value=\"" . JobInfo::UNKNOWN_JOB_NUMBER . "\"></option>";
      }
      else
      {
         $html = "<option style=\"display:none\">";
      }
      
      $jobNumbers = JobInfo::getJobNumbers($onlyActive);
      
      // Add selected job number, if not already in the array.
      // Note: This handles the case of viewing an entry that references a non-active job.
      if (($selectedJobNumber != JobInfo::UNKNOWN_JOB_NUMBER) &&
          (!in_array($selectedJobNumber, $jobNumbers)))
      {
         $jobNumbers[] = $selectedJobNumber;
         sort($jobNumbers);
      }
      
      foreach ($jobNumbers as $jobNumber)
      {
         $selected = ($jobNumber == $selectedJobNumber) ? "selected" : "";
         
         $html .= "<option value=\"$jobNumber\" $selected>$jobNumber</option>";
      }
      
      return ($html);
   }
   
   public static function getWcLabel($wcNumber)
   {
      $label = "";
      
      switch ($wcNumber)
      {
         case JobInfo::UNKNOWN_WC_NUMBER:
         {
            $label = "";
            break;   
         }
         
         case JobInfo::OUTSIDE_WC_NUMBER:
         {
            $label = JobInfo::OUTSIDE_WC_LABEL;
            break;
         }
         
         case JobInfo::SORTING_MACHINE_WC_NUMBER:
         {
            $label = JobInfo::SORTING_MACHINE_WC_LABEL;
            break;
         }
         
         case JobInfo::ROTARY_WC_NUMBER:
         {
            $label = JobInfo::ROTARY_WC_LABEL;
            break;
         }
         
         default:
         {
            $label = $wcNumber;
         }
      }
      
      return ($label);
   }
   
   public static function getWcNumberOptions($jobNumber, $selectedWcNumber)
   {
      $html = "<option style=\"display:none\">";
      
      $workCenters = null;
      if ($jobNumber != JobInfo::UNKNOWN_JOB_NUMBER)
      {
         $workCenters = PPTPDatabase::getInstance()->getWorkCentersForJob($jobNumber);
      }
      else
      {
         $workCenters = PPTPDatabase::getInstance()->getWorkCenters();
      }
      
      foreach ($workCenters as $workCenter)
      {
         $wcNumber = intval($workCenter["wcNumber"]);
         $label = JobInfo::getWcLabel($wcNumber);
         $selected = ($wcNumber == $selectedWcNumber) ? "selected" : "";
         
         $html .= "<option value=\"$wcNumber\" $selected>$label</option>";
      }
      
      return ($html);
   }
   
   public static function getCustomerPrint($jobNumber)
   {
      $customerPrint = null;
      
      $part = Part::load(JobInfo::getJobPrefix($jobNumber), Part::UNKNOWN_PPTP_NUMBER);
      
      if ($part)
      {
         $customerPrint = $part->customerPrint;
      }
      
      return ($customerPrint);
   }
}

/*
if (isset($_GET["$jobId"]))
{
   $jobId = $_GET["jobId"];
   $jobInfo = JobInfo::load($jobId);
   
   if ($jobInfo)
   {
      echo "jobId: " .               $jobInfo->jobId .               "<br/>";
      echo "jobNumber: " .           $jobInfo->jobNumber .           "<br/>";
      echo "creator: " .             $jobInfo->creator .             "<br/>";
      echo "dateTime: " .            $jobInfo->dateTime .            "<br/>";
      echo "partNumber: " .          $jobInfo->partNumber .          "<br/>";
      echo "wcNumber: " .            $jobInfo->wcNumber .            "<br/>";
      echo "grossPartsPerHour: " .   $jobInfo->grossPartsPerHour .   "<br/>";
      echo "netPartsPerHour: " .     $jobInfo->netPartsPerHour .     "<br/>";
      
      echo "status: " . JobStatus::getName($jobInfo->status) . "<br/>";
   }
   else
   {
      echo "No job found.";
   }
}
*/

?>