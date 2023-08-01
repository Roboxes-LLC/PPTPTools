<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/pptpdatabase.php';
require_once ROOT.'/core/component/customer.php';

class CustomerManager
{
   public static function getCustomers()
   {
      $customers = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getCustomers();
      
      foreach ($result as $row)
      {
         $customer = new Customer();
         $customer->initialize($row);
         
         $customers[] = $customer;
      }
      
      return ($customers);
   }
}