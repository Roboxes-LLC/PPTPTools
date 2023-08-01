<?php

if (!defined('ROOT')) require_once '../../../root.php';
require_once ROOT.'/core/common/address.php';
require_once ROOT.'/core/component/customer.php';

class CustomerTest
{
   private const CUSTOMER_NAME = "Dunder Mifflin";
   private const ADDRESS_LINE_1 = "309 N. Maple Ave.";
   private const ADDRESS_LINE_2 = "Apt 1";
   private const CITY = "Greensburg";
   private const STATE = 1;
   private const ZIPCODE = "15601";
   private const PRIMARY_CONTACT_ID = 1;
   
   private const OTHER_CUSTOMER_NAME = "Frequentis";
   private const OTHER_ADDRESS_LINE_1 = "10000 Broadway St.";
   private const OTHER_ADDRESS_LINE_2 = "Suite 7";
   private const OTHER_CITY = "Irwin";
   private const OTHER_STATE = 2;
   private const OTHER_ZIPCODE = "15239";
   private const OTHER_PRIMARY_CONTACT_ID = 2;
   
   public static function run()
   {
      echo "Running CustomerTest ...<br>";
      
      $test = new CustomerTest();
      
      $test->testSave_Add();
      
      if (CustomerTest::$newCustomerId != Customer::UNKNOWN_CUSTOMER_ID)
      {
         $test->testLoad();
         
         $test->testSave_Update();
                  
         $test->testGetOptions();
         
         $test->testGetLink();         
         
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
      
      $customer->customerName = CustomerTest::CUSTOMER_NAME;
      $customer->address = new Address();
      $customer->address->addressLine1 = CustomerTest::ADDRESS_LINE_1;
      $customer->address->addressLine2 = CustomerTest::ADDRESS_LINE_2;
      $customer->address->city = CustomerTest::CITY;
      $customer->address->state = CustomerTest::STATE;
      $customer->address->zipcode = CustomerTest::ZIPCODE;
      $customer->primaryContactId = CustomerTest::PRIMARY_CONTACT_ID;
      
      Customer::save($customer);
      
      CustomerTest::$newCustomerId = $customer->customerId;
      
      $customer = Customer::load(CustomerTest::$newCustomerId);
      
      var_dump($customer);
   }
   
   public function testSave_Update()
   {
      echo "Customer::save(existingCustomer)<br>";
      
      $customer = Customer::load(CustomerTest::$newCustomerId);
      
      $customer->customerName = CustomerTest::OTHER_CUSTOMER_NAME;
      $customer->address->addressLine1 = CustomerTest::OTHER_ADDRESS_LINE_1;
      $customer->address->addressLine2 = CustomerTest::OTHER_ADDRESS_LINE_2;
      $customer->address->city = CustomerTest::OTHER_CITY;
      $customer->address->state = CustomerTest::OTHER_STATE;
      $customer->address->zipcode = CustomerTest::OTHER_ZIPCODE;
      $customer->primaryContactId = CustomerTest::OTHER_PRIMARY_CONTACT_ID;
      
      Customer::save($customer);
      
      var_dump($customer);
   }
   
   public function testDelete()
   {
      echo "Customer::delete()<br>";
      
      Customer::delete(CustomerTest::$newCustomerId);
   }
   
   public function testGetOptions()
   {
      echo "CustomerCategory::getOptions()<br>";
      
      echo "<select>" . Customer::getOptions(CustomerTest::$newCustomerId) . "</select><br>";
   }
   
   public function testGetLink()
   {
      echo Customer::getLink(CustomerTest::$newCustomerId) . "<br>";
   }
   
   private static $newCustomerId = Customer::UNKNOWN_CUSTOMER_ID;
}

CustomerTest::run();

?>