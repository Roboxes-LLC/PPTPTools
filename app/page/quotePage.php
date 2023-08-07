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
          case "new_quote":
          {
             if (Page::requireParams($params, ["customerId", "contactId", "customerPartNumber", "pptpPartNumber", "quantity"]))
             {
                $quote = new Quote();

                QuotePage::getQuoteParams($quote, $params);
                
                if (Quote::save($quote))
                {
                   $this->result->quoteId = $quote->quoteId;
                   $this->result->quote = $quote;
                   $this->result->success = true;

                   $quote->request(Time::now(), Authentication::getAuthenticatedUser()->employeeNumber, null);
                   
                   ActivityLog::logComponentActivity(
                      Authentication::getAuthenticatedUser()->employeeNumber,
                      ActivityType::ADD_QUOTE,
                      $quote->quoteId,
                      $quote->getQuoteNumber());
                }
                else
                {
                   $this->error("Database error");
                }
             }
             break;
          }
          
          case "delete_quote":
          {
             if (Page::requireParams($params, ["quoteId"]))
             {
                $quoteId = $params->getInt("quoteId");
                
                $quote = Quote::load($quoteId);
                
                if ($quote)
                {
                   Quote::delete($quoteId);
                   
                   $this->result->quoteId = $quote->quoteId;
                   $this->result->success = true;
                   
                   ActivityLog::logComponentActivity(
                      Authentication::getAuthenticatedUser()->employeeNumber,
                      ActivityType::DELETE_QUOTE,
                      $quote->quoteId,
                      $quote->getQuoteNumber());
                }
                else
                {
                   $this->error("Invalid quote id [$quoteId]");
                }
             }
             break;
          }
          
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
                   $dateTime = Time::dateTimeObject(null);
                   
                   $endDate = Time::endOfDay($dateTime->format(Time::STANDARD_FORMAT));
                   $startDate = Time::startofDay($dateTime->modify("-1 month")->format(Time::STANDARD_FORMAT));
                   $activeQuotes = false;
                   
                   if (isset($params["startDate"]))
                   {
                      $startDate = Time::startOfDay($params["startDate"]);
                   }
                   
                   if (isset($params["endDate"]))
                   {
                      $endDate = Time::endOfDay($params["endDate"]);
                   }
                   
                   if (isset($params["activeQuotes"]))
                   {
                      $activeOrders = $params->getBool("activeQuotes");
                   }                   
                   
                   $this->result->success = true;
                   
                   if ($activeQuotes)
                   {
                      $this->result->quotes = QuoteManager::getQuotesByStatus(QuoteStatus::$activeStatuses);
                   }
                   else
                   {
                      $this->result->quotes = QuoteManager::getQuotes($startDate, $endDate);
                   }
                   
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
    
    private static function getQuoteParams($quote, $params)
    {
       $quote->customerId = $params->getInt("customerId");
       $quote->contactId = $params->getInt("contactId");
       $quote->customerPartNumber = $params->get("customerPartNumber");
       $quote->pptpPartNumber = $params->get("pptpPartNumber");
       $quote->quantity = $params->getInt("quantity");
    }
    
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