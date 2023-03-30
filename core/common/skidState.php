<?php

abstract class SkidState
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const CREATED = SkidState::FIRST;
   const ASSEMBLING = 2;
   const PENDING_INSPECTION = 3;
   const PASSED = 4;
   const FAILED = 5;
   const OUT_FOR_PLATING = 6;
   const REJECTED = 7;
   const COMPLETE = 8;
   const DELIVERED = 9;
   const LAST = 10;
   const COUNT = (SkidState::LAST - SkidState::FIRST);
   
   public static $values = 
      array(
         SkidState::CREATED,
         SkidState::ASSEMBLING,
         SkidState::PENDING_INSPECTION,
         SkidState::PASSED,
         SkidState::FAILED,
         SkidState::OUT_FOR_PLATING,
         SkidState::REJECTED,
         SkidState::COMPLETE,
         SkidState::DELIVERED
      );
      
   public static $activeStates =
      array(
         SkidState::CREATED,
         SkidState::ASSEMBLING,
         SkidState::PENDING_INSPECTION,
         SkidState::PASSED,
         SkidState::FAILED,
         SkidState::OUT_FOR_PLATING,
         SkidState::REJECTED,
         SkidState::COMPLETE,
      );
   
   public static function getLabel($skidState)
   {
      $labels = array("", "Created", "Assembling", "Pending Inspection", "Passed", "Failed", "Out for Plating", "Rejected", "Complete", "Delivered");
      
      return ($labels[$skidState]);
   }
   
   public static function getOptions($selectedState)
   {
      $html = "<option style=\"display:none\">";
      
      foreach (SkidState::$values as $skidState)
      {
         $label = SkidState::getLabel($skidState);
         $selected = ($skidState == $selectedState) ? "selected" : "";
         
         $html .= "<option value=\"$skidState\" $selected>$label</option>";
      }
      
      return ($html);
   }
}

?>