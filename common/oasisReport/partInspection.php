<?php

require_once 'partMeasurement.php';
require_once 'reportLineType.php';

class PartInspection
{
   public function parse($line)
   {
      // Oasis part inspection format:
      /*
       START|C:\gpc\Oasis\Data\M8206 Rev 10.txt
       DATA|thd lgh|0.6600|0.6620|0.6667|0.6980|0.7000|PASS
       DATA|.75|0.7400|0.7410|0.7485|0.7590|0.7600|PASS
       DATA|.641|0.6310|0.6320|0.6419|0.6500|0.6510|PASS
       DATA|und cut|0.6310|0.6320|0.6426|0.6500|0.6510|PASS
       DATA|major|0.7385|0.7389|0.7454|0.7461|0.7465|PASS
       DATA|pitch|0.7030|0.7032|0.7055|0.7063|0.7065|PASS
       DATA|nose|0.4280|0.4290|0.4414|0.4470|0.4480|PASS
       DATA|45 DEG| 44.50| 44.55| 44.64| 45.45| 45.50|PASS
       DATA|.25 LGH|0.2400|0.2410|0.2476|0.2590|0.2600|PASS
       DATA|OAL|1.5180|1.5185|1.5236|1.5275|1.5280|PASS
       END|Mar 21 2017|11:05:43 PM
       */
      
      $success = true;
      
      if ($this->isValid == true)
      {
         // Parse error!
         $success = false;
      }
      else
      {      
         $tokens = explode("|", $line);
      
         $tokenCount = count($tokens);
         
         if ($tokenCount > 0)
         {
            $lineType = ReportLineType::valueOf($tokens[0]);
            
            if ($tokenCount != ReportLineType::getTokenCount($lineType))
            {
               // Parse error!
               $success = false;
            }
            else
            {
               switch ($lineType)
               {
                  case ReportLineType::PART_INSPECTION_START:
                  {
                     $this->dataFile = $tokens[1];
                     break;
                  }
                     
                  case ReportLineType::PART_INSPECTION_DATA:
                  {
                     $partMeasurement = PartMeasurement::parse($line);
                     if ($partMeasurement)
                     {
                        $this->measurements[] = $partMeasurement;
                     }
                     else
                     {
                        // Parse error!
                        $success = false;
                     }
                     break;
                  }
                     
                  case ReportLineType::PART_INSPECTION_END:
                  {
                     $this->date = $tokens[1] . " " . $tokens[2];
                     
                     // TODO
                     /*
                     // To match: Mar 21 2017|11:05:43 PM
                     DateFormat format = new SimpleDateFormat("MMM dd yyyy hh:mm:ss a", Locale.ENGLISH);
                     date = format.parse(dateString);
                     */
                        
                     $this->isValid = true;
                     break;
                  }
                     
                  default:
                  {
                     // Parse error!
                     $success = false;
                  }
               }
            }
         }
      }
      
      return ($success);
   }
   
   public function isValid()
   {
      return ($this->isValid);
   }
   
   public function getDataFile()
   {
      return ($this->dataFile);
   }
   
   public function setDataFile($dataFile)
   {
      $this->dataFile = $dataFile;
   }
   
   public function getDate()
   {
      return ($this->date);
   }
   
   public function setDate($date)
   {
     $this->$date = $date;
   }
   
   public function getMeasurements()
   {
     return ($this->measurements);
   }
   
   public function addMeasurement($measurement)
   {
     $this->measurements[] = $measurement;
   }
  
   public function getCountByStatus($inspectionStatus)
   {
      $count = 0;
     
      foreach ($this->measurements as $measurement)
      {
        switch ($inspectionStatus)
        {
            case InspectionStatus::PASS:
            {
               if (MeasurementResult::isPassed($measurement->getResult()))
               {
                 $count++;
               }
               break;
            }
              
            case InspectionStatus::WARNING:
            {
               if (MeasurementResult::isWarning($measurement->getResult()))
               {
                 $count++;
               }
               break;
            }
              
            case InspectionStatus::FAIL:
            {
               if (MeasurementResult::isFailed($measurement->getResult()))
               {
                 $count++;
               }
               break;
            }
              
            case InspectionStatus::NON_APPLICABLE:
            default:
            {
              break;
            }
         }
      }
     
      return ($count);
   }
   
   public function pass()
   {
     return (!$this->fail() &&
             !$this->warning() &&
             ($this->getCountByStatus(InspectionStatus::PASS) > 0));
   }
   
   public function warning()
   {
     return (!$this->fail() && ($this->getCountByStatus(InspectionStatus::WARNING) > 0));
   }
   
   public function fail()
   {
     return ($this->getCountByStatus(InspectionStatus::FAIL) > 0);
   }
   
   public function toHtml()
   {
      $html = "<table class=\"part-inspection-table\">";

      // Table heading.
      $html .= "<tr class=\"part-inspection-table-header\">";
      $html .= "<th>Name</th>";
      foreach (MeasurementType::$VALUES as $measurmentType)
      {
         $html .= "<th>" . MeasurementType::getLabel($measurmentType) . "</th>";
      }
      $html .= "<th>Result</th>";
      $html .= "</tr>";
      
      foreach ($this->measurements as $measurement)
      {
         $html .= $measurement->toHtml();
      }
      
      $html .= "</table>";
        
      return ($html);
   }
   
   private $isValid = false;
   
   private $dataFile = "";
   
   private $date = "";
   
   private $measurements = array();
}

/*
$partMeasurement = new PartMeasurement();
$partMeasurement->setName("Measurement 1");
$partMeasurement->addValue(MeasurementType::LOW_WARN, 2.5);
$partMeasurement->addValue(MeasurementType::MEASURED, 7.1);
$partMeasurement->addValue(MeasurementType::HIGH_WARN, 1.0);
$partMeasurement->addValue(MeasurementType::HIGH_LIMIT, 6.9);
$partMeasurement->setResult(MeasurementResult::FAIL_LOW);

$partInspection = new PartInspection();
$partInspection->setDataFile("report.rpt");
$partInspection->setDate(new DateTime());
$partInspection->addMeasurement($partMeasurement);

$validString = $partInspection->isValid() ? "true" : "false";
echo "isValid: $validString<br>";
echo "dataFile: {$partInspection->getDataFile()}<br>";
echo "date: {$partInspection->getDate()->format("Y-m-d")}<br>";
echo "failureCount: {$partInspection->getFailureCount()}<br>";

echo $partInspection->toHtml();

$lines = array(
   "START|C:\gpc\Oasis\Data\M8206 Rev 10.txt",
   "DATA|thd lgh|0.6600|0.6620|0.6667|0.6980|0.7000|PASS",
   "DATA|.75|0.7400|0.7410|0.7485|0.7590|0.7600|PASS",
   "DATA|.641|0.6310|0.6320|0.6419|0.6500|0.6510|PASS",
   "DATA|und cut|0.6310|0.6320|0.6426|0.6500|0.6510|PASS",
   "DATA|major|0.7385|0.7389|0.7454|0.7461|0.7465|PASS",
   "DATA|pitch|0.7030|0.7032|0.7055|0.7063|0.7065|PASS",
   "DATA|nose|0.4280|0.4290|0.4414|0.4470|0.4480|PASS",
   "DATA|45 DEG| 44.50| 44.55| 44.64| 45.45| 45.50|PASS",
   "DATA|.25 LGH|0.2400|0.2410|0.2476|0.2590|0.2600|PASS",
   "DATA|OAL|1.5180|1.5185|1.5236|1.5275|1.5280|PASS",
   "END|Mar 21 2017|11:05:43 PM");

$partInspection = new PartInspection();

foreach ($lines as $line)
{
   $partInspection->parse($line);
}

echo $partInspection->toHtml();

$validString = $partInspection->isValid() ? "true" : "false";
echo "isValid: $validString<br>";
*/
?>