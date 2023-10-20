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
   
   public static $values = [InspectionStatus::PASS, InspectionStatus::WARNING, InspectionStatus::FAIL, InspectionStatus::NON_APPLICABLE];
   
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
   
   public static function getJavascript($enumName)
   {
      // Note: Keep synced with enum.
      $varNames = array("UNKNOWN", "PASS", "WARNING", "FAIL", "NON_APPLICABLE");
      
      $html = "$enumName = {";
      
      $html .= "{$varNames[InspectionStatus::UNKNOWN]}: " . InspectionStatus::UNKNOWN . ", ";
      
      $index = 0;
      
      foreach (InspectionStatus::$values as $inspectionStatus)
      {
         $html .= "{$varNames[$inspectionStatus]}: $inspectionStatus";
         $html .= ($index < (count(InspectionStatus::$values) - 1) ? ", " : "");
         
         $index++;
      }
      
      $html .= "};";
      
      return ($html);
   }
   
   public static function getJavascriptInspectionClasses($enumName)
   {
      $html = "$enumName = [";
      
      for ($inspectionStatus = InspectionStatus::FIRST; $inspectionStatus < InspectionStatus::LAST; $inspectionStatus++)
      {
         $class = InspectionStatus::getClass($inspectionStatus);
         
         $html .= "\"$class\"";
         $html .= ($inspectionStatus < (InspectionStatus::LAST - 1)) ? ", " : "";
      }
      
      $html .= "];";
      
      return ($html);
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
      $labels = array("", "Oasis Inspection", "Line Inspection", "QCP Inspection", "In Process", "Generic", "First Piece", "Final");
      
      return ($labels[$inspectionType]);
   }
   
   public static function getDefaultOptionalProperties($inspectionType)
   {
      // Bits
      // 0 = UNKNOWN;
      // 1 = JOB_NUMBER
      // 2 = WC_NUMBER
      // 3 = OPERATOR
      // 4 = MFG_DATE
      // 5 = INSPECTION_NUMBER
      // 6 = QUANTITY
      
      $optionalProperties = [
         0b0000000,  // UNKNOWN
         0b0001110,  // OASIS
         0b0011110,  // LINE
         0b0011110,  // QCP
         0b0111110,  // IN_PROCESS
         0b0000000,  // GENERIC (configurable)
         0b0011110,  // FIRST_PART
         0b1010010   // FINAL              
      ];
         
      return ($optionalProperties[$inspectionType]);
   }
   
   public static function isTimeBased($inspectionType)
   {
      return ($inspectionType == InspectionType::QCP);
   }
   
   public static function getJavascript($enumName)
   {
      // Note: Keep synced with enum.
      $varNames = array("UNKNOWN", "OASIS", "LINE", "QCP", "IN_PROCESS", "GENERIC", "FIRST_PART", "FINAL");
      
      $html = "$enumName = {";
      
      $html .= "{$varNames[InspectionType::UNKNOWN]}: " . InspectionType::UNKNOWN . ", ";
      
      $index = 0;
      
      foreach (InspectionType::$VALUES as $inspectionType)
      {
         $html .= "{$varNames[$inspectionType]}: $inspectionType";
         $html .= ($index < (count(InspectionType::$VALUES) - 1) ? ", " : "");
         
         $index++;
      }
      
      $html .= "};";
      
      return ($html);
   } 
}

// The number of required In Process inspections.
const REQUIRED_IN_PROCESS_INPECTIONS = 2;

function getInspectionNumberOptions($selectedInspectionNumber)
{
   $html = "<option style=\"display:none\">";
   
   for ($inspectionNumber = 1; $inspectionNumber <= REQUIRED_IN_PROCESS_INPECTIONS; $inspectionNumber++)
   {
      $value = $inspectionNumber;
      $label = $inspectionNumber;
      $selected = ($inspectionNumber == $selectedInspectionNumber) ? "selected" : "";
      
      $html .= "<option value=\"$value\" $selected>$label</option>";
   }
   
   return ($html);
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
   const INSPECTION_NUMBER = 5;
   const QUANTITY = 6;
   const LAST = 7;
   const COUNT = OptionalInspectionProperties::LAST - OptionalInspectionProperties::FIRST;
   
   // Optional inspection properties that are valid for Generic inspections.
   public static $genericOptionalInspectionProperties = [
      OptionalInspectionProperties::JOB_NUMBER,
      OptionalInspectionProperties::WC_NUMBER,
      OptionalInspectionProperties::OPERATOR,
      OptionalInspectionProperties::MFG_DATE
   ];
   
   public static function getLabel($optionalProperty)
   {
      $labels = array("", "Job Number", "WC Number", "Operator", "Mfg Date", "Inspection #", "Quantity");
      
      return ($labels[$optionalProperty]);
   }
}

abstract class SamplingPlan
{
   public static $minSamples = 3;
   
   public static $maxSamples = 29;
   
   // Table for determining the number of samples required in a Final Inspection
   // based on party quantity produced.
   // Note: Provided by J. Orbin in 10/11/23 email "sampling plan".
   private static $sampleThresholds = 
      // quantity threshold => samples
      array(
         25 => 3, 
         90 => 6,
         150 => 7,
         280 => 10,
         500 => 11,
         1200 => 15,
         3200 => 18,
         10000 => 22,
         35000 => 29
      );
      
   public static function getSampleCount($quantity)
   {
      $sampleCount = SamplingPlan::$maxSamples;
      
      foreach (SamplingPlan::$sampleThresholds as $threshold => $samples)
      {
         if ($quantity <= $threshold)
         {
            $sampleCount = $samples;
            break;
         }
      }
      
      // Can't have more samples than parts.
      $sampleCount = min([$sampleCount, $quantity]);
      
      return ($sampleCount);
   }
}



?>