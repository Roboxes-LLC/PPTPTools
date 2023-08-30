<?php

abstract class ChargeCode
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const TOOLING = ChargeCode::FIRST;
   const LAST = 2;
   const COUNT = ChargeCode::LAST - ChargeCode::FIRST;
   
   public static $values = array(ChargeCode::TOOLING);
   
   public static function getLabel($chargeCode)
   {
      $labels = array("", "Tooling");
      
      return ($labels[$chargeCode]);
   }
   
   public static function getOptions($selectedChargeCode)
   {
      $label = "";
      $value = ChargeCode::UNKNOWN;      
      $html = "<option value=\"$value\">$label</option>";
      
      foreach (ChargeCode::$values as $chargeCode)
      {
         $label = ChargeCode::getLabel($chargeCode);
         $value = $chargeCode;
         $selected = ($chargeCode == $selectedChargeCode) ? "selected" : "";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
      
      return ($html);
   }   
}