<?php

if (!defined('ROOT')) require_once '../../../root.php';
require_once ROOT.'/core/component/quote.php';

class QuoteTest
{
   private const AUTHOR_ID = 1975;
   
   private const QUOTE_STATUS = QuoteStatus::REQUESTED;
   private const CUSTOMER_ID = 1;
   private const CONTACT_ID = 1;
   private const CUSTOMER_PART_NUMBER = "Dunlop-7234B";
   private const PPTP_PART_NUMBER = "M1234";
   private const QUANTITY = 20000;
   private const UNIT_PRICE = 0.25;
   private const COST_PER_HOUR = 500.50;
   private const ADDITIONAL_CHARGE = 250.25;
   private const CHARGE_CODE = ChargeCode::TOOLING;
   private const TOTAL_COST = 5000.75;
   private const LEAD_TIME = 3;

   private const OTHER_QUOTE_STATUS = QuoteStatus::ESTIMATED;
   private const OTHER_CUSTOMER_ID = 2;
   private const OTHER_CONTACT_ID = 2;
   private const OTHER_CUSTOMER_PART_NUMBER = "Hammersmill-66765";
   private const OTHER_PPTP_PART_NUMBER = "M7890";
   private const OTHER_QUANTITY = 40000;
   private const OTHER_UNIT_PRICE = 0.50;
   private const OTHER_COST_PER_HOUR = 1500.00;
   private const OTHER_ADDITIONAL_CHARGE = 500.30;
   private const OTHER_CHARGE_CODE = ChargeCode::UNKNOWN;
   private const OTHER_TOTAL_COST = 10000.20;
   private const OTHER_LEAD_TIME = 6;
   
   public static function run()
   {
      echo "Running QuoteTest ...<br>";
      
      $test = new QuoteTest();
      
      $test->testSave_Add();
      
      if (QuoteTest::$newQuoteId != Quote::UNKNOWN_QUOTE_ID)
      {
         $test->testLoad();
         
         $test->testSave_Update();
         
         $test->testGetLink();         
         
         $test->testDelete();
      }
   }
   
   public function testLoad()
   {
      echo "Quote::load()<br>";
      
      $quote = Quote::load(QuoteTest::$newQuoteId);
      
      var_dump($quote);
   }
   
   public function testSave_Add()
   {
      echo "Quote::save(newQuote)<br>";
      
      $quote = new Quote();
      
      $quote->quoteStatus = QuoteTest::QUOTE_STATUS;
      $quote->customerId = QuoteTest::CUSTOMER_ID;
      $quote->contactId = QuoteTest::CONTACT_ID;
      $quote->customerPartNumber = QuoteTest::CUSTOMER_PART_NUMBER;
      $quote->pptpPartNumber = QuoteTest::PPTP_PART_NUMBER;
      $quote->quantity = QuoteTest::QUANTITY;
      
      $estimate = new Estimate();
      $estimate->unitPrice = QuoteTest::UNIT_PRICE;
      $estimate->costPerHour = QuoteTest::COST_PER_HOUR;
      $estimate->additionalCharge = QuoteTest::ADDITIONAL_CHARGE;
      $estimate->chargeCode = QuoteTest::CHARGE_CODE;
      $estimate->totalCost = QuoteTest::TOTAL_COST;
      $estimate->leadTime = QuoteTest::LEAD_TIME;
      $quote->setEstimate($estimate, 0);

      $estimate = clone $estimate;
      $estimate->additionalCharge += 50;      
      $quote->setEstimate($estimate, 1);
      
      $estimate = clone $estimate;
      $estimate->totalCost += 50;
      $quote->setEstimate($estimate, 2);
      
      Quote::save($quote);
      
      QuoteTest::$newQuoteId = $quote->quoteId;
      
      $quote = Quote::load(QuoteTest::$newQuoteId);
      
      $quote->request(Time::now(), QuoteTest::AUTHOR_ID, "Requested");
      
      var_dump($quote);
   }
   
   public function testSave_Update()
   {
      echo "Quote::save(existingQuote)<br>";
      
      $quote = Quote::load(QuoteTest::$newQuoteId);
      
      $quote->quoteStatus = QuoteTest::OTHER_QUOTE_STATUS;
      $quote->customerId = QuoteTest::OTHER_CUSTOMER_ID;
      $quote->contactId = QuoteTest::OTHER_CONTACT_ID;
      $quote->customerPartNumber = QuoteTest::OTHER_CUSTOMER_PART_NUMBER;
      $quote->pptpPartNumber = QuoteTest::OTHER_PPTP_PART_NUMBER;
      $quote->quantity = QuoteTest::OTHER_QUANTITY;
      
      $estimate = clone $quote->getEstimate(0);
      $estimate->unitPrice = QuoteTest::OTHER_UNIT_PRICE;
      $estimate->costPerHour = QuoteTest::OTHER_COST_PER_HOUR;
      $estimate->additionalCharge = QuoteTest::OTHER_ADDITIONAL_CHARGE;
      $estimate->chargeCode = QuoteTest::OTHER_CHARGE_CODE;
      $estimate->totalCost = QuoteTest::OTHER_TOTAL_COST;
      $estimate->leadTime = QuoteTest::OTHER_LEAD_TIME;
      $quote->setEstimate($estimate, 0);
      
      Quote::save($quote);
      
      var_dump($quote);
   }
   
   public function testDelete()
   {
      echo "Quote::delete()<br>";
      
      Quote::delete(QuoteTest::$newQuoteId);
   }
      
   public function testGetLink()
   {
      echo Quote::getLink(QuoteTest::$newQuoteId) . "<br>";
   }
   
   private static $newQuoteId = Quote::UNKNOWN_QUOTE_ID;
}

QuoteTest::run();

?>