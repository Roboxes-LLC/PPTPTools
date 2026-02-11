<?php

if (!defined('ROOT')) require_once '../../../root.php';
require_once ROOT.'/core/common/maintenanceTicketDefs.php';
require_once ROOT.'/core/component/maintenanceTicket.php';

$maintenanceTicket = new MaintenanceTicket();

class MaintenanceTicketTest
{
   private const AUTHOR = 1975;
   private const POSTED = "2026-1-26 08:00:00";
   private const WC_NUMBER = 1;
   private const JOB_ID = 1;
   private const MACHINE_STATE = MachineState::RUNNING;
   private const DESCRIPTION = "Champher bit threw a rod";
   private const DETAILS = "I think Gary left his gum in the machine.";
   private const ASSIGNED = 1;
   
   private const OTHER_AUTHOR = 3;
   private const OTHER_POSTED = "2026-1-27 04:00:00";
   private const OTHER_WC_NUMBER = 2;
   private const OTHER_JOB_ID = 2;
   private const OTHER_MACHINE_STATE = MachineState::DOWN;
   private const OTHER_DESCRIPTION = "Oil pan is fried";
   private const OTHER_DETAILS = "I think Stephen used vegatable oil.";
   private const OTHER_ASSIGNED = 2;
   
   public static function run()
   {
      echo "Running MaintenanceTicketTest ...<br>";
      
      $test = new MaintenanceTicketTest();

      $test->testSave_Add();
      
      if (MaintenanceTicketTest::$newTicketId != MaintenanceTicket::UNKNOWN_TICKET_ID)
      {
         $test->testLoad();
         
         $test->testSave_Update();     
         
         $test->testDelete();
      }
   }
   
   public function testLoad()
   {
      echo "MaintenanceTicket::load()<br>";
      
      $maintenanceTicket = MaintenanceTicket::load(MaintenanceTicketTest::$newTicketId);
      
      var_dump($maintenanceTicket);
   }
   
   public function testSave_Add()
   {
      echo "MaintenanceTicket::save(newMaintenanceTicket)<br>";
      
      $maintenanceTicket = new MaintenanceTicket();
      
      $maintenanceTicket->author = MaintenanceTicketTest::AUTHOR;
      $maintenanceTicket->posted = MaintenanceTicketTest::POSTED;
      $maintenanceTicket->wcNumber = MaintenanceTicketTest::WC_NUMBER;
      $maintenanceTicket->jobId = MaintenanceTicketTest::JOB_ID;
      $maintenanceTicket->machineState = MaintenanceTicketTest::MACHINE_STATE;
      $maintenanceTicket->description = MaintenanceTicketTest::DESCRIPTION;
      $maintenanceTicket->details = MaintenanceTicketTest::DETAILS;
      $maintenanceTicket->assigned = MaintenanceTicketTest::ASSIGNED;
      
      MaintenanceTicket::save($maintenanceTicket);
      
      MaintenanceTicketTest::$newTicketId = $maintenanceTicket->ticketId;
      
      $maintenanceTicket = MaintenanceTicket::load(MaintenanceTicketTest::$newTicketId);
      
      var_dump($maintenanceTicket);
   }
   
   public function testSave_Update()
   {
      echo "MaintenanceTicket::save(existingMaintenanceTicket)<br>";
      
      $maintenanceTicket = MaintenanceTicket::load(MaintenanceTicketTest::$newTicketId);
      
      $maintenanceTicket->author = MaintenanceTicketTest::OTHER_AUTHOR;
      $maintenanceTicket->posted = MaintenanceTicketTest::OTHER_POSTED;
      $maintenanceTicket->wcNumber = MaintenanceTicketTest::OTHER_WC_NUMBER;
      $maintenanceTicket->jobId = MaintenanceTicketTest::OTHER_JOB_ID;
      $maintenanceTicket->machineState = MaintenanceTicketTest::OTHER_MACHINE_STATE;
      $maintenanceTicket->description = MaintenanceTicketTest::OTHER_DESCRIPTION;
      $maintenanceTicket->details = MaintenanceTicketTest::OTHER_DETAILS;
      $maintenanceTicket->assigned = MaintenanceTicketTest::OTHER_ASSIGNED;
      
      MaintenanceTicket::save($maintenanceTicket);
      
      var_dump($maintenanceTicket);
   }
   
   public function testDelete()
   {
      echo "MaintenanceTicket::delete()<br>";
      
      MaintenanceTicket::delete(MaintenanceTicketTest::$newTicketId);
   }
   
   private static $newTicketId = MaintenanceTicket::UNKNOWN_TICKET_ID;
}

MaintenanceTicketTest::run();

?>