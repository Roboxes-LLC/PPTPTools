<?php

 require_once 'database.php';
 
 class MaterialInfo
 {
    const UNKNOWN_MATERIAL_ID = 0;
    
    public $materialId;
    public $partNumber;
    public $description;
    public $length;
    
    public function __construct()
    {
       $this->materialId = MaterialInfo::UNKNOWN_MATERIAL_ID;
       $this->partNumber = "";
       $this->description = "";
       $this->length = "";
    }
    
    public static function load($materialId)
    {
       $materialInfo = null;
       
       $database = PPTPDatabase::getInstance();
       
       if ($database && ($database->isConnected()))
       {
          $result = $database->getMaterial($materialId);
          
          if ($result && ($row = $result->fetch_assoc()))
          {
             $materialInfo = new MaterialInfo();
             
             $materialInfo->initialize($row);
          }
       }
       
       return ($materialInfo);
    }

    public static function getOptions($selectedMaterialId)
    {
       $html = "<option style=\"display:none\">";
       
       $database = PPTPDatabase::getInstance();
       
       if ($database && ($database->isConnected()))
       {
          $result = $database->getMaterials();
       
          while ($result && ($row = $result->fetch_assoc()))
          {
             $materialInfo = new MaterialInfo();
             
             $materialInfo->initialize($row);
             
             $selected = ($materialInfo->materialId == $selectedMaterialId) ? "selected" : "";
             
             $html .= "<option value=\"$materialInfo->materialId\" $selected>$materialInfo->partNumber</option>";
             
          }
       }
       
       return ($html);
    }
    
    private function initialize($row)
    {
       $this->materialId = intval($row['materialId']);
       $this->partNumber = $row['partNumber'];
       $this->description = $row['description'];
       $this->length = $row['length'];
    }
 }
 
 /*
 $options = MaterialInfo::getOptions(MaterialInfo::UNKNOWN_MATERIAL_ID);
 echo "Materials <select>$options</select>";
 */
 