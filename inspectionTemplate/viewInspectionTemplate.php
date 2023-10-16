<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once '../common/authentication.php';
require_once '../common/header.php';
require_once '../common/inspection.php';
require_once '../common/inspectionTemplate.php';
require_once '../common/jobInfo.php';
require_once '../common/params.php';
require_once '../common/root.php';
require_once '../common/userInfo.php';

const ONLY_ACTIVE = true;

const INVALID_PROPERTY_INDEX = -1;

abstract class InspectionTemplateInputField
{
   const FIRST = 0;
   const NAME = InspectionTemplateInputField::FIRST;
   const DESCRIPTION = 1;
   const INSPECTION_TYPE = 2;
   const SAMPLE_SIZE = 3;
   const NOTES = 4;
   const PROPERTIES = 5;
   const LAST = 6;
   const COUNT = InspectionTemplateInputField::LAST - InspectionTemplateInputField::FIRST;
}

abstract class View
{
   const NEW_INSPECTION_TEMPLATE = 0;
   const VIEW_INSPECTION_TEMPLATE = 1;
   const EDIT_INSPECTION_TEMPLATE = 2;
}

function getParams()
{
   static $params = null;
   
   if (!$params)
   {
      $params = Params::parse();
   }
   
   return ($params);
}

function getView()
{
   $view = View::VIEW_INSPECTION_TEMPLATE;
   
   if (getTemplateId() == InspectionTemplate::UNKNOWN_TEMPLATE_ID)
   {
      $view = View::NEW_INSPECTION_TEMPLATE;
   }
   else if (Authentication::checkPermissions(Permission::EDIT_INSPECTION_TEMPLATE))
   {
      $view = View::EDIT_INSPECTION_TEMPLATE;
   }
   
   return ($view);
}

function getTemplateId()
{
   $params = getParams();
   
   return ($params->keyExists("templateId") ? $params->get("templateId") : InspectionTemplate::UNKNOWN_TEMPLATE_ID);
}

function getCopyFromTemplateId()
{
   $params = getParams();
   
   return ($params->keyExists("copyFrom") ? $params->get("copyFrom") : InspectionTemplate::UNKNOWN_TEMPLATE_ID);
}

function getInspectionTemplate()
{
   static $inspectionTemplate = null;
   
   if (!$inspectionTemplate)
   {
      $templateId = getTemplateId();
      
      $copyFromTemplateId = getCopyFromTemplateId();
      
      if ($templateId != InspectionTemplate::UNKNOWN_TEMPLATE_ID)
      {
         $inspectionTemplate = InspectionTemplate::load($templateId, true);  // Load properties.
      }
      else if ($copyFromTemplateId != InspectionTemplate::UNKNOWN_TEMPLATE_ID)
      {
         $inspectionTemplate = InspectionTemplate::load($copyFromTemplateId, true);  // Load properties.
         
         // Clear/modify select fields.
         $inspectionTemplate->templateId = InspectionTemplate::UNKNOWN_TEMPLATE_ID;
         $inspectionTemplate->name = $inspectionTemplate->name . "_copy";
      }
      else
      {
         $inspectionTemplate = new InspectionTemplate();
         
         // Start with a single property.
         $inspectionTemplate->inspectionProperties[] = new InspectionProperty();
      }
   }
   
   return ($inspectionTemplate);
}

function getInspectionType()
{
   $inspectionType = InspectionType::UNKNOWN;
   
   $inspectionTemplate = getInspectionTemplate();
   
   if ($inspectionTemplate)
   {
      $inspectionType = $inspectionTemplate->inspectionType;
   }
   
   return ($inspectionType);
}

function getInspectionName()
{
   $inspectionName = "";
   
   $inspectionTemplate = getInspectionTemplate();
   
   if ($inspectionTemplate)
   {
      $inspectionName = $inspectionTemplate->name;
   }
   
   return ($inspectionName);
}

function getInspectionDescription()
{
   $description = "";
   
   $inspectionTemplate = getInspectionTemplate();
   
   if ($inspectionTemplate)
   {
      $description = $inspectionTemplate->description;
   }
   
   return ($description);
}

function getSampleSize()
{
   $sampleSize = InspectionTemplate::DEFAULT_SAMPLE_SIZE;
   
   $inspectionTemplate = getInspectionTemplate();
   
   if ($inspectionTemplate)
   {
      $sampleSize = $inspectionTemplate->sampleSize;
   }
   
   return ($sampleSize);
}

function getDataTypeOptions($selectedDataType)
{
   $options = "<option value\"" . InspectionDataType::UNKNOWN . "\"></option>";
   
   for ($dataType = InspectionDataType::FIRST; $dataType != InspectionDataType::LAST; $dataType++)
   {
      $selected = ($dataType == $selectedDataType) ? "selected" : "";
      
      $label = InspectionDataType::getLabel($dataType);
      
      $options .= "<option value=\"$dataType\" $selected>$label</option>";
   }
   
   return ($options);
}

function getDataUnitsOptions($selectedDataUnits)
{
   $options = "<option value\"" . InspectionDataUnits::UNKNOWN . "\"></option>";
   
   for ($dataUnits = InspectionDataUnits::FIRST; $dataUnits != InspectionDataUnits::LAST; $dataUnits++)
   {
      $selected = ($dataUnits == $selectedDataUnits) ? "selected" : "";
      
      $label = InspectionDataUnits::getLabel($dataUnits);
      
      $options .= "<option value=\"$dataUnits\" $selected>$label</option>";
   }
   
   return ($options);
}

function getHeading()
{
   $heading = "";
   
   switch (getView())
   {
      case View::NEW_INSPECTION_TEMPLATE:
      {
         $heading = "Create a New Inspection Template";
         break;
      }
         
      case View::EDIT_INSPECTION_TEMPLATE:
      {
         $heading = "Update an Inspection Template";
         break;
      }
         
      case View::VIEW_INSPECTION_TEMPLATE:
      default:
      {
         $heading = "View an Inspection Template";
         break;
      }
   }
   
   return ($heading);
}

function getDescription()
{
   $description = "";
   
   switch (getView())
   {
      case View::NEW_INSPECTION_TEMPLATE:
      {
         $description = "Create a new template for inspections.  Start by selecting an inspection type and then add as many inspection properties as you need.";
         break;
      }
         
      case View::EDIT_INSPECTION_TEMPLATE:
      {
         $description = "Edit an existing template.  Note that adding or removing properties from this inspection will affect any current inspections that rely on this template.";
         break;
      }
         
      case View::VIEW_INSPECTION_TEMPLATE:
      default:
      {
         $description = "View the details on an existing template.";
         break;
      }
   }
   
   return ($description);
}

function isEditable($field)
{
   $view = getView();
   
   // Start with the edit mode, as dictated by the view.
   $isEditable = (($view == View::NEW_INSPECTION_TEMPLATE) ||
                  ($view == View::EDIT_INSPECTION_TEMPLATE));
   
   switch ($field)
   {
      case InspectionTemplateInputField::INSPECTION_TYPE:
      {
         $isEditable = ($view == View::NEW_INSPECTION_TEMPLATE);
         break;   
      }
      
      default:
      {
         // Edit status based solely on view.
         break;
      }
   }
   
   return ($isEditable);
}

function getDisabled($field)
{
   return (isEditable($field) ? "" : "disabled");
}

function getOptionalProperties()
{
   $html = "";
   
   $inspectionTemplate = getInspectionTemplate();
   
   if ($inspectionTemplate)
   {
      foreach (OptionalInspectionProperties::$genericOptionalInspectionProperties as $optionalProperty)
      {
         $name = "optional-property-$optionalProperty-input";
         $label = OptionalInspectionProperties::getLabel($optionalProperty);
         $checked = $inspectionTemplate->isOptionalPropertySet($optionalProperty) ? "checked" : "";
         $disabled = !isEditable(InspectionTemplateInputField::PROPERTIES);
         
         $html .=
<<<HEREDOC
         <input type="checkbox" name="$name" form="input-form" value="1" $checked $disabled>$label&nbsp;&nbsp;
HEREDOC;
      }
   }
   
   return ($html);
}

function getNotes()
{
   $notes = "";
   
   $inspectionTemplate = getInspectionTemplate();
   
   if ($inspectionTemplate)
   {
      $notes = $inspectionTemplate->notes;
   }
   
   return ($notes);
}

function getInspectionPropertyCount()
{
   $count = 0;
   
   $inspectionTemplate = getInspectionTemplate();
   
   if ($inspectionTemplate)
   {
      $count = count($inspectionTemplate->inspectionProperties);
   }
   
   return ($count);
   
}

function getInspectionProperties()
{
   $html = "";
   
   $inspectionTemplate = getInspectionTemplate();
   
   if ($inspectionTemplate)
   {
      // TODO: Remove.
      reorderProperties($inspectionTemplate);
      
      $propertyIndex = 0;
      foreach ($inspectionTemplate->inspectionProperties as $inspectionProperty)
      {
         $html .= getInspectionRow($propertyIndex, $inspectionProperty);
         
         $propertyIndex++;
      }
   }
   
   return ($html);
}

function getInspectionRow($propertyIndex, $inspectionProperty)
{
   
   $name = "property" . $propertyIndex;
   
   $propertyId = $inspectionProperty ? $inspectionProperty->propertyId : "0";
   $propertyName = $inspectionProperty ? $inspectionProperty->name : "";
   $specification = $inspectionProperty ? $inspectionProperty->specification : "";
   $dataType = $inspectionProperty ? $inspectionProperty->dataType : InspectionDataType::UNKNOWN;
   $dataUnits = $inspectionProperty ? $inspectionProperty->dataUnits : InspectionDataUnits::UNKNOWN;
   $ordering = $inspectionProperty ? $inspectionProperty->ordering : "$propertyIndex";
   
   $dataTypeOptions = getDataTypeOptions($dataType);
   $dataUnitsOptions = getDataUnitsOptions($dataUnits);
   
   $disabled = !isEditable(InspectionTemplateInputField::PROPERTIES);
   
   $html =
<<<HEREDOC
   <tr id="{$name}_row">
      <input name="{$name}_propertyId" type="hidden" form="input-form" value="$propertyId" $disabled>
      <input name="{$name}_ordering" type="hidden" form="input-form" value="$ordering" $disabled>
      <td></td>
      <td><input name="{$name}_name" type="text" form="input-form" value="$propertyName" $disabled></td>
      <td><input name="{$name}_specification" type="text" form="input-form" value="$specification" $disabled></td>
      <td><select name="{$name}_dataType" form="input-form" $disabled>$dataTypeOptions</select></td>
      <td><select name="{$name}_dataUnits" form="input-form" $disabled>$dataUnitsOptions</select></td>
      <td><div class="flex-vertical"><button onclick="onReorderProperty($propertyIndex, -1)">&#x25B2</button><button onclick="onReorderProperty($propertyIndex, 1)">&#x25BC</button></div></td>
   </tr>
HEREDOC;
   
   return ($html);
}

function reorderProperties(&$inspectionTemplate)
{
   // Temporary function for adding in ordering to existing templates.
   
   // Detect the condition where all properties have an ordering of zero.
   $allZeros = true;
   foreach ($inspectionTemplate->inspectionProperties as $inspectionProperty)
   {
      if ($inspectionProperty->ordering != 0)
      {
         $allZeros = false;
         break;
      }
   }
   
   if ($allZeros)
   {
      $propertyIndex = 0;
      foreach ($inspectionTemplate->inspectionProperties as $inspectionProperty)
      {
         $inspectionProperty->ordering = $propertyIndex;
         $propertyIndex++;
      }
   }
}

// ********************************** BEGIN ************************************

Time::init();

session_start();

if (!Authentication::isAuthenticated())
{
   header('Location: ../login.php');
   exit;
}

?>

<!DOCTYPE html>
<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">

   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons"/>
   
   <link rel="stylesheet" type="text/css" href="../common/theme.css"/>
   <link rel="stylesheet" type="text/css" href="../common/common.css"/>
   <link rel="stylesheet" type="text/css" href="inspectionTemplate.css"/>
   
   <script src="/common/common.js"></script>
   <script src="/common/validate.js"></script>
   <script src="/script/common/commonDefs.php<?php echo versionQuery();?>"></script>
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script>
   <script src="inspectionTemplate.js"></script>

</head>

<body class="flex-vertical flex-top flex-left">
        
   <form id="input-form" action="" method="POST">
      <input id="inspection-id-input" type="hidden" name="templateId" value="<?php echo getTemplateId(); ?>">
      <input type="hidden" name="inspectionType" value="<?php echo getInspectionType(); ?>">
      <!-- Hidden inputs make sure disabled fields below get posted. -->
   </form>

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading"><?php echo getHeading(); ?></div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div id="description" class="description"><?php echo getDescription(); ?></div>
         
         <br>
               
         <div class="form-item">
            <div class="form-label">Inspection Type</div>
            <select id="inspection-type-input" name="inspectionType"  form="input-form" oninput="onInspectionTypeChange();" <?php echo getDisabled(InspectionTemplateInputField::INSPECTION_TYPE); ?>>
                <?php echo getInspectionTypeOptions(getInspectionType(), false, [InspectionType::OASIS]); ?>
            </select>
         </div>
      
         <div class="form-item">
            <div class="form-label">Inspection Name</div>
            <input id="template-name-input" name="templateName" type="text"  style="width: 250px;" form="input-form" value="<?php echo getInspectionName() ?>" <?php echo getDisabled(InspectionTemplateInputField::NAME); ?>>
         </div>
         
         <div class="form-item">
            <div class="form-label">Description</div>
            <input name="templateDescription" type="text"  style="width: 450px;" form="input-form" value="<?php echo getInspectionDescription() ?>" <?php echo getDisabled(InspectionTemplateInputField::DESCRIPTION); ?>>
         </div>
         
         <div id="sample-size-input-container" class="form-item">
            <div class="form-label">Sample Size</div>
            <input id="sample-size-input" name="sampleSize" type="number"  style="width: 50px;" form="input-form" value="<?php echo getSampleSize() ?>" <?php echo getDisabled(InspectionTemplateInputField::SAMPLE_SIZE); ?>>
         </div>
         
         <div id="optional-properties-input-container" class="form-item">
            <div class="form-label">Optional Properties</div>
            <?php echo getOptionalProperties() ?>                     
         </div>
         
         <div class="form-item">
            <div class="form-label">Notes</div>
            <textarea name="notes" rows="4" cols="50" form="input-form" <?php echo getDisabled(InspectionTemplateInputField::NOTES); ?>><?php echo getNotes() ?></textarea>
         </div>
         
         <div class="flex-vertical flex-right">
         
            <div class="form-item">
               <table id="property-table">
                  <tr>
                     <th></th>
                     <th>Property</th>
                     <th>Specification</th>
                     <th>Data Type</th>
                     <th>Units</th>
                     <th></th>
                  <tr>
                  <?php echo getInspectionProperties() ?>
               </table>
            </div>
            
            <div class="form-item" style="justify-content: flex-end;">
               <button onclick="onAddProperty()" <?php echo getDisabled(InspectionTemplateInputField::PROPERTIES); ?>>+</button>
            </div>
            
         </div>
         
         <br>
         
         <div class="flex-horizontal flex-h-center">
            <button id="cancel-button">Cancel</button>&nbsp;&nbsp;&nbsp;
            <button id="save-button" class="accent-button">Save</button>            
         </div>
      
      </div> <!-- content -->
     
   </div> <!-- main -->   
         
   <script>
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::INSPECTION_TEMPLATE ?>);  
      
      preserveSession();
      
      var inspectionTypeValidator = new SelectValidator("inspection-type-input");
      
      inspectionTypeValidator.init();
   
      var propertyCount = <?php echo getInspectionPropertyCount(); ?>;
   
      function getNewInspectionRow()
      {
         var innerHtml = "<?php echo preg_replace( "/\r|\n/", "", addslashes(getInspectionRow("@", null)));?>";

         innerHtml = innerHtml.replace(/@/g, propertyCount);

         propertyCount++;

         return (innerHtml);
      }

      // Initialize visibility of optional properties.
      onInspectionTypeChange();

      // Setup event handling on all DOM elements.
      document.getElementById("cancel-button").onclick = function(){onCancel();};
      document.getElementById("save-button").onclick = function(){onSaveInspectionTemplate();};      
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};

      // Store the initial state of the form, for change detection.
      setInitialFormState("input-form");
            
   </script>

</body>

</html>
