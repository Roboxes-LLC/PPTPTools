<?php

if (!defined('ROOT')) require_once '../../root.php';

class Session
{
   // Session variables.
   const IS_AUTHENTICATED = "authenticated";
   const AUTHENTICATED_USER_ID = "authenticatedUserId";
   const AUTHENTICATED_PERMISSIONS = "permissions";
   const MENU = "menu";
   
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