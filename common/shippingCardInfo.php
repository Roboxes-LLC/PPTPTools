<?php
require_once 'commentCodes.php';
require_once 'database.php';
require_once 'jobInfo.php';
require_once 'time.php';
require_once 'userInfo.php';

abstract class ShippingActivity
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const SHIPPING_ROOM = ShippingActivity::FIRST;
   const QUALILTY_ROOM = 2;
   const SORTING_MACHINE = 3;
   const REPAIR_REWORK = 4;
   const LAST = 5;
   const COUNT = ShippingActivity::LAST - ShippingActivity::FIRST;
   
   public static $values = array(ShippingActivity::SHIPPING_ROOM, ShippingActivity::QUALILTY_ROOM, ShippingActivity::SORTING_MACHINE, ShippingActivity::REPAIR_REWORK);
   
   public static function getLabel($shippingActivity)
   {
      $labels = array("", "Shipping Room Sort/Pack", "Quality Room Sort", "Sorting Machine Sort", "Repair/Rework");
      
      return ($labels[$shippingActivity]);
   }
   
   public static function getOptions($selectedActivity)
   {
      $html = "<option style=\"display:none\">";
      
      foreach (ShippingActivity::$values as $shippingActivity)
      {
         $selected = ($shippingActivity == $selectedActivity) ? "selected" : "";
         $label = ShippingActivity::getLabel($shippingActivity);
         
         $html .= "<option value=\"$shippingActivity\" $selected>$label</option>";
      }
      
      return ($html);
   }
}

abstract class ScrapType
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const SHIPPING_ROOM = ScrapType::FIRST;
   const QUALILTY_ROOM = 2;
   const SORTING_MACHINE = 3;
   const LAST = 5;
   const COUNT = ScrapType::LAST - ScrapType::FIRST;
   
   public static $values = array(ScrapType::SHIPPING_ROOM, ScrapType::QUALILTY_ROOM, ScrapType::SORTING_MACHINE);
   
   public static function getLabel($scrapType)
   {
      $labels = array("", "Quality Room Scrap", "Shipping Room Scrap", "Sorting Machine Scrap");
      
      return ($labels[$scrapType]);
   }
   
   public static function getOptions($selectedScrapType)
   {
      $html = "<option style=\"display:none\">";
      
      foreach (ScrapType::$values as $scrapType)
      {
         $selected = ($scrapType == $selectedScrapType) ? "selected" : "";
         $label = ScrapType::getLabel($scrapType);
         
         $html .= "<option value=\"$scrapType\" $selected>$label</option>";
      }
      
      return ($html);
   }
}

class ShippingCardInfo
{
   const UNKNOWN_SHIPPING_CARD_ID = 0;
   
   const MINUTES_PER_HOUR = 60;
   
   const MAX_SHIFT_HOURS = 13;  // hours
   
   const DEFAULT_SHIFT_HOURS = 10;  // hours
   
   const DEFAULT_SHIFT_TIME = (ShippingCardInfo::DEFAULT_SHIFT_HOURS * ShippingCardInfo::MINUTES_PER_HOUR);  // minutes
   
   public $shippingCardId;
   public $dateTime;
   public $employeeNumber;
   public $timeCardId;
   public $shiftTime;   
   public $shippingTime;
   public $activity;
   public $partCount;
   public $scrapCount;
   public $scrapType;
   public $comments;
   
   // These attributes were added for manual entry when no time card is available.
   public $jobId = JobInfo::UNKNOWN_JOB_ID;
   public $operator = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
   public $manufactureDate = null;
   
   
   public function __construct()
   {
      $this->shippingCardId = ShippingCardInfo::UNKNOWN_SHIPPING_CARD_ID;
      $this->dateTime = null;
      $this->employeeNumber = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->timeCardId = TimeCardInfo::UNKNOWN_TIME_CARD_ID;
      $this->shiftTime = 0;
      $this->shippingTime = 0;
      $this->activity = ShippingActivity::UNKNOWN;
      $this->partCount = 0;
      $this->scrapCount = 0;
      $this->scrapType = ScrapType::UNKNOWN;
      $this->comments = null;
      
      $this->jobId = JobInfo::UNKNOWN_JOB_ID;
      $this->operator = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->manufactureDate = null;
      
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
            
            $shippingCardInfo->initialize($row);
         }
      }
      
      return ($shippingCardInfo);
   }
   
   public function initialize($row)
   {
      $this->shippingCardId = intval($row['shippingCardId']);
      $this->dateTime = Time::fromMySqlDate($row['dateTime'], "Y-m-d H:i:s");
      $this->employeeNumber = intval($row['employeeNumber']);
      $this->timeCardId = intval($row['timeCardId']);
      $this->shiftTime = $row['shiftTime'];
      $this->shippingTime = $row['shippingTime'];
      $this->activity = intval($row['activity']);
      $this->partCount = intval($row['partCount']);
      $this->scrapCount = intval($row['scrapCount']);
      $this->scrapType = intval($row['scrapType']);
      $this->comments = $row['comments'];
      
      // These attributes were added for manual entry when no time card is available.
      $this->jobId = intval($row['jobId']);
      $this->operator = intval($row['operator']);
      if ($row['manufactureDate'])
      {
         $this->manufactureDate = Time::fromMySqlDate($row['manufactureDate'], "Y-m-d H:i:s");
      }
   }
   
   public function incompleteShiftTime()
   {
      return ($this->shiftTime == 0);
   }
   
   public function incompleteShippingTime()
   {
      return ($this->shippingTime == 0);
   }
      
   public function incompletePartCount()
   {
      return ($this->partCount == 0);
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