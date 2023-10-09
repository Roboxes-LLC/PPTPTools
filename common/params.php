<?php
class Params extends ArrayObject
{
   public static function parse()
   {
      $params = new Params(array());
      
      if (isset($_SESSION))
      {
         foreach ($_SESSION as $key => $value)
         {
            $params[$key] = filter_var($_SESSION[$key], FILTER_SANITIZE_SPECIAL_CHARS);
         }
      }
      
      if ($_SERVER["REQUEST_METHOD"] === "GET")
      {
         foreach ($_GET as $key => $value)
         {
            $params[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
         }
      }
      else if ($_SERVER["REQUEST_METHOD"] === "POST")
      {
         foreach ($_POST as $key => $value)
         {
            $params[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
         }
      }
      
      return $params;
   }
   
   public function keyExists($key)
   {
       return (isset($this[$key]));
   }
   
   public function get($key)
   {
      return (isset($this[$key]) ? $this[$key] : "");
   }
   
   public function getInt($key)
   {
      return (intval($this->get($key)));
   }
   
   public function getFloat($key)
   {
      return (floatval($this->get($key)));
   }
   
   public function getBool($key)
   {
      return (filter_var($this->get($key), FILTER_VALIDATE_BOOLEAN));
   }
}
