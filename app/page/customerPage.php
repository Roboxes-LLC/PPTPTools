<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/app/page/page.php';
require_once ROOT.'/core/manager/activityLog.php';
require_once ROOT.'/core/manager/customerManager.php';

class CustomerPage extends Page
{
    public function handleRequest($params)
    {
       switch ($this->getRequest($params))
       {
          case "save_contact":
          {
             if (Page::requireParams($params, ["contactId", "firstName", "lastName", "customerId", "email", "phone"]))
             {
                $contactId = $params->getInt("contactId");
                $newContact = ($contactId == Contact::UNKNOWN_CONTACT_ID);
                
                $contact = null;
                if ($newContact)
                {
                   $contact = new Contact();
                }
                else
                {
                   $contact = Contact::load($contactId);
                   
                   if (!$contact)
                   {
                      $contact = null;
                      $this->error("Invalid contact id [$contactId]");
                   }
                }
                
                if ($contact)
                {
                   CustomerPage::getContactParams($contact, $params);
                   
                   if (Contact::save($contact))
                   {
                      $this->result->contactId = $contact->contactId;
                      $this->result->contact = $contact;
                      $this->result->success = true;
                      
                      ActivityLog::logComponentActivity(
                         Authentication::getAuthenticatedUser()->employeeNumber,
                         ($newContact ? ActivityType::ADD_CONTACT : ActivityType::EDIT_CONTACT),
                         $contact->contactId,
                         $contact->getFullName());
                   }
                   else
                   {
                      $this->error("Database error");
                   }
                }
             }
             break;
          }
          
          case "delete_contact":
          {
             if (Page::requireParams($params, ["contactId"]))
             {
                $contactId = $params->getInt("contactId");
                
                $contact = Contact::load($contactId);
                
                if ($contact)
                {
                   Contact::delete($contactId);
                   
                   $this->result->contactId = $contactId;
                   $this->result->success = true;
                   
                   ActivityLog::logComponentActivity(
                      Authentication::getAuthenticatedUser()->employeeNumber,
                      ActivityType::DELETE_CONTACT,
                      $contact->contactId,
                      $contact->getFullName());
                }
                else
                {
                   $this->error("Invalid contact id [$contactId]");
                }                
             }
             break;
          }                   
          
          case "save_customer":
          {
             if (Page::requireParams($params, ["customerName", "addressLine1", "addressLine2", "city", "state", "zipcode", "primaryContactId"]))
             {
                $customerId = $params->getInt("customerId");
                $newCustomer = ($customerId == Customer::UNKNOWN_CUSTOMER_ID);
                
                $customer = null;
                if ($newCustomer)
                {
                   $customer = new Customer();
                }
                else
                {
                   $customer = Customer::load($customerId);
                   
                   if (!$customer)
                   {
                      $contact = null;
                      $this->error("Invalid customer id [$customerId]");
                   }
                }
                
                if ($customer)
                {
                   CustomerPage::getCustomerParams($customer, $params);
                   
                   if (Customer::save($customer))
                   {
                      $this->result->customerId = $customer->customerId;
                      $this->result->customer = $customer;
                      $this->result->success = true;
                      
                      ActivityLog::logComponentActivity(
                         Authentication::getAuthenticatedUser()->employeeNumber,
                         ($newCustomer ? ActivityType::ADD_CUSTOMER : ActivityType::EDIT_CUSTOMER),
                         $customer->customerId,
                         $customer->customerName);
                   }
                   else
                   {
                      $this->error("Database error");
                   }
                }
             }
             break;
          }
             
          case "delete_customer":
          {
             if (Page::requireParams($params, ["customerId"]))
             {
                $customerId = $params->getInt("customerId");
                
                $customer = Customer::load($customerId);
                
                if ($customer)
                {
                   Customer::delete($customerId);
                   
                   $this->result->customerId = $customer->customerId;
                   $this->result->success = true;
                   
                   ActivityLog::logComponentActivity(
                         Authentication::getAuthenticatedUser()->employeeNumber,
                         ActivityType::DELETE_CUSTOMER,
                         $customer->customerId,
                         $customer->customerName);
                }
                else
                {
                   $this->error("Invalid customer id [$customerId]");
                }
             }
             break;
          }
             
          case "fetch_contact":
          {
             if ($this->authenticate([Permission::VIEW_CUSTOMER]))
             {
                // Fetch single component.
                if (isset($params["contactId"]))
                {
                   $contactId = $params->getInt("contactId");
                   
                   $contact = Contact::load($contactId);
                   
                   if ($contact)
                   {
                      $this->result->success = true;
                      $this->result->contact = $contact;
                      
                      CustomerPage::augmentContact($contact);
                   }
                   else
                   {
                      $this->error("Invalid contact id [$contactId]");
                   }
                }
                // Fetch all components.
                else
                {
                   $this->result->success = true;
                   $this->result->contacts = CustomerManager::getContacts();
                   
                   // Augment data.
                   foreach ($this->result->contacts as $contact)
                   {
                      CustomerPage::augmentContact($contact);
                   }
                }
             }
             break;
             
          }
          
          case "fetch":
          default:
          {
             if ($this->authenticate([Permission::VIEW_CUSTOMER]))
             {
                // Fetch single component.
                if (isset($params["customerId"]))
                {
                   $customerId = $params->getInt("customerId");
                   
                   $customer = Customer::load($customerId);
                   
                   if ($customer)
                   {
                      $this->result->customer = $customer;
                      $this->result->success = true;
                   }
                   else
                   {
                      $this->error("Invalid customer id [$customerId]");
                   }
                }
                // Fetch all components.
                else 
                {
                   $this->result->success = true;
                   $this->result->customers = CustomerManager::getCustomers();
                   
                   // Augment data.
                   foreach ($this->result->customers as $customer)
                   {
                      CustomerPage::augmentCustomer($customer);
                   }
                }
             }
             break;
          }
       }
       
       echo json_encode($this->result);
    }
    
    private function getContactParams($contact, $params)
    {
       $contact->firstName = $params->get("firstName");
       $contact->lastName = $params->get("lastName");
       $contact->customerId = $params->getInt("customerId");
       $contact->email = $params->get("email");
       $contact->phone = $params->get("phone");
    }
    
    private function getCustomerParams($customer, $params)
    {
       $customer->customerName = $params->get("customerName");
       $customer->address->addressLine1 = $params->get("addressLine1");
       $customer->address->addressLine2 = $params->get("addressLine2");
       $customer->address->city = $params->get("city");
       $customer->address->state = $params->getInt("state");
       $customer->address->zipcode = $params->get("zipcode");
       $customer->primaryContactId = $params->getInt("primaryContactId");       
    }
    
    private static function augmentCustomer(&$customer)
    {
       // stateLabel
       if ($customer->address->state != UsaStates::UNKNOWN_STATE_ID)
       {
          $customer->address->stateLabel = UsaStates::getStateAbbreviation($customer->address->state);
       }
       
       // primaryContact
       if ($customer->primaryContactId != Contact::UNKNOWN_CONTACT_ID)
       {
          $customer->primaryContact = Contact::load($customer->primaryContactId);
          
          if ($customer->primaryContact)
          {
             $customer->primaryContact->fullName = $customer->primaryContact->getFullName();
          }
       }
    }
    
    private static function augmentContact(&$contact)
    {
       // customerName
       $customer = Customer::load($contact->customerId);
       if ($customer)
       {
          $contact->customerName = $customer->customerName;
       }
    }
 }
 
 ?>