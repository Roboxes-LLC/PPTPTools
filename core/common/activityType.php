<?php

abstract class ActivityType
{
   const UNKNOWN = 0;
   const FIRST = 1;
   // Log in/out
   const LOG_IN = ActivityType::FIRST;
   const LOG_OUT = 2;
   // User config
   const ADD_USER = 6;
   const EDIT_USER = 7;
   const DELETE_USER = 8;
   // Customer config
   const ADD_CUSTOMER = 9;
   const EDIT_CUSTOMER = 10;
   const DELETE_CUSTOMER = 11;
   // Contact config
   const ADD_CONTACT = 12;
   const EDIT_CONTACT = 13;
   const DELETE_CONTACT = 14;
   // Quote
   const ADD_QUOTE = 15;
   const EDIT_QUOTE = 16;
   const DELETE_QUOTE = 17;
   const ESTIMATE_QUOTE = 18; 
   const APPROVE_QUOTE = 19;
   const UNAPPROVE_QUOTE = 20;
   const SEND_QUOTE = 21;
   const ACCEPT_QUOTE = 22;
   const REJECT_QUOTE = 23;
   const REVISE_QUOTE = 24;
   const PASS_QUOTE = 25;
   //
   const LAST = 26;
   const COUNT = ActivityType::LAST - ActivityType::FIRST;
   
   public static $quoteActivites = 
      array(
         ActivityType::ADD_QUOTE,
         ActivityType::EDIT_QUOTE,
         ActivityType::DELETE_QUOTE,
         ActivityType::ESTIMATE_QUOTE,
         ActivityType::APPROVE_QUOTE,
         ActivityType::UNAPPROVE_QUOTE,
         ActivityType::SEND_QUOTE,
         ActivityType::ACCEPT_QUOTE,
         ActivityType::REJECT_QUOTE,
         ActivityType::REVISE_QUOTE,
         ActivityType::PASS_QUOTE,
      );
      
   public static function getLabel($activityType)
   {
      $labels = 
         array(
            "",
            "Log In",
            "Log Out",
            "Add User", 
            "Edit User", 
            "Delete User", 
            "Add Customer", 
            "Edit Customer", 
            "Delete Customer", 
            "Add Contact",
            "Edit Contact",
            "Delete Contact",
            "Add Quote",
            "Edit Quote",
            "Delete Quote",
            "Estimate Quote",
            "Approve Quote",
            "Unapprove Quote",
            "Send Quote",
            "Accept Quote",
            "Reject Quote",
            "Revise Quote",
            "Pass On Quote"                      
         );
      
      return ($labels[$activityType]);
   }
   
   public static function getIcon($activityType)
   {
      $icon = null;
      
      switch ($activityType)
      {
         case ActivityType::LOG_IN:
         case ActivityType::SWITCH_SITE:
         {
            $icon = "login";
            break;
         }
         
         case ActivityType::LOG_OUT:
         {
            $icon = "logout";
            break;
         }
         
         case ActivityType::ADD_QUOTE:
         {
            $icon = "add";
            break;
         }
         
         case ActivityType::EDIT_QUOTE:
         {
            $icon = "edit";
            break;
         }
         
         case ActivityType::APPROVE_QUOTE:
         {
            $icon = "thumb_up_alt";
            break;
         }
      
         default:
         {
            break;
         }
      }
      
      return ($icon);
   }
}

?>