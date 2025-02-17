<?php

if (!defined('ROOT')) require_once '../../root.php';

class Session
{
   // Session variables.
   const IS_AUTHENTICATED = "authenticated";
   const AUTHENTICATED_USER_ID = "authenticatedUserId";
   const AUTHENTICATED_PERMISSIONS = "permissions";
   const MENU = "menu";
   const QUOTE_ACTIVE_QUOTES = "quote.activeQuotes";
   const QUOTE_START_DATE = "quote.startDate";
   const QUOTE_END_DATE = "quote.endDate";   
   const SCHEDULE_MFG_DATE = "schedule.mfgDate";   
   const NOTIFICATION_START_DATE = "notification.startDate";   
   const NOTIFICATION_END_DATE = "notification.endDate";
   const NOTIFICATION_SHOW_ALL_UNACKNOWLEDGED = "notification.showAllUnacknowledged";
   const SALES_ORDER_ACTIVE_ORDERS = "salesOrder.activeOrders";
   const SALES_ORDER_FILTER_DATE_TYPE = "salesOrder.filterDateType";
   const SALES_ORDER_START_DATE = "salesOrder.startDate";
   const SALES_ORDER_END_DATE = "salesOrder.endDate";
   const SHIPMENT_SHIPMENT_LOCATION = "shipment.shipmentLocation";
   const SHIPMENT_START_DATE = "shipment.startDate";
   const SHIPMENT_END_DATE = "shipment.endDate";
      
   public static function isset($key)
   {
      return (isset($_SESSION[$key]));
   }
   
   public static function getVar($key)
   {
      $value = null;
       
      if (isset($_SESSION[$key]))
      {
         $value = $_SESSION[$key];
      }
      
      return ($value);
   }
    
   public static function setVar($key, $value)
   {
      $_SESSION[$key] = $value;
   }
   
   public static function clearVar($key)
   {
      unset($_SESSION[$key]);
   }
}

?>