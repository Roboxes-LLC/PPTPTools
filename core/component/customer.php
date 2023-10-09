<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/address.php';
require_once ROOT.'/core/common/pptpdatabase.php';
require_once ROOT.'/core/component/contact.php';

class Customer
{
   const UNKNOWN_CUSTOMER_ID = 0;
   
   public $customerId;
   public $customerName;
   public $address;
   public $primaryContactId;
   
   public function __construct()
   {
      $this->customerId = Customer::UNKNOWN_CUSTOMER_ID;
      $this->customerName = null;
      $this->address = new Address();
      $this->primaryContactId = Contact::UNKNOWN_CONTACT_ID;
   }
   
   // **************************************************************************
   // Component interface
   
   public static function load($customerId)
   {
      $customer = null;
      
      $result = PPTPDatabaseAlt::getInstance()->getCustomer($customerId);
      
      if ($result && ($row = $result[0]))
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
         $success = PPTPDatabaseAlt::getInstance()->addCustomer($customer);
         
         $customer->customerId = intval(PPTPDatabaseAlt::getInstance()->lastInsertId());
      }
      else
      {
         $success = PPTPDatabaseAlt::getInstance()->updateCustomer($customer);
      }
      
      return ($success);
   }
   
   public static function delete($customerId)
   {
      return (PPTPDatabaseAlt::getInstance()->deleteCustomer($customerId));
   }
   
   public function initialize($row)
   {
      $this->customerId = intval($row['customerId']);
      $this->customerName =  $row['customerName'];
      $this->address->initialize($row);
      $this->primaryContactId = intval($row['primaryContactId']);
   }
   
   // **************************************************************************
      
   public static function getOptions($selectedCustomerId = null)
   {
      $html = "<option style=\"display:none\">";
      
      $result = PPTPDatabaseAlt::getInstance()->getCustomers();
      
      $customer = new Customer();
      
      foreach ($result as $row)
      {
         $customer->initialize($row);
         
         $label = htmlspecialchars($customer->customerName);
         $value = $customer->customerId;
         $selected = ($customer->customerId == $selectedCustomerId) ? "selected" : "";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
      
      return ($html);
   }
   
   public static function getLink($customerId)
   {
      $html = "";
      
      $customer = Customer::load($customerId);
      if ($customer)
      {
         $label = $customer->customerName;
         
         $html = "<a href=\"/customer/customer.php?customerId=$customerId\">$label</a>";
      }
      
      return ($html);
   }
}