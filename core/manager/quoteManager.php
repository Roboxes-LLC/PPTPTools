<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/pptpdatabase.php';
require_once ROOT.'/core/component/quote.php';

class QuoteManager
{
   public static function getQuotes($startDate = null, $endDate = null)
   {
      $quotes = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getQuotes($startDate, $endDate);
      
      foreach ($result as $row)
      {
         $quote = new Quote();
         $quote->initialize($row);
         
         $quotes[] = $quote;
      }
      
      return ($quotes);
   }
   
   public static function getQuotesByStatus($quoteStatuses)
   {
      $quotes = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getQuotesByStatus($quoteStatuses);
      
      foreach ($result as $row)
      {
         $quote = new Quote();
         $quote->initialize($row);
         
         $quotes[] = $quote;
      }
      
      return ($quotes);
   }
}