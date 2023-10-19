<?php 

const UNKNOWN_QUOTE_ID = 0;

const MAX_ESTIMATES = 3;

abstract class LeadTime
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const TWO_TO_FOUR_WEEKS = LeadTime::FIRST;
   const FOUR_TO_SIX_WEEKS = 2;
   const SIX_TO_EIGHT_WEEKS = 3;
   const EIGHT_PLUS_WEEKS = 4;
   const LAST = 5;
   const COUNT = LeadTime::LAST - LeadTime::FIRST;
   
   public static $values = array(LeadTime::TWO_TO_FOUR_WEEKS, LeadTime::FOUR_TO_SIX_WEEKS, LeadTime::SIX_TO_EIGHT_WEEKS, LeadTime::EIGHT_PLUS_WEEKS);
   
   public static function getLabel($leadTime)
   {
      $labels = array("", "2-4 weeks", "4-6 weeks", "6-8 weeks", "8+ weeks");
      
      return ($labels[$leadTime]);
   }
   
   public static function getOptions($selectedLeadTime)
   {
      $label = "";
      $value = LeadTime::UNKNOWN;
      $html = "<option value=\"$value\">$label</option>";
      
      foreach (LeadTime::$values as $leadTime)
      {
         $label = LeadTime::getLabel($leadTime);
         $value = $leadTime;
         $selected = ($leadTime == $selectedLeadTime) ? "selected" : "";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
      
      return ($html);
   }
}

?>