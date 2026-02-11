<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/userInfo.php';
require_once ROOT.'/core/common/componentType.php';
require_once ROOT.'/core/common/maintenanceTicketDefs.php';
require_once ROOT.'/core/common/pptpDatabase.php';

class MaintenanceTicket
{
   const UNKNOWN_TICKET_ID = 0;
   
   public $ticketId;
   public $author;
   public $posted;
   public $wcNumber;
   public $jobNumber;
   public $machineState;
   public $description;
   public $details;
   public $assigned;
   public $status;

   public $actions;
   public $attachments;

   public $job;
   
   public function __construct()
   {
      $this->ticketId = MaintenanceTicket::UNKNOWN_TICKET_ID;
      $this->author = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->posted = null;
      $this->wcNumber = JobInfo::UNKNOWN_WC_NUMBER;
      $this->jobNumber = JobInfo::UNKNOWN_JOB_NUMBER;
      $this->machineState = MachineState::UNKNOWN;
      $this->description = null;
      $this->details = null;
      $this->assigned = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->status = MaintenanceTicketStatus::UNKNOWN;

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
      $this->wcNumber = $row["wcNumber"];
      $this->jobNumber = $row["jobNumber"];
      $this->machineState = intval($row["machineState"]);
      $this->description = $row["description"];
      $this->details = $row["details"];
      $this->assigned = intval($row["assigned"]);
      $this->status = intval($row["status"]);
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
}