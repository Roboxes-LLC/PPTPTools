<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/quoteStatus.php';
require_once ROOT.'/common/time.php';
require_once ROOT.'/common/userInfo.php';

class QuoteAction
{
   const UNKNOWN_ACTION_ID = 0;
   
   const UNKNOWN_QUOTE_ID = 0;

   public $quoteActionId;
   public $quoteId;
   public $quoteStatus;
   public $dateTime;
   public $userId;
   public $notes;
   
   public function __construct()
   {
      $this->quoteActionId = QuoteAction::UNKNOWN_ACTION_ID;
      $this->quoteId = QuoteAction::UNKNOWN_QUOTE_ID;
      $this->quoteStatus = QuoteStatus::UNKNOWN;
      $this->dateTime = null;
      $this->userId = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->notes = null;
   }
   
   public function initialize($row)
   {
      $this->quoteActionId = intval($row["quoteActionId"]);
      $this->quoteId = intval($row["quoteId"]);
      $this->quoteStatus = intval($row["quoteStatus"]);
      $this->dateTime = $row["dateTime"] ?
                           Time::fromMySqlDate($row["dateTime"]) :
                           null;
      $this->userId = intval($row["userId"]);
      $this->notes = $row["notes"];      
   }
   
   // **************************************************************************
   // Component interface
   
   public static function load($quoteActionId)
   {
      $quoteAction = null;
      
      $result = PPTPDatabaseAlt::getInstance()->getQuoteAction($quoteActionId);
      
      if ($result && ($row = $result[0]))
      {
         $quoteAction = new QuoteAction();
         
         $quoteAction->initialize($row);
      }
      
      return ($quoteAction);
   }
   
   public static function save($quoteAction)
   {
      $success = false;
      
      if ($quoteAction->quoteActionId == QuoteAction::UNKNOWN_ACTION_ID)
      {
         $success = PPTPDatabaseAlt::getInstance()->addQuoteAction($quoteAction);
      }
      else
      {
         $success = PPTPDatabaseAlt::getInstance()->updateQuoteAction($quoteAction);
      }
     
      return ($success);
   }
   
   public static function delete($quoteActionId)
   {
      return (PPTPDatabaseAlt::getInstance()->deleteQuoteAction($quoteActionId));
   }
}