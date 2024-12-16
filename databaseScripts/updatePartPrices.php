<?php 

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/params.php';
require_once ROOT.'/core/component/part.php';
require_once ROOT.'/core/manager/salesOrderManager.php';

$params = Params::parse();
$commit = $params->getBool("commit");

$entriesUpdated = 0;

$salesOrders = SalesOrderManager::getSalesOrders(Time::now(), Time::now(), true);

foreach ($salesOrders as $salesOrder)
{
   if ($salesOrder->unitPrice > 0)
   {
      $part = Part::load($salesOrder->customerPartNumber, Part::USE_CUSTOMER_NUMBER);
      
      if ($part && ($part->unitPrice == 0))
      {
         echo "Part $part->pptpNumber: " . number_format($salesOrder->unitPrice, 4) . "<br>";

         $part->unitPrice = $salesOrder->unitPrice;
         $entriesUpdated++;
         
         if ($commit)
         {
            Part::save($part);
         }
      }
   }
}

echo "Updated $entriesUpdated entries<br>";