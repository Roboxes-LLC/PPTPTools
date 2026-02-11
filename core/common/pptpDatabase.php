<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/time.php';
require_once ROOT.'/core/common/database.php';

class PPTPDatabaseAlt extends PDODatabase
{
   public static function getInstance()
   {
      if (!PPTPDatabaseAlt::$databaseInstance)
      {
         self::$databaseInstance = new PPTPDatabaseAlt();
         
         self::$databaseInstance->connect();
      }
      
      return (self::$databaseInstance);
   }
   
   public function __construct()
   {
      global $DATABASE_TYPE, $SERVER, $USER, $PASSWORD, $DATABASE;
      
      parent::__construct($DATABASE_TYPE, $SERVER, $USER, $PASSWORD, $DATABASE);
   }   
   
   // **************************************************************************
   //                                Activity
   
   public function getActivity($activityId)
   {
      $statement = $this->pdo->prepare(
         "SELECT * FROM activity WHERE activityId = ?;");
      
      $result = $statement->execute([$activityId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getActivities($startDateTime, $endDateTime, $orderAscending = true)
   {
      $startDate = Time::toMySqlDate(Time::startOfDay($startDateTime));
      $endDate = Time::toMySqlDate(Time::endOfDay($endDateTime));
      
      $questionMarks = array();
      for ($i = 0; $i < count(ActivityType::$excludeActivities); $i++)
      {
         $questionMarks[] = "?";
      }
      $excludeClause = "activityType NOT IN (" . implode(", ", $questionMarks) . ") ";
      
      $order = $orderAscending ? "ASC" : "DESC";
      
      $statement = $this->pdo->prepare(
         "SELECT * FROM activity " .
         "WHERE (dateTime BETWEEN ? AND ?) AND " .
         $excludeClause .
         "ORDER BY dateTime $order;");
            
      $params = [$startDate, $endDate];
      $params = array_merge($params, ActivityType::$excludeActivities);
      
      $result = $statement->execute($params) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getActivitiesForUser($startDateTime, $endDateTime, $userId, $orderAscending = true)
   {
      $startDate = Time::toMySqlDate(Time::startOfDay($startDateTime));
      $endDate = Time::toMySqlDate(Time::endOfDay($endDateTime));
      
      $authorClause = ($userId != UserInfo::UNKNOWN_EMPLOYEE_NUMBER) ? "author = ?" : "TRUE";
      
      $order = $orderAscending ? "ASC" : "DESC";
      
      $statement = $this->pdo->prepare(
         "SELECT * FROM activity " .
         "WHERE (dateTime BETWEEN ? AND ?) AND " .
         "$authorClause " .
         "ORDER BY dateTime $order;");
            
      $params = [$startDate, $endDate];
      if ($userId != 0)
      {
         $params[] = $userId;
      }
      
      $result = $statement->execute($params) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getActivitiesForQuote($quoteId, $orderAscending = true)
   {
      $order = $orderAscending ? "ASC" : "DESC";
      
      $questionMarks = array();
      for ($i = 0; $i < count(ActivityType::$quoteActivites); $i++)
      {
         $questionMarks[] = "?";
      }
      $activityList = "(" . implode(", ", $questionMarks) . ")";
      
      $statement = $this->pdo->prepare(
         "SELECT * FROM activity " .
         "WHERE object_0 = ? AND activityType IN $activityList " .
         "ORDER BY dateTime $order;");
      
      $params = [$quoteId];
      $params = array_merge($params, ActivityType::$quoteActivites);
            
      $result = $statement->execute($params) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getActivitiesForCorrectiveAction($correctiveActionId, $orderAscending = true)
   {
      $order = $orderAscending ? "ASC" : "DESC";
      
      $questionMarks = array();
      for ($i = 0; $i < count(ActivityType::$correctiveActionActivites); $i++)
      {
         $questionMarks[] = "?";
      }
      $activityList = "(" . implode(", ", $questionMarks) . ")";
      
      $statement = $this->pdo->prepare(
         "SELECT * FROM activity " .
         "WHERE object_0 = ? AND activityType IN $activityList " .
         "ORDER BY dateTime $order;");
      
      $params = [$correctiveActionId];
      $params = array_merge($params, ActivityType::$correctiveActionActivites);
      
      $result = $statement->execute($params) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function addActivity($activity)
   {
      $statement = $this->pdo->prepare(
         "INSERT INTO activity " .
         "(dateTime, author, activityType, object_0, object_1, object_2) " .
         "VALUES (?, ?, ?, ?, ?, ?)");
      
      $result = $statement->execute(
         [
            Time::toMySqlDate($activity->dateTime),
            $activity->author,
            $activity->activityType,
            $activity->objects[0],
            $activity->objects[1],
            $activity->objects[2],
         ]);
      
      return ($result);
   }
   
   public function updateActivity($activity)
   {
      $statement = $this->pdo->prepare(
         "UPDATE activity " .
         "SET dateTime = ?, author = ?, activityType = ?, object_0 = ?, object_1 = ?, object_2 = ? " .
         "WHERE activityId = ?");
      
      $result = $statement->execute(
         [
            Time::toMySqlDate($activity->dateTime),
            $activity->author,
            $activity->activityType,
            $activity->objects[0],
            $activity->objects[1],
            $activity->objects[2],
            $activity->activityId
         ]);
      
      return ($result);
   }
   
   public function deleteActivity($activityId)
   {
      $statement = $this->pdo->prepare("DELETE FROM activity WHERE activityId = ?");
      
      $result = $statement->execute([$activityId]);
      
      return ($result);
   }   
   
   // **************************************************************************
   //                                  Company
   
   public function getCompany($companyId)
   {
      $statement = $this->pdo->prepare("SELECT * FROM company WHERE companyId = ?;");
      
      $result = $statement->execute([$companyId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   // **************************************************************************
   //                                   Contact
   
   public function getContact($contactId)
   {
      $statement = $this->pdo->prepare("SELECT * FROM contact WHERE contactId = ?;");
      
      $result = $statement->execute([$contactId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getContacts()
   {
      $statement = $this->pdo->prepare("SELECT * FROM contact ORDER BY lastName ASC;");
      
      $result = $statement->execute() ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getContactsForCustomer($customerId)
   {
      $result = null;
      
      $statement = $this->pdo->prepare("SELECT * FROM contact WHERE customerId = ? ORDER BY lastName ASC;");
      
      $result = $statement->execute([$customerId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function addContact($contact)
   {
      $statement = $this->pdo->prepare(
         "INSERT INTO contact " .
         "(firstName, lastName, customerId, phone, email) " .
         "VALUES (?, ?, ?, ?, ?)");
      
      $result = $statement->execute(
         [
            $contact->firstName,
            $contact->lastName,
            $contact->customerId,
            $contact->phone,
            $contact->email
         ]);
      
      return ($result);
   }
   
   public function updateContact($contact)
   {
      $statement = $this->pdo->prepare(
         "UPDATE contact " .
         "SET firstName = ?, lastName = ?, customerId = ?, phone = ?, email = ? " .
         "WHERE contactId = ?");
      
      $result = $statement->execute(
         [
            $contact->firstName,
            $contact->lastName,
            $contact->customerId,
            $contact->phone,
            $contact->email,
            $contact->contactId
         ]);
      
      $statement = $this->pdo->prepare(
         "UPDATE customer " .
         "SET primaryContactId = ? " .
         "WHERE primaryContactId = ?");
      
      $result &= $statement->execute(
         [
            Contact::UNKNOWN_CONTACT_ID,
            $contact->contactId
         ]);
      
      return ($result);
   }
   
   public function deleteContact($contactId)
   {
      $statement = $this->pdo->prepare("DELETE FROM contact WHERE contactId = ?");
      
      $result = $statement->execute([$contactId]);
      
      return ($result);
   } 

   // **************************************************************************
   //                                   Customer
   
   public function getCustomer($customerId)
   {
      $statement = $this->pdo->prepare("SELECT * FROM customer WHERE customerId = ?;");
      
      $result = $statement->execute([$customerId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getCustomers()
   {
      $statement = $this->pdo->prepare("SELECT * FROM customer ORDER BY customerName ASC;");
      
      $result = $statement->execute() ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function addCustomer($customer)
   {
      $statement = $this->pdo->prepare(
         "INSERT INTO customer " .
         "(customerName, addressLine1, addressLine2, city, state, zipcode, primaryContactId) " .
         "VALUES (?, ?, ?, ?, ?, ?, ?)");
      
      $result = $statement->execute(
         [
            $customer->customerName,
            $customer->address->addressLine1,
            $customer->address->addressLine2,
            $customer->address->city,
            $customer->address->state,
            $customer->address->zipcode,
            $customer->primaryContactId                  
         ]);
      
      return ($result);
   }
      
   public function updateCustomer($customer)
   {
      $statement = $this->pdo->prepare(
         "UPDATE customer " .
         "SET customerName = ?, addressLine1 = ?, addressLine2 = ?, city = ?, state = ?, zipcode = ?, primaryContactId = ? " .
         "WHERE customerId = ?");
      
      $result = $statement->execute(
         [
            $customer->customerName,
            $customer->address->addressLine1,
            $customer->address->addressLine2,
            $customer->address->city,
            $customer->address->state,
            $customer->address->zipcode,
            $customer->primaryContactId,
            $customer->customerId
         ]);
      
      return ($result);
   }
   
   public function deleteCustomer($customerId)
   {
      $statement = $this->pdo->prepare("DELETE FROM customer WHERE customerId = ?");
      
      $result = $statement->execute([$customerId]);
      
      return ($result);
   }
   
   // **************************************************************************
   //                                   Quote
   
   public function getQuote($quoteId)
   {
      $statement = $this->pdo->prepare("SELECT * FROM quote WHERE quoteId = ?;");
      
      $result = $statement->execute([$quoteId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getQuotes($startDate, $endDate)
   {
      // TODO: Date and status clauses.
      $statement = $this->pdo->prepare("SELECT * FROM quote ORDER BY quoteId ASC;");
      
      $result = $statement->execute() ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getQuotesByStatus($quoteStatuses)
   {
      $questionMarks = array();
      for ($i = 0; $i < count($quoteStatuses); $i++)
      {
         $questionMarks[] = "?";
      }
      $statusList = "(" . implode(", ", $questionMarks) . ")";
      
      $statement = $this->pdo->prepare(
         "SELECT * FROM quote " .
         "WHERE quoteStatus IN $statusList " .
         "ORDER BY quoteId ASC;");
      
      $params = $quoteStatuses;
      
      $result = $statement->execute($params) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function addQuote($quote)
   {
      $statement = $this->pdo->prepare(
         "INSERT INTO quote " .
         "(quoteStatus, customerId, contactId, customerPartNumber, pptpPartNumber, partDescription, quantity) " .
         "VALUES (?, ?, ?, ?, ?, ?, ?)");
      
      $result = $statement->execute(
         [
            $quote->quoteStatus,
            $quote->customerId,
            $quote->contactId,
            $quote->customerPartNumber,
            $quote->pptpPartNumber,
            $quote->partDescription,
            $quote->quantity,
         ]);
      
      return ($result);
   }
   
   public function updateQuote($quote)
   {
      $statement = $this->pdo->prepare(
         "UPDATE quote " .
         "SET quoteStatus = ?, customerId = ?, contactId = ?, customerPartNumber = ?, pptpPartNumber = ?, partDescription = ?, quantity = ? " .
         "WHERE quoteId = ?");
      
      $result = $statement->execute(
         [
            $quote->quoteStatus,
            $quote->customerId,
            $quote->contactId,
            $quote->customerPartNumber,
            $quote->pptpPartNumber,
            $quote->partDescription,
            $quote->quantity,
            $quote->quoteId,
         ]);
      
      return ($result);
   }
   
   public function updateQuoteStatus($quoteId, $quoteStatus)
   {
      $statement = $this->pdo->prepare(
         "UPDATE quote SET quoteStatus = ? WHERE quoteId = ?");
      
      $result = $statement->execute([$quoteStatus, $quoteId]);
      
      return ($result);
   }
   
   public function updateQuoteEmailNotes($quoteId, $emailNotes)
   {
      $statement = $this->pdo->prepare(
         "UPDATE quote SET emailNotes = ? WHERE quoteId = ?");
      
      $result = $statement->execute([$emailNotes, $quoteId]);
      
      return ($result);
   }
   
   public function deleteQuote($quoteId)
   {
      $statement = $this->pdo->prepare("DELETE FROM quote WHERE quoteId = ?");
      
      $result = $statement->execute([$quoteId]);
      
      $statement = $this->pdo->prepare("DELETE FROM estimate WHERE quoteId = ?");
      
      $result &= $statement->execute([$quoteId]);
      
      $statement = $this->pdo->prepare("DELETE FROM quoteaction WHERE quoteId = ?");
      
      $result &= $statement->execute([$quoteId]);
      
      return ($result);
   }
   
   // **************************************************************************
   //                             Quote Action
   
   public function getQuoteAction($quoteActionId)
   {
      $statement = $this->pdo->prepare(
         "SELECT * FROM quoteaction WHERE quoteActionId = ?;");
      
      $result = $statement->execute([$quoteActionId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getQuoteActions($quoteId)
   {
      $statement = $this->pdo->prepare(
         "SELECT * FROM quoteaction WHERE quoteId = ? ORDER BY dateTime ASC;");
      
      $result = $statement->execute([$quoteId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function addQuoteAction($quoteAction)
   {
      $dateTime = ($quoteAction->dateTime) ?
         Time::toMySqlDate($quoteAction->dateTime) :
         null;
      
      $statement = $this->pdo->prepare(
         "INSERT INTO quoteaction " .
         "(quoteId, quoteStatus, dateTime, userId, notes) " .
         "VALUES (?, ?, ?, ?, ?)");
      
      $result = $statement->execute(
         [
            $quoteAction->quoteId,
            $quoteAction->quoteStatus,
            $dateTime,
            $quoteAction->userId,
            $quoteAction->notes,
         ]);
      
      return ($result);
   }
   
   public function updateQuoteAction($quoteAction)
   {
      $dateTime = ($quoteAction->dateTime) ?
         Time::toMySqlDate($quoteAction->dateTime) :
         null;
      
      $statement = $this->pdo->prepare(
         "UPDATE quoteaction " .
         "SET quoteId = ?, quoteStatus= ?, dateTime = ?, userId = ?, notes = ? " .
         "WHERE quoteActionId = ?");
      
      $result = $statement->execute(
         [
            $quoteAction->quoteId,
            $quoteAction->quoteStatus,
            $dateTime,
            $quoteAction->userId,
            $quoteAction->notes,
            $quoteAction->quoteActionId
         ]);
      
      return ($result);
   }
   
   public function deleteQuoteAction($quoteActionId)
   {
      $statement = $this->pdo->prepare("DELETE FROM quoteaction WHERE quoteActionId = ?");
      
      $result = $statement->execute([$quoteActionId]);
      
      return ($result);
   }   
   
   // **************************************************************************
   //                             Quote Attachment
   
   public function getQuoteAttachment($attachmentId)
   {
      $statement = $this->pdo->prepare(
         "SELECT * FROM quoteattachment WHERE attachmentId = ?;");
      
      $result = $statement->execute([$attachmentId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getQuoteAttachments($quoteId)
   {
      $statement = $this->pdo->prepare(
         "SELECT * FROM quoteattachment WHERE quoteId = ? ORDER BY attachmentId ASC;");
      
      $result = $statement->execute([$quoteId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function addQuoteAttachment($quoteAttachment)
   {      
      $statement = $this->pdo->prepare(
         "INSERT INTO quoteattachment " .
         "(quoteId, filename, storedFilename, description) " .
         "VALUES (?, ?, ?, ?)");
      
      $result = $statement->execute(
         [
            $quoteAttachment->quoteId,
            $quoteAttachment->filename,
            $quoteAttachment->storedFilename,
            $quoteAttachment->description
         ]);
      
      return ($result);
   }
   
   public function updateQuoteAttachment($quoteAttachment)
   {
      $statement = $this->pdo->prepare(
         "UPDATE quoteattachment " .
         "SET quoteId = ?, filename = ?, storedFilename = ?, description = ? " .
         "WHERE attachmentId = ?");
      
      $result = $statement->execute(
         [
            $quoteAttachment->quoteId,
            $quoteAttachment->filename,
            $quoteAttachment->storedFilename,
            $quoteAttachment->storedFilename,
            $quoteAttachment->attachmentId
         ]);
      
      return ($result);
   }
   
   public function deleteQuoteAttachment($attachmentId)
   {
      $statement = $this->pdo->prepare("DELETE FROM quoteattachment WHERE attachmentId = ?");
      
      $result = $statement->execute([$attachmentId]);
      
      return ($result);
   }   
   
   // **************************************************************************
   //                                 Estimate

   public function estimateExists($quoteId, $estimateIndex)
   {
      return ($this->getEstimate($quoteId, $estimateIndex) != null);
   }
   
   public function getEstimate($quoteId, $estimateIndex)
   {
      $statement = $this->pdo->prepare("SELECT * FROM estimate WHERE quoteId = ? AND estimateIndex = ?;");
      
      $result = $statement->execute([$quoteId, $estimateIndex]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function addEstimate($estimate)
   {
      $statement = $this->pdo->prepare(
         "INSERT INTO estimate " .
         "(quoteId, estimateIndex, quantity, grossPiecesPerHour, netPiecesPerHour, unitPrice, costPerHour, markup, additionalCharge, chargeCode, leadTime, isSelected) " .
         "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      
      $result = $statement->execute(
         [
            $estimate->quoteId,
            $estimate->estimateIndex,
            $estimate->quantity,
            $estimate->grossPiecesPerHour,
            $estimate->netPiecesPerHour,
            $estimate->unitPrice,
            $estimate->costPerHour,
            $estimate->markup,
            $estimate->additionalCharge,
            $estimate->chargeCode,
            $estimate->leadTime,
            $estimate->isSelected ? 1 : 0,
         ]);
      
      return ($result);
   }
   
   public function updateEstimate($estimate)
   {
      $statement = $this->pdo->prepare(
         "UPDATE estimate " .
         "SET quantity = ?, grossPiecesPerHour = ?, netPiecesPerHour = ?, unitPrice = ?, costPerHour = ?, markup = ?, additionalCharge = ?, chargeCode = ?, leadTime = ?, isSelected = ? " .
         "WHERE quoteId = ? AND estimateIndex = ?");
      
      $result = $statement->execute(
         [
            $estimate->quantity,
            $estimate->grossPiecesPerHour,
            $estimate->netPiecesPerHour,
            $estimate->unitPrice,
            $estimate->costPerHour,
            $estimate->markup,
            $estimate->additionalCharge,
            $estimate->chargeCode,
            $estimate->leadTime,
            $estimate->isSelected ? 1 : 0,
            $estimate->quoteId,
            $estimate->estimateIndex
         ]);
      
      return ($result);
   }
      
   public function deleteEstimate($quoteId, $estimateIndex)
   {
      $statement = $this->pdo->prepare("DELETE FROM estimate WHERE quoteId = ? AND estimateIndex = ?");
      
      $result = $statement->execute([$quoteId, $estimateIndex]);
      
      return ($result);
   }
   
   // **************************************************************************
   //                                 Part
   
   public function getPart($partNumber, $useCustomerNumber)
   {
      $partNumberColumn = $useCustomerNumber ? "customerNumber" : "pptpNumber";
      
      $statement = $this->pdo->prepare("SELECT * from part WHERE $partNumberColumn = ? LIMIT 1");
      
      $result = $statement->execute([$partNumber]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getParts()
   {
      $statement = $this->pdo->prepare("SELECT * from part ORDER BY pptpNumber ASC");
      
      $result = $statement->execute() ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getPartsForCustomer($customerId)
   {
      $statement = $this->pdo->prepare("SELECT * from part WHERE customerId = ? ORDER BY pptpNumber ASC");
      
      $result = $statement->execute([$customerId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getCustomerPartNumber($pptpPartNumber)
   {
      $statement = $this->pdo->prepare("SELECT * from part WHERE pptpNumber = ? LIMIT 1");
      
      $result = $statement->execute([$pptpPartNumber]) ? $statement->fetchAll() : null;
      
      if ($result)
      {
         // Return the customerNumber from the first (and only) entry.
         $result = (count($result) > 0) ? $result[0]["customerNumber"] : null;
      }

      return ($result);
   }
   
   public function addPart($part)
   {
      $statement = $this->pdo->prepare(
         "INSERT INTO part " .
         "(pptpNumber, customerNumber, customerId, sampleWeight, unitPrice, firstPartTemplateId, lineTemplateId, qcpTemplateId, inProcessTemplateId, finalTemplateId, customerPrint) " .
         "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      
      $result = $statement->execute(
         [
            $part->pptpNumber,
            $part->customerNumber,
            $part->customerId,
            $part->sampleWeight,
            $part->unitPrice,
            $part->inspectionTemplateIds[InspectionType::FIRST_PART],
            $part->inspectionTemplateIds[InspectionType::LINE],
            $part->inspectionTemplateIds[InspectionType::QCP],
            $part->inspectionTemplateIds[InspectionType::IN_PROCESS],
            $part->inspectionTemplateIds[InspectionType::FINAL],
            $part->customerPrint
         ]);
      
      return ($result);
   }
   
   public function updatePart($part)
   {
      $statement = $this->pdo->prepare(
         "UPDATE part " .
         "SET customerNumber = ?, customerId = ?, sampleWeight = ?, unitPrice = ?, firstPartTemplateId = ?, lineTemplateId = ?, qcpTemplateId = ?, inProcessTemplateId = ?, finalTemplateId = ?, customerPrint = ? " .
         "WHERE pptpNumber = ?");
      
      $result = $statement->execute(
         [
            $part->customerNumber,
            $part->customerId,
            $part->sampleWeight,
            $part->unitPrice,
            $part->inspectionTemplateIds[InspectionType::FIRST_PART],
            $part->inspectionTemplateIds[InspectionType::LINE],
            $part->inspectionTemplateIds[InspectionType::QCP],
            $part->inspectionTemplateIds[InspectionType::IN_PROCESS],
            $part->inspectionTemplateIds[InspectionType::FINAL],
            $part->customerPrint,
            $part->pptpNumber
         ]);
      
      return ($result);
   }
   
   public function deletePart($pptpNumber)
   {
      $statement = $this->pdo->prepare("DELETE FROM part WHERE pptpNumber = ?");
      
      $result = $statement->execute([$pptpNumber]);
      
      // TODO: Delete jobs, inspections, time cards, etc.
      
      return ($result);
   }
   
   // **************************************************************************
   //                             Schedule Entry

   public function getScheduleEntry($entryId)
   {
      $statement = $this->pdo->prepare(
            "SELECT * FROM schedule WHERE entryId = ?;");
      
      $result = $statement->execute([$entryId]) ? $statement->fetchAll() : null;
   
      return ($result);
   }

   public function getSchedule($mfgDate)
   {
      $mfgDate = $mfgDate ? Time::toMySqlDate(Time::startOfDay($mfgDate)) : null;
      
      $statement = $this->pdo->prepare(
         "SELECT * FROM schedule WHERE ((endDate IS NULL) AND (? >= startDate)) OR (? BETWEEN startDate AND endDate);");
      
      $result = $statement->execute([$mfgDate, $mfgDate]) ? $statement->fetchAll() : null;
            
      return ($result);
   }
   
   public function getScheduleForJob($jobId, $mfgDate)
   {
      $mfgDate = $mfgDate ? Time::toMySqlDate(Time::startOfDay($mfgDate)) : null;
      
      $statement = $this->pdo->prepare(
         "SELECT * FROM schedule WHERE jobId = ? AND ? BETWEEN startDate AND endDate;");
      
      $result = $statement->execute([$jobId, $mfgDate]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function addScheduleEntry($scheduleEntry)
   {
      $startDate = $scheduleEntry->startDate ? Time::toMySqlDate(Time::startOfDay($scheduleEntry->startDate)) : null;
      $endDate = $scheduleEntry->endDate ? Time::toMySqlDate(Time::startOfDay($scheduleEntry->endDate)) : null;
      
      $statement = $this->pdo->prepare(
         "INSERT INTO schedule " .
         "(jobId, employeeNumber, startDate, endDate) " .
         "VALUES (?, ?, ?, ?)");
      
      $result = $statement->execute(
         [
            $scheduleEntry->jobId,
            $scheduleEntry->employeeNumber,
            $startDate,
            $endDate
         ]);
      
      return ($result);
   }
   
   public function updateScheduleEntry($scheduleEntry)
   {
      $startDate = $scheduleEntry->startDate ? Time::toMySqlDate(Time::startOfDay($scheduleEntry->startDate)) : null;
      $endDate = $scheduleEntry->endDate ? Time::toMySqlDate(Time::startOfDay($scheduleEntry->endDate)) : null;
      
      $statement = $this->pdo->prepare(
         "UPDATE schedule " .
         "SET jobId = ?, employeeNumber = ?, startDate = ?, endDate = ? " .
         "WHERE entryId = ?");
      
      $result = $statement->execute(
         [
            $scheduleEntry->jobId,
            $scheduleEntry->employeeNumber,
            $startDate,
            $endDate,
            $scheduleEntry->entryId
         ]);
      
      return ($result);
   }
   
   public function deleteScheduleEntry($entryId)
   {
      $statement = $this->pdo->prepare("DELETE FROM schedule WHERE entryId = ?");
      
      $result = $statement->execute([$entryId]);
      
      return ($result);
   }
   
   // **************************************************************************
   //                             App Notification
   
   public function getAppNotification($notificationId)
   {
      $statement = $this->pdo->prepare("SELECT * FROM appnotification WHERE notificationId = ?;");
      
      $result = $statement->execute([$notificationId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getAppNotificationsForUser($employeeNumber, $startDateTime, $endDateTime)
   {
      $startDate = Time::toMySqlDate(Time::startOfDay($startDateTime));
      $endDate = Time::toMySqlDate(Time::endOfDay($endDateTime));
      
      $statement = $this->pdo->prepare(
         "SELECT appnotification.* FROM appnotification " .
         "INNER JOIN appnotification_user ON appnotification.notificationId = appnotification_user.notificationId " .
         "WHERE appnotification_user.employeeNumber = ? AND (appnotification.dateTime BETWEEN ? AND ?)");
      
      $result = $statement->execute([$employeeNumber, $startDate, $endDate]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getUnacknowledgedAppNotificationsForUser($employeeNumber)
   {
      $statement = $this->pdo->prepare(
         "SELECT appnotification.* FROM appnotification " .
         "INNER JOIN appnotification_user ON appnotification.notificationId = appnotification_user.notificationId " .
         "WHERE appnotification_user.employeeNumber = ? AND appnotification_user.acknowledged = FALSE");
      
      $result = $statement->execute([$employeeNumber]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function addAppNotification($appNotification)
   {
      $dateTime = $appNotification->dateTime ? Time::toMySqlDate($appNotification->dateTime) : null;
      
      $statement = $this->pdo->prepare(
         "INSERT INTO appnotification " .
         "(dateTime, author, priority, subject, message) " .
         "VALUES (?, ?, ?, ?, ?)");
      
      $result = $statement->execute(
         [
            $dateTime,
            $appNotification->author,
            $appNotification->priority,
            $appNotification->subject,
            $appNotification->message
         ]);
      
      return ($result);
   }
   
   public function updateAppNotification($appNotification)
   {
      $dateTime = $appNotification->dateTime ? Time::toMySqlDate($appNotification->dateTime) : null;
      
      $statement = $this->pdo->prepare(
         "UPDATE appnotification " .
         "SET dateTime = ?, author = ?, priority = ?, subject = ?, message = ? " .
         "WHERE notificationId = ?");
      
      $result = $statement->execute(
         [
            $dateTime,
            $appNotification->author,
            $appNotification->priority,
            $appNotification->subject,
            $appNotification->message,
            $appNotification->notificationId
         ]);
      
      return ($result);
   }
   
   public function deleteAppNotification($notificationId)
   {
      $statement = $this->pdo->prepare("DELETE FROM appnotification WHERE notificationId = ?");
      
      $result = $statement->execute([$notificationId]);
      
      return ($result);
   }   
   
   public function addUserToAppNotification($employeeNumber, $notificationId)
   {
      $statement = $this->pdo->prepare(
         "INSERT INTO appnotification_user " .
         "(notificationId, employeeNumber) " .
         "VALUES (?, ?)");
      
      $result = $statement->execute(
         [
            $notificationId,
            $employeeNumber
         ]);
   
      return ($result);
   }
   
   public function removeUserFromAppNotification($employeeNumber, $notificationId)
   {
      $statement = $this->pdo->prepare(
         "DELETE FROM appnotification_user WHERE notificationId = ? AND employeeNumber = ?");
      
      $result = $statement->execute([$notificationId, $employeeNumber]);
      
      return ($result);
   }
   
   public function acknowledgeAppNotification($notificationId, $employeeNumber, $acknowledged)
   {
      $dateTime = $acknowledged ? Time::toMySqlDate(Time::now()) : null;
      
      $statement = $this->pdo->prepare(
         "UPDATE appnotification_user " .
         "SET acknowledged = ?, dateTime = ? " .
         "WHERE notificationId = ? AND employeeNumber = ?");
      
      $result = $statement->execute(
         [
            $acknowledged ? 1 : 0,
            $dateTime,
            $notificationId,
            $employeeNumber,
         ]);
      
      return ($result);
   }
   
   public function getAppNotificationAcknowledgement($notificationId, $employeeNumber)
   {
      $statement = $this->pdo->prepare("SELECT * FROM appnotification_user WHERE notificationId = ? && employeeNumber = ?;");
      
      $result = $statement->execute([$notificationId, $employeeNumber]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   // **************************************************************************
   //                                 Shipment
   
   public function getShipment($shipmentId)
   {
      $statement = $this->pdo->prepare(
         "SELECT * FROM shipment WHERE shipmentId = ?;");
      
      $result = $statement->execute([$shipmentId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getShipmentFromInspection($inspectionId)
   {
      $statement = $this->pdo->prepare(
         "SELECT * FROM shipment WHERE inspectionId = ?;");
      
      $result = $statement->execute([$inspectionId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getShipments($shipmentLocation, $startDate, $endDate)
   {
      $startDate = $startDate ? Time::toMySqlDate(Time::startOfDay($startDate)) : null;
      $endDate = $endDate ? Time::toMySqlDate(Time::endOfDay($endDate)) : null;
      
      $dateClause = ($startDate && $endDate) ? "(dateTime BETWEEN '$startDate' AND '$endDate')" : "TRUE";
      
      $locations = ($shipmentLocation == ShipmentLocation::ALL_ACTIVE) ?
                      ShipmentLocation::$activeLocations :
                      [$shipmentLocation];
      
      $questionMarks = array();
      for ($i = 0; $i < count($locations); $i++)
      {
         $questionMarks[] = "?";
      }
      $locationList = "(" . implode(", ", $questionMarks) . ")";
      
      $statement = $this->pdo->prepare(
         "SELECT * FROM shipment WHERE location IN $locationList AND $dateClause ORDER BY shipmentId ASC;");
      
      $result = $statement->execute($locations) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getChildShipments($shipmentId)
   {
      $statement = $this->pdo->prepare(
         "SELECT * FROM shipment WHERE parentShipmentId = ? ORDER BY shipmentId ASC;");
      
      $result = $statement->execute([$shipmentId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getShipmentsByPart($location, $partNumber)
   {
      $locations = ($location == ShipmentLocation::ALL_ACTIVE) ?
                      ShipmentLocation::$activeLocations :
                      [$location];
      
      $questionMarks = array();
      for ($i = 0; $i < count($locations); $i++)
      {
         $questionMarks[] = "?";
      }
      $locationList = "(" . implode(", ", $questionMarks) . ")";
    
      $statement = $this->pdo->prepare(
         "SELECT * from shipment " .
         "WHERE location IN $locationList AND " .
         "EXISTS (SELECT 1 FROM job WHERE job.partNumber = ? AND job.jobNumber = shipment.jobNumber) " .
         "ORDER BY shipmentId ASC;");
            
      $params = $locations;
      $params[] = $partNumber;
      
      $result = $statement->execute($params) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function addShipment($shipment)
   {
      $dateTime = $shipment->dateTime ? Time::toMySqlDate($shipment->dateTime) : null;
      $vendorShippedDate = $shipment->vendorShippedDate ? Time::toMySqlDate($shipment->vendorShippedDate) : null;
      $customerShippedDate = $shipment->customerShippedDate ? Time::toMySqlDate($shipment->customerShippedDate) : null;
      
      $statement = $this->pdo->prepare(
         "INSERT INTO shipment " .
         "(parentShipmentId, childIndex, jobNumber, dateTime, author, inspectionId, quantity, vendorPackingList, customerPackingList, location, vendorShippedDate, customerShippedDate) " .
         "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      
      $result = $statement->execute(
         [
            $shipment->parentShipmentId,
            $shipment->childIndex,
            $shipment->jobNumber,
            $dateTime,      
            $shipment->author,
            $shipment->inspectionId,
            $shipment->quantity,
            $shipment->vendorPackingList,
            $shipment->customerPackingList,
            $shipment->location,
            $vendorShippedDate,
            $customerShippedDate
         ]);
      
      return ($result);
   }
   
   public function updateShipment($shipment)
   {
      $dateTime = $shipment->dateTime ? Time::toMySqlDate($shipment->dateTime) : null;
      $vendorShippedDate = $shipment->vendorShippedDate ? Time::toMySqlDate($shipment->vendorShippedDate) : null;
      $customerShippedDate = $shipment->customerShippedDate ? Time::toMySqlDate($shipment->customerShippedDate) : null;
      
      $statement = $this->pdo->prepare(
         "UPDATE shipment " .
         "SET parentShipmentId = ?, childIndex = ?, jobNumber = ?, dateTime = ?, author = ?, inspectionId = ?, quantity = ?, vendorPackingList = ?, customerPackingList = ?, location = ?, vendorShippedDate = ?, customerShippedDate = ? " .
         "WHERE shipmentId = ?");
      
      $result = $statement->execute(
         [
            $shipment->parentShipmentId,
            $shipment->childIndex,
            $shipment->jobNumber,
            $dateTime,      
            $shipment->author,
            $shipment->inspectionId,
            $shipment->quantity,
            $shipment->vendorPackingList,
            $shipment->customerPackingList,
            $shipment->location,
            $vendorShippedDate,
            $customerShippedDate,
            $shipment->shipmentId
         ]);
      
      return ($result);
   }
   
   public function deleteShipment($shipmentId)
   {
      $statement = $this->pdo->prepare("DELETE FROM shipment WHERE shipmentId = ?");
      
      $result = $statement->execute([$shipmentId]);
      
      return ($result);
   }
   
   // **************************************************************************
   //                                 Sales order
   
   public function getSalesOrder($shipmentId)
   {
      $statement = $this->pdo->prepare(
         "SELECT * FROM salesorder WHERE salesOrderId = ?;");
      
      $result = $statement->execute([$shipmentId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getSalesOrders($dateType, $startDate, $endDate, $allActive)
   {
      $dateField = "orderDate";
      switch ($dateType)
      {
         case FilterDateType::ENTRY_DATE:
         {
            $dateField = "dateTime";
            break;
         }
         
         case FilterDateType::ORDERED_DATE:
         {
            $dateField = "orderDate";
            break;
         }
         
         case FilterDateType::DUE_DATE:
         {
            $dateField = "dueDate";
            break;
         }
         
         default:
         {
            break;
         }
      }
      
      $startDate = $startDate ? Time::toMySqlDate(Time::startOfDay($startDate)) : null;
      $endDate = $endDate ? Time::toMySqlDate(Time::endOfDay($endDate)) : null;
      
      $dateClause = ($startDate && $endDate && !$allActive) ? "($dateField BETWEEN '$startDate' AND '$endDate')" : "TRUE";
      $statusClause = (!$allActive) ? "TRUE" : "(orderStatus != " . SalesOrderStatus::SHIPPED . ")";
            
      $statement = $this->pdo->prepare(
         "SELECT * FROM salesorder WHERE $dateClause AND $statusClause ORDER BY salesOrderId ASC;");
      
      $result = $statement->execute() ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function addSalesOrder($salesOrder)
   {
      $dateTime = $salesOrder->dateTime ? Time::toMySqlDate($salesOrder->dateTime) : null;
      $orderDate = $salesOrder->orderDate ? Time::toMySqlDate($salesOrder->orderDate) : null;
      $dueDate = $salesOrder->dueDate ? Time::toMySqlDate($salesOrder->dueDate) : null;
      
      $statement = $this->pdo->prepare(
         "INSERT INTO salesorder " .
         "(author, dateTime, orderNumber, customerId, customerPartNumber, poNumber, orderDate, quantity, unitPrice, dueDate, orderStatus, comments, packingList) " .
         "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      
      $result = $statement->execute(
         [
            $salesOrder->author,
            $dateTime,
            $salesOrder->orderNumber,
            $salesOrder->customerId,
            $salesOrder->customerPartNumber,
            $salesOrder->poNumber,
            $orderDate,
            $salesOrder->quantity,
            $salesOrder->unitPrice,
            $dueDate,
            $salesOrder->orderStatus,
            $salesOrder->comments,
            $salesOrder->packingList
         ]);
      
      return ($result);
   }
   
   public function updateSalesOrder($salesOrder)
   {
      $dateTime = $salesOrder->dateTime ? Time::toMySqlDate($salesOrder->dateTime) : null;
      $orderDate = $salesOrder->orderDate ? Time::toMySqlDate($salesOrder->orderDate) : null;
      $dueDate = $salesOrder->dueDate ? Time::toMySqlDate($salesOrder->dueDate) : null;
      
      $statement = $this->pdo->prepare(
         "UPDATE salesorder " .
         "SET author = ?, dateTime = ?, orderNumber = ?, customerId = ?, customerPartNumber = ?, poNumber = ?, orderDate = ?, quantity = ?, unitPrice = ?, dueDate = ?, orderStatus = ?, comments = ?, packingList = ? " .
         "WHERE salesOrderId = ?");

      $result = $statement->execute(
         [
            $salesOrder->author,
            $dateTime,
            $salesOrder->orderNumber,
            $salesOrder->customerId,
            $salesOrder->customerPartNumber,
            $salesOrder->poNumber,
            $orderDate,
            $salesOrder->quantity,
            $salesOrder->unitPrice,
            $dueDate,
            $salesOrder->orderStatus,
            $salesOrder->comments,
            $salesOrder->packingList,
            $salesOrder->salesOrderId
         ]);
      
      return ($result);
   }
   
   public function deleteSalesOrder($salesOrderId)
   {
      $statement = $this->pdo->prepare("DELETE FROM salesorder WHERE salesOrderId = ?");
      
      $result = $statement->execute([$salesOrderId]);
      
      return ($result);
   }
   
   // **************************************************************************
   //                              Corrective Action
   
   public function getCorrectiveAction($correctiveActionId)
   {
      $statement = $this->pdo->prepare("SELECT * FROM correctiveaction WHERE correctiveActionId = ?;");
      
      $result = $statement->execute([$correctiveActionId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getCorrectiveActions($dateType, $startDate, $endDate, $allActive)
   {
      $dateField = "occuranceDate";
      switch ($dateType)
      {
         case FilterDateType::OCCURANCE_DATE:
         {
            $dateField = "occuranceDate";
            break;
         }

         // TODO: Add other date filters.
         default:
         {
            break;
         }
      }
      
      $startDate = $startDate ? Time::dateTimeObject($startDate)->format(Time::MYSQL_DATE_FORMAT) : null;
      $endDate = $endDate ? Time::dateTimeObject($endDate)->format(Time::MYSQL_DATE_FORMAT) : null;
      
      $dateClause = ($startDate && $endDate && !$allActive) ? "($dateField BETWEEN '$startDate' AND '$endDate')" : "TRUE";
      $statusClause = (!$allActive) ? "TRUE" : "(status != " . CorrectiveActionStatus::CLOSED . ")";
      
      $statement = $this->pdo->prepare("SELECT * FROM correctiveaction WHERE $dateClause AND $statusClause ORDER BY occuranceDate ASC;");
      
      $result = $statement->execute() ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getCorrectiveActionsForInspection($inspectionId)
   {
      $statement = $this->pdo->prepare("SELECT * FROM correctiveaction WHERE inspectionId = ? ORDER BY occuranceDate ASC;");
      
      $result = $statement->execute([$inspectionId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
      
   public function addCorrectiveAction($correctiveAction)
   {
      $occuranceDate = $correctiveAction->occuranceDate ? 
                          Time::toMySqlDate($correctiveAction->occuranceDate) : 
                          null;

      $statement = $this->pdo->prepare(
         "INSERT INTO correctiveaction " .
         "(occuranceDate, jobId, inspectionId, shipmentId, description, employee, batchSize, dimensionalDefectCount, platingDefectCount, otherDefectCount, disposition, rootCause, dmrNumber, initiator, location, status) " .
         "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
   
      $result = $statement->execute(
         [
            $occuranceDate,
            $correctiveAction->jobId,
            $correctiveAction->inspectionId,
            $correctiveAction->shipmentId,
            $correctiveAction->description,
            $correctiveAction->employee,
            $correctiveAction->batchSize,
            $correctiveAction->dimensionalDefectCount,
            $correctiveAction->platingDefectCount,
            $correctiveAction->otherDefectCount,
            $correctiveAction->disposition,
            $correctiveAction->rootCause,
            $correctiveAction->dmrNumber,
            $correctiveAction->initiator,
            $correctiveAction->location,
            $correctiveAction->status
         ]);
      
      return ($result);
   }
   
   public function updateCorrectiveAction($correctiveAction)
   {
      $occuranceDate = $correctiveAction->occuranceDate ?
                          Time::toMySqlDate($correctiveAction->occuranceDate) :
                          null;
      
     $shortTermDueDate = $correctiveAction->shortTermCorrection->dueDate ?
        Time::toMySqlDate($correctiveAction->shortTermCorrection->dueDate) :
        null;
     
     $longTermDueDate = $correctiveAction->longTermCorrection->dueDate ?
        Time::toMySqlDate($correctiveAction->longTermCorrection->dueDate) :
        null;
     
     $reviewDate = $correctiveAction->review->reviewDate ?
                      Time::toMySqlDate($correctiveAction->review->reviewDate) :
                      null;
      
      $statement = $this->pdo->prepare(
         "UPDATE correctiveaction " .
          "SET occuranceDate = ?, jobId = ?, inspectionId = ?, shipmentId = ?, description = ?, employee = ?, batchSize = ?, dimensionalDefectCount = ?, platingDefectCount = ?, otherDefectCount = ?, disposition = ?, rootCause = ?, dmrNumber = ?, initiator = ?, location = ?, status = ?, " .
          "shortTerm_description = ?, shortTerm_dueDate = ?, shortTerm_employee = ?, shortTerm_responsibleDetails = ?, " .
          "longTerm_description = ?, longTerm_dueDate = ?, longTerm_employee = ?, longTerm_responsibleDetails = ?, " .
          "reviewDate = ?, reviewer = ?, effectiveness = ?, reviewComments = ? " .
          "WHERE correctiveActionId = ?");
            
      $result = $statement->execute(
         [
            $occuranceDate,
            $correctiveAction->jobId,
            $correctiveAction->inspectionId,
            $correctiveAction->shipmentId,
            $correctiveAction->description,
            $correctiveAction->employee,
            $correctiveAction->batchSize,
            $correctiveAction->dimensionalDefectCount,
            $correctiveAction->platingDefectCount,
            $correctiveAction->otherDefectCount,
            $correctiveAction->disposition,
            $correctiveAction->rootCause,
            $correctiveAction->dmrNumber,
            $correctiveAction->initiator,
            $correctiveAction->location,
            $correctiveAction->status,
            $correctiveAction->shortTermCorrection->description,
            $shortTermDueDate,
            $correctiveAction->shortTermCorrection->employee,
            $correctiveAction->shortTermCorrection->responsibleDetails,
            $correctiveAction->longTermCorrection->description,
            $longTermDueDate,
            $correctiveAction->longTermCorrection->employee,
            $correctiveAction->longTermCorrection->responsibleDetails,
            $reviewDate,
            $correctiveAction->review->reviewer,
            $correctiveAction->review->effectiveness,
            $correctiveAction->review->comments,
            $correctiveAction->correctiveActionId
         ]);
                          
      return ($result);
   }
   
   public function updateCorrectiveActionStatus($correctiveActionId, $status)
   {
      $statement = $this->pdo->prepare(
         "UPDATE correctiveaction SET status = ? WHERE correctiveActionId = ?");
      
      $result = $statement->execute([$status, $correctiveActionId]);
      
      return ($result);
   }
   
   public function deleteCorrectiveAction($correctiveActionId)
   {
      $statement = $this->pdo->prepare("DELETE FROM correctiveaction WHERE correctiveActionId = ?");
      
      $result = $statement->execute([$correctiveActionId]);
      
      $statement = $this->pdo->prepare("DELETE FROM action WHERE componentType = ? AND componentId = ?");
      
      $result &= $statement->execute([ComponentType::CORRECTIVE_ACTION, $correctiveActionId]);
      
      return ($result);
   }
   
   // **************************************************************************
   //                                   Action
   
   public function getAction($actionId)
   {
      $statement = $this->pdo->prepare(
         "SELECT * FROM action WHERE actionId = ?;");
      
      $result = $statement->execute([$actionId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getActions($componentType, $componentId)
   {
      $statement = $this->pdo->prepare(
         "SELECT * FROM action WHERE componentType = ? AND componentId = ? ORDER BY dateTime ASC;");
      
      $result = $statement->execute([$componentType, $componentId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function addAction($action)
   {
      $dateTime = ($action->dateTime) ?
      Time::toMySqlDate($action->dateTime) :
                     null;
      
      $statement = $this->pdo->prepare(
         "INSERT INTO action " .
         "(componentType, componentId, status, dateTime, userId, notes) " .
         "VALUES (?, ?, ?, ?, ?, ?)");
      
      $result = $statement->execute(
         [
            $action->componentType,
            $action->componentId,
            $action->status,
            $dateTime,
            $action->userId,
            $action->notes,
         ]);
      
      return ($result);
   }
   
   public function updateComponentAction($table, $action)
   {
      $dateTime = ($action->dateTime) ?
                     Time::toMySqlDate($action->dateTime) :
                     null;
      
      $statement = $this->pdo->prepare(
         "UPDATE action " .
         "SET compnentType = ?, componentId = ?, status= ?, dateTime = ?, userId = ?, notes = ? " .
         "WHERE actionId = ?");
      
      $result = $statement->execute(
         [
            $action->componentType,
            $action->componentId,
            $action->status,
            $dateTime,
            $action->userId,
            $action->notes,
            $action->actionId
         ]);
      
      return ($result);
   }
   
   public function deleteAction($actionId)
   {
      $statement = $this->pdo->prepare("DELETE FROM action WHERE actionId = ?");
      
      $result = $statement->execute([$table, $actionId]);
      
      return ($result);
   }
   
   // **************************************************************************
   //                                  Attachment
   
   public function getAttachment($attachmentId)
   {
      $statement = $this->pdo->prepare(
         "SELECT * FROM attachment WHERE attachmentId = ?;");
      
      $result = $statement->execute([$attachmentId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getAttachments($componentType, $componentId)
   {
      $statement = $this->pdo->prepare(
         "SELECT * FROM attachment WHERE componentType = ? AND componentId = ? ORDER BY attachmentId ASC;");
      
      $result = $statement->execute([$componentType, $componentId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function addAttachment($attachment)
   {
      $statement = $this->pdo->prepare(
         "INSERT INTO attachment " .
         "(componentType, componentId, filename, storedFilename, description) " .
         "VALUES (?, ?, ?, ?, ?)");
      
      $result = $statement->execute(
         [
            $attachment->componentType,
            $attachment->componentId,
            $attachment->filename,
            $attachment->storedFilename,
            $attachment->description
         ]);
      
      return ($result);
   }
   
   public function updateAttachment($attachment)
   {
      $statement = $this->pdo->prepare(
         "UPDATE attachment " .
         "SET componentType = ?, componentId = ?, filename = ?, storedFilename = ?, description = ? " .
         "WHERE attachmentId = ?");
      
      $result = $statement->execute(
         [
            $attachment->componentType,
            $attachment->componentId,
            $attachment->filename,
            $attachment->storedFilename,
            $attachment->storedFilename,
            $attachment->attachmentId
         ]);
      
      return ($result);
   }
   
   public function deleteAttachment($attachmentId)
   {
      $statement = $this->pdo->prepare("DELETE FROM attachment WHERE attachmentId = ?");
      
      $result = $statement->execute([$attachmentId]);
      
      return ($result);
   }
   
   // **************************************************************************
   //                                Audit
   
   public function getAudit($auditId)
   {
      $statement = $this->pdo->prepare(
         "SELECT * FROM audit WHERE auditId = ?;");
      
      $result = $statement->execute([$auditId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getAudits($startDateTime, $endDateTime)
   {
      // Truncate to simple dates.
      $startDate = Time::dateTimeObject($startDateTime)->format("Y-m-d");
      $endDate = Time::dateTimeObject($endDateTime)->format("Y-m-d");
      
      $statement = $this->pdo->prepare(
         "SELECT * FROM audit " .
         "WHERE (scheduled BETWEEN ? AND ?) " .
         "ORDER BY scheduled ASC;");
      
      $params = [$startDate, $endDate];
      
      $result = $statement->execute($params) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getAuditsByStatus($auditStatuses)
   {
      $questionMarks = array();
      for ($i = 0; $i < count($auditStatuses); $i++)
      {
         $questionMarks[] = "?";
      }
      $statusList = "(" . implode(", ", $questionMarks) . ")";
      
      $statement = $this->pdo->prepare(
         "SELECT * FROM audit " .
         "WHERE status IN $statusList " .
         "ORDER BY scheduled ASC;");
      
      $params = [];
      $params = array_merge($params, $auditStatuses);
      
      $result = $statement->execute($params) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function addAudit($audit)
   {
      $createdDateTime = $audit->created ? Time::toMySqlDate($audit->created) : null;
      $scheduledDateTime = $audit->scheduled ? Time::toMySqlDate($audit->scheduled) : null;
      
      $statement = $this->pdo->prepare(
         "INSERT INTO audit " .
         "(auditName, created, author, scheduled, assigned, location, partNumber, isAdHoc, notes, status) " .
         "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      
      $result = $statement->execute(
         [
            $audit->auditName,
            $createdDateTime,
            $audit->author,
            $scheduledDateTime,
            $audit->assigned,
            $audit->location,
            $audit->partNumber,
            $audit->isAdHoc ? 1 : 0,
            $audit->notes,
            $audit->status
         ]);
      
      return ($result);
   }
   
   public function updateAudit($audit)
   {
      $createdDateTime = $audit->created ? Time::toMySqlDate($audit->created) : null;
      $scheduledDateTime = $audit->scheduled ? Time::toMySqlDate($audit->scheduled) : null;
      
      $statement = $this->pdo->prepare(
         "UPDATE audit " .
         "SET auditName = ?, created = ?, author = ?, scheduled = ?, assigned = ?, location = ?, partNumber = ?, isAdHoc = ?, notes = ?, status = ? " .
         "WHERE auditId = ?");
      
      $result = $statement->execute(
         [
            $audit->auditName,
            $createdDateTime,
            $audit->author,
            $scheduledDateTime,
            $audit->assigned,
            $audit->location,
            $audit->partNumber,
            $audit->isAdHoc ? 1 : 0,
            $audit->notes,
            $audit->status,
            $audit->auditId
         ]);
      
      return ($result);
   }
   
   public function deleteAudit($auditId)
   {
      $statement = $this->pdo->prepare("DELETE FROM audit WHERE auditId = ?");
      
      $result = $statement->execute([$auditId]);
      
      // Delete audit lines associated with this audit.
      $statement = $this->pdo->prepare("DELETE FROM auditline WHERE auditId = ?");
      
      $result &= $statement->execute([$auditId]);
      
      return ($result);
   }
   
   // **************************************************************************
   //                                Audit Line
   
   public function getAuditLine($auditLineId)
   {
      $statement = $this->pdo->prepare(
         "SELECT * FROM auditline WHERE auditLineId = ?;");
      
      $result = $statement->execute([$auditLineId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getAuditLines($auditId)
   {
      $statement = $this->pdo->prepare(
         "SELECT * FROM auditline WHERE auditId = ? ORDER BY auditLineId ASC;");
      
      $result = $statement->execute([$auditId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function addAuditLine($auditLine)
   {
      $statement = $this->pdo->prepare(
         "INSERT INTO auditline " .
         "(auditId, shipmentId, confirmed, confirmedBy, recordedCount, adjustedCount, adjustedLocation) " .
         "VALUES (?, ?, ?, ?, ?, ?, ?)");
      
      $result = $statement->execute(
         [
            $auditLine->auditId,
            $auditLine->shipmentId,
            $auditLine->confirmed ? 1 : 0,
            $auditLine->confirmedBy,
            $auditLine->recordedCount,
            $auditLine->adjustedCount,
            $auditLine->adjustedLocation,
         ]);
      
      return ($result);
   }
   
   public function updateAuditLine($auditLine)
   {
      $statement = $this->pdo->prepare(
         "UPDATE auditline " .
         "SET shipmentId = ?, confirmed = ?, confirmedBy = ?, recordedCount =?, adjustedCount = ?, adjustedLocation = ? " .
         "WHERE auditLineId = ?");
      
      $result = $statement->execute(
         [
            $auditLine->shipmentId,
            $auditLine->confirmed ? 1 : 0,
            $auditLine->confirmedBy,
            $auditLine->recordedCount,
            $auditLine->adjustedCount,
            $auditLine->adjustedLocation,
            $auditLine->auditLineId,
         ]);
      
      return ($result);
   }
   
   public function deleteAuditLine($auditLineId)
   {
      $statement = $this->pdo->prepare("DELETE FROM auditline WHERE auditLineId = ?");
      
      $result = $statement->execute([$auditLineId]);
      
      return ($result);
   }
   
   // **************************************************************************
   //                                Prospira Doc
   
   public function getProspiraDoc($docId)
   {
      $statement = $this->pdo->prepare(
         "SELECT * FROM prospiradoc WHERE docId = ?;");
      
      $result = $statement->execute([$docId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getProspiraDocs($startDate, $endDate)
   {
      $startDate = $startDate ? Time::toMySqlDate(Time::startOfDay($startDate)) : null;
      $endDate = $endDate ? Time::toMySqlDate(Time::endOfDay($endDate)) : null;
      
      $statement = $this->pdo->prepare(
         "SELECT * FROM prospiradoc " .
         "INNER JOIN shipment ON shipment.shipmentId = prospiradoc.shipmentId " .
         "WHERE (shipment.dateTime BETWEEN ? AND ?) " .
         "ORDER BY docId ASC;");
      
      $result = $statement->execute([$startDate, $endDate]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function addUpdateProspiraDoc($prospiraDoc)
   {
      $time = $prospiraDoc->time ? Time::toMySqlDate($prospiraDoc->time) : null;
      
      $statement = $this->pdo->prepare(
            "INSERT INTO prospiradoc (docId, shipmentId, clockNumber, serialNumber) " .
            "VALUES (?, ?, ?, ?)" .
            "ON DUPLICATE KEY UPDATE shipmentId = ?, clockNumber = ?, serialNumber = ?");
      
      $result = $statement->execute(
         [
            // Add
            $prospiraDoc->docId,
            $prospiraDoc->shipmentId,
            $prospiraDoc->clockNumber,
            $prospiraDoc->serialNumber,
            // Update
            $prospiraDoc->shipmentId,
            $prospiraDoc->clockNumber,
            $prospiraDoc->serialNumber
         ]);
      
      return ($result);
   }
   
   public function deleteProspiraDoc($docId)
   {
      $statement = $this->pdo->prepare("DELETE FROM prospiradoc WHERE docId = ?");
      
      $result = $statement->execute([$docId]);
      
      return ($result);
   }

   // **************************************************************************
   //                           Maintenance Ticket
   
   public function getMaintenanceTicket($ticketId)
   {
      $statement = $this->pdo->prepare(
         "SELECT * FROM maintenanceticket WHERE ticketId = ?;");
      
      $result = $statement->execute([$ticketId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getMaintenanceTickets($dateType, $startDate, $endDate, $allActive)
   {
      $dateField = "posted";
      switch ($dateType)
      {
         case FilterDateType::OCCURANCE_DATE:
         {
            $dateField = "posted";
            break;
         }

         // TODO: Add other date filters.
         default:
         {
            break;
         }
      }
      
      $startDate = $startDate ? Time::dateTimeObject($startDate)->format(Time::MYSQL_DATE_FORMAT) : null;
      $endDate = $endDate ? Time::dateTimeObject($endDate)->format(Time::MYSQL_DATE_FORMAT) : null;
      
      $dateClause = ($startDate && $endDate && !$allActive) ? "($dateField BETWEEN '$startDate' AND '$endDate')" : "TRUE";
      $statusClause = (!$allActive) ? "TRUE" : "(status != " . MaintenanceTicketStatus::CLOSED . ")";
      
      $statement = $this->pdo->prepare("SELECT * FROM maintenanceticket WHERE $dateClause AND $statusClause ORDER BY $dateField ASC;");
      
      $result = $statement->execute() ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function addMaintenanceTicket($maintenanceTicket)
   {
      $postedDateTime = $maintenanceTicket->posted ? Time::toMySqlDate($maintenanceTicket->posted) : null;

      $statement = $this->pdo->prepare(
         "INSERT INTO maintenanceticket " .
         "(author, posted, wcNumber, jobNumber, machineState, description, details, assigned, status) " .
         "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

      $result = $statement->execute(
         [
            $maintenanceTicket->author,
            $postedDateTime,
            $maintenanceTicket->wcNumber,
            $maintenanceTicket->jobNumber,
            $maintenanceTicket->machineState,
            $maintenanceTicket->description,
            $maintenanceTicket->details,
            $maintenanceTicket->assigned,
            $maintenanceTicket->status
         ]);
      
      return ($result);
   }
   
   public function updateMaintenanceTicket($maintenanceTicket)
   {
      $postedDateTime = $maintenanceTicket->posted ? Time::toMySqlDate($maintenanceTicket->posted) : null;

      $statement = $this->pdo->prepare(
         "UPDATE maintenanceticket " .
         "SET author = ?, posted = ?, wcNumber = ?, jobNumber =?, machineState = ?, description = ?, details = ?, assigned = ?, status = ? " .
         "WHERE ticketId = ?");
      
      $result = $statement->execute(
         [
            $maintenanceTicket->author,
            $postedDateTime,
            $maintenanceTicket->wcNumber,
            $maintenanceTicket->jobNumber,
            $maintenanceTicket->machineState,
            $maintenanceTicket->description,
            $maintenanceTicket->details,
            $maintenanceTicket->assigned,
            $maintenanceTicket->status,
            $maintenanceTicket->ticketId
         ]);
      
      return ($result);
   }
   
   public function deleteMaintenanceTicket($ticketId)
   {
      $statement = $this->pdo->prepare("DELETE FROM maintenanceticket WHERE ticketId = ?");
      
      $result = $statement->execute([$ticketId]);
      
      return ($result);
   }
      
   // **************************************************************************
   
   private static $databaseInstance;
}
