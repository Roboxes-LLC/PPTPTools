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
     
   public static function decimalToFraction($decimal)
   {
      $string = "";
      
      $fraction = StringUtils::floatToRational($decimal);
      $compound = StringUtils::compoundToMixedFraction($fraction->numerator, $fraction->denominator);
      
      if ($compound->whole > 0)
      {
         $string = "$compound->whole {$compound->numerator}/{$compound->denominator}";
      }
      else
      {
         $string = "{$compound->numerator}/{$compound->denominator}";
      }
         
      return ($string);
   }
   
   // **************************************************************************
   
   private static function floatToRational($n, $tolerance = 1.e-6)
   {
      // https://stackoverflow.com/questions/14330713/converting-float-decimal-to-fraction
      
      $fraction = new stdClass();
      $fraction->numerator = 0;
      $fraction->denominator = 0;
      
      if ($n != 0)
      {
         $h1=1;
         $h2=0;
         $k1=0;
         $k2=1;
         $b = 1 / $n;
         
         do
         {
            $b = 1 / $b;
            $a = floor($b);
            $aux = $h1;
            $h1 = $a * $h1 + $h2;
            $h2 = $aux;
            $aux = $k1;
            $k1 = $a * $k1 + $k2;
            $k2 = $aux;
            $b = $b - $a;
         } while (abs($n-$h1/$k1) > ($n * $tolerance));
         
         $fraction->numerator = $h1;
         $fraction->denominator = $k1;
      }
      
      return ($fraction);
   }
   
   private static function compoundToMixedFraction($numerator, $denominator)
   {
      $compound = new stdClass();
      $compound->whole = 0;
      $compound->numerator = $numerator;
      $compound->denominator = $denominator;
      
      if ($numerator > $denominator)
      {
         $compound->whole = intdiv($numerator, $denominator);
         $compound->numerator = ($numerator - ($compound->whole * $denominator));
      }
      
      return ($compound);
   }
}

?>