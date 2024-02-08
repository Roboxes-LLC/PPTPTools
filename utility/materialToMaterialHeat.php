<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/common/materialEntry.php';

function getPartNumber($string)
{
   $partNumber = null;
   
   $pos = strpos($string, " ");
   if ($pos > 0)
   {
      $partNumber = substr($string, 0, $pos);
   }

   return ($partNumber);
}

function getShape($string)
{
   $shape = MaterialShape::UNKNOWN;
   
   if (strpos($string, "HEX"))
   {
      $shape = MaterialShape::HEXAGONAL;
   }
   else if (strpos($string, "RD"))
   {
      $shape = MaterialShape::ROUND;
   }
   
   return ($shape);
}

$result = PPTPDatabase::getInstance()->query("SELECT * FROM materialheat");

while ($result && ($row = $result->fetch_assoc())) 
{
   $materialId = intval($row["materialId"]);

   $materialHeatInfo = MaterialHeatInfo::load($row["vendorHeatNumber"]);
   
   if ($materialHeatInfo)
   {
      $result2 = PPTPDatabase::getInstance()->query("SELECT * from material WHERE materialId = $materialId");
      if ($result2 && ($row2 = $result2->fetch_assoc()))
      {
         
         $materialHeatInfo->materialInfo->type = intval($row2["materialType"]);
         $materialHeatInfo->materialInfo->size = floatval($row2["size"]);
         $materialHeatInfo->materialInfo->length = intval($row2["length"]);
         $materialHeatInfo->materialInfo->partNumber = getPartNumber($row2["partNumber"]);
         $materialHeatInfo->materialInfo->shape = getShape($row2["partNumber"]);
         
         echo "***************************************************************<br>";
         echo "Updating material heat [{$materialHeatInfo->vendorHeatNumber}]<br><br>";
         
         var_dump($materialHeatInfo);
         
         echo "<br><br>";
         
         PPTPDatabase::getInstance()->updateMaterialHeat($materialHeatInfo);
      }         
   }
}