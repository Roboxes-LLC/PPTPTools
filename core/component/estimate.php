<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/chargeCode.php';
require_once ROOT.'/core/common/quoteDefs.php';
require_once ROOT.'/core/common/pptpdatabase.php';

class Estimate
{
   const UNKNOWN_QUOTE_ID = UNKNOWN_QUOTE_ID;  // global constant
   
   const MAX_ESTIMATES = MAX_ESTIMATES;  // global constant
   
   public $quoteId;
   public $estimateIndex;   
   public $unitPrice;
   public $costPerHour;
   public $additionalCharge;
   public $chargeCode;
   public $totalCost;
   public $leadTime;   
   
   public function __construct()
   {
      $this->quoteId = Estimate::UNKNOWN_QUOTE_ID;
      $this->estimateIndex = 0;
      $this->unitPrice = 0.0;
      $this->costPerHour = 0.0;
      $this->additionalCharge = 0.0;
      $this->chargeCode = ChargeCode::UNKNOWN;
      $this->totalCost = 0.0;
      $this->leadTime = 0;
   }
   
   // **************************************************************************
   // Component interface
   
   public static function load($quoteId, $estimateIndex)
   {
      $estimate = null;
      
      $result = PPTPDatabaseAlt::getInstance()->getEstimate($quoteId, $estimateIndex);
      
      if ($result && ($row = $result[0]))
      {
         $estimate = new Estimate();
         
         $estimate->initialize($row);
      }
      
      return ($estimate);
   }
   
   public static function save($estimate)
   {
      $success = false;

      if ($estimate->isValid())
      {      
         if (!PPTPDatabaseAlt::getInstance()->estimateExists($estimate->quoteId, $estimate->estimateIndex))
         {
            $success = PPTPDatabaseAlt::getInstance()->addEstimate($estimate);
         }
         else
         {
            $success = PPTPDatabaseAlt::getInstance()->updateEstimate($estimate);
         }
      }
      
      return ($success);
   }
   
   public static function delete($quoteId, $estimateIndex)
   {
      return (PPTPDatabaseAlt::getInstance()->deleteEstimate($quoteId, $estimateIndex));
   }
   
   public function initialize($row)
   {
      $this->quoteId = intval($row["quoteId"]);
      $this->estimateIndex = intval($row["estimateIndex"]);      
      $this->unitPrice = doubleval($row["unitPrice"]);
      $this->costPerHour = doubleval($row["costPerHour"]);
      $this->additionalCharge = doubleval($row["additionalCharge"]);
      $this->chargeCode = intval($row["chargeCode"]);
      $this->totalCost = doubleval($row["totalCost"]);
      $this->leadTime = intval($row["leadTime"]);
   }
   
   // **************************************************************************
   
   public function isValid()
   {
      return (($this->quoteId != Estimate::UNKNOWN_QUOTE_ID) &&
              ($this->estimateIndex < Estimate::MAX_ESTIMATES));
   }
}