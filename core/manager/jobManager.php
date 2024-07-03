<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/pptpdatabase.php';
require_once ROOT.'/common/jobInfo.php';

class JobManager
{
   public static function getCustomerPartNumber($pptpPartNumber)
   {
      return (PPTPDatabaseAlt::getInstance()->getCustomerPartNumber($pptpPartNumber));
   }
   
   public static function saveCustomerPartNumber($pptpPartNumber, $customerPartNumber)
   {
      return (PPTPDatabaseAlt::getInstance()->saveCustomerPartNumber($pptpPartNumber, $customerPartNumber));
   }
}
   