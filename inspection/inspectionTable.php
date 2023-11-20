<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/inspection.php';

class InspectionTable
{
   public static function getHtml($inspectionId, $templateId, $quantity, $allowQuickInspection, $isEditable)
   {
      $html = "";
      
      $inspection = InspectionTable::getInspection($inspectionId, $templateId);
      
      $inspectionTemplate = $inspection ? InspectionTemplate::load($inspection->templateId, true) : null;  // Load properties.
      
      if ($inspection && $inspectionTemplate)
      {
         $sampleSize = $inspection->getSampleSize($quantity);
         
         $header = InspectionTable::getHeader($inspection, $inspectionTemplate, $sampleSize, $allowQuickInspection);
         
         $rows = InspectionTable::getRows($inspection, $inspectionTemplate, $sampleSize, $isEditable, $allowQuickInspection);
   
         $html = 
<<<HEREDOC
         <table class="inspection-table">
            $header
            $rows
         </table>
HEREDOC;
      }
      
      return ($html);
   }
   
   // **************************************************************************
   //                                Private
   
   private static function getInspection($inspectionId, $templateId)
   {
      $inspection = null;
      
      if ($inspectionId != Inspection::UNKNOWN_INSPECTION_ID)
      {
         $inspection = Inspection::load($inspectionId, true);  // Load actual results.
      }
      else
      {
         $inspection = new Inspection();
         $inspection->templateId = $templateId;
         
         $inspectionTemplate = InspectionTemplate::load($inspection->templateId, true);  // Load properties.
      }
      
      return ($inspection);
   }
   
   private static function getHeader($inspection, $inspectionTemplate, $sampleSize, $allowQuickInspection)
   {
      $html = "";
      
      $quickInspection = InspectionTable::getQuickInspectionButton($allowQuickInspection);  // approveAll
      
      $html .=
<<<HEREDOC
      <tr>
         <td>$quickInspection</td>
         <td></td>
         <td></td>
HEREDOC;
      
      // Sample heading.
      for ($sampleIndex = 0; $sampleIndex < $sampleSize; $sampleIndex++)
      {
         $sampleId = ($sampleIndex + 1);
         
         if (InspectionType::isTimeBased($inspectionTemplate->inspectionType))
         {
            $timeStr = "";
            
            $dateTime = $inspection->getSampleDateTime($sampleIndex, false);
            if ($dateTime != null)
            {
               $timeStr = Time::dateTimeObject($dateTime)->format("g:i a");
            }
            
            $html .=
<<<HEREDOC
            <th>
               <div class="flex-column">
                  <div>Check $sampleId</div>
                  <div>$timeStr</div>
               </div>
            </th>
HEREDOC;
         }
         else
         {
            $html .=
<<<HEREDOC
            <th>Sample $sampleId</th>
HEREDOC;
         }
      }
      
      $html .= "<th>Comment</th>";
      
      $html .= "</tr>";
            
      return ($html);
   }   
   
   private static function getRows($inspection, $inspectionTemplate, $sampleSize, $isEditable, $allowQuickInspection)
   {
      $html = "";
            
      foreach ($inspectionTemplate->inspectionProperties as $inspectionProperty)
      {
         $hasData = InspectionTable::hasData($inspection, $inspectionProperty->propertyId);
         $dataRowDisplayStyle = $hasData ? "" : "none";
         $expandButtonDisplayStyle = $hasData ? "none" : "";
         $condenseButtonDisplayStyle = $hasData ? "" : "none";
         
         $html .= InspectionTable::getRow($inspection, $inspectionProperty, $sampleSize, $isEditable, $allowQuickInspection);
      }
      
      return ($html);
   }
      
   private static function getRow($inspection, $inspectionProperty, $sampleSize, $isEditable, $allowQuickInspection)
   {
      $html = "";
      
      $quickInspection = InspectionTable::getQuickInspectionButton($allowQuickInspection, $inspectionProperty->propertyId);  // approveRow
      
      $hasData = InspectionTable::hasData($inspection, $inspectionProperty->propertyId);
      $dataRowDisplayStyle = $hasData ? "" : "none";
      $expandButtonDisplayStyle = $hasData ? "none" : "";
      $condenseButtonDisplayStyle = $hasData ? "" : "none";
      
      $html .= "<tr data-property-id=\"$inspectionProperty->propertyId\">";
      
      $html .=
<<<HEREDOC
         <td><div class="expand-button" style="display:$expandButtonDisplayStyle;" onclick="showData(this)">+</div><div class="condense-button" style="display:$condenseButtonDisplayStyle;" onclick="hideData(this)">-</div></td>
         <td>$quickInspection</td>         
         <td>
            <div class="flex-vertical">
               <div class="inspection-property-name">$inspectionProperty->name</div>
               <div>$inspectionProperty->specification</div>
            </div>
         </td>
HEREDOC;
      
      for ($sampleIndex = 0; $sampleIndex < $sampleSize; $sampleIndex++)
      {
         $inspectionResult = null;
         if (isset($inspection->inspectionResults[$inspectionProperty->propertyId][$sampleIndex]))
         {
            $inspectionResult = $inspection->inspectionResults[$inspectionProperty->propertyId][$sampleIndex];
         }
         
         $html .= InspectionTable::getInspectionInput($inspectionProperty, $sampleIndex, $inspectionResult, $isEditable);
      }
      
      $comment = "";
      if (isset($inspection->inspectionResults[$inspectionProperty->propertyId][InspectionResult::COMMENT_SAMPLE_INDEX]))
      {
         $inspectionResult = $inspection->inspectionResults[$inspectionProperty->propertyId][InspectionResult::COMMENT_SAMPLE_INDEX];
         
         $comment = $inspectionResult->data;
      }
      
      $html .= InspectionTable::getInspectionCommentInput($inspectionProperty, $comment, $isEditable);
      
      $html .= "</tr>";
      
      $html .= "<tr style=\"display:$dataRowDisplayStyle;\"><td/><td/><td/>";
      
      for ($sampleIndex = 0; $sampleIndex < $sampleSize; $sampleIndex++)
      {
         $inspectionResult = null;
         if (isset($inspection->inspectionResults[$inspectionProperty->propertyId][$sampleIndex]))
         {
            $inspectionResult = $inspection->inspectionResults[$inspectionProperty->propertyId][$sampleIndex];
         }
         
         $html .= InspectionTable::getInspectionDataInput($inspectionProperty, $sampleIndex, $inspectionResult, $isEditable);
      }
      
      $html .= "</tr>";
      
      return ($html);
   }
   
   private static function hasData($inspection, $inspectionPropertyId)
   {
      $hasData = false;
      
      if (isset($inspection->inspectionResults[$inspectionPropertyId]))
      {
         foreach ($inspection->inspectionResults[$inspectionPropertyId] as $inspectionResult)
         {
            if (!(($inspectionResult->sampleIndex == InspectionResult::COMMENT_SAMPLE_INDEX) ||
                  ($inspectionResult->data == null) ||
                  ($inspectionResult->data === "")))
            {
               $hasData = true;
               break;
            }
         }
      }
      
      return ($hasData);
   }
   
   private static function getQuickInspectionButton($allowQuickInspection, $propertyId = 0)
   {
      $html = "";
      
      if ($allowQuickInspection)
      {
         $approveAll = ($propertyId == 0);
         $function = $approveAll ? "approveAll()" : "approveRow($propertyId)";
         $class = $approveAll ? "approve-all" : "approve-row";
         
         $html =
<<<HEREDOC
         <i class="material-icons $class" onclick="$function">thumb_up</i>
HEREDOC;
      }
      
      return ($html);
   }
   
   private static function getInspectionInput($inspectionProperty, $sampleIndex, $inspectionResult, $isEditable)
   {  
      $html = "<td>";
      
      if ($inspectionProperty)
      {
         $name = InspectionResult::getInputName($inspectionProperty->propertyId, $sampleIndex);
         
         $pass = "";
         $warning = "";
         $fail = "";
         $nonApplicable = "selected";
         $updateTime = "";
         $class = "";
         
         if ($inspectionResult)
         {
            $pass = ($inspectionResult->pass()) ? "selected" : "";
            $warning = ($inspectionResult->warning()) ? "selected" : "";
            $fail = ($inspectionResult->fail()) ? "selected" : "";
            $nonApplicable = ($inspectionResult->nonApplicable()) ? "selected" : "";
            $class = InspectionStatus::getClass($inspectionResult->status);
            
            if (!$inspectionResult->nonApplicable() && ($inspectionResult->dateTime))
            {
               $dateTime = new DateTime($inspectionResult->dateTime, new DateTimeZone('America/New_York'));
               $updateTime = $dateTime->format("g:i a");
            }
         }
         
         $nonApplicableValue = InspectionStatus::NON_APPLICABLE;
         $passValue = InspectionStatus::PASS;
         $warningValue = InspectionStatus::WARNING;
         $failValue = InspectionStatus::FAIL;
         
         $disabled = !$isEditable ? "disabled" : "";
         
         $html .=
<<<HEREDOC
         <div class="flex-vertical">
            <select name="$name" class="inspection-status-input $class" form="input-form" oninput="onInspectionStatusUpdate(this)" $disabled>
               <option value="$nonApplicableValue" $nonApplicable>N/A</option>
               <option value="$passValue" $pass>PASS</option>
               <option value="$warningValue" $warning>WARNING</option>
               <option value="$failValue" $fail>FAIL</option>
            </select>
            <!--div style="height:20px">$updateTime</div-->
         </div>
HEREDOC;
      }
      
      $html .= "</td>";
      
      return ($html);
   }
   
   private static function getInspectionCommentInput($inspectionProperty, $comment, $isEditable)
   {
      $html = "<td>";
      
      if ($inspectionProperty)
      {
         $name = InspectionResult::getInputName($inspectionProperty->propertyId, InspectionResult::COMMENT_SAMPLE_INDEX);
         
         $disabled = !$isEditable ? "disabled" : "";
         
         $html .= "<input name=\"$name\" type=\"text\" form=\"input-form\" maxlength=\"80\" value=\"$comment\" $disabled>";
      }
      
      $html .= "</td>";
      
      return ($html);
   }   
   
   private static function getInspectionDataInput($inspectionProperty, $sampleIndex, $inspectionResult, $isEditable)
   {
      $html = "<td>";
      
      if ($inspectionProperty)
      {
         $name = InspectionResult::getInputName($inspectionProperty->propertyId, $sampleIndex);
         $dataName = $name . "_data";
         $inputType = "text";
         $dataValue = "";
         
         if ($inspectionResult)
         {
            $dataValue = $inspectionResult->data;
         }
         
         $disabled = !$isEditable ? "disabled" : "";
         
         $dataUnits = InspectionDataUnits::getAbbreviatedLabel($inspectionProperty->dataUnits);
         
         if (($inspectionProperty->dataType == InspectionDataType::INTEGER) ||
             ($inspectionProperty->dataType == InspectionDataType::DECIMAL))
         {
            $inputType = "number";
         }
         
         $html .=
<<<HEREDOC
         <input name="$dataName" type="$inputType" form="input-form" style="width:80px;" value="$dataValue" $disabled>&nbsp$dataUnits
HEREDOC;
      }
      
      $html .= "</td>";
      
      return ($html);
   }
}

?>