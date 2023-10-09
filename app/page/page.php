<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/authentication.php';

class Page
{
   public function __construct()
   {
      $this->result = new stdClass();
   }
   
   protected function getRequest($params)
   {
      return (isset($params["request"]) ? $params["request"] : null);
   }
   
   protected function authenticate($permissionIds)
   {
      Authentication::authenticate();
      
      $authenticated = Authentication::isAuthenticated();
   
      if ($authenticated)
      {
         foreach ($permissionIds as $permissionId)
         {
            $authenticated &= Authentication::checkPermissions($permissionId);
         }
      }

      if (!$authenticated)
      {
         $this->result->success = false;
         $this->result->error = "Authentication error";
      }
         
      return ($authenticated);
   }
   
   protected function requireParams($params, $requiredParams)
   {
      $missingParams = array();

      foreach ($requiredParams as $paramName)
      {
         if (!isset($params[$paramName]))
         {
            $missingParams[] = $paramName;
         }
      }
      
      $success = (count($missingParams) == 0);
      
      if (!$success)
      {
         $this->result->success = false;
         $this->result->error = "Missing parameters [" . implode(', ', $missingParams) . "]";
      }
      
      return ($success);
   }
   
   protected function error($errorDescription)
   {
      $this->result->success = false;
      $this->result->error = $errorDescription;
   }
   
   protected $result;
}

?>