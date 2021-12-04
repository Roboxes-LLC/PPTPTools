<?php

require_once '../common/dailySummaryReport.php';

function testReportEntrySorting()
{
   $reportEntries = [
         (object)["runTime" => 8, "efficiency" => 0.50],
         (object)["runTime" => 8, "efficiency" => 0.27],
         (object)["runTime" => 8, "efficiency" => 0.99]
   ];
   
   usort($reportEntries, "ReportEntry::sorter");
   
   echo "Data set 1:<br>";
   var_dump($reportEntries);
   
   $reportEntries = [
         (object)["runTime" => 4, "efficiency" => 0.75],
         (object)["runTime" => 12, "efficiency" => 0.75],
         (object)["runTime" => 10, "efficiency" => 0.75]
   ];
   
   usort($reportEntries, "ReportEntry::sorter");
   
   echo "Data set 2:<br>";
   var_dump($reportEntries);
   
   $reportEntries = [
         (object)["runTime" => 4, "efficiency" => 0.95],
         (object)["runTime" => 8, "efficiency" => 0.75],
         (object)["runTime" => 8, "efficiency" => 0.76]
   ];
   
   usort($reportEntries, "ReportEntry::sorter");
   
   echo "Data set 3:<br>";
   var_dump($reportEntries);
}

testReportEntrySorting();

?>