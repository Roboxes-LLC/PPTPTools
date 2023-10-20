<?php

require_once 'databaseKey.php';
require_once 'jobInfo.php';
require_once 'time.php';

interface Database
{
   public function connect();

   public function disconnect();

   public function isConnected();

   public function query(
      $query);
}

class MySqlDatabase implements Database
{
   function __construct(
      $server,
      $user,
      $password,
      $database)
   {
      $this->server = $server;
      $this->user = $user;
      $this->password = $password;
      $this->database = $database;
   }

   public function connect()
   {
      // Create connection
      $this->connection = new mysqli($this->server, $this->user, $this->password, $this->database);

      // Check connection
      if ($this->connection->connect_error)
      {
         // TODO?
      }
      else
      {
         $this->isConnected = true;
      }
   }

   public function disconnect()
   {
      if ($this->isConnected())
      {
         $this->connection->close();
      }
   }

   public function isConnected()
   {
      return ($this->isConnected);
   }

   public function query(
      $query)
   {
      $result = NULL;

      if ($this->isConnected())
      {
         $result = $this->connection->query($query);
      }

      return ($result);
   }
   
   public static function countResults($result)
   {
      return (mysqli_num_rows($result));
   }
   
   public function rowsAffected()
   {
      return(mysqli_affected_rows($this->connection));
   }
   
   public function lastInsertId()
   {
      return (mysqli_insert_id($this->connection));
   }
   
   public function lastQuery()
   {
      return ($this->connection->last_query());
   }
   
   protected function getConnection()
   {
      return ($this->connection);
   }

   private $server = "";

   private $user = "";

   private $password = "";

   private $database = "";

   private $connection;

   private $isConnected = false;
}

class PPTPDatabase extends MySqlDatabase
{
   public static function getInstance()
   {
      if (!PPTPDatabase::$databaseInstance)
      {
         self::$databaseInstance = new PPTPDatabase();
         
         self::$databaseInstance->connect();
      }
      
      return (self::$databaseInstance);
   }
   
   public function __construct()
   {
      global $SERVER, $USER, $PASSWORD, $DATABASE;
      
      parent::__construct($SERVER, $USER, $PASSWORD, $DATABASE);
   }
   
   // **************************************************************************
   //                             Operators
   // **************************************************************************
   
   public function getOperators()
   {
      $result = $this->query("SELECT * FROM operator ORDER BY LastName ASC");

      return ($result);
   }

   public function getOperator(
      $employeeNumber)
   {
      $operator = NULL;

      $result = $this->query("SELECT * FROM operator WHERE EmployeeNumber=" . $employeeNumber . ";");

      if ($result && ($row = $result->fetch_assoc()))
      {
         $operator = $row;
      }

      return ($operator);
   }
   
   // **************************************************************************
   //                              Work Centers
   // **************************************************************************

   public function getWorkCenters()
   {
      $result = $this->query("SELECT * FROM workcenter ORDER BY wcNumber ASC");

      return ($result);
   }
   
   public function getActiveWorkCenters()
   {
      $active = JobStatus::ACTIVE;

      $query = "SELECT DISTINCT workcenter.wcNumber FROM workcenter INNER JOIN job ON job.wcNumber = workcenter.wcNumber WHERE job.status = $active ORDER BY workcenter.wcNumber ASC;";

      $result = $this->query($query);

      return ($result);
   }
   
   public function getWorkCentersForJob($jobNumber)
   {
      $query = "SELECT DISTINCT wcNumber FROM job WHERE jobNumber = \"$jobNumber\" ORDER BY wcNumber ASC;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   // **************************************************************************
   //                               Equipmenet
   // **************************************************************************

   public function getEquipments()  // Bad grammar, but whatever.
   {
      $result = $this->query("SELECT * FROM equipment ORDER BY equipmentId ASC");

      return ($result);
   }
   
   public function getEquipment($equipmentId)
   {
      $result = $this->query("SELECT * FROM equipment WHERE equipmentId = $equipmentId;");

      return ($result);
   }
   
   // **************************************************************************
   //                                Time Cards
   // **************************************************************************
   
   public function getTimeCard(
      $timeCardId)
   {
      $query = "SELECT * FROM timecard WHERE timeCardId = $timeCardId";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function matchTimeCard(
      $jobId,
      $employeeNumber,
      $manufactureDate)
   {
      $startDate = Time::startOfDay($manufactureDate);
      $endDate = Time::endOfDay($manufactureDate);
      
      $query = "SELECT * FROM timecard WHERE jobId = $jobId AND employeeNumber = $employeeNumber AND manufactureDate BETWEEN '" . Time::toMySqlDate($startDate) . "' AND '" . Time::toMySqlDate($endDate) . "';";

      $result = $this->query($query);
      
      return ($result);
   }

   public function getTimeCards(
      $employeeNumber,
      $startDate,
      $endDate,
      $useMfgDate = false)
   {
      $result = null;
      
      $dateField = ($useMfgDate ? "manufactureDate" : "dateTime");
      
      if ($employeeNumber == UserInfo::UNKNOWN_EMPLOYEE_NUMBER)
      {
         $query = "SELECT * FROM timecard WHERE $dateField BETWEEN '" . Time::toMySqlDate($startDate) . "' AND '" . Time::toMySqlDate($endDate) . "' ORDER BY $dateField DESC, timeCardId DESC;";

         $result = $this->query($query);
      }
      else
      {
         $query = "SELECT * FROM timecard WHERE employeeNumber=" . $employeeNumber . " AND $dateField BETWEEN '" . Time::toMySqlDate($startDate) . "' AND '" . Time::toMySqlDate($endDate) . "' ORDER BY $dateField DESC, timeCardId DESC;";
         
         $result = $this->query($query);
      }

      return ($result);
   }

   public function newTimeCard(
      $timeCardInfo)
   {
      $date = Time::toMySqlDate($timeCardInfo->dateTime);
      $manufactureDate = Time::toMySqlDate($timeCardInfo->manufactureDate);
      
      $comments = mysqli_real_escape_string($this->getConnection(), $timeCardInfo->comments);
      
      $query =
         "INSERT INTO timecard " .
         "(employeeNumber, dateTime, manufactureDate, jobId, materialNumber, shiftTime, setupTime, runTime, panCount, partCount, scrapCount, commentCodes, comments, runTimeApprovedBy, setupTimeApprovedBy) " .
         "VALUES " .
         "('$timeCardInfo->employeeNumber', '$date', '$manufactureDate', '$timeCardInfo->jobId', '$timeCardInfo->materialNumber', '$timeCardInfo->shiftTime', '$timeCardInfo->setupTime', '$timeCardInfo->runTime', '$timeCardInfo->panCount', '$timeCardInfo->partCount', '$timeCardInfo->scrapCount', '$timeCardInfo->commentCodes', '$comments', '$timeCardInfo->runTimeApprovedBy', '$timeCardInfo->setupTimeApprovedBy');";

      $result = $this->query($query);
      
      return ($result);
   }

   public function updateTimeCard(
      $timeCardInfo)
   {
      $dateTime = Time::toMySqlDate($timeCardInfo->dateTime);
      $manufactureDate = Time::toMySqlDate($timeCardInfo->manufactureDate);      
      
      $comments = mysqli_real_escape_string($this->getConnection(), $timeCardInfo->comments);
      
      $query =
      "UPDATE timecard " .
      "SET employeeNumber = $timeCardInfo->employeeNumber, dateTime = \"$dateTime\", manufactureDate = \"$manufactureDate\", jobId = \"$timeCardInfo->jobId\", materialNumber = \"$timeCardInfo->materialNumber\", shiftTime = $timeCardInfo->shiftTime, setupTime = $timeCardInfo->setupTime, runTime = $timeCardInfo->runTime, panCount = $timeCardInfo->panCount, partCount = $timeCardInfo->partCount, scrapCount = $timeCardInfo->scrapCount, commentCodes = $timeCardInfo->commentCodes, comments = \"$comments\", runTimeApprovedBy = $timeCardInfo->runTimeApprovedBy, setupTimeApprovedBy = $timeCardInfo->setupTimeApprovedBy " .
      "WHERE timeCardId = $timeCardInfo->timeCardId;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function deleteTimeCard(
      $timeCardId)
   {
      $query = "DELETE FROM timecard WHERE timeCardId = $timeCardId;";
      
      $result = $this->query($query);
      
      $query = "DELETE FROM partweight WHERE timeCardId = $timeCardId;";
      
      $result = $this->query($query);
      
      $query = "DELETE FROM partwasher WHERE timeCardId = $timeCardId;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getIncompleteTimeCards($employeeNumber)
   {
      $query = "SELECT * FROM timecard WHERE EmployeeNumber=" . $employeeNumber . " AND NOT EXISTS (SELECT * FROM panticket WHERE panticket.timeCardId = timecard.TimeCard_Id) ORDER BY Date DESC, TimeCard_ID DESC;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   // **************************************************************************
   //                               Shipping Cards
   // **************************************************************************
   
   public function getShippingCard(
      $shippingCardId)
   {
      $query = "SELECT * FROM shippingcard WHERE shippingCardId = $shippingCardId";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function matchShippingCard(
      $jobId,
      $employeeNumber,
      $manufactureDate)
   {
      $startDate = Time::startOfDay($manufactureDate);
      $endDate = Time::endOfDay($manufactureDate);
      
      $query = "SELECT * FROM shippingcard WHERE jobId = $jobId AND employeeNumber = $employeeNumber AND manufactureDate BETWEEN '" . Time::toMySqlDate($startDate) . "' AND '" . Time::toMySqlDate($endDate) . "';";

      $result = $this->query($query);
      
      return ($result);
   }

   public function getShippingCards(
      $employeeNumber,
      $startDate,
      $endDate,
      $useMfgDate = false)
   {
      $result = null;
      
      $dateField = ($useMfgDate ? "manufactureDate" : "dateTime");
      
      if ($employeeNumber == UserInfo::UNKNOWN_EMPLOYEE_NUMBER)
      {
         $query = "SELECT * FROM shippingcard WHERE $dateField BETWEEN '" . Time::toMySqlDate($startDate) . "' AND '" . Time::toMySqlDate($endDate) . "' ORDER BY $dateField DESC, shippingCardId DESC;";

         $result = $this->query($query);
      }
      else
      {
         $query = "SELECT * FROM shippingcard WHERE employeeNumber=" . $employeeNumber . " AND $dateField BETWEEN '" . Time::toMySqlDate($startDate) . "' AND '" . Time::toMySqlDate($endDate) . "' ORDER BY $dateField DESC, shippingCardId DESC;";
         
         $result = $this->query($query);
      }

      return ($result);
   }

   public function newShippingCard(
      $shippingCardInfo)
   {
      $date = Time::toMySqlDate($shippingCardInfo->dateTime);
      $manufactureDate = Time::toMySqlDate($shippingCardInfo->manufactureDate);
      
      $comments = mysqli_real_escape_string($this->getConnection(), $shippingCardInfo->comments);
      
      $query =
         "INSERT INTO shippingcard " .
         "(employeeNumber, dateTime, timeCardId, shiftTime, shippingTime, activity, partCount, scrapCount, scrapType, comments, jobId, operator, manufactureDate) " .
         "VALUES " .
         "('$shippingCardInfo->employeeNumber', '$date', '$shippingCardInfo->timeCardId', '$shippingCardInfo->shiftTime', '$shippingCardInfo->shippingTime', '$shippingCardInfo->activity', '$shippingCardInfo->partCount', '$shippingCardInfo->scrapCount', '$shippingCardInfo->scrapType', '$comments', '$shippingCardInfo->jobId', '$shippingCardInfo->operator', '$manufactureDate');";

      $result = $this->query($query);
      
      return ($result);
   }

   public function updateShippingCard(
      $shippingCardInfo)
   {
      $dateTime = Time::toMySqlDate($shippingCardInfo->dateTime);
      $manufactureDate = Time::toMySqlDate($shippingCardInfo->manufactureDate);      
      
      $comments = mysqli_real_escape_string($this->getConnection(), $shippingCardInfo->comments);
      
      $query =
      "UPDATE shippingcard " .
      "SET employeeNumber = $shippingCardInfo->employeeNumber, dateTime = \"$dateTime\", timeCardId = $shippingCardInfo->timeCardId, shiftTime = $shippingCardInfo->shiftTime, shippingTime = $shippingCardInfo->shippingTime, activity = $shippingCardInfo->activity, partCount = $shippingCardInfo->partCount, scrapCount = $shippingCardInfo->scrapCount, scrapType = $shippingCardInfo->scrapType, comments = \"$comments\", jobId = $shippingCardInfo->jobId, operator =  $shippingCardInfo->operator, manufactureDate = \"$manufactureDate\" " .
      "WHERE shippingCardId = $shippingCardInfo->shippingCardId;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function deleteShippingCard(
      $shippingCardId)
   {
      $query = "DELETE FROM shippingcard WHERE shippingCardId = $shippingCardId;";

      $result = $this->query($query);
            
      return ($result);
   }
   
   // **************************************************************************
   //                                 Users
   // **************************************************************************
   
   public function getUser($employeeNumber)
   {
      $query = "SELECT * FROM user WHERE employeeNumber = \"$employeeNumber\";";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getUserByName($username)
   {
      $query = "SELECT * FROM user WHERE username = \"$username\";";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getUsers()
   {
      $query = "SELECT * FROM user ORDER BY firstName ASC;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getUsersByRole($role)
   {
      $roleClause = "";
      if ($role != Role::UNKNOWN)
      {
         $roleClause = "WHERE roles = $role";
      }
      
      $query = "SELECT * FROM user $roleClause ORDER BY firstName ASC;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getUsersByRoles($roles)
   {
      $result = null;
      
      if (sizeof($roles) > 0)
      {
         $rolesClause = "roles in (";
         
         $count = 0;
         foreach ($roles as $role)
         {
            $rolesClause .= "'$role'";
            
            $count++;
            
            if ($count < sizeof($roles))
            {
               $rolesClause .= ", ";
            }
         }
         
         $rolesClause .= ")";
         
         $query = "SELECT * FROM user WHERE $rolesClause ORDER BY firstName ASC;";

         $result = $this->query($query);
      }
      
      return ($result);
   }
   
   public function newUser($userInfo)
   {
      $query =
      "INSERT INTO user " .
      "(employeeNumber, username, password, roles, permissions, firstName, lastName, email, authToken, defaultShiftHours, notifications) " .
      "VALUES " .
      "('$userInfo->employeeNumber', '$userInfo->username', '$userInfo->password', '$userInfo->roles', '$userInfo->permissions', '$userInfo->firstName', '$userInfo->lastName', '$userInfo->email', '$userInfo->authToken', '$userInfo->defaultShiftHours', $userInfo->notifications);";
 
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function updateUser($userInfo)
   {
      $query =
      "UPDATE user " .
      "SET username = '$userInfo->username', password = '$userInfo->password', roles = '$userInfo->roles', permissions = '$userInfo->permissions', firstName = '$userInfo->firstName', lastName = '$userInfo->lastName', email = '$userInfo->email', authToken = '$userInfo->authToken', defaultShiftHours = '$userInfo->defaultShiftHours', notifications = $userInfo->notifications " .
      "WHERE employeeNumber = '$userInfo->employeeNumber';";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function deleteUser($employeeNumber)
   {
      $query = "DELETE FROM user WHERE employeeNumber = '$employeeNumber';";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   // **************************************************************************
   //                                 Sensors
   // **************************************************************************
  
   public function getSensors()
   {
      $query = "SELECT * FROM sensor ORDER BY wcNumber ASC;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getSensor($sensorId)
   {
      $query = "SELECT * FROM sensor WHERE sensorId = \"$sensorId\";";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getSensorForWorkcenter($wcNumber)
   {
      $query = "SELECT * FROM sensor WHERE wcNumber = \"$wcNumber\";";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   // **************************************************************************
   //                               Part Counts
   // **************************************************************************
   
   public function getPartCounts($wcNumber, $startDate, $endDate)
   {
      
   }
   
   public function getPartCountsByHour($wcNumber, $date)
   {
      
   }
   
   public function getPartCountsByShift($wcNumber, $shift)
   {
      
   }
   
   public function resetPartCounter($sensorId)
   {
      $now = Time::toMySqlDate(Time::now("Y-m-d H:i:s"));
      
      // Record last contact time.
      $query = "UPDATE sensor SET lastContact = \"$now\" WHERE sensorId = \"$sensorId\";";
      $this->query($query);
      
      // Record the reset time.
      $query = "UPDATE sensor SET resetTime = \"$now\" WHERE sensorId = \"$sensorId\";";
      $this->query($query);
      
      // Update counter count.
      $query = "UPDATE sensor SET partCount = 0 WHERE sensorId = \"$sensorId\";";
      $this->query($query);
   }
   
   public function updatePartCount($sensorId, $partCount)
   {
      $this->checkForNewSensor($sensorId);
      
      $now = Time::toMySqlDate(Time::now("Y-m-d H:i:s"));
      
      // Record last contact time.
      $query = "UPDATE sensor SET lastContact = \"$now\" WHERE sensorId = \"$sensorId\";";
      $this->query($query);
      
      if ($partCount > 0)
      {
         // Record last part count time.
         $query = "UPDATE sensor SET lastCount = \"$now\" WHERE sensorId = \"$sensorId\";";
         $this->query($query);
         
         // Update counter count.
         $query = "UPDATE sensor SET partCount = partCount + $partCount WHERE sensorId = \"$sensorId\";";
         $this->query($query);

         $this->updatePartCount_Hour($sensorId, $partCount);
         $this->updatePartCount_Day($sensorId, $partCount);
         $this->updatePartCount_Shift($sensorId, $partCount);
      }
   }
   
   // **************************************************************************
   //                               Part Inspections
   // **************************************************************************
      
   public function newPartInspection($partInspection)
   {
      $date = Time::toMySqlDate($partInspection->dateTime);
      
      $query =
      "INSERT INTO partinspection " .
      "(dateTime, employeeNumber, wcNumber, partNumber, partCount, failures, efficiency) " .
      "VALUES " .
      "('$date', '$partInspection->employeeNumber', '$partInspection->wcNumber', '$partInspection->partNumber', '$partInspection->partCount', '$partInspection->failures', '$partInspection->efficiency');";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getPartInspection(
         $partInspectionId)
   {
      $query = "SELECT * FROM partinspection WHERE partInspectionId = \"$partInspectionId\";";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getPartInspections(
      $employeeNumber,
      $startDate,
      $endDate)
   {
      $result = NULL;
      if ($employeeNumber == 0)
      {
         $query = "SELECT * FROM partinspection WHERE dateTime BETWEEN '" . Time::toMySqlDate($startDate) . "' AND '" . Time::toMySqlDate($endDate) . "' ORDER BY dateTime DESC;";

         $result = $this->query($query);
      }
      else
      {
         $query = "SELECT * FROM partinspection WHERE employeeNumber =" . $employeeNumber . " AND dateTime BETWEEN '" . Time::toMySqlDate($startDate) . "' AND '" . Time::toMySqlDate($endDate) . "' ORDER BY dateTime DESC;";
         
         $result = $this->query($query);
      }
      
      return ($result);
   }
   
   // **************************************************************************
   //                              Part Washer Log
   // **************************************************************************
   
   public function getPartWasherEntry($partWasherEntryId)
   {
      $query = "SELECT * FROM partwasher WHERE partWasherEntryId = \"$partWasherEntryId\";";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getPartWasherEntries(
      $jobId,
      $employeeNumber,
      $startDate,
      $endDate,
      $useMfgDate)
   {      
      $jobClause = "";
      if ($jobId != JobInfo::UNKNOWN_JOB_ID)
      {
         // Job id may be in the part washer entry itself, or in the associated time card.
         $jobClause = "(partwasher.jobId = '$jobId' OR timecard.jobId = '$jobId') AND ";
      }
      
      $employeeClause = "";
      if ($employeeNumber != UserInfo::UNKNOWN_EMPLOYEE_NUMBER)
      {
         $employeeClause = "partwasher.employeeNumber = '$employeeNumber' AND";
      }
      
      $dateTimeClause = "";
      if ($useMfgDate == true)
      {
         // Manufacture time may be in the part washer entry itself, or in the associated time card.
         $dateTimeClause = "((partwasher.manufactureDate BETWEEN '" . Time::toMySqlDate($startDate) . "' AND '" . Time::toMySqlDate($endDate) . "') OR" .
                           " (timecard.dateTime BETWEEN '" .   Time::toMySqlDate($startDate) . "' AND '" . Time::toMySqlDate($endDate) . "'))";
      }
      else
      {
         $dateTimeClause = "partwasher.dateTime BETWEEN '" . Time::toMySqlDate($startDate) . "' AND '" . Time::toMySqlDate($endDate) . "'";
      }
      
      $query = "SELECT partwasher.* FROM partwasher " .
               "LEFT JOIN timecard ON partwasher.timeCardId = timecard.timeCardId " .
               "WHERE $jobClause $employeeClause $dateTimeClause ORDER BY partwasher.dateTime DESC;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getPartWasherEntriesByTimeCard($timeCardId)
   {
      $query = "SELECT * FROM partwasher WHERE timeCardId = \"$timeCardId\" ORDER BY dateTime DESC;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function newPartWasherEntry(
      $partWasherEntry)
   {
      $dateTime = Time::toMySqlDate($partWasherEntry->dateTime);
      
      $manufactureDate = "null";  // Note: Must use "null" for dates, rather than "".
      if ($partWasherEntry->manufactureDate)
      {
         $manufactureDate = "'" . Time::toMySqlDate($partWasherEntry->manufactureDate) . "'";
      }
      
      $query =
      "INSERT INTO partwasher " .
      "(dateTime, employeeNumber, timeCardId, panCount, partCount, jobId, operator, manufactureDate) " .
      "VALUES " .
      "('$dateTime', '$partWasherEntry->employeeNumber', '$partWasherEntry->timeCardId', '$partWasherEntry->panCount', '$partWasherEntry->partCount', '$partWasherEntry->jobId', '$partWasherEntry->operator', $manufactureDate);";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function updatePartWasherEntry(
      $partWasherEntry)
   {
      $dateTime = Time::toMySqlDate($partWasherEntry->dateTime);
      
      $manufactureDate = "null";  // Note: Must use "null" for dates, rather than "".
      if ($partWasherEntry->manufactureDate)
      {
         $manufactureDate = "'" . Time::toMySqlDate($partWasherEntry->manufactureDate) . "'";
      }
      
      $query =
      "UPDATE partwasher " .
      "SET dateTime = \"$dateTime\", employeeNumber = $partWasherEntry->employeeNumber, timeCardId = $partWasherEntry->timeCardId, panCount = $partWasherEntry->panCount, partCount = $partWasherEntry->partCount, jobId = $partWasherEntry->jobId, operator = $partWasherEntry->operator, manufactureDate = $manufactureDate " .
      "WHERE partWasherEntryId = $partWasherEntry->partWasherEntryId;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function deletePartWasherEntry(
      $partWasherEntryId)
   {
      $query = "DELETE FROM partwasher WHERE partWasherEntryId = $partWasherEntryId;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function deleteAllPartWasherEntries(
      $timeCardId)
   {
      $query = "DELETE FROM partwasher WHERE timeCardId = $timeCardId;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   // **************************************************************************
   //                              Part Weight Log
   // **************************************************************************
   
   public function getPartWeightEntry(
      $partWeightEntryId)
   {
      $query = "SELECT * FROM partweight WHERE partWeightEntryId = \"$partWeightEntryId\";";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getPartWeightEntries(
      $jobId,
      $employeeNumber,
      $startDate,
      $endDate,
      $useMfgDate)
   {
      $jobClause = "";
      if ($jobId != JobInfo::UNKNOWN_JOB_ID)
      {
         // Job id may be in the part weight entry itself, or in the associated time card.
         $jobClause = "(partweight.jobId = '$jobId' OR timecard.jobId = '$jobId') AND ";
      }
            
      $employeeClause = "";
      if ($employeeNumber != UserInfo::UNKNOWN_EMPLOYEE_NUMBER)
      {
         $employeeClause = "partweight.employeeNumber = '$employeeNumber' AND";
      }
      
      $dateTimeClause = "";
      if ($useMfgDate == true)
      {
         // Manufacture time may be in the part weight entry itself, or in the associated time card.
         $dateTimeClause = "((partweight.manufactureDate BETWEEN '" . Time::toMySqlDate($startDate) . "' AND '" . Time::toMySqlDate($endDate) . "') OR" .
                           " (timecard.dateTime BETWEEN '" .   Time::toMySqlDate($startDate) . "' AND '" . Time::toMySqlDate($endDate) . "'))";
      }
      else
      {
         $dateTimeClause = "partweight.dateTime BETWEEN '" . Time::toMySqlDate($startDate) . "' AND '" . Time::toMySqlDate($endDate) . "'";
      }
      
      $query = "SELECT partweight.* FROM partweight " .
               "LEFT JOIN timecard ON partweight.timeCardId = timecard.timeCardId " .
               "WHERE $jobClause $employeeClause $dateTimeClause ORDER BY partweight.dateTime DESC;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getPartWeightEntriesByTimeCard($timeCardId)
   {
      $query = "SELECT * FROM partweight WHERE timeCardId = \"$timeCardId\" ORDER BY dateTime DESC;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function newPartWeightEntry(
      $partWeightEntry)
   {
      $dateTime = Time::toMySqlDate($partWeightEntry->dateTime);
      
      $manufactureDate = "null";  // Note: Must use "null" for dates, rather than "".
      if ($partWeightEntry->manufactureDate)
      {
         $manufactureDate = "'" . Time::toMySqlDate($partWeightEntry->manufactureDate) . "'";
      }

      $query =
      "INSERT INTO partweight " .
      "(dateTime, employeeNumber, timeCardId, weight, jobId, operator, manufactureDate, panCount) " .
      "VALUES " .
      "('$dateTime', '$partWeightEntry->employeeNumber', '$partWeightEntry->timeCardId', '$partWeightEntry->weight', '$partWeightEntry->jobId', '$partWeightEntry->operator', $manufactureDate, '$partWeightEntry->panCount');";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function updatePartWeightEntry(
      $partWeightEntry)
   {
      $dateTime = Time::toMySqlDate($partWeightEntry->dateTime);
      
      $manufactureDate = "null";  // Note: Must use "null" for dates, rather than "".
      if ($partWeightEntry->manufactureDate)
      {
         $manufactureDate = "'" . Time::toMySqlDate($partWeightEntry->manufactureDate) . "'";
      }
            
      $query =
      "UPDATE partweight " .
      "SET dateTime = \"$dateTime\", employeeNumber = $partWeightEntry->employeeNumber, timeCardId = $partWeightEntry->timeCardId, weight = $partWeightEntry->weight, jobId = $partWeightEntry->jobId, operator = $partWeightEntry->operator, manufactureDate = $manufactureDate, panCount = $partWeightEntry->panCount " .
      "WHERE partWeightEntryId = $partWeightEntry->partWeightEntryId;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function deletePartWeightEntry(
      $partWeightEntryId)
   {
      $query = "DELETE FROM partweight WHERE partWeightEntryId = $partWeightEntryId;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function deleteAllPartWeightEntries(
      $timeCardId)
   {
      $query = "DELETE FROM partweight WHERE timeCardId = $timeCardId;";

      $result = $this->query($query);
      
      return ($result);
   }
      
   // **************************************************************************
   //                                  Jobs
   // **************************************************************************
   
   public function getJobNumbers($onlyActive)
   {
      $maintenanceJobId = JobInfo::MAINTENANCE_JOB_ID;
   
      $active = JobStatus::ACTIVE;
      $deleted = JobStatus::DELETED;
      
      $statusClause = "status != $deleted";
      if ($onlyActive)
      {
         $statusClause = "status = $active";
      }
      
      $query = "SELECT DISTINCT jobNumber FROM job WHERE $statusClause AND jobId != $maintenanceJobId ORDER BY jobNumber ASC;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getJobs($jobNumber, $jobStatuses)
   {
      $result = null;

      // Validate input.
      // If an array of job statuses is specified, it must not be empty.
      if (is_null($jobStatuses) || (count($jobStatuses) > 0))
      {
         $jobNumberClause = "TRUE";
         if ($jobNumber != JobInfo::UNKNOWN_JOB_NUMBER)
         {
            $jobNumberClause = "jobNumber = '$jobNumber'";
         }
         
         $jobStatusClause = "TRUE";
         if ($jobStatuses && count($jobStatuses) > 0)
         {
            $jobStatusClause = "(";
            
            $or = false;
            foreach ($jobStatuses as $jobStatus)
            {
               if ($or)
               {
                  $jobStatusClause .= " OR ";
               }
                  
               $jobStatusClause .= "status = $jobStatus";
                  
               $or = true;
            }
            
            $jobStatusClause .= ")";
         }
         
         $query = "SELECT * FROM job WHERE $jobNumberClause AND $jobStatusClause ORDER BY jobNumber ASC;";

         $result = $this->query($query);
      }
      
      return ($result);
   }
   
   public function getActiveJobs($wcNumber)
   {
      $active = JobStatus::ACTIVE;
      
      $wcClause = $wcNumber ? "wcNumber = '$wcNumber' AND" : "";
      
      $query = "SELECT * FROM job WHERE $wcClause status = $active ORDER BY jobNumber ASC;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getJob($jobId)
   {
      $query = "SELECT * FROM job WHERE jobId = $jobId;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getJobsByJobNumber($jobNumber)
   {
      $query = "SELECT * FROM job WHERE jobNumber = \"$jobNumber\";";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getJobByComponents($jobNumber, $wcNumber)
   {
      $query = "SELECT * FROM job WHERE jobNumber = \"$jobNumber\" AND wcNumber = \"$wcNumber\";";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function newJob($jobInfo)
   {
      $dateTime = Time::toMySqlDate($jobInfo->dateTime);
      
      $query =
      "INSERT INTO job " .
      "(jobNumber, creator, dateTime, partNumber, sampleWeight, wcNumber, grossPartsPerHour, netPartsPerHour, status, customerPrint, firstPartTemplateId, inProcessTemplateId, lineTemplateId, qcpTemplateId, finalTemplateId) " .
      "VALUES " .
      "('$jobInfo->jobNumber', '$jobInfo->creator', '$dateTime', '$jobInfo->partNumber', '$jobInfo->sampleWeight', '$jobInfo->wcNumber', '$jobInfo->grossPartsPerHour', '$jobInfo->netPartsPerHour', '$jobInfo->status', '$jobInfo->customerPrint', '$jobInfo->firstPartTemplateId', '$jobInfo->inProcessTemplateId', '$jobInfo->lineTemplateId', '$jobInfo->qcpTemplateId', '$jobInfo->finalTemplateId');";

      $result = $this->query($query);

      return ($result);
   }
   
   public function updateJob($jobInfo)
   {
      $dateTime = Time::toMySqlDate($jobInfo->dateTime);
      
      $query =
         "UPDATE job " .
         "SET creator = '$jobInfo->creator', dateTime = '$dateTime', partNumber = '$jobInfo->partNumber', sampleWeight = '$jobInfo->sampleWeight', wcNumber = '$jobInfo->wcNumber', grossPartsPerHour = '$jobInfo->grossPartsPerHour', netPartsPerHour = '$jobInfo->netPartsPerHour', status = '$jobInfo->status', customerPrint = '$jobInfo->customerPrint', firstPartTemplateId = '$jobInfo->firstPartTemplateId', inProcessTemplateId = '$jobInfo->inProcessTemplateId', lineTemplateId = '$jobInfo->lineTemplateId',  qcpTemplateId = '$jobInfo->qcpTemplateId', finalTemplateId = '$jobInfo->finalTemplateId' " .
         "WHERE jobId = '$jobInfo->jobId';";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function setCustomerPrint($jobId, $filename)
   {
      $query = "UPDATE job SET customerPrint = '$filename' WHERE jobId = '$jobId';";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function updateJobStatus($jobId, $status)
   {
      $query =
         "UPDATE job " .
         "SET status = '$status' " .
         "WHERE jobId = '$jobId';";

      $result = $this->query($query);

      return ($result);
   }
   
   public function deleteJob($jobId)
   {
      $query = "DELETE FROM job WHERE jobId = '$jobId';";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getCommentCodes()
   {
      $query = "SELECT * FROM comment;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   // **************************************************************************
   //                                 Signs
   // **************************************************************************
   
   public function getSign(
      $signId)
   {
      $query = "SELECT * FROM sign WHERE signId = \"$signId\";";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getSigns()
   {
      $query = "SELECT * FROM sign ORDER BY signId ASC;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function newSign(
      $signInfo)
   {
      $query =
      "INSERT INTO sign " .
      "(name, description, url) " .
      "VALUES " .
      "('$signInfo->name', '$signInfo->description', '$signInfo->url');";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function updateSign(
      $signInfo)
   {
      $query =
      "UPDATE sign " .
      "SET name = '$signInfo->name', description = '$signInfo->description', url = '$signInfo->url'" .
      "WHERE signId = $signInfo->signId;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function deleteSign(
      $signId)
   {
      $query = "DELETE FROM sign WHERE signId = $signId;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   // **************************************************************************
   //                          Line Inspections
   // **************************************************************************
   
   public function getLineInspections($employeeNumber, $jobNumber, $startDate, $endDate)
   {
      $operatorClause = "";
      if ($employeeNumber != 0)
      {
         $operatorClause = "operator = $employeeNumber AND ";
      }
      
      $jobNumberClause = "";
      if ($jobNumber != "All")
      {
         $jobNumberClause = "jobNumber = '$jobNumber' AND ";
      }
      
      $query = "SELECT * FROM lineinspection WHERE $operatorClause $jobNumberClause dateTime BETWEEN '" . Time::toMySqlDate($startDate) . "' AND '" . Time::toMySqlDate($endDate) . "' ORDER BY dateTime DESC, entryId DESC;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getLineInspection($entryId)
   {
      $query = "SELECT * FROM lineinspection WHERE entryId = \"$entryId\";";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function newLineInspection($lineInspectionInfo)
   {
      $dateTime = Time::toMySqlDate($lineInspectionInfo->dateTime);
      
      $query =
      "INSERT INTO lineinspection " .
      "(dateTime, inspector, operator, jobNumber, wcNumber, inspection1, inspection2, inspection3, inspection4, inspection5, inspection6, comments) " .
      "VALUES " .
      "('$dateTime', '$lineInspectionInfo->inspector', '$lineInspectionInfo->operator', '$lineInspectionInfo->jobNumber', '$lineInspectionInfo->wcNumber', '{$lineInspectionInfo->inspections[0]}', '{$lineInspectionInfo->inspections[1]}', '{$lineInspectionInfo->inspections[2]}', '{$lineInspectionInfo->inspections[3]}', '{$lineInspectionInfo->inspections[4]}', '{$lineInspectionInfo->inspections[5]}', '$lineInspectionInfo->comments');";

      $result = $this->query($query);

      return ($result);
   }
   
   public function updateLineInspection($lineInspectionInfo)
   {
      $dateTime = Time::toMySqlDate($lineInspectionInfo->dateTime);
      
      $query =
      "UPDATE lineinspection " .
      "SET dateTime = '$dateTime',  inspector = '$lineInspectionInfo->inspector', operator = '$lineInspectionInfo->operator', jobNumber = '$lineInspectionInfo->jobNumber', wcNumber = '$lineInspectionInfo->wcNumber', inspection1 = '{$lineInspectionInfo->inspections[0]}', inspection2 = '{$lineInspectionInfo->inspections[1]}', inspection3 = '{$lineInspectionInfo->inspections[2]}', inspection4 = '{$lineInspectionInfo->inspections[3]}', inspection5 = '{$lineInspectionInfo->inspections[4]}', inspection6 = '{$lineInspectionInfo->inspections[5]}', comments = '$lineInspectionInfo->comments' " .
      "WHERE entryId = '$lineInspectionInfo->entryId';";
      
      $result = $this->query($query);

      return ($result);
   }
   
   public function deleteLineInspection($entryId)
   {
      $query = "DELETE FROM lineinspection WHERE entryId = $entryId;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   // **************************************************************************
   //                            Inspection Templates
   // **************************************************************************
   
   public function getInspectionTemplates($inspectionType)
   {
      $typeClause = "";
      if ($inspectionType != InspectionType::UNKNOWN)
      {
         $typeClause = "WHERE inspectionType = $inspectionType ";
      }
      
      $query = "SELECT * FROM inspectiontemplate $typeClause ORDER BY name ASC;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getInspectionTemplatesForJobNumber($inspectionType, $jobNumber)
   {
      $result = null;
      
      // Not valid for GENERIC inspections.
      if ($inspectionType != InspectionType::GENERIC)
      {
         $typeClause = ($inspectionType != InspectionType::UNKNOWN) ?
                          "inspectionType = $inspectionType " :
                          "TRUE";
         
         $templateIdClause = "TRUE";
         switch ($inspectionType)
         {
            case InspectionType::LINE:
            {
               $templateIdClause = "job.lineTemplateId = inspectiontemplate.templateId";
               break;
            }
            
            case InspectionType::QCP:
            {
               $templateIdClause = "job.qcpTemplateId = inspectiontemplate.templateId";
               break;
            }
            
            case InspectionType::IN_PROCESS:
            {
               $templateIdClause = "job.inProcessTemplateId = inspectiontemplate.templateId";
               break;
            }
            
            case InspectionType::FIRST_PART:
            {
               $templateIdClause = "job.firstPartTemplateId = inspectiontemplate.templateId";
               break;
            }
            
            case InspectionType::FINAL:
            {
               $templateIdClause = "job.finalTemplateId = inspectiontemplate.templateId";
               break;
            }
            
            default:
            {
               break;
            }
         }
      
         $query = "SELECT * FROM inspectiontemplate " .
                  "INNER JOIN job ON $templateIdClause " .
                  "WHERE $typeClause AND job.jobNumber = '$jobNumber' " .
                  "ORDER BY name ASC;";
         
         $result = $this->query($query);
      }
            
      return ($result);
   }
   
   public function getInspectionTemplate($templateId)
   {
      $query = "SELECT * FROM inspectiontemplate WHERE templateId = $templateId;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getInspectionProperties($templateId)
   {
      $query = "SELECT * FROM inspectionproperty WHERE templateId = $templateId ORDER BY ordering ASC;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function newInspectionTemplate($inspectionTemplate)
   {
      $query =
      "INSERT INTO inspectiontemplate " .
      "(name, description, inspectionType, sampleSize, optionalProperties, notes) " .
      "VALUES " .
      "('$inspectionTemplate->name', '$inspectionTemplate->description', '$inspectionTemplate->inspectionType', '$inspectionTemplate->sampleSize', '$inspectionTemplate->optionalProperties', '$inspectionTemplate->notes');";

      $result = $this->query($query);
      
      if ($result)
      {
         // Get the last auto-increment id, which should be the inspection id.
         $templateId = mysqli_insert_id($this->getConnection());
         
         foreach ($inspectionTemplate->inspectionProperties as $inspectionProperty)
         {
            $query =
            "INSERT INTO inspectionproperty " .
            "(templateId, name, specification, dataType, dataUnits, ordering) " .
            "VALUES " .
            "('$templateId', '$inspectionProperty->name', '$inspectionProperty->specification', '$inspectionProperty->dataType', '$inspectionProperty->dataUnits', '$inspectionProperty->ordering');";

            $result &= $this->query($query);
            
            if (!$result)
            {
               break;
            }
         }
      }
      
      return ($result);
   }
   
   public function updateInspectionTemplate($inspectionTemplate)
   {
      $query =
      "UPDATE inspectiontemplate " .
      "SET name = '$inspectionTemplate->name', description = '$inspectionTemplate->description', inspectionType = '$inspectionTemplate->inspectionType', sampleSize = '$inspectionTemplate->sampleSize', optionalProperties = '$inspectionTemplate->optionalProperties', notes = '$inspectionTemplate->notes' " .
      "WHERE templateId = '$inspectionTemplate->templateId';";

      $result = $this->query($query);

      if ($result)
      {
         // Gather the original set of property ids.
         $origInspectionPropertyIds = [];
         $query = "SELECT propertyId FROM inspectionproperty WHERE templateId = '$inspectionTemplate->templateId'";
         $result = $this->query($query);
         while ($result && ($row = $result->fetch_assoc()))
         {
            $origInspectionPropertyIds[] = intval($row["propertyId"]);
         }
         
         // Gather the current set of property ids as we go.
         $updatedInspectionPropertyIds = [];
         
         foreach ($inspectionTemplate->inspectionProperties as $inspectionProperty)
         {
            if ($inspectionProperty->propertyId == InspectionProperty::UNKNOWN_PROPERTY_ID)
            {
               // New property.
               $query =
               "INSERT INTO inspectionproperty " .
               "(templateId, name, specification, dataType, dataUnits, ordering) " .
               "VALUES " .
               "('$inspectionTemplate->templateId', '$inspectionProperty->name', '$inspectionProperty->specification', '$inspectionProperty->dataType', '$inspectionProperty->dataUnits', '$inspectionProperty->ordering');";

               $result = $this->query($query);
            }
            else
            {
               $updatedInspectionPropertyIds[] = $inspectionProperty->propertyId;
               
               // Updated property.
               $query =
               "UPDATE inspectionproperty " .
               "SET name = '$inspectionProperty->name', specification = '$inspectionProperty->specification', dataType =  '$inspectionProperty->dataType', dataUnits = '$inspectionProperty->dataUnits', ordering = '$inspectionProperty->ordering' " .
               "WHERE propertyId = '$inspectionProperty->propertyId';";

               $result = $this->query($query);
            }
            
            if (!$result)
            {
               break;
            }
         }
         
         // Process deletes.
         if ($result)
         {
            $deletedInspectionPropertyIds = array_diff($origInspectionPropertyIds, $updatedInspectionPropertyIds);

            foreach ($deletedInspectionPropertyIds as $inspectionPropertyId)
            {
               $query = "DELETE FROM inspectionproperty WHERE propertyId = '$inspectionPropertyId';";
               $this->query($query);
            }
         }
      }
      
      return ($result);
   }
   
   public function deleteInspectionTemplate($templateId)
   {
      $query = "DELETE FROM inspectiontemplate WHERE templateId = $templateId;";
      $result = $this->query($query);
      
      $query = "DELETE FROM inspectionproperty WHERE templateId = $templateId;";
      $result &= $this->query($query);
      
      $query = "SELECT inspectionId FROM inspection WHERE templateId = $templateId;";
      $searchResult = $this->query($query);

      while ($searchResult && ($row = $searchResult->fetch_assoc()))
      {
         $this->deleteInspection(intval($row['inspectionId']));
      }
      
      return ($result);
   }
   
   // **************************************************************************
   //                                Inspections
   // **************************************************************************
   
   public function getInspections($inspectionType, $inspector, $operator, $startDate, $endDate)
   {
      $userClause = "";
      if (($inspector != UserInfo::UNKNOWN_EMPLOYEE_NUMBER) || ($operator != UserInfo::UNKNOWN_EMPLOYEE_NUMBER))
      {
         $userClause = "(";
         
         if ($inspector != UserInfo::UNKNOWN_EMPLOYEE_NUMBER)
         {
            $userClause .= "inspector = $inspector";
         }
         
         if (($inspector != UserInfo::UNKNOWN_EMPLOYEE_NUMBER) && ($operator != UserInfo::UNKNOWN_EMPLOYEE_NUMBER))
         {
            $userClause .= " OR ";
         }
         
         if ($operator != UserInfo::UNKNOWN_EMPLOYEE_NUMBER)
         {
            $userClause .= "operator = $operator";
         }
         
         $userClause .= ") AND";
      }
      
      $typeClause = "";
      if ($inspectionType != InspectionType::UNKNOWN)
      {
         $typeClause = "inspectiontemplate.inspectionType = $inspectionType AND ";
      }
      
      $query = "SELECT * FROM inspection " .
               "INNER JOIN inspectiontemplate ON inspection.templateId = inspectiontemplate.templateId " .
               "WHERE $userClause $typeClause inspection.mfgDate BETWEEN '" . Time::toMySqlDate($startDate) . "' AND '" . Time::toMySqlDate($endDate) . "' ORDER BY inspection.dateTime DESC, inspectionId DESC;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getInspection($inspectionId)
   {
      $query = "SELECT * FROM inspection WHERE inspectionId = $inspectionId;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getInspectionResults($inspectionId)
   {
      $query = "SELECT * FROM inspectionresult " .
               "INNER JOIN inspectionproperty ON inspectionresult.propertyId = inspectionproperty.propertyId " .
               "WHERE inspectionresult.inspectionId = $inspectionId ORDER BY inspectionproperty.ordering ASC, inspectionresult.sampleIndex ASC;";

      $result = $this->query($query);

      return ($result);
   }
   
   public function newInspection($inspection)
   {
      $dateTime = Time::toMySqlDate($inspection->dateTime);      
      $mfgDate = $inspection->mfgDate ? Time::toMySqlDate($inspection->mfgDate) : null;
      
      $mfgClause = ($mfgDate ? "'$mfgDate'" : "NULL");
      
      $query =
      "INSERT INTO inspection " .
      "(templateId, dateTime, inspector, comments, jobId, jobNumber, wcNumber, operator, mfgDate, inspectionNumber, quantity, samples, naCount, passCount, failCount, dataFile) " .
      "VALUES " .
      "('$inspection->templateId', '$dateTime', '$inspection->inspector', '$inspection->comments', '$inspection->jobId', '$inspection->jobNumber', '$inspection->wcNumber', '$inspection->operator', $mfgClause, '$inspection->inspectionNumber', '$inspection->quantity', '$inspection->samples', '$inspection->naCount', '$inspection->passCount', '$inspection->failCount', '$inspection->dataFile');";
      
      $result = $this->query($query);
      
      // Get the last auto-increment id, which should be the inspection id.
      $inspectionId = $result ? mysqli_insert_id($this->getConnection()) : Inspection::UNKNOWN_INSPECTION_ID;
      
      if ($result && $inspection->inspectionResults)
      {
         foreach ($inspection->inspectionResults as $inspectionRow)
         {
            foreach ($inspectionRow as $inspectionResult)
            {
               $query =
               "INSERT INTO inspectionresult " .
               "(inspectionId, propertyId, sampleIndex, dateTime, status, data) " .
               "VALUES " .
               "('$inspectionId', '$inspectionResult->propertyId', '$inspectionResult->sampleIndex', '$dateTime', '$inspectionResult->status', '$inspectionResult->data');";

               $result &= $this->query($query);
               
               if (!$result)
               {
                  break;
               }
            }
         }
      }
      
      return ($inspectionId);  // A little different because we need the newly created inspectionId for notifications.
   }
   
   public function updateInspection($inspection)
   {
      $dateTime = Time::toMySqlDate($inspection->dateTime);
      $mfgDate = $inspection->mfgDate ? Time::toMySqlDate($inspection->mfgDate) : null;
      
      $mfgClause = "mfgDate = " . ($mfgDate ? "'$mfgDate'" : "NULL");  
      
      $query =
      "UPDATE inspection " .
      "SET dateTime = '$dateTime', inspector = '$inspection->inspector', comments = '$inspection->comments', jobId = '$inspection->jobId', jobNumber = '$inspection->jobNumber', wcNumber = '$inspection->wcNumber', operator = '$inspection->operator', $mfgClause, inspectionNumber = '$inspection->inspectionNumber', quantity = '$inspection->quantity', samples = '$inspection->samples', naCount = '$inspection->naCount', passCount = '$inspection->passCount', failCount = '$inspection->failCount', dataFile = '$inspection->dataFile'  " .
      "WHERE inspectionId = '$inspection->inspectionId';";

      $result = $this->query($query);
      
      if ($result)
      {
         foreach ($inspection->inspectionResults as $inspectionRow)
         {
            foreach ($inspectionRow as $inspectionResult)
            {
               $query = 
               "SELECT * FROM inspectionresult " .
               "WHERE inspectionId = '$inspection->inspectionId' AND propertyId = '$inspectionResult->propertyId' AND sampleIndex='$inspectionResult->sampleIndex';";

               $seachResult = $this->query($query);
               
               if (MySqlDatabase::countResults($seachResult) == 0)
               {
                  // New result.
                  $query =
                  "INSERT INTO inspectionresult " .
                  "(inspectionId, propertyId, sampleIndex, dateTime, status, data) " .
                  "VALUES " .
                  "('$inspection->inspectionId', '$inspectionResult->propertyId', '$inspectionResult->sampleIndex', '$dateTime', '$inspectionResult->status', '$inspectionResult->data');";

                  $result &= $this->query($query);
               }
               else
               {
                  // Detect a change to the inspection result.
                  $row = $seachResult->fetch_assoc();
                  $changed = (($inspectionResult->status != intval($row['status'])) ||
                              ($inspectionResult->data != $row['data']));
                  
                  //  Only update date if a change is detected.
                  $dateClause = $changed ? "dateTime = '$dateTime', " : "";
                  
                  // Updated result.
                  $query =
                  "UPDATE inspectionresult " .
                  "SET $dateClause status = '$inspectionResult->status', data = '$inspectionResult->data' " .
                  "WHERE inspectionId = '$inspection->inspectionId' AND propertyId = '$inspectionResult->propertyId' AND sampleIndex='$inspectionResult->sampleIndex';";

                  $result &= $this->query($query);
               }

               if (!$result)
               {
                  break;
               }
            }
         }
         
         // Delete inspections results that were invalidated by a sample size change.
         $query = 
         "DELETE FROM inspectionresult  " .
         "WHERE inspectionId = '$inspection->inspectionId' AND propertyId = '$inspectionResult->propertyId' AND sampleIndex >= {$inspection->getSampleSize()};";
         
         $result &= $this->query($query);
      }
      
      return ($result);
   }
   
   public function deleteInspection($inspectionId)
   {
      $query = "DELETE FROM inspection WHERE inspectionId = $inspectionId;";
      
      $result = $this->query($query);
      
      $query = "DELETE FROM inspectionresult WHERE inspectionId = $inspectionId;";
      
      $result &= $this->query($query);
      
      return ($result);
   }
   
   // **************************************************************************
   //                                 Printer
   // **************************************************************************
   
   public function getPrinter($printerName)
   {
      $printerName = addslashes($printerName);
      
      $query = "SELECT * FROM printer WHERE printerName = '$printerName';";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function newPrinter($printerInfo)
   {
      $printerName = addslashes($printerInfo->printerName);
      
      $dateTime = Time::toMySqlDate($printerInfo->lastContact);
      
      $isConnected = intval($printerInfo->isConnected);
      
      $query =
      "INSERT INTO printer " .
      "(printerName, model, isConnected, lastContact) " .
      "VALUES " .
      "('$printerName', '$printerInfo->model', '$isConnected', '$dateTime');";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function updatePrinter($printerInfo)
   {
      $printerName = addslashes($printerInfo->printerName);
      
      $dateTime = Time::toMySqlDate($printerInfo->lastContact);
      
      $isConnected = intval($printerInfo->isConnected);

      $query =
      "UPDATE printer " .
      "SET model = '$printerInfo->model', isConnected = '$isConnected', lastContact = '$dateTime' " .
      "WHERE printerName = '$printerName';";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function deletePrinter($printerName)
   {
      $query = "DELETE FROM printer WHERE printerName = '$printerName';";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getPrinters()
   {
      $query = "SELECT * FROM printer;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   // **************************************************************************
   //                                 Print Job
   // **************************************************************************
   
   public function getPrintJob($printJobId)
   {
      $query = "SELECT * FROM printjob WHERE printJobId = $printJobId;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getPrintJobIds()
   {
      $queued = PrintJobStatus::QUEUED;
      $pending = PrintJobStatus::PENDING;
      $printing = PrintJobStatus::PRINTING;
      $statusClause = "WHERE status IN ($queued, $pending, $printing)";
      
      $query = "SELECT printJobId FROM printjob $statusClause ORDER BY dateTime ASC;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function newPrintJob($printJob)
   {
      $printerName = addslashes($printJob->printerName);
      
      $dateTime = Time::toMySqlDate($printJob->dateTime);
      
      $query =
      "INSERT INTO printjob " .
      "(owner, dateTime, description, printerName, copies, status, xml) " . 
      "VAlUES " .
      "('$printJob->owner', '$dateTime', '$printJob->description', '$printerName', '$printJob->copies', '$printJob->status', '$printJob->xml');";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function setPrintJobStatus($printJobId, $status)
   {
      $query = "UPDATE printjob SET status = '$status' WHERE printJobId = $printJobId;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function deletePrintJob($printJobId)
   {
      $query = "DELETE FROM printjob WHERE printJobId = $printJobId;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   // **************************************************************************
   //                             Maintenance Type
   // **************************************************************************
   
   public function getMaintenanceType($typeId)
   {
      $query = "SELECT * FROM maintenancetype WHERE typeId = $typeId;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getMaintenanceTypes()
   {
      $query = "SELECT * FROM maintenancetype ORDER BY label ASC;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function newMaintenanceType($label)
   {
      $query = "INSERT INTO maintenancetype (label) VALUES (\"$label\");";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function updateMaintenanceType($typeId, $label)
   {
      $query = "UPDATE maintenancetype SET label = $label WHERE typeId = typeId;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function deleteMaintenanceType($typeId)
   {
      $UNKNOWN_TYPE_ID = MaintenanceEntry::UNKNOWN_TYPE_ID;
      $UNKNOWN_CATEGORY_ID = MaintenanceEntry::UNKNOWN__CATEGORY_ID;
      $UNKNOWN_SUBCATEGORY_ID = MaintenanceEntry::UNKNOWN_SUBCATEGORY_ID;

      /*   
      // Clean up maintenancesubcategory table.      
      $query = "DELETE FROM maintenancesubcategory INNER JOIN maintenancetype maintenance SET categoryId = $UNKNOWN_SUBCATEGORY_ID\" WHERE typeId = $maintenanceTypeId;";      
      $result = $this->query($query);
      
      // Clean up maintenancecategory table.      
      $query = "UPDATE maintenance SET categoryId = $UNKNOWN_CATEGORY_ID\" WHERE typeId = $maintenanceTypeId;";      
      $result = $this->query($query);
      */
      
      // Clean up maintenancetype table. 
      $query = "DELETE FROM maintenancetype WHERE yypeId = $typeId;"; 
      $result = $this->query($query);

      // Clean up maintenance table.    
      $query = "UPDATE maintenance SET typeId = $UNKNOWN_TYPE_ID, categoryId = $UNKNOWN_CATEGORY_ID, subcategoryId = $UNKNOWN_SUBCATEGORY_ID WHERE typeId = $typeId;";      
      $result = $this->query($query);
      
      return ($result);
   }
   
   // **************************************************************************
   //                             Maintenance Category
   // **************************************************************************
   
   public function getMaintenanceCategory($categoryId)
   {
      $query = "SELECT * FROM maintenancecategory WHERE categoryId = $categoryId;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getMaintenanceCategories($typeId)
   {
      $typeClause = "TRUE";
      if ($typeId != MaintenanceEntry::UNKNOWN_TYPE_ID)
      {
         $typeClause = "typeId = $typeId";
      }
      
      $query = "SELECT * FROM maintenancecategory WHERE $typeClause ORDER BY label ASC;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function newMaintenanceCategory($label)
   {
      $query = "INSERT INTO maintenancecategory (label) VALUES (\"$label\");";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function updateMaintenanceCategory($categoryId, $label)
   {
      $query = "UPDATE maintenancecategory set label = \"$label\" WHERE categoryId = $categoryId);";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function deleteMaintenanceCategory($categoryId)
   {
      $UNKNOWN_CATEGORY_ID = MaintenanceCategory::UNKNOWN_CATEGORY_ID;
      $UNKNOWN_SUBCATEGORY_ID = MaintenanceEntry::UNKNOWN_SUBCATEGORY_ID;
            
      /*   
      // Clean up maintenancesubcategory table.      
      $query = "DELETE FROM maintenancesubcategory INNER JOIN maintenancetype maintenance SET categoryId = $UNKNOWN_SUBCATEGORY_ID\" WHERE categoryId = $categoryId;";      
      $result = $this->query($query);
      */
      
      // Clean up maintenancecategory table.      
      $query = "DELETE FROM maintenancecategory WHERE categoryId = $categoryId;";      
      $result = $this->query($query);

      // Clean up maintenance table.    
      $query = "UPDATE maintenance SET categoryId = $UNKNOWN_CATEGORY_ID, subcategoryId = $UNKNOWN_SUBCATEGORY_ID WHERE categoryId = $categoryId;";      
      $result = $this->query($query);
      
      return ($result);
   }
   
   // **************************************************************************
   //                             Maintenance Subcategory
   // **************************************************************************
   
   public function getMaintenanceSubcategory($subcategoryId)
   {
      $query = "SELECT * FROM maintenancesubcategory WHERE subcategoryId = $subcategoryId;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getMaintenanceSubcategories($categoryId)
   {
      $categoryClause = "TRUE";
      if ($categoryId != MaintenanceEntry::UNKNOWN_CATEGORY_ID)
      {
         $categoryClause = "categoryId = $categoryId";
      }
      
      $query = "SELECT * FROM maintenancesubcategory WHERE $categoryClause ORDER BY label ASC;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function newMaintenanceSubcategory($label)
   {
      $query = "INSERT INTO maintenancesubcategory (label) VALUES (\"$label\");";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function updateMaintenanceSubcategory($subcategoryId, $label)
   {
      $query = "UPDATE maintenancesubcategory set label = \"$label\" WHERE subcategoryId = $subcategoryId);";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function deleteMaintenanceSubcategory($subcategoryId)
   {
      $UNKNOWN_SUBCATEGORY_ID = MaintenanceEntry::UNKNOWN_SUBCATEGORY_ID;
            
      // Clean up maintenancesubcategory table.      
      $query = "DELETE FROM maintenancesubcategory WHERE subcategoryId = $subcategoryId;";      
      $result = $this->query($query);

      // Clean up maintenance table.    
      $query = "UPDATE maintenance SET subcategoryId = $UNKNOWN_SUBCATEGORY_ID WHERE subcategoryId = $subcategoryId;";      
      $result = $this->query($query);
      
      return ($result);
   }   
   
   // **************************************************************************
   //                              Maintenance Log
   // **************************************************************************
   
   public function getMaintenanceEntry($maintenanceEntryId)
   {
      $query = "SELECT * FROM maintenance WHERE maintenanceEntryId = \"$maintenanceEntryId\";";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getMaintenanceEntries(
      $startDate,
      $endDate,
      $employeeNumber,
      $wcNumber,
      $useMaintenanceDate)
   {
      $dateTimeClause = "";
      if ($useMaintenanceDate == true)
      {
         $dateTimeClause = "maintenanceDateTime BETWEEN '" . Time::toMySqlDate($startDate) . "' AND '" . Time::toMySqlDate($endDate) . "'";
      }
      else
      {
         $dateTimeClause = "dateTime BETWEEN '" . Time::toMySqlDate($startDate) . "' AND '" . Time::toMySqlDate($endDate) . "'";
      }
      
      $userClause = "";
      if ($employeeNumber != UserInfo::UNKNOWN_EMPLOYEE_NUMBER)
      {
         $userClause = "AND employeeNumber = $employeeNumber";
      }
      
      $wcClause = "";
      if ($wcNumber != JobInfo::UNKNOWN_WC_NUMBER)
      {
         $wcClause = "AND wcNumber = \"$wcNumber\"";
      }
      
      $query = "SELECT * FROM maintenance WHERE $dateTimeClause $userClause $wcClause ORDER BY maintenanceDateTime DESC;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function newMaintenanceEntry(
      $maintenanceEntry)
   {
      $dateTime = Time::toMySqlDate($maintenanceEntry->dateTime);
      $maintenanceDateTime = Time::toMySqlDate($maintenanceEntry->maintenanceDateTime);
      
      $query =
      "INSERT INTO maintenance " .
      "(dateTime, maintenanceDateTime, employeeNumber, typeId, categoryId, subcategoryId, jobNumber, wcNumber, equipmentId, shiftTime, maintenanceTime, partId, comments) " .
      "VALUES " .
      "('$dateTime', '$maintenanceDateTime', '$maintenanceEntry->employeeNumber', '$maintenanceEntry->typeId', '$maintenanceEntry->categoryId', '$maintenanceEntry->subcategoryId', '$maintenanceEntry->jobNumber', '$maintenanceEntry->wcNumber', '$maintenanceEntry->equipmentId', '$maintenanceEntry->shiftTime', '$maintenanceEntry->maintenanceTime', '$maintenanceEntry->partId', '$maintenanceEntry->comments');";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function updateMaintenanceEntry(
      $maintenanceEntry)
   {
      $dateTime = Time::toMySqlDate($maintenanceEntry->dateTime);
      $maintenanceDateTime = Time::toMySqlDate($maintenanceEntry->maintenanceDateTime);
      
      $query =
      "UPDATE maintenance " .
      "SET dateTime = \"$dateTime\", maintenanceDateTime = \"$maintenanceDateTime\", employeeNumber = $maintenanceEntry->employeeNumber, typeId = $maintenanceEntry->typeId, categoryId = $maintenanceEntry->categoryId, subcategoryId = $maintenanceEntry->subcategoryId, jobNumber = '$maintenanceEntry->jobNumber', wcNumber = $maintenanceEntry->wcNumber, equipmentId = $maintenanceEntry->equipmentId, shiftTime = $maintenanceEntry->shiftTime, maintenanceTime = $maintenanceEntry->maintenanceTime, partId = $maintenanceEntry->partId, comments = \"$maintenanceEntry->comments\" " .
      "WHERE maintenanceEntryId = $maintenanceEntry->maintenanceEntryId;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function deleteMaintenanceEntry(
      $maintenanceEntryId)
   {
      $query = "DELETE FROM maintenance WHERE maintenanceEntryId = $maintenanceEntryId;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   // **************************************************************************
   //                              Part Inventory
   // **************************************************************************
   
   public function getPartInventoryPart($partId)
   {
      $query = "SELECT * FROM partinventory WHERE partId = \"$partId\";";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getPartInventory()
   {
      $query = "SELECT * FROM partinventory ORDER BY partNumber DESC;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function addToPartInventory(
      $machinePartInfo)
   {      
      $query =
      "INSERT INTO partinventory " .
      "(partNumber, description, inventoryCount) " .
      "VALUES " .
      "('$machinePartInfo->partNumber', '$machinePartInfo->description', '$machinePartInfo->inventoryCount');";
      
      $result = $this->query($query);

      return ($result);
   }
   
   public function updatePartInventory(
      $machinePartInfo)
   {
      $query =
      "UPDATE partinventory " .
      "SET partNumber = '$machinePartInfo->partNumber', description = '$machinePartInfo->description', wcNumber = '$machinePartInfo->inventoryCount' " .
      "WHERE partId = $machinePartInfo->partId;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function deleteFromPartInventory(
      $partId)
   {
      $query = "DELETE FROM partinventory WHERE partId = $partId;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   // **************************************************************************
   //                                    ISO
   // **************************************************************************
   
   public function getIso($isoDoc)
   {
      $query = "SELECT * FROM iso WHERE isoDoc = \"$isoDoc\";";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   // **************************************************************************
   //                                 Material
   // **************************************************************************
   
   public function getMaterials($materialType = MaterialType::UNKNOWN)
   {
      $materialTypeClause = "";
      if ($materialType != MaterialType::UNKNOWN)
      {
         $materialTypeClause = "WHERE materialType = $materialType";
      }
      
      $query = "SELECT * FROM material $materialTypeClause ORDER BY partNumber ASC;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getMaterial($materialId)
   {
      $query = "SELECT * FROM material WHERE materialId = $materialId;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   // **************************************************************************
   //                             Material Vendor
   // **************************************************************************
   
   public function getMaterialVendors()
   {
      $query = "SELECT * FROM materialvendor;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getMaterialVendor($vendorId)
   {
      $query = "SELECT * FROM materialvendor WHERE vendorId = $vendorId;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   // **************************************************************************
   //                                Material Heat
   // **************************************************************************
   
   public function getMaterialHeat($heatNumber, $useInternalHeatNumber = false)
   {
      $heatClause = "";
      if ($useInternalHeatNumber)
      {
         $heatClause = "WHERE internalHeatNumber = $heatNumber";
      }
      else
      {
         $heatClause = "WHERE vendorHeatNumber = '$heatNumber'";
      }
   
      $query = "SELECT * FROM materialheat $heatClause;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function newMaterialHeat($materialHeatInfo)
   {
      $query =
         "INSERT INTO materialheat " .
         "(vendorHeatNumber, internalHeatNumber, materialId, vendorId) " .
         "VALUES " .
         "('$materialHeatInfo->vendorHeatNumber', '$materialHeatInfo->internalHeatNumber', '$materialHeatInfo->materialId', '$materialHeatInfo->vendorId');";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function updateMaterialHeat($materialHeatInfo)
   {
      $query =
         "UPDATE materialheat " .
         "SET internalHeatNumber = $materialHeatInfo->internalHeatNumber, materialId = $materialHeatInfo->materialId, vendorId = $materialHeatInfo->vendorId " .
         "WHERE vendorHeatNumber = '$materialHeatInfo->vendorHeatNumber';";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function deleteMaterialHeat($heatNumber, $useInternalHeatNumber = false)
   {
      $heatClause = "";
      if ($useInternalHeatNumber)
      {
         $heatClause = "WHERE internalHeatNumber = $heatNumber";
      }
      else
      {
         $heatClause = "WHERE vendorHeatNumber = '$heatNumber'";
      }
      
      $query = "DELETE FROM materialheat $heatClause;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getNextInternalHeatNumber()
   {
      $internalHeatNumber = 0;
      
      $query = "select MAX(internalHeatNumber) from materialheat";
      
      $result = $this->query($query);
      
      if ($result && ($row = $result->fetch_assoc()))
      {
         $internalHeatNumber = intval($row["MAX(internalHeatNumber)"]) + 1;
      }
      
      return ($internalHeatNumber);
   }
      
   // **************************************************************************
   //                                Material Log
   // **************************************************************************
   
   public function getMaterialEntry($materialEntryId)
   {
      $query = "SELECT * FROM materiallog WHERE materialEntryId = $materialEntryId";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getMaterialEntries($materialEntryStatus, $startDate, $endDate, $useReceiveDate = false)
   {
      $statusClause = "";
      if ($materialEntryStatus == MaterialEntryStatus::RECEIVED)
      {
         $statusClause = "AND issuedDateTime IS NULL";
      }
      else if ($materialEntryStatus == MaterialEntryStatus::ISSUED)
      {
         $statusClause = "AND issuedDateTime IS NOT NULL AND acknowledgedDateTime IS NULL";
      }
      else if ($materialEntryStatus == MaterialEntryStatus::ACKNOWLEDGED)
      {
         $statusClause = "AND acknowledgedDateTime IS NOT NULL";
      }

      $dateField = ($useReceiveDate ? "receivedDateTime" : "enteredDateTime");
      $dateTimeClause = "$dateField BETWEEN '" . Time::toMySqlDate($startDate) . "' AND '" . Time::toMySqlDate($endDate) . "'";
            
      $query = "SELECT * FROM materiallog WHERE $dateTimeClause $statusClause ORDER BY receivedDateTime DESC;";

      $result = $this->query($query);

      return ($result);
   }
   
   public function newMaterialEntry($materialEntry)
   {
      $enteredDateTime = $materialEntry->enteredDateTime ? Time::toMySqlDate($materialEntry->enteredDateTime) : null;
      
      $receivedDateTime = $materialEntry->receivedDateTime ? Time::toMySqlDate($materialEntry->receivedDateTime) : null;
      
      $query =
         "INSERT INTO materiallog " .
         "(vendorHeatNumber, tagNumber, location, pieces, enteredUserId, enteredDateTime, receivedDateTime) " .
         "VALUES " .
         "('$materialEntry->vendorHeatNumber', '$materialEntry->tagNumber', '$materialEntry->location', '$materialEntry->pieces', '$materialEntry->enteredUserId', '$enteredDateTime', '$receivedDateTime');";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function updateMaterialEntry($materialEntry)
   {
      $enteredDateTime = $materialEntry->enteredDateTime ? Time::toMySqlDate($materialEntry->enteredDateTime) : null;
      
      $receivedDateTime = $materialEntry->receivedDateTime ? Time::toMySqlDate($materialEntry->receivedDateTime) : null;
      
      $query =
         "UPDATE materiallog " .
         "SET vendorHeatNumber = '$materialEntry->vendorHeatNumber', tagNumber = '$materialEntry->tagNumber', location = $materialEntry->location, pieces = $materialEntry->pieces, enteredUserId = $materialEntry->enteredUserId, enteredDateTime = '$enteredDateTime', receivedDateTime = '$receivedDateTime' " .
         "WHERE materialEntryId = $materialEntry->materialEntryId;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function issueMaterial($materialEntry)
   {
      $issuedDateTime = $materialEntry->issuedDateTime ? Time::toMySqlDate($materialEntry->issuedDateTime) : null;

      $dateTimeClause = "";
      if ($issuedDateTime)
      {
         $dateTimeClause = ", issuedDateTime = \"$issuedDateTime\"";
      }
      else 
      {
         $dateTimeClause = ", issuedDateTime = NULL";
      }
      
      $query =
         "UPDATE materiallog " .
         "SET issuedJobId = $materialEntry->issuedJobId, issuedUserId = $materialEntry->issuedUserId$dateTimeClause " .
         "WHERE materialEntryId = $materialEntry->materialEntryId;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function acknowledgeIssuedMaterial($materialEntry)
   {
      $acknowledgedDateTime = $materialEntry->acknowledgedDateTime ? Time::toMySqlDate($materialEntry->acknowledgedDateTime) : null;
      
      $dateTimeClause = "";
      if ($acknowledgedDateTime)
      {
         $dateTimeClause = ", acknowledgedDateTime = \"$acknowledgedDateTime\"";
      }
      else
      {
         $dateTimeClause = ", acknowledgedDateTime = NULL";
      }
      
      $query =
         "UPDATE materiallog " .
         "SET acknowledgedUserId = $materialEntry->acknowledgedUserId$dateTimeClause " .
         "WHERE materialEntryId = $materialEntry->materialEntryId;";

      $result = $this->query($query);
      
      return ($result);
   }
   
   public function deleteMaterialEntry($materialEntryId)
   {
      $query = "DELETE FROM materiallog WHERE materialEntryId = $materialEntryId;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   // **************************************************************************
   //                                Cron Job
   // **************************************************************************
   
   public function getCronJob($jobId)
   {
      $query = "SELECT * FROM cronjob WHERE jobId = $jobId;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getCronJobs()
   {
      $query = "SELECT * FROM cronjob ORDER BY jobId ASC;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function addCronJob($job)
   {
      $lastRun = $job->lastRun ? Time::toMySqlDate($job->lastRun) : null;
      
      $isEnabled = $job->isEnabled ? 1 : 0;
      
      $config = mysqli::real_escape_string(json_encode($job->config));
      $status = mysqli::real_escape_string(json_encode($job->status));
      
      $query = 
         "INSERT INTO cronjob " .
         "(jobName, jobClass, description, isEnabled, lastRun, jobPeriod, hour, day, month, config, status) " .
         "VALUES ('$job->jobName', '$job->jobClass', '$job->description', $isEnabled, '$lastRun', $job->jobPeriod, $job->hour, $job->day, $job->month, '$config', '$status')";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function updateCronJob($job)
   {
      $lastRun = $job->lastRun ? Time::toMySqlDate($job->lastRun) : null;
      
      $isEnabled = $job->isEnabled ? 1 : 0;
      
      $config = mysqli_real_escape_string($this->getConnection(), json_encode($job->config));
      $status = mysqli_real_escape_string($this->getConnection(), json_encode($job->status));
                  
      $query = 
         "UPDATE cronjob " .
         "SET jobName = '$job->jobName', jobClass = '$job->jobClass', description = '$job->description', isEnabled = $isEnabled, lastRun = '$lastRun', jobPeriod = $job->jobPeriod, hour = $job->hour, day = $job->day, month = $job->month, config = '$config', status = '$status' " .
         "WHERE jobId = $job->jobId";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function deleteCronJob($jobId)
   {
      $query = $this->pdo->prepare("DELETE FROM job WHERE jobId = $job->jobId");
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   // **************************************************************************
   //                                  Private
   // **************************************************************************
   
   private function checkForNewSensor($sensorId)
   {
      $result = $this->query("SELECT * FROM sensor WHERE sensorId = \"$sensorId\";");
      
      if (mysqli_num_rows($result) == 0)
      {
         $query =
         "INSERT INTO sensor " .
         "(sensorId, lastContact, partCount, resetTime) " .
         "VALUES (\"$sensorId\", NOW(), 0, NOW());";
         
         $this->query($query);
      }
   }
   
   private function updatePartCount_Hour($sensorId, $partCount)
   {
   }
   
   private function updatePartCount_Day($sensorId, $partCount)
   {
      
   }
   
   private function updatePartCount_Shift($sensorId, $partCount)
   {
      
   }
   
   private static $databaseInstance = null;
}

?>
