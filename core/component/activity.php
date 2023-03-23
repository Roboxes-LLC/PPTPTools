<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/activityType.php';
require_once ROOT.'/core/common/cartTrackerDatabase.php';
require_once ROOT.'/core/component/cart.php';
require_once ROOT.'/core/component/customer.php';
require_once ROOT.'/core/component/site.php';
require_once ROOT.'/core/component/user.php';

class Activity
{
   const UNKNOWN_ACTIVITY_ID = 0;
   
   const UNKNOWN_OBJECT = null;
   
   const MAX_OBJECTS = 3;
   
   public $activityId;
   public $siteId;   
   public $dateTime;
   public $author;
   public $activityType;
   public $objects;
   
   public function __construct()
   {
      $this->activityId = Activity::UNKNOWN_ACTIVITY_ID;
      $this->siteId = Site::UNKNOWN_SITE_ID;
      $this->dateTime = null;
      $this->author = User::UNKNOWN_USER_ID;
      $this->activityType = ActivityType::UNKNOWN;
      $this->objects = array();
      for ($index = 0; $index < Activity::MAX_OBJECTS; $index++)
      {
         $this->objects[$index] = Activity::UNKNOWN_OBJECT;
      }
   }
   
   // **************************************************************************
   // Component interface
   
   public function initialize($row)
   {
      $this->activityId = intval($row['activityId']);
      $this->siteId = intval($row['siteId']);
      $this->dateTime =  $row['dateTime'] ? Time::fromMySqlDate($row['dateTime'], "Y-m-d H:i:s") : null;
      $this->author = intval($row['author']);
      $this->activityType = intval($row['activityType']);
      $this->objects[0] = $row['object_0'];
      $this->objects[1] = $row['object_1'];
      $this->objects[2] = $row['object_2'];
   }
   
   public static function load($activityId)
   {
      $activity = null;
      
      $result = CartTrackerDatabase::getInstance()->getActivity($activityId);
      
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
         $success = CartTrackerDatabase::getInstance()->addActivity($activity);
         
         $activity->activityId = intval(CartTrackerDatabase::getInstance()->lastInsertId());
      }
      else
      {
         $success = CartTrackerDatabase::getInstance()->updateActivity($activity);
      }
      
      return ($success);
   }
   
   public static function delete($activityId)
   {
      return(CartTrackerDatabase::getInstance()->deleteActivity($activityId));
   }
   
   // **************************************************************************
   
   public function getDescription()
   {
      $description = "";
      
      $authorUsername = User::getUsername($this->author);
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
         
         case ActivityType::ADD_SITE:
         {
            $site = Site::load(intval($this->objects[0]));
            $siteName = $site ? $site->siteName : $this->objects[1];
            
            $description = "User $authorUsername added new site $siteName";
            break;
         }
            
         case ActivityType::EDIT_SITE:
         {
            $site = Site::load(intval($this->objects[0]));
            $siteName = $site ? $site->siteName : $this->objects[1];
            
            $description = "User $authorUsername edited properties of site $siteName";
            break;
         }
            
         case ActivityType::DELETE_SITE:
         {
            $siteName = $this->objects[1];
            
            $description = "User $authorUsername deleted site $siteName";
            break;
         }
            
         case ActivityType::ADD_USER:
         {
            $username = User::getUsername(intval($this->objects[0]));
            $username = ($username == "") ? $this->objects[1] : $username;
            
            $description = "User $authorUsername added new user $username";
            
            break;
         }
            
         case ActivityType::EDIT_USER:
         {
            $username = User::getUsername(intval($this->objects[0]));
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
                  
         case ActivityType::ADD_CUSTOMER:
         {
            $customerName = Customer::getCustomerName(intval($this->objects[0]));
            $customerName = ($customerName == "") ? $this->objects[1] : $customerName;
            
            $description = "User $authorUsername added new customer $customerName";
            break;
         }
            
         case ActivityType::EDIT_CUSTOMER:
         {
            $customerName = Customer::getCustomerName(intval($this->objects[0]));
            $customerName = ($customerName == "") ? $this->objects[1] : $customerName;
            
            $description = "User $authorUsername edited properties of customer $customerName";
            break;
         }
            
         case ActivityType::DELETE_CUSTOMER:
         {
            $customerName = Customer::getCustomerName(intval($this->objects[0]));
            $customerName = ($customerName == "") ? $this->objects[1] : $customerName;
            
            $description = "User $authorUsername deleted customer $customerName";
            break;
         }
            
         case ActivityType::ADD_CART:
         {
            $cartIdentifier = Cart::getCartIdentifier($this->objects[0]);
            $cartIdentifier = ($cartIdentifier == "") ? $this->objects[1] : $cartIdentifier;
            
            $description = "User $authorUsername added a new cart $cartIdentifier";
            break;
         }
            
         case ActivityType::EDIT_CART:
         {
            $cartIdentifier = Cart::getCartIdentifier($this->objects[0]);
            $cartIdentifier = ($cartIdentifier == "") ? $this->objects[1] : $cartIdentifier;
            
            $description = "User $authorUsername edited the properties of cart $cartIdentifier";
            break;
         }
            
         case ActivityType::DELETE_CART:
         {
            $cartIdentifier = Cart::getCartIdentifier($this->objects[0]);
            $cartIdentifier = ($cartIdentifier == "") ? $this->objects[1] : $cartIdentifier;
            
            $description = "User $authorUsername deleted cart $cartIdentifier";
            break;
         }
            
         case ActivityType::SCAN_CART:
         {
            $cartIdentifier = Cart::getCartIdentifier($this->objects[0]);
            $cartIdentifier = ($cartIdentifier == "") ? $this->objects[1] : $cartIdentifier;
            
            $description = "User $authorUsername scanned cart $cartIdentifier";
            break;
         }
            
         case ActivityType::CHECK_IN_CART:
         {
            $cartIdentifier = Cart::getCartIdentifier($this->objects[0]);
            $cartIdentifier = ($cartIdentifier == "") ? "<unknown>" : $cartIdentifier;
            
            $customerName = Customer::getCustomerName($this->objects[1]);
            $customerName = ($customerName == "") ? "<unknown>" : $customerName;
            
            $description = "User $authorUsername checked in cart $cartIdentifier from customer $customerName";
            break;
         }
            
         case ActivityType::CHECK_OUT_CART:
         {
            $cartIdentifier = Cart::getCartIdentifier($this->objects[0]);
            $cartIdentifier = ($cartIdentifier == "") ? "<unknown>" : $cartIdentifier;
            
            $customerName = Customer::getCustomerName($this->objects[1]);
            $customerName = ($customerName == "") ? "<unknown>" : $customerName;
            
            $description = "User $authorUsername checked out cart $cartIdentifier to customer $customerName";
            
            break;
         }
            
         case ActivityType::IN_CIRCULATION_CART:
         {
            $cartIdentifier = Cart::getCartIdentifier($this->objects[0]);
            $cartIdentifier = ($cartIdentifier == "") ? $this->objects[1] : $cartIdentifier;
            
            $description = "User $authorUsername put cart $cartIdentifier in circulation";
            break;
         }
            
         case ActivityType::OUT_OF_CIRCULATION_CART:
         {
            $cartIdentifier = Cart::getCartIdentifier($this->objects[0]);
            $cartIdentifier = ($cartIdentifier == "") ? $this->objects[1] : $cartIdentifier;
            
            $description = "User $authorUsername took cart $cartIdentifier out of circulation";
            break;
         }
         
         case ActivityType::NUDGE_CUSTOMER:
         {
            $customerName = Customer::getCustomerName($this->objects[0]);
            $customerName = ($customerName == "") ? "<unknown>" : $customerName;
                        
            $description = "User $authorUsername nudged customer $customerName";
            break;
         }
            
         default:
         {
            $description = "Unknown activity";
            break;
         }
      }
      
      return ($description);
   }
   
   public function getCartActivityDescription()
   {
      $description = "";
      
      switch ($this->activityType)
      {
         case ActivityType::ADD_CART:
         {
            $description = "Added";
            break;
         }
         
         case ActivityType::EDIT_CART:
         {
            $description = "Edited";
            break;
         } 
         
         case ActivityType::DELETE_CART:
         {
            $description = "Deleted";
            break;
         }

         case ActivityType::SCAN_CART:
         {
            $description = "Scanned";
            break;
         }            
         
         case ActivityType::CHECK_IN_CART:
         {
            $description = "Checked in";
            break;
         }
            
         case ActivityType::CHECK_OUT_CART:
         {
            $customerId = $this->objects[1];
            $customerName = Customer::getCustomerName($customerId);
            $customerName = ($customerName == "") ? "<unknown>" : $customerName;
            
            $description = "Checked out to $customerName";
            break;
         }
            
         case ActivityType::IN_CIRCULATION_CART:
         {
            $description = "Put in circulation";
            break;
         }
            
         case ActivityType::OUT_OF_CIRCULATION_CART:
         {
            $description = "Taken out of circulation";
            break;
         }
            
         default:
         {
            break;
         }
      }
      
      return ($description);
   }
}

?>