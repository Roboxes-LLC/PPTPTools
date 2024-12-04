<?php 

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/jobInfo.php';

class SampleWeightEntry
{
   public $jobNumber;
   public $sampleWeight;
   
   public function __construct($jobNumber, $sampleWeight)
   {
      $this->jobNumber = $jobNumber;
      $this->sampleWeight = $sampleWeight;
   }
}

function floatIndex($float)
{
   return (number_format($float, 4));
}

$entries = [];

$result = PPTPDatabase::getInstance()->query("SELECT * from job");

while ($result && ($row = $result->fetch_assoc()))
{
   $jobInfo = new JobInfo();
   $jobInfo->initialize($row);
   
   if ($jobInfo->sampleWeight != 0.0)
   {
      $weightIndex = floatIndex($jobInfo->sampleWeight);
      
      if (!isset($entries[$jobInfo->partNumber]))
      {
         $entries[$jobInfo->partNumber] = [];
         $entries[$jobInfo->partNumber][$weightIndex] = [];
         $entries[$jobInfo->partNumber][$weightIndex][] = $jobInfo->jobNumber;
      }
      else if (!isset($entries[$jobInfo->partNumber][$weightIndex]))
      {
         $entries[$jobInfo->partNumber][$weightIndex] = [];
         $entries[$jobInfo->partNumber][$weightIndex][] = $jobInfo->jobNumber;
      }
      else
      {
         $entries[$jobInfo->partNumber][$weightIndex][] = $jobInfo->jobNumber;
      }
   }
}

foreach ($entries as $partNumber => $entry)
{
   if (count($entry) == 1)
   {
      unset($entries[$partNumber]);
   }
}

$csv = [];
$csv[0] = [];
$header = &$csv[0];
$header[0] = "Part Number";
$header[1] = "Sample Weight";
$header[2] = "Job Counts";
$header[3] = "Sample Weight";
$header[4] = "Job Counts";
$header[5] = "Sample Weight";
$header[6] = "Job Counts";

$rowIndex = 1;
foreach ($entries as $partNumber => $entry)
{
   $csv[$rowIndex] = [];
   
   $row = &$csv[$rowIndex];
   $row[0] = $partNumber;
   
   $colIndex = 1;
   foreach ($entry as $sampleWeight => $jobNumbers)
   {  
      $row[$colIndex] = $sampleWeight;
      $row[$colIndex + 1] = count($jobNumbers);
   
      $colIndex += 2;
   }
   
   $rowIndex++;
}

$file = fopen("sampleWeights.csv", 'w');

if ($file)
{
   foreach ($csv as $row)
   {
      fputcsv($file, $row);
   }
   
   fclose($file);
}

var_dump($csv);
