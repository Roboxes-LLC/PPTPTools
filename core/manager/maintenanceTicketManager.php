<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/component/maintenanceTicket.php';
require_once ROOT.'/core/common/pptpDatabase.php';

class MaintenanceTicketManager
{
   public static function getMaintenanceTickets($startDate = null, $endDate = null, $allActive = false)
   {
      $maintenanceTickets = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getMaintenanceTickets(FilterDateType::OCCURANCE_DATE, $startDate, $endDate, $allActive);
      
      foreach ($result as $row)
      {
         $maintenanceTicket = new MaintenanceTicket();
         $maintenanceTicket->initialize($row);
         $maintenanceTicket->job = JobInfo::load($maintenanceTicket->jobId);
         $maintenanceTicket->actions = MaintenanceTicket::getActions($maintenanceTicket->ticketId);
         $maintenanceTicket->attachments = MaintenanceTicket::getAttachments($maintenanceTicket->ticketId);
         
         $maintenanceTickets[] = $maintenanceTicket;
      }
      
      return ($maintenanceTickets);
   }
}