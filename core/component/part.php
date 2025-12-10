<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/inspection.php';
require_once ROOT.'/core/common/pptpDatabase.php';
require_once ROOT.'/core/component/customer.php';

class Part
{
   const UNKNOWN_PPTP_NUMBER = null;
   
   const UNKNOWN_CUSTOMER_NUMBER = null;
   
   const UNKNOWN_SAMPLE_WEIGHT = 0.0;
   
   // Constants used in calling Part::load().
   const USE_PPTP_NUMBER = false;
   const USE_CUSTOMER_NUMBER = true;
   
   public $pptpNumber;
   public $customerNumber;
   public $customerId;
   public $sampleWeight;
   public $unitPrice;
   public $inspectionTemplateIds;
   public $customerPrint;
   
   public function __construct()
   {
      $this->pptpNumber = Part::UNKNOWN_PPTP_NUMBER;
      $this->customerNumber = Part::UNKNOWN_CUSTOMER_NUMBER;
      $this->customerId = Customer::UNKNOWN_CUSTOMER_ID;
      $this->sampleWeight = Part::UNKNOWN_SAMPLE_WEIGHT;
      $this->unitPrice = 0.0;
      $this->inspectionTemplateIds = [];
      $this->customerPrint = null;
      
      foreach (InspectionType::$VALUES as $inspectionType)
      {
         $this->inspectionTemplateIds[$inspectionType] = Inspection::UNKNOWN_INSPECTION_ID;
      }
   }
   
   public function initialize($row)
   {
      $this->pptpNumber = $row["pptpNumber"];
      $this->customerNumber = $row["customerNumber"];
      $this->customerId = intval($row["customerId"]);
      $this->sampleWeight = floatval($row["sampleWeight"]);
      $this->unitPrice = floatval($row["unitPrice"]);
      $this->inspectionTemplateIds[InspectionType::FIRST_PART] = intval($row['firstPartTemplateId']);
      $this->inspectionTemplateIds[InspectionType::LINE] = intval($row['lineTemplateId']);
      $this->inspectionTemplateIds[InspectionType::QCP] = intval($row['qcpTemplateId']);
      $this->inspectionTemplateIds[InspectionType::IN_PROCESS] = intval($row['inProcessTemplateId']);
      $this->inspectionTemplateIds[InspectionType::FINAL] = intval($row['finalTemplateId']);
      $this->customerPrint = $row['customerPrint'];
   }
   
   // **************************************************************************
   // Component interface
   
   public static function load($partNumber, $useCustomerNumber)
   {
      $part = null;
      
      $result = PPTPDatabaseAlt::getInstance()->getPart($partNumber, $useCustomerNumber);

      if ($result && ($row = $result[0]))
      {
         $part = new Part();
         
         $part->initialize($row);
      }
      
      return ($part);
   }
   
   public static function save($part)
   {
      $success = false;
      
      $partExists = (Part::load($part->pptpNumber, Part::USE_PPTP_NUMBER) != null);
      
      if (!$partExists)
      {
         $success = PPTPDatabaseAlt::getInstance()->addPart($part);
      }
      else
      {
         $success = PPTPDatabaseAlt::getInstance()->updatePart($part);
      }
      
      return ($success);
   }
   
   public static function delete($pptpNumber)
   {
      return (PPTPDatabaseAlt::getInstance()->deletePart($pptpNumber));
   }
}