<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/pptpdatabase.php';
require_once ROOT.'/core/component/quote.php';

class QuoteManager
{
   public static function getQuotes($startDate = null, $endDate = null, $quoteStatuses = array())
   {
      $quotes = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getQuotes($startDate, $endDate, $quoteStatuses);
      
      foreach ($result as $row)
      {
         $quote = new Quote();
         $quote->initialize($row);
         
         $quotes[] = $quote;
      }
      
      return ($quotes);
   }
}