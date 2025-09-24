<?php

if (!defined('ROOT')) require_once '../../../root.php';
require_once ROOT.'/core/manager/shipmentManager.php';

class ShipmentManagerTest
{
   public static function run()
   {
      echo "Running ShipmentManagerTest ...<br>";
      
      $test = new ShipmentManagerTest();
      
      $test->testSplit();
   }
   
   private static function testSplit()
   {
      echo "ShipmentManager::testSplit()<br>";
      
      $shipment = new Shipment();
      $shipment->author = 1975;
      $shipment->dateTime = Time::now();
      $shipment->quantity = 1000;
      $shipment->location = ShipmentLocation::WIP;
      
      Shipment::save($shipment);
      var_dump($shipment);
      
      $childShipmentId = ShipmentManager::split($shipment->shipmentId, 202, 300, ShipmentLocation::CUSTOMER);
      
      $child = Shipment::load($childShipmentId);
      $parent = null;
      if ($child)
      {
         $parent = $child->getParent();
      }
      
      echo "Parent {$parent->shipmentId} : " . ShipmentManager::getShipmentTicketCode($parent->shipmentId);
      var_dump($parent);
      
      echo "Child {$child->shipmentId} : " . ShipmentManager::getShipmentTicketCode($child->shipmentId);
      var_dump($child);
      
      Shipment::delete($parent->shipmentId);
      Shipment::delete($child->shipmentId);
   }
}
      
ShipmentManagerTest::run();
      
?>