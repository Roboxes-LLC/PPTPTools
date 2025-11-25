<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/pptpDatabase.php';
require_once ROOT.'/core/component/audit.php';

class AuditManager
{
   public static function getAudits($startDateTime, $endDateTime)
   {
      $audits = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getAudits($startDateTime, $endDateTime);
      
      foreach ($result as $row)
      {
         $audit = new Audit();
         $audit->initialize($row);
         
         $audit->lineItems = Audit::getLineItems($audit->auditId);
         
         $audits[] = $audit;
      }
      
      return ($audits);
   }
   
   public static function getAuditsByStatus($auditStatuses)
   {
      $audits = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getAuditsByStatus($auditStatuses);
      
      foreach ($result as $row)
      {
         $audit = new Audit();
         $audit->initialize($row);
         
         $audit->lineItems = Audit::getLineItems($audit->auditId);
         
         $audits[] = $audit;
      }
      
      return ($audits);
   }
   
   public static function getAuditAccuracy($auditId)
   {
      $accuracy = 0; // percent
      
      $audit = Audit::load($auditId);
      
      if ($audit)
      {
         $expectedCount = count($audit->lineItems);
         $actualCount = 0;
         
         foreach ($audit->lineItems as $auditLine)
         {
            if ($auditLine->confirmed)
            {
               $actualCount++;
            }
         }
         
         if ($expectedCount > 0)
         {
            $accuracy = round((($actualCount / $expectedCount) * 100));
         }
      }      
      
      return ($accuracy);
   }
   
   // **************************************************************************
   //                                   Private
   
   private static function createAuditLines($siteId, &$audit)
   {
      /*
      $inventoryView = InventoryView::load($audit->viewId);
      
      if ($inventoryView)
      {
         $filter = InventoryItemFilter::CATALOG | InventoryItemFilter::ACTIVE | InventoryItemFilter::INVENTORY;
         
         foreach ($inventoryView->getFilteredInventoryItems($siteId, $filter) as $inventoryItem)
         {
            $auditLine = new AuditLine();
            $auditLine->auditId = $audit->auditId;
            $auditLine->itemId = $inventoryItem->itemId;
            $auditLine->adjustedCount = 0;
            
            $audit->lineItems[] = $auditLine;
         }
      }
      */
   }
}