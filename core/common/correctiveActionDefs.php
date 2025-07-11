<?php

abstract class CorrectiveActionStatus
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const OPEN = CorrectiveActionStatus::FIRST;
   const APPROVED = 2;
   const EVALUATED = 3;
   const CLOSED = 4;
   const LAST = 5;
   const COUNT = CorrectiveActionStatus::LAST - CorrectiveActionStatus::FIRST;

   public static $values = array(CorrectiveActionStatus::OPEN, CorrectiveActionStatus::APPROVED, CorrectiveActionStatus::EVALUATED, CorrectiveActionStatus::CLOSED);

   public static function getLabel($status)
   {
      $labels = array("", "Open", "Approved", "Evaluated", "Closed");

      return ($labels[$status]);
   }

   public static function getOptions($selectedStatus)
   {
      $html = "<option style=\"display:none\">";

      foreach (CorrectiveActionStatus::$values as $status)
      {
         $label = CorrectiveActionStatus::getLabel($status);
         $value = $status;
         $selected = ($status == $selectedStatus) ? "selected" : "";

         $html .= "<option value=\"$value\" $selected>$label</option>";
      }

      return ($html);
   }
}

abstract class CorrectiveActionInitiator
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const CUSTOMER = CorrectiveActionInitiator::FIRST;
   const INTERNAL = 2;
   const VENDOR = 3;
   const LAST = 4;
   const COUNT = CorrectiveActionInitiator::LAST - CorrectiveActionInitiator::FIRST;
   
   public static $values = array(CorrectiveActionInitiator::CUSTOMER, CorrectiveActionInitiator::INTERNAL, CorrectiveActionInitiator::VENDOR);
   
   public static function getLabel($initiator)
   {
      $labels = array("", "Customer", "Internal", "Vendor");
      
      return ($labels[$initiator]);
   }
   
   public static function getOptions($selectedInitiator)
   {
      $html = "<option style=\"display:none\">";
      
      foreach (CorrectiveActionInitiator::$values as $initiator)
      {
         $label = SalesOrderStatus::getLabel($initiator);
         $value = $initiator;
         $selected = ($initiator == $selectedInitiator) ? "selected" : "";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
      
      return ($html);
   }
}

abstract class Disposition
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const USE_AS_IS = Disposition::FIRST;
   const RETURN_TO_VENDOR = 2;
   const ISSUE_CREDIT = 3;
   const SEND_REPLACEMENT = 4;
   const SCRAP = 5;
   const SORT_AND_REWORK = 6;
   const STRIP = 7;
   const OTHER = 8;
   const LAST = 9;
   const COUNT = Disposition::LAST - Disposition::FIRST;
   
   public static $values = 
      array(
         Disposition::USE_AS_IS, 
         Disposition::RETURN_TO_VENDOR, 
         Disposition::ISSUE_CREDIT,
         Disposition::SEND_REPLACEMENT,
         Disposition::SCRAP,
         Disposition::SORT_AND_REWORK,
         Disposition::STRIP,
         Disposition::OTHER
      );
   
   public static function getLabel($disposition)
   {
      $labels = 
         array(
            "", 
            "Use As Is", 
            "Return to Vendor",
            "Issue Credit",
            "Send Replacement",
            "Scrap",
            "Sort and Rework",
            "Strip",
            "Other"
         );
      
      return ($labels[$disposition]);
   }
   
   public static function getOptions($selectedDisposition)
   {
      $html = "<option style=\"display:none\">";
      
      foreach (Disposition::$values as $disposition)
      {
         $label = Disposition::getLabel($initiator);
         $value = $disposition;
         $selected = ($disposition == $selectedDisposition) ? "selected" : "";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
      
      return ($html);
   }
}

?>