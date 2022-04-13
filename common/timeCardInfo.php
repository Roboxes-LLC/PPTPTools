<?php
require_once 'commentCodes.php';
require_once 'database.php';
require_once 'jobInfo.php';
require_once 'time.php';
require_once 'userInfo.php';

class TimeCardInfo
{
   const UNKNOWN_TIME_CARD_ID = 0;
   
   const MINUTES_PER_HOUR = 60;
   
   const MAX_SHIFT_HOURS = 13;  // hours
   
   const DEFAULT_SHIFT_HOURS = 8;  // hours
      
   const DEFAULT_SHIFT_TIME = (TimeCardInfo::DEFAULT_SHIFT_HOURS * TimeCardInfo::MINUTES_PER_HOUR);  // minutes
   
   const DEFAULT_RUN_HOURS = 8;  // hours   
   
   const DEFAULT_RUN_TIME = (TimeCardInfo::DEFAULT_RUN_HOURS * TimeCardInfo::MINUTES_PER_HOUR);  // minutes
   
   public $timeCardId = TimeCardInfo::UNKNOWN_TIME_CARD_ID;
   public $dateTime;
   public $manufactureDate;
   public $employeeNumber = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
   public $jobId;
   public $materialNumber;
   public $shiftTime;   
   public $setupTime;
   public $runTime;
   public $panCount;
   public $partCount;
   public $scrapCount;
   public $commentCodes;
   public $comments;
   public $runTimeApprovedBy = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
   public $runTimeApprovedDateTime;
   public $setupTimeApprovedBy = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
   public $setupTimeApprovedDateTime;
   
   public function isPlaceholder()
   {
      $isPlaceholder = false;
      
      if ($this->jobId != JobInfo::UNKNOWN_JOB_ID)
      {
         $jobInfo = JobInfo::load($this->jobId);
         
         $isPlaceholder = ($jobInfo && $jobInfo->isPlaceholder());
      }
      
      return ($isPlaceholder);
   }
   
   public function formatShiftTime()
   {
      return($this->getShiftTimeHours() . ":" . sprintf("%02d", $this->getShiftTimeMinutes()));
   }   
   
   public function getShiftTimeHours()
   {
      return ((int)($this->shiftTime / TimeCardInfo::MINUTES_PER_HOUR));
   }
   
   public function getShiftTimeMinutes()
   {
      return ($this->shiftTime % 60);
   }
   
   public function getShiftTimeInHours()
   {
      return (round(($this->shiftTime / TimeCardInfo::MINUTES_PER_HOUR), 2));
   }   
   
   public function formatSetupTime()
   {
      return($this->getSetupTimeHours() . ":" . sprintf("%02d", $this->getSetupTimeMinutes()));
   }
   
   public function getSetupTimeHours()
   {
      return ((int)($this->setupTime / TimeCardInfo::MINUTES_PER_HOUR));
   }
   
   public function getSetupTimeMinutes()
   {
      return ($this->setupTime % TimeCardInfo::MINUTES_PER_HOUR);
   }
   
   public function formatRunTime()
   {
      return($this->getRunTimeHours() . ":" . sprintf("%02d", $this->getRunTimeMinutes()));
   }
   
   public function getRunTimeHours()
   {
      return ((int)($this->runTime / TimeCardInfo::MINUTES_PER_HOUR));
   }
   
   public function getRunTimeMinutes()
   {
      return ($this->runTime % TimeCardInfo::MINUTES_PER_HOUR);
   }
   
   public function getRunTimeInHours()
   {
      return (round(($this->runTime / TimeCardInfo::MINUTES_PER_HOUR), 2));
   }
   
   public function formatTotalTime()
   {
      return($this->getTotalTimeHours() . ":" . sprintf("%02d", $this->getTotalTimeMinutes()));
   }
   
   public function getTotalTime()
   {
      return ($this->runTime + $this->setupTime);
   }
   
   public function getTotalTimeHours()
   {
      return (round($this->getTotalTime() / TimeCardInfo::MINUTES_PER_HOUR));
   }
   
   public function getTotalTimeMinutes()
   {
      return (round($this->getTotalTime() % TimeCardInfo::MINUTES_PER_HOUR));
   }
   
   public function hasCommentCode($code)
   {
      $hasCode = false;
      
      $commentCode = CommentCode::getCommentCode($code);
      
      if ($commentCode)
      {
         $hasCode = (($this->commentCodes & $commentCode->bits) != 0);
      }
      
      return ($hasCode);
   }
   
   public function setCommentCode($code)
   {
      $commentCode = CommentCode::getCommentCode($code);
      
      if ($commentCode)
      {
         $this->commentCodes |= $commentCode->bits;
      }
   }
   
   public function clearCommentCode($code)
   {
      $commentCode = CommentCode::getCommentCode($code);
      
      if ($commentCode)
      {
         $this->commentCodes &= ~($commentCode->bits);
      }
   }
   
   public static function load($timeCardId)
   {
      $timeCardInfo = null;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getTimeCard($timeCardId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $timeCardInfo = new TimeCardInfo();
            
            $timeCardInfo->timeCardId = intval($row['timeCardId']);
            $timeCardInfo->dateTime = Time::fromMySqlDate($row['dateTime'], "Y-m-d H:i:s");
            $timeCardInfo->manufactureDate = Time::fromMySqlDate($row['manufactureDate'], "Y-m-d H:i:s");
            $timeCardInfo->employeeNumber = intval($row['employeeNumber']);
            $timeCardInfo->jobId = $row['jobId'];
            $timeCardInfo->materialNumber = intval($row['materialNumber']);
            $timeCardInfo->shiftTime = $row['shiftTime'];
            $timeCardInfo->setupTime = $row['setupTime'];
            $timeCardInfo->runTime = $row['runTime'];
            $timeCardInfo->panCount = intval($row['panCount']);
            $timeCardInfo->partCount = intval($row['partCount']);
            $timeCardInfo->scrapCount = intval($row['scrapCount']);
            $timeCardInfo->commentCodes = intval($row['commentCodes']);
            $timeCardInfo->comments = $row['comments'];
            $timeCardInfo->runTimeApprovedBy = intval($row['runTimeApprovedBy']);
            $timeCardInfo->runTimeApprovedDateTime = Time::fromMySqlDate($row['runTimeApprovedDateTime'], "Y-m-d H:i:s");            
            $timeCardInfo->setupTimeApprovedBy = intval($row['setupTimeApprovedBy']);
            $timeCardInfo->setupTimeApprovedDateTime = Time::fromMySqlDate($row['setupTimeApprovedDateTime'], "Y-m-d H:i:s");
         }
      }
      
      return ($timeCardInfo);
   }
   
   public static function matchTimeCard(
      $jobId,
      $employeeNumber,
      $manufactureDate)
   {
      $timeCardId = TimeCardInfo::UNKNOWN_TIME_CARD_ID;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->matchTimeCard($jobId, $employeeNumber, $manufactureDate);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $timeCardId = intval($row["timeCardId"]);
         }
      }
      
      return ($timeCardId);
   }
   
   public static function isUniqueTimeCard(
      $jobId,
      $employeeNumber,
      $manufactureDate)
   {
      $isUnique = (TimeCardInfo::matchTimeCard($jobId, $employeeNumber, $manufactureDate) == TimeCardInfo::UNKNOWN_TIME_CARD_ID);

      return ($isUnique);
   }
   
   public static function calculateEfficiency(
      $runTime,            // Actual run time, in hours
      $grossPartsPerHour,  // Expected part count, based on cycle time
      $partCount)          // Actual part count
   {
      $efficiency = 0.0;

      // Calculate the total number of parts that could be potentially created in the run time.
      $potentialParts = ($runTime * $grossPartsPerHour);
         
      if ($potentialParts > 0)
      {
         // Calculate the efficiency.
         $efficiency = ($partCount / $potentialParts);
      }
      
      return ($efficiency);
   }   
   
   public function getEfficiency()
   {
      $efficiency = 0.0;
      
      // Retrieve the associated job.
      $jobInfo = JobInfo::load($this->jobId);
      
      if ($jobInfo)
      {
         $efficiency = 
            TimeCardInfo::calculateEfficiency(
                  $this->getRunTimeInHours(), 
                  $jobInfo->grossPartsPerHour, 
                  $this->partCount);
      }
      
      return ($efficiency);
   }
   
   public function requiresSetupTimeApproval()
   {
      return ($this->setupTime > 0);      
   }
   
   public function requiresRunTimeApproval()
   {
      return ($this->runTime < $this->shiftTime);      
   }
      
   public function requiresApproval()
   {
      return ($this->requiresRunTimeApproval() || $this->requiresSetupTimeApproval());
   }

   public function isRunTimeApproved()
   {
      return (!$this->requiresRunTimeApproval() || ($this->runTimeApprovedBy != UserInfo::UNKNOWN_EMPLOYEE_NUMBER));
   }
   
   public function getApprovedRunTime()  // hours
   {
      $runTime = ($this->isRunTimeApproved() ? $this->getRunTimeInHours() : $this->getShiftTimeInHours());
      
      return ($runTime);
   }
   
   public function isSetupTimeApproved()
   {
      return (!$this->requiresSetupTimeApproval() || ($this->setupTimeApprovedBy != UserInfo::UNKNOWN_EMPLOYEE_NUMBER));
   }
   
   public function isApproved()
   {
      return ($this->isRunTimeApproved() && $this->isSetupTimeApproved());
   }
   
   public function incompleteShiftTime()
   {
      return (!$this->isPlaceholder() && $this->shiftTime == 0);
   }
   
   public function incompleteRunTime()
   {
      return (!$this->isPlaceholder() && ($this->setupTime == 0) && ($this->runTime == 0));
   }
   
   public function incompletePanCount()
   {
      return (!$this->isPlaceholder() && $this->panCount == 0);
   }
   
   public function incompletePartCount()
   {
      return (!$this->isPlaceholder() && ($this->partCount == 0) && ($this->scrapCount == 0));
   }
   
   public function isComplete()
   {
      return (!($this->incompleteShiftTime() || 
                $this->incompleteRunTime() || 
                $this->incompletePanCount() || 
                $this->incompletePartCount()));
   }
}

/*
if (isset($_GET["timeCardId"]))
{
   $timeCardId = $_GET["timeCardId"];
   $timeCardInfo = TimeCardInfo::load($timeCardId);
 
   if ($timeCardInfo)
   {
      $runTime = $timeCardInfo->formatRunTime();
      $setupTime = $timeCardInfo->formatSetupTime();
      $totalTime = $timeCardInfo->formatTotalTime();
      
      echo "timeCardId: " .                $timeCardInfo->timeCardId .              "<br/>";
      echo "dateTime: " .                  $timeCardInfo->dateTime .                "<br/>";
      echo "manufactureDate: " .           $timeCardInfo->manufactureDate .         "<br/>";      
      echo "employeeNumber: " .            $timeCardInfo->employeeNumber .          "<br/>";
      echo "jobId: " .                     $timeCardInfo->jobId .                   "<br/>";
      echo "materialNumber: " .            $timeCardInfo->materialNumber .          "<br/>";
      echo "shiftTime: " .                 $shiftTime .                             "<br/>";
      echo "runTime: " .                   $runTime .                               "<br/>";
      echo "setupTime: " .                 $setupTime .                             "<br/>";
      echo "totalTime: " .                 $totalTime .                             "<br/>";
      echo "partCount: " .                 $timeCardInfo->partCount .               "<br/>";
      echo "scrapCount: " .                $timeCardInfo->scrapCount .              "<br/>";
      echo "commentCodes:" .               dechex($timeCardInfo->commentCodes) .    "<br/>"; 
      echo "comments: " .                  $timeCardInfo->comments .                "<br/>";
      echo "runTimeApprovedBy: " .         $timeCardInfo->runTimeApprovedBy .       "<br/>";
      echo "runTimeApprovedDateTime: " .   $timeCardInfo->runTimeApprovedDateTime . "<br/>";
      echo "setupTimeApprovedBy: " .       $timeCardInfo->setupTimeApprovedBy .     "<br/>";
      echo "setupTimeApprovedDateTime: " . $timeCardInfo->approvedDateTime .        "<br/>";
   }
   else
   {
        echo "No time card found.";
   }
}
*/
?>