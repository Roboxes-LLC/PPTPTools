<?php

abstract class MachineState
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const RUNNING = MachineState::FIRST;
   const DOWN = 2;
   const LAST = 3;
   const COUNT = MachineState::LAST - MachineState::FIRST;

   public static $values = array(MachineState::RUNNING, MachineState::DOWN);

      public static function getLabel($status)
   {
      $labels = array("", "Running", "Down");

      return ($labels[$status]);
   }

   public static function getOptions($selectedState)
   {
      $html = "<option style=\"display:none\">";

      foreach (MachineState::$values as $state)
      {
         $label = MachineState::getLabel($state);
         $value = $state;
         $selected = ($state == $selectedState) ? "selected" : "";

         $html .= "<option value=\"$value\" $selected>$label</option>";
      }

      return ($html);
   }

   public static function getClass($status)
   {
      return (strtolower(MachineState::getLabel($status)));
   }
}

abstract class MaintenanceTicketStatus
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const REPORTED = MaintenanceTicketStatus::FIRST;
   const ACKNOWLEDGED = 2;
   const UNDER_REPAIR = 3;
   const REPAIRED = 4;
   const CONFIRMED = 5;
   const CLOSED = 6;
   const LAST = 7;
   const COUNT = MaintenanceTicketStatus::LAST - MaintenanceTicketStatus::FIRST;

   public static $values = array(MaintenanceTicketStatus::REPORTED, MaintenanceTicketStatus::ACKNOWLEDGED, MaintenanceTicketStatus::UNDER_REPAIR, MaintenanceTicketStatus::REPAIRED, MaintenanceTicketStatus::CONFIRMED, MaintenanceTicketStatus::CLOSED);

   public static function getLabel($status)
   {
      $labels = array("", "Reported", "Acknowledged", "Under Repair", "Repaired", "Confirmed", "Closed");

      return ($labels[$status]);
   }
   
   public static function getClass($status)
   {
      return (strtolower(MaintenanceTicketStatus::getLabel($status)));
   }

   public static function getOptions($selectedStatus)
   {
      $html = "<option style=\"display:none\">";

      foreach (MaintenanceTicketStatus::$values as $status)
      {
         $label = MaintenanceTicketStatus::getLabel($status);
         $value = $status;
         $selected = ($status == $selectedStatus) ? "selected" : "";

         $html .= "<option value=\"$value\" $selected>$label</option>";
      }

      return ($html);
   }
   
   public static function getJavascript($enumName)
   {
      // Note: Keep synced with enum.
      $varNames = array("UNKNOWN", "REPORTED", "ACKNOWLEDGED", "UNDER_REPAIR", "REPAIRED", "CONFIRMED", "CLOSED");
      
      $html = "$enumName = {";
      
      $html .= "{$varNames[MaintenanceTicketStatus::UNKNOWN]}: " . MaintenanceTicketStatus::UNKNOWN . ", ";
      
      foreach (MaintenanceTicketStatus::$values as $status)
      {
         $html .= "{$varNames[$status]}: $status";
         $html .= ($status < (MaintenanceTicketStatus::LAST - 1) ? ", " : "");
      }
      
      $html .= "};";
      
      return ($html);
   } 
}

?>