<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/jobInfo.php';
require_once ROOT.'/common/inspection.php';
require_once ROOT.'/common/userInfo.php';

class Shipment
{
   const UNKNOWN_SHIPMENT_ID = 0;
   
   const UNKNOWN_PACKING_LIST_NUMBER = null;
   
   public $shipmentId;
   public $dateTime;
   public $author;
   public $jobId;
   public $finalInspectionId;
   public $quantity;
   public $packingListNumber;
   
   public $jobInfo;
   public $inspection;
  
   public function __construct()
   {
      $this->shipmentId = Shipment::UNKNOWN_SHIPMENT_ID;
      $this->dateTime = null;
      $this->author = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->jobId = JobInfo::UNKNOWN_JOB_ID;
      $this->finalInspectionId = Inspection::UNKNOWN_INSPECTION_ID;
      $this->quantity = 0;
      $this->packingListNumber = Shipment::UNKNOWN_PACKING_LIST_NUMBER;
      
      $this->jobInfo = null;
      $this->inspection = null;
   }
   
   public function initialize($row)
   {
      $this->shipmentId = intval($row["shipmentId"]);
      $this->dateTime = $row["dateTime"] ?
                           Time::fromMySqlDate($row["dateTime"]) :
                           null;
      $this->author = $row["author"];
      $this->jobId = intval($row["jobId"]);
      $this->inspectionId = intval($row["inspectionId"]);
      $this->quantity = intval($row["quantity"]);
      $this->packingListNumber = $row["packingListNumber"];
   }
   
   // **************************************************************************
   // Component interface
   
   public static function load($shipmentId)
   {
      $shipment = null;
      
      $result = PPTPDatabaseAlt::getInstance()->getShipment($shipmentId);
      
      if ($result && ($row = $result[0]))
      {
         $shipment = new Shipment();
         
         $shipment->initialize($row);
         
         $shipment->jobInfo = JobInfo::load($shipment->jobId);
         $shipment->inspection = Inspection::load($shipment->inspectionId, false);
      }
      
      return ($shipment);
   }
   
   public static function save($shipment)
   {
      $success = false;
      
      if ($shipment->shipmentId == Shipment::UNKNOWN_SHIPMENT_ID)
      {
         $success = PPTPDatabaseAlt::getInstance()->addShipment($shipment);
      }
      else
      {
         $success = PPTPDatabaseAlt::getInstance()->updateShipment($shipment);
      }
      
      return ($success);
   }
   
   public static function delete($shipmentId)
   {
      return (PPTPDatabaseAlt::getInstance()->deleteShipment($shipmentId));
   }
}