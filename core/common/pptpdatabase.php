<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/time.php';
require_once ROOT.'/core/common/database.php';
require_once ROOT.'/core/component/customer.php';

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
   
   public function getActivitiesForQuote($startDateTime, $endDateTime, $quoteId, $orderAscending = true)
   {
      $startDate = Time::toMySqlDate(Time::startOfDay($startDateTime));
      $endDate = Time::toMySqlDate(Time::endOfDay($endDateTime));
      
      $quoteIdClause = ($quoteId != Quote::UNKNOWN_QUOTE_ID) ? "object_0 = ?" : "TRUE";
            
      $order = $orderAscending ? "ASC" : "DESC";
      
      $statement = $this->pdo->prepare(
         "SELECT * FROM activity " .
         "WHERE (dateTime BETWEEN ? AND ?) AND " .
         "$quoteIdClause " .
         "ORDER BY dateTime $order;");
            
      $params = [$startDate, $endDate];
      if ($quoteId != Quote::UNKNOWN_QUOTE_ID)
      {
         $params[] = $quoteId;
      }
      
      $result = $statement->execute($params) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function addActivity($activity)
   {
      $statement = $this->pdo->prepare(
         "INSERT INTO activity " .
         "(dateTime, author, activityType, object_0, object_1, object_2) " .
         "VALUES (?, ?, ?, ?, ?)");
      
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
      
      if ($customerId == Customer::UNKNOWN_CUSTOMER_ID)
      {
         $result = $this->getContacts();
      }
      else
      {
         $statement = $this->pdo->prepare("SELECT * FROM customer WHERE customerId = ? ORDER BY lastName ASC;");
         
         $result = $statement->execute() ? $statement->fetchAll() : null;
      }
      
      return ($result);
   }
   
   public function addContact($contact)
   {
      $statement = $this->pdo->prepare(
         "INSERT INTO contact " .
         "(firstName, lastName, customerId, phone, email) " .
         "VALUES (?, ?, ?, ?, ?)");
      
      echo $statement->queryString;
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
   
   public function getQuotes($startDate, $endDate, $quoteStatuses)
   {
      // TODO: Date and status clauses.
      $statement = $this->pdo->prepare("SELECT * FROM quote ORDER BY quoteId ASC;");
      
      $result = $statement->execute() ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function addQuote($quote)
   {
      $statement = $this->pdo->prepare(
         "INSERT INTO quote " .
         "(quoteStatus, customerId, contactId, customerPartNumber, pptpPartNumber, quantity, unitPrice, costPerHour, additionalCharge, chargeCode, totalCost, leadTime) " .
         "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      
      $result = $statement->execute(
         [
            $quote->quoteStatus,
            $quote->customerId,
            $quote->contactId,
            $quote->customerPartNumber,
            $quote->pptpPartNumber,
            $quote->quantity,
            $quote->unitPrice,
            $quote->costPerHour,
            $quote->additionalCharge,
            $quote->chargeCode,
            $quote->totalCost,
            $quote->leadTime
         ]);
      
      return ($result);
   }
   
   public function updateQuote($quote)
   {
      $statement = $this->pdo->prepare(
         "UPDATE quote " .
         "SET quoteStatus = ?, customerId = ?, contactId = ?, customerPartNumber = ?, pptpPartNumber = ?, quantity = ?, unitPrice = ?, costPerHour = ?, additionalCharge = ?, chargeCode = ?, totalCost = ?, leadTime = ? " .
         "WHERE quoteId = ?");
      
      $result = $statement->execute(
         [
            $quote->quoteStatus,
            $quote->customerId,
            $quote->contactId,
            $quote->customerPartNumber,
            $quote->pptpPartNumber,
            $quote->quantity,
            $quote->unitPrice,
            $quote->costPerHour,
            $quote->additionalCharge,
            $quote->chargeCode,
            $quote->totalCost,
            $quote->leadTime,
            $quote->quoteId
         ]);
      
      return ($result);
   }
   
   public function deleteQuote($quoteId)
   {
      $statement = $this->pdo->prepare("DELETE FROM quote WHERE quoteId = ?");
      
      $result = $statement->execute([$quoteId]);
      
      // TODO: Delete actions
      
      return ($result);
   }
      
   // **************************************************************************
   
   private static $databaseInstance;
}