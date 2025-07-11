<?php 

abstract class ShipmentLocation
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const PPTP = ShipmentLocation::FIRST;
   const PLATER = 2;
   const CUSTOMER = 3;
   const VENDOR = 4;
   const LAST = 5;
   const COUNT = ShipmentLocation::LAST - ShipmentLocation::FIRST;
   
   public static $values = array(ShipmentLocation::PPTP, ShipmentLocation::PLATER, ShipmentLocation::CUSTOMER, ShipmentLocation::VENDOR);
   
   public static $activeLocations = [ShipmentLocation::PPTP, ShipmentLocation::PLATER];
   
   public static function getLabel($location)
   {
      $labels = array("", "Pittsburgh Precision", "Plater", "Customer", "Vendor");
      
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
      $varNames = array("UNKNOWN", "PPTP", "PLATER", "CUSTOMER", "VENDOR");
      
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

?>