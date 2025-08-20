<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/inspection.php';
require_once ROOT.'/common/jobInfo.php';
require_once ROOT.'/common/userInfo.php';
require_once ROOT.'/core/common/correctiveActionDefs.php';
require_once ROOT.'/core/common/pptpDatabase.php';
require_once ROOT.'/core/common/shipmentDefs.php';
require_once ROOT.'/core/component/action.php';
require_once ROOT.'/core/component/attachment.php';
require_once ROOT.'/core/component/customer.php';
require_once ROOT.'/core/component/shipment.php';

class CorrectiveAction
{
   const UNKNOWN_CA_ID = 0;
   
   // Corrective action formatting constants.
   const CA_NUMBER_PREFIX = "CA";
   
   public $correctiveActionId;
   public $occuranceDate;
   public $customerId;
   public $jobId;
   public $inspectionId;
   public $description;
   public $employee;  // TODO: More than one.
   public $batchSize;
   public $dimensionalDefectCount;
   public $platingDefectCount;
   public $otherDefectCount;
   public $disposition;
   public $rootCause;
   public $dmrNumber;
   public $initiator;
   public $location;
   
   public $shortTermCorrection;
   public $longTermCorrection;
   
   public $status;
   
   public $actions;
   public $attachments;
   
   
   public $jobInfo;
   public $customer;
   public $inspection;
   public $shipment;
   
   public function __construct()
   {
      $this->correctiveActionId = CorrectiveAction::UNKNOWN_CA_ID;
      $this->occuranceDate = null;
      $this->customerId = Customer::UNKNOWN_CUSTOMER_ID;
      $this->jobId = JobInfo::UNKNOWN_JOB_ID;
      $this->inspectionId = Inspection::UNKNOWN_INSPECTION_ID;
      $this->shipmentId = Shipment::UNKNOWN_SHIPMENT_ID;
      $this->description = null;
      $this->employee = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->batchSize = 0;
      $this->dimensionalDefectCount = 0;
      $this->platingDefectCount = 0;
      $this->otherDefectCount = 0;
      $this->disposition = Disposition::UNKNOWN;
      $this->rootCause = null;
      $this->dmrNumber = null;
      $this->initiator = CorrectiveActionInitiator::UNKNOWN;
      $this->location = ShipmentLocation::UNKNOWN;
      $this->shortTermCorrection = null;
      $this->longTermCorrection = null;
      
      $this->status = CorrectiveActionStatus::UNKNOWN;
      
      $this->actions = array();
      $this->attachments = array();
      
      $this->jobInfo = null;
      $this->customer = null;
      $this->inspection = null;
      $this->shipment = null;
   }
   
   public function initialize($row)
   {
      $this->correctiveActionId = $row["correctiveActionId"];
      $this->occuranceDate = $row["occuranceDate"] ?
                                Time::fromMySqlDate($row["occuranceDate"]) :
                                null;
      $this->customerId = intval($row["customerId"]);
      $this->jobId = intval($row["jobId"]);
      $this->inspectionId = intval($row["inspectionId"]);
      $this->inspectionId = intval($row["inspectionId"]);
      $this->description = $row["description"];
      $this->employee = intval($row["employee"]);
      $this->batchSize = intval($row["batchSize"]);
      $this->dimensionalDefectCount = intval($row["dimensionalDefectCount"]);
      $this->platingDefectCount = intval($row["platingDefectCount"]);
      $this->otherDefectCount = intval($row["otherDefectCount"]);
      $this->disposition = intval($row["disposition"]);
      $this->rootCause = $row["rootCause"];
      $this->dmrNumber = $row["dmrNumber"];
      $this->initiator = intval($row["initiator"]);
      $this->location = intval($row["location"]);
      $this->shortTermCorrection = $row["shortTermCorrection"];
      $this->longTermCorrection = $row["longTermCorrection"];
      
      $this->status = intval($row["status"]);
   }
   
   // **************************************************************************
   // Component interface
   
   public static function load($correctiveActionId)
   {
      $correctiveAction = null;
      
      $result = PPTPDatabaseAlt::getInstance()->getCorrectiveAction($correctiveActionId);
      
      if ($result && ($row = $result[0]))
      {
         $correctiveAction = new CorrectiveAction();
         
         $correctiveAction->initialize($row);
         
         $correctiveAction->jobInfo = JobInfo::load($correctiveAction->jobId);
         $correctiveAction->customer = Customer::load($correctiveAction->customerId);
         $correctiveAction->inspection = Customer::load($correctiveAction->inspectionId);
         $correctiveAction->shipment = Customer::load($correctiveAction->shipmentId);
         
         $correctiveAction->actions = CorrectiveAction::getActions($correctiveAction->correctiveActionId);
         $correctiveAction->attachments = CorrectiveAction::getAttachments($correctiveAction->correctiveActionId);
      }
      
      return ($correctiveAction);
   }
   
   public static function save($correctiveAction)
   {
      $success = false;
      
      if ($correctiveAction->correctiveActionId == CorrectiveAction::UNKNOWN_CA_ID)
      {
         $success = PPTPDatabaseAlt::getInstance()->addCorrectiveAction($correctiveAction);
         
         $correctiveAction->correctiveActionId = intval(PPTPDatabaseAlt::getInstance()->lastInsertId());
      }
      else
      {
         $success = PPTPDatabaseAlt::getInstance()->updateCorrectiveAction($correctiveAction);
      }
      
      return ($success);
   }
   
   public static function delete($correctiveActionId)
   {
      return (PPTPDatabaseAlt::getInstance()->deleteCorrectiveAction($correctiveActionId));
   }
   
   public static function getActions($correctiveActionId)
   {
      $actions = array();
      
      if ($correctiveActionId != CorrectiveAction::UNKNOWN_CA_ID)
      {
         $result = PPTPDatabaseAlt::getInstance()->getActions(ComponentType::CORRECTIVE_ACTION, $correctiveActionId);
         
         foreach ($result as $row)
         {
            $action = new Action();
            $action->initialize($row);
            $actions[] = $action;
         }
      }
      
      return ($actions);
   }
   
   public static function getAttachments($correctiveActionId)
   {
      $attachments = array();
      
      if ($correctiveActionId != CorrectiveAction::UNKNOWN_CA_ID)
      {
         $result = PPTPDatabaseAlt::getInstance()->getAttachments(ComponentType::CORRECTIVE_ACTION, $correctiveActionId);
         
         foreach ($result as $row)
         {
            $attachment = new Attachment();
            $attachment->initialize($row);
            $attachments[] = $attachment;
         }
      }
      
      return ($attachments);
   }
   
   public function getCorrectiveActionNumber()
   {
      return (sprintf('%s%05d', CorrectiveAction::CA_NUMBER_PREFIX, $this->correctiveActionId));
   }
   
   public function getTotalDefectCount()
   {
      return ($this->dimensionalDefectCount +
              $this->platingDefectCount +
              $this->otherDefectCount);
   }
   
   public function create()
   {
      // TODO: 
   }
}