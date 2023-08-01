<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/userInfo.php';
require_once ROOT.'/core/common/activityType.php';
require_once ROOT.'/core/common/pptpDatabase.php';

class Activity
{
   const UNKNOWN_ACTIVITY_ID = 0;
   
   const UNKNOWN_OBJECT = null;
   
   const MAX_OBJECTS = 3;
   
   const MAX_OBJECT_LENGTH = 32;
   
   public $activityId;
   public $dateTime;
   public $author;
   public $activityType;
   public $objects;
   
   public function __construct()
   {
      $this->activityId = Activity::UNKNOWN_ACTIVITY_ID;
      $this->dateTime = null;
      $this->author = UserInfo::UNKNOWN_USER_ID;
      $this->activityType = ActivityType::UNKNOWN;
      $this->objects = array();
      for ($index = 0; $index < Activity::MAX_OBJECTS; $index++)
      {
         $this->objects[$index] = Activity::UNKNOWN_OBJECT;
      }
   }
   
   // **************************************************************************
   // Component interface
   
   public static function load($activityId)
   {
      $activity = null;
      
      $result = PPTPDatabaseAlt::getInstance()->getActivity($activityId);
      
      if ($result && ($row = $result[0]))
      {
         $activity = new Activity();
         
         $activity->initialize($row);
      }
      
      return ($activity);
   }
   
   public static function save($activity)
   {
      $success = false;
      
      if ($activity->activityId == Site::UNKNOWN_SITE_ID)
      {
         $success = PPTPDatabaseAlt::getInstance()->addActivity($activity);
         
         $activity->activityId = intval(PPTPDatabaseAlt::getInstance()->lastInsertId());
      }
      else
      {
         $success = PPTPDatabaseAlt::getInstance()->updateActivity($activity);
      }
      
      return ($success);
   }
   
   public static function delete($activityId)
   {
      return(PPTPDatabaseAlt::getInstance()->deleteActivity($activityId));
   }
   
   // **************************************************************************
   
   public function getDescription()
   {
      $description = "";
      
      $authorUsername = UserInfo::getLink($this->author);
      $authorUsername = ($authorUsername == "") ? "<unknown>" : $authorUsername;
      
      switch ($this->activityType)
      {         
         case ActivityType::LOG_IN:
         {
            $description = "User $authorUsername logged in";
            break;
         }
            
         case ActivityType::LOG_OUT:
         {
            $description = "User $authorUsername logged out";
            break;
         }
            
         case ActivityType::ADD_USER:
         {
            $username = UserInfo::getLink(intval($this->objects[0]));
            $username = ($username == "") ? $this->objects[1] : $username;
            
            $description = "User $authorUsername added new user $username";
            
            break;
         }
            
         case ActivityType::EDIT_USER:
         {
            $username = UserInfo::getLink(intval($this->objects[0]));
            $username = ($username == "") ? $this->objects[1] : $username;
            
            $description = "User $authorUsername edited properties of user $username";
            break;
         }
            
         case ActivityType::DELETE_USER:
         {
            $username = $this->objects[1];
            
            $description = "User $authorUsername deleted user $username";
            break;
         }
         
         case ActivityType::ADD_CONTACT:
         {
            $contactName = Contact::getLink(intval($this->objects[0]));
            $contactName = ($contactName == "") ? $this->objects[1] : $contactName;
            
            $description = "User $authorUsername added new contact $contactName";
            break;
         }
            
         case ActivityType::EDIT_CONTACT:
         {
            $contactName = Contact::getLink(intval($this->objects[0]));
            $contactName = ($contactName == "") ? $this->objects[1] : $contactName;
            
            $description = "User $authorUsername edited properties of contact $contactName";
            break;
         }
            
         case ActivityType::DELETE_CONTACT:
         {
            $contactName = $this->objects[1];
            
            $description = "User $authorUsername deleted contact $contactName";
            break;
         }
         
         case ActivityType::ADD_CUSTOMER:
         {
            $customerName = Customer::getLink(intval($this->objects[0]));
            $customerName = ($categoryName == "") ? $this->objects[1] : $customerName;
            
            $description = "User $authorUsername added new category $customerName";
            break;
         }
            
         case ActivityType::EDIT_CUSTOMER:
         {
            $customerName = Customer::getLink(intval($this->objects[0]));
            $customerName = ($categoryName == "") ? $this->objects[1] : $customerName;
            
            
            $description = "User $authorUsername renamed category $customerName";
            break;
         }
            
         case ActivityType::DELETE_CUSTOMER:
         {
            $customerName = $this->objects[1];
            
            $description = "User $authorUsername deleted category $customerName";
            break;
         }
            
         default:
         {
            $description = "Unknown activity";
            break;
         }
      }
      
      // Tabulator has an odd quirk where it removes whitespace before an after links.
      $description = StringUtils::addTabulatorLinkSpaces($description);
      
      return ($description);
   }
   
   public function initialize($row)
   {
      $this->activityId = intval($row['activityId']);
      $this->siteId = intval($row['siteId']);
      $this->dateTime =  Time::fromMySqlDate($row['dateTime']);
      $this->author = intval($row['author']);
      $this->activityType = intval($row['activityType']);
      $this->objects[0] = $row['object_0'];
      $this->objects[1] = $row['object_1'];
      $this->objects[2] = $row['object_2'];
   }
}

?>