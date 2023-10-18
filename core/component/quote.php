<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/chargeCode.php';
require_once ROOT.'/core/common/pptpdatabase.php';
require_once ROOT.'/core/common/quoteDefs.php';
require_once ROOT.'/core/common/quoteStatus.php';
require_once ROOT.'/core/component/contact.php';
require_once ROOT.'/core/component/customer.php';
require_once ROOT.'/core/component/estimate.php';
require_once ROOT.'/core/component/quoteAction.php';
require_once ROOT.'/core/component/quoteAttachment.php';

class Quote
{
   const UNKNOWN_QUOTE_ID = UNKNOWN_QUOTE_ID;  // global constant
   
   const MAX_ESTIMATES = MAX_ESTIMATES;  // global constant
   
   // Quote number formatting constants.
   const QUOTE_NUMBER_PREFIX = "PPTP";
   const QUOTE_NUMBER_DATE_FORMAT = "mdy";
   const QUOTE_NUMBER_ID_FORMAT = "%05d";
   
   public $quoteId;
   public $quoteStatus;
   
   // Request info
   public $customerId;
   public $contactId;
   public $customerPartNumber;
   public $pptpPartNumber;
   public $partDescription;
   public $quantity;
   
   // Sending
   public $emailNotes;
   
   // Estimates
   public $estimates;
   
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
      $this->partDescription = null;
      $this->quantity = 0;
      $this->emailNotes = null;
      
      $this->estimates = array();
      for ($estimateIndex = 0; $estimateIndex < Quote::MAX_ESTIMATES; $estimateIndex++)
      {
         $this->estimates[$estimateIndex] = null;
      }
      
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
         
         $quote->estimates = Quote::getEstimates($quote->quoteId);
         
         $quote->actions = Quote::getActions($quote->quoteId);
         
         $quote->attachments = Quote::getAttachments($quote->quoteId);
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
      
      // Save estimates.
      for ($estimateIndex = 0; $estimateIndex < Quote::MAX_ESTIMATES; $estimateIndex++)
      {
         if ($quote->estimates[$estimateIndex])
         {
            $quote->estimates[$estimateIndex]->quoteId = $quote->quoteId;
            $quote->estimates[$estimateIndex]->estimateIndex = $estimateIndex;
            
            $success &= Estimate::save($quote->estimates[$estimateIndex]);
         }
         else if (PPTPDatabaseAlt::getInstance()->estimateExists($quote->quoteId, $estimateIndex))
         {
            $success &= Estimate::delete($quote->quoteId, $estimateIndex);
         }
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
      $this->partDescription = $row["partDescription"];
      $this->quantity = doubleval($row["quantity"]);
      $this->emailNotes = $row["emailNotes"];
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
   
   public static function getEstimates($quoteId)
   {
      $estimates = array();
      
      for ($estimateIndex = 0; $estimateIndex < Quote::MAX_ESTIMATES; $estimateIndex++)
      {
         $estimates[$estimateIndex] = Estimate::load($quoteId, $estimateIndex);
      }
      
      return ($estimates);
   }
   
   public function hasEstimate($estimateIndex)
   {
      return ($this->getEstimate($estimateIndex) != null);
   }
   
   public function getEstimate($estimateIndex)
   {
      return (($estimateIndex < Quote::MAX_ESTIMATES) ? $this->estimates[$estimateIndex] : null);
   }
   
   public function setEstimate($estimate, $estimateIndex)
   {
      if ($estimateIndex < Quote::MAX_ESTIMATES)
      {
         if ($estimate)
         {
            $this->estimates[$estimateIndex] = clone $estimate;
            $this->estimates[$estimateIndex]->quoteId = $this->quoteId;
            $this->estimates[$estimateIndex]->estimateIndex = $estimateIndex;
         }
         else
         {
            $this->estimates[$estimateIndex] = null;
         }
      }
   }
   
   public function getSelectedEstimates()
   {
      $selectedEstimates = [];
      
      foreach ($this->estimates as $estimate)
      {
         if ($estimate->isSelected)
         {
            $selectedEstimates[] = $estimate;
         }
      }
      
      return ($selectedEstimates);
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
   
   public static function getAttachments($quoteId)
   {
      $attachments = array();
      
      if ($quoteId != Quote::UNKNOWN_QUOTE_ID)
      {
         $result = PPTPDatabaseAlt::getInstance()->getQuoteAttachments($quoteId);
         
         foreach ($result as $row)
         {
            $quoteAttachment = new QuoteAttachment();
            $quoteAttachment->initialize($row);
            $attachments[] = $quoteAttachment;
         }
      }
      
      return ($attachments);
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
   
   public function estimate($dateTime, $userId, $notes)
   {
      return ($this->addQuoteAction(QuoteStatus::ESTIMATED, $dateTime, $userId, $notes));
   }
   
   public function isEstimated()
   {
      return (count($this->findActions(QuoteStatus::ESTIMATED)) > 0);
   }
   
   public function approve($dateTime, $userId, $notes)
   {
      return ($this->addQuoteAction(QuoteStatus::APPROVED, $dateTime, $userId, $notes));
   }
   
   public function unapprove($dateTime, $userId, $notes)
   {
      return ($this->addQuoteAction(QuoteStatus::UNAPPROVED, $dateTime, $userId, $notes));
   }
   
   public function revise($dateTime, $userId, $notes)
   {
      return ($this->addQuoteAction(QuoteStatus::REVISED, $dateTime, $userId, $notes));
   }
   
   public function saveEmailDraft($emailNotes)
   {
      $this->emailNotes = $emailNotes;
      
      return (PPTPDatabaseAlt::getInstance()->updateQuoteEmailNotes($this->quoteId, $emailNotes));
   }
   
   public function send($dateTime, $userId, $notes)
   {
      $this->saveEmailDraft($notes); 
      
      return ($this->addQuoteAction(QuoteStatus::SENT, $dateTime, $userId, $notes));
   }
   
   public function isSent()
   {
      return (count($this->findActions(QuoteStatus::SENT)) > 0);
   }
   
   public function getSentAction()
   {
      $quoteActions = $this->findActions(QuoteStatus::SENT);
      
      return ((count($quoteActions) > 0) ? $quoteActions[0] : null);
   }
   
   public function getSentNotes()
   {
      $sentNotes = null;
      
      // Retrieve last SENT action.
      $quoteActions = $this->findActions(QuoteStatus::SENT);
      $quoteAction = end($quoteActions);
      
      if ($quoteAction)
      {
         $sentNotes = $quoteAction->notes;
      }
      
      return ($sentNotes);
   }
   
   public function accept($dateTime, $userId, $notes)
   {
      return ($this->addQuoteAction(QuoteStatus::ACCEPTED, $dateTime, $userId, $notes));
   }
   
   public function reject($dateTime, $userId, $notes)
   {
      return ($this->addQuoteAction(QuoteStatus::REJECTED, $dateTime, $userId, $notes));
   }
   
   public function requote($dateTime, $userId, $notes)
   {
      return ($this->addQuoteAction(QuoteStatus::REQUOTED, $dateTime, $userId, $notes));
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