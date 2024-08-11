<?php

abstract class NotificationPriority
{
   const UNKNOWN = 0;
   const FIRST = 1;
   // Informational
   const INFORMATIONAL = NotificationPriority::FIRST;
   // Timely
   const PRIORITY = 2;
   // Problematic, but not devastating.
   const WARNING = 3;
   // Devastating and immediate.
   const CRITICAL = 4;
   const LAST = 5;
   const COUNT = NotificationPriority::LAST - NotificationPriority::FIRST;

   public static $values = array(NotificationPriority::INFORMATIONAL, NotificationPriority::PRIORITY, NotificationPriority::WARNING, NotificationPriority::CRITICAL);

   public static function getLabel($priority)
   {
      $labels = array("", "Info", "Priority", "Warning", "Critical");

      return ($labels[$priority]);
   }

   public static function getClass($priority)
   {
      return (strtolower(NotificationPriority::getLabel($priority)));
   }
   
   public static function getOptions($selectedPriority = null)
   {
      $html = "<option style=\"display:none\">";
      
      foreach (NotificationPriority::$values as $priority)
      {
         $label = NotificationPriority::getLabel($priority);
         $value = $priority;
         $selected = ($priority == $selectedPriority) ? "selected" : "";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
      
      return ($html);
   }
}

?>
