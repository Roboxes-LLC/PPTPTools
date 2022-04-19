<?php

abstract class Quarter
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const QUARTER_1 = Quarter::FIRST;
   const QUARTER_2 = 2;
   const QUARTER_3 = 3;
   const QUARTER_4 = 4;
   const LAST = 5;
   const COUNT = (Quarter::LAST - Quarter::FIRST);
   
   const WEEKS_IN_QUARTER = 13;
   
   public static $values = array(Quarter::QUARTER_1, Quarter::QUARTER_2, Quarter::QUARTER_3, Quarter::QUARTER_4);
   
   public static function getWeekRange($quarter)
   {
      $range = null;
      
      if (($quarter >= Quarter::FIRST) &&
          ($quarter < Quarter::LAST))
      {
         $range = new stdClass();
         
         $range->start = ((($quarter - 1) * Quarter::WEEKS_IN_QUARTER) + 1);
         $range->end = ($range->start + Quarter::WEEKS_IN_QUARTER - 1);
      }
      
      return ($range);
   }
   
   public static function getLabel($workDay)
   {
      $labels = array("---",
                      "Quarter 1",
                      "Quarter 2",
                      "Quarter 3",
                      "Quarter 4");
      
      return ($labels[$workDay]);
   }
   
   // Returns an array of 13 date ranges ($dates[week - 1]->start, $dates[week]->end) coresponding to the start/end dates for each week 
   // in the specified quarter/year.
   public static function getDates($year, $quarter)
   {
      $dates = array();
      
      $weekRange = Quarter::getWeekRange($quarter);
      
      if ($weekRange)
      {
         for ($week = $weekRange->start; $week <= $weekRange->end; $week++)
         {
            $dates[] = Quarter::getDatesForWeek($year, $week);
         }
      }
      
      return ($dates);
   }
   
   public static function getOptions($selectedQuarter)
   {
      $html = "<option style=\"display:none\">";
      
      foreach (Quarter::$values as $quarter)
      {
         $label = Quarter::getLabel($quarter);
         $selected = ($quarter == $selectedQuarter) ? "selected" : "";
         
         $html .= "<option value=\"$quarter\" $selected>$label</option>";
      }
      
      return ($html);
   }
   
   private static function getDatesForWeek($year, $week)
   {
      // TODO: ISO weeks seem to go M - Su.  Reporting in PPTP has, so far, been Su - Sa.
      
      $range = new stdClass();
      
      $dt = new DateTime();
      $range->week = $week;
      $range->start = $dt->setISODate($year, $week)->format('Y-m-d');
      $range->end = $dt->modify('+6 days')->format('Y-m-d');
      
      return ($range);
   }
}

abstract class QuarterlySummaryReportTable
{
   const FIRST = 0;
   const OPERATOR_SUMMARY = QuarterlySummaryReportTable::FIRST;
   const SHOP_SUMMARY = 1;
   const LAST = 2;
   const COUNT = QuarterlySummaryReportTable::LAST - QuarterlySummaryReportTable::FIRST;
}

class QuarterlyOperatorSummary
{
   public $machineHoursMade;
   public $shiftTime;
   public $ratio;
   
   public function __construct($employeeNumber, $weeklySummaryReports)
   {
      $this->machineHoursMade = QuarterlyOperatorSummary::calculateMachineHoursMade($employeeNumber, $weeklySummaryReports);
      
      $this->shiftTime = QuarterlyOperatorSummary::calculateShiftTime($employeeNumber, $weeklySummaryReports);
      
      $this->ratio = Calculations::calculateRatio($this->machineHoursMade, $this->shiftTime);
   }
   
   private static function calculateMachineHoursMade($employeeNumber, $weeklySummaryReports)
   {
      $machineHoursMade = 0;
      
      foreach ($weeklySummaryReports as $weeklySummaryReport)
      {
         if (isset($weeklySummaryReport->operatorSummaries[$employeeNumber]))
         {
            $operatorSummary = $weeklySummaryReport->operatorSummaries[$employeeNumber];
            $machineHoursMade += $operatorSummary->machineHoursMade;
         }
      }
      
      return ($machineHoursMade);
   }
   
   private static function calculateShiftTime($employeeNumber, $weeklySummaryReports)
   {
      $shiftTime = 0;
      
      foreach ($weeklySummaryReports as $weeklySummaryReport)
      {
         if (isset($weeklySummaryReport->operatorSummaries[$employeeNumber]))
         {
            $operatorSummary = $weeklySummaryReport->operatorSummaries[$employeeNumber];
            
            $shiftTime += $operatorSummary->shiftTime;
         }
      }
      
      return ($shiftTime);
   }
}

class QuarterlySummaryReport
{
   public $quarter;
   public $year;
   public $weeklySummaryReports;
   public $operatorSummaries;
   
   public function __construct()
   {
      $this->quarter = Quarter::UNKNOWN;
      $this->year = 0;
      $this->weeklySummaryReports = array();
      $this->operatorSummaries = array();
   }
   
   public static function load($year, $quarter, $useMaintenanceLogEntries)
   {
      $quarterlySummaryReport = new QuarterlySummaryReport();
      
      $quarterlySummaryReport->year = $year;
      $quarterlySummaryReport->quarter = $quarter;
      
      $dates = Quarter::getDates($year, $quarter);
      
      foreach ($dates as $dateRange)
      {
         $quarterlySummaryReport->weeklySummaryReports[] = WeeklySummaryReport::load($dateRange->start, $useMaintenanceLogEntries);
      }
      
      // Compile operator summaries.
      $quarterlySummaryReport->compileOperatorSummaries();
      
      return ($quarterlySummaryReport);
   }
   
   public function getEmployeeNumbers()
   {
      $employeeNumbers = array();
      
      for ($weekIndex = 0; $weekIndex < Quarter::WEEKS_IN_QUARTER; $weekIndex++)
      {
         $employeeNumbers = array_merge($employeeNumbers, array_diff($this->weeklySummaryReports[$weekIndex]->getEmployeeNumbers(), $employeeNumbers));
      }
      
      return ($employeeNumbers);
   }
   
   public function getReportData($table)
   {
      $reportData = array();
      
      switch ($table)
      {
         case WeeklySummaryReportTable::OPERATOR_SUMMARY:
         {
            $reportData = $this->getOperatorSummaryData();
            break;
         }
            
         case WeeklySummaryReportTable::SHOP_SUMMARY:
         {
            $reportData = $this->getShopSummaryData();
            break;
         }
          
         /*
         case WeeklySummaryReportTable::BONUS:
         {
            $reportData = $this->getBonusData();
            break;
         }
         */
            
         default:
         {
            break;
         }
      }
      
      return ($reportData);
   }
   
   private function compileOperatorSummaries()
   {
      $employeeNumbers = $this->getEmployeeNumbers();
      
      foreach ($employeeNumbers as $employeeNumber)
      {
         $this->operatorSummaries[$employeeNumber] = new QuarterlyOperatorSummary($employeeNumber, $this->weeklySummaryReports);
      }
   }
   
   private function getOperatorSummaryData()
   {
      $reportData = array();
      
      $employeeNumbers = $this->getEmployeeNumbers();
      
      foreach ($employeeNumbers as $employeeNumber)
      {
         $userInfo = UserInfo::load($employeeNumber);
         
         $row = new stdClass();
         
         $row->operator = $userInfo ? $userInfo->getFullName() : "unknown";
         $row->employeeNumber = $employeeNumber;
         $row->week1 = $this->getWeeklyStats(0, $employeeNumber);
         $row->week2 = $this->getWeeklyStats(1, $employeeNumber);
         $row->week3 = $this->getWeeklyStats(2, $employeeNumber);
         $row->week4 = $this->getWeeklyStats(3, $employeeNumber);
         $row->week5 = $this->getWeeklyStats(4, $employeeNumber);
         $row->week6 = $this->getWeeklyStats(5, $employeeNumber);
         $row->week7 = $this->getWeeklyStats(6, $employeeNumber);
         $row->week8 = $this->getWeeklyStats(7, $employeeNumber);
         $row->week9 = $this->getWeeklyStats(8, $employeeNumber);
         $row->week10 = $this->getWeeklyStats(9, $employeeNumber);
         $row->week11 = $this->getWeeklyStats(10, $employeeNumber);
         $row->week12 = $this->getWeeklyStats(11, $employeeNumber);
         $row->week13 = $this->getWeeklyStats(12, $employeeNumber);
         $row->quarter = $this->getQuarterlyStats($employeeNumber);
         
         $reportData[] = $row;
      }
      
      return ($reportData);
   }
   
   private function getShopSummaryData()
   {
      $reportData = array();
      
      for ($weekIndex = 0; $weekIndex < Quarter::WEEKS_IN_QUARTER; $weekIndex++)
      {
         $reportData[] = $this->getWeeklyShopStats($weekIndex);
      }
      
      return ($reportData);
   }

   /*
   private function getBonusData()
   {
      $reportData = array();
      
      $employeeNumbers = $this->getEmployeeNumbers();
      
      foreach ($employeeNumbers as $employeeNumber)
      {
         $userInfo = UserInfo::load($employeeNumber);
         
         $row = new stdClass();
         
         $row->operator = $userInfo->getFullName();
         $row->employeeNumber = $userInfo->employeeNumber;
         $row->runTime = $this->operatorSummaries[$employeeNumber]->runTime;
         $row->efficiency = round(($this->operatorSummaries[$employeeNumber]->efficiency * 100), 2);
         $row->machineHoursMade = round($this->operatorSummaries[$employeeNumber]->machineHoursMade, 2);
         $row->pcOverG = round($this->operatorSummaries[$employeeNumber]->pcOverG, 2);
         $row->tier = Bonus::getTier($this->operatorSummaries[$employeeNumber]->efficiency);
         $row->tier1 = Bonus::calculateBonus(Bonus::TIER1, $row->runTime);
         $row->tier2 = Bonus::calculateBonus(Bonus::TIER2, $row->runTime);
         $row->tier3 = Bonus::calculateBonus(Bonus::TIER3, $row->runTime);
         $row->tier4 = Bonus::calculateBonus(Bonus::TIER4, $row->runTime);
         $row->tier5 = Bonus::calculateBonus(Bonus::TIER5, $row->runTime);
         $row->tier6 = Bonus::calculateBonus(Bonus::TIER6, $row->runTime);
         $row->additionalMachineBonus = Bonus::calculateAdditionalMachineBonus($this->operatorSummaries[$employeeNumber]->pcOverG);
         $row->additionalMachineBonusEarned = ($row->additionalMachineBonus > 0);
         
         $reportData[] = $row;
      }
      
      return ($reportData);
   }
   */
   
   private function getWeeklyStats($weekIndex, $employeeNumber)
   {
      $stats = new stdClass();
      
      if (isset($this->weeklySummaryReports[$weekIndex]->operatorSummaries[$employeeNumber]))
      {
         $operatorSummary = $this->weeklySummaryReports[$weekIndex]->operatorSummaries[$employeeNumber];
         
         $stats->machineHoursMade = round($operatorSummary->machineHoursMade, 2);
         $stats->ratio            = round($operatorSummary->ratio, 2);
      }
      else
      {
         $stats->machineHoursMade = 0;
         $stats->ratio            = 0;
      }
      
      return ($stats);
   }
   
   private function getQuarterlyStats($employeeNumber)
   {
      $stats = new stdClass();
      
      if (isset($this->operatorSummaries[$employeeNumber]))
      {
         $operatorSummary = $this->operatorSummaries[$employeeNumber];
         
         $stats->machineHoursMade = round($operatorSummary->machineHoursMade, 2);
         $stats->shiftTime        = round($operatorSummary->shiftTime, 2);
         $stats->ratio            = round($operatorSummary->ratio, 2);
      }
      else
      {
         $stats->machineHoursMade = 0;
         $stats->shiftTime        = 0;
         $stats->ratio            = 0;
      }
      
      return ($stats);
   }
   
   private function getWeeklyShopStats($weekIndex)
   {
      $stats = new stdClass();
      
      $weekRange = Quarter::getWeekRange($this->quarter);
      $dates = Quarter::getDates($this->year, $this->quarter);
      
      $stats->week = $weekRange->start + $weekIndex;
      
      $dateTime = new DateTime($dates[$weekIndex]->start);
      $startDate = $dateTime->format("m/d");
      $dateTime = new DateTime($dates[$weekIndex]->end);
      $endDate = $dateTime->format("m/d");
      $stats->dates = $startDate . " - " . $endDate;
      
      $stats->machineHoursMade = 0;
      $stats->shiftTime = 0;
      $stats->ratio = 0;
      
      if (isset($this->weeklySummaryReports[$weekIndex]))
      {
         foreach ($this->weeklySummaryReports[$weekIndex]->operatorSummaries as $operatorSummary)
         {
            $stats->machineHoursMade += $operatorSummary->machineHoursMade;
            $stats->shiftTime        += $operatorSummary->shiftTime;
         }

         $stats->ratio = Calculations::calculateRatio($stats->machineHoursMade, $stats->shiftTime);
         
         // Round for display.
         $stats->machineHoursMade = round($stats->machineHoursMade, 2);
         $stats->shiftTime = round($stats->shiftTime, 2);
         $stats->ratio = round($stats->ratio, 2);
      }
      
      return ($stats);
   }
}

/*
$dates = Quarter::getDates(2021, Quarter::QUARTER_1);
var_dump($dates);

echo "<select>" . Quarter::getOptions(Quarter::QUARTER_2) . "</select>";
*/

?>