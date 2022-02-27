<?php
require_once 'commentCodes.php';
require_once 'database.php';
require_once 'jobInfo.php';
require_once 'time.php';
require_once 'userInfo.php';

class ShippingCardInfo
{
   const UNKNOWN_SHIPPING_CARD_ID = 0;
   
   const MINUTES_PER_HOUR = 60;
   
   const MAX_SHIFT_HOURS = 13;  // hours
   
   const DEFAULT_SHIFT_HOURS = 10;  // hours
   
   const DEFAULT_SHIFT_TIME = (ShippingCardInfo::DEFAULT_SHIFT_HOURS * ShippingCardInfo::MINUTES_PER_HOUR);  // minutes
   
   public $shippingCardId = ShippingCardInfo::UNKNOWN_SHIPPING_CARD_ID;
   public $dateTime;
   public $manufactureDate;
   public $employeeNumber = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
   public $jobId;
   public $shiftTime;   
   public $shippingTime;
   public $partCount;
   public $scrapCount;
   public $commentCodes;
   public $comments;
   
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
      return ((int)($this->shiftTime / ShippingCardInfo::MINUTES_PER_HOUR));
   }
   
   public function getShiftTimeMinutes()
   {
      return ($this->shiftTime % 60);
   }
   
   public function getShiftTimeInHours()
   {
      return (round(($this->shiftTime / ShippingCardInfo::MINUTES_PER_HOUR), 2));
   }   
   
   public function formatShippingTime()
   {
      return($this->getShippingTimeHours() . ":" . sprintf("%02d", $this->getShippingTimeMinutes()));
   }
   
   public function getShippingTimeHours()
   {
      return ((int)($this->shippingTime / ShippingCardInfo::MINUTES_PER_HOUR));
   }
   
   public function getShippingTimeMinutes()
   {
      return ($this->shippingTime % ShippingCardInfo::MINUTES_PER_HOUR);
   }
   
   public function getShippingTimeInHours()
   {
      return (round(($this->shippingTime / ShippingCardInfo::MINUTES_PER_HOUR), 2));
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
   
   public static function load($shippingCardId)
   {
      $shippingCardInfo = null;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getShippingCard($shippingCardId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $shippingCardInfo = new ShippingCardInfo();
            
            $shippingCardInfo->shippingCardId = intval($row['shippingCardId']);
            $shippingCardInfo->dateTime = Time::fromMySqlDate($row['dateTime'], "Y-m-d H:i:s");
            $shippingCardInfo->manufactureDate = Time::fromMySqlDate($row['manufactureDate'], "Y-m-d H:i:s");
            $shippingCardInfo->employeeNumber = intval($row['employeeNumber']);
            $shippingCardInfo->jobId = $row['jobId'];
            $shippingCardInfo->shiftTime = $row['shiftTime'];
            $shippingCardInfo->shippingTime = $row['shippingTime'];
            $shippingCardInfo->partCount = intval($row['partCount']);
            $shippingCardInfo->scrapCount = intval($row['scrapCount']);
            $shippingCardInfo->commentCodes = intval($row['commentCodes']);
            $shippingCardInfo->comments = $row['comments'];
         }
      }
      
      return ($shippingCardInfo);
   }
   
   public static function matchTimeCard(
      $jobId,
      $employeeNumber,
      $manufactureDate)
   {
      $shippingCardId = ShippingCardInfo::UNKNOWN_SHIPPING_CARD_ID;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->matchTimeCard($jobId, $employeeNumber, $manufactureDate);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $shippingCardId = intval($row["shippingCardId"]);
         }
      }
      
      return ($shippingCardId);
   }
   
   public static function isUniqueShippingCard(
      $jobId,
      $employeeNumber,
      $manufactureDate)
   {
      $isUnique = (TimeCardInfo::matchTimeCard($jobId, $employeeNumber, $manufactureDate) == TimeCardInfo::UNKNOWN_TIME_CARD_ID);
      
      return ($isUnique);
   }
   
   public static function calculateEfficiency(
      $shippingTime,       // Actual run time, in hours
      $grossPartsPerHour,  // Expected part count, based on cycle time
      $partCount)          // Actual part count
   {
      $efficiency = 0.0;
      
      return ($efficiency);
   }   
   
   public function getEfficiency()
   {
      $efficiency = 0.0;
      
      return ($efficiency);
   }
   
   public function incompleteShiftTime()
   {
      return (!$this->isPlaceholder() && $this->shiftTime == 0);
   }
   
   public function incompleteShippingTime()
   {
      return (!$this->isPlaceholder() && ($this->shippingTime == 0));
   }
      
   public function incompletePartCount()
   {
      return (!$this->isPlaceholder() && ($this->partCount == 0));
   }
   
   public function isComplete()
   {
      return (!($this->incompleteShippingTime() || 
                $this->incompletePartCount()));
   }
}

/*
if (isset($_GET["shippingCardId"]))
{
   $shippingCardId = $_GET["shippingCardId"];
   $shippingCardInfo = ShippingCardInfo::load($shippingCardId);
 
   if ($shippingCardInfo)
   {
      $shippingTime = $shippingCardInfo->formatShippingTime();
      
      echo "shippingCardId: " .            $shippingCardInfo->shippingCardId .              "<br/>";
      echo "dateTime: " .                  $shippingCardInfo->dateTime .                "<br/>";
      echo "manufactureDate: " .           $shippingCardInfo->manufactureDate .         "<br/>";      
      echo "employeeNumber: " .            $shippingCardInfo->employeeNumber .          "<br/>";
      echo "jobId: " .                     $shippingCardInfo->jobId .                   "<br/>";
      echo "shiftTime: " .                 $shiftTime .                             "<br/>";
      echo "shippingTime: " .              $shippingTime .                               "<br/>";
      echo "partCount: " .                 $shippingCardInfo->partCount .               "<br/>";
      echo "scrapCount: " .                $shippingCardInfo->scrapCount .              "<br/>";
      echo "commentCodes:" .               dechex($shippingCardInfo->commentCodes) .    "<br/>"; 
      echo "comments: " .                  $shippingCardInfo->comments .                "<br/>";
   }
   else
   {
        echo "No shipping card found.";
   }
}
*/
?>