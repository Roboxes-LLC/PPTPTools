<?php

require_once 'jobInfo.php';
require_once 'materialInfo.php';
require_once 'materialVendor.php';
require_once 'userInfo.php';

class MaterialEntry
{
   const UNKNOWN_ENTRY_ID = 0;

   const UNKNOWN_TAG_NUMBER = 0;

   const UNKNOWN_HEAT_NUMBER = 0;
   
   public $materialEntryId;
   public $materialId;
   public $vendorId;
   public $tagNumber;
   public $heatNumber;
   public $pieces;
   public $enteredUserId;
   public $enteredDateTime;
   public $issuedUserId;
   public $issuedDateTime;
   public $issuedJobId;
   public $acknowledgedUserId;
   public $acknowledgedDateTime;
   
   public function __construct()
   {
      $this->materialEntryId = MaterialEntry::UNKNOWN_ENTRY_ID;
      $this->materialId = MaterialInfo::UNKNOWN_MATERIAL_ID;
      $this->vendorId = MaterialVendor::UNKNOWN_MATERIAL_VENDOR_ID;
      $this->tagNumber = MaterialEntry::UNKNOWN_TAG_NUMBER;
      $this->heatNumber = MaterialEntry::UNKNOWN_HEAT_NUMBER; 
      $this->pieces = 0;
      $this->enteredUserId = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->enteredDateTime = null;
      $this->issuedUserId = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->issuedDateTime = null;
      $this->issuedJobId = JobInfo::UNKNOWN_JOB_ID;
      $this->acknowledgedUserId = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->acknowledgedDateTime = null;
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
      $this->materialId = intval($row['materialId']);
      $this->vendorId = intval($row['vendorId']);
      $this->tagNumber = intval($row['tagNumber']);
      $this->heatNumber = intval($row['heatNumber']);
      $this->pieces = intval($row['pieces']);
      $this->enteredUserId = intval($row['enteredUserId']);
      $this->enteredDateTime = $row['enteredDateTime'] ? Time::fromMySqlDate($row['enteredDateTime'], "Y-m-d H:i:s") : null;
      $this->issuedUserId = intval($row['issuedUserId']);
      $this->issuedDateTime = $row['issuedDateTime'] ? Time::fromMySqlDate($row['issuedDateTime'], "Y-m-d H:i:s") : null;
      $this->issuedJobId = intval($row['issuedJobId']);
      $this->acknowledgedUserId = intval($row['acknowledgedUserId']);
      $this->acknowledgedDateTime = $row['acknowledgedDateTime'] ? Time::fromMySqlDate($row['acknowledgedDateTime'], "Y-m-d H:i:s") : null;
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
      
      if (($this->materialId != MaterialInfo::UNKNOWN_MATERIAL_ID) &&
          ($materialInfo = MaterialInfo::load($this->materialId)))
      {
         $length = ($this->pieces * $materialInfo->length);
      }
      
      return ($length);
   }
}

/*
if (isset($_GET["materialEntryId"]))
{
   $materialEntryId = intval($_GET["materialEntryId"]);
   
   $materialEntry = MaterialEntry::load($materialEntryId);
   $materialEntry->length = $materialEntry->getTotalLength();
 
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

?>