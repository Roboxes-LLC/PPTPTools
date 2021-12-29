<?php

 require_once 'database.php';
 
 abstract class MaterialType
 {
    const FIRST = 0;
    const UNKNOWN = MaterialType::FIRST;
    const ALUMINUM = 1;
    const BRASS = 2;
    const STEEL = 3;
    const LAST = 4;
    const COUNT = MaterialType::LAST - MaterialType::FIRST;
    
    public static $VALUES = array(MaterialType::ALUMINUM, MaterialType::BRASS, MaterialType::STEEL);
    
    public static function getLabel($materialType)
    {
       $labels = array("---", "Aluminum", "Brass", "Steel");
       
       return ($labels[$materialType]);
    }
 }
 
 class MaterialInfo
 {
    const UNKNOWN_MATERIAL_ID = 0;
    
    public $materialId;
    public $materialType;
    public $partNumber;
    public $description;
    public $length;
    
    public function __construct()
    {
       $this->materialId = MaterialInfo::UNKNOWN_MATERIAL_ID;
       $this->materialType = MaterialType::UNKNOWN;
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
          foreach (MaterialType::$VALUES as $materialType)
          {
             $result = $database->getMaterials($materialType);
             
             $includeGroup = ($result && ($database->countResults($result) > 0));
             $groupLabel = MaterialType::getLabel($materialType);

             if ($includeGroup)
             {
                $html .= "<optgroup label=\"$groupLabel\">";
             }
          
             while ($result && ($row = $result->fetch_assoc()))
             {
                $materialInfo = new MaterialInfo();
                
                $materialInfo->initialize($row);
                
                $selected = ($materialInfo->materialId == $selectedMaterialId) ? "selected" : "";
                
                $html .= "<option value=\"$materialInfo->materialId\" $selected>$materialInfo->partNumber</option>";
                
             }
             
             if ($includeGroup)
             {
                $html .= "</optgroup>";
             }
          }
       }
       
       return ($html);
    }
    
    public static function getJavascriptLengthArray()
    {
       $html = "{";
    
       $database = PPTPDatabase::getInstance();
       
       if ($database && ($database->isConnected()))
       {
          $result = $database->getMaterials();
          
          $commaRequired = false;
          
          while ($result && ($row = $result->fetch_assoc()))
          {
             $materialInfo = new MaterialInfo();
             
             $materialInfo->initialize($row);
           
             $html .= ($commaRequired ? ", " : "");
             $html .= "$materialInfo->materialId:$materialInfo->length";
             
             $commaRequired = true;
          }
       }
       
       $html .= "}";

       return ($html);
    }
    
    private function initialize($row)
    {
       $this->materialId = intval($row['materialId']);
       $this->materialType = intval($row['materialType']);
       $this->partNumber = $row['partNumber'];
       $this->description = $row['description'];
       $this->length = $row['length'];
    }
 }
 
 /*
 $options = MaterialInfo::getOptions(MaterialInfo::UNKNOWN_MATERIAL_ID);
 echo "Materials <select>$options</select>";
 */
 