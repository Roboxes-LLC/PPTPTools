<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/pptpdatabase.php';

class Contact
{
   const UNKNOWN_CONTACT_ID = 0;
   
   public $contactId;
   public $firstName;
   public $lastName;
   public $customerId;
   public $phone;
   public $email;
   
   public function __construct()
   {
      $this->contactId = Contact::UNKNOWN_CONTACT_ID;
      $this->firstName = null;
      $this->lastName = null;
      $this->customerId = Customer::UNKNOWN_CUSTOMER_ID;
      $this->phone = null;
      $this->email = null;
   }
   
   // **************************************************************************
   // Component interface
   
   public static function load($contactId)
   {
      $contact = null;
      
      $result = PPTPDatabaseAlt::getInstance()->getContact($contactId);
      
      if ($result && ($row = $result[0]))
      {
         $contact = new Contact();
         
         $contact->initialize($row);
      }
      
      return ($contact);
   }
   
   public static function save($contact)
   {
      $success = false;
      
      if ($contact->contactId == Contact::UNKNOWN_CONTACT_ID)
      {
         $success = PPTPDatabaseAlt::getInstance()->addContact($contact);
         
         $contact->contactId = intval(PPTPDatabaseAlt::getInstance()->lastInsertId());
      }
      else
      {
         $success = PPTPDatabaseAlt::getInstance()->updateContact($contact);
      }
      
      return ($success);
   }
   
   public static function delete($contactId)
   {
      return (PPTPDatabaseAlt::getInstance()->deleteContact($contactId));
   }
   
   public function initialize($row)
   {
      $this->contactId = intval($row['contactId']);
      $this->firstName =  $row['firstName'];
      $this->lastName =  $row['lastName'];
      $this->customerId = intval($row['customerId']);
      $this->phone = $row['phone'];
      $this->email = $row['email'];
   }   
   
   // **************************************************************************
         
   public function getFullName()
   {
      return ($this->firstName . " " . $this->lastName);
   }
   
   public static function getOptions($selectedContactId = null, $customerId = Customer::UNKNOWN_CUSTOMER_ID)
   {
      $html = "<option style=\"display:none\">";
      
      $result = PPTPDatabaseAlt::getInstance()->getContactsForCustomer($customerId);
      
      $contact = new Contact();
      
      foreach ($result as $row)
      {
         $contact->initialize($row);
         
         $label = htmlspecialchars($contact->getFullName());
         $value = $contact->contactId;
         $selected = ($contact->contactId == $selectedContactId) ? "selected" : "";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
      
      return ($html);
   }
   
   public static function getLink($contactId)
   {
      $html = "";
      
      $contact = Contact::load($contactId);
      if ($contact)
      {
         $label = $contact->getFullName();
         
         $html = "<a href=\"contact.php?contactId=$contactId\">$label</a>";
      }
      
      return ($html);
   }
}

?>