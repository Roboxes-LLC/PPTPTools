<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/pptpDatabase.php';
require_once ROOT.'/core/component/audit.php';
require_once ROOT.'/core/component/shipment.php';

class AuditLine
{
   const UNKNOWN_AUDIT_LINE_ID = 0;
   
   public $auditLineId;
   public $auditId;
   public $shipmentId;
   public $confirmed;
   public $recordedCount;
   public $adjustedCount;
   
   public function __construct()
   {
      $this->auditLineId = AuditLine::UNKNOWN_AUDIT_LINE_ID;
      $this->auditId = Audit::UNKNOWN_AUDIT_ID;
      $this->shipmentId = Shipment::UNKNOWN_SHIPMENT_ID;
      $this->confirmed = false;
      $this->recordedCount = null;
      $this->adjustedCount = null;
   }
   
   // **************************************************************************
   // Component interface
   
   public static function load($auditLineId)
   {
      $auditLine = null;
      
      $result = PPTPDatabaseAlt::getInstance()->getAuditLine($auditLineId);
      
      if ($result && ($row = $result[0]))
      {
         $auditLine = new AuditLine();
         
         $auditLine->initialize($row);
      }
      
      return ($auditLine);
   }
   
   public static function save($auditLine)
   {
      $success = false;
      
      if ($auditLine->auditLineId == AuditLine::UNKNOWN_AUDIT_LINE_ID)
      {
         $success = PPTPDatabaseAlt::getInstance()->addAuditLine($auditLine);
      }
      else
      {
         $success = PPTPDatabaseAlt::getInstance()->updateAuditLine($auditLine);
      }
      
      return ($success);
   }
   
   public static function delete($auditLine)
   {
      return (PPTPDatabaseAlt::getInstance()->deleteAuditLine($auditLine));
   }
   
   // **************************************************************************
   
   public function initialize($row)
   {
      $this->auditLineId = intval($row['auditLineId']);
      $this->auditId = intval($row['auditId']);
      $this->shipmentId = intval($row['shipmentId']);
      $this->confirmed = filter_var($row['confirmed'], FILTER_VALIDATE_BOOLEAN);
      $this->recordedCount = !is_null($row['recordedCount']) ? intval($row['recordedCount']) : null;
      $this->adjustedCount = !is_null($row['adjustedCount']) ? intval($row['adjustedCount']) : null;
   }
}

?>