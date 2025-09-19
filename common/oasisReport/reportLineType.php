<?php

class ReportLineType
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const PART_INSPECTION_START = ReportLineType::FIRST;
   const PART_INSPECTION_DATA = 2;
   const PART_INSPECTION_END = 3;
   const USER_FIELD_LABEL = 4;
   const USER_FIELD_VALUE = 5;
   const ENCODING = 6;
   const LAST = ReportLineType::ENCODING;
   
   private static $LABELS = array(
      "UNKNOWN",
      "START",
      "DATA",
      "END",
      "UserField",
      "UserData",
      "ENCODING"
   );
   
   private static $TOKEN_COUNT = array(
      0,
      2,
      8,
      3,
      2,
      2,
      2
   );
   
   public static $VALUES = array(
      ReportLineType::PART_INSPECTION_START,
      ReportLineType::PART_INSPECTION_DATA,
      ReportLineType::PART_INSPECTION_END,
      ReportLineType::USER_FIELD_LABEL,
      ReportLineType::USER_FIELD_VALUE,
      ReportLineType::ENCODING
   );
   
   public static function valueOf($token)
   {
      $ordinal = ReportLineType::UNKNOWN;
      
      foreach (ReportLineType::$VALUES as $reportLineType)
      {
         if (strpos($token, ReportLineType::getLabel($reportLineType)) !== false)
         {
            $ordinal = $reportLineType;
            break;
         }
      }
      
      return ($ordinal);
   }
   
   public static function getLabel($reportLineType)
   {
      $label = ReportLineType::$LABELS[ReportLineType::UNKNOWN];
      
      if ($reportLineType <= ReportLineType::LAST)
      {
         $label = ReportLineType::$LABELS[$reportLineType];
      }
      
      return ($label);
   }
   
   public static function getTokenCount($reportLineType)
   {
      $tokenCount = ReportLineType::$TOKEN_COUNT[ReportLineType::UNKNOWN];
      
      if ($reportLineType <= ReportLineType::LAST)
      {
         $tokenCount = ReportLineType::$TOKEN_COUNT[$reportLineType];
      }
      
      return ($tokenCount);
   }
}

/*
$label = ReportLineType::getLabel(ReportLineType::PART_INSPECTION_DATA);
echo "getLabel(PART_INSPECTION_DATA) = $label<br>";
 
$reportLineType = ReportLineType::valueOf("UserField1");
echo "valueOf(\"UserField1\") = $reportLineType<br>";
 
foreach (ReportLineType::$VALUES as $reportLineType)
{
   $label = ReportLineType::getLabel($reportLineType);
   $tokenCount = ReportLineType::getTokenCount($reportLineType);
   echo "[$reportLineType] : $label, $tokenCount<br>";
}
*/

?>