<?php

require_once 'jobInfo.php';
require_once 'materialHeatInfo.php';
require_once 'materialInfo.php';
require_once 'userInfo.php';

abstract class MaterialEntryStatus
{
   const FIRST = 0;
   const UNKNOWN = MaterialEntryStatus::FIRST;
   const RECEIVED = 1;
   const ISSUED = 2;
   const ACKNOWLEDGED = 3;
   const LAST = 4;
   const COUNT = MaterialEntryStatus::LAST - MaterialEntryStatus::FIRST;
   
   public static $VALUES = array(MaterialEntryStatus::RECEIVED, MaterialEntryStatus::ISSUED, MaterialEntryStatus::ACKNOWLEDGED);
   
   public static function getLabel($materialEntryStatus)
   {
      $labels = array("---", "Received", "Issued", "Acknowledged");
      
      return ($labels[$materialEntryStatus]);
   }
   
   public static function getOptions($selectedStatus, $includeAll = false)
   {
      $html = "<option style=\"display:none\">";
      
      if ($includeAll)
      {
         $all = MaterialEntryStatus::UNKNOWN;
         $label = "All";
         $selected = ($selectedStatus == $all) ? "selected" : "";
         $html .= "<option value=\"$all\" $selected>$label</option>";
      }
      
      foreach (MaterialEntryStatus::$VALUES as $materialEntryStatus)
      {
         $selected = ($materialEntryStatus == $selectedStatus) ? "selected" : "";
         $label = MaterialEntryStatus::getLabel($materialEntryStatus);
         
         $html .= "<option value=\"$materialEntryStatus\" $selected>$label</option>";
      }
      
      return ($html);
   }
}

class MaterialEntry
{
   const UNKNOWN_ENTRY_ID = 0;

   const UNKNOWN_HEAT_NUMBER = 0;
   
   public $materialEntryId;
   public $vendorHeatNumber;
   public $tagNumber;
   public $pieces;
   public $enteredUserId;
   public $enteredDateTime;
   public $receivedDateTime;
   public $issuedUserId;
   public $issuedDateTime;
   public $issuedJobId;
   public $acknowledgedUserId;
   public $acknowledgedDateTime;
 
   public $materialHeatInfo;
   public $materialInfo;
   
   public function __construct()
   {
      $this->materialEntryId = MaterialEntry::UNKNOWN_ENTRY_ID;
      $this->vendorHeatNumber = null;
      $this->tagNumber = null;
      $this->pieces = 0;
      $this->enteredUserId = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->enteredDateTime = null;
      $this->receivedDateTime = null;
      $this->issuedUserId = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->issuedDateTime = null;
      $this->issuedJobId = JobInfo::UNKNOWN_JOB_ID;
      $this->acknowledgedUserId = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->acknowledgedDateTime = null;
      
      $this->materialHeatInfo = null;
      $this->materialInfo = null;
   }
      
   public static function load($materialEntryId)
   {
      $materialEntry = null;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && ($database->isConnected()))
      {
         $result = $database->getMaterialEntry($materialEntryId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $materialEntry = new MaterialEntry();
            
            $materialEntry->initialize($row);
         }
      }
      
      return ($materialEntry);
   }
   
   public function issueMaterial($jobId, $userId)
   {
      $returnStatus = false;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && ($database->isConnected()))
      {
         $this->issuedJobId = $jobId;
         $this->issuedUserId = $userId;
         $this->issuedDateTime = Time::now("Y-m-d H:i:s");

         $returnStatus = $database->issueMaterial($this);
      }
      
      return ($returnStatus);
   }
   
   public function revokeMaterial()
   {
      $returnStatus = false;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && ($database->isConnected()))
      {
         $this->issuedJobId = JobInfo::UNKNOWN_JOB_ID;
         $this->issuedUserId = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
         $this->issuedDateTime = null;
         $this->acknowledgedUserId = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
         $this->acknowledgedDateTime = null;
         
         $returnStatus = $database->issueMaterial($this);
         $returnStatus &= $database->acknowledgeIssuedMaterial($this);
      }
      
      return ($returnStatus);
   }
   
   public function acknowledge($userId)
   {
      $returnStatus = false;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && ($database->isConnected()))
      {
         $this->acknowledgedUserId = $userId;
         $this->acknowledgedDateTime = Time::now("Y-m-d H:i:s");
         
         $returnStatus = $database->acknowledgeIssuedMaterial($this);
      }
      
      return ($returnStatus);
   }
   
   public function unacknowledge()
   {
      $returnStatus = false;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && ($database->isConnected()))
      {
         $this->acknowledgedUserId = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
         $this->acknowledgedDateTime = null;
         
         $returnStatus = $database->acknowledgeIssuedMaterial($this);
      }
      
      return ($returnStatus);
   }
   
   public function initialize($row)
   {
      $this->materialEntryId = intval($row['materialEntryId']);
      $this->vendorHeatNumber = $row['vendorHeatNumber'];
      $this->tagNumber = $row['tagNumber'];
      $this->pieces = intval($row['pieces']);
      $this->enteredUserId = intval($row['enteredUserId']);
      $this->enteredDateTime = $row['enteredDateTime'] ? Time::fromMySqlDate($row['enteredDateTime'], "Y-m-d H:i:s") : null;
      $this->receivedDateTime = $row['receivedDateTime'] ? Time::fromMySqlDate($row['receivedDateTime'], "Y-m-d H:i:s") : null;
      $this->issuedUserId = intval($row['issuedUserId']);
      $this->issuedDateTime = $row['issuedDateTime'] ? Time::fromMySqlDate($row['issuedDateTime'], "Y-m-d H:i:s") : null;
      $this->issuedJobId = intval($row['issuedJobId']);
      $this->acknowledgedUserId = intval($row['acknowledgedUserId']);
      $this->acknowledgedDateTime = $row['acknowledgedDateTime'] ? Time::fromMySqlDate($row['acknowledgedDateTime'], "Y-m-d H:i:s") : null;
   
      $this->materialHeatInfo = MaterialHeatInfo::load($this->vendorHeatNumber);
      if ($this->materialHeatInfo)
      {
         $this->materialInfo = MaterialInfo::load($this->materialHeatInfo->materialId);
      }
   }
   
   public function isIssued()
   {
      return ($this->issuedJobId != JobInfo::UNKNOWN_JOB_ID);
   }
   
   public function isAcknowledged()
   {
      return ($this->isIssued() && ($this->acknowledgedUserId != UserInfo::UNKNOWN_EMPLOYEE_NUMBER));
      
   }
   
   public function getQuantity()
   {
      $length = 0;
      
      if ($this->materialInfo)
      {
         $length = ($this->pieces * $this->materialInfo->length);
      }
      
      return ($length);
   }
}

/*
if (isset($_GET["materialEntryId"]))
{
   $materialEntryId = intval($_GET["materialEntryId"]);
   
   $materialEntry = MaterialEntry::load($materialEntryId);
   $materialEntry->quantity = $materialEntry->getQuantity();
 
   if ($materialEntry)
   {
      var_dump($materialEntry);
   }
   else
   {
      echo "No material entry found.";
   }
}
else
{
   echo "No material entry id specified.";
}
*/

/*
if (isset($_GET["makeHeats"]))
{
   $database = PPTPDatabase::getInstance();
   
   if ($database && ($database->isConnected()))
   {
      $dbaseResult = $database->getMaterialEntries(0, "11/01/2021", "");
      
      foreach ($dbaseResult as $row)
      {
         $materialEntry = new MaterialEntry();
         $materialEntry->initialize($row);
         
         $vendorId = intval($row["vendorId"]);
         $materialId = intval($row["materialId"]);
         $vendorHeatNumber = $row["vendorHeatNumber"];
         $internalHeatNumber = $row["heatNumber"];
         
         $materialHeatInfo = MaterialHeatInfo::load($vendorHeatNumber);
         if (!$materialHeatInfo)
         {
            $materialHeatInfo = new MaterialHeatInfo();
            $materialHeatInfo->vendorHeatNumber = $vendorHeatNumber;
            $materialHeatInfo->internalHeatNumber = $internalHeatNumber;
            $materialHeatInfo->vendorId = $vendorId;
            $materialHeatInfo->materialId = $materialId;
            
            $database->newMaterialHeat($materialHeatInfo);
         }
      }
   }
}
*/

?>