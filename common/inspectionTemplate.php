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
   
   const OASIS_TEMPLATE_ID = 1;
   
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
   
   public function initializeFromDatabaseRow($row)
   {
      $this->templateId = intval($row['templateId']);
      $this->inspectionType = intval($row['inspectionType']);
      $this->name = $row['name'];
      $this->description = $row['description'];
      $this->sampleSize = intval($row['sampleSize']);
      $this->optionalProperties = intval($row['optionalProperties']);
      $this->notes = $row['notes'];
   }
   
   public static function load($templateId, $loadInspectionProperties = false)
   {
      $inspectionTemplate = null;
      
      $database = PPTPDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getInspectionTemplate($templateId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $inspectionTemplate = new InspectionTemplate();
            
            $inspectionTemplate->initializeFromDatabaseRow($row);
            
            // Optionally load actual inspection properties.
            if ($loadInspectionProperties)
            {
               $result = $database->getInspectionProperties($templateId);
               
               while ($result && ($row = $result->fetch_assoc()))
               {
                  $inspectionTemplate->inspectionProperties[] = InspectionProperty::load($row);
               }
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
      // GENERIC inspections have configurable optional properties.
      // All other types have hardcoded values.
      $optionalProperties = 
         ($this->inspectionType == InspectionType::GENERIC) ? 
            $this->optionalProperties : 
            InspectionType::getDefaultOptionalProperties($this->inspectionType);
      
      return (($optionalProperties & (1 << $optionalProperty)) > 0);
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
   
   public static function getInspectionTemplatesForJobNumber($inspectionType, $jobNumber)
   {
      $templateIds = array();
      
      $partNumber = JobInfo::getJobPrefix($jobNumber);
      
      $part = Part::load($partNumber, Part::USE_PPTP_NUMBER);
      
      if ($part && 
          ($part->inspectionTemplateIds[$inspectionType] != InspectionTemplate::UNKNOWN_TEMPLATE_ID))
      {
         $templateIds[] = $part->inspectionTemplateIds[$inspectionType];
      }
      
      return ($templateIds);
   }
   
   public static function getInspectionTemplatesForJob($inspectionType, $jobNumber, $jobId)
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
                        
         // These inspection types have a jobId associated with them.
         case InspectionType::FIRST_PART:
         case InspectionType::IN_PROCESS:
         case InspectionType::LINE:
         case InspectionType::QCP:
         {
            $jobInfo = JobInfo::load($jobId);
            if ($jobInfo &&
                $jobInfo->part &&
                ($jobInfo->part->inspectionTemplateIds[$inspectionType] != InspectionTemplate::UNKNOWN_TEMPLATE_ID))
            {
               // Note: At one point, each JobInfo had a set of templates, so a particular jobNumber could be associated with multiple templates.
               //       With the addition of the part component, a job number can only be associated with a single template of each type, by its part.
               $templateIds[] = $jobInfo->part->inspectionTemplateIds[$inspectionType];
            }
            break;
         }
         
         // Whereas a Final inspection covers multiple jobs, all with the same job number.
         case InspectionType::FINAL:
         {
            $templateIds = InspectionTemplate::getInspectionTemplatesForJobNumber($inspectionType, $jobNumber);
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
   $inspectionTemplate = InspectionTemplate::load($templateId, true);  // Load properties.
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