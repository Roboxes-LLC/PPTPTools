<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/app/page/page.php';
require_once ROOT.'/core/manager/quoteManager.php';

class QuotePage extends Page
{
    public function handleRequest($params)
    {
       switch ($this->getRequest($params))
       {
          /*
          case "save_quote":
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
             if ($this->authenticate([Permission::VIEW_QUOTE]))
             {
                // Fetch single component.
                if (isset($params["quoteId"]))
                {
                   $quoteId = $params->getInt("quoteId");
                   
                   $quote = Customer::load($quoteId);
                   
                   if ($quote)
                   {
                      $this->result->quote = $quote;
                      $this->result->success = true;
                   }
                   else
                   {
                      $this->error("Invalid quote id [$quoteId]");
                   }
                }
                // Fetch all components.
                else 
                {
                   $this->result->success = true;
                   $this->result->quotes = QuoteManager::getQuotes();  // TODO: Date and status clause
                   
                   // Augment data.
                   foreach ($this->result->quotes as $quote)
                   {
                      QuotePage::augmentQuote($quote);
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
    
    private static function augmentQuote(&$quote)
    {
       // quoteNumber
       $quote->quoteNumber = $quote->getQuoteNumber();
       
       // customerName
       $customer = Customer::load($quote->customerId);
       if ($customer)
       {
          $quote->customerName = $customer->customerName;
       }
       
       // contactName
       $contact = Contact::load($quote->contactId);
       if ($contact)
       {
          $quote->contactName = $contact->getFullName();
       }       
       
       // quoteStatusLabel
       $quote->quoteStatusLabel = QuoteStatus::getLabel($quote->quoteStatus);
   }
 }
 
 ?>