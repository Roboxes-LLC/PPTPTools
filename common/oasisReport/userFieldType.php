<?php

class UserFieldType
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const PAN_TICKET = UserFieldType::FIRST;
   const EMPLOYEE_NUMBER = 2;
   const COMMENTS = 3;
   const PART_COUNT = 4;
   const SAMPLE_SIZE = 5;
   const MACHINE_NUMBER = 6;
   const DATE = 7;
   const PART_NUMBER = 8;
   const EFFICIENCY = 9;
   const LAST = UserFieldType::EFFICIENCY;
   
   private static $LABELS = array(
      "UNKNOWN",
      "Pan Ticket #",
      "Employee #",
      "Comments",
      "Part Count",
      "Sample Size",
      "Machine #",
      "Date",
      "Part #",
      "Efficiency"
   );
   
   public static $VALUES = array(
      UserFieldType::PAN_TICKET,
      UserFieldType::EMPLOYEE_NUMBER,
      UserFieldType::COMMENTS,
      UserFieldType::PART_COUNT,
      UserFieldType::SAMPLE_SIZE,
      UserFieldType::MACHINE_NUMBER,
      UserFieldType::DATE,
      UserFieldType::PART_NUMBER,
      UserFieldType::EFFICIENCY
   );

   public static function valueOf($token)
   {
      // Sample: UserField1
      
      $ordinal = UserFieldType::FIRST + (intval(preg_replace("/[^0-9]/", "", $token)) - 1);
      
      return ($ordinal);
   }
   
   public static function getLabel($userFieldType)
   {
      $label = UserFieldType::$LABELS[ReportLineType::UNKNOWN];
      
      if ($userFieldType <= UserFieldType::LAST)
      {
         $label = UserFieldType::$LABELS[$userFieldType];
      }
      
      return ($label);
   }
}

/*
$userFieldType = UserFieldType::getValue(UserFieldType::EMPLOYEE_NUMBER);
echo "getValue(EMPLOYEE_NUMBER) = {$userFieldType->label}<br>";
 
foreach (UserFieldType::getValues() as $userFieldType)
{
   echo "[{$userFieldType->ordinal}] : {$userFieldType->label}<br>";
}
*/

?>