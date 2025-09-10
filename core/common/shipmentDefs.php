<?php 

abstract class ShipmentLocation
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const WIP = ShipmentLocation::FIRST;
   const VENDOR = 2;
   const FINISHED_GOODS = 3;
   const CUSTOMER = 4;
   const LAST = 5;
   const COUNT = ShipmentLocation::LAST - ShipmentLocation::FIRST;
   
   public static $values = array(ShipmentLocation::WIP, ShipmentLocation::VENDOR, ShipmentLocation::FINISHED_GOODS, ShipmentLocation::CUSTOMER);
   
   public static $activeLocations = [ShipmentLocation::WIP, ShipmentLocation::VENDOR, ShipmentLocation::FINISHED_GOODS];
   
   public static function getLabel($location)
   {
      $labels = array("", "WIP", "Vendor", "Finished Goods", "Customer");
      
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
      $varNames = array("UNKNOWN", "WIP", "VENDOR", "FINISHED_GOODS", "CUSTOMER");
      
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