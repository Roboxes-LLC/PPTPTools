<?php

abstract class QuoteStatus
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const REQUESTED = QuoteStatus::FIRST;
   const ESTIMATED = 2;
   const APPROVED = 3;
   const UNAPPROVED = 4;
   const REVISED = 5;
   const SENT = 6;
   const ACCEPTED = 7;
   const REJECTED = 8;
   const REQUOTED = 9;  // TODO: Rename?
   const PASSED = 10;
   const LAST = 11;
   const COUNT = QuoteStatus::LAST - QuoteStatus::FIRST;
   
   public static $values = 
      [
         QuoteStatus::REQUESTED,
         QuoteStatus::ESTIMATED,
         QuoteStatus::APPROVED,
         QuoteStatus::UNAPPROVED,
         QuoteStatus::REVISED,
         QuoteStatus::SENT,
         QuoteStatus::ACCEPTED,
         QuoteStatus::REJECTED,
         QuoteStatus::REQUOTED,
         QuoteStatus::PASSED,
      ];
   
   public static $activeStatuses = 
      [
         QuoteStatus::REQUESTED,
         QuoteStatus::ESTIMATED,
         QuoteStatus::APPROVED,
         QuoteStatus::UNAPPROVED,
         QuoteStatus::REVISED,
         QuoteStatus::SENT,
         QuoteStatus::REQUOTED,
      ];
      
   public static function getLabel($quoteStatus)
   {
      $labels = array("", "Requested", "Estimated", "Approved", "Unapproved", "Revised", "Sent", "Accepted", "Rejected", "Requoted", "Passed");
      
      return ($labels[$quoteStatus]);
   }
   
   public static function getOptions($selectedQuoteStatus)
   {
      $html = "<option style=\"display:none\">";
      
      foreach (QuoteStatus::$values as $quoteStatus)
      {
         $label = QuoteStatus::getLabel($quoteStatus);
         $value = $quoteStatus;
         $selected = ($quoteStatus == $selectedQuoteStatus) ? "selected" : "";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
      
      return ($html);
   }
   
   public static function getJavascript($enumName)
   {
      // Note: Keep synced with enum.
      $varNames = array("UNKNOWN", "REQUESTED", "ESTIMATED", "APPROVED", "UNAPPROVED", "REVISED", "SENT", "ACCEPTED", "REJECTED", "REQUOTED", "PASSED");
      
      $html = "$enumName = {";
      
      $html .= "{$varNames[QuoteStatus::UNKNOWN]}: " . QuoteStatus::UNKNOWN . ", ";
      
      foreach (QuoteStatus::$values as $quoteStatus)
      {
         $html .= "{$varNames[$quoteStatus]}: $quoteStatus";
         $html .= ($quoteStatus < (QuoteStatus::LAST - 1) ? ", " : "");
      }
      
      $html .= "};";
      
      return ($html);
   } 
}