<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/component/activity.php';
   
class ActivityLog
{
   public static function getActivities($siteId, $startDateTime, $endDateTime)
   {
      $activities = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getActivities($siteId, $startDateTime, $endDateTime);
      
      foreach ($result as $row)
      {
         $activity = new Activity();
         $activity->initialize($row);
         
         $activities[] = $activity;
      }
      
      return ($activities);
   }
   
   public static function getActivitiesForUser($startDateTime, $endDateTime, $userId = UserInfo::UNKNOWN_EMPLOYEE_NUMBER, $orderAscending = true)
   {
      $activities = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getActivitiesForUser($startDateTime, $endDateTime, $userId, $orderAscending);
      
      foreach ($result as $row)
      {
         $activity = new Activity();
         $activity->initialize($row);
         
         $activities[] = $activity;
      }
      
      return ($activities);
   }
   
   public static function getActivitiesForQuote($quoteId)
   {
      $activities = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getActivitiesForQuote($quoteId);
      
      foreach ($result as $row)
      {
         $activity = new Activity();
         $activity->initialize($row);
         
         $activities[] = $activity;
      }
      
      return ($activities);
   }
      
   public static function logComponentActivity($author, $activityType, $componentId, $componentLabel = null)
   {
      return (ActivityLog::createLogEntry($author, $activityType, array($componentId, $componentLabel)));
   }
   
   public static function logLogIn($siteId, $author)
   {
      return (ActivityLog::createLogEntry($author, ActivityType::LOG_IN, array()));
   }
   
   public static function logLogOut($author)
   {
      return (ActivityLog::createLogEntry($author, ActivityType::LOG_OUT, array()));
   }
   
   public static function logApproveQuote($author, $componentId, $componentLabel, $notes)
   {
      return (ActivityLog::createLogEntry($author, ActivityType::APPROVE_QUOTE, array($componentId, $componentLabel, $notes)));
   }
   
   public static function logUnapproveQuote($author, $componentId, $componentLabel, $notes)
   {
      return (ActivityLog::createLogEntry($author, ActivityType::UNAPPROVE_QUOTE, array($componentId, $componentLabel, $notes)));
   }
   
   public static function logAcceptQuote($author, $componentId, $componentLabel, $notes)
   {
      return (ActivityLog::createLogEntry($author, ActivityType::ACCEPT_QUOTE, array($componentId, $componentLabel, $notes)));
   }
   
   public static function logRejectQuote($author, $componentId, $componentLabel, $notes)
   {
      return (ActivityLog::createLogEntry($author, ActivityType::REJECT_QUOTE, array($componentId, $componentLabel, $notes)));
   }
   
   public static function deleteActivity($activityId)
   {
      return (PPTPDatabaseAlt::getInstance()->deleteActivity($activityId));
   }
      
   private static function createLogEntry($author, $activityType, $objects)
   {
      $activity = new Activity();
      
      $activity->dateTime = Time::now();
      $activity->author = $author;
      $activity->activityType = $activityType;         
         
      for ($index = 0; $index < Activity::MAX_OBJECTS; $index++)
      {
         if (isset($objects[$index]))
         {
            $activity->objects[$index] = substr($objects[$index], 0, Activity::MAX_OBJECT_LENGTH);
         }
      }
      
      $success = Activity::save($activity);   
      
      return ($success);
   }
}
