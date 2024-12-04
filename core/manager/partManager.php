<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/pptpDatabase.php';
require_once ROOT.'/core/component/part.php';

class PartManager
{
   public static function getParts()
   {
      $parts = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getParts();
      
      foreach ($result as $row)
      {
         $part = new Part();
         $part->initialize($row);
         
         $parts[] = $part;
      }
      
      return ($parts);
   }
   
   public static function getPartsForCustomer($customerId)
   {
      $parts = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getPartsForCustomer($customerId);
      
      foreach ($result as $row)
      {
         $part = new Part();
         $part->initialize($row);
         
         $parts[] = $part;
      }
      
      return ($parts);
   }
   
   public static function getCustomerPartNumber($pptpPartNumber)
   {
      $part = Part::load($pptpPartNumber, Part::USE_PPTP_NUMBER);
      
      return ($part ? $part->customerNumber : null);
   }
   
   public static function getPPTPPartNumber($customerPartNumber)
   {
      $part = Part::load($customerPartNumber, Part::USE_CUSTOMER_NUMBER);
      
      return ($part ? $part->pptpNumber : null);
   }
   
   public static function getCustomerPartNumberOptions($customerId, $selectedCustomerPartNumber)
   {
      $html = "<option style=\"display:none\">";
      
      $parts = PartManager::getPartsForCustomer($customerId);
      
      foreach ($parts as $part)
      {
         $value = $part->customerNumber;
         $label = $part->customerNumber;
         $selected = ($part->customerNumber == $selectedCustomerPartNumber) ? "selected" : "";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
   
      return ($html);
   }
}