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
   const ANNOTATE_QUOTE = 26;
   const ADD_QUOTE_ATTACHMENT = 27;
   const DELETE_QUOTE_ATTACHMENT = 28;
   // Corrective Action
   const ADD_CORRECTIVE_ACTION = 29;
   const EDIT_CORRECTIVE_ACTION = 30;
   const DELETE_CORRECTIVE_ACTION = 31;
   const ADD_CORRECTIVE_ACTION_ATTACHMENT = 32;
   const DELETE_CORRECTIVE_ACTION_ATTACHMENT = 33;
   const APPROVE_CORRECTIVE_ACTION = 34;
   const UNAPPROVE_CORRECTIVE_ACTION = 35;
   const ANNOTATE_CORRECTIVE_ACTION = 36;
   //
   const LAST = 37;
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
         ActivityType::ANNOTATE_QUOTE,
      );
      
   public static $correctiveActionActivites =
      array(
         ActivityType::ADD_CORRECTIVE_ACTION,
         ActivityType::EDIT_CORRECTIVE_ACTION,
         ActivityType::DELETE_CORRECTIVE_ACTION,
         ActivityType::ADD_CORRECTIVE_ACTION_ATTACHMENT,
         ActivityType::DELETE_CORRECTIVE_ACTION_ATTACHMENT,
         ActivityType::APPROVE_CORRECTIVE_ACTION,
         ActivityType::UNAPPROVE_CORRECTIVE_ACTION,
         ActivityType::ANNOTATE_CORRECTIVE_ACTION,
      );
      
   public static $activitiesWithNotes =
      array(
         ActivityType::APPROVE_QUOTE,
         ActivityType::UNAPPROVE_QUOTE,
         ActivityType::ACCEPT_QUOTE,
         ActivityType::REJECT_QUOTE,
         ActivityType::APPROVE_CORRECTIVE_ACTION,
         ActivityType::UNAPPROVE_CORRECTIVE_ACTION
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
            "Pass On Quote",
            "Annotate Quote",
            "Add Attachment",
            "Remove Attachment",
            "Add Corrective Action Request",
            "Edit Corrective Action Request",
            "Add Attachment",
            "Remove Attachment",
            "Approve Corrective Action Request",
            "Unapprove Corrective Action Request",
            "Annotate Corrective Action Request"
         );
      
      return ($labels[$activityType]);
   }
   
   public static function getIcon($activityType)
   {
      $icon = null;
      
      switch ($activityType)
      {
         case ActivityType::LOG_IN:
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
         case ActivityType::ADD_CORRECTIVE_ACTION:
         {
            $icon = "add";
            break;
         }
         
         case ActivityType::EDIT_QUOTE:
         case ActivityType::REVISE_QUOTE:
         case ActivityType::EDIT_CORRECTIVE_ACTION:
         {
            $icon = "edit";
            break;
         }
         
         case ActivityType::ESTIMATE_QUOTE:
         {
            $icon = "calculate";
            break;
         }
         
         case ActivityType::APPROVE_QUOTE:
         case ActivityType::APPROVE_CORRECTIVE_ACTION:
            
         {
            $icon = "thumb_up_alt";
            break;
         }
         
         case ActivityType::UNAPPROVE_QUOTE:
         case ActivityType::UNAPPROVE_CORRECTIVE_ACTION:
         {
            $icon = "thumb_down_alt";
            break;
         }
         
         case ActivityType::SEND_QUOTE:
         {
            $icon = "mail";
            break;
         }
         
         case ActivityType::ACCEPT_QUOTE:
         {
            $icon = "done";
            break;
         }
         
         case ActivityType::REJECT_QUOTE:
         {
            $icon = "cancel";
            break;
         }
         
         case ActivityType::ANNOTATE_QUOTE:
         case ActivityType::ANNOTATE_CORRECTIVE_ACTION:
         {
            $icon = "chat";
            break;
         }
         
         case ActivityType::ADD_QUOTE_ATTACHMENT:
         case ActivityType::ADD_CORRECTIVE_ACTION_ATTACHMENT:
         {
            $icon = "attachment";
            break;
         }
         
         case ActivityType::DELETE_QUOTE_ATTACHMENT:
         case ActivityType::DELETE_CORRECTIVE_ACTION_ATTACHMENT:
         {
            $icon = "link_off";
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