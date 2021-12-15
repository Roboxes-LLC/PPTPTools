<?php

require_once 'database.php';
 
class MaterialVendor
{
   const UNKNOWN_MATERIAL_VENDOR_ID = 0;
    
   public static function getMaterialVendors()
   {
      $vendors = [];
       
      $database = PPTPDatabase::getInstance();
       
      if ($database && $database->isConnected())
      {
         $result = $database->getMaterialVendors();
          
         while ($result && ($row = $result->fetch_assoc()))
         {
            $materials[intval($row["vendorId"])] = $row["name"];
         }
      }
      
      return ($materials);
   }
   
   public static function getMaterialVendor($vendorId)
   {
      $vendor = "";
      
      $vendors = MaterialVendor::getMaterialVendors();
      
      if (isset($vendors[$vendorId]))
      {
         $vendor = $vendors[$vendorId];
      }
      
      return ($vendor);
   }
 
   public static function getOptions($selectedVendorId)
   {
      $html = "<option style=\"display:none\">";
       
      $vendors = MaterialVendor::getMaterialVendors();
       
      foreach($vendors as $vendorId => $name)
      {
         $selected = ($vendorId == $selectedVendorId) ? "selected" : "";
          
         $html .= "<option value=\"$vendorId\" $selected>$name</option>";
      }
       
      return ($html);
   }
}

/* 
$options = MaterialVendor::getOptions(MaterialVendor::UNKNOWN_MATERIAL_VENDOR_ID);
echo "Material vendors <select>$options</select>";
*/
