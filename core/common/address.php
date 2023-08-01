<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/usaStates.php';

class Address
{
   const UNKNOWN_ZIPCODE = 0;
   
   public $addressLine1;
   public $addressLine2;
   public $city;
   public $state;
   public $zipcode;
   
   public function __construct()
   {
      $this->addressLine1 = null;
      $this->addressLine2 = null;
      $this->city = null;
      $this->state = UsaStates::UNKNOWN_STATE_ID;
      $this->zipcode = 0;
   }
   
   public function initialize($row, $prefix = "")
   {
      $this->addressLine1 = $row["{$prefix}addressLine1"];
      $this->addressLine2 = $row["{$prefix}addressLine2"];
      $this->city = $row["{$prefix}city"];
      $this->state = intval($row["{$prefix}state"]);
      $this->stateAbbreviation = UsaStates::getStateAbbreviation($this->state);
      $this->zipcode = $row["{$prefix}zipcode"];
   }
   
   public function getCityState()
   {
      return(
         $this->city .
         ((!empty($this->city) && !empty($this->state)) ? ", " : "") . 
         UsaStates::getStateAbbreviation($this->state));
   }
}

?>