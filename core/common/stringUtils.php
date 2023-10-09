<?php

abstract class StringUtils
{
   public static function matchCase($target, $compare)
   {
      if (ctype_upper(substr($compare, 0, 1)))
      {
         $target = ucfirst($target);
      }
      else
      {
         $target = lcfirst($target);
      }
      
      return ($target);
   }
   
   public static function pluralize($noun, $count)
   {
      $IRREGULAR = [
         (object)["singular" => "foot", "plural" => "feet"]
      ];
      
      $RULES = [
         (object)["regex" => "/[hsx]$/",  "suffix" => "es"],  // fish -> fishes, bass -> basses, fox -> foxes
         (object)["regex" => "/ch$/",    "suffix" => "es"],   // finch -> finches
         (object)["regex" => "/[^sx]$/", "suffix" => "s"]     // frog -> frogs
      ];
      
      if (abs($count) != 1)
      {
         $matched = false;
         
         foreach ($IRREGULAR as $rule)
         {
            if (strtolower($noun) == $rule->singular)
            {
               $noun = StringUtils::matchCase($rule->plural, $noun);
               $matched = true;
            }
         }
         
         if (!$matched)
         {
            foreach ($RULES as $rule)
            {
               if (preg_match($rule->regex, $noun))
               {
                  $noun .= $rule->suffix;
                  break;
               }
            }
         }
      }
      
      return ($noun);
   }
   
   public static function addTabulatorLinkSpaces($string)
   {
      // Tabulator has an odd quirk where it removes whitespace before an after links.
      
      $startPos = strpos($string, "<a", 0);
      
      while ($startPos)
      {
         if (substr($string, ($startPos - 1), 1) == " ")
         {
            $string = substr_replace($string, "&nbsp;", $startPos, 0);
         }
         
         $endPos = strpos($string, "</a>", $startPos);
         
         if (($endPos) &&
             (substr($string, ($endPos + 4), 1) == " "))
         {
            
            $string = substr_replace($string, "&nbsp;", ($endPos + 4), 0);
         }
         
         $startPos = strpos($string, "<a", $endPos);
      }
      
      return ($string);
   }
   
   public static function xmlEncode($string)
   {
      return (htmlspecialchars($string, ENT_XML1, 'UTF-8'));
   }
}

?>