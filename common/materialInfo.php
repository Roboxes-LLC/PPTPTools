<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/materialDefs.php';
require_once ROOT.'/core/common/stringUtils.php';

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
      $size = StringUtils::decimalToFraction($this->size);
      $shape = MaterialShape::getAbbreviation($this->shape);
      
      
      $description =
<<<HEREDOC
      {$size}" $shape $material {$this->length} FT
HEREDOC;
      
      return ($description);
   }
}
