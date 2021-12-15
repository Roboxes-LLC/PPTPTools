<?php

require_once 'database.php';

abstract class IsoDoc
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const PAN_TICKET = IsoDoc::FIRST;
   const TIME_CARD = 2;
   const PART_WEIGHT_LOG = 3;
   const PART_WASHER_LOG = 4;
   const IN_PROCESS_INSPECTION = 5;
   const QCP_INSPECTION = 6;
   const LINE_INSPECTION = 7;
   const MATERIAL_TICKET = 8;
   const LAST = 9;
   const COUNT = IsoDoc::LAST - IsoDoc::FIRST;
}

class IsoInfo 
{
   const DEFAULT_ISO_NUMBER = "";
   
   public $isoDoc;
   public $isoNumber;
   
   public function __construct()
   {
      $this->isoDoc = IsoDoc::UNKNOWN;
      $this->isoNumber = IsoInfo::DEFAULT_ISO_NUMBER;
   }
   
   public static function load($isoDoc)
   {
      $isoInfo = null;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getIso($isoDoc);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $isoInfo = new IsoInfo();
            
            $isoInfo->isoDoc =    intval($row['isoDoc']);
            $isoInfo->isoNumber = $row['isoNumber'];
         }
      }
      
      return ($isoInfo);
   }

   public static function getIsoNumber($isoDoc)
   {
      $isoNumber = IsoInfo::DEFAULT_ISO_NUMBER;
      
      $isoInfo = IsoInfo::Load($isoDoc);
      
      if ($isoInfo)
      {
         $isoNumber = $isoInfo->isoNumber;
      }
      
      return ($isoNumber);
   }
}
   

?>