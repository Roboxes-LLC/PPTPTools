<?php 

abstract class FilterDateType
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const ENTRY_DATE = FilterDateType::FIRST;
   const MANUFACTURING_DATE = 2;
   const WEIGH_DATE = 3;
   const WASH_DATE = 4;
   const MAINTENANCE_DATE = 5;
   const RECEIVE_DATE = 6;
   const LAST = 7;
   const COUNT = FilterDateType::LAST - FilterDateType::FIRST;
   
   public static $values = array(FilterDateType::ENTRY_DATE, FilterDateType::MANUFACTURING_DATE, FilterDateType::WEIGH_DATE, FilterDateType::WASH_DATE, FilterDateType::MAINTENANCE_DATE, FilterDateType::RECEIVE_DATE);
   
   public static function getLabel($filterDateType)
   {
      $labels = array("", "Entry Date", "Manufacturing Date", "Weigh Date", "Wash Date", "Maintenance Date", "Receive Date");
      
      return ($labels[$filterDateType]);
   }
   
   public static function getOptions($options, $selectedFilterDateType)
   {
      $html = "<option style=\"display:none\">";

      foreach ($options as $filterDateType)
      {
         $label = FilterDateType::getLabel($filterDateType);
         $selected = ($filterDateType == $selectedFilterDateType) ? "selected" : "";
         
         $html .= "<option value=\"$filterDateType\" $selected>$label</option>";
      }
      
      return ($html);
   }
}

?>