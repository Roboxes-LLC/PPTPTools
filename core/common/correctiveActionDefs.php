<?php

abstract class CorrectiveActionStatus
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const OPEN = CorrectiveActionStatus::FIRST;
   const APPROVED = 2;
   const REVIEWED = 3;
   const CLOSED = 4;
   const LAST = 5;
   const COUNT = CorrectiveActionStatus::LAST - CorrectiveActionStatus::FIRST;

   public static $values = array(CorrectiveActionStatus::OPEN, CorrectiveActionStatus::APPROVED, CorrectiveActionStatus::REVIEWED, CorrectiveActionStatus::CLOSED);

   public static function getLabel($status)
   {
      $labels = array("", "Open", "Approved", "Reviewed", "Closed");

      return ($labels[$status]);
   }
   
   public static function getClass($status)
   {
      return (strtolower(CorrectiveActionStatus::getLabel($status)));
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
   
   public static function getJavascript($enumName)
   {
      // Note: Keep synced with enum.
      $varNames = array("UNKNOWN", "OPEN", "APPROVED", "REVIEWED", "CLOSED");
      
      $html = "$enumName = {";
      
      $html .= "{$varNames[CorrectiveActionStatus::UNKNOWN]}: " . CorrectiveActionStatus::UNKNOWN . ", ";
      
      foreach (CorrectiveActionStatus::$values as $status)
      {
         $html .= "{$varNames[$status]}: $status";
         $html .= ($status < (CorrectiveActionStatus::LAST - 1) ? ", " : "");
      }
      
      $html .= "};";
      
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
         $label = CorrectiveActionInitiator::getLabel($initiator);
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
            "Other (Specify Below)"
         );
      
      return ($labels[$disposition]);
   }
   
   public static function getOptions($selectedDispositions)
   {
      $html = "<option style=\"display:none\">";
      
      foreach (Disposition::$values as $disposition)
      {
         $label = Disposition::getLabel($disposition);
         $value = $disposition;
         $selected = ((array_search($disposition, $selectedDispositions) !== false) ? "selected" : "");
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
      
      return ($html);
   }
   
   public static function setDisposition($disposition, &$bitset)
   {
      if (($disposition >= Disposition::FIRST) &&
          ($disposition < Disposition::LAST))
      {
         $bitset |= (1 << ($disposition - 1));
      }
   }
   
   public static function hasDisposition($disposition, $bitset)
   {
      $hasDisposition = false;

      if (($disposition >= Disposition::FIRST) &&
          ($disposition < Disposition::LAST))
      {
         $hasDisposition = (($bitset & (1 << ($disposition - 1))) > 0);
      }
      
      return ($hasDisposition);
   }
   
   public static function getDispositions($bitset)
   {
      $dispositions = [];
      
      foreach (Disposition::$values as $disposition)
      {
         if (Disposition::hasDisposition($disposition, $bitset))
         {
            $dispositions[] = $disposition;
         }
      }
      
      return ($dispositions);
   }
}

abstract class CorrectionType
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const SHORT_TERM = CorrectionType::FIRST;
   const LONG_TERM = 2;
   const LAST = 3;
   const COUNT = CorrectionType::LAST - CorrectionType::FIRST;
   
   public static $values = array(CorrectionType::SHORT_TERM, CorrectionType::LONG_TERM);
   
   public static function getLabel($correctionType)
   {
      $labels = array("", "Short Term", "Long Term");
      
      return ($labels[$correctionType]);
   }
   
   public static function getInputPrefix($correctionType)
   {
      $prefixes = array("", "shortTerm_", "longTerm_");
      
      return ($prefixes[$correctionType]);
   }
   
   public static function getClassPrefix($correctionType)
   {
      $prefixes = array("", "short-term", "long-term");
      
      return ($prefixes[$correctionType]);
   }
}


abstract class CorrectiveActionLocation
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const PPTP = CorrectiveActionLocation::FIRST;
   const PLATER = 2;
   const CUSTOMER = 3;
   const VENDOR = 4;
   const LAST = 5;
   const COUNT = CorrectiveActionLocation::LAST - CorrectiveActionLocation::FIRST;
   
   public static $values = array(CorrectiveActionLocation::PPTP, CorrectiveActionLocation::PLATER, CorrectiveActionLocation::CUSTOMER, CorrectiveActionLocation::VENDOR);
   
   public static $activeLocations = [CorrectiveActionLocation::PPTP, CorrectiveActionLocation::PLATER];
   
   public static function getLabel($location)
   {
      $labels = array("", "Pittsburgh Precision", "Plater", "Customer", "Vendor");
      
      return ($labels[$location]);
   }
   
   public static function getOptions($selectedLocation)
   {
      $html = "<option style=\"display:none\">";
      
      foreach (CorrectiveActionLocation::$values as $location)
      {
         $label = CorrectiveActionLocation::getLabel($location);
         $value = $location;
         $selected = ($location == $selectedLocation) ? "selected" : "";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
      
      return ($html);
   }
   
   public static function getJavascript($enumName)
   {
      // Note: Keep synced with enum.
      $varNames = array("UNKNOWN", "PPTP", "PLATER", "CUSTOMER", "VENDOR");
      
      $html = "$enumName = {";
      
      $html .= "{$varNames[CorrectiveActionLocation::UNKNOWN]}: " . CorrectiveActionLocation::UNKNOWN . ", ";
      
      foreach (CorrectiveActionLocation::$values as $location)
      {
         $html .= "{$varNames[$location]}: $location";
         $html .= ($location < (CorrectiveActionLocation::LAST - 1) ? ", " : "");
      }
      
      $html .= "};";
      
      return ($html);
   }
}

?>