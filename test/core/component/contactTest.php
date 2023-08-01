<?php

if (!defined('ROOT')) require_once '../../../root.php';
require_once ROOT.'/core/component/contact.php';

class ContactTest
{
   private const FIRST_NAME = "Todd";
   private const LAST_NAME = "Packer";
   private const CUSTOMER_ID = 1;
   private const PHONE = "724-543-5294";
   private const EMAIL = "tpacker@dundermifflin.com";
   
   private const OTHER_FIRST_NAME = "Ron";
   private const OTHER_LAST_NAME = "Howard";
   private const OTHER_CUSTOMER_ID = 2;
   private const OTHER_PHONE = "724-543-5295";
   private const OTHER_EMAIL = "rhoward@infinity.com";
   
   public static function run()
   {
      echo "Running ContactTest ...<br>";
      
      $test = new ContactTest();
      
      $test->testSave_Add();
      
      if (ContactTest::$newContactId != Contact::UNKNOWN_CONTACT_ID)
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
      echo "Contact::load()<br>";
      
      $contact = Contact::load(ContactTest::$newContactId);
      
      var_dump($contact);
   }
   
   public function testSave_Add()
   {
      echo "Contact::save(newContact)<br>";
      
      $contact = new Contact();
      
      $contact->firstName = ContactTest::FIRST_NAME;
      $contact->lastName = ContactTest::LAST_NAME;
      $contact->customerId = ContactTest::CUSTOMER_ID;
      $contact->phone = ContactTest::PHONE;
      $contact->email = ContactTest::EMAIL;
      
      Contact::save($contact);
      
      ContactTest::$newContactId = $contact->contactId;
      
      $contact = Contact::load(ContactTest::$newContactId);
      
      var_dump($contact);
   }
   
   public function testSave_Update()
   {
      echo "Contact::save(existingContact)<br>";
      
      $contact = Contact::load(ContactTest::$newContactId);
      
      $contact->firstName = ContactTest::OTHER_FIRST_NAME;
      $contact->lastName = ContactTest::OTHER_LAST_NAME;
      $contact->customerId = ContactTest::OTHER_CUSTOMER_ID;
      $contact->phone = ContactTest::OTHER_PHONE;
      $contact->email = ContactTest::OTHER_EMAIL;
      
      Contact::save($contact);
      
      var_dump($contact);
   }
   
   public function testDelete()
   {
      echo "Contact::delete()<br>";
      
      Contact::delete(ContactTest::$newContactId);
   }
   
   public function testGetOptions()
   {
      echo "ContactCategory::getOptions()<br>";
      
      echo "<select>" . Contact::getOptions(ContactTest::$newContactId) . "</select><br>";
   }
   
   public function testGetLink()
   {
      echo Contact::getLink(ContactTest::$newContactId) . "<br>";
   }
   
   private static $newContactId = Contact::UNKNOWN_CONTACT_ID;
}

ContactTest::run();

?>