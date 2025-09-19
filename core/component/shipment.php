<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/jobInfo.php';
require_once ROOT.'/common/inspection.php';
require_once ROOT.'/common/userInfo.php';
require_once ROOT.'/core/common/shipmentDefs.php';

class Shipment
{
   const UNKNOWN_SHIPMENT_ID = 0;
   
   const UNKNOWN_PACKING_LIST_NUMBER = null;
   
   public $shipmentId;
   public $dateTime;
   public $author;
   public $jobNumber;
   public $inspectionId;
   public $quantity;
   public $packingListNumber;
   public $vendorPackingList;
   public $customerPackingList;
   public $location;
   public $vendorShippedDate;
   public $customerShippedDate;
   
   public $inspection;
  
   public function __construct()
   {
      $this->shipmentId = Shipment::UNKNOWN_SHIPMENT_ID;
      $this->dateTime = null;
      $this->author = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->jobNumber = JobInfo::UNKNOWN_JOB_NUMBER;
      $this->inspectionId = Inspection::UNKNOWN_INSPECTION_ID;
      $this->quantity = 0;
      $this->packingListNumber = Shipment::UNKNOWN_PACKING_LIST_NUMBER;
      $this->vendorPackingList = null;
      $this->customerPackingList = null;
      $this->location = ShipmentLocation::UNKNOWN;
      $this->vendorShippedDate = null;
      $this->customerShippedDate = null;
      
      $this->inspection = null;
   }
   
   public function initialize($row)
   {
      $this->shipmentId = intval($row["shipmentId"]);
      $this->dateTime = $row["dateTime"] ?
                           Time::fromMySqlDate($row["dateTime"]) :
                           null;
      $this->author = $row["author"];
      $this->jobNumber = $row["jobNumber"];
      $this->inspectionId = intval($row["inspectionId"]);
      $this->quantity = intval($row["quantity"]);
      $this->packingListNumber = $row["packingListNumber"];
      $this->vendorPackingList = $row["vendorPackingList"];
      $this->customerPackingList = $row["customerPackingList"];
      $this->location = intval($row["location"]);
      $this->vendorShippedDate = $row["vendorShippedDate"];
      $this->customerShippedDate = $row["customerShippedDate"];
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