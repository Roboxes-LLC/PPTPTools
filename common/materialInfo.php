<?php

require_once 'database.php';
require_once 'materialDefs.php';

class MaterialInfo
{
   public $partNumber;
   public $type;
   public $shape;
   public $size;
   public $length;
   
   public function __construct()
   {
      $this->partNumber = null;
      $this->type = MaterialType::UNKNOWN;
      $this->shape = MaterialShape::UNKNOWN;
      $this->size = 0;
      $this->length = 0;
   }
   
   public function initialize($row)
   {
      $this->partNumber = $row['materialPartNumber'];
      $this->type = intval($row['materialType']);
      $this->shape = intval($row['materialShape']);
      $this->size = floatval($row['materialSize']);
      $this->length = intval($row['materialLength']);
   }
   
   public function getMaterialLabel()
   {
      // Ex: 12L14 HEX .75 15FT
      
      $shape = MaterialShape::getLabel($this->shape);
      
      $label = 
<<<HEREDOC
      $this->partNumber $shape $this->size {$this->length}FT 
HEREDOC;
      
      return ($label);
   }
   
   public function getMaterialDescription()
   {
      // Ex: 3/4" HEX STEEL 15FT SS
      
      $material = MaterialType::getAbbreviation($this->type);
      $size = MaterialInfo::floatToRational($this->size);
      $shape = MaterialShape::getAbbreviation($this->shape);
      
      
      $description =
<<<HEREDOC
      {$size}" $shape $material {$this->length} FT
HEREDOC;
      
      return ($description);
   }
   
   // **************************************************************************
   
   // https://stackoverflow.com/questions/14330713/converting-float-decimal-to-fraction
   private function floatToRational($n, $tolerance = 1.e-6)
   {
      $rationalString = "";
      
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
         
         $rationalString = "$h1/$k1";
      }
      
      return ($rationalString);
   }
}
