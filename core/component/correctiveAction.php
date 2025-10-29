<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/inspection.php';
require_once ROOT.'/common/jobInfo.php';
require_once ROOT.'/common/userInfo.php';
require_once ROOT.'/core/common/correctiveActionDefs.php';
require_once ROOT.'/core/common/pptpDatabase.php';
require_once ROOT.'/core/component/action.php';
require_once ROOT.'/core/component/attachment.php';
require_once ROOT.'/core/component/customer.php';
require_once ROOT.'/core/component/shipment.php';
require_once ROOT.'/core/manager/jobManager.php';

class Correction
{
   public $description;   
   public $dueDate;
   public $employee;
   public $responsibleDetails;

   public function __construct()
   {
      $this->description = null;
      $this->dueDate = null;
      $this->employee = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->responsibleDetails = null;
   }
   
   public function initialize($row, $prefix)
   {
      $this->description = $row[$prefix."description"];
      $this->dueDate = $row[$prefix."dueDate"];  // Note: When reading from a MYSQL DATE field, Time::fromMySqlDate() is not required.
      $this->employee = intval($row[$prefix."employee"]);
      $this->responsibleDetails = $row[$prefix."responsibleDetails"];
   }
}

class Review
{
   public $reviewDate;
   public $reviewer;
   public $effectiveness;
   public $comments;
   
   public function __construct()
   {
      $this->reviewDate = null;
      $this->reviewer = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->effectiveness = null;
      $this->comments = null;
   }
   
   public function initialize($row)
   {
      $this->reviewDate = $row["reviewDate"];  // Note: When reading from a MYSQL DATE field, Time::fromMySqlDate() is not required.
      $this->reviewer = intval($row["reviewer"]);
      $this->effectiveness = $row["effectiveness"];
      $this->comments = $row["reviewComments"];
   }
}

class CorrectiveAction
{
   const UNKNOWN_CA_ID = 0;
   
   // Corrective action formatting constants.
   const CA_NUMBER_PREFIX = "CA";
   
   const NO_DISPOSITION = 0;
   
   public $correctiveActionId;
   public $occuranceDate;
   public $jobId;
   public $inspectionId;
   public $shipmentId;
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
   public $review;
   
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
      $this->jobId = JobInfo::UNKNOWN_JOB_ID;
      $this->inspectionId = Inspection::UNKNOWN_INSPECTION_ID;
      $this->shipmentId = Shipment::UNKNOWN_SHIPMENT_ID;
      $this->description = null;
      $this->employee = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->batchSize = 0;
      $this->dimensionalDefectCount = 0;
      $this->platingDefectCount = 0;
      $this->otherDefectCount = 0;
      $this->disposition = CorrectiveAction::NO_DISPOSITION;
      $this->rootCause = null;
      $this->dmrNumber = null;
      $this->initiator = CorrectiveActionInitiator::UNKNOWN;
      $this->location = CorrectiveActionLocation::UNKNOWN;
      
      $this->shortTermCorrection = new Correction();
      $this->longTermCorrection = new Correction();
      $this->review = new Review();
      
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
      $this->occuranceDate = $row["occuranceDate"];  // Note: When reading from a MYSQL DATE field, Time::fromMySqlDate() is not required.
      $this->jobId = intval($row["jobId"]);
      $this->inspectionId = intval($row["inspectionId"]);
      $this->shipmentId = intval($row["shipmentId"]);
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
      
      $this->shortTermCorrection->initialize($row, CorrectionType::getInputPrefix(CorrectionType::SHORT_TERM));
      $this->longTermCorrection->initialize($row, CorrectionType::getInputPrefix(CorrectionType::LONG_TERM));
      $this->review->initialize($row);
      
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
         $correctiveAction->customer = Customer::load($correctiveAction->getCustomerId());
         $correctiveAction->inspection = Customer::load($correctiveAction->inspectionId);
         $correctiveAction->shipment = Shipment::load($correctiveAction->shipmentId);
         
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
   
   public static function getLink($correctiveActionId)
   {
      $html = "";
      
      $correctiveAction = CorrectiveAction::load($correctiveActionId);
      if ($correctiveAction)
      {
         $label = $correctiveAction->getCorrectiveActionNumber();
         
         $html = "<a href=\"/correctiveAction/correctiveAction.php?correctiveActionId=$correctiveActionId\">$label</a>";
      }
      
      return ($html);
   }
   
   public function getTotalDefectCount()
   {
      return ($this->dimensionalDefectCount +
              $this->platingDefectCount +
              $this->otherDefectCount);
   }
   
   public function getCustomerId()
   {
      $customerId = Customer::UNKNOWN_CUSTOMER_ID;
      
      $jobNumber = JobInfo::UNKNOWN_JOB_NUMBER;

      // Job id specified explicitly.
      if ($this->jobInfo)
      {
         $jobNumber = $this->jobInfo->jobNumber;
      }
      // Job id implied in shipment.
      else if ($this->shipment)
      {
         $jobNumber = $this->shipment->jobNumber;
      }
      
      if ($jobNumber != JobInfo::UNKNOWN_JOB_NUMBER)
      {
         $customer = JobManager::getCustomerFromJobNumber($jobNumber);
         
         if ($customer)
         {
            $customerId = $customer->customerId;
         }
      }
      
      return ($customerId);
   }
   
   public function open($dateTime, $userId, $notes)
   {
      return ($this->addAction(CorrectiveActionStatus::OPEN, $dateTime, $userId, $notes));
   }
   
   public function approve($dateTime, $userId, $notes)
   {
      return ($this->addAction(CorrectiveActionStatus::APPROVED, $dateTime, $userId, $notes));
   }
   
   public function unapprove($dateTime, $userId, $notes)
   {
      return ($this->addAction(CorrectiveActionStatus::OPEN, $dateTime, $userId, $notes));
   }
   
   public function review($dateTime, $userId, $notes)
   {
      return ($this->addAction(CorrectiveActionStatus::REVIEWED, $dateTime, $userId, $notes));
   }
   
   public function close($dateTime, $userId, $notes)
   {
      return ($this->addAction(CorrectiveActionStatus::CLOSED, $dateTime, $userId, $notes));
   }
   
   public function addDisposition($disposition)
   {
      if (($disposition > Disposition::UNKNOWN) &&
          ($disposition < Disposition::LAST))
      {
         $this->disposition |= (1 << ($disposition - 1));
      }
   }
   
   public function hasDisposition($disposition)
   {
      $hasDisposition = false;
      
      if (($disposition > Disposition::UNKNOWN) &&
          ($disposition < Disposition::LAST))
      {
         $hasDisposition = ($this->disposition & (1 << ($disposition - 1)) > 0);
      }
      
      return ($hasDisposition);
   }
   
   public function getDispositions()
   {
      $dispositions = [];
      
      foreach (Disposition::$values as $disposition)
      {
         
      }
      
      
   }
   
   // **************************************************************************
   
   private function addAction($status, $dateTime, $userId, $notes, $attachment = null)
   {
      $action = new Action();
      $action->componentType = ComponentType::CORRECTIVE_ACTION;
      $action->componentId = $this->correctiveActionId;
      $action->status = $status;
      $action->dateTime = $dateTime;
      $action->userId = $userId;
      $action->notes = $notes;
      
      $success = Action::save($action);
      
      if ($success)
      {
         $this->actions[] = $action;
         
         $this->recalculateStatus();
         
         $success &= PPTPDatabaseAlt::getInstance()->updateCorrectiveActionStatus($this->correctiveActionId, $this->status);
      }
      
      return ($success);
   }
   
   private function recalculateStatus()
   {
      if (count($this->actions) > 0)
      {
         // The status is determined by the last quote action.
         $this->status = end($this->actions)->status;
      }
      else
      {
         $this->status = CorrectiveActionStatus::UNKNOWN;
      }
      
      return ($this->status);
   }
}