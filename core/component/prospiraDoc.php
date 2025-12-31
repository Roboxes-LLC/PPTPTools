<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/pptpDatabase.php';
require_once ROOT.'/core/component/shipment.php';

class ProspiraDoc
{
   const UNKNOWN_DOC_ID = 0;
   
   const NULL_CLOCK_NUMBER = 0;
   const NULL_SERIAL_NUMBER = 0;
   
   public $docId;
   public $shipmentId;
   public $clockNumber;   
   public $time;
   public $serialNumber;

   // Fields duplicated in Shipment.  Maybe not needed?
   public $pptpPartNumber;
   public $bapmPartNumber;
   public $supplier;
   public $mfgDate;
   public $quantity;
   
   public $shipment;
   
   public function __construct()
   {
      $this->docId = ProspiraDoc::UNKNOWN_DOC_ID;
      $this->shipmentId = Shipment::UNKNOWN_SHIPMENT_ID;
      $this->bapmPartNumber = null;
      $this->clockNumber = ProspiraDoc::NULL_CLOCK_NUMBER;
      $this->time = null;
      $this->serialNumber = ProspiraDoc::NULL_SERIAL_NUMBER;
   }
   
   public function initialize($row)
   {
      $this->docId = intval($row["docId"]);
      $this->shipmentId = intval($row["shipmentId"]);
      $this->bapmPartNumber = $row["bapmPartNumber"];;
      $this->clockNumber = intval($row["clockNumber"]);
      $this->time = $row["time"] ?
                       Time::fromMySqlDate($row["time"]) :
                       null;
      $this->quantity = intval($row["quantity"]);
      $this->serialNumber = $row["serialNumber"];
      
      if ($this->shipmentId != Shipment::UNKNOWN_SHIPMENT_ID)
      {
         $this->shipment = Shipment::load($this->shipmentId);
      }
   }
   
   // **************************************************************************
   // Component interface
   
   public static function load($docId)
   {
      $prospiraDoc = null;
      
      $result = PPTPDatabaseAlt::getInstance()->getProspiraDoc($docId);
      
      if ($result && ($row = $result[0]))
      {
         $prospiraDoc = new ProspiraDoc();
         
         $prospiraDoc->initialize($row);
      }
      
      return ($prospiraDoc);
   }
   
   public static function save($prospiraDoc)
   {
      $success = false;
      
      if ($prospiraDoc->docId == ProspiraDoc::UNKNOWN_DOC_ID)
      {
         $success = PPTPDatabaseAlt::getInstance()->addUpdateProspiraDoc($prospiraDoc);
      }
      else
      {
         $success = PPTPDatabaseAlt::getInstance()->addUpdateProspiraDoc($prospiraDoc);
      }
      
      return ($success);
   }
   
   public static function delete($docId)
   {
      return (PPTPDatabaseAlt::getInstance()->deleteProspiraDoc($docId));
   }
   
   // **************************************************************************
   
   public function getLotNumber()
   {
      return ($this->shipment->getShipmentTicketCode());
   }
}