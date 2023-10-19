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
   public $quantity;
   public $grossPiecesPerHour;
   public $netPiecesPerHour;
   public $unitPrice;
   public $costPerHour;
   public $markup;
   public $additionalCharge;
   public $chargeCode;
   public $leadTime;
   public $isSelected;
   
   public function __construct()
   {
      $this->quoteId = Estimate::UNKNOWN_QUOTE_ID;
      $this->estimateIndex = 0;
      $this->quantity = 0;
      $this->grossPiecesPerHour = 0;
      $this->netPiecesPerHour = 0;
      $this->unitPrice = 0.0;
      $this->costPerHour = 0.0;
      $this->markup = 0.0;
      $this->additionalCharge = 0.0;
      $this->chargeCode = ChargeCode::UNKNOWN;
      $this->leadTime = 0;
      $this->isSelected = false;
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
      $this->quantity = doubleval($row["quantity"]);
      $this->grossPiecesPerHour = intval($row["grossPiecesPerHour"]);
      $this->netPiecesPerHour = intval($row["netPiecesPerHour"]);
      $this->unitPrice = doubleval($row["unitPrice"]);
      $this->costPerHour = doubleval($row["costPerHour"]);
      $this->markup = doubleval($row["markup"]);
      $this->additionalCharge = doubleval($row["additionalCharge"]);
      $this->chargeCode = intval($row["chargeCode"]);
      $this->leadTime = intval($row["leadTime"]);
      $this->isSelected = filter_var($row["isSelected"], FILTER_VALIDATE_BOOLEAN);
   }
   
   // **************************************************************************
   
   public function isValid()
   {
      return (($this->quoteId != Estimate::UNKNOWN_QUOTE_ID) &&
              ($this->estimateIndex < Estimate::MAX_ESTIMATES));
   }
   
   public function isComplete()
   {
      return ($this->isValid() &&
              ($this->quantity > 0) &&
              ($this->unitPrice > 0) &&
              ($this->leadTime != LeadTime::UNKNOWN));
   }
   
   public static function getInputName($property, $estimateIndex)
   {
      return ($property . "_" . $estimateIndex);
   }
   
   public function getTotalCost()
   {
      return (($this->quantity * $this->unitPrice) + $this->additionalCharge);
   }
}