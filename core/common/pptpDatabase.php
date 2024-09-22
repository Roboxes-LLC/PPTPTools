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
   
   public function saveCustomerPartNumber($pptpPartNumber, $customerPartNumber)
   {
      $statement = $this->pdo->prepare("INSERT INTO part (pptpNumber, customerNumber) VALUES(?, ?) ON DUPLICATE KEY UPDATE pptpNumber = ?, customerNumber = ?");
      
      $result = $statement->execute([$pptpPartNumber, $customerPartNumber, $pptpPartNumber, $customerPartNumber]);
      
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
   
   public function getShipments()
   {
      $statement = $this->pdo->prepare(
         "SELECT * FROM shipment ORDER BY shipmentId ASC;");
      
      $result = $statement->execute() ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function addShipment($shipment)
   {
      $dateTime = $shipment->dateTime ? Time::toMySqlDate($shipment->dateTime) : null;
      
      $statement = $this->pdo->prepare(
         "INSERT INTO shipment " .
         "(jobId, dateTime, author, inspectionId, quantity, packingListNumber) " .
         "VALUES (?, ?, ?, ?, ?, ?)");
      
      $result = $statement->execute(
         [
            $shipment->jobId,
            $dateTime,      
            $shipment->author,
            $shipment->inspectionId,
            $shipment->quantity,
            $shipment->packingListNumber
         ]);
      
      return ($result);
   }
   
   public function updateShipment($shipment)
   {
      $dateTime = $shipment->dateTime ? Time::toMySqlDate($shipment->dateTime) : null;
      
      $statement = $this->pdo->prepare(
         "UPDATE shipment " .
         "SET jobId = ?, $dateTime = ?, author = ?, inspectionId = ?, quantity = ?, packingListNumber = ? " .
         "WHERE shipmentId = ?");
      
      $result = $statement->execute(
         [
            $shipment->jobId,
            $dateTime,      
            $shipment->author,
            $shipment->inspectionId,
            $shipment->quantity,
            $shipment->packingListNumber,
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
   
   private static $databaseInstance;
}