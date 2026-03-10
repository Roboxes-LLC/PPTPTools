<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/component/maintenanceTicket.php';
require_once ROOT.'/core/common/pptpDatabase.php';

class MaintenanceTicketManager
{
   public static function getMaintenanceTickets($dateType, $startDate = null, $endDate = null, $allActive = false)
   {
      $maintenanceTickets = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getMaintenanceTickets($dateType, $startDate, $endDate, $allActive);
      
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

   public static function getMaintenanceDescriptions()
   {
      $descriptions = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getMaintenanceDescriptions();
      
      foreach ($result as $row)
      {
         $descriptions[intval($row["descriptionId"])] = $row["label"];
      }
      
      return ($descriptions);
   }

   public static function getNextPriority()
   {
      $nextPriority = 0;

      $maintenanceTickets = MaintenanceTicketManager::getMaintenanceTickets(FilterDateType::POSTED_DATE, null, null, true);

      usort($maintenanceTickets, [MaintenanceTicketManager::class, "priorityComparator"]);

      $nextPriority = empty($maintenanceTickets) ? 
                         MaintenanceTicket::HIGHEST_PRIORITY : 
                         (end($maintenanceTickets)->priority + 1);

      return ($nextPriority);
   }
   
   public static function priorityComparator($a, $b)
   {
      $returnStatus = 0;

      if ($a->priority == $b->priority)
      {
        $returnStatus = 0;
      }
      else
      {
         $returnStatus = ($a->priority < $b->priority) ? -1 : 1;
      }

      return ($returnStatus);
   }
}