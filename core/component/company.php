<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/address.php';
require_once ROOT.'/core/common/pptpDatabase.php';

class Company
{
   const UNKNOWN_COMPANY_ID = 0;
   
   const PPTP_ID = 1;
   
   public $companyId;
   public $companyName;
   public $address;
   public $phone;
   public $fax;
   public $website;
   public $iso;
   
   public function __construct()
   {
      $this->companyId = Company::UNKNOWN_COMPANY_ID;
      $this->companyName = null;
      $this->address = new Address();
      $this->phone = null;
      $this->fax = null;
      $this->website = null;
      $this->iso = null;
   }
   
   // **************************************************************************
   // Component interface
   
   public static function load($companyId)
   {
      $company = null;
      
      $result = PPTPDatabaseAlt::getInstance()->getCompany($companyId);
      
      if ($result && ($row = $result[0]))
      {
         $company = new Company();
         
         $company->initialize($row);
      }
      
      return ($company);
   }
   
   public static function save($company)
   {
      echo "Company::save(): Unsupported operation"; 
      
      /*
      $success = false;
      
      if ($company->companyId == Company::UNKNOWN_COMPANY_ID)
      {
         $success = PPTPDatabaseAlt::getInstance()->addCompany($company);
         
         $company->companyId = intval(PPTPDatabaseAlt::getInstance()->lastInsertId());
      }
      else
      {
         $success = PPTPDatabaseAlt::getInstance()->updateCompany($company);
      }
      
      return ($success);
      */
   }
   
   public static function delete($companyId)
   {
      echo "Company::delete(): Unsupported operation"; 
      
      //return (PPTPDatabaseAlt::getInstance()->deleteCompany($companyId));
   }
   
   public function initialize($row)
   {
      $this->companyId = intval($row['companyId']);
      $this->companyName =  $row['companyName'];
      $this->address->initialize($row);
      $this->phone = $row['phone'];
      $this->fax = $row['fax'];
      $this->website = $row['website'];
      $this->iso = $row['iso'];
   }
}