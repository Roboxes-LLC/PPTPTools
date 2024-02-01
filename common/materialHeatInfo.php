<?php

require_once 'materialInfo.php';
require_once 'materialVendor.php';

class MaterialHeatInfo
{
   const UNKNOWN_INTERNAL_HEAT_NUMBER = 0;
   
   public $vendorHeatNumber;
   public $internalHeatNumber;
   public $vendorId;
   public $materialInfo;
   
   public function __construct()
   {
      $this->vendorHeatNumber = null;
      $this->internalHeatNumber = MaterialHeatInfo::UNKNOWN_INTERNAL_HEAT_NUMBER;
      $this->vendorId = MaterialVendor::UNKNOWN_MATERIAL_VENDOR_ID;
      $this->materialInfo = new MaterialInfo();
   }
   
   public static function load($heatNumber, $useInternalHeatNumber = false)
   {
      $materialHeatInfo = null;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && ($database->isConnected()))
      {
         $result = $database->getMaterialHeat($heatNumber, $useInternalHeatNumber);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $materialHeatInfo = new MaterialHeatInfo();
            
            $materialHeatInfo->initialize($row);
         }
      }
      
      return ($materialHeatInfo);
   }
   
   public static function getNextInternalHeatNumber()
   {
      $internalHeatNumber = MaterialHeatInfo::UNKNOWN_INTERNAL_HEAT_NUMBER;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && ($database->isConnected()))
      {
         $internalHeatNumber = $database->getNextInternalHeatNumber();
      }
      
      return ($internalHeatNumber);
   }   
   
   public function initialize($row)
   {
      $this->vendorHeatNumber = $row['vendorHeatNumber'];
      $this->internalHeatNumber = intval($row['internalHeatNumber']);
      $this->vendorId = intval($row['vendorId']);
      $this->materialInfo->initialize($row);
   }
}

/*
if (isset($_GET["heatNumber"]))
{
  $heatNumber = $_GET["heatNumber"];
 
   $materialHeatInfo = MaterialHeatInfo::load($heatNumber);
   
   if ($materialHeatInfo)
   {
      var_dump($materialHeatInfo);
   }
   else
   {
      echo "No vendor heat found.";
   }
}
else
{
   echo "No heat number specified.";
}
*/
