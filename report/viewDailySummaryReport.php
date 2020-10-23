<?php

require_once '../common/authentication.php';
require_once '../common/database.php';
require_once '../common/dailySummaryReport.php';
require_once '../common/header.php';
require_once '../common/jobInfo.php';
require_once '../common/menu.php';
require_once '../common/newIndicator.php';
require_once '../common/permissions.php';
require_once '../common/roles.php';
require_once '../common/timeCardInfo.php';
require_once '../common/userInfo.php';

function getMfgDate()
{
   $mfgDate = Time::now("Y-m-d");
   
   if (isset($_SESSION["dailySummaryReport.filter.mfgDate"]))
   {
      $mfgDate = $_SESSION["dailySummaryReport.filter.mfgDate"];
   }
   
   return ($mfgDate);
}

function getFilterMfgDate()
{
   $mfgDate = getMfgDate();

   // Convert to Javascript date format.
   $dateTime = new DateTime($mfgDate, new DateTimeZone('America/New_York'));  // TODO: Replace
   $mfgDate = $dateTime->format(Time::$javascriptDateFormat);
   
   return ($mfgDate);
}

function getReportFilename()
{
   $mfgDate = getFilterMfgDate();
   
   $filename = "DailySummaryReport_" . $mfgDate . ".csv";
   
   return ($filename);
}

function getTableHeader()
{
   $mfgDate = getFilterMfgDate();
   
   $dateTime = new DateTime($mfgDate, new DateTimeZone('America/New_York'));
   
   $header =  "Week " . $dateTime->format("W") . ": " . $dateTime->format("l");
   
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
   
   <link rel="stylesheet" type="text/css" href="../common/theme.css"/>
   <link rel="stylesheet" type="text/css" href="../common/common.css"/>
   
   <script src="../thirdParty/tabulator/js/tabulator.min.js"></script>
   <script src="../thirdParty/moment/moment.min.js"></script>
   
   <script src="../common/common.js"></script>
   <script src="../common/validate.js"></script>
      
</head>

<body class="flex-vertical flex-top flex-left">

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left" style="width: auto;">
   
      <?php Menu::render(Activity::REPORT); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading">Daily Summary Report</div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div id="description" class="description">Something something something ...</div>
         
         <br>
         
         <div class="flex-horizontal flex-v-center flex-left">
            <div style="white-space: nowrap">Manufacture date</div>
            &nbsp;
            <input id="mfg-date-filter" type="date" value="<?php echo getFilterMfgDate()?>">
            &nbsp;&nbsp;
            <button id="today-button" class="small-button">Today</button>
            &nbsp;&nbsp;
            <button id="yesterday-button" class="small-button">Yesterday</button>
         </div>
         
         <br>
         
         <div id="report-table-header" class="table-header"></div>
         
         <br>
        
         <div id="report-table"></div>
         
         <br>
         
         <div class="table-header">Operator Summary</div>
         
         <br>
        
         <div id="operator-summary-table"></div>
         
         <br>
         
         <div class="table-header">Shop Summary</div>
         
         <br>
        
         <div id="shop-summary-table"></div>
         
         <br>
        
         <div id="download-link" class="download-link">Download CSV file</div>
         
         <div id="print-link" class="download-link">Print</div>         
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>
   
      const DAILY_SUMMARY_TABLE = <?php echo DailySummaryReportTable::DAILY_SUMMARY; ?>;
      const OPERATOR_SUMMARY_TABLE = <?php echo DailySummaryReportTable::OPERATOR_SUMMARY; ?>;
      const SHOP_SUMMARY_TABLE = <?php echo DailySummaryReportTable::SHOP_SUMMARY; ?>;   
   
      preserveSession();

      function getTableQuery()
      {
         return ("<?php echo $ROOT ?>/api/dailySummaryReportData/");
      }

      function getTableQueryParams(table)
      {
         
         var params = new Object();
         params.mfgDate =  document.getElementById("mfg-date-filter").value;
         params.table = table;

         return (params);
      }
      
      // Array of tables.
      var tables = [];

      var url = getTableQuery();
      var params = getTableQueryParams(DAILY_SUMMARY_TABLE);
      
      tables[DAILY_SUMMARY_TABLE] = new Tabulator("#report-table", {
         //height:500,            // set height of table (in CSS or here), this enables the Virtual DOM and improves render speed dramatically (can be any valid css height value)
         layout:"fitData",
         cellVertAlign:"middle",
         printAsHtml:true,          //enable HTML table printing
         printRowRange:"all",       // print all rows 
         printHeader:"<h1>Daily Summary Report<h1>",
         printFooter:"<h2>TODO: Date range<h2>",
         groupBy:"operator",
         ajaxURL:url,
         ajaxParams:params,
         //Define Table Columns
         columns:[
            {title:"Time Card Id", field:"timeCardId",    hozAlign:"left", visible:false},
            {title:"Ticket",       field:"panTicketCode", hozAlign:"left", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">receipt</i>&nbsp" + cell.getRow().getData().panTicketCode);
               },
               tooltip:function(cell) {
                  var toolTip = cell.getRow().getData().panTicketCode;
                  return (toolTip);                  
               }
            },
            {title:"Mfg. Date", field:"manufactureDate", hozAlign:"left", print:true,
               formatter:"datetime",  // Requires moment.js 
               formatterParams:{
                  outputFormat:"M/D/YYYY",
                  invalidPlaceholder:"---"
               }
            },
            {title:"Operator",     field:"operator",        hozAlign:"left", headerFilter:true, print:true},
            {title:"Employee #",   field:"employeeNumber",  hozAlign:"left",                    print:true},
            {title:"Job #",        field:"jobNumber",       hozAlign:"left", headerFilter:true, print:true},
            {title:"WC #",         field:"wcNumber",        hozAlign:"left", headerFilter:true, print:true},
            {title:"Shift Time",   field:"shiftTime",       hozAlign:"left",                    print:true,
               formatter:function(cell, formatterParams, onRendered){

                  var minutes = parseInt(cell.getValue());
                  
                  var cellValue = Math.floor(minutes / 60) + ":" + ("0" + (minutes % 60)).slice(-2);
                  
                  if (cell.getRow().getData().incompleteShiftTime)
                  {
                     cellValue += "&nbsp<span class=\"incomplete-indicator\">incomplete</div>";
                  }
                  
                  return (cellValue);
               },
               formatterPrint:function(cell, formatterParams, onRendered){

                  var minutes = parseInt(cell.getValue());
                  
                  var cellValue = Math.floor(minutes / 60) + ":" + ("0" + (minutes % 60)).slice(-2);
                  
                  return (cellValue);
               }              
            },
            {title:"Run Time",     field:"runTime",         hozAlign:"left",                    print:true,
               formatter:function(cell, formatterParams, onRendered){

                  var minutes = parseInt(cell.getValue());
                  
                  var cellValue = Math.floor(minutes / 60) + ":" + ("0" + (minutes % 60)).slice(-2);
                  
                  if (cell.getRow().getData().incompleteRunTime)
                  {
                     cellValue += "&nbsp<span class=\"incomplete-indicator\">incomplete</div>";
                  }
                  
                  return (cellValue);
                },
               formatterPrint:function(cell, formatterParams, onRendered){

                  var minutes = parseInt(cell.getValue());
                  
                  var cellValue = Math.floor(minutes / 60) + ":" + ("0" + (minutes % 60)).slice(-2);
                  
                  return (cellValue);
               }                
            },
            {title:"Basket Count",            field:"panCount",             hozAlign:"left", print:true},
            {title:"Sample Weight",           field:"sampleWeight",         hozAlign:"left", print:true},
            {title:"Total Weight",            field:"partWeight",           hozAlign:"left", print:true},
            {title:"Avg. Basket Weight",      field:"averagePanWeight",     hozAlign:"left", print:true},
            {title:"Part Count (time card)",  field:"partCountByTimeCard",  hozAlign:"left", print:true},
            {title:"Part Count (weight log)", field:"partCountByWeightLog", hozAlign:"left", print:true},
            {title:"Part Count (washer log)", field:"partCountByWasherLog", hozAlign:"left", print:true},
            {title:"Part Count",              field:"partCountEstimate",    hozAlign:"left", print:true},
            {title:"Gross Hour",              field:"grossPartsPerHour",    hozAlign:"left", print:true},
            {title:"Gross Shift",             field:"grossPartsPerShift",   hozAlign:"left", print:true},
            {title:"Efficiency",              field:"efficiency",           hozAlign:"left", print:true,
               formatter:function(cell, formatterParams, onRendered){
                  return (cell.getValue() + "%");
               },
               formatterPrint:function(cell, formatterParams, onRendered){
                  return (cell.getValue() + "%");
               },  
            },
            {title:"Scrap Count",             field:"scrapCount",           hozAlign:"left", print:true},
            {title:"Quoted Net",              field:"netPartsPerHour",      hozAlign:"left", print:true},
            {title:"Machine Hours Made",      field:"machineHoursMade",     hozAlign:"left", print:true},            
         ],
         cellClick:function(e, cell){
            var timeCardId = cell.getRow().getData().timeCardId;

            if (cell.getColumn().getField() == "panTicketCode")
            {
               document.location = "<?php echo $ROOT?>/panTicket/viewPanTicket.php?panTicketId=" + timeCardId;
            }
         },
         rowClick:function(e, row){
            // No row click function needed.
         },
      });
      
      params = getTableQueryParams(OPERATOR_SUMMARY_TABLE);
      
      tables[OPERATOR_SUMMARY_TABLE] = new Tabulator("#operator-summary-table", {
         //height:500,            // set height of table (in CSS or here), this enables the Virtual DOM and improves render speed dramatically (can be any valid css height value)
         layout:"fitData",
         cellVertAlign:"middle",
         printAsHtml:true,          //enable HTML table printing
         printRowRange:"all",       // print all rows 
         printHeader:"<h1>Totals<h1>",
         ajaxURL:url,
         ajaxParams:params,
         //Define Table Columns
         columns:[
            {title:"Operator",           field:"operator",       hozAlign:"left", headerFilter:true, print:true},
            {title:"Employee #",         field:"employeeNumber", hozAlign:"left", print:true},
            {title:"Run Time",           field:"runTime",        hozAlign:"left", print:true,},
            {title:"Efficiency",         field:"efficiency",     hozAlign:"left", print:true,
               formatter:function(cell, formatterParams, onRendered){
                  return (cell.getValue() + "%");
               }
            },
            {title:"Paid Hours",         field:"shiftHours",       hozAlign:"left", print:true},            
            {title:"Machine Hours Made", field:"machineHoursMade", hozAlign:"left", print:true},
            {title:"Ratio",              field:"ratio",            hozAlign:"left", print:true}
         ],
      });
      
      params = getTableQueryParams(SHOP_SUMMARY_TABLE);
      
      tables[SHOP_SUMMARY_TABLE] = new Tabulator("#shop-summary-table", {
         //height:500,            // set height of table (in CSS or here), this enables the Virtual DOM and improves render speed dramatically (can be any valid css height value)
         layout:"fitData",
         cellVertAlign:"middle",
         printAsHtml:true,          //enable HTML table printing
         printRowRange:"all",       // print all rows 
         printHeader:"<h1>Totals<h1>",
         ajaxURL:url,
         ajaxParams:params,
         //Define Table Columns
         columns:[
            {title:"Hours",              field:"hours",         hozAlign:"left", print:true},
            {title:"Efficiency",         field:"efficiency",    hozAlign:"left", print:true,
               formatter:function(cell, formatterParams, onRendered){
                  return (cell.getValue() + "%");
               }
            },
            {title:"Paid Hours",         field:"shiftHours",       hozAlign:"left", print:true},                        
            {title:"Machine Hours Made", field:"machineHoursMade", hozAlign:"left", print:true},
            {title:"Ratio",              field:"ratio",            hozAlign:"left", print:true}
         ],
      });

      function updateFilter(event)
      {
         if (document.readyState === "complete")
         {
            var filterId = event.srcElement.id;
   
            if (filterId == "mfg-date-filter")
            {
               tables[DAILY_SUMMARY_TABLE].setData(getTableQuery(), getTableQueryParams(DAILY_SUMMARY_TABLE))
               .then(function(){
                  // Run code after table has been successfuly updated
               })
               .catch(function(error){
                  // Handle error loading data
               });
               
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

               if (filterId == "mfg-date-filter")
               {
                  setSession("dailySummaryReport.filter.mfgDate", document.getElementById("mfg-date-filter").value);
               }
            }
         }
      }

      function formattedDate(date)
      {
         // Convert to Y-M-D format, per HTML5 Date control.
         // https://stackoverflow.com/questions/12346381/set-date-in-input-type-date
         var day = ("0" + date.getDate()).slice(-2);
         var month = ("0" + (date.getMonth() + 1)).slice(-2);
         
         var formattedDate = date.getFullYear() + "-" + (month) + "-" + (day);

         return (formattedDate);
      }            

      function filterToday()
      {
         var mfgDateFilter = document.querySelector('#mfg-date-filter');
         
         if (mfgDateFilter != null)
         {
            var today = new Date();
            
            mfgDateFilter.value = formattedDate(today); 

            mfgDateFilter.dispatchEvent(new Event('change'));
         }         
      }

      function filterYesterday()
      {
         var mfgDateFilter = document.querySelector('#mfg-date-filter');
         
         if (mfgDateFilter != null)
         {
            var yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            
            mfgDateFilter.value = formattedDate(yesterday); 

            mfgDateFilter.dispatchEvent(new Event('change'));
         }      
      }

      // Setup event handling on all DOM elements.
      window.addEventListener('resize', function() { tables[DAILY_SUMMARY_TABLE].redraw(); tables[OPERATOR_SUMMARY_TABLE].redraw(); tables[SHOP_SUMMARY_TABLE].redraw();});
      document.getElementById("mfg-date-filter").addEventListener("change", updateFilter);      
      document.getElementById("today-button").onclick = filterToday;
      document.getElementById("yesterday-button").onclick = filterYesterday;
      document.getElementById("download-link").onclick = function(){table.download("csv", "<?php echo getReportFilename() ?>", {delimiter:"."})};
      document.getElementById("print-link").onclick = function(){tables[DAILY_SUMMARY_TABLE].print(false, true);};

      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
      document.getElementById("menu-button").onclick = function(){document.getElementById("menu").classList.toggle('shown');};
   </script>
   
</body>

</html>