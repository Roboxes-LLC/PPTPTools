<?php

if (!defined('ROOT')) require_once '../../../root.php';
require_once ROOT.'/core/manager/ProspiraDocManager.php';

class ProspiraDocManagerTest
{
   const NON_PROSPIRA_SHIPMENT_ID = 53;
   
   const PROSPIRA_SHIPMENT_ID = 54;
   
   public static function run()
   {
      echo "Running ProspiraDocManagerTest ...<br>";
      
      $test = new ProspiraDocManagerTest();
      
      $test->testGetProspiraCustomerId();
      
      $test->testIsProspiraShipment();
   }
   
   private static function testGetProspiraCustomerId()
   {
      echo "ProspiraDocManager::getProspiraCustomerId()<br>";
      
      $customer = Customer::load(ProspiraDocManager::getProspiraCustomerId());
      
      echo "Prospira customer is {$customer->customerName}<br>";
   }
   
   private static function testIsProspiraShipment()
   {
      echo "ProspiraDocManager::isProspiraShipment()<br>";
      
      $isProspiraShipment = ProspiraDocManager::isProspiraShipment(ProspiraDocManagerTest::PROSPIRA_SHIPMENT_ID) ? "is" : "is not";
      
      echo "Shipment " .  ProspiraDocManagerTest::PROSPIRA_SHIPMENT_ID . " $isProspiraShipment a Prospira shippment.<br>";
      
      $isProspiraShipment = ProspiraDocManager::isProspiraShipment(ProspiraDocManagerTest::NON_PROSPIRA_SHIPMENT_ID) ? "is" : "is not";
      
      echo "Shipment " .  ProspiraDocManagerTest::NON_PROSPIRA_SHIPMENT_ID . " $isProspiraShipment a Prospira shippment.<br>";
   }
}
      
ProspiraDocManagerTest::run();
      
?>