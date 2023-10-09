<?php

class UsaStates
{
   const UNKNOWN_STATE_ID = 0;
   
   public static $states = array(
      'Alabama'=>'AL',
      'Alaska'=>'AK',
      'Arizona'=>'AZ',
      'Arkansas'=>'AR',
      'California'=>'CA',
      'Colorado'=>'CO',
      'Connecticut'=>'CT',
      'Delaware'=>'DE',
      'Florida'=>'FL',
      'Georgia'=>'GA',
      'Hawaii'=>'HI',
      'Idaho'=>'ID',
      'Illinois'=>'IL',
      'Indiana'=>'IN',
      'Iowa'=>'IA',
      'Kansas'=>'KS',
      'Kentucky'=>'KY',
      'Louisiana'=>'LA',
      'Maine'=>'ME',
      'Maryland'=>'MD',
      'Massachusetts'=>'MA',
      'Michigan'=>'MI',
      'Minnesota'=>'MN',
      'Mississippi'=>'MS',
      'Missouri'=>'MO',
      'Montana'=>'MT',
      'Nebraska'=>'NE',
      'Nevada'=>'NV',
      'New Hampshire'=>'NH',
      'New Jersey'=>'NJ',
      'New Mexico'=>'NM',
      'New York'=>'NY',
      'North Carolina'=>'NC',
      'North Dakota'=>'ND',
      'Ohio'=>'OH',
      'Oklahoma'=>'OK',
      'Oregon'=>'OR',
      'Pennsylvania'=>'PA',
      'Rhode Island'=>'RI',
      'South Carolina'=>'SC',
      'South Dakota'=>'SD',
      'Tennessee'=>'TN',
      'Texas'=>'TX',
      'Utah'=>'UT',
      'Vermont'=>'VT',
      'Virginia'=>'VA',
      'Washington'=>'WA',
      'West Virginia'=>'WV',
      'Wisconsin'=>'WI',
      'Wyoming'=>'WY'
   );
   
   public static function getStateName($stateId)
   {
      $stateName = "";
      
      $keys = array_keys(UsaStates::$states);
      
      if ($stateId < count($keys))
      {
         $stateName = $keys[$stateId - 1];
      }
      
      return ($stateName);
   }
   
   public static function getStateAbbreviation($stateId)
   {
      $stateAbbreviation = "";
      
      $keys = array_keys(UsaStates::$states);
      
      if (($stateId > 0) && ($stateId < count($keys)))
      {
         $stateAbbreviation = UsaStates::$states[$keys[$stateId - 1]];
      }
      
      return ($stateAbbreviation);
   }
   
   public static function getStateId($stateAbbreviation)
   {
      $stateId = UsaStates::UNKNOWN_STATE_ID;
      
      $keys = array_keys(UsaStates::$states);
      
      for ($index = 0; $index < count($keys); $index++)
      {
         $key = $keys[$index];
         
         if (UsaStates::$states[$key] == strtoupper($stateAbbreviation))
         {
            $stateId = ($index + 1);
            break;
         }
      }
      
      return ($stateId);
   }
   
   public static function getOptions($selectedStateId)
   {
      $html = "<option style=\"display:none\">";
      
      $keys = array_keys(UsaStates::$states);
      
      for ($stateIndex = 0; $stateIndex < count($keys); $stateIndex++)
      {
         $stateId = ($stateIndex + 1);
         $selected = ($stateId == $selectedStateId) ? "selected" : "";
         $label = $keys[$stateIndex];
                  
         $html .= "<option value=\"$stateId\" $selected>$label</option>";
      }
      
      return ($html);
   }
}

?>