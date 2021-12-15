<?php

 require_once 'database.php';
 
 class Material
 {
    const UNKNOWN_MATERIAL_ID = 0;
    
    public static function getMaterials()
    {
       $materials = [];
       
       $database = PPTPDatabase::getInstance();
       
       if ($database && $database->isConnected())
       {
          $result = $database->getMaterials();
          
          while ($result && ($row = $result->fetch_assoc()))
          {
             $materials[intval($row["materialId"])] = $row["descriptor"];
          }
       }
       
       return ($materials);
    }
    
    public static function getMaterial($materialId)
    {
       $material = "";
       
       $materials = Material::getMaterials();
       
       if (isset($materials[$materialId]))
       {
          $material = $materials[$materialId];
       }
       
       return ($material);
    }
 
    public static function getOptions($selectedMaterialId)
    {
       $html = "<option style=\"display:none\">";
       
       $materials = Material::getMaterials();
       
       foreach($materials as $materialId => $descriptor)
       {
          $selected = ($materialId == $selectedMaterialId) ? "selected" : "";
          
          $html .= "<option value=\"$materialId\" $selected>$descriptor</option>";
       }
       
       return ($html);
    }
 }
 
 /*
 $options = Material::getOptions(Material::UNKNOWN_MATERIAL_ID);
 echo "Materials <select>$options</select>";
 */