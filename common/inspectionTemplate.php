<?php

require_once 'inspectionDefs.php';

class InspectionProperty
{
   const UNKNOWN_PROPERTY_ID = 0;
   
   public $propertyId;
   public $templateId;
   public $name;
   public $specification;
   public $dataType;
   public $dataUnits;
   public $ordering;
   
   public function __construct()
   {
      $this->propertyId = InspectionProperty::UNKNOWN_PROPERTY_ID;
      $this->templateId = InspectionTemplate::UNKNOWN_TEMPLATE_ID;
      $this->name = "";
      $this->specification = "";
      $this->dataType = InspectionDataType::UNKNOWN;
      $this->dataUnits = InspectionDataUnits::UNKNOWN;
      $this->ordering = 0;
   }
   
   public static function load($row)
   {
      $inspectionProperty = null;
      
      if ($row)
      {
         $inspectionProperty = new InspectionProperty();
         
         $inspectionProperty->propertyId = intval($row['propertyId']);
         $inspectionProperty->templateId = intval($row['templateId']);
         $inspectionProperty->name = $row['name'];
         $inspectionProperty->specification = $row['specification'];
         $inspectionProperty->dataType = intval($row['dataType']);
         $inspectionProperty->dataUnits = intval($row['dataUnits']);
         $inspectionProperty->ordering = intval($row['ordering']);
      }
      
      return ($inspectionProperty);
   }
}

class InspectionTemplate
{
   const UNKNOWN_TEMPLATE_ID = 0;
   
   const DEFAULT_SAMPLE_SIZE = 1;
   
   public $templateId;
   public $inspectionType;
   public $name;
   public $description;
   public $sampleSize;
   public $optionalProperties;
   public $notes;
   public $inspectionProperties;
   
   public function __construct()
   {
      $this->templateId = InspectionTemplate::UNKNOWN_TEMPLATE_ID;
      $this->inspectionType = InspectionType::UNKNOWN;
      $this->name = "";
      $this->description = "";
      $this->sampleSize = InspectionTemplate::DEFAULT_SAMPLE_SIZE;
      $this->optionalProperties = 0;
      $this->notes = "";
      $this->inspectionProperties = array();
   }
   
   public static function load($templateId)
   {
      $inspectionTemplate = null;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getInspectionTemplate($templateId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $inspectionTemplate = new InspectionTemplate();
            
            $inspectionTemplate->templateId = intval($row['templateId']);
            $inspectionTemplate->inspectionType = intval($row['inspectionType']);
            $inspectionTemplate->name = $row['name'];
            $inspectionTemplate->description = $row['description'];
            $inspectionTemplate->sampleSize = intval($row['sampleSize']);
            $inspectionTemplate->optionalProperties = intval($row['optionalProperties']);
            $inspectionTemplate->notes = $row['notes'];
            
            $result = $database->getInspectionProperties($templateId);
            
            while ($result && ($row = $result->fetch_assoc()))
            {
               $inspectionTemplate->inspectionProperties[] = InspectionProperty::load($row);
            }
         }
      }
      
      return ($inspectionTemplate);
   }
   
   public function setOptionalProperty($optionalProperty)
   {
      if (($optionalProperty >= OptionalInspectionProperties::FIRST) &&
          ($optionalProperty < OptionalInspectionProperties::LAST))
      {
         $this->optionalProperties |= (1 << $optionalProperty);
      }
   }
   
   public function clearOptionalProperty($optionalProperty)
   {
      if (($optionalProperty >= OptionalInspectionProperties::FIRST) &&
          ($optionalProperty < OptionalInspectionProperties::LAST))
      {
         $this->optionalProperties &= (~(1 << $optionalProperty));
      }
   }
   
   public function isOptionalPropertySet($optionalProperty)
   {
      return (($this->optionalProperties & (1 << $optionalProperty)) > 0);
   }
   
   public static function getInspectionTemplates($inspectionType)
   {
      $templateIds = array();
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getInspectionTemplates($inspectionType);
         
         while ($result && ($row = $result->fetch_assoc()))
         {
            $templateIds[] = intval($row["templateId"]);
         }
      }
      
      return ($templateIds);
   }
   
   public static function getInspectionTemplatesForJob($inspectionType, $jobId)
   {
      $templateIds = array();

      switch ($inspectionType)
      {
         case InspectionType::OASIS:
         case InspectionType::GENERIC:
         {
            $templateIds = InspectionTemplate::getInspectionTemplates($inspectionType);
            break;
         }
            
         case InspectionType::IN_PROCESS:
         case InspectionType::LINE:
         case InspectionType::QCP:
         {
           $jobInfo = JobInfo::load($jobId);
           if ($jobInfo)
           {
              if ($inspectionType == InspectionType::IN_PROCESS)
              {
                 $templateIds[] = $jobInfo->inProcessTemplateId;
              }
              else if ($inspectionType == InspectionType::LINE)
              {
                 $templateIds[] = $jobInfo->lineTemplateId;
              }
              else if ($inspectionType == InspectionType::QCP)
              {
                 $templateIds[] = $jobInfo->qcpTemplateId;
              }
           }
           break;
         }
            
         default:
         {
            break;
         }
      }
      
      return ($templateIds);
   }
   
}

/*
if (isset($_GET["templateId"]))
{
   $templateId = $_GET["templateId"];
   $inspectionTemplate = InspectionTemplate::load($templateId);
   if ($inspectionTemplate)
   {
      echo "templateId: " .         $inspectionTemplate->templateId .                               "<br/>";
      echo "inspectionType: " .     InspectionType::getLabel($inspectionTemplate->inspectionType) . "<br/>";
      echo "name: " .               $inspectionTemplate->name .                                     "<br/>";
      echo "description: " .        $inspectionTemplate->description .                              "<br/>";
      echo "sampleSize: " .         $inspectionTemplate->sampleSize .                               "<br/>";
      echo "optionalProperties: " . $inspectionTemplate->optionalProperties .                       "<br/>";
      echo "notes: " .              $inspectionTemplate->notes .                                    "<br/>";
      
      foreach ($inspectionTemplate->inspectionProperties as $inspectionProperty)
      {
         echo $inspectionProperty->name . ": " . InspectionDataType::getLabel($inspectionProperty->dataType) . ", " . $inspectionProperty->ordering . "<br/>";
      }
   }
   else
   {
      echo "No inspection template found.";
   }
}
*/
?>