<?php

require_once '../common/database.php';

abstract class MaintenanceType
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const REPAIR = MaintenanceType::FIRST;
   const PREVENTATIVE = 2;
   const CLEANING = 3;
   const TOOL_ROOM = 4;
   const LAST = 5;
   const COUNT = MaintenanceType::LAST - MaintenanceType::FIRST;
   
   public static $values = array(MaintenanceType::REPAIR, MaintenanceType::PREVENTATIVE, MaintenanceType::CLEANING, MaintenanceType::TOOL_ROOM);
   
   public static function getLabel($maintenanceCategory)
   {
      $labels = array("---", "Repair", "Preventative", "Cleaning", "Tool Room");
      
      return ($labels[$maintenanceCategory]);
   }
   
   public static function getOptions($selectedMaintenanceType)
   {
      $html = "<option style=\"display:none\">";
      
      foreach (MaintenanceType::$values as $maintenanceType)
      {
         $label = MaintenanceType::getLabel($maintenanceType);
         $selected = ($maintenanceType == $selectedMaintenanceType) ? "selected" : "";
         
         $html .= "<option value=\"$maintenanceType\" $selected>$label</option>";
      }
      
      return ($html);
   }
}

class MaintenanceCategory
{
   const UNKNOWN_CATEGORY_ID = 0;
   
   public $maintenanceCategoryId;
   public $maintenanceType;
   public $label;
   
   public function __construct()
   {
      $this->maintenanceCategoryId = MaintenanceCategory::UNKNOWN_CATEGORY_ID;
      $this->maintenanceType = MaintenanceType::UNKNOWN;
      $this->label = "";
   }
   
   public static function load($maintenanceCategoryId)
   {
      $maintenanceCategory = null;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getMaintenanceCategory($maintenanceCategoryId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $maintenanceCategory = new MaintenanceCategory();
            
            $maintenanceCategory->maintenanceCategoryId = intval($row['maintenanceCategoryId']);
            $maintenanceCategory->maintenanceType = intval($row['maintenanceType']);
            $maintenanceCategory->label = $row['label'];
         }
      }
      
      return ($maintenanceCategory);
   }
   
   public static function getOptions($maintenanceType, $selectedMaintenanceCategoryId)
   {
      $html = "<option style=\"display:none\">";
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getMaintenanceCategories($maintenanceType);
         
         while ($result && ($row = $result->fetch_assoc()))
         {
            $maintenanceCategoryId = intval($row["maintenanceCategoryId"]);
            $label = $row["label"];
            $selected = ($maintenanceCategoryId == $selectedMaintenanceCategoryId) ? "selected" : "";
            
            $html .= "<option value=\"$maintenanceCategoryId\" $selected>$label</option>";
         }
      }
      
      return ($html);
   }
}

/*
if (isset($_GET["maintenanceCategoryId"]))
{
   $maintenanceCategoryId = $_GET["maintenanceCategoryId"];
 
   $maintenanceCategory = MaintenanceCategory::load($maintenanceCategoryId);
 
   if ($maintenanceCategory)
   {
      echo "maintenanceCategoryId: " . $maintenanceCategory->maintenanceCategoryId .                            "<br/>";
      echo "maintenanceType: " .       MaintenanceType::getLabel($maintenanceCategory->maintenanceCategoryId) . "<br/>";
      echo "label: " .                 $maintenanceCategory->label .                                            "<br/>";
   }
   else
   {
      echo "No maintenance category found.";
   }
}
*/

?>