<?php

require_once 'database.php';

class CommentCode
{
   const NULL_COMMENT_CODE_ID = 0;
   
   const NO_CODES = 0x0000;
   
   const ALL_CODES = 0xFFFF;
   
   const PARTS_TAKEN_EARLY_CODE_ID = 9;
   
   public $code;
   
   public $description;
   
   public $bits;
   
   public static function getCommentCodes()
   {
      if (CommentCode::$codes == null)
      {
         CommentCode::$codes = array();
      
         $database = PPTPDatabase::getInstance();
         
         if ($database && $database->isConnected())
         {
            $result = $database->getCommentCodes();
            
            while ($result && ($row = $result->fetch_assoc()))
            {
               CommentCode::$codes[] = new CommentCode(intval($row["code"]), $row["description"]);
            }
         }
      }
      
      return (CommentCode::$codes);
   }
   
   public static function getCommentCode($code)
   {
      $foundIt = null;
      
      $commentCodes = CommentCode::getCommentCodes();
      foreach ($commentCodes as $commentCode)
      {
         if ($commentCode->code == $code)
         {
            $foundIt = $commentCode;
            break;
         }
      }
      
      return ($foundIt);
   }
   
   public function isSetIn($mask)
   {
      return (($this->bits & $mask) > 0);
   }
   
   public static function getBits(...$codes)
   {
      $bits = CommentCode::NO_CODES;
      
      foreach ($codes as $code)
      {
         $bits |=  CommentCode::CommentCode($code)->bits;
      }
      
      return ($bits);
   }
   
   private static $codes = null;
   
   private function __construct($code, $description)
   {
      $this->code = $code;
      $this->description = $description;
      
      if ($code > CommentCode::NULL_COMMENT_CODE_ID)
      {
         $this->bits = (1 << ($code - 1));
      }
      else
      {
         $this->bits = 0;
      }
   }
}

/*
$commentCodes = CommentCode::getCommentCodes();
foreach ($commentCodes as $commentCode)
{
   echo $commentCode->code . ": \"" . $commentCode->description . "\" (" . $commentCode->bits . ")<br/>";
}

$commentCode = CommentCode::getCommentCode(2);
if ($commentCode)
{
   echo $commentCode->code . ": \"" . $commentCode->description . "\" (" . $commentCode->bits . ")<br/>";
}
*/