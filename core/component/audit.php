<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/userInfo.php';
require_once ROOT.'/core/common/pptpDatabase.php';
require_once ROOT.'/core/component/auditLine.php';
require_once ROOT.'/core/component/shipment.php';
require_once ROOT.'/core/manager/shipmentManager.php';

abstract class AuditStatus
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const SCHEDULED = AuditStatus::FIRST;
   const IN_PROGRESS = 2;
   const COMPLETE = 3;
   const CANCELLED = 4;
   const APPLIED = 5;
   const LAST = 6;
   const COUNT = AuditStatus::LAST - AuditStatus::FIRST;
   
   public static $values = array(AuditStatus::SCHEDULED, AuditStatus::IN_PROGRESS, AuditStatus::COMPLETE,  AuditStatus::CANCELLED,  AuditStatus::APPLIED);
   
   public static $activeStatuses = array(AuditStatus::SCHEDULED, AuditStatus::IN_PROGRESS, AuditStatus::COMPLETE);
      
   public static function getLabel($category)
   {
      $labels = array("", "Scheduled", "In Progress", "Complete", "Cancelled", "Applied");
      
      return ($labels[$category]);
   }
   
   public static function getJavascript($enumName)
   {
      // Note: Keep synced with enum.
      $varNames = array("UNKNOWN", "SCHEDULED", "IN_PROGRESS", "COMPLETE", "CANCELLED", "APPLIED");
      
      $html = "$enumName = {";
      
      $html .= "{$varNames[AuditStatus::UNKNOWN]}: " . AuditStatus::UNKNOWN . ", ";
      
      foreach (AuditStatus::$values as $auditStatus)
      {
         $html .= "{$varNames[$auditStatus]}: $auditStatus";
         $html .= ($auditStatus < (PoStatus::LAST - 1) ? ", " : "");
      }
      
      $html .= "};";
      
      return ($html);
   } 
}

class Audit
{
   const UNKNOWN_AUDIT_ID = 0;
   
   // Constants for use in calls to Audit::findLineItem().
   const FIND_BY_AUDIT_LINE_ID = 1;
   const FIND_BY_SHIPMENT_ID = 2;
   
   public $auditId;
   public $auditName;
   public $siteId;
   public $created;
   public $author; 
   public $scheduled;
   public $assigned;
   public $location;
   public $partNumber;
   public $isAdHoc;
   public $notes;
   public $status;
   
   public $lineItems;
   
   public function __construct()
   {
      $this->auditId = Audit::UNKNOWN_AUDIT_ID;
      $this->auditName = null;
      $this->created = null;
      $this->author = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->scheduled = null;
      $this->assigned = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->location =ShipmentLocation::UNKNOWN;
      $this->partNumber = null;
      $this->isAdHoc = false;
      $this->notes = null;
      $this->status = AuditStatus::UNKNOWN;
      
      $this->lineItems = array(); 
   }
   
   // **************************************************************************
   // Component interface
   
   public static function load($auditId)
   {
      $audit = null;
      
      $result = PPTPDatabaseAlt::getInstance()->getAudit($auditId);
      
      if ($result && ($row = $result[0]))
      {
         $audit = new Audit();
         
         $audit->initialize($row);
         
         $audit->lineItems = Audit::getLineItems($audit->auditId); 
      }
      
      return ($audit);
   }
   
   public static function save($audit)
   {
      $success = false;
      
      if ($audit->auditId == Audit::UNKNOWN_AUDIT_ID)
      {
         $success = PPTPDatabaseAlt::getInstance()->addAudit($audit);
         
         $audit->auditId = intval(PPTPDatabaseAlt::getInstance()->lastInsertId());
      }
      else
      {
         $success = PPTPDatabaseAlt::getInstance()->updateAudit($audit);
      }
      
      $prevLineItems = Audit::getLineItems($audit->auditId);
      
      // Deleted lines.
      foreach ($prevLineItems as $auditLine)
      {
         if (!$audit->findLineItem($auditLine->auditLineId, Audit::FIND_BY_AUDIT_LINE_ID))
         {
            AuditLine::delete($auditLine->auditLineId);
         }
      }
      
      // New/updated lines.
      foreach ($audit->lineItems as $auditLine)
      {
         $auditLine->auditId = $audit->auditId;
         $success &= AuditLine::save($auditLine);
      }
      
      return ($success);
   }
   
   public static function delete($auditId)
   {
      return (PPTPDatabaseAlt::getInstance()->deleteAudit($auditId));
   }
   
   // **************************************************************************
   
   public function initialize($row)
   {
      $this->auditId = intval($row['auditId']);
      $this->auditName = $row['auditName'];    
      $this->created = $row['created'] ? Time::fromMySqlDate($row['created']) : null;
      $this->author = intval($row['author']);
      $this->scheduled = $row['scheduled'];  // Store dates without any conversion.
      $this->assigned = intval($row['assigned']);      
      $this->location = intval($row['location']);
      $this->partNumber = $row['partNumber'];
      $this->isAdHoc = filter_var($row['isAdHoc'], FILTER_VALIDATE_BOOLEAN);
      $this->notes = $row['notes'];
      $this->status = intval($row['status']);
   }
   
   public function isAdhocAudit()
   {
      return ($this->isAdHoc);
   }
   
   public function getProgress()
   {
      $progress = 0; // percent
      
      $lineItemCount = count($this->lineItems);
      $auditedCount = 0;
      
      foreach ($this->lineItems as $auditLine)
      {
         if (!is_null($auditLine->adjustedCount))
         {
            $auditedCount++;
         }
      }
      
      if ($lineItemCount > 0)
      {
         $progress = round(($auditedCount / $lineItemCount) * 100);
      }
      
      return ($progress);
   }
   
   public function createLineItems()
   {
      if ((!$this->isAdHoc) &&
          ($this->location != ShipmentLocation::UNKNOWN))
      {
         $shipments = [];
         if ($this->partNumber != null)
         {
            $shipments = ShipmentManager::getShipmentsByPart($this->location, $this->partNumber);
         }
         else
         {
            $shipments = ShipmentManager::getShipments($this->location);
         }
         
         foreach ($shipments as $shipment)
         {
            $auditLine = new AuditLine();
            $auditLine->auditId = $this->auditId;
            $auditLine->shipmentId = $shipment->shipmentId;
            $auditLine->adjustedCount = null;
            
            $this->lineItems[] = $auditLine;
         }
      }
   }
   
   public static function getLineItems($auditId)
   {
      $lineItems = array();
      
      if ($auditId != Audit::UNKNOWN_AUDIT_ID)
      {
         $result = PPTPDatabaseAlt::getInstance()->getAuditLines($auditId);
         
         foreach ($result as $row)
         {
            $auditLine = new AuditLine();
            $auditLine->initialize($row);
            $lineItems[] = $auditLine;
         }
      }
      
      return ($lineItems);
   }
   
   public function &findLineItem($id, $searchMethod = Audit::FIND_BY_AUDIT_LINE_ID)
   {
      $foundIt = null;
      
      foreach ($this->lineItems as $auditLine)
      {
         if ((($searchMethod == Audit::FIND_BY_AUDIT_LINE_ID) &&
              ($auditLine->auditLineId == $id)) ||
             (($searchMethod == Audit::FIND_BY_SHIPMENT_ID) &&
              ($auditLine->shipmentId == $id)))
         {
            $foundIt = $auditLine;
            break;
         }
      }
      
      return ($foundIt);
   }
   
   public static function getLink($auditId)
   {
      $html = "";
      
      $audit = Audit::load($auditId);
      if ($audit)
      {
         $label = $audit->auditName;
         
         $html = "<a href=\"audit.php?auditId=$auditId\">$label</a>";
      }
      
      return ($html);
   }
}

?>