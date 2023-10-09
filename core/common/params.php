<?php

class Params extends ArrayObject
{
   public static function parse()
   {
      if (!Params::$params)
      {
         $params = new Params(array());
         
         if (isset($_SESSION))
         {
            foreach ($_SESSION as $key => $value)
            {
               $params[$key] = $value;
            }
         }
         
         if ($_SERVER["REQUEST_METHOD"] === "GET")
         {
            foreach ($_GET as $key => $value)
            {
               if (is_array($_GET[$key]))
               {
                  $params[$key] = $_GET[$key];
               }
               else
               {
                  $params[$key] = $value;
               }
            }
         }
         else if ($_SERVER["REQUEST_METHOD"] === "POST")
         {
            if ($_SERVER["CONTENT_TYPE"] == "application/json")
            {
               $json = file_get_contents('php://input');
   
               if ($data = json_decode($json))
               {
                  foreach ($data as $key => $value)
                  {
                     $params[$key] = $value;
                  }
               }
            }
            else
            {
               foreach ($_POST as $key => $value)
               {
                  $params[$key] = $value;
               }
            }
         }
         
         Params::$params = $params;
      }
      
      return (Params::$params);
   }
   
   public static function reset()
   {
      Params::$params = null;
   }
   
   public function keyExists($key)
   {
       return (isset($this[$key]));
   }
   
   public function get($key)
   {
      return (isset($this[$key]) ? $this[$key] : "");
   }
   
   public function getBool($key)
   {
      return (isset($this[$key]) && filter_var($this[$key], FILTER_VALIDATE_BOOLEAN));
   }
   
   public function getInt($key)
   {
      return (intval($this->get($key)));
   }
   
   public function getFloat($key)
   {
      return (floatval($this->get($key)));
   }
   
   private static $params = null;
}

?>