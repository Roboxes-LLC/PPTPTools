<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/app/page/page.php';

class UserPage extends Page
{
   public function handleRequest($params)
   {
      switch ($this->getRequest($params))
      {
         case "confirm_pin":
         {
            if (Page::requireParams($params, ["confirmPin"]))
            {
               $this->result->success = true;
               $this->result->confirmed = false;
               
               $pin = $params->getInt("confirmPin");
               
               $user = Authentication::getAuthenticatedUser();
               
               if ($user->employeeNumber == $pin)
               {
                  $this->result->confirmed = true;
               }
               // Special case for longer employee numbers.
               else if ((strlen($user->employeeNumber) > 3) &&  // MAX_SEQUENCE_LENGTH
                        (substr($user->employeeNumber, 0, 3) == $pin))
               {
                  $this->result->confirmed = true;
               }
            }
            break;
         }

         default:
         {
            $this->error("Invalid request");
            break;
         }
      }
      
      echo json_encode($this->result);
   }
}

?>