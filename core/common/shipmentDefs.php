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
   
   public static function getLabel($location)
   {
      $labels = array("", "Pittsburgh Precision", "Plater", "Customer");
      
      return ($labels[$location]);
   }
   
   public static function getOptions($selectedLocation)
   {
      $label = "";
      $value = ShipmentLocation::UNKNOWN;
      $html = "<option value=\"$value\">$label</option>";
      
      foreach (ShipmentLocation::$values as $location)
      {
         $label = ShipmentLocation::getLabel($location);
         $value = $location;
         $selected = ($location == $selectedLocation) ? "selected" : "";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
      
      return ($html);
   }
}

?>