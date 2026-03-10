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
   const ASSIGNED = 2;
   const ACKNOWLEDGED = 3;
   const UNDER_REPAIR = 4;
   const REPAIRED = 5;
   const CONFIRMED = 6;
   const CLOSED = 7;
   const LAST = 8;
   const COUNT = MaintenanceTicketStatus::LAST - MaintenanceTicketStatus::FIRST;

   public static $values = array(MaintenanceTicketStatus::REPORTED, MaintenanceTicketStatus::ASSIGNED, MaintenanceTicketStatus::ACKNOWLEDGED, MaintenanceTicketStatus::UNDER_REPAIR, MaintenanceTicketStatus::REPAIRED, MaintenanceTicketStatus::CONFIRMED, MaintenanceTicketStatus::CLOSED);

   public static function getLabel($status)
   {
      $labels = array("", "Reported", "Assigned", "Acknowledged", "Under Repair", "Repaired", "Confirmed", "Closed");

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
      $varNames = array("UNKNOWN", "REPORTED", "ASSIGNED", "ACKNOWLEDGED", "UNDER_REPAIR", "REPAIRED", "CONFIRMED", "CLOSED");
      
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

abstract class MaintenanceTicketAction
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const REPORT = MaintenanceTicketAction::FIRST;
   const ASSIGN = 2;
   const ACKNOWLEDGE = 3;
   const BEGIN_REPAIR = 4;
   const COMPLETE_REPAIR = 5;
   const CONFIRM = 6;
   const CLOSE = 7;
   const LAST = 8;
   const COUNT = MaintenanceTicketAction::LAST - MaintenanceTicketAction::FIRST;

   public static $values = array(MaintenanceTicketAction::REPORT, MaintenanceTicketAction::ASSIGN, MaintenanceTicketAction::ACKNOWLEDGE, MaintenanceTicketAction::BEGIN_REPAIR, MaintenanceTicketAction::COMPLETE_REPAIR, MaintenanceTicketAction::CONFIRM, MaintenanceTicketAction::CLOSE);

   public static function getLabel($action)
   {
      $labels = array("", "Report", "Assign", "Acknowledge", "Begin Repair", "Complete Repair", "Confirm", "Close");

      return ($labels[$action]);
   }

   public static function getApiCommand($action)
   {
      $commands = array("", "report", "assign", "acknowledge", "begin_repair", "complete_repair", "confirm", "close");

      return ($commands[$action]);
   }

   public static function getNextAction($status)
   {
      $action = MaintenanceTicketAction::UNKNOWN;

      if (($status >= MaintenanceTicketStatus::UNKNOWN) &&
          ($status < MaintenanceTicketStatus::CLOSED))
      {
         $action = MaintenanceTicketAction::$values[$status];
      }

      return ($action);
   }
}

?>