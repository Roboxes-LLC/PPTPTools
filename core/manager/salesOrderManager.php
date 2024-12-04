<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/pptpDatabase.php';
require_once ROOT.'/core/component/salesOrder.php';

class SalesOrderManager
{
   public static function getSalesOrders($startDate, $endDate, $allActive)
   {
      $salesOrders = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getSalesOrders($startDate, $endDate, $allActive);
      
      foreach ($result as $row)
      {
         $salesOrder = new SalesOrder();
         $salesOrder->initialize($row);
         
         $salesOrders[] = $salesOrder;
      }
      
      return ($salesOrders);
   }
}