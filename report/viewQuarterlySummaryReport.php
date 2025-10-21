<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/jobInfo.php';
require_once ROOT.'/common/newIndicator.php';
require_once ROOT.'/common/permissions.php';
require_once ROOT.'/common/quarterlySummaryReport.php';
require_once ROOT.'/common/timeCardInfo.php';
require_once ROOT.'/common/userInfo.php';
require_once ROOT.'/common/version.php';
require_once ROOT.'/core/common/role.php';

function getCurrentYear()
{
   return (Time::now("Y"));
}

function getQuarter()
{
   $quarter = Quarter::FIRST;
   
   if (isset($_SESSION["quarterlySummaryReport.filter.quarter"]))
   {
      $quarter = intval($_SESSION["quarterlySummaryReport.filter.quarter"]);
   }
   
   return ($quarter);
}

function getYear()
{
   $year = getCurrentYear();
   
   if (isset($_SESSION["quarterlySummaryReport.filter.year"]))
   {
      $year = intval($_SESSION["quarterlySummaryReport.filter.year"]);
   }
   
   return ($year);
}

function getYearOptions($selectedYear)
{
   $html = "<option style=\"display:none\">";
   
   for ($year = 2020; $year <= getCurrentYear(); $year++)
   {
      $selected = ($year == $selectedYear) ? "selected" : "";
      
      $html .= "<option value=\"$year\" $selected>$year</option>";
   }
   
   return ($html);
}

function getUseMaintenanceLogEntries()
{
   $useMaintenanceLogEntries = false;
   
   if (isset($_SESSION["quarterlySummaryReport.filter.useMaintenanceLogEntries"]))
   {
      $useMaintenanceLogEntries = filter_var($_SESSION["quarterlySummaryReport.filter.useMaintenanceLogEntries"], FILTER_VALIDATE_BOOLEAN);
   }
   
   return ($useMaintenanceLogEntries);
}

function getReportFilename($tableId)
{
   $quarter = getQuarter();
   $year = getYear();
   
   $tableLabel = ($tableId == QuarterlySummaryReportTable::OPERATOR_SUMMARY) ? "OperatorSummary" : "ShopSummary";
   $quarterLabel = Quarter::getLabel($quarter);
   
   $filename = "QuarterlySummaryReport_{$tableLabel}_{$quarterLabel}_$year.csv";
   
   return ($filename);
}

function getTableHeader()
{
   $quarter = getQuarter();
   $year = getYear();
   $dates = Quarter::getDates($year, $quarter);
   
   $dt = new DateTime($dates[0]->start);
   $startDate = $dt->format("m/d");
   $dt = new DateTime($dates[Quarter::WEEKS_IN_QUARTER - 1]->start);
   $endDate = $dt->format("m/d");

   // Ex: "Quarter 1, 2021 (1/4 - 3/29)
   $header =  Quarter::getLabel($quarter) . ", " . $year . " (" . $startDate . " - " . $endDate . ")";
   
   return ($header);
}

// ********************************** BEGIN ************************************

Time::init();

session_start();

if (!Authentication::isAuthenticated())
{
   header('Location: ../login.php');
   exit;
}

// Post/Redirect/Get idiom.
// getFilter() stores all $_POST data in the $_SESSION variable.
// header() redirects to this page, but with a GET request.
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
   // Redirect to this page.
   header("Location: " . $_SERVER['REQUEST_URI']);
   exit();
}
?>

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">

   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons"/>
   <link rel="stylesheet" type="text/css" href="../thirdParty/tabulator/css/tabulator.min.css<?php echo versionQuery();?>"/>
   
   <link rel="stylesheet" type="text/css" href="../common/theme.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="../common/common.css<?php echo versionQuery();?>"/>
   
   <script src="/thirdParty/tabulator/js/tabulator.min.js<?php echo versionQuery();?>"></script>
   <script src="/thirdParty/luxon/luxon.min.js<?php echo versionQuery();?>"></script>
   
   <script src="/common/common.js<?php echo versionQuery();?>"></script>
   <script src="/common/validate.js<?php echo versionQuery();?>"></script>
   <script src="/script/common/common.js<?php echo versionQuery();?>"></script>
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script>
      
</head>

<body class="flex-vertical flex-top flex-left">

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading">Quarterly Summary Report</div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div id="description" class="description">Something something something ...</div>
         
         <br>
         
         <div class="flex-horizontal flex-v-center flex-left">
            <div style="white-space: nowrap">Quarter</div>
            &nbsp;
            <select id="quarter-filter"><?php echo Quarter::getOptions(getQuarter()); ?></select>
            &nbsp;&nbsp;
            <div style="white-space: nowrap">Year</div>
            &nbsp;
            <select id="year-filter"><?php echo getYearOptions(getYear()); ?></select>
            &nbsp;&nbsp;
            <input id="maintenance-log-filter" type="checkbox" <?php echo getUseMaintenanceLogEntries() ? "checked" : "" ?>/>Include maintenace log
         </div>
         
         <br>
         
         <div id="report-table-header" class="table-header">Operator Summary</div>
         
         <br>
         
         <div id="week-number-div" class="date-range-header"></div>
       
         <div id="operator-summary-table"></div>
         
         <br>
         
         <div id="download-operator-summary-link" class="download-link">Download CSV file</div>
         
         <div id="print-operator-summary-link" class="download-link">Print</div>         
                  
         <br>
         
         <div id="report-table-header" class="table-header">Shop Summary</div>
         
         <br>
         
         <div id="week-number-div" class="date-range-header"></div>
       
         <div id="shop-summary-table"></div>
         
         <br>
        
         <div id="download-shop-summary-link" class="download-link">Download CSV file</div>
         
         <div id="print-shop-summary-link" class="download-link">Print</div>         
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::QUARTERLY_REPORT ?>);   
   
      const OPERATOR_SUMMARY_TABLE = <?php echo QuarterlySummaryReportTable::OPERATOR_SUMMARY; ?>;
      const SHOP_SUMMARY_TABLE = <?php echo QuarterlySummaryReportTable::SHOP_SUMMARY; ?>;
   
      preserveSession();

      function getTableQuery()
      {
         return ("<?php echo $ROOT ?>/api/quarterlySummaryReportData/");
      }

      function getTableQueryParams(table)
      {
         var params = new Object();
         params.quarter = document.getElementById("quarter-filter").value;
         params.year = document.getElementById("year-filter").value;
         params.useMaintenanceLogEntries = document.getElementById("maintenance-log-filter").checked;
         params.table = table;

         return (params);
      }
      
      // Array of tables.
      var tables = [];

      var url = getTableQuery();
      
      // ***********************************************************************
      //                             Operator Summary
      
      var params = getTableQueryParams(OPERATOR_SUMMARY_TABLE);
      
      tables[OPERATOR_SUMMARY_TABLE] = new Tabulator("#operator-summary-table", {
         // Data
         ajaxURL:url,
         ajaxParams:params,
         // Layout
         maxHeight:500,
         layout:"fitData",
         columnDefaults:{
            hozAlign:"left", 
            vertAlign:"middle"
         },
         persistence:true,
         // Printing
         printAsHtml:true,
         printRowRange:"all", 
         printHeader:"<h1>Totals<h1>",
         // Columns
         columns:[
            {title:"Operator",   field:"operator",       headerFilter:true, frozen:true},
            {title:"Employee #", field:"employeeNumber", frozen:true},
            // Week 1
            {
               title:"Week 1",
               columns:[
                  {title:"Machine Hours", field:"week1.machineHoursMade"},
                  {title:"Ratio",         field:"week1.ratio"}
               ]
            },
            // Week 2
            {
               title:"Week 2",
               columns:[
                  {title:"Machine Hours", field:"week2.machineHoursMade"},
                  {title:"Ratio",         field:"week2.ratio"}
               ]
            },            
            // Week 3
            {
               title:"Week 3",
               columns:[
                  {title:"Machine Hours", field:"week3.machineHoursMade"},
                  {title:"Ratio",         field:"week3.ratio"}
               ]
            },
            // Week 4
            {
               title:"Week 4",
               columns:[
                  {title:"Machine Hours", field:"week4.machineHoursMade"},
                  {title:"Ratio",         field:"week4.ratio"}
               ]
            },
            // Week 5
            {
               title:"Week 5",
               columns:[
                  {title:"Machine Hours", field:"week5.machineHoursMade"},
                  {title:"Ratio",         field:"week5.ratio"}
               ]
            },            
            // Week 6
            {
               title:"Week 6",
               columns:[
                  {title:"Machine Hours", field:"week6.machineHoursMade"},
                  {title:"Ratio",         field:"week6.ratio"}
               ]
            },
            // Week 7
            {
               title:"Week 7",
               columns:[
                  {title:"Machine Hours", field:"week7.machineHoursMade"},
                  {title:"Ratio",         field:"week7.ratio"}
               ]
            },                        
            // Week 8
            {
               title:"Week 8",
               columns:[
                  {title:"Machine Hours", field:"week8.machineHoursMade"},
                  {title:"Ratio",         field:"week8.ratio"}
               ]
            },            
            // Week 9
            {
               title:"Week 9",
               columns:[
                  {title:"Machine Hours", field:"week9.machineHoursMade"},
                  {title:"Ratio",         field:"week9.ratio"}
               ]
            },            
            // Week 10
            {
               title:"Week 10",
               columns:[
                  {title:"Machine Hours", field:"week10.machineHoursMade"},
                  {title:"Ratio",         field:"week10.ratio"}
               ]
            },            
            // Week 11
            {
               title:"Week 11",
               columns:[
                  {title:"Machine Hours", field:"week11.machineHoursMade"},
                  {title:"Ratio",         field:"week11.ratio"}
               ]
            },            
            // Week 12
            {
               title:"Week 12",
               columns:[
                  {title:"Machine Hours", field:"week12.machineHoursMade"},
                  {title:"Ratio",         field:"week12.ratio"}
               ]
            },            
            // Week 13
            {
               title:"Week 13",
               columns:[
                  {title:"Machine Hours", field:"week13.machineHoursMade"},
                  {title:"Ratio",         field:"week13.ratio"}
               ]
            },
            // Quarter
            {
               title:"Quarter",
               columns:[
                  {title:"Machine Hours", field:"quarter.machineHoursMade"},
                  {title:"Shift Time",    field:"quarter.shiftTime"},
                  {title:"Ratio",         field:"quarter.ratio"}
               ]
            }
         ]
      });
      
      // ***********************************************************************
      //                                Shop Summary
      
      params = getTableQueryParams(SHOP_SUMMARY_TABLE);
      
      tables[SHOP_SUMMARY_TABLE] = new Tabulator("#shop-summary-table", {
         // Data
         ajaxURL:url,
         ajaxParams:params,
         // Layout
         maxHeight:500,
         layout:"fitData",
         columnDefaults:{
            hozAlign:"left", 
            vertAlign:"middle"
         },
         persistence:true,
         // Printing
         printAsHtml:true,
         printRowRange:"all", 
         printHeader:"<h1>Totals<h1>",
         // Columns
         columns:[
            {title:"Week",               field:"week",
                formatter:function(cell, formatterParams, onRendered){
                   return ("Week " + cell.getValue());
                }
            },
            {title:"Dates",              field:"dates"},         
            {title:"Machine Hours", field:"machineHoursMade"},
            {title:"Shift Hours",        field:"shiftTime"},
            {title:"Ratio",              field:"ratio"}
         ]
      });
      
      // ***********************************************************************  

      function updateFilter(event)
      {
         if (document.readyState === "complete")
         {
            var filterId = event.srcElement.id;
   
            if ((filterId == "quarter-filter") ||
                (filterId == "year-filter") ||
                (filterId == "maintenance-log-filter"))
            {
               tables[OPERATOR_SUMMARY_TABLE].setData(getTableQuery(), getTableQueryParams(OPERATOR_SUMMARY_TABLE))
               .then(function(){
                  // Run code after table has been successfuly updated
               })
               .catch(function(error){
                  // Handle error loading data
               });
               
               tables[SHOP_SUMMARY_TABLE].setData(getTableQuery(), getTableQueryParams(SHOP_SUMMARY_TABLE))
               .then(function(){
                  // Run code after table has been successfuly updated
               })
               .catch(function(error){
                  // Handle error loading data
               });

               /*
               tables[BONUS_TABLE].setData(getTableQuery(), getTableQueryParams(BONUS_TABLE))
               .then(function(){
                  // Run code after table has been successfuly updated
               })
               .catch(function(error){
                  // Handle error loading data
               });
               */
               
               updateReportDates();

               if (filterId == "quarter-filter")
               {
                  setSession("quarterlySummaryReport.filter.quarter", document.getElementById("quarter-filter").value);
               }
               
               if (filterId == "year-filter")
               {
                  setSession("quarterlySummaryReport.filter.year", document.getElementById("year-filter").value);
               }
               
               if (filterId == "maintenance-log-filter")
               {
                  setSession("quarterlySummaryReport.filter.useMaintenanceLogEntries", document.getElementById("maintenance-log-filter").checked);
               }
            }
         }
      }
      
      function updateReportDates()
      {
         var quarter = document.getElementById("quarter-filter").value;
         var year = document.getElementById("year-filter").value;
      
         // AJAX call to retrieve report dates.
         requestUrl = "../api/quarterlySummaryReportDates/?quarter=" + quarter + "&year=" + year;
         console.log(requestUrl);
         
         var xhttp = new XMLHttpRequest();
         xhttp.onreadystatechange = function()
         {
            if (this.readyState == 4 && this.status == 200)
            {
               try
               {            
                  var json = JSON.parse(this.responseText);
                  
                  if (json.success == true)
                  {
                     // Update table headers.
                     var headers = document.getElementsByClassName("date-range-header");
                     for (var header of headers)
                     {
                        header.innerHTML = "Quarter " + quarter + ", " + year + " (Week " + json.dates[0].week + " - " + json.dates[12].week + ")";
                     }
                     
                     //
                     // Update "week" columns in operator summary report.
                     //
                     
                     var columns = tables[OPERATOR_SUMMARY_TABLE].getColumns(true);
                     
                     for (var week = 1; week <= 13; week++)
                     {
                        var index = (week - 1);
                        var column = (2 + (week - 1));
                        
                        columns[column]._column.titleElement.innerHTML = "Week " + json.dates[index].week + " (" + json.dates[index].start + " - " + json.dates[index].end + ")";
                     }
                     
                     // Update "quarter" column in operator summary report.
                     columns[15]._column.titleElement.innerHTML = "Quarter " + quarter + ", " + year;
                  }
                  else
                  {
                     console.log("API call to retrieve report dates.");
                  }
               }
               catch (exception)
               {
                  console.log("JSON syntax error");
                  console.log(this.responseText);
               }
            }
         };
         xhttp.open("GET", requestUrl, true);
         xhttp.send();
      }

      // Setup event handling on all DOM elements.
      window.addEventListener('resize', function() { tables[OPERATOR_SUMMARY_TABLE].redraw(); tables[SHOP_SUMMARY_TABLE].redraw(); /*tables[BONUS_TABLE].redraw();*/ });
      document.getElementById("quarter-filter").addEventListener("change", updateFilter);      
      document.getElementById("year-filter").addEventListener("change", updateFilter);
      document.getElementById("maintenance-log-filter").addEventListener("change", updateFilter);
      document.getElementById("download-operator-summary-link").onclick = function(){tables[OPERATOR_SUMMARY_TABLE].download("csv", "<?php echo getReportFilename(QuarterlySummaryReportTable::OPERATOR_SUMMARY) ?>", {delimiter:","})};
      document.getElementById("print-operator-summary-link").onclick = function(){tables[OPERATOR_SUMMARY_TABLE].print("active", true);};
      document.getElementById("download-shop-summary-link").onclick = function(){tables[SHOP_SUMMARY_TABLE].download("csv", "<?php echo getReportFilename(QuarterlySummaryReportTable::SHOP_SUMMARY) ?>", {delimiter:","})};
      document.getElementById("print-shop-summary-link").onclick = function(){tables[SHOP_SUMMARY_TABLE].print("active", true);};

      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
      
      updateReportDates();
   </script>
   
</body>

</html>