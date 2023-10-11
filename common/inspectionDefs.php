<?php

require_once 'database.php';
require_once 'jobInfo.php';
require_once 'time.php';
require_once 'userInfo.php';

abstract class InspectionStatus
{
   const FIRST = 0;
   const UNKNOWN = InspectionStatus::FIRST;
   const PASS = 1;
   const WARNING = 2;
   const FAIL = 3;
   const NON_APPLICABLE = 4;
   const LAST = 5;
   const COUNT = InspectionStatus::LAST - InspectionStatus::FIRST;
   
   public static function getLabel($inspectionStatus)
   {
      $labels = array("---", "PASS", "WARNING", "FAIL", "N/A");
      
      return ($labels[$inspectionStatus]);
   }
   
   public static function getClass($inspectionStatus)
   {
      $classes = array("", "pass", "warning", "fail", "n/a");
      
      return ($classes[$inspectionStatus]);
   }
}

abstract class InspectionType
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const OASIS = InspectionType::FIRST;
   const LINE = 2;
   const QCP = 3;
   const IN_PROCESS = 4;
   const GENERIC = 5;
   const FIRST_PART = 6;
   const FINAL = 7;
   const LAST = 8;
   const COUNT = InspectionType::LAST - InspectionType::FIRST;
   
   public static $VALUES = array(
      InspectionType::OASIS,
      InspectionType::FIRST_PART,
      InspectionType::LINE,
      InspectionType::QCP,
      InspectionType::IN_PROCESS,
      InspectionType::FINAL,
      InspectionType::GENERIC);
   
   public static function getLabel($inspectionType)
   {
      $labels = array("", "Oasis Inspection", "Line Inspection", "QCP Inspection", "In Process", "Generic", "First Part", "Final");
      
      return ($labels[$inspectionType]);
   }
   
   public static function getDefaultOptionalProperties($inspectionType)
   {
      $optionalProperties = array(0b0000, 0b1110, 0b1110, 0b1110, 0b1110, 0b0000, 0b1110, 0b1110);
         
      return ($optionalProperties[$inspectionType]);
   }
   
   public static function isTimeBased($inspectionType)
   {
      return ($inspectionType == InspectionType::QCP);
   }
}

function getInspectionTypeOptions($selectedInspectionType, $includeAllOption = false, $excludeTypes = [])
{
   $html = "<option style=\"display:none\">";
   
   if ($includeAllOption)
   {
      $value = InspectionType::UNKNOWN;
      $label = "All";
      $selected = ($value == $selectedInspectionType) ? "selected" : "";
   
      $html .= "<option value=\"$value\" $selected>$label</option>";
   }

   foreach (InspectionType::$VALUES as $inspectionType)
   {
      if (!in_array($inspectionType, $excludeTypes))
      {
         $label = InspectionType::getLabel($inspectionType);
         $value = $inspectionType;
         $selected = ($inspectionType == $selectedInspectionType) ? "selected" : "";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
   }
   
   return ($html);
}

abstract class InspectionDataType
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const INTEGER = InspectionDataType::FIRST;
   const DECIMAL = 2;
   const STRING = 3;
   const LAST = 4;
   const COUNT = InspectionDataType::LAST - InspectionDataType::FIRST;
   
   public static function getLabel($dataType)
   {
      $labels = array("---", "Integer", "Decimal", "String");
      
      return ($labels[$dataType]);
   }
}

abstract class InspectionDataUnits
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const INCHES = InspectionDataUnits::FIRST;
   const MILLIMETERS = 2;
   const DEGREES = 3;
   const LAST = 4;
   const COUNT = InspectionDataUnits::LAST - InspectionDataUnits::FIRST;
   
   public static function getLabel($dataType)
   {
      $labels = array("---", "Inches", "Millimeters", "Degrees");
      
      return ($labels[$dataType]);
   }
   
   public static function getAbbreviatedLabel($dataType)
   {
      $labels = array("", "in", "mm", "deg");
      
      return ($labels[$dataType]);
   }
}

abstract class OptionalInspectionProperties
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const JOB_NUMBER = OptionalInspectionProperties::FIRST;
   const WC_NUMBER = 2;
   const OPERATOR = 3;
   const MFG_DATE = 4;
   const LAST = 5;
   const COUNT = OptionalInspectionProperties::LAST - OptionalInspectionProperties::FIRST;
   
   public static function getLabel($optionalProperty)
   {
      $labels = array("", "Job Number", "WC Number", "Operator", "Mfg Date");
      
      return ($labels[$optionalProperty]);
   }
}

?>