<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/chargeCode.php';
require_once ROOT.'/core/common/pptpdatabase.php';
require_once ROOT.'/core/common/quoteStatus.php';
require_once ROOT.'/core/component/contact.php';
require_once ROOT.'/core/component/customer.php';
require_once ROOT.'/core/component/quoteAction.php';

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
   public $attachments;
   
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
      $this->attachments = array();
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
         
         $quote->actions = Quote::getActions($quote->quoteId);
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
      $dt = DateTime::createFromFormat("Y-m-d", Time::now("Y-m-d"));  // TODO: Requested date
      
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
   
   public static function getActions($quoteId)
   {
      $actions = array();
      
      if ($quoteId != Quote::UNKNOWN_QUOTE_ID)
      {
         $result = PPTPDatabaseAlt::getInstance()->getQuoteActions($quoteId);
         
         foreach ($result as $row)
         {
            $quoteAction = new QuoteAction();
            $quoteAction->initialize($row);
            $actions[] = $quoteAction;
         }
      }
      
      return ($actions);
   }
   
   public function findActions($quoteStatus)
   {
      $foundActions = array();
      
      foreach ($this->actions as $poAction)
      {
         if ($poAction->quoteStatus == $quoteStatus)
         {
            $foundActions[] = $poAction;
         }
      }
      
      return ($foundActions);
   }
   
  // **************************************************************************
   
   public function request($dateTime, $userId, $notes)
   {
      return ($this->addQuoteAction(QuoteStatus::REQUESTED, $dateTime, $userId, $notes));
   }
   
   public function isRequested()
   {
      return (count($this->findActions(QuoteStatus::REQUESTED)) > 0);
   }
   
   public function getRequestedAction()
   {
      $quoteActions = $this->findActions(QuoteStatus::REQUESTED);
      
      return ((count($quoteActions) > 0) ? $quoteActions[0] : null);
   }
   
   public function quote($dateTime, $userId, $notes)
   {
      return ($this->addPoAction(QuoteStatus::QUOTED, $dateTime, $userId, $notes));
   }
   
   public function approve($dateTime, $userId, $notes)
   {
      return ($this->addPoAction(QuoteStatus::APPROVED, $dateTime, $userId, $notes));
   }
   
   public function unapprove($dateTime, $userId, $notes)
   {
      return ($this->addPoAction(QuoteStatus::UNPPROVED, $dateTime, $userId, $notes));
   }
   
   public function revise($dateTime, $userId, $notes)
   {
      return ($this->addPoAction(QuoteStatus::REVISED, $dateTime, $userId, $notes));
   }
   
   public function send($dateTime, $userId, $notes)
   {
      return ($this->addPoAction(QuoteStatus::SENT, $dateTime, $userId, $notes));
   }
   
   public function accept($dateTime, $userId, $notes)
   {
      return ($this->addPoAction(QuoteStatus::ACCEPTED, $dateTime, $userId, $notes));
   }
   
   public function reject($dateTime, $userId, $notes)
   {
      return ($this->addPoAction(QuoteStatus::REJECTED, $dateTime, $userId, $notes));
   }
   
   public function requote($dateTime, $userId, $notes)
   {
      return ($this->addPoAction(QuoteStatus::REQUOTED, $dateTime, $userId, $notes));
   }
   
   private function addQuoteAction($quoteStatus, $dateTime, $userId, $notes, $attachment = null)
   {
      $quoteAction = new QuoteAction();
      $quoteAction->quoteId = $this->quoteId;
      $quoteAction->quoteStatus = $quoteStatus;
      $quoteAction->dateTime = $dateTime;
      $quoteAction->userId = $userId;
      $quoteAction->notes = $notes;
      
      $success = QuoteAction::save($quoteAction);
      
      if ($success)
      {
         $this->actions[] = $quoteAction;
         
         $this->recalculateStatus();
         
         $success &= PPTPDatabaseAlt::getInstance()->updateQuoteStatus($this->quoteId, $this->quoteStatus);
      }
      
      return ($success);
   }
   
   private function removeQuoteAction($quoteActionId)
   {
      $success = QuoteAction::delete($quoteActionId);
      
      if ($success)
      {
         $this->actions = Quote::getActions($this->quoteId);
         
         $this->recalculateStatus();
         
         $success &= PPTPDatabaseAlt::getInstance()->updateQuoteStatus($this->quoteId, $this->quoteStatus);
      }
      
      return ($success);
   }
   
   private function recalculateStatus()
   {
      if (count($this->actions) > 0)
      {
         // The status is determined by the last quote action.
         $this->quoteStatus = end($this->actions)->quoteStatus;
      }
      else
      {
         $this->quoteStatus = QuoteStatus::UNKNOWN;
      }
      
      return ($this->quoteStatus);
   }
}