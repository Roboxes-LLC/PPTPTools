<?php 

abstract class ShipmentLocation
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const PPTP = ShipmentLocation::FIRST;
   const PLATER = 2;
   const CUSTOMER = 3;
   const LAST = 4;
   const COUNT = ShipmentLocation::LAST - ShipmentLocation::FIRST;
   
   public static $values = array(ShipmentLocation::PPTP, ShipmentLocation::PLATER, ShipmentLocation::CUSTOMER);
   
   public static $activeLocations = [ShipmentLocation::PPTP, ShipmentLocation::PLATER];
   
   public static function getLabel($location)
   {
      $labels = array("", "Pittsburgh Precision", "Plater", "Customer");
      
      return ($labels[$location]);
   }
   
   public static function getOptions($selectedLocation)
   {
      $html = "<option style=\"display:none\">";
      
      foreach (ShipmentLocation::$values as $location)
      {
         $label = ShipmentLocation::getLabel($location);
         $value = $location;
         $selected = ($location == $selectedLocation) ? "selected" : "";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
      
      return ($html);
   }
   
   public static function getJavascript($enumName)
   {
      // Note: Keep synced with enum.
      $varNames = array("UNKNOWN", "PPTP", "PLATER", "CUSTOMER");
      
      $html = "$enumName = {";
      
      $html .= "{$varNames[ShipmentLocation::UNKNOWN]}: " . ShipmentLocation::UNKNOWN . ", ";
      
      foreach (ShipmentLocation::$values as $location)
      {
         $html .= "{$varNames[$location]}: $location";
         $html .= ($location < (ShipmentLocation::LAST - 1) ? ", " : "");
      }
      
      $html .= "};";
      
      return ($html);
   } 
}

abstract class PlatingStatus
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const RAW = PlatingStatus::FIRST;
   const PLATED = 2;
   const STRIP_AND_REPLATE = 3;
   const LAST = 4;
   const COUNT = PlatingStatus::LAST - PlatingStatus::FIRST;
   
   public static $values = array(PlatingStatus::PLATED, PlatingStatus::PLATED, PlatingStatus::STRIP_AND_REPLATE);
   
   public static function getLabel($status)
   {
      $labels = array("", "Raw", "Plated", "Strip and Replate");
      
      return ($labels[$status]);
   }
   
   public static function getOptions($selectedStatus)
   {
      $html = "<option style=\"display:none\">";
      
      foreach (PlatingStatus::$values as $status)
      {
         $label = PlatingStatus::getLabel($status);
         $value = $status;
         $selected = ($status == $selectedStatus) ? "selected" : "";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
      
      return ($html);
   }
}

?>