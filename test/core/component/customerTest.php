<?php

if (!defined('ROOT')) require_once '../../../root.php';
require_once ROOT.'/core/component/customer.php';

class CustomerTest
{
   const CUSTOMER_NAME = "Trader Horn";
   
   const OTHER_CUSTOMER_NAME = "Jamesway";
   
   public static function run()
   {
      echo "Running CustomerTest ...<br>";
      
      $test = new CustomerTest();
      
      $test->testSave_Add();
      
      if (CustomerTest::$newCustomerId != Customer::UNKNOWN_CUSTOMER_ID)
      {
         $test->testLoad();
         
         $test->testSave_Update();
         
         $test->testDelete();
      }
   }
   
   public function testLoad()
   {
      echo "Customer::load()<br>";
      
      $customer = Customer::load(CustomerTest::$newCustomerId);
      
      var_dump($customer);
   }
   
   public function testSave_Add()
   {
      echo "Customer::save(newCustomer)<br>";
      
      $customer = new Customer();
      
      $customer->name = CustomerTest::CUSTOMER_NAME;
      
      if (Customer::save($customer))
      {
         CustomerTest::$newCustomerId = $customer->customerId;
         
         $customer = Customer::load(CustomerTest::$newCustomerId);
      }
      
      var_dump($customer);      
   }
   
   public function testSave_Update()
   {
      echo "Customer::save(existingCustomer)<br>";
      
      $customer = Customer::load(CustomerTest::$newCustomerId);
      
      $customer->name = CustomerTest::OTHER_CUSTOMER_NAME;
      
      Customer::save($customer);
      
      var_dump($customer);
   }
   
   public function testDelete()
   {
      echo "Customer::delete()<br>";
      
      Customer::delete(CustomerTest::$newCustomerId);
   }
   
   private static $newCustomerId = 0;
}

CustomerTest::run();

?>