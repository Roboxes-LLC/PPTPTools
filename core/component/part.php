<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/core/component/customer.php';

class Part
{
   const UNKNOWN_PART_NUMBER = null;
   
   const UNKNOWN_CUSTOMER_NUMBER = null;
   
   public $partNumber;
   public $customerId;
   public $customerNumber;
   
   public function __construct()
   {
      $this->partNumber = Part::UNKNOWN_PART_NUMBER;
      $this->partNumber = Customer::UNKNOWN_CUSTOMER_ID;
      $this->customerNumber = Part::UNKNOWN_CUSTOMER_NUMBER;
   }
   
   // **************************************************************************
   // Component interface
   
   public function initialize($row)
   {
      $this->partNumber = $row['partNumber'];
      $this->customerId = intval($row['customerId']);
      $this->customerNumber = $row['customerNumber'];
   }
   
   public static function load($partNumber)
   {
      $part = null;
      
      $result = PPTPDatabase::getInstance()->getPart($partNumber);
      
      if ($result && ($row = $result->fetch_assoc()))
      {
         $part = new Part();
         
         $part->initialize($row);
      }
      
      return ($part);
   }
   
   public static function save($part)
   {
      $success = false;
      
      if (!PPTPDatabase::getInstance()->partExists($part->partNumber))
      {
         $success = PPTPDatabase::getInstance()->newPart($part);
      }
      else
      {
         $success = PPTPDatabase::getInstance()->updatePart($part);
      }
      
      return ($success);
   }
   
   public static function delete($partNumber)
   {
      return(PPTPDatabase::getInstance()->deletePart($partNumber));
   }
   
   // **************************************************************************
   
   public static function partExists($partNumber)
   {
      return (PPTPDatabase::getInstance()->partExists($partNumber));
   }
   
   public static function getCustomerName($partNumber)
   {
      $customerName = "";
      
      $part = Part::load($partNumber);
      if ($part)
      {
         $customerName = Customer::getCustomerName($part->customerId);
      }
      
      return ($customerName);
   }
   
   public static function getOptions($selectedPartNumber)
   {
      $html = "<option style=\"display:none\">";
      
      $result = PPTPDatabase::getInstance()->getParts();
      
      $part = new Part();
      
      foreach ($result as $row)
      {
         $part->initialize($row);
         
         $selected = ($part->partNumber == $selectedPartNumber) ? "selected" : "";
            
         $html .= "<option value=\"$part->partNumber\" $selected>$part->partNumber</option>";
      }
      
      return ($html);
   }
}

?>