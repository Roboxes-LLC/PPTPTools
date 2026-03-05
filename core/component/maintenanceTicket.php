<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/userInfo.php';
require_once ROOT.'/core/common/componentType.php';
require_once ROOT.'/core/common/maintenanceTicketDefs.php';
require_once ROOT.'/core/common/pptpDatabase.php';
require_once ROOT.'/core/component/action.php';

class MaintenanceTicket
{
   const UNKNOWN_TICKET_ID = 0;

   // Maintenance ticket number formatting prefix.
   const MAINTENANCE_TICKET_NUMBER_PREFIX = "MNT";

   const HIGHEST_PRIORITY = 1;

   public $ticketId;
   public $author;
   public $posted;
   public $occured;
   public $wcNumber;
   public $jobNumber;
   public $machineState;
   public $description;
   public $details;
   public $assigned;
   public $status;
   public $priority;

   public $actions;
   public $attachments;

   public $job;
   
   public function __construct()
   {
      $this->ticketId = MaintenanceTicket::UNKNOWN_TICKET_ID;
      $this->author = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->posted = null;
      $this->occured = null;
      $this->wcNumber = JobInfo::UNKNOWN_WC_NUMBER;
      $this->jobNumber = JobInfo::UNKNOWN_JOB_NUMBER;
      $this->machineState = MachineState::UNKNOWN;
      $this->description = null;
      $this->details = null;
      $this->assigned = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->status = MaintenanceTicketStatus::UNKNOWN;
      $this->priority = 0;

      $this->actions = array();
      $this->attachments = array();
   }
   
   public function initialize($row)
   {
      $this->ticketId = intval($row["ticketId"]);
      $this->author = intval($row["author"]);
      $this->posted = $row["posted"] ?
                         Time::fromMySqlDate($row["posted"]) :
                         null;
      $this->occured = $row["occured"] ?
                          Time::fromMySqlDate($row["occured"]) :
                          null;
      $this->wcNumber = $row["wcNumber"];
      $this->jobNumber = $row["jobNumber"];
      $this->machineState = intval($row["machineState"]);
      $this->description = $row["description"];
      $this->details = $row["details"];
      $this->assigned = intval($row["assigned"]);
      $this->status = intval($row["status"]);
      $this->priority = intval($row["priority"]);
   }
   
   // **************************************************************************
   // Component interface
   
   public static function load($ticketId)
   {
      $maintenanceTicket = null;
      
      $result = PPTPDatabaseAlt::getInstance()->getMaintenanceTicket($ticketId);
      
      if ($result && ($row = $result[0]))
      {
         $maintenanceTicket = new MaintenanceTicket();
         
         $maintenanceTicket->initialize($row);

         $maintenanceTicket->actions = MaintenanceTicket::getActions($maintenanceTicket->ticketId);
         
         $maintenanceTicket->attachments = MaintenanceTicket::getAttachments($maintenanceTicket->ticketId);
      }
      
      return ($maintenanceTicket);
   }
   
   public static function save($maintenanceTicket)
   {
      $success = false;
      
      if ($maintenanceTicket->ticketId == MaintenanceTicket::UNKNOWN_TICKET_ID)
      {
         $success = PPTPDatabaseAlt::getInstance()->addMaintenanceTicket($maintenanceTicket);

         $maintenanceTicket->ticketId = intval(PPTPDatabaseAlt::getInstance()->lastInsertId());
      }
      else
      {
         $success = PPTPDatabaseAlt::getInstance()->updateMaintenanceTicket($maintenanceTicket);
      }
      
      return ($success);
   }
   
   public static function delete($docId)
   {
      return (PPTPDatabaseAlt::getInstance()->deleteMaintenanceTicket($docId));
   }
   
   // **************************************************************************

   public function report($userId, $notes)
   {
      return ($this->addAction(MaintenanceTicketStatus::REPORTED, Time::now(), $userId, $notes));
   }

   public function assign($userId, $assigned, $notes)
   {
      $this->assigned = $assigned;
      MaintenanceTicket::save($this);

      return ($this->addAction(MaintenanceTicketStatus::ASSIGNED, Time::now(), $userId, $notes));
   }

   public function acknowledge($userId, $notes)
   {
      return ($this->addAction(MaintenanceTicketStatus::ACKNOWLEDGED, Time::now(), $userId, $notes));
   }

   public function beginRepair($userId, $notes)
   {
      return ($this->addAction(MaintenanceTicketStatus::UNDER_REPAIR, Time::now(), $userId, $notes));
   }

   public function completeRepair($userId, $notes)
   {
      return ($this->addAction(MaintenanceTicketStatus::REPAIRED, Time::now(), $userId, $notes));
   }

   public function confirm($userId, $notes)
   {
      return ($this->addAction(MaintenanceTicketStatus::CONFIRMED, Time::now(), $userId, $notes));
   }

   public function close($userId, $notes)
   {
      return ($this->addAction(MaintenanceTicketStatus::CLOSED, Time::now(), $userId, $notes));
   }

   // **************************************************************************
   
   private function addAction($status, $dateTime, $userId, $notes)
   {
      $action = new Action();
      $action->componentType = ComponentType::MAINTENANCE_TICKET;
      $action->componentId = $this->ticketId;
      $action->status = $status;
      $action->dateTime = $dateTime;
      $action->userId = $userId;
      $action->notes = $notes;
      
      $success = Action::save($action);
      
      if ($success)
      {
         $this->actions[] = $action;
         
         $this->recalculateStatus();
         
         $success &= PPTPDatabaseAlt::getInstance()->updateMaintenanceTicketStatus($this->ticketId, $this->status);
      }
      
      return ($success);
   }

   public static function getActions($ticketId)
   {
      $actions = array();
      
      if ($ticketId != MaintenanceTicket::UNKNOWN_TICKET_ID)
      {
         $result = PPTPDatabaseAlt::getInstance()->getActions(ComponentType::MAINTENANCE_TICKET, $ticketId);
         
         foreach ($result as $row)
         {
            $action = new Action();
            $action->initialize($row);
            $actions[] = $action;
         }
      }
      
      return ($actions);
   }
   
   public static function getAttachments($ticketId)
   {
      $attachments = array();
      
      if ($ticketId != MaintenanceTicket::UNKNOWN_TICKET_ID)
      {
         $result = PPTPDatabaseAlt::getInstance()->getAttachments(ComponentType::MAINTENANCE_TICKET, $ticketId);
         
         foreach ($result as $row)
         {
            $attachment = new Attachment();
            $attachment->initialize($row);
            $attachments[] = $attachment;
         }
      }
      
      return ($attachments);
   }

   public function getMaintenanceTicketNumber()
   {
      return (sprintf('%s%05d', MaintenanceTicket::MAINTENANCE_TICKET_NUMBER_PREFIX, $this->ticketId));
   }

   public static function getLink($ticketId)
   {
      $html = "";
      
      $maintenanceTicket = MaintenanceTicket::load($ticketId);
      if ($maintenanceTicket)
      {
         $label = $maintenanceTicket->getMaintenanceTicketNumber();
         
         $html = "<a href=\"/maintenanceTicket/maintenanceTicket.php?correctiveActionId=$ticketId\">$label</a>";
      }
      
      return ($html);
   }

   public function getUpdateTime()
   {
      $updateTime = null;

      if (count($this->actions) > 0)
      {
         $updateTime = end($this->actions)->dateTime;
      }

      return ($updateTime);
   }

   public function getResolveTime()
   {
      $resolvedTime = 0;

      if (($this->status == MaintenanceTicketStatus::CONFIRMED) ||
          ($this->status == MaintenanceTicketStatus::CLOSED))
      {
         $actions = $this->findActions(MaintenanceTicketStatus::REPORTED);
         $reportedTime = (count($actions) > 0) ? end($actions)->dateTime : null;

         $actions = $this->findActions(MaintenanceTicketStatus::CONFIRMED);
         $confirmedTime = (count($actions) > 0) ? end($actions)->dateTime : null;

         if ($reportedTime && $confirmedTime)
         {
            $resolvedTime = Time::differenceSeconds($reportedTime, $confirmedTime);
         }
      }

      return ($resolvedTime);
   }

   public function findActions($maintenanceTicketStatus)
   {
      $foundActions = array();
      
      foreach ($this->actions as $action)
      {
         if ($action->status == $maintenanceTicketStatus)
         {
            $foundActions[] = $action;
         }
      }
      
      return ($foundActions);
   }

   private function recalculateStatus()
   {
      if (count($this->actions) > 0)
      {
         // The status is determined by the last action.
         $this->status = end($this->actions)->status;
      }
      else
      {
         $this->status = MaintenanceTicketStatus::UNKNOWN;
      }
      
      return ($this->status);
   }
}