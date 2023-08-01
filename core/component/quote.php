<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/chargeCode.php';
require_once ROOT.'/core/common/pptpdatabase.php';
require_once ROOT.'/core/common/quoteStatus.php';
require_once ROOT.'/core/component/contact.php';
require_once ROOT.'/core/component/customer.php';

class Quote
{
   const UNKNOWN_QUOTE_ID = 0;
   
   // Quote number formatting constants.
   const QUOTE_NUMBER_PREFIX = "PPTP";
   const QUOTE_NUMBER_DATE_FORMAT = "mdy";
   const QUOTE_NUMBER_ID_FORMAT = "%05d";
   
   public $quoteId;
   public $quoteStatus;
   public $customerId;
   public $contactId;
   public $customerPartNumber;
   public $pptpPartNumber;
   public $quantity;
   public $unitPrice;
   public $costPerHour;
   public $additionalCharge;
   public $chargeCode;
   public $totalCost;
   public $leadTime;   
   
   public $actions;
   
   public function __construct()
   {
      $this->quoteId = Quote::UNKNOWN_QUOTE_ID;
      $this->quoteStatus = QuoteStatus::UNKNOWN;
      $this->customerId = Customer::UNKNOWN_CUSTOMER_ID;
      $this->contactId = Contact::UNKNOWN_CONTACT_ID;
      $this->customerPartNumber = null;
      $this->pptpPartNumber = null;
      $this->quantity = 0;
      $this->unitPrice = 0.0;
      $this->costPerHour = 0.0;
      $this->additionalCharge = 0.0;
      $this->chargeCode = ChargeCode::UNKNOWN;
      $this->totalCost = 0.0;
      $this->leadTime = 0;
      
      $this->actions = array();
   }
   
   // **************************************************************************
   // Component interface
   
   public static function load($quoteId)
   {
      $quote = null;
      
      $result = PPTPDatabaseAlt::getInstance()->getQuote($quoteId);
      
      if ($result && ($row = $result[0]))
      {
         $quote = new Quote();
         
         $quote->initialize($row);
      }
      
      return ($quote);
   }
   
   public static function save($quote)
   {
      $success = false;
      
      if ($quote->quoteId == Quote::UNKNOWN_QUOTE_ID)
      {
         $success = PPTPDatabaseAlt::getInstance()->addQuote($quote);
         
         $quote->quoteId = intval(PPTPDatabaseAlt::getInstance()->lastInsertId());
      }
      else
      {
         $success = PPTPDatabaseAlt::getInstance()->updateQuote($quote);
      }
      
      return ($success);
   }
   
   public static function delete($quoteId)
   {
      return (PPTPDatabaseAlt::getInstance()->deleteQuote($quoteId));
   }
   
   public function initialize($row)
   {
      $this->quoteStatus = intval($row["quoteStatus"]);
      $this->quoteId = intval($row["quoteId"]);
      $this->customerId = intval($row["customerId"]);
      $this->contactId = intval($row["contactId"]);
      $this->customerPartNumber = $row["customerPartNumber"];
      $this->pptpPartNumber = $row["pptpPartNumber"];
      $this->quantity = doubleval($row["customerId"]);
      $this->unitPrice = doubleval($row["unitPrice"]);
      $this->costPerHour = doubleval($row["costPerHour"]);
      $this->additionalCharge = doubleval($row["additionalCharge"]);
      $this->chargeCode = intval($row["chargeCode"]);
      $this->totalCost = doubleval($row["totalCost"]);
      $this->leadTime = intval($row["leadTime"]);
   }
   
   // **************************************************************************
   
   public function getQuoteNumber()
   {
      $dt = DateTime::createFromFormat("Y-m-d", Time::now("Y-m-d"));  // TODO: Creation date
      
      $quoteNumber = 
         Quote::QUOTE_NUMBER_PREFIX .
         $dt->format(Quote::QUOTE_NUMBER_DATE_FORMAT) .
         "-" .
         sprintf(Quote::QUOTE_NUMBER_ID_FORMAT, $this->quoteId);
      
      return ($quoteNumber);
   }
   
   public static function getLink($quoteId)
   {
      $html = "";
      
      $quote = Quote::load($quoteId);
      if ($quote)
      {
         $label = $quote->getQuoteNumber();
         
         $html = "<a href=\"/quote/quote.php?quoteId=$quoteId\">$label</a>";
      }
      
      return ($html);
   }
}