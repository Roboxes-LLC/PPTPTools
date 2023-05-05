<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/database.php';

class Customer
{
   const UNKNOWN_CUSTOMER_ID = 0;
   
   public $customerId;
   public $name;
   
   public function __construct()
   {
      $this->customerId = Customer::UNKNOWN_CUSTOMER_ID;
      $this->name = null;
   }
   
   // **************************************************************************
   // Component interface
   
   public function initialize($row)
   {
      $this->customerId = intval($row['customerId']);
      $this->name = $row['name'];
   }
   
   public static function load($customerId)
   {
      $customer = null;
      
      $result = PPTPDatabase::getInstance()->getCustomer($customerId);
      
      if ($result && ($row = $result->fetch_assoc()))
      {
         $customer = new Customer();
         
         $customer->initialize($row);
      }
      
      return ($customer);
   }
   
   public static function save($customer)
   {
      $success = false;
      
      if ($customer->customerId == Customer::UNKNOWN_CUSTOMER_ID)
      {
         $success = PPTPDatabase::getInstance()->newCustomer($customer);
         
         $customer->customerId = intval(PPTPDatabase::getInstance()->lastInsertId());
      }
      else
      {
         $success = PPTPDatabase::getInstance()->updateCustomer($customer);
      }
      
      return ($success);
   }
   
   public static function delete($customerId)
   {
      return(PPTPDatabase::getInstance()->deleteCustomer($customerId));
   }
   
   // **************************************************************************
   
   public static function getCustomerName($customerId)
   {
      $customerName = "";
      
      $customer = Customer::load($customerId);
      if ($customer)
      {
         $customerName = $customer->name;
      }
      
      return ($customerName);
   }
   
   public static function getOptions($selectedCustomerId)
   {
      $html = "<option style=\"display:none\">";
      
      $result = PPTPDatabase::getInstance()->getCustomers();
      
      $customer = new Customer();
      
      foreach ($result as $row)
      {
         $customer->initialize($row);
         
         $selected = ($customer->customerId == $selectedCustomerId) ? "selected" : "";
            
         $html .= "<option value=\"$customer->customerId\" $selected>$customer->name</option>";
      }
      
      return ($html);
   }
}

?>