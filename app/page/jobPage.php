<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/app/page/page.php';
require_once ROOT.'/common/jobInfo.php';

class JobPage extends Page
{
   public function handleRequest($params)
   {
      if (Page::authenticate([Permission::VIEW_JOB]))
      {
         $request = $this->getRequest($params);
         
         switch ($request)
         {
            case "get_customer_number":
            {
               if (Page::requireParams($params, ["pptpPartNumber"]))
               {
                  $pptpPartNumber = $params->get("pptpPartNumber");
                  
                  $customerPartNumber = PPTPDatabaseAlt::getInstance()->getCustomerPartNumber($pptpPartNumber);
                  
                  if ($customerPartNumber !== null)
                  {
                     $this->result->success = true;
                     $this->result->pptpPartNumber = $pptpPartNumber;
                     $this->result->customerPartNumber = $customerPartNumber;
                  }
                  else
                  {
                     $this->error("Invalid part [$pptpPartNumber]");
                  }
               }
               break;
            }
            
            default:
            {
               $this->error("Unsupported command [$request]");
            }
         }
      }
      
      echo json_encode($this->result);
   }
}