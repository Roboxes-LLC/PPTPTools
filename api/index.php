<?php

if (!defined('ROOT')) require_once '../root.php';

require_once 'rest.php';
require_once '../common/authentication.php';
require_once '../common/dailySummaryReport.php';
require_once '../common/filterDateType.php';
require_once '../common/inspection.php';
require_once '../common/inspectionTemplate.php';
require_once '../common/jobInfo.php';
require_once '../common/maintenanceEntry.php';
require_once '../common/materialEntry.php';
require_once '../common/materialTicket.php';
require_once '../common/oasisReport/oasisReport.php';
require_once '../common/panTicket.php';
require_once '../common/partWasherEntry.php';
require_once '../common/partWeightEntry.php';
require_once '../common/printerInfo.php';
require_once '../common/quarterlySummaryReport.php';
require_once '../common/root.php';
require_once '../common/signInfo.php';
require_once '../common/shippingCardInfo.php';
require_once '../common/shipmentTicket.php';
require_once '../common/timeCardInfo.php';
require_once '../common/upload.php';
require_once '../common/userInfo.php';
require_once '../common/weeklySummaryReport.php';
require_once '../core/job/cronJobManager.php';
require_once '../core/manager/inspectionManager.php';
require_once '../core/manager/jobManager.php';
require_once '../core/manager/notificationManager.php';
require_once '../core/manager/shipmentManager.php';
require_once '../inspection/inspectionTable.php';
require_once '../printer/printJob.php';
require_once '../printer/printQueue.php';

// *****************************************************************************
//                                   Begin

session_start();

$router = new Router();
$router->setLogging(false);

$router->add("ping", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   echo json_encode($result);
});

$router->add("setSession", function($params) {
   $result = new stdClass();
   $result->success = false;
   
   if (isset($params["key"]) &&
       isset($params["value"]))
   {
      $_SESSION[$params["key"]] = $params["value"];
      
      $result->key = $params["key"];
      $result->value = $params["value"];
      $result->success = true;
   }
   else
   {
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("getSession", function($params) {
   $result = new stdClass();
   $result->success = false;
   
   if (isset($params["key"]))
   {
      if (isset($_SESSION[$params["key"]]))
      {
         $result->key = $params["key"];
         $result->value = $_SESSION[$params["key"]];
         $result->success = true;
      }
      else
      {
         $result->key = $params["key"];
         $result->error = "Undefined session key.";
      }
   }
   else
   {
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("timeCardData", function($params) {
   $result = array();
   
   $dateType = FilterDateType::ENTRY_DATE;
   $startDate = Time::startOfDay(Time::now("Y-m-d"));
   $endDate = Time::endOfDay(Time::now("Y-m-d"));
   
   if (isset($params["filters"]))
   {
      foreach ($params["filters"] as $filter)
      {
         if ($filter->field == "date")
         {
            if ($filter->type == ">=")
            {
               $startDate = Time::startOfDay($filter->value);
            }
            else if ($filter->type == "<=")
            {
               $endDate = Time::endOfDay($filter->value);
            }
         }
      }
   }
   
   if (isset($params["dateType"]))
   {
      $dateType = $params->getInt("dateType");
   }
   
   if (isset($params["startDate"]))
   {
      $startDate = Time::startOfDay($params["startDate"]);
   }
   
   if (isset($params["endDate"]))
   {
      $endDate = Time::endOfDay($params["endDate"]);
   }
   
   $employeeNumberFilter = 
      (Authentication::checkPermissions(Permission::VIEW_OTHER_USERS)) ? 
         UserInfo::UNKNOWN_EMPLOYEE_NUMBER :                      // No filter
         Authentication::getAuthenticatedUser()->employeeNumber;  // Filter on authenticated user
   
   $database = PPTPDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $timeCards = $database->getTimeCards($employeeNumberFilter, $startDate, $endDate, ($dateType == FilterDateType::MANUFACTURING_DATE));
      
      // Populate data table.
      foreach ($timeCards as $timeCard)
      {
         $timeCardInfo = TimeCardInfo::load($timeCard["timeCardId"]);
         if ($timeCardInfo)
         {
            $timeCard["panTicketCode"] = PanTicket::getPanTicketCode($timeCardInfo->timeCardId);            
            $timeCard["efficiency"] = round(($timeCardInfo->getEfficiency() * 100), 2);
         
            $userInfo = UserInfo::load($timeCard["employeeNumber"]);
            if ($userInfo)
            {
               $timeCard["operator"] = $userInfo->getFullName() . " (" . $timeCard["employeeNumber"] . ")";
            }
            
            $jobInfo = JobInfo::load($timeCard["jobId"]);
            if ($jobInfo)
            {
               $timeCard["jobNumber"] = $jobInfo->jobNumber;
               $timeCard["wcNumber"] = $jobInfo->wcNumber;
               $timeCard["wcLabel"] = JobInfo::getWcLabel($jobInfo->wcNumber);            
            }
            
            $timeCard["isNew"] = Time::isNew($timeCardInfo->dateTime, Time::NEW_THRESHOLD);
            $timeCard["incompleteShiftTime"] = $timeCardInfo->incompleteShiftTime();
            $timeCard["incompleteRunTime"] = $timeCardInfo->incompleteRunTime();
            $timeCard["incompletePanCount"] = $timeCardInfo->incompletePanCount();
            $timeCard["incompletePartCount"] = $timeCardInfo->incompletePartCount();
            
            $timeCard["runTimeRequiresApproval"] = $timeCardInfo->requiresRunTimeApproval();
            $userInfo = UserInfo::load($timeCardInfo->runTimeApprovedBy);
            if ($userInfo)
            {
               $timeCard["runTimeApprovedByName"] = $userInfo->getFullName();
            }
            
            $timeCard["setupTimeRequiresApproval"] = $timeCardInfo->requiresSetupTimeApproval();
            $userInfo = UserInfo::load($timeCardInfo->setupTimeApprovedBy);
            if ($userInfo)
            {
               $timeCard["setupTimeApprovedByName"] = $userInfo->getFullName();
            }
            
            $timeCard["partsTakenEarly"] = $timeCardInfo->hasCommentCode(CommentCode::PARTS_TAKEN_EARLY_CODE_ID);
                     
            $result[] = $timeCard;
         }
      }
   }

   echo json_encode($result);
});

$router->add("timeCardInfo", function($params) {
   $result = new stdClass();

   if (isset($params["timeCardId"]) ||
       isset($params["panTicketCode"]) ||
       (isset($params["jobNumber"]) &&
        isset($params["wcNumber"]) && 
        isset($params["operator"]) &&
        isset($params["manufactureDate"])))             
   {
      $result->timeCardId = TimeCardInfo::UNKNOWN_TIME_CARD_ID;
      
      // Look up by time card id
      if (isset($params["timeCardId"]))
      {
         $result->timeCardId = intval($params["timeCardId"]);         
      }
      // Look up by pan ticket code
      else if (isset($params["panTicketCode"]))
      {
         $result->timeCardId = PanTicket::getPanTicketId($params["panTicketCode"]);  
      }
      // Look up by time card components
      else
      {
         $jobNumber = $params["jobNumber"];
         $wcNumber = intval($params["wcNumber"]);
         $employeeNumber = intval($params["operator"]);
         $manufactureDate = Time::startOfDay($params->get("manufactureDate"));
         
         $jobId = JobInfo::getJobIdByComponents($jobNumber, $wcNumber);
         
         if ($jobId != JobInfo::UNKNOWN_JOB_ID)
         {
            $result->timeCardId = TimeCardInfo::matchTimeCard($jobId, $employeeNumber, $manufactureDate);
         }
      }
      
      $timeCardInfo = TimeCardInfo::load($result->timeCardId);
      
      if ($timeCardInfo)
      {
         $result->success = true;
         $result->timeCardInfo = $timeCardInfo;
         
         if ($params->getBool("expandedProperties"))
         {
            $result->isComplete = ($timeCardInfo->isComplete());
            $result->panTicketCode = PanTicket::getPanTicketCode($result->timeCardId);
            
            $jobInfo = JobInfo::load($timeCardInfo->jobId);
            
            if ($jobInfo)
            {
               $result->jobNumber = $jobInfo->jobNumber;
               $result->wcNumber = $jobInfo->wcNumber;
               $result->wcLabel = JobInfo::getWcLabel($jobInfo->wcNumber);
               $result->sampleWeight = $jobInfo->part->sampleWeight;
               $result->isActiveJob = ($jobInfo->status == JobStatus::ACTIVE);
            }
            
            $userInfo = UserInfo::load($timeCardInfo->employeeNumber);
            
            if ($userInfo)
            {
               $result->operatorName = $userInfo->getFullName();
            }
         }
      }
      else
      {
         $result->success = false;
         $result->error = "No matching time card.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "No time card ID specified.";
   }
   
   echo json_encode($result);
});

$router->add("jobInfo", function($params) {
   $result = new stdClass();
   
   $jobInfo = null;
   
   if (isset($params["jobId"]))
   {
      $jobInfo = JobInfo::Load(intval($params["jobId"]));
   }
   else if ((isset($params["jobNumber"])) &&
            (isset($params["wcNumber"])))
   {
      $jobNumber = $params["jobNumber"];
      $wcNumber = intval($params["wcNumber"]);
      
      $jobId = JobInfo::getJobIdByComponents($jobNumber, $wcNumber);
      
      if ($jobId != JobInfo::UNKNOWN_JOB_ID)
      {
         $jobInfo = JobInfo::load($jobId);   
         
         if (!$jobInfo)
         {
            $result->success = false;
            $result->error = "Failed to look up job from components.";
         }
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   if ($jobInfo)
   {
      $result->success = true;
      $result->jobInfo = $jobInfo;
   }
   
   echo json_encode($result);
});

$router->add("jobs", function($params) {
   $result = new stdClass();
   
   $result->success = true;
   $result->jobs = JobInfo::getJobNumbers(true);  // only active
   
   echo json_encode($result);
});

$router->add("wcNumbers", function($params) {
   $result = new stdClass();
   
   $database = PPTPDatabase::getInstance();
   $dbaseResult = null;
   
   if (isset($params["jobNumber"]))
   {
      $dbaseResult = $database->getWorkCentersForJob($params["jobNumber"]);
   }
   else
   {
      $dbaseResult = $database->getWorkCenters();
   }
   
   if ($dbaseResult)
   {
      $result->success = true;
      $result->wcNumbers = array();
      
      while ($row = $dbaseResult->fetch_assoc())
      {
         $wcNumber = intval($row["wcNumber"]);
         $label = JobInfo::getWcLabel($wcNumber);
         $result->wcNumbers[] = (object)array("wcNumber" => $wcNumber, "label" => $label);
      }
   }
   else
   {
      $result->status = false;
      $result->error = "No work centers found.";
   }
   
   echo json_encode($result);
});

$router->add("jobNumbers", function($params) {
   $result = new stdClass();
   
   $database = PPTPDatabase::getInstance();
   $dbaseResult = null;
   
   if (isset($params["wcNumber"]))
   {
      $dbaseResult = $database->getActiveJobs(intval($params["wcNumber"]));
   }
   else
   {
      $dbaseResult = $database->getActiveJobs();
   }
   
   if ($dbaseResult)
   {
      $result->success = true;
      $result->jobNumbers = array();
      
      while ($row = $dbaseResult->fetch_assoc())
      {
         $result->jobNumbers[] = $row["jobNumber"];
      }
   }
   else
   {
      $result->status = false;
      $result->error = "No work centers found.";
   }
   
   echo json_encode($result);
});

$router->add("user", function($params) {
   $result = new stdClass();
      
   if (isset($params["employeeNumber"]))
   {
      $userInfo = UserInfo::load(intval($params["employeeNumber"]));
      
      if ($userInfo)
      {
         $result->success = true;
         $result->user = $userInfo;
         
         // Augment data.
         $result->user->fullName = $userInfo->getFullName();
      }
      else
      {
         $result->success = false;
         $result->error = "No user found.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("users", function($params) {
   $result = new stdClass();
   
   $database = PPTPDatabase::getInstance();
   $dbaseResult = null;
   
   if (isset($params["role"]))
   {
      $dbaseResult = $database->getUsersByRole(intval($params["role"]));
   }
   else
   {
      $dbaseResult = $database->getUsers();
   }
   
   if ($dbaseResult)
   {
      $result->success = true;
      $result->operators = array();
      
      while ($row = $dbaseResult->fetch_assoc())
      {
         $userInfo = UserInfo::load($row["employeeNumber"]);
         
         if ($userInfo)
         {
            $operatorInfo = new stdClass();
            $operatorInfo->employeeNumber = $userInfo->employeeNumber;
            $operatorInfo->name = $userInfo->getFullName();
            $result->operators[] = $operatorInfo;
         }
      }
   }
   else
   {
      $result->status = false;
      $result->error = "No users found.";
   }
   
   echo json_encode($result);
});

$router->add("userData", function($params) {
   $result = array();
   
   $database = PPTPDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $users = $database->getUsers();
      
      // Populate data table.
      foreach ($users as $user)
      {
         $userInfo = UserInfo::load($user["employeeNumber"]);
         if ($userInfo)
         {
            $user["name"] = $userInfo->getFullName();
         }
         
         $user["roleLabel"] = Role::getRole(intval($user["roles"]))->roleName;
         
         $result[] = $user;
      }
   }
   
   echo json_encode($result);
});

$router->add("saveUser", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   $dbaseResult = null;
      
   if (isset($params["employeeNumber"]) &&
       isset($params["firstName"]) &&
       isset($params["lastName"]) &&
       isset($params["email"]) &&
       isset($params["roles"]) &&
       isset($params["username"]) &&
       isset($params["password"]) &&
       isset($params["defaultShiftHours"]))
   {
      $employeeNumber = intval($params["employeeNumber"]);
         
      $newUser = false;
      $userInfo = UserInfo::load($employeeNumber);
      
      if (!$userInfo)
      {
         $newUser = true;
         $userInfo = new UserInfo();
      }
      
      $userInfo->employeeNumber = $employeeNumber;
      $userInfo->firstName = $params["firstName"];
      $userInfo->lastName = $params["lastName"];
      $userInfo->email = $params["email"];
      $userInfo->roles = intval($params["roles"]);
      $userInfo->username = $params["username"];
      $userInfo->password = $params["password"];
      $userInfo->authToken = $params["authToken"];
      $userInfo->defaultShiftHours = $params["defaultShiftHours"];
      
      foreach (Permission::getPermissions() as $permission)
      {
         $name = "permission-" . $permission->permissionId;
         
         if (isset($params[$name]))
         {
            // Set bit.
            $userInfo->permissions |= $permission->bits;
         }
         else if ($permission->isSetIn($userInfo->permissions))
         {
            // Clear bit.
            $userInfo->permissions &= ~($permission->bits);
         }
      }
      
      foreach (Notification::getNotifications() as $notification)
      {
         if (isset($params[$notification->getInputName()]))
         {
            // Set bit.
            $userInfo->notifications |= $notification->bits;
         }
         else if ($notification->isSetIn($userInfo->notifications))
         {
            // Clear bit.
            $userInfo->notifications &= ~($notification->bits);
         }
      }
      
      if ($newUser)
      {
         $dbaseResult = $database->newUser($userInfo);
      }
      else
      {
         $dbaseResult = $database->updateUser($userInfo);
      }
      
      if ($dbaseResult)
      {
         $result->userInfo = $userInfo;
      }
      else
      {
         $result->success = false;
         $result->error = "Database query failed.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("deleteUser", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   
   if (isset($params["employeeNumber"]) &&
       is_numeric($params["employeeNumber"]) &&
      (intval($params["employeeNumber"]) != UserInfo::UNKNOWN_EMPLOYEE_NUMBER))
   {
      $employeeNumber = intval($params["employeeNumber"]);
      
      $userInfo = UserInfo::load($employeeNumber);
      
      if ($userInfo)
      {
         $dbaseResult = $database->deleteUser($employeeNumber);
         
         if ($dbaseResult)
         {
            $result->success = true;
         }
         else
         {
            $result->success = false;
            $result->error = "Database query failed.";
         }
      }
      else
      {
         $result->success = false;
         $result->error = "No existing user found.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("grossPartsPerHour", function($params) {
   $result = new stdClass();
   
   if (isset($params["jobNumber"]) &&
       isset($params["wcNumber"]))
   {
      $jobInfo = null;
      
      $jobId = JobInfo::getJobIdByComponents($params->get("jobNumber"), $params->getInt("wcNumber"));
      
      if ($jobId != JobInfo::UNKNOWN_JOB_ID)
      {
         $jobInfo = JobInfo::load($jobId);
      }
      
      if ($jobInfo)
      {
         $result->success = true;
         $result->grossPartsPerHour = $jobInfo->grossPartsPerHour;
      }
      else
      {
         $result->success = false;
         $result->error = "Failed to lookup job ID.";
         $result->jobNumber = $params->get("jobNumber");
         $result->wcNumber = $params->getInt("wcNumber");
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("saveTimeCard", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   $dbaseResult = null;
   
   $timeCardInfo = null;
   
   if (isset($params["timeCardId"]) &&
       is_numeric($params["timeCardId"]) &&
       (intval($params["timeCardId"]) != TimeCardInfo::UNKNOWN_TIME_CARD_ID))
   {
      $timeCardId = intval($params["timeCardId"]);
      
      //  Updated entry
      $timeCardInfo = TimeCardInfo::load($timeCardId);
      
      if (!$timeCardInfo)
      {
         $result->success = false;
         $result->error = "No existing part weight entry found.";
      }
   }
   else
   {
      // New time card.
      $timeCardInfo = new TimeCardInfo();
      
      // Use current date/time as time card time.
      $timeCardInfo->dateTime = Time::now("Y-m-d h:i:s A");
   }
   
   if ($result->success)
   {
      if (isset($params["operator"]) &&
          isset($params["manufactureDate"]) &&
          isset($params["jobNumber"]) &&
          isset($params["wcNumber"]) &&
          isset($params["materialNumber"]) &&
          isset($params["shiftTime"]) &&            
          isset($params["setupTime"]) &&
          isset($params["runTimeApprovedBy"]) &&
          isset($params["setupTimeApprovedBy"]) &&
          isset($params["runTime"]) &&
          isset($params["panCount"]) &&
          isset($params["partCount"]) &&
          isset($params["scrapCount"]) &&
          isset($params["comments"]))
      {
         $jobId = JobInfo::getJobIdByComponents($params->get("jobNumber"), $params->getInt("wcNumber"));
         
         if ($jobId != JobInfo::UNKNOWN_JOB_ID)
         {
            $timeCardInfo->employeeNumber = intval($params["operator"]);
            $timeCardInfo->manufactureDate = Time::startOfDay($params->get("manufactureDate"));
            $timeCardInfo->jobId = $jobId;
            $timeCardInfo->materialNumber = intval($params["materialNumber"]);
            $timeCardInfo->shiftTime = intval($params["shiftTime"]);
            $timeCardInfo->setupTime = intval($params["setupTime"]);
            $timeCardInfo->setupTimeApprovedBy = intval($params["setupTimeApprovedBy"]);
            $timeCardInfo->runTime = intval($params["runTime"]);
            $timeCardInfo->runTimeApprovedBy = intval($params["runTimeApprovedBy"]);            
            $timeCardInfo->panCount = intval($params["panCount"]);
            $timeCardInfo->partCount = intval($params["partCount"]);
            $timeCardInfo->scrapCount = intval($params["scrapCount"]);
            $timeCardInfo->comments = $params["comments"];
            
            $commentCodes = CommentCode::getCommentCodes();
            
            foreach ($commentCodes as $commentCode)
            {
               $code = $commentCode->code;
               $name = "code-" . $code;
               
               if (isset($params[$name]))
               {
                  $timeCardInfo->setCommentCode($code);
               }
               else
               {
                  $timeCardInfo->clearCommentCode($code);
               }
            }

            // Check for unique time card.
            if (($timeCardInfo->timeCardId == TimeCardInfo::UNKNOWN_TIME_CARD_ID) &&
                (!TimeCardInfo::isUniqueTimeCard(
                     $timeCardInfo->jobId, 
                     $timeCardInfo->employeeNumber, 
                     $timeCardInfo->manufactureDate)))
            {
               $result->success = false;
               $result->error = "Duplicate time card.";
            }
            else
            {
               $result->success = TimeCardInfo::save($timeCardInfo);
               
               if ($result->success)
               {
                  $result->timeCardId = $timeCardInfo->timeCardId;
               }
               else
               {
                  $result->error = "Database query failed.";
               }
            }
         }
         else
         {
            $result->success = false;
            $result->error = "Failed to lookup job ID.";
         }
      }
      else
      {
         $result->success = false;
         $result->error = "Missing parameters.";
      }
   }
   
   echo json_encode($result);
});

$router->add("deleteTimeCard", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   
   if (isset($params["timeCardId"]) &&
       is_numeric($params["timeCardId"]) &&
       (intval($params["timeCardId"]) != TimeCardInfo::UNKNOWN_TIME_CARD_ID))
   {
      $timeCardId = intval($params["timeCardId"]);
      
      $timeCardInfo = TimeCardInfo::load($timeCardId);
      
      if ($timeCardInfo)
      {
         $dbaseResult = $database->deleteTimeCard($timeCardId);
         
         if ($dbaseResult)
         {
            $result->success = true;
         }
         else
         {
            $result->success = false;
            $result->error = "Database query failed.";
         }
      }
      else
      {
         $result->success = false;
         $result->error = "No existing time card found.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("approveRunTime", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   
   if (isset($params["timeCardId"]) &&
       isset($params["isApproved"]))
   {
      $timeCardId = intval($params["timeCardId"]);
      $isApproved = filter_var($params["isApproved"], FILTER_VALIDATE_BOOLEAN);
      
      $timeCardInfo = TimeCardInfo::load($timeCardId);
      
      if ($timeCardInfo)
      {
         if ($timeCardInfo->requiresRunTimeApproval())
         {
            if ($isApproved)
            {
               $timeCardInfo->runTimeApprovedBy = Authentication::getAuthenticatedUser()->employeeNumber;
               $timeCardInfo->runTimeApprovedDateTime = Time::now("Y-m-d H:i:s");
            }
            else
            {
               $timeCardInfo->runTimeApprovedBy = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
               $timeCardInfo->runTimeApprovedDateTime = null;
            }
            
            if ($database->updateTimeCard($timeCardInfo))
            {
               $result->timeCardId = $timeCardInfo->timeCardId;
               $result->runTime = $timeCardInfo->runTime;
               if ($isApproved)
               {
                  $result->runTimeApprovedBy = $timeCardInfo->runTimeApprovedBy;
                  $result->runTimeApprovedByName = Authentication::getAuthenticatedUser()->getFullName();
               }
            }
            else
            {
               $result->success = false;
               $result->error = "Database query failed.";
            }
         }
         else
         {
            $result->success = false;
            $result->error = "No approval required.";
         }
      }
      else
      {
         $result->success = false;
         $result->error = "No existing time card found.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("approveSetupTime", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   
   if (isset($params["timeCardId"]) &&
       isset($params["isApproved"]))
   {
      $timeCardId = intval($params["timeCardId"]);
      $isApproved = filter_var($params["isApproved"], FILTER_VALIDATE_BOOLEAN);
      
      $timeCardInfo = TimeCardInfo::load($timeCardId);
      
      if ($timeCardInfo)
      {
         if ($timeCardInfo->requiresSetupTimeApproval())
         {
            if ($isApproved)
            {
               $timeCardInfo->setupTimeApprovedBy = Authentication::getAuthenticatedUser()->employeeNumber;
               $timeCardInfo->setupTimeApprovedDateTime = Time::now("Y-m-d H:i:s");
            }
            else
            {
               $timeCardInfo->setupTimeApprovedBy = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
               $timeCardInfo->setupTimeApprovedDateTime = null;
            }
            
            if ($database->updateTimeCard($timeCardInfo))
            {
               $result->timeCardId = $timeCardInfo->timeCardId;
               $result->setupTime = $timeCardInfo->setupTime;
               if ($isApproved)
               {
                  $result->setupTimeApprovedBy = $timeCardInfo->setupTimeApprovedBy;
                  $result->setupTimeApprovedByName = Authentication::getAuthenticatedUser()->getFullName();
               }
            }
            else
            {
               $result->success = false;
               $result->error = "Database query failed.";
            }
         }
         else
         {
            $result->success = false;
            $result->error = "No approval required.";
         }
      }
      else
      {
         $result->success = false;
         $result->error = "No existing time card found.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("partWasherLogData", function($params) {
   $result = array();
   
   $timeCardId = TimeCardInfo::UNKNOWN_TIME_CARD_ID;
   $dateType = FilterDateType::ENTRY_DATE;
   $startDate = Time::startOfDay(Time::now("Y-m-d"));
   $endDate = Time::endOfDay(Time::now("Y-m-d"));
   
   if (isset($params["timeCardId"]))
   {
      $timeCardId = $params->getInt("timeCardId");
   }
   
   if (isset($params["dateType"]))
   {
      $dateType = $params->getInt("dateType");
   }
   
   if (isset($params["startDate"]))
   {
      $startDate = Time::startOfDay($params["startDate"]);
   }
   
   if (isset($params["endDate"]))
   {
      $endDate = Time::endOfDay($params["endDate"]);
   }
   
   $database = PPTPDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $databaseResult = null;
      if ($timeCardId != TimeCardInfo::UNKNOWN_TIME_CARD_ID)
      {
         $databaseResult = $database->getPartWasherEntriesByTimeCard($timeCardId);
      }
      else
      {
         $databaseResult = $database->getPartWasherEntries(JobInfo::UNKNOWN_JOB_ID, UserInfo::UNKNOWN_EMPLOYEE_NUMBER, $startDate, $endDate, ($dateType == FilterDateType::MANUFACTURING_DATE));
      }
      
      // Populate data table.
      foreach ($databaseResult as $row)
      {
         $partWasherEntry = new PartWasherEntry();
         $partWasherEntry->initializeFromDatabaseRow($row);
         
         $userInfo = UserInfo::load($partWasherEntry->employeeNumber);
         if ($userInfo)
         {
            $partWasherEntry->washerName = $userInfo->getFullName() .  " (" . $partWasherEntry->employeeNumber . ")";
         }

         $jobId = $partWasherEntry->jobId;
         
         $operator = $partWasherEntry->operator;
         
         $partWasherEntry->timeCardId = $partWasherEntry->timeCardId;
         
         $partWasherEntry->panTicketCode =
            ($partWasherEntry->timeCardId == TimeCardInfo::UNKNOWN_TIME_CARD_ID) ?
               "0000" :
               PanTicket::getPanTicketCode($partWasherEntry->timeCardId);    
         
         if ($partWasherEntry->timeCardId)
         {
            $timeCardInfo = TimeCardInfo::load($partWasherEntry->timeCardId);
            
            if ($timeCardInfo)
            {
               $partWasherEntry->panTicketCode = PanTicket::getPanTicketCode($timeCardInfo->timeCardId);               
               
               $jobId = $timeCardInfo->jobId;
               
               $operator = $timeCardInfo->employeeNumber;
               
               $partWasherEntry->manufactureDate = $timeCardInfo->manufactureDate;
            }
         }
         
         $jobInfo = JobInfo::load($jobId);
         if ($jobInfo)
         {
            $partWasherEntry->jobNumber = $jobInfo->jobNumber;
            $partWasherEntry->wcNumber = $jobInfo->wcNumber;
            $partWasherEntry->wcLabel = JobInfo::getWcLabel($jobInfo->wcNumber);
         }
         
         $userInfo = UserInfo::load($operator);
         if ($userInfo)
         {
            $partWasherEntry->operatorName = $userInfo->getFullName() .  " (" . $operator . ")";
         }
         
         $partWasherEntry->isNew = Time::isNew($partWasherEntry->dateTime, Time::NEW_THRESHOLD);
         
         // Mismatch checking.
         $partWasherEntry->panCountMismatch = false;
         $partWasherEntry->totalPartWeightLogPanCount = 0;
         $partWasherEntry->totalPartWasherLogPanCount = 0;
         if ($partWasherEntry->timeCardId)  // Only validate entries that have an associated time card.
         {
            $partWasherEntry->totalPartWeightLogPanCount = PartWeightEntry::getPanCountForTimeCard($partWasherEntry->timeCardId);
            $partWasherEntry->totalPartWasherLogPanCount = PartWasherEntry::getPanCountForTimeCard($partWasherEntry->timeCardId);
            
            $partWasherEntry->panCountMismatch =
               ($partWasherEntry->totalPartWeightLogPanCount != $partWasherEntry->totalPartWasherLogPanCount);
         }
         
         // Tabulator 5.0 and beyond does not handle duplicate fields in tables.
         // Therefore, we'll break dateTime up into washDate and washTime.
         $partWasherEntry->washDate = $partWasherEntry->dateTime;
         $partWasherEntry->washTime = $partWasherEntry->dateTime;
         
         $result[] = $partWasherEntry;
      }
   }
   
   echo json_encode($result);
});

$router->add("savePartWasherEntry", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   $dbaseResult = null;
   
   $partWasherEntry = null;

   if (isset($params["entryId"]) && 
       is_numeric($params["entryId"]) && 
       (intval($params["entryId"]) != PartWasherEntry::UNKNOWN_ENTRY_ID))
   {
      //  Updated entry
      $partWasherEntry = PartWasherEntry::load($params["entryId"]);
      
      if (!$partWasherEntry)
      {
         $result->success = false;
         $result->error = "No existing part washer entry found.";
      }
   }
   else
   {
      // New entry.
      $partWasherEntry = new PartWasherEntry();
      
      // Use current date/time as entry time.
      $partWasherEntry->dateTime = Time::now("Y-m-d h:i:s A");
   }
   
   if ($result->success)
   {
      if (isset($params["panTicketCode"]) &&
          ($params["panTicketCode"] != ""))
      {
         //
         // Pan ticket entry
         //
         
         $panTicketId = PanTicket::getPanTicketId($params["panTicketCode"]);
         
         // Validate panTicketId.
         if (TimeCardInfo::load($panTicketId) != null)
         {
            $partWasherEntry->timeCardId = $panTicketId;
         }
         else
         {
            $result->success = false;
            $result->error = "Invalid pan ticket code.";
         }
      }
      else if (isset($params["jobNumber"]) &&
               isset($params["wcNumber"]) &&
               isset($params["manufactureDate"]) &&
               isset($params["operator"]))
      {
         //
         // Manual entry
         //

         $jobId = JobInfo::getJobIdByComponents($params->get("jobNumber"), $params->getInt("wcNumber"));
         
         if ($jobId != JobInfo::UNKNOWN_JOB_ID)
         {
            $partWasherEntry->jobId = $jobId;
            $partWasherEntry->manufactureDate = $params["manufactureDate"];
            $partWasherEntry->operator = intval($params["operator"]);
         }
         else
         {
            $result->success = false;
            $result->error = "Failed to lookup job ID.";
         }
      }
      else
      {
         $result->success = false;
         $result->error = "Missing parameters.";
      }
      
      if ($result->success)
      {
         if (isset($params["washer"]) &&
             isset($params["panCount"]) &&
             isset($params["partCount"]))
         {
            $partWasherEntry->employeeNumber = intval($params["washer"]);
            $partWasherEntry->panCount = intval($params["panCount"]);
            $partWasherEntry->partCount = intval($params["partCount"]);
            
            if ($partWasherEntry->validatePartCount() == false)
            {
               $result->success = false;
               $result->error = "Unreasonable part count.  Please check this value for errors.";
            }
            else
            {
               if ($partWasherEntry->partWasherEntryId == PartWasherEntry::UNKNOWN_ENTRY_ID)
               {
                  $dbaseResult = $database->newPartWasherEntry($partWasherEntry);
               }
               else
               {
                  $dbaseResult = $database->updatePartWasherEntry($partWasherEntry);
               }
               
               if (!$dbaseResult)
               {
                  $result->success = false;
                  $result->error = "Database query failed.";
               }
            }
         }
         else
         {
            $result->success = false;
            $result->error = "Missing parameters.";
         }
      }
   }

   echo json_encode($result);
});

$router->add("deletePartWasherEntry", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   
   if (isset($params["entryId"]) &&
       is_numeric($params["entryId"]) &&
       (intval($params["entryId"]) != PartWasherEntry::UNKNOWN_ENTRY_ID))
   {
      $partWasherEntryId = intval($params["entryId"]);
      
      $partWasherEntry = PartWasherEntry::load($partWasherEntryId);
      
      if ($partWasherEntry)
      {
         $dbaseResult = $database->deletePartWasherEntry($partWasherEntryId);
         
         if ($dbaseResult)
         {
            $result->success = true;
         }
         else
         {
            $result->success = false;
            $result->error = "Database query failed.";
         }
      }
      else
      {
         $result->success = false;
         $result->error = "No existing entry found.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("partWeightLogData", function($params) {
   $result = array();
   
   $timeCardId = TimeCardInfo::UNKNOWN_TIME_CARD_ID;
   $dateType = FilterDateType::ENTRY_DATE;
   $startDate = Time::startOfDay(Time::now("Y-m-d"));
   $endDate = Time::endOfDay(Time::now("Y-m-d"));
   
   if (isset($params["timeCardId"]))
   {
      $timeCardId = $params->getInt("timeCardId");
   }
   
   if (isset($params["dateType"]))
   {
      $dateType = $params->getInt("dateType");
   }
   
   if (isset($params["startDate"]))
   {
      $startDate = Time::startOfDay($params["startDate"]);
   }
   
   if (isset($params["endDate"]))
   {
      $endDate = Time::endOfDay($params["endDate"]);
   }
   
   $database = PPTPDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $databaseResult = null;
      if ($timeCardId != TimeCardInfo::UNKNOWN_TIME_CARD_ID)
      {
         $databaseResult = $database->getPartWeightEntriesByTimeCard($timeCardId);
      }
      else 
      {
         $databaseResult = $database->getPartWeightEntries(JobInfo::UNKNOWN_JOB_ID, UserInfo::UNKNOWN_EMPLOYEE_NUMBER, $startDate, $endDate, ($dateType == FilterDateType::MANUFACTURING_DATE));
      }
      
      // Populate data table.
      foreach ($databaseResult as $row)
      {
         $partWeightEntry = new PartWeightEntry();
         $partWeightEntry->initializeFromDatabaseRow($row);
         
         $userInfo = UserInfo::load($partWeightEntry->employeeNumber);
         if ($userInfo)
         {
            $partWeightEntry->laborerName = $userInfo->getFullName() .  " (" . $partWeightEntry->employeeNumber . ")";
         }
         
         $jobId = $partWeightEntry->jobId;
         
         $operator = $partWeightEntry->operator;
         
         $partWeightEntry->timeCardId = $partWeightEntry->timeCardId;
         
         $partWeightEntry->panTicketCode = 
            ($partWeightEntry->timeCardId == TimeCardInfo::UNKNOWN_TIME_CARD_ID) ?
               "0000" :
               PanTicket::getPanTicketCode($partWeightEntry->timeCardId);         
               
         if ($partWeightEntry->timeCardId)
         {
            $timeCardInfo = TimeCardInfo::load($partWeightEntry->timeCardId);
            
            if ($timeCardInfo)
            {
               $jobId = $timeCardInfo->jobId;
               
               $operator = $timeCardInfo->employeeNumber;
               
               $partWeightEntry->manufactureDate = $timeCardInfo->manufactureDate;
            }
         }
         
         $jobInfo = JobInfo::load($jobId);
         if ($jobInfo)
         {
            $partWeightEntry->jobNumber = $jobInfo->jobNumber;
            $partWeightEntry->wcNumber = $jobInfo->wcNumber;
            $partWeightEntry->wcLabel = JobInfo::getWcLabel($jobInfo->wcNumber);
         }
         
         $userInfo = UserInfo::load($operator);
         if ($userInfo)
         {
            $partWeightEntry->operatorName = $userInfo->getFullName() .  " (" . $operator . ")";
         }
         
         $partWeightEntry->partCount = $partWeightEntry->calculatePartCount();
         
         $partWeightEntry->isNew = Time::isNew($partWeightEntry->dateTime, Time::NEW_THRESHOLD);
         
         // Mismatch checking.
         $partWeightEntry->panCountMismatch = false;
         $partWeightEntry->totalPartWeightLogPanCount = 0;
         $partWeightEntry->totalPartWasherLogPanCount = 0;
         if ($partWeightEntry->timeCardId)  // Only validate entries that have an associated time card.
         {
            $partWeightEntry->totalPartWeightLogPanCount = PartWeightEntry::getPanCountForTimeCard($partWeightEntry->timeCardId);
            $partWeightEntry->totalPartWasherLogPanCount = PartWasherEntry::getPanCountForTimeCard($partWeightEntry->timeCardId);
            
            $partWeightEntry->panCountMismatch =
               (($partWeightEntry->totalPartWasherLogPanCount > 0) &&
                ($partWeightEntry->totalPartWeightLogPanCount != $partWeightEntry->totalPartWasherLogPanCount));
         }
         
         // Tabulator 5.0 and beyond does not handle duplicate fields in tables.
         // Therefore, we'll break dateTime up into washDate and washTime.
         $partWeightEntry->weighDate = $partWeightEntry->dateTime;
         $partWeightEntry->weighTime = $partWeightEntry->dateTime;
         
         $result[] = $partWeightEntry;
      }
   }
   
   echo json_encode($result);
});

$router->add("savePartWeightEntry", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   $dbaseResult = null;
   
   $partWeightEntry = null;
   
   if (isset($params["entryId"]) &&
       is_numeric($params["entryId"]) &&
       (intval($params["entryId"]) != PartWasherEntry::UNKNOWN_ENTRY_ID))
   {
      //  Updated entry
      $partWeightEntry = PartWeightEntry::load($params["entryId"]);
      
      if (!$partWeightEntry)
      {
         $result->success = false;
         $result->error = "No existing part weight entry found.";
      }
   }
   else
   {
      // New entry.
      $partWeightEntry = new PartWeightEntry();
      
      // Use current date/time as entry time.
      $partWeightEntry->dateTime = Time::now("Y-m-d h:i:s A");
   }
   
   if ($result->success)
   {
      if (isset($params["panTicketCode"]) &&
          ($params["panTicketCode"] != ""))
      {
         //
         // Pan ticket entry
         //
         
         $panTicketId = PanTicket::getPanTicketId($params["panTicketCode"]);
         
         // Validate panTicketId.
         if (TimeCardInfo::load($panTicketId) != null)
         {
            $partWeightEntry->timeCardId = $panTicketId;            
         }
         else
         {
            $result->success = false;
            $result->error = "Invalid pan ticket code.";
         }
      }
      else if (isset($params["jobNumber"]) &&
               isset($params["wcNumber"]) &&
               isset($params["manufactureDate"]) &&
               isset($params["operator"]))
      {
         //
         // Manual entry
         //
         
         $jobId = JobInfo::getJobIdByComponents($params->get("jobNumber"), $params->getInt("wcNumber"));
         
         if ($jobId != JobInfo::UNKNOWN_JOB_ID)
         {
            $partWeightEntry->jobId = $jobId;
            $partWeightEntry->manufactureDate = $params["manufactureDate"];
            $partWeightEntry->operator = intval($params["operator"]);
         }
         else
         {
            $result->success = false;
            $result->error = "Failed to lookup job ID.";
         }
         
         $partWeightEntry->panCount = intval($params["panCount"]);
      }
      else
      {
         $result->success = false;
         $result->error = "Missing parameters.";
      }
      
      if ($result->success)
      {
         if (isset($params["laborer"]) &&
             isset($params["panCount"]) &&
             isset($params["partWeight"]))
         {
            $partWeightEntry->employeeNumber = intval($params["laborer"]);
            $partWeightEntry->panCount = intval($params["panCount"]);
            $partWeightEntry->weight = floatval($params["partWeight"]);
            
            // Validate the part count based on the supplied weight.
            if ($partWeightEntry->validatePartCount() == false)
            {
               $result->success = false;
               $result->error = "Unreasonable part weight.  Please check this value for errors.";
            }
            // For pan ticket entries, validate that a weight log entry does not already exist.
            // (Customer request on 7/22/2021)
            else if (($partWeightEntry->partWeightEntryId == PartWeightEntry::UNKNOWN_ENTRY_ID) &&            // New entry
                     ($partWeightEntry->timeCardId != TimeCardInfo::UNKNOWN_TIME_CARD_ID) &&                  // Pan ticket entry
                     (PartWeightEntry::getPartWeightEntryForTimeCard($partWeightEntry->timeCardId) != null))  // Entry exists
            {
               $result->success = false;
               $result->error = "A part weight log entry already exists for this pan ticket.";
            }
            else
            {
               if ($partWeightEntry->partWeightEntryId == PartWeightEntry::UNKNOWN_ENTRY_ID)
               {
                  $dbaseResult = $database->newPartWeightEntry($partWeightEntry);
               }
               else
               {
                  $dbaseResult = $database->updatePartWeightEntry($partWeightEntry);
               }
               
               if (!$dbaseResult)
               {
                  $result->success = false;
                  $result->error = "Database query failed.";
               }
            }
         }
         else
         {
            $result->success = false;
            $result->error = "Missing parameters.";
         }
      }
   }
   
   echo json_encode($result);
});
   
$router->add("deletePartWeightEntry", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   
   if (isset($params["entryId"]) &&
       is_numeric($params["entryId"]) &&
       (intval($params["entryId"]) != PartWeightEntry::UNKNOWN_ENTRY_ID))
   {
      $entryId = intval($params["entryId"]);
      
      $partWeightEntry = PartWeightEntry::load($entryId);
      
      if ($partWeightEntry)
      {
         $dbaseResult = $database->deletePartWeightEntry($entryId);
         
         if ($dbaseResult)
         {
            $result->success = true;
         }
         else
         {
            $result->success = false;
            $result->error = "Database query failed.";
         }
      }
      else
      {
         $result->success = false;
         $result->error = "No existing part weight entry found.";
      }
   }
   
   echo json_encode($result);
});

$router->add("inspectionTemplates", function($params) {
   $result = new stdClass();
   $result->success = false;
   $result->templates = array();
   
   if (is_numeric($params["inspectionType"]))
   {
      $inspectionType = intval($params["inspectionType"]);

      $jobNumber = isset($params["jobNumber"]) ? $params["jobNumber"] : JobInfo::UNKNOWN_JOB_NUMBER;
      $wcNumber = isset($params["wcNumber"]) ? $params->getInt("wcNumber") : JobInfo::UNKNOWN_WC_NUMBER;
      $jobId = JobInfo::getJobIdByComponents($jobNumber, $wcNumber);

      $templateIds = InspectionTemplate::getInspectionTemplatesForJob($inspectionType, $jobNumber, $jobId);
      
      foreach ($templateIds as $templateId)
      {
         $inspectionTemplate = InspectionTemplate::load($templateId); 
         
         if ($inspectionTemplate)
         {
            $result->templates[] = $inspectionTemplate;
         }
      }

      $result->success = true;
   }
   else
   {
      $result->success = false;
      $result->error = "No inspection type specified.";
   }
   
   echo json_encode($result);
});

$router->add("inspectionData", function($params) {
   $result = array();
   
   $startDate = Time::startOfDay(Time::now("Y-m-d"));
   $endDate = Time::endOfDay(Time::now("Y-m-d"));
   $dateType = FilterDateType::ENTRY_DATE;
   $inspectionType = InspectionType::UNKNOWN;
   $allIncomplete = false;
   
   if (isset($params["dateType"]))
   {
      $dateType = $params->getInt("dateType");
   }
   
   if (isset($params["startDate"]))
   {
      $startDate = Time::startOfDay($params["startDate"]);
   }
   
   if (isset($params["endDate"]))
   {
      $endDate = Time::endOfDay($params["endDate"]);
   }
   
   if (isset($params["inspectionType"]))
   {
      $inspectionType = intval($params["inspectionType"]);
   }
   
   if (isset($params["allIncomplete"]))
   {
      $allIncomplete = $params->getBool("allIncomplete");
   }   
   
   $authorInspectorOperator = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;  // Get inspections for all authors/inspectors/operators.
   if (Authentication::checkPermissions(Permission::VIEW_OTHER_USERS) == false)
   {
      // Limit to own inspections.
      $authorInspectorOperator = Authentication::getAuthenticatedUser()->employeeNumber;
   }
   
   $database = PPTPDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $databaseResult = null;
      if ($allIncomplete)
      {
         $databaseResult = $database->getInspectionsByStatus($inspectionType, $authorInspectorOperator, InspectionStatus::INCOMPLETE);
      }
      else
      {
         $useMfgDate = ($dateType == FilterDateType::MANUFACTURING_DATE);
         $databaseResult = $database->getInspections($inspectionType, $authorInspectorOperator, $startDate, $endDate, $useMfgDate);
      }
      
      // Populate data table.
      foreach ($databaseResult as $row)
      {
         $inspection = new Inspection();
         $inspection->initializeFromDatabaseRow($row);
         if (!$inspection->hasSummary())
         {
            $inspection->loadInspectionResults();
         }
         $inspectionType = intval($row["inspectionType"]);
         
         $row["dateTime"] = $inspection->dateTime;
         $row["completedDateTime"] = $inspection->getCompletedDateTime();
         
         $row["timeCardId"] = $inspection->timeCardId;
         $row["jobNumber"] = $inspection->getJobNumber();
         $row["wcNumber"] = $inspection->getWcNumber();
         $row["wcLabel"] = JobInfo::getWcLabel($row["wcNumber"]);
         $row["mfgDate"] = $inspection->getManufactureDate();
                  
         // Ticket code
         // Could be pan ticket code, or shipment ticket code (for Final inspections).
         $row["shipmentId"] = Shipment::UNKNOWN_SHIPMENT_ID;
         if ($inspection->timeCardId != TimeCardInfo::UNKNOWN_TIME_CARD_ID)
         {
            $row["ticketCode"] = PanTicket::getPanTicketCode($inspection->timeCardId);
         }
         else if ($inspectionType == InspectionType::FINAL)
         {
            $shipment = ShipmentManager::getShipmentFromInspection($inspection->inspectionId);
            if ($shipment)
            {
               $row["shipmentId"] = $shipment->shipmentId;
               $row["ticketCode"] = ShipmentTicket::getShipmentTicketCode($shipment->shipmentId);
            }
         }
         
         $row["inspectionTypeLabel"] = InspectionType::getLabel($inspectionType);
         
         $inspectionStatus = $inspection->getInspectionStatus();
         $row["inspectionStatus"] = $inspectionStatus;
         $row["inspectionStatusLabel"] = InspectionStatus::getLabel($inspectionStatus);
         $row["inspectionStatusClass"] = InspectionStatus::getClass($inspectionStatus);
         
         $userInfo = UserInfo::load($inspection->author);
         if ($userInfo)
         {
            $row["authorName"] = $userInfo->getFullName();
         }
         
         $userInfo = UserInfo::load($inspection->inspector);
         if ($userInfo)
         {
            $row["inspectorName"] = $userInfo->getFullName();
         }
         
         $userInfo = UserInfo::load($inspection->getOperator());
         if ($userInfo)
         {
            $row["operatorName"] = $userInfo->getFullName();
         }
         
         $row["count"] = $inspection->getCount(true);
         $row["naCount"] = $inspection->getCountByStatus(InspectionStatus::NON_APPLICABLE);
         $row["passCount"] = $inspection->getCountByStatus(InspectionStatus::PASS);
         $row["warningCount"] = $inspection->getCountByStatus(InspectionStatus::WARNING);
         $row["failCount"] = $inspection->getCountByStatus(InspectionStatus::FAIL);
         
         $result[] = $row;
      }
   }
   
   echo json_encode($result);
});

$router->add("saveInspection", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   $dbaseResult = null;
   
   $inspection = null;
   $newInspection = false;
   $wasComplete = false;
   
   if (isset($params["inspectionId"]) &&
       is_numeric($params["inspectionId"]) &&
       (intval($params["inspectionId"]) != Inspection::UNKNOWN_INSPECTION_ID))
   {
      //  Updated entry
      //  Note: Don't load actual results.  We'll rebuild them below.
      $inspection = Inspection::load($params["inspectionId"], false);
      
      if (!$inspection)
      {
         $result->success = false;
         $result->error = "No existing inspection found.";
      }
      else
      {
         // Remember if this existing inspection was complete, prior to this save.
         $wasComplete = $inspection->complete();
      }
   }
   else
   {
      // New entry.
      $inspection = new Inspection();
      $newInspection = true;
      
      // Use current date/time as the entry time.
      $inspection->dateTime = Time::now("Y-m-d h:i:s A");
   }
   
   if ($result->success)
   {
      if (isset($params["templateId"]) &&
          isset($params["author"]) &&
          isset($params["inspector"]) &&
          isset($params["comments"]))
      {
         $inspection->templateId = intval($params["templateId"]);
         $inspection->author = intval($params["author"]);
         $inspection->inspector = intval($params["inspector"]);
         $inspection->comments = $params["comments"];
         
         $inspectionTemplate = InspectionTemplate::load($inspection->templateId, true);  // Load properties. 
         
         if ($inspectionTemplate)
         {   
            if (isset($params["timeCardId"]) && $params->get("timeCardId"))
            {
               $inspection->timeCardId = $params->get("timeCardId");
            }
            else
            {
               if (isset($params["jobNumber"]))
               {
                  $inspection->jobNumber = $params->get("jobNumber");
               }
               
               if (isset($params["wcNumber"]))
               {
                  $inspection->wcNumber = $params->get("wcNumber");
               }
   
               if (isset($params["operator"]))
               {
                  $inspection->operator = $params->get("operator");
               }
               
               if (isset($params["mfgDate"]))
               {
                  $inspection->mfgDate = Time::startOfDay($params->get("mfgDate"));
               }
            }
            
            if (isset($params["inspectionNumber"]))
            {
               $inspection->inspectionNumber = $params->getInt("inspectionNumber");
            }
            
            if (isset($params["quantity"]))
            {
               $inspection->quantity = $params->getInt("quantity");
            }
            
            $inspection->isPriority = $params->keyExists("isPriority");
            
            if (isset($params["startMfgDate"]))
            {
               $inspection->startMfgDate = Time::startOfDay($params->get("startMfgDate"));
            }
            
            foreach ($inspectionTemplate->inspectionProperties as $inspectionProperty)
            {
               for ($sampleIndex = 0; $sampleIndex < $inspection->getSampleSize(); $sampleIndex++)
               {
                  $name = InspectionResult::getInputName($inspectionProperty->propertyId, $sampleIndex);
                  $dataName = $name . "_data";

                  if ((isset($params[$name])) &&
                      (isset($params[$dataName])))
                  {
                     $inspectionResult = new InspectionResult();
                     $inspectionResult->propertyId = $inspectionProperty->propertyId;
                     $inspectionResult->sampleIndex = $sampleIndex;
                     $inspectionResult->status = intval($params[$name]);
                     $inspectionResult->data = $params[$dataName];
                     
                     if (!isset($inspection->inspectionResults[$inspectionResult->propertyId]))
                     {
                        $inspection->inspectionResults[$inspectionResult->propertyId] = array();
                     }
                     
                     $inspection->inspectionResults[$inspectionResult->propertyId][$sampleIndex] = $inspectionResult;
                  }
                  else
                  {
                     $result->success = false;
                     $result->error = "Missing property [$name]";
                     break;
                  }
               }
               
               // Comment.
               $name = InspectionResult::getInputName($inspectionProperty->propertyId, InspectionResult::COMMENT_SAMPLE_INDEX);

               if (isset($params[$name]))
               {
                  $inspectionResult = new InspectionResult();
                  $inspectionResult->propertyId = $inspectionProperty->propertyId;
                  $inspectionResult->sampleIndex = InspectionResult::COMMENT_SAMPLE_INDEX;
                  $inspectionResult->status = InspectionStatus::UNKNOWN;
                  $inspectionResult->data = $params[$name];
                  
                  $inspection->inspectionResults[$inspectionResult->propertyId][InspectionResult::COMMENT_SAMPLE_INDEX] = $inspectionResult;                  
               }
               else
               {
                  $result->success = false;
                  $result->error = "Missing property [$name]";
                  break;
               }
            }
                 
            if ($result->success)
            {
               $inspection->updateSummary();
               
               // Updated date/time.
               $inspection->updatedDateTime = Time::now("Y-m-d h:i:s A");
               
               if ($newInspection)
               {
                  $newInspectionId = $database->newInspection($inspection);
                  $dbaseResult = ($newInspectionId != Inspection::UNKNOWN_INSPECTION_ID);
                  
                  if ($dbaseResult)
                  {
                     $inspection->inspectionId = $newInspectionId;
                  }
               }
               else
               {
                  $dbaseResult = $database->updateInspection($inspection);
               }
               
               if ($dbaseResult)
               {
                  // Success.
                  
                  // Send a notification.
                  if ($newInspection && 
                      ($inspectionTemplate->inspectionType == InspectionType::FINAL))
                  {
                     NotificationManager::onFinalInspectionCreated($inspection->inspectionId, $inspection->isPriority);
                  }
                  else if ($newInspection &&
                           ($inspectionTemplate->inspectionType == InspectionType::FIRST_PART))
                  {
                     NotificationManager::onFirstPartInspectionCreated($inspection->inspectionId);
                  }
                  else if (($inspectionTemplate->inspectionType == InspectionType::FIRST_PART) &&
                           (!$inspection->incomplete()))
                  {
                     NotificationManager::onFirstPartInspectionComplete($inspection->inspectionId);
                  }
                  
                  // React to the creation of a FINAL inspection by creating a new shipment.
                  if (($inspectionTemplate->inspectionType == InspectionType::FINAL) &&
                      $newInspection)
                  {
                     ShipmentManager::onFinalInspectionCreated($inspection->inspectionId);
                  }
               }
               else
               {
                  $result->success = false;
                  $result->error = "Database query failed.";
               }
            }
         }
         else
         {
            $result->success = false;
            $result->error = "Failed to lookup inspection template.";
         }
      }
      else
      {
         $result->success = false;
         $result->error = "Missing parameters.";
      }
   }
   
   echo json_encode($result);
});

$router->add("deleteInspection", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   
   if (!Authentication::checkPermissions(Permission::DELETE_INSPECTION))
   {
      $result->success = false;
      $result->error = "Permissions error.";
   }
   else if (isset($params["inspectionId"]) &&
            is_numeric($params["inspectionId"]) &&
            (intval($params["inspectionId"]) != Inspection::UNKNOWN_INSPECTION_ID))
   {
      $inspectionId = intval($params["inspectionId"]);
      
      $inspection = Inspection::load($inspectionId, false);  // Don't load actual results, for efficiency.
      
      if ($inspection)
      {
         $dbaseResult = $database->deleteInspection($inspectionId);
         
         if ($dbaseResult)
         {
            $result->success = true;
         }
         else
         {
            $result->success = false;
            $result->error = "Database query failed.";
         }
      }
      else
      {
         $result->success = false;
         $result->error = "No existing inspection found.";
      }
   }
   
   echo json_encode($result);
});

$router->add("inspectionTemplateData", function($params) {
   $result = array();
   
   $inspectionType = InspectionType::UNKNOWN;

   if (isset($params["inspectionType"]))
   {
      $inspectionType = intval($params["inspectionType"]);
   }
   
   $database = PPTPDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $databaseResult = $database->getInspectionTemplates($inspectionType);
      
      // Populate data table.
      foreach ($databaseResult as $row)
      {
         $inspectionTemplate = new InspectionTemplate();
         $inspectionTemplate->initializeFromDatabaseRow($row);
         
         $inspectionTemplate->inspectionTypeLabel = InspectionType::getLabel($inspectionTemplate->inspectionType);
         
         $result[] = $inspectionTemplate;
      }
   }
   
   echo json_encode($result);
});

$router->add("saveInspectionTemplate", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   $dbaseResult = null;
   
   $inspectionTemplate = null;
   
   if (isset($params["templateId"]) &&
       is_numeric($params["templateId"]) &&
       (intval($params["templateId"]) != InspectionTemplate::UNKNOWN_TEMPLATE_ID))
   {
      //  Updated entry
      //  Note: Don't load properties.  We'll build the new set below.
      $inspectionTemplate = InspectionTemplate::load($params["templateId"], false);
      
      if (!$inspectionTemplate)
      {
         $result->success = false;
         $result->error = "No existing template found.";
      }
   }
   else
   {
      // New entry.
      $inspectionTemplate = new InspectionTemplate();
   }
   
   if ($result->success)
   {
      if (isset($params["templateName"]) &&
          isset($params["templateDescription"]) &&
          isset($params["inspectionType"]) &&
          isset($params["sampleSize"]) &&
          isset($params["notes"]))
      {
         $inspectionTemplate->name = $params["templateName"];
         $inspectionTemplate->description = $params["templateDescription"];
         $inspectionTemplate->inspectionType = intval($params["inspectionType"]);
         $inspectionTemplate->sampleSize = intval($params["sampleSize"]);
         $inspectionTemplate->notes = $params["notes"];
         
         // Optional properties
         if ($inspectionTemplate->inspectionType == InspectionType::GENERIC)
         {
            for ($optionalProperty = OptionalInspectionProperties::FIRST;
                 $optionalProperty < OptionalInspectionProperties::LAST;
                 $optionalProperty++)
            {
               $name = "optional-property-$optionalProperty-input";
               
               if (isset($params[$name]))
               {
                  $inspectionTemplate->setOptionalProperty($optionalProperty);
               }
               else
               {
                  $inspectionTemplate->clearOptionalProperty($optionalProperty);
               }
            }
         }
         
         // Gather up all submitted property indexes.
         $propertyIndexes = [];
         foreach ($params as $key => $value)
         {
            // Look for "name" parameters.
            // Ex. property1_name
            if (strpos($key, "_name") !== false)
            {
               $startPos = strpos($key, "property") + strlen("property");
               $endPos = strpos($key, "_name");
               $length = ($endPos - $startPos);
               $index = intval(substr($key, $startPos, $length));
               
               $propertyIndexes[] = $index;
            }
         }
         
         foreach ($propertyIndexes as $propertyIndex)
         {
            $name = "property" . $propertyIndex;
            
            if (isset($params[$name . "_propertyId"]) &&
                isset($params[$name . "_ordering"]) &&
                isset($params[$name . "_specification"]) &&
                isset($params[$name . "_dataType"]) &&
                isset($params[$name . "_dataUnits"]) &&
                isset($params[$name . "_ordering"]))
            {
               $inspectionProperty = new InspectionProperty();

               $inspectionProperty->propertyId = intval($params[$name . "_propertyId"]);
               $inspectionProperty->templateId = $inspectionTemplate->templateId;
               $inspectionProperty->name = $params[$name . "_name"];
               $inspectionProperty->specification = $params[$name . "_specification"];
               $inspectionProperty->dataType = intval($params[$name . "_dataType"]);
               $inspectionProperty->dataUnits = intval($params[$name . "_dataUnits"]);
               $inspectionProperty->ordering = intval($params[$name . "_ordering"]);
               
               if ($inspectionProperty->propertyId == InspectionProperty::UNKNOWN_PROPERTY_ID)
               {
                  // New property.
                  $inspectionTemplate->inspectionProperties[] = $inspectionProperty;
               }
               else
               {
                  // Updated property.
                  $inspectionTemplate->inspectionProperties[$inspectionProperty->propertyId] = $inspectionProperty;
               }
            }
            else
            {
               $result->success = false;
               $result->error = "Missing parameters for property[$propertyIndex].";
               break;
            }
         }
               
         if ($result->success)
         {
            if ($inspectionTemplate->templateId == InspectionTemplate::UNKNOWN_TEMPLATE_ID)
            {
               $dbaseResult = $database->newInspectionTemplate($inspectionTemplate);
            }
            else
            {
               $dbaseResult = $database->updateInspectionTemplate($inspectionTemplate);
            }
            
            if (!$dbaseResult)
            {
               $result->success = false;
               $result->error = "Database query failed.";
            }
         }
      }
      else
      {
         $result->success = false;
         $result->error = "Missing parameters.";
      }
   }
   
   echo json_encode($result);
});
   
$router->add("deleteInspectionTemplate", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   
   if (isset($params["templateId"]) &&
       is_numeric($params["templateId"]) &&
       (intval($params["templateId"]) != InspectionTemplate::UNKNOWN_TEMPLATE_ID))
   {
      $templateId = intval($params["templateId"]);
      
      $inspectionTemplate = InspectionTemplate::load($templateId);
      
      if ($inspectionTemplate)
      {
         $dbaseResult = $database->deleteInspectionTemplate($templateId);
         
         if ($dbaseResult)
         {
            $result->success = true;
         }
         else
         {
            $result->success = false;
            $result->error = "Database query failed.";
         }
      }
      else
      {
         $result->success = false;
         $result->error = "No existing template found.";
      }
   }
   
   echo json_encode($result);
});

$router->add("inspectionTable", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   if (isset($params["inspectionId"]) &&
       isset($params["templateId"]) &&
       isset($params["quantity"]))
   {
      $inspectionId = $params->getInt("inspectionId");
      $templateId = $params->getInt("templateId");
      $quantity = $params->getInt("quantity");
      
      $inspection = null;
      $inspectionTemplate = null;
      
      if ($inspectionId != Inspection::UNKNOWN_INSPECTION_ID)
      {
         $inspection = Inspection::load($inspectionId, false);  // Don't load results.
      }
      else if ($templateId != InspectionTemplate::UNKNOWN_TEMPLATE_ID)
      {
         $inspectionTemplate = InspectionTemplate::load($templateId);
      }
      
      if ($inspection || $inspectionTemplate)
      {
         $result->success = true;
         $result->html = InspectionTable::getHtml($inspectionId, $templateId, $quantity, Authentication::checkPermissions(Permission::QUICK_INSPECTION), true);
      }
      else
      {
         $result->success = false;
         $result->error = "No existing inspection/template found.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }      
   
   echo json_encode($result);
});      

$router->add("printerData", function($params) {
   $result = array();
   
   $database = PPTPDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $databaseResult = $database->getPrinters();
      
      foreach ($databaseResult as $row)
      {
         $printerInfo = PrinterInfo::load($row["printerName"]);
         
         if ($printerInfo && $printerInfo->isCurrent())
         {
            $row["displayName"] = $printerInfo->getDisplayName();
            
            $row["status"] = ($printerInfo->isConnected) ? "Online" : "Offline";
            
            $result[] = $row;
         }
      }
   }
   
   echo json_encode($result);
});

$router->add("registerPrinter", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   
   if (isset($params["printerName"]) &&
       isset($params["model"]) &&
       isset($params["isConnected"]))
   {
      $printerInfo = PrinterInfo::load($params["printerName"]);

      if ($printerInfo)
      {
         $printerInfo->isConnected = $params->getBool("isConnected");
         $printerInfo->lastContact = Time::now("Y-m-d H:i:s");
         
         $dbaseResult = $database->updatePrinter($printerInfo);
      }
      else
      {
         $printerInfo = new PrinterInfo($printerInfo);
         
         $printerInfo->printerName =  $params["printerName"];
         $printerInfo->model =  $params["model"];
         $printerInfo->isConnected = $params->getBool("isConnected");
         $printerInfo->lastContact = Time::now("Y-m-d H:i:s");
         
         $dbaseResult = $database->newPrinter($printerInfo);
      }
      
      if ($dbaseResult)
      {
         $result->success = true;
      }
      else
      {
         $result->success = false;
         $result->error = "Database query failed.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("queuePrintJob", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   
   if (is_numeric($params["owner"]) &&
       isset($params["description"]) &&
       isset($params["printerName"]) &&
       is_numeric($params["copies"]) &&
       isset($params["xml"]))
   {
      $printJob = new PrintJob();
      
      $printJob->owner = intval($params["owner"]);
      $printJob->dateTime = Time::now("Y-m-d H:i:s");
      $printJob->description = $params["description"];
      $printJob->printerName = $params["printerName"];
      $printJob->copies = intval($params["copies"]);
      $printJob->status = PrintJobStatus::QUEUED;
      $printJob->xml = $params["xml"];

      $dbaseResult = $database->newPrintJob($printJob);

      if ($dbaseResult)
      {
         $result->success = true;
      }
      else
      {
         $result->success = false;
         $result->error = "Database query failed.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("printQueue", function($params) {
   $result = new stdClass();
   
   $printQueue = PrintQueue::load();
   
   // Add user names
   foreach ($printQueue->queue as $printJob)
   {
      $userInfo = UserInfo::load($printJob->owner);
      if ($userInfo)
      {
         $printJob->ownerName = $userInfo->getFullName();
      }
   }
   
   $result->success = true;
   $result->queue = $printQueue->queue;
   
   echo json_encode($result);
});

$router->add("printQueueData", function($params) {
   $result = array();
   
   $printQueue = PrintQueue::load();
   
   foreach ($printQueue->queue as $printJob)
   {
      $userInfo = UserInfo::load($printJob->owner);
      if ($userInfo)
      {
         $printJob->ownerName = $userInfo->getFullName();
      }
      
      $printJob->printerDisplayName = getPrinterDisplayName($printJob->printerName);
      
      $printJob->statusLabel = PrintJobStatus::getLabel($printJob->status);
      
      $result[] = $printJob;
   }
   
   echo json_encode($result);
});

$router->add("setPrintJobStatus", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   
   if (is_numeric($params["printJobId"]) &&
       is_numeric($params["status"]))
   {
      $printJobId = intval($params["printJobId"]);
      $status = intval($params["status"]);
      
      $dbaseResult = $database->setPrintJobStatus($printJobId, $status);

      if ($dbaseResult)
      {
         $result->success = true;
         $result->printJobId = $printJobId;
         $result->status = $status;
      }
      else
      {
         $result->success = false;
         $result->printJobId = $printJobId;
         $result->error = "Database query failed.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("cancelPrintJob", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   
   if (is_numeric($params["printJobId"]))
   {
      $printJobId = intval($params["printJobId"]);
      
      $dbaseResult = $database->deletePrintJob($printJobId);
      
      if ($dbaseResult && ($database->rowsAffected() == 1))
      {
         $result->success = true;
         $result->printJobId = $printJobId;
      }
      else
      {
         $result->success = false;
         $result->printJobId = $printJobId;
         $result->error = "Database query failed.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("panTicket", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   if (is_numeric($params["panTicketId"]))
   {
      $panTicket = new PanTicket(intval($params["panTicketId"]));
      
      if ($panTicket)
      {
         $result->success = true;
         $result->panTicketId = $panTicket->panTicketId;
         $result->labelXML = $panTicket->labelXML;
      }
      else
      {
         $result->success = false;
         $result->error = "Failed to create pan ticket.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("printPanTicket", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   
   if (is_numeric($params["panTicketId"]) &&
       isset($params["printerName"]) &&
       is_numeric($params["copies"]))
   {
      $panTicket = new PanTicket(intval($params["panTicketId"]));
      
      if ($panTicket)
      {         
         $printJob = new PrintJob();
         $printJob->owner = Authentication::getAuthenticatedUser()->employeeNumber;
         $printJob->dateTime = Time::now("Y-m-d H:i:s");
         $printJob->description = $panTicket->printDescription;
         $printJob->printerName = $params["printerName"];
         $printJob->copies = intval($params["copies"]);
         $printJob->status = PrintJobStatus::QUEUED;
         $printJob->xml = $panTicket->labelXML;
         
         $dbaseResult = $database->newPrintJob($printJob);
         
         if ($dbaseResult)
         {
            $result->success = true;
         }
         else
         {
            $result->success = false;
            $result->error = "Database query failed.";
         }
      }
      else
      {
         $result->success = false;
         $result->error = "Failed to create pan ticket.";
      }
      
      // Store preferred printer for session.
      $_SESSION["preferredPrinter"] = $params["printerName"];
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("uploadOasisReport", function($params) {
   $result = new stdClass();
   $result->success = false;
   
   global $OASIS_REPORTS_DIR;
   
   $database = PPTPDatabase::getInstance();
   
   if (isset($_FILES["reportFile"]))
   {
      $target_dir = ROOT.$OASIS_REPORTS_DIR;
      $target_file = $target_dir . basename($_FILES["reportFile"]["name"]);
      
      if (move_uploaded_file($_FILES["reportFile"]["tmp_name"], $target_file))
      {
         $oasisReport = OasisReport::parseFile($target_file);
         
         if (!$oasisReport)
         {
            $result->success = false;
            $result->error = "Failed to parse the Oasis report file.";
         }
         else
         {
            // Create a new inspection from the Oasis report.
            $inspection = new Inspection();
            $inspection->initializeFromOasisReport($oasisReport);
            
            if ($database->newInspection($inspection))
            {
               $result->success = true;
            }
            else
            {
               $result->error = "Database error.";
               $result->sqlQuery = $database->lastQuery();
            }
            
            // Auto-generate an In Process inspection from the Oasis inspection.
            if (($result->success) &&
                (!$oasisReport->isBPartNumber())) // Don't generate In Process for "B" part inspections.
            {
               InspectionManager::generateInProcessFromOasis($inspection);
            }
         }
      }
      else
      {
         $result->success = false;
         $result->error = "Failed to save the report file.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "No report file specified.";
   }
   
   echo json_encode($result);
});

$router->add("signData", function($params) {
   $result = array();
   
   $database = PPTPDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $dbaseResult = $database->getSigns();
      
      // Populate data table.
      foreach ($dbaseResult as $row)
      {
         $result[] = $row;
      }
   }
   
   echo json_encode($result);
});

$router->add("saveSign", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   $dbaseResult = null;
   
   if (isset($params["signId"]) &&
       is_numeric($params["signId"]) &&
      (intval($params["signId"]) != SignInfo::UNKNOWN_SIGN_ID))
   {
      $signId = intval($params["signId"]);
      
      //  Updated entry
      $signInfo = SignInfo::load($signId);
      
      if (!$signInfo)
      {
         $result->success = false;
         $result->error = "No existing sign found.";
      }
   }
   else
   {
      // New sign.
      $signInfo = new SignInfo();
   }
   
   if ($result->success)
   {
      if (isset($params["name"]) &&
          isset($params["description"]) &&
          isset($params["url"]))
      {
         $signInfo->name = $params["name"];
         $signInfo->description = $params["description"];
         $signInfo->url = $params["url"];

         if ($signInfo->signId == SignInfo::UNKNOWN_SIGN_ID)
         {
            $dbaseResult = $database->newSign($signInfo);
         }
         else
         {
            $dbaseResult = $database->updateSign($signInfo);
         }
      
         if ($dbaseResult)
         {
            $result->signInfo = $signInfo;
         }
         else
         {
            $result->success = false;
            $result->error = "Database query failed.";
         }
      }
      else
      {
         $result->success = false;
         $result->error = "Missing parameters.";
      }
   }
   
   echo json_encode($result);
});

$router->add("deleteSign", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   
   if (isset($params["signId"]) &&
       is_numeric($params["signId"]) &&
       (intval($params["signId"]) != SignInfo::UNKNOWN_SIGN_ID))
   {
      $signId = intval($params["signId"]);
      
      $signInfo = SignInfo::load($signId);
      
      if ($signInfo)
      {
         $dbaseResult = $database->deleteSign($signId);
         
         if ($dbaseResult)
         {
            $result->success = true;
         }
         else
         {
            $result->success = false;
            $result->error = "Database query failed.";
         }
      }
      else
      {
         $result->success = false;
         $result->error = "No existing sign found.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("dailySummaryReportData", function($params) {
   $result = array();
   
   $mfgDate = Time::startOfDay(Time::now("Y-m-d"));
   $useMaintenanceLogEntries = false;
   
   if (isset($params["mfgDate"]))
   {
      $mfgDate = Time::startOfDay($params["mfgDate"]);
   }
   
   if (isset($params["useMaintenanceLogEntries"]))
   {
      $useMaintenanceLogEntries = $params->getBool("useMaintenanceLogEntries");
   }
   
   $table = DailySummaryReportTable::DAILY_SUMMARY;
   if (isset($params["table"]))
   {
      $table = intval($params["table"]);
   }
   
   $database = PPTPDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $dailySummaryReport = DailySummaryReport::load(UserInfo::UNKNOWN_EMPLOYEE_NUMBER, $mfgDate, $useMaintenanceLogEntries);
      
      if ($dailySummaryReport)
      {
         $result = $dailySummaryReport->getReportData($table);
      }
   }
   
   echo json_encode($result);
});

$router->add("weeklySummaryReportData", function($params) {
   $result = array();
   
   $mfgDate = Time::startOfDay(Time::now("Y-m-d"));
   $useMaintenanceLogEntries = false;

   if (isset($params["mfgDate"]))
   {
      $mfgDate = Time::startOfDay($params["mfgDate"]);
   }
   
   if (isset($params["useMaintenanceLogEntries"]))
   {
      $useMaintenanceLogEntries = $params->getBool("useMaintenanceLogEntries");
   }
   
   $table = WeeklySummaryReportTable::OPERATOR_SUMMARY;
   if (isset($params["table"]))
   {
      $table = intval($params["table"]);
   }
   
   $database = PPTPDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $weeklySummaryReport = WeeklySummaryReport::load($mfgDate, $useMaintenanceLogEntries);
      
      if ($weeklySummaryReport)
      {
         $result = $weeklySummaryReport->getReportData($table);
      }
   }
   
   echo json_encode($result);
});

$router->add("weeklySummaryReportDates", function($params) {
   $result = new stdClass();
   $result->success = true;

   $mfgDate = Time::startOfDay(Time::now("Y-m-d"));

   if (isset($params["mfgDate"]))
   {
      $mfgDate = Time::startOfDay($params["mfgDate"]);
   }

   $dates = WorkDay::getDates($mfgDate);
   
   $dateTime = new DateTime($dates[WorkDay::SUNDAY], new DateTimeZone('America/New_York'));  // TODO: Replace
   $result->weekStartDate = $dateTime->format("D n/j");
      
   $dateTime = new DateTime($dates[WorkDay::SATURDAY], new DateTimeZone('America/New_York'));  // TODO: Replace
   $result->weekEndDate = $dateTime->format("D n/j");
   
   $result->weekNumber = Time::weekNumber($dates[WorkDay::MONDAY]);  // ISO weeks start on Mondays

   echo json_encode($result);
});

$router->add("quarterlySummaryReportData", function($params) {
   $result = array();
   
   $quarter = Quarter::QUARTER_1;
   $year = 2021;  // TODO
   $useMaintenanceLogEntries = false;
   
   if (isset($params["quarter"]))
   {
      $quarter = intval($params["quarter"]);
   }
   
   if (isset($params["year"]))
   {
      $year = intval($params["year"]);
   }
   
   if (isset($params["useMaintenanceLogEntries"]))
   {
      $useMaintenanceLogEntries = $params->getBool("useMaintenanceLogEntries");
   }
   
   $table = QuarterlySummaryReportTable::OPERATOR_SUMMARY;
   if (isset($params["table"]))
   {
      $table = intval($params["table"]);
   }
   
   $database = PPTPDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $quarterlySummaryReport = QuarterlySummaryReport::load($year, $quarter, $useMaintenanceLogEntries);
      
      if ($quarterlySummaryReport)
      {
         $result = $quarterlySummaryReport->getReportData($table);
      }
   }
   
   echo json_encode($result);
});

$router->add("quarterlySummaryReportDates", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $quarter = Quarter::QUARTER_1;
   $year = 2021;  // TODO
   
   if (isset($params["quarter"]))
   {
      $quarter = intval($params["quarter"]);
   }
   
   if (isset($params["year"]))
   {
      $year = intval($params["year"]);
   }
   
   $dates = Quarter::getDates($year, $quarter);
   
   // Convert to "Sun 3/27" format.
   foreach ($dates as $date)
   {
      $dateTime = new DateTime($date->start, new DateTimeZone('America/New_York'));  // TODO: Replace
      $date->start = $dateTime->format("n/j");
      
      $dateTime = new DateTime($date->end, new DateTimeZone('America/New_York'));  // TODO: Replace
      $date->end = $dateTime->format("n/j");
   }
   
   $result->dates = $dates;
   
   echo json_encode($result);
});

$router->add("maintenanceLogData", function($params) {
   $result = array();
   
   $dateType = FilterDateType::ENTRY_DATE;
   $startDate = Time::startOfDay(Time::now("Y-m-d"));
   $endDate = Time::endOfDay(Time::now("Y-m-d"));
   
   if (isset($params["dateType"]))
   {
      $dateType = $params->getInt("dateType");
   }
   
   if (isset($params["startDate"]))
   {
      $startDate = Time::startOfDay($params["startDate"]);
   }
   
   if (isset($params["endDate"]))
   {
      $endDate = Time::endOfDay($params["endDate"]);
   }
   
   $wcNumber = JobInfo::UNKNOWN_WC_NUMBER;
   if (isset($params["wcNumber"]))
   {
      $wcNumber = intval($params["wcNumber"]);
   }
   
   $database = PPTPDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $dbaseResult = $database->getMaintenanceEntries($startDate, $endDate, UserInfo::UNKNOWN_EMPLOYEE_NUMBER, $wcNumber, ($dateType == FilterDateType::MAINTENANCE_DATE));
      
      foreach ($dbaseResult as $row)
      {
         $maintenanceEntry = MaintenanceEntry::load(intval($row["maintenanceEntryId"]));
         
         if ($maintenanceEntry)
         {
            $userInfo = UserInfo::load(intval($row["employeeNumber"]));
            if ($userInfo)
            {
               $maintenanceEntry->technicianName = $userInfo->getFullName();
            }
            
            $maintenanceEntry->equipmentName = "";
            if ($maintenanceEntry->wcNumber != JobInfo::UNKNOWN_WC_NUMBER)
            {
               $maintenanceEntry->equipmentName = JobInfo::getWcLabel($maintenanceEntry->wcNumber);
            }
            else if ($maintenanceEntry->equipmentId != EquipmentInfo::UNKNOWN_EQUIPMENT_ID)
            {
               $equipmentInfo = EquipmentInfo::load($maintenanceEntry->equipmentId);
               if ($equipmentInfo)
               {
                  $maintenanceEntry->equipmentName = $equipmentInfo->name;
               }
            }

            $maintenanceEntry->typeLabel = MaintenanceEntry::getTypeLabel($maintenanceEntry->typeId);
            $maintenanceEntry->categoryLabel = MaintenanceEntry::getCategoryLabel($maintenanceEntry->categoryId);
            $maintenanceEntry->subcategoryLabel = MaintenanceEntry::getSubcategoryLabel($maintenanceEntry->subcategoryId);
            
            if ($maintenanceEntry->partId != MachinePartInfo::UNKNOWN_PART_ID)
            {
               $machinePartInfo = MachinePartInfo::load($maintenanceEntry->partId);
               if ($machinePartInfo)
               {
                  $maintenanceEntry->partNumber = $machinePartInfo->partNumber;
               }
            }
         
            $result[] = $maintenanceEntry;
         }
      }
   }
   
   echo json_encode($result);
});

$router->add("saveMaintenanceEntry", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   $dbaseResult = null;
   
   $maintenancEntry = null;
   
   if (isset($params["entryId"]) &&
       is_numeric($params["entryId"]) &&
       (intval($params["entryId"]) != MaintenanceEntry::UNKNOWN_ENTRY_ID))
   {
      $entryId = intval($params["entryId"]);
      
      //  Updated entry
      $maintenancEntry = MaintenanceEntry::load($entryId);
      
      if (!$maintenancEntry)
      {
         $result->success = false;
         $result->error = "No existing maintenance entry found.";
      }
   }
   else
   {
      // New entry.
      $maintenancEntry = new MaintenanceEntry();
      
      // Use current date/time as the entry time.
      $maintenancEntry->dateTime = Time::now("Y-m-d h:i:s A");
   }
   
   if ($result->success)
   {
      if (isset($params["maintenanceDate"]) &&
          isset($params["employeeNumber"]) &&
          (isset($params["wcNumber"]) || isset($params["equipmentId"])) &&
          ((isset($params["typeId"]) || isset($params["categoryId"]) || isset($params["subcategoryId"]))) &&
          isset($params["shiftTime"]) &&
          isset($params["maintenanceTime"]) &&
          isset($params["comments"]))
      {
         // Required fields.
         $maintenancEntry->maintenanceDateTime = Time::startOfDay($params->get("maintenanceDate"));
         $maintenancEntry->employeeNumber = intval($params["employeeNumber"]);
         $maintenancEntry->wcNumber = isset($params["wcNumber"]) ? intval($params["wcNumber"]) : JobInfo::UNKNOWN_WC_NUMBER;
         $maintenancEntry->equipmentId = isset($params["equipmentId"]) ? intval($params["equipmentId"]) : EquipmentInfo::UNKNOWN_EQUIPMENT_ID;
         $maintenancEntry->typeId = intval($params["typeId"]);
         $maintenancEntry->shiftTime = intval($params["shiftTime"]);
         $maintenancEntry->maintenanceTime = intval($params["maintenanceTime"]);
         $maintenancEntry->comments = $params["comments"];
         
         //
         // Optional fields.
         //
         
         if (isset($params["jobNumber"]))
         {
            $maintenancEntry->jobNumber = $params["jobNumber"];
         }
         else
         {
            // Clear the value.
            $maintenancEntry->jobNumber = JobInfo::UNKNOWN_JOB_NUMBER;
         }
         
         $maintenancEntry->categoryId = isset($params["categoryId"]) ? $params->getInt("categoryId") : MaintenanceEntry::UNKNOWN_CATEGORY_ID;
         $maintenancEntry->subcategoryId = isset($params["subcategoryId"]) ? $params->getInt("subcategoryId") : MaintenanceEntry::UNKNOWN_SUBCATEGORY_ID;

         if (isset($params["partId"]))
         {
            // Use an exisiting part id.
            $maintenancEntry->partId = intval($params["partId"]);
         }
         else
         {
            // Clear the value.
            $maintenancEntry->partId = MachinePartInfo::UNKNOWN_PART_ID;
            
            if ((isset($params["newPartNumber"])) &&
                ($params["newPartNumber"] != MachinePartInfo::UNKNOWN_PART_NUMBER) &&
                (isset($params["newPartDescription"])))
            {
               // Create a new part id.
               
               $machinePartInfo = new MachinePartInfo();
               
               $machinePartInfo->partNumber = $params["newPartNumber"];
               $machinePartInfo->description = $params->get("newPartDescription");
               
               $dbaseResult = $database->addToPartInventory($machinePartInfo);
               
               if ($dbaseResult)
               {
                  $maintenancEntry->partId = $database->lastInsertId();
               }
            }
         }
         
         if ($maintenancEntry->maintenanceEntryId == MaintenanceEntry::UNKNOWN_ENTRY_ID)
         {
            $dbaseResult = $database->newMaintenanceEntry($maintenancEntry);
            
            if ($dbaseResult)
            {
               $result->entryId = $database->lastInsertId();
            }
         }
         else
         {
            $dbaseResult = $database->updateMaintenanceEntry($maintenancEntry);
            $result->entryId = $maintenancEntry->maintenanceEntryId;
         }
               
         if (!$dbaseResult)
         {
            $result->success = false;
            $result->error = "Database query failed.";
         }
      }
      else
      {
         $result->success = false;
         $result->error = "Missing parameters.";
      }
   }
   
   echo json_encode($result);
});
      
$router->add("deleteMaintenanceEntry", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   
   if (isset($params["entryId"]) &&
       is_numeric($params["entryId"]) &&
       (intval($params["entryId"]) != MaintenanceEntry::UNKNOWN_ENTRY_ID))
   {
      $entryId = intval($params["entryId"]);

      $dbaseResult = $database->deleteMaintenanceEntry($entryId);
         
      if ($dbaseResult)
      {
         $result->success = true;
      }
      else
      {
         $result->success = false;
         $result->error = "Database query failed.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("maintenanceCategories", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   
   if (isset($params["typeId"]))
   {
      $typeId = intval($params["typeId"]);
      
      $dbaseResult = $database->getMaintenanceCategories($typeId);
      
      if ($dbaseResult)
      {
         $result->success = true;
         
         // Requires mysqlnd driver
         // https://stackoverflow.com/questions/6694437/mysqli-fetch-all-not-a-valid-function
         //$result->maintenanceCategories = $dbaseResult->fetch_all(MYSQLI_ASSOC);
         
         $result->maintenanceCategories = array();
         while ($dbaseResult && ($row = $dbaseResult->fetch_assoc()))
         {
            $maintenanceCategory = new stdClass();
            $maintenanceCategory->categoryId = intval($row["categoryId"]);
            $maintenanceCategory->typeId = intval($row["typeId"]);
            $maintenanceCategory->label = $row["label"];

            $result->maintenanceCategories[] = $maintenanceCategory;
         }
         
         $result->selectedCategoryId = MaintenanceEntry::UNKNOWN_CATEGORY_ID;
         if (isset($params["entryId"]))
         {
            $maintenanceEntry = MaintenanceEntry::load($params->getInt("entryId"));
            
            if ($maintenanceEntry)
            {
               $result->selectedCategoryId = $maintenanceEntry->categoryId;
            }
         }
      }
      else
      {
         $result->success = false;
         $result->error = "Database query failed.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("maintenanceSubcategories", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   
   if (isset($params["categoryId"]))
   {
      $categoryId = intval($params["categoryId"]);
      
      $dbaseResult = $database->getMaintenanceSubcategories($categoryId);
      
      if ($dbaseResult)
      {
         $result->success = true;
         
         // Requires mysqlnd driver
         // https://stackoverflow.com/questions/6694437/mysqli-fetch-all-not-a-valid-function
         //$result->maintenanceSubcategories = $dbaseResult->fetch_all(MYSQLI_ASSOC);
         
         $result->maintenanceSubcategories = array();
         while ($dbaseResult && ($row = $dbaseResult->fetch_assoc()))
         {
            $maintenanceSubcategory = new stdClass();
            $maintenanceSubcategory->subcategoryId = intval($row["subcategoryId"]);
            $maintenanceSubcategory->categoryId = intval($row["categoryId"]);
            $maintenanceSubcategory->label = $row["label"];
            
            $result->maintenanceSubcategories[] = $maintenanceSubcategory;
         }
         
         $result->selectedSubcategoryId = MaintenanceEntry::UNKNOWN_SUBCATEGORY_ID;
         if (isset($params["entryId"]))
         {
            $maintenanceEntry = MaintenanceEntry::load($params->getInt("entryId"));
            
            if ($maintenanceEntry)
            {
               $result->selectedSubcategoryId = $maintenanceEntry->subcategoryId;
            }
         }
      }
      else
      {
         $result->success = false;
         $result->error = "Database query failed.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("materialHeat", function($params) {
   global $MATERIAL_CERTS_DIR;
   
   $result = new stdClass();
   $result->success = true;
   
   if (isset($params["heatNumber"]))
   {
      $heatNumber = $params["heatNumber"];
      $result->heatNumber = $heatNumber;
      
      $result->materialHeatInfo = MaterialHeatInfo::load($heatNumber);
      
      if ($result->materialHeatInfo)
      {
         $result->materialHeatInfo->certPath = "$MATERIAL_CERTS_DIR/{$result->materialHeatInfo->certFile}";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("materialData", function($params) {
   $result = array();
   
   $materialEntryStatus = MaterialEntryStatus::UNKNOWN;
   $dateType = FilterDateType::ENTRY_DATE;
   $startDate = Time::startOfDay(Time::now("Y-m-d"));
   $endDate = Time::endOfDay(Time::now("Y-m-d"));
   
   if (isset($params["status"]))
   {
      $materialEntryStatus = intval($params["status"]);
   }
      
   if (isset($params["dateType"]))
   {
      $dateType = $params->getInt("dateType");
   }
   
   if (isset($params["startDate"]))
   {
      $startDate = Time::startOfDay($params["startDate"]);
   }
   
   if (isset($params["endDate"]))
   {
      $endDate = Time::endOfDay($params["endDate"]);
   }
   
   if (isset($params["allUnissued"]))
   {      
      $materialEntryStatus = MaterialEntryStatus::RECEIVED;
      $startDate = null;
      $endDate = null;
      $dateType = null;
   }   
   
   $database = PPTPDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $dbaseResult = $database->getMaterialEntries($materialEntryStatus, $startDate, $endDate, ($dateType == FilterDateType::RECEIVE_DATE));
      
      $vendors = MaterialVendor::getMaterialVendors();
      
      foreach ($dbaseResult as $row)
      {
         $materialEntry = MaterialEntry::load(intval($row["materialEntryId"]));
         
         if ($materialEntry)
         {
            $materialEntry->locationLabel = MaterialLocation::getLabel($materialEntry->location);
            $materialEntry->materialStampLabel = MaterialStamp::getLabel($materialEntry->materialStamp);
            
            if ($materialEntry->materialHeatInfo)
            {
               $materialEntry->vendorName = $vendors[$materialEntry->materialHeatInfo->vendorId];
               $materialEntry->materialDescription = $materialEntry->materialHeatInfo->materialInfo->getMaterialDescription();
               $materialEntry->materialHeatInfo->materialInfo->typeLabel = MaterialType::getLabel($materialEntry->materialHeatInfo->materialInfo->type);
               $materialEntry->quantity = $materialEntry->getQuantity();
            }
                        
            $materialEntry->materialTicketCode = MaterialTicket::getMaterialTicketCode($materialEntry->materialEntryId);
            
            if ($materialEntry->isIssued())
            {
               $jobInfo = JobInfo::load($materialEntry->issuedJobId);
               
               if ($jobInfo)
               {
                  $materialEntry->issuedJobNumber = $jobInfo->jobNumber;
                  $materialEntry->issuedWCNumber = $jobInfo->wcNumber;
               }
            }
            
            $materialEntry->isIssued = $materialEntry->isIssued();
            $materialEntry->isAcknowledged = $materialEntry->isAcknowledged();
             
            $result[] = $materialEntry;
         }
      }
   }

   echo json_encode($result);
});

$router->add("saveMaterialEntry", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   $dbaseResult = null;
   
   $materialEntry = null;
   
   if (isset($params["entryId"]) &&
       is_numeric($params["entryId"]) &&
       (intval($params["entryId"]) != MaterialEntry::UNKNOWN_ENTRY_ID))
   {
      $entryId = intval($params["entryId"]);
      
      //  Updated entry
      $materialEntry = MaterialEntry::load($entryId);
      
      if (!$materialEntry)
      {
         $result->success = false;
         $result->error = "No existing material entry found.";
      }
   }
   else
   {
      // New entry.
      $materialEntry = new MaterialEntry();
      
      // Use current date/time as the entry time.
      $materialEntry->enteredDateTime = Time::now("Y-m-d h:i:s A");
   }
   
   if ($result->success)
   {
      // Required fields.
      if (isset($params["vendorHeatNumber"]) &&
          isset($params["tagNumber"]) &&
          isset($params["location"]) &&
          isset($params["pieces"]) &&
          isset($params["enteredUserId"]) &&
          isset($params["receivedDate"]))
      {
         $materialEntry->vendorHeatNumber = $params->get("vendorHeatNumber");
         $materialEntry->tagNumber = $params->get("tagNumber");
         $materialEntry->location = $params->getInt("location");
         $materialEntry->pieces = $params->getInt("pieces");
         $materialEntry->enteredUserId = $params->getInt("enteredUserId");
         $materialEntry->receivedDateTime = Time::startOfDay($params->get("receivedDate"));
         
         // Material heat update (optional).
         if (isset($params["vendorHeatNumber"]) &&
             isset($params["internalHeatNumber"]) &&
             isset($params["vendorId"]) &&
             (isset($params["materialPartNumber"]) || isset($params["newMaterialPartNumber"])) &&
             isset($params["materialType"]) &&
             isset($params["materialShape"]) &&
             isset($params["materialSize"]) &&
             isset($params["materialLength"]))
         {
            $vendorHeatNumber = $params["vendorHeatNumber"];
            
            $materialHeatInfo = MaterialHeatInfo::load($vendorHeatNumber);
            $newMaterialHeat = ($materialHeatInfo == null);
            
            if ($newMaterialHeat)
            {
               // New heat.
               $materialHeatInfo = new MaterialHeatInfo();
               $materialHeatInfo->vendorHeatNumber = $vendorHeatNumber;
            }
            
            $materialHeatInfo->internalHeatNumber = $params->getInt("internalHeatNumber");
            $materialHeatInfo->vendorId = $params->getInt("vendorId");
            
            // Material part number.
            if (isset($params["newMaterialPartNumber"]) && !empty($params["newMaterialPartNumber"]))
            {
               $materialHeatInfo->materialInfo->partNumber = $params->get("newMaterialPartNumber");
            }
            else
            {
               $materialHeatInfo->materialInfo->partNumber = $params->get("materialPartNumber");
            }
            
            // Material info.
            $materialHeatInfo->materialInfo->type = $params->getInt("materialType");
            $materialHeatInfo->materialInfo->shape = $params->getInt("materialShape");
            $materialHeatInfo->materialInfo->size = $params->getFloat("materialSize");
            $materialHeatInfo->materialInfo->length = $params->getInt("materialLength");
            
            if ($newMaterialHeat)
            {
               $dbaseResult = $database->newMaterialHeat($materialHeatInfo);
            }
            else
            {
               $dbaseResult = $database->updateMaterialHeat($materialHeatInfo);
            }
            
            if (!$dbaseResult)
            {
               $result->success = false;
               $result->error = "Database query failed.";
            }
         }

         // Material inspection (optional?)
         if (isset($params["acceptedPieces"]) &&
             isset($params["inspectedSize"]) &&
             isset($params["materialStamp"]) &&
             isset($params["poNumber"]))
         {
            $materialEntry->acceptedPieces = ($params["acceptedPieces"] === null) ? null : $params->getInt("acceptedPieces");
            $materialEntry->inspectedSize = ($params["inspectedSize"] === null) ? null : $params->getFloat("inspectedSize");
            $materialEntry->materialStamp = ($params["materialStamp"] === null) ? null : $params->getInt("materialStamp");
            $materialEntry->poNumber = ($params["poNumber"] === null) ? null : $params->get("poNumber");
         }

         if ($result->success)
         {
            if ($materialEntry->materialEntryId == MaterialEntry::UNKNOWN_ENTRY_ID)
            {
               $dbaseResult = $database->newMaterialEntry($materialEntry);
               
               if ($dbaseResult)
               {
                  $result->entryId = $database->lastInsertId();
               }
            }
            else
            {
               $dbaseResult = $database->updateMaterialEntry($materialEntry);
               $result->entryId = $materialEntry->materialEntryId;
            }
         
            if (!$dbaseResult)
            {
               $result->success = false;
               $result->error = "Database query failed.";
            }
            else
            {
               //
               // Process uploaded material certification.
               //
               
               if (isset($_FILES["materialCertFile"]) && ($_FILES["materialCertFile"]["name"] != ""))
               {
                  $uploadStatus = Upload::uploadMaterialCert($_FILES["materialCertFile"]);
                  
                  if ($uploadStatus == UploadStatus::UPLOADED)
                  {
                     $filename = basename($_FILES["materialCertFile"]["name"]);
                     
                     $database->setMaterialCert($materialEntry->materialHeatInfo->internalHeatNumber, $filename);
                  }
                  else
                  {
                     $result->success = false;
                     $result->error = "File upload failed! " . UploadStatus::toString($uploadStatus);
                  }
               }
            }
         }
      }
      else
      {
         $result->success = false;
         $result->error = "Missing parameters.";
      }
   }
   
   echo json_encode($result);
});
      
$router->add("deleteMaterialEntry", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   
   if (isset($params["entryId"]) &&
       is_numeric($params["entryId"]) &&
       (intval($params["entryId"]) != MaterialEntry::UNKNOWN_ENTRY_ID))
   {
      $entryId = intval($params["entryId"]);
      
      $dbaseResult = $database->deleteMaterialEntry($entryId);
      
      if ($dbaseResult)
      {
         $result->success = true;
      }
      else
      {
         $result->success = false;
         $result->error = "Database query failed.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("revokeMaterialEntry", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   if (isset($params["entryId"]) &&
       is_numeric($params["entryId"]) &&
       (intval($params["entryId"]) != MaterialEntry::UNKNOWN_ENTRY_ID))
   {
      $entryId = intval($params["entryId"]);
      $materialEntry = MaterialEntry::load($entryId);
      
      if ($materialEntry)
      {
         $materialEntry->revokeMaterial();
      }
      else
      {
         $result->success = false;
         $result->error = "No existing material entry found.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("issueMaterialEntry", function($params) {
   $result = new stdClass();
   $result->success = true;

   if (isset($params["entryId"]) &&
       is_numeric($params["entryId"]) &&
       (intval($params["entryId"]) != MaterialEntry::UNKNOWN_ENTRY_ID))
   {
      $entryId = intval($params["entryId"]);
      $materialEntry = MaterialEntry::load($entryId);

      if (!$materialEntry)
      {
         $result->success = false;
         $result->error = "No existing material entry found.";
      }
      else
      {
         if (isset($params["jobNumber"]) &&
             isset($params["wcNumber"]) &&
             isset($params["issuedUserId"]))
         {
            // Required fields.
            $jobNumber = $params->get("jobNumber");
            $wcNumber = $params->getInt("wcNumber");
            $employeeNumber = $params->getInt("issuedUserId");
            
            $jobId = JobInfo::getJobIdByComponents($jobNumber, $wcNumber);
            $userInfo = UserInfo::load($employeeNumber);
            
            if ($jobId == JobInfo::UNKNOWN_JOB_ID)
            {
               $result->success = false;
               $result->error = "Failed to look up job.";
            }
            else if (!$userInfo)
            {
               $result->success = false;
               $result->error = "Failed to look up user.";
            }
            else
            {
               $materialEntry->issueMaterial($jobId, $employeeNumber);
            }
         }
         else
         {
            $result->success = false;
            $result->error = "Missing parameters.";
         }
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("acknowledgeMaterialEntry", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   if (isset($params["entryId"]) &&
       is_numeric($params["entryId"]) &&
       (intval($params["entryId"]) != MaterialEntry::UNKNOWN_ENTRY_ID))
   {
      $entryId = intval($params["entryId"]);
      $materialEntry = MaterialEntry::load($entryId);
      
      if (!$materialEntry)
      {
         $result->success = false;
         $result->error = "No existing material entry found.";
      }
      else if (!$materialEntry->isIssued())
      {
         $result->success = false;
         $result->error = "Material has not been issued.";
      }
      else
      {
         if (isset($params["acknowledgedUserId"]))
         {
            // Required fields.
            $employeeNumber = $params->getInt("acknowledgedUserId");
            
            $userInfo = UserInfo::load($employeeNumber);
            
            if (!$userInfo)
            {
               $result->success = false;
               $result->error = "Failed to look up user.";
            }
            else
            {
               $materialEntry->acknowledge($employeeNumber);
            }
         }
         else
         {
            $result->success = false;
            $result->error = "Missing parameters.";
         }
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("unacknowledgeMaterialEntry", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   if (isset($params["entryId"]) &&
       is_numeric($params["entryId"]) &&
       (intval($params["entryId"]) != MaterialEntry::UNKNOWN_ENTRY_ID))
   {
      $entryId = intval($params["entryId"]);
      $materialEntry = MaterialEntry::load($entryId);
      
      if (!$materialEntry)
      {
         $result->success = false;
         $result->error = "No existing material entry found.";
      }
      else if (!$materialEntry->isAcknowledged())
      {
         $result->success = false;
         $result->error = "Material has not been acknowledged.";
      }
      else
      {
         $materialEntry->unacknowledge();
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("materialTicket", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   if (is_numeric($params["materialTicketId"]))
   {
      $materialTicket = new MaterialTicket(intval($params["materialTicketId"]));
      
      if ($materialTicket)
      {
         $result->success = true;
         $result->panTicketId = $materialTicket->materialTicketId;
         $result->labelXML = $materialTicket->labelXML;
      }
      else
      {
         $result->success = false;
         $result->error = "Failed to create material ticket.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});
   
$router->add("printMaterialTicket", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   
   if (is_numeric($params["materialTicketId"]) &&
       isset($params["printerName"]) &&
       is_numeric($params["copies"]))
   {
      $materialTicket = new MaterialTicket(intval($params["materialTicketId"]));
      
      if ($materialTicket)
      {
         $printJob = new PrintJob();
         $printJob->owner = Authentication::getAuthenticatedUser()->employeeNumber;
         $printJob->dateTime = Time::now("Y-m-d H:i:s");
         $printJob->description = $materialTicket->printDescription;
         $printJob->printerName = $params["printerName"];
         $printJob->copies = intval($params["copies"]);
         $printJob->status = PrintJobStatus::QUEUED;
         $printJob->xml = $materialTicket->labelXML;
         
         $dbaseResult = $database->newPrintJob($printJob);
         
         if ($dbaseResult)
         {
            $result->success = true;
         }
         else
         {
            $result->success = false;
            $result->error = "Database query failed.";
         }
      }
      else
      {
         $result->success = false;
         $result->error = "Failed to create material ticket.";
      }
      
      // Store preferred printer for session.
      $_SESSION["preferredPrinter"] = $params["printerName"];
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});

$router->add("shippingCardData", function($params) {
   $result = array();
   
   $dateType = FilterDateType::ENTRY_DATE;
   $startDate = Time::startOfDay(Time::now("Y-m-d"));
   $endDate = Time::endOfDay(Time::now("Y-m-d"));
   
   if (isset($params["filters"]))
   {
      foreach ($params["filters"] as $filter)
      {
         if ($filter->field == "date")
         {
            if ($filter->type == ">=")
            {
               $startDate = Time::startOfDay($filter->value);
            }
            else if ($filter->type == "<=")
            {
               $endDate = Time::endOfDay($filter->value);
            }
         }
      }
   }
   
   if (isset($params["dateType"]))
   {
      $dateType = $params->getInt("dateType");
   }
   
   if (isset($params["startDate"]))
   {
      $startDate = Time::startOfDay($params["startDate"]);
   }
   
   if (isset($params["endDate"]))
   {
      $endDate = Time::endOfDay($params["endDate"]);
   }
   
   $employeeNumberFilter =
      (Authentication::checkPermissions(Permission::VIEW_OTHER_USERS)) ?
         UserInfo::UNKNOWN_EMPLOYEE_NUMBER :                      // No filter
         Authentication::getAuthenticatedUser()->employeeNumber;  // Filter on authenticated user
   
   $database = PPTPDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $dbResult = $database->getShippingCards($employeeNumberFilter, $startDate, $endDate, ($dateType == FilterDateType::MANUFACTURING_DATE));
      
      // Populate data table.
      foreach ($dbResult as $row)
      {
         $shippingCardInfo = new ShippingCardInfo();
         $shippingCardInfo->initialize($row);
         
         $userInfo = UserInfo::load($shippingCardInfo->employeeNumber);
         if ($userInfo)
         {
            $shippingCardInfo->shipper = $userInfo->getFullName() . " (" . $shippingCardInfo->employeeNumber . ")";
         }

         $shippingCardInfo->panTicketCode = "0000";
         
         $jobId = $shippingCardInfo->jobId;
         
         $operator = $shippingCardInfo->operator;
         
         if ($shippingCardInfo->timeCardId != TimeCardInfo::UNKNOWN_TIME_CARD_ID)
         {
            $timeCardInfo = TimeCardInfo::load($shippingCardInfo->timeCardId);
            
            if ($timeCardInfo)
            {
               $shippingCardInfo->panTicketCode = PanTicket::getPanTicketCode($timeCardInfo->timeCardId);
               
               $jobId = $timeCardInfo->jobId;
               
               $operator = $timeCardInfo->employeeNumber;
               
               $shippingCardInfo->manufactureDate = $timeCardInfo->manufactureDate;
            }
         }
         
         $jobInfo = JobInfo::load($jobId);
         if ($jobInfo)
         {
            $shippingCardInfo->jobNumber = $jobInfo->jobNumber;
            $shippingCardInfo->wcNumber = $jobInfo->wcNumber;
         }
         
         $userInfo = UserInfo::load($operator);
         if ($userInfo)
         {
            $shippingCardInfo->operatorName = $userInfo->getFullName() .  " (" . $operator . ")";
         }
         
         $shippingCardInfo->isNew = Time::isNew($shippingCardInfo->dateTime, Time::NEW_THRESHOLD);
         $shippingCardInfo->incompleteShiftTime = $shippingCardInfo->incompleteShiftTime();
         $shippingCardInfo->incompleteShippingTime = $shippingCardInfo->incompleteShippingTime();
         $shippingCardInfo->incompletePartCount = $shippingCardInfo->incompletePartCount();
         $shippingCardInfo->activityLabel = ShippingActivity::getLabel($shippingCardInfo->activity);
         $shippingCardInfo->scrapTypeLabel = ScrapType::getLabel($shippingCardInfo->scrapType);
         
         $result[] = $shippingCardInfo;
      }
   }
   
   echo json_encode($result);
});

$router->add("saveShippingCard", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();
   $dbaseResult = null;
   
   $timeCardInfo = null;
   
   if (isset($params["shippingCardId"]) &&
       is_numeric($params["shippingCardId"]) &&
       (intval($params["shippingCardId"]) != ShippingCardInfo::UNKNOWN_SHIPPING_CARD_ID))
   {
      $shippingCardId = intval($params["shippingCardId"]);
      
      //  Updated entry
      $shippingCardInfo = ShippingCardInfo::load($shippingCardId);
      
      if (!$shippingCardInfo)
      {
         $result->success = false;
         $result->error = "No existing shipping card found.";
      }
   }
   else
   {
      // New shipping card.
      $shippingCardInfo = new ShippingCardInfo();
      
      // Use current date/time as time card time.
      $shippingCardInfo->dateTime = Time::now("Y-m-d h:i:s A");
   }
   
   if ($result->success)
   {
      if (isset($params["panTicketCode"]) &&
          ($params["panTicketCode"] != ""))
      {
         //
         // Pan ticket entry
         //
         
         $panTicketId = PanTicket::getPanTicketId($params["panTicketCode"]);
         
         // Validate panTicketId.
         if (TimeCardInfo::load($panTicketId) != null)
         {
            $shippingCardInfo->timeCardId = $panTicketId;
         }
         else
         {
            $result->success = false;
            $result->error = "Invalid pan ticket code.";
         }
      }
      else if (isset($params["jobNumber"]) &&
               isset($params["wcNumber"]) &&
               isset($params["manufactureDate"]) &&
               isset($params["operator"]))
      {
         //
         // Manual entry
         //
         
         $jobId = JobInfo::getJobIdByComponents($params->get("jobNumber"), $params->getInt("wcNumber"));
         
         if ($jobId != JobInfo::UNKNOWN_JOB_ID)
         {
            $shippingCardInfo->jobId = $jobId;
            $shippingCardInfo->manufactureDate = $params["manufactureDate"];
            $shippingCardInfo->operator = intval($params["operator"]);
         }
         else
         {
            $result->success = false;
            $result->error = "Failed to lookup job ID.";
         }
      }
      else
      {
         $result->success = false;
         $result->error = "Missing parameters.";
      }
      
      if ($result->success)
      {
         // Required parameters.
         if (isset($params["shipper"]) &&
             isset($params["shiftTime"]) &&
             isset($params["shippingTime"]) &&
             isset($params["activity"]) &&
             isset($params["partCount"]) &&
             isset($params["scrapCount"]))
         {
            $shippingCardInfo->employeeNumber = intval($params["shipper"]);
            $shippingCardInfo->shiftTime = intval($params["shiftTime"]);
            $shippingCardInfo->shippingTime = intval($params["shippingTime"]);
            $shippingCardInfo->activity = intval($params["activity"]);
            $shippingCardInfo->partCount = intval($params["partCount"]);
            $shippingCardInfo->scrapCount = intval($params["scrapCount"]);
            
            // Optional parameters.
            if (isset($params["scrapType"]))
            {
               $shippingCardInfo->scrapType = intval($params["scrapType"]);
            }
            
            // Optional parameters.
            if (isset($params["comments"]))
            {
               $shippingCardInfo->comments = $params["comments"];
            }
               
            if ($shippingCardInfo->shippingCardId == ShippingCardInfo::UNKNOWN_SHIPPING_CARD_ID)
            {
               $dbaseResult = $database->newShippingCard($shippingCardInfo);
               
               if ($dbaseResult)
               {
                  $result->shippingCardId = $database->lastInsertId();
               }
            }
            else
            {
               $dbaseResult = $database->updateShippingCard($shippingCardInfo);
               $result->shippingCardId = $shippingCardInfo->shippingCardId;
            }
            
            if ($result->success && !$dbaseResult)
            {
               $result->success = false;
               $result->error = "Database query failed.";
            }
         }
         else
         {
            $result->success = false;
            $result->error = "Missing parameters.";
         }
      }
   }
   
   echo json_encode($result);
});
   
$router->add("deleteShippingCard", function($params) {
   $result = new stdClass();
   $result->success = true;
   
   $database = PPTPDatabase::getInstance();

   if (isset($params["shippingCardId"]) &&
       is_numeric($params["shippingCardId"]) &&
       (intval($params["shippingCardId"]) != ShippingCardInfo::UNKNOWN_SHIPPING_CARD_ID))
   {
      $shippingCardId = intval($params["shippingCardId"]);
      
      $shippingCardInfo = ShippingCardInfo::load($shippingCardId);
      
      if ($shippingCardInfo)
      {
         $dbaseResult = $database->deleteShippingCard($shippingCardId);
         
         if ($dbaseResult)
         {
            $result->success = true;
         }
         else
         {
            $result->success = false;
            $result->error = "Database query failed.";
         }
      }
      else
      {
         $result->success = false;
         $result->error = "No existing shipping card found.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Missing parameters.";
   }
   
   echo json_encode($result);
});


$router->add("runCronJobs", function($params) {
   $result = new stdClass();
   $result->success = true;

   CronJobManager::update();
   
   echo json_encode($result);
});

$router->route();
?>