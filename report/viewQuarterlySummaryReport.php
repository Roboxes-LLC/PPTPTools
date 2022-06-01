<?php

require_once '../common/authentication.php';
require_once '../common/database.php';
require_once '../common/header.php';
require_once '../common/jobInfo.php';
require_once '../common/menu.php';
require_once '../common/newIndicator.php';
require_once '../common/permissions.php';
require_once '../common/quarterlySummaryReport.php';
require_once '../common/roles.php';
require_once '../common/timeCardInfo.php';
require_once '../common/userInfo.php';
require_once '../common/version.php';

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
   $year = 2022;  // TODO: Current year
   
   if (isset($_SESSION["quarterlySummaryReport.filter.year"]))
   {
      $year = intval($_SESSION["quarterlySummaryReport.filter.year"]);
   }
   
   return ($year);
}

function getYearOptions($selectedYear)
{
   $html = "<option style=\"display:none\">";
   
   for ($year = 2020; $year <= 2022; $year++)  // TODO
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
   <link rel="stylesheet" type="text/css" href="../thirdParty/tabulator/css/tabulator.min.css"/>
   
   <link rel="stylesheet" type="text/css" href="../common/theme.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="../common/common.css<?php echo versionQuery();?>"/>
   
   <script src="../thirdParty/tabulator/js/tabulator.min.js"></script>
   <script src="../thirdParty/moment/moment.min.js"></script>
   
   <script src="../common/common.js<?php echo versionQuery();?>"></script>
   <script src="../common/validate.js<?php echo versionQuery();?>"></script>
      
</head>

<body class="flex-vertical flex-top flex-left">

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(Activity::QUARTERLY_REPORT); ?>
      
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
         maxHeight:500,  // set height of table (in CSS or here), this enables the Virtual DOM and improves render speed dramatically (can be any valid css height value)      
         layout:"fitData",
         cellVertAlign:"middle",
         printAsHtml:true,          //enable HTML table printing
         printRowRange:"all",       // print all rows 
         printHeader:"<h1>Totals<h1>",
         ajaxURL:url,
         ajaxParams:params,
         //Define Table Columns
         columns:[
            {title:"Operator",   field:"operator",       hozAlign:"left", headerFilter:true, print:true, frozen:true},
            {title:"Employee #", field:"employeeNumber", hozAlign:"left",                    print:true, frozen:true},
            // Week 1
            {
               title:"Week 1",
               columns:[
                  {title:"Machine Hours", field:"week1.machineHoursMade", hozAlign:"left", print:true},
                  {title:"Ratio",         field:"week1.ratio",            hozAlign:"left", print:true}
               ]
            },
            // Week 2
            {
               title:"Week 2",
               columns:[
                  {title:"Machine Hours", field:"week2.machineHoursMade", hozAlign:"left", print:true},
                  {title:"Ratio",         field:"week2.ratio",            hozAlign:"left", print:true}
               ]
            },            
            // Week 3
            {
               title:"Week 3",
               columns:[
                  {title:"Machine Hours", field:"week3.machineHoursMade", hozAlign:"left", print:true},
                  {title:"Ratio",         field:"week3.ratio",            hozAlign:"left", print:true}
               ]
            },
            // Week 4
            {
               title:"Week 4",
               columns:[
                  {title:"Machine Hours", field:"week4.machineHoursMade", hozAlign:"left", print:true},
                  {title:"Ratio",         field:"week4.ratio",            hozAlign:"left", print:true}
               ]
            },
            // Week 5
            {
               title:"Week 5",
               columns:[
                  {title:"Machine Hours", field:"week5.machineHoursMade", hozAlign:"left", print:true},
                  {title:"Ratio",         field:"week5.ratio",            hozAlign:"left", print:true}
               ]
            },            
            // Week 6
            {
               title:"Week 6",
               columns:[
                  {title:"Machine Hours", field:"week6.machineHoursMade", hozAlign:"left", print:true},
                  {title:"Ratio",         field:"week6.ratio",            hozAlign:"left", print:true}
               ]
            },
            // Week 7
            {
               title:"Week 7",
               columns:[
                  {title:"Machine Hours", field:"week7.machineHoursMade", hozAlign:"left", print:true},
                  {title:"Ratio",         field:"week7.ratio",            hozAlign:"left", print:true}
               ]
            },                        
            // Week 8
            {
               title:"Week 8",
               columns:[
                  {title:"Machine Hours", field:"week8.machineHoursMade", hozAlign:"left", print:true},
                  {title:"Ratio",         field:"week8.ratio",            hozAlign:"left", print:true}
               ]
            },            
            // Week 9
            {
               title:"Week 9",
               columns:[
                  {title:"Machine Hours", field:"week9.machineHoursMade", hozAlign:"left", print:true},
                  {title:"Ratio",         field:"week9.ratio",            hozAlign:"left", print:true}
               ]
            },            
            // Week 10
            {
               title:"Week 10",
               columns:[
                  {title:"Machine Hours", field:"week10.machineHoursMade", hozAlign:"left", print:true},
                  {title:"Ratio",         field:"week10.ratio",            hozAlign:"left", print:true}
               ]
            },            
            // Week 11
            {
               title:"Week 11",
               columns:[
                  {title:"Machine Hours", field:"week11.machineHoursMade", hozAlign:"left", print:true},
                  {title:"Ratio",         field:"week11.ratio",            hozAlign:"left", print:true}
               ]
            },            
            // Week 12
            {
               title:"Week 12",
               columns:[
                  {title:"Machine Hours", field:"week12.machineHoursMade", hozAlign:"left", print:true},
                  {title:"Ratio",         field:"week12.ratio",            hozAlign:"left", print:true}
               ]
            },            
            // Week 13
            {
               title:"Week 13",
               columns:[
                  {title:"Machine Hours", field:"week13.machineHoursMade", hozAlign:"left", print:true},
                  {title:"Ratio",         field:"week13.ratio",            hozAlign:"left", print:true}
               ]
            },
            // Quarter
            {
               title:"Quarter",
               columns:[
                  {title:"Machine Hours", field:"quarter.machineHoursMade", hozAlign:"left", print:true},
                  {title:"Shift Time",    field:"quarter.shiftTime",        hozAlign:"left", print:true},
                  {title:"Ratio",         field:"quarter.ratio",            hozAlign:"left", print:true}
               ]
            }
         ]
      });
      
      // ***********************************************************************
      //                                Shop Summary
      
      params = getTableQueryParams(SHOP_SUMMARY_TABLE);
      
      tables[SHOP_SUMMARY_TABLE] = new Tabulator("#shop-summary-table", {
         maxHeight:500,  // set height of table (in CSS or here), this enables the Virtual DOM and improves render speed dramatically (can be any valid css height value)      
         layout:"fitData",
         cellVertAlign:"middle",
         printAsHtml:true,          //enable HTML table printing
         printRowRange:"all",       // print all rows 
         printHeader:"<h1>Totals<h1>",
         ajaxURL:url,
         ajaxParams:params,
         //Define Table Columns
         columns:[
            {title:"Week",               field:"week",             hozAlign:"left", print:true,
                formatter:function(cell, formatterParams, onRendered){
                   return ("Week " + cell.getValue());
                }
            },
            {title:"Dates",              field:"dates",            hozAlign:"left", print:true},         
            {title:"Machine Hours", field:"machineHoursMade", hozAlign:"left", print:true},
            {title:"Shift Hours",        field:"shiftTime",        hozAlign:"left", print:true},
            {title:"Ratio",              field:"ratio",            hozAlign:"left", print:true}
         ]
      });
      
      // ***********************************************************************
      //                                Bonus
      
      /*
      params = getTableQueryParams(BONUS_TABLE);
      
      var bonusFormatter = function(cell, formatterParams, onRendered)
      {
         var tier = parseInt(cell.getRow().getData().tier);

         if (tier == formatterParams.tier)
         {
            cell.getElement().classList.add("bonus-earned");
         }
         
         return ("$" + cell.getValue().toFixed(2));
      } 
      
      tables[BONUS_TABLE] = new Tabulator("#bonus-table", {
         maxHeight:500,  // set height of table (in CSS or here), this enables the Virtual DOM and improves render speed dramatically (can be any valid css height value)      
         layout:"fitData",
         cellVertAlign:"middle",
         printAsHtml:true,          //enable HTML table printing
         printRowRange:"all",       // print all rows 
         printHeader:"<h1>Totals<h1>",
         ajaxURL:url,
         ajaxParams:params,
         //Define Table Columns
         columns:[
            {title:"Operator",   field:"operator",          hozAlign:"left", headerFilter:true, print:true},
            {title:"Employee #", field:"employeeNumber",    hozAlign:"left",                    print:true},         
            {title:"Hours",      field:"runTime",      hozAlign:"left",                    print:true},
            {title:"Efficiency", field:"efficiency", hozAlign:"left",                    print:true,
               formatter:function(cell, formatterParams, onRendered){
                  return (cell.getValue() + "%");
               }
            },
            {title:"Machine Hours", field:"machineHoursMade", hozAlign:"left", print:true},
            {title:"PC/G", field:"pcOverG", hozAlign:"left", print:true},
            {
               title:"75%",
               columns:[
                  {title:"$0.25", field:"tier1", hozAlign:"left", print:true, formatter:bonusFormatter, formatterParams:{tier:1}}
               ]
            },
            {
               title:"80%",
               columns:[
                  {title:"$0.50", field:"tier2", hozAlign:"left", print:true, formatter:bonusFormatter, formatterParams:{tier:2}}
               ]
            },
            {
               title:"85%",
               columns:[
                  {title:"$1.00", field:"tier3", hozAlign:"left", print:true, formatter:bonusFormatter, formatterParams:{tier:3}}
               ]
            },
            {
               title:"90%",
               columns:[
                  {title:"$1.50", field:"tier4", hozAlign:"left", print:true, formatter:bonusFormatter, formatterParams:{tier:4}}
               ]
            },
            {
               title:"95%",
               columns:[
                  {title:"$2.00", field:"tier5", hozAlign:"left", print:true, formatter:bonusFormatter, formatterParams:{tier:5}}
               ]
            },
            {
               title:"100%",
               columns:[
                  {title:"$3.00", field:"tier6", hozAlign:"left", print:true, formatter:bonusFormatter, formatterParams:{tier:6}}
               ]
            },
            {
               title:"3+ Machine",
               columns:[
                  {title:"$4.00", field:"additionalMachineBonus", hozAlign:"left", print:true, 
                     formatter:function(cell, formatterParams, onRendered) {
                        if (cell.getRow().getData().additionalMachineBonusEarned)
                        {
                           cell.getElement().classList.add("bonus-earned");
                        }
         
                        return ("$" + cell.getValue().toFixed(2));
                     }
                  }
               ]
            },
         ]
      });
      */      

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
      document.getElementById("print-operator-summary-link").onclick = function(){tables[OPERATOR_SUMMARY_TABLE].print(false, true);};
      document.getElementById("download-shop-summary-link").onclick = function(){tables[OPERATOR_SUMMARY_TABLE].download("csv", "<?php echo getReportFilename(QuarterlySummaryReportTable::SHOP_SUMMARY) ?>", {delimiter:","})};
      document.getElementById("print-shop-summary-link").onclick = function(){tables[SHOP_SUMMARY_TABLE].print(false, true);};

      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
      document.getElementById("menu-button").onclick = function(){document.getElementById("menu").classList.toggle('shown');};
      
      updateReportDates();
   </script>
   
</body>

</html>