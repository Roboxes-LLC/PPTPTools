<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/app/page/page.php';
require_once ROOT.'/core/manager/customerManager.php';

class CustomerPage extends Page
{
    public function handleRequest($params)
    {
       switch ($this->getRequest($params))
       {
          /*
          case "save_customer":
          {
             if (Page::requireParams($params, ["vendorId", "vendorName", "contactName", "addressLine1", "addressLine2", "city", "state", "zipcode", "phone", "email"]))
             {
                $customerId = $params->getInt("vendorId");
                $newCustomer = ($customerId == Customer::UNKNOWN_VENDOR_ID);
                
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
                      $customer = null;
                      $this->error("Invalid vendor id [$customerId]");
                   }
                }
                
                if ($customer)
                {
                   CustomerPage::getCustomerParams($customer, $params);
                   
                   if (Customer::save($customer))
                   {
                      $this->result->vendorId = $customer->vendorId;
                      $this->result->vendor = $customer;
                      $this->result->success = true;
                      
                      $this->updateCustomerSites($customer, $params);
                      
                      ActivityLog::logComponentActivity(
                         Authentication::getSite(),
                         Authentication::getAuthenticatedUser()->userId,
                         ($newCustomer ? ActivityType::ADD_VENDOR : ActivityType::EDIT_VENDOR),
                         $customer->vendorId,
                         $customer->vendorName);
                   }
                   else
                   {
                      $this->error("Database error");
                   }
                }
             }
             break;
          }
          
          case "delete_vendor":
          {
             if (Page::requireParams($params, ["vendorId"]))
             {
                $customerId = $params->getInt("vendorId");
                
                $customer = Customer::load($customerId);
                
                if ($customer)
                {
                   Customer::delete($customerId);
                   
                   $this->result->vendorId = $customerId;
                   $this->result->success = true;
                   
                   ActivityLog::logComponentActivity(
                      Authentication::getSite(),
                      Authentication::getAuthenticatedUser()->userId,
                      ActivityType::DELETE_VENDOR,
                      $customerId,
                      $customer->vendorName);
                }
                else
                {
                   $this->error("Invalid vendor id [$customerId]");
                }
             }
             break;
          }
          */
          
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
    
    /*
    private function getCustomerParams($customer, $params)
    {
       $customer->vendorName = $params->get("vendorName");
       $customer->contactName = $params->get("contactName");
       $customer->address->addressLine1 = $params->get("addressLine1");
       $customer->address->addressLine2 = $params->get("addressLine2");
       $customer->address->city = $params->get("city");
       $customer->address->state = $params->getInt("state");
       $customer->address->zipcode = $params->get("zipcode");
       $customer->phone = $params->get("phone");
       $customer->email = $params->get("email");
       $customer->supportAltPricing = $params->getBool("supportAltPricing");
    }
    */
    
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
 }
 
 ?>