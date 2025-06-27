<?php 

abstract class MaterialType
{
   const FIRST = 0;
   const UNKNOWN = MaterialType::FIRST;
   const ALUMINUM = 1;
   const BRASS = 2;
   const STEEL = 3;
   const STAINLESS_STEEL = 4;
   const BRONZE = 5;
   const COPPER_NICKEL = 6;
   const LAST = 6;
   const COUNT = MaterialType::LAST - MaterialType::FIRST;
   
   public static $VALUES = array(MaterialType::ALUMINUM, MaterialType::BRASS, MaterialType::STEEL, MaterialType::STAINLESS_STEEL, MaterialType::BRONZE, MaterialType::COPPER_NICKEL);
   
   public static function getLabel($materialType)
   {
      $labels = array("---", "Aluminum", "Brass", "Steel", "Stainless Steel", "Bronze", "Copper Nickel");
      
      return ($labels[$materialType]);
   }
   
   public static function getAbbreviation($materialType)
   {
      $labels = array("---", "ALUM", "BRASS", "STEEL", "STEEL SS", "BRONZE", "COP/NICK");
      
      return ($labels[$materialType]);
   }
   
   public static function getOptions($selectedMaterialType)
   {
      $html = "<option style=\"display:none\">";
      
      foreach (MaterialType::$VALUES as $materialType)
      {
         $value = $materialType;
         $label = MaterialType::getLabel($materialType);
         $selected = ($materialType == $selectedMaterialType) ? "selected" : "";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
      
      return ($html);
   }
}

abstract class MaterialShape
{
   const FIRST = 0;
   const UNKNOWN = MaterialShape::FIRST;
   const ROUND = 1;
   const HEXAGONAL = 2;
   const SQUARE = 3;
   const LAST = 4;
   const COUNT = MaterialShape::LAST - MaterialShape::FIRST;
   
   public static $VALUES = array(MaterialShape::HEXAGONAL, MaterialShape::ROUND, MaterialShape::SQUARE);
   
   public static function getLabel($materialShape)
   {
      $labels = array("", "Round", "Hexagonal", "Square");
      
      return ($labels[$materialShape]);
   }
   
   public static function getAbbreviation($materialShape)
   {
      $abbreviations = array("", "RD", "HEX", "SQ");
      
      return ($abbreviations[$materialShape]);
   }
   
   public static function getOptions($selectedMaterialShape)
   {
      $html = "<option style=\"display:none\">";
      
      foreach (MaterialShape::$VALUES as $materialShape)
      {
         $value = $materialShape;
         $label = MaterialShape::getLabel($materialShape);
         $selected = ($materialShape == $selectedMaterialShape) ? "selected" : "";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
      
      return ($html);
   }
}

abstract class MaterialEntryStatus
{
   const FIRST = 0;
   const UNKNOWN = MaterialEntryStatus::FIRST;
   const RECEIVED = 1;
   const ISSUED = 2;
   const ACKNOWLEDGED = 3;
   const LAST = 4;
   const COUNT = MaterialEntryStatus::LAST - MaterialEntryStatus::FIRST;
   
   public static $VALUES = array(MaterialEntryStatus::RECEIVED, MaterialEntryStatus::ISSUED, MaterialEntryStatus::ACKNOWLEDGED);
   
   public static function getLabel($materialEntryStatus)
   {
      $labels = array("---", "Received", "Issued", "Acknowledged");
      
      return ($labels[$materialEntryStatus]);
   }
   
   public static function getOptions($selectedStatus, $includeAll = false)
   {
      $html = "<option style=\"display:none\">";
      
      if ($includeAll)
      {
         $all = MaterialEntryStatus::UNKNOWN;
         $label = "All";
         $selected = ($selectedStatus == $all) ? "selected" : "";
         $html .= "<option value=\"$all\" $selected>$label</option>";
      }
      
      foreach (MaterialEntryStatus::$VALUES as $materialEntryStatus)
      {
         $selected = ($materialEntryStatus == $selectedStatus) ? "selected" : "";
         $label = MaterialEntryStatus::getLabel($materialEntryStatus);
         
         $html .= "<option value=\"$materialEntryStatus\" $selected>$label</option>";
      }
      
      return ($html);
   }
}

abstract class MaterialLocation
{
   const FIRST = 0;
   const UNKNOWN = MaterialLocation::FIRST;
   const ON_SITE = 1;
   const OTHER_VENDOR = 2;
   const BURNS = 3;
   const HOLDEN = 4;
   const IND_SOLUTIONS = 5;   
   const KEY = 6;
   const GREENDALE = 7;
   const LAST = 8;
   const COUNT = MaterialLocation::LAST - MaterialLocation::FIRST;
   
   public static $VALUES = array(MaterialLocation::ON_SITE, MaterialLocation::BURNS, MaterialLocation::GREENDALE, MaterialLocation::HOLDEN, MaterialLocation::IND_SOLUTIONS, MaterialLocation::KEY, MaterialLocation::OTHER_VENDOR);
   
   public static function getLabel($materialLocation)
   {
      $labels = array("", "On Site", "Other Vendor", "Burns", "Holden", "Ind. Solutions", "Key", "Greendale");
      
      return ($labels[$materialLocation]);
   }
   
   public static function getOptions($selectedLocation)
   {
      $html = "<option style=\"display:none\">";
      
      foreach (MaterialLocation::$VALUES as $materialLocation)
      {
         $selected = ($materialLocation == $selectedLocation) ? "selected" : "";
         $label = MaterialLocation::getLabel($materialLocation);
         
         $html .= "<option value=\"$materialLocation\" $selected>$label</option>";
      }
      
      return ($html);
   }
}

abstract class MaterialPartNumber
{
   const UNKNOWN_MATERIAL_PART_NUMBER = null;
   
   public static function getOptions($selectedPartNumber)
   {
      $html = "<option style=\"display:none\">";
      
      $result = PPTPDatabase::getInstance()->getMaterialPartNumbers();
      
      while ($result && ($row = $result->fetch_assoc()))
      {
         $value = $row["materialPartNumber"];
         $label = $value;
         $selected = ($selectedPartNumber == $value) ? "selected" : "";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
      
      return ($html);
   }
}

abstract class MaterialLength
{
   const UNKNOWN_MATERIAL_LENGTH = 0;
   
   const MIN_MATERIAL_LENGTH = 12;
   
   const MAX_MATERIAL_LENGTH = 15;
   
   public static function getOptions($selectedMaterialLength)
   {
      $html = "<option style=\"display:none\">";
      
      for ($length = MaterialLength::MIN_MATERIAL_LENGTH; $length <= MaterialLength::MAX_MATERIAL_LENGTH; $length++)
      {
         $value = $length;
         $label = $length . " feet";
         $selected = ($selectedMaterialLength == $value) ? "selected" : "";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
      
      return ($html);
   }
}

abstract class MaterialStamp
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const NO_STAMP = MaterialStamp::FIRST;
   const A9_SS = 2;
   const LAST = 3;
   const COUNT = MaterialStamp::LAST - MaterialStamp::FIRST;
   
   public static $values = array(MaterialStamp::NO_STAMP, MaterialStamp::A9_SS);
   
   public static function getLabel($stamp)
   {
      $labels = array("", "No Stamp", "A9/SS");
      
      return ($labels[$stamp]);
   }
   
   public static function getOptions($selectedMaterialStamp)
   {
      $html = "<option style=\"display:none\">";
      
      foreach (MaterialStamp::$values as $stamp)
      {
         $value = $stamp;
         $label = MaterialStamp::getLabel($stamp);
         $selected = ($selectedMaterialStamp == $value) ? "selected" : "";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
      
      return ($html);
   }
}

?>