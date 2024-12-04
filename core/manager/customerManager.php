<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/pptpDatabase.php';
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
   
   public static function getContacts()
   {
      $contacts = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getContacts();
      
      foreach ($result as $row)
      {
         $contact = new Contact();
         $contact->initialize($row);
         
         $contacts[] = $contact;
      }
      
      return ($contacts);
   }
   
   public static function getContactsForCustomer($customerId)
   {
      $contacts = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getContactsForCustomer($customerId);
      
      foreach ($result as $row)
      {
         $contact = new Contact();
         $contact->initialize($row);
         
         $contacts[] = $contact;
      }
      
      return ($contacts);
   }
   
   public static function getCustomerOptions($selectedCustomerId)
   {
      $html = "<option style=\"display:none\">";
      
      $customers = CustomerManager::getCustomers();
      
      foreach ($customers as $customer)
      {
         $label = $customer->customerName;
         $value = $customer->customerId;
         $selected = ($customer->customerId == $selectedCustomerId) ? "selected" : "";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
      
      return ($html);
   }
}