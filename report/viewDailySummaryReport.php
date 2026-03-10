<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/common/dailySummaryReport.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/jobInfo.php';
require_once ROOT.'/common/newIndicator.php';
require_once ROOT.'/common/permissions.php';
require_once ROOT.'/common/timeCardInfo.php';
require_once ROOT.'/common/userInfo.php';
require_once ROOT.'/common/version.php';
require_once ROOT.'/core/common/role.php';

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
   $mfgDate = Time::toJavascriptDate($mfgDate);
   
   return ($mfgDate);
}

function getUseMaintenanceLogEntries()
{
   $useMaintenanceLogEntries = false;
   
   if (isset($_SESSION["dailySummaryReport.filter.useMaintenanceLogEntries"]))
   {
      $useMaintenanceLogEntries = filter_var($_SESSION["dailySummaryReport.filter.useMaintenanceLogEntries"], FILTER_VALIDATE_BOOLEAN);
   }
   
   return ($useMaintenanceLogEntries);   
}

function getReportFilename($tableId)
{
   $mfgDate = getFilterMfgDate();
   
   $tableLabel = "";
   switch ($tableId)
   {
      case DailySummaryReportTable::DAILY_SUMMARY:
      {
         $tableLabel = "DailySummary";
         break;
      }
      
      case DailySummaryReportTable::OPERATOR_SUMMARY:
      {
         $tableLabel = "OperatorSummary";
         break;
      }
      
      case DailySummaryReportTable::SHOP_SUMMARY:
      {
         $tableLabel = "ShopSummary";
         break;
      }
      
      default:
      {
         break;
      }
   }
   
   $filename = "DailySummaryReport_{$tableLabel}_{$mfgDate}.csv";
   
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
   <link rel="stylesheet" type="text/css" href="../thirdParty/tabulator/dist/css/tabulator.min.css<?php echo versionQuery();?>"/>
   
   <link rel="stylesheet" type="text/css" href="../common/theme.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="../common/common.css<?php echo versionQuery();?>"/>
   
   <script src="/thirdParty/tabulator/dist/js/tabulator.min.js<?php echo versionQuery();?>"></script>
   <script src="/thirdParty/luxon/luxon.min.js<?php echo versionQuery();?>"></script>
   
   <script src="/common/common.js<?php echo versionQuery();?>"></script>
   <script src="/common/validate.js<?php echo versionQuery();?>"></script>
   <script src="/script/common/common.js<?php echo versionQuery();?>"></script>
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script>
      
</head>

<body class="flex-vertical flex-top flex-left">

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render() ?>
      
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
            &nbsp;&nbsp;
            <input id="maintenance-log-filter" type="checkbox" <?php echo getUseMaintenanceLogEntries() ? "checked" : "" ?>/>Include maintenace log
         </div>
         
         <br>
         
         <div id="report-table-header" class="table-header"></div>
         
         <br>
        
         <div id="report-table"></div>
         
         <br>
         
         <div id="download-daily-summary-link" class="download-link">Download CSV file</div>
         
         <div id="print-daily-summary-link" class="download-link">Print</div>         
         
         <br>
         
         <div class="table-header">Operator Summary</div>
         
         <br>
        
         <div id="operator-summary-table"></div>
         
         <br>
         
         <div id="download-operator-summary-link" class="download-link">Download CSV file</div>
         
         <div id="print-operator-summary-link" class="download-link">Print</div>         
         
         <br>
         
         <div class="table-header">Shop Summary</div>
         
         <br>
        
         <div id="shop-summary-table"></div>
         
         <br>
        
         <div id="download-shop-summary-link" class="download-link">Download CSV file</div>
         
         <div id="print-shop-summary-link" class="download-link">Print</div>         
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::REPORT ?>);   
   
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
         params.useMaintenanceLogEntries = document.getElementById("maintenance-log-filter").checked;
         params.table = table;

         return (params);
      }
      
      // Array of tables.
      var tables = [];

      var url = getTableQuery();
      var params = getTableQueryParams(DAILY_SUMMARY_TABLE);
      
      tables[DAILY_SUMMARY_TABLE] = new Tabulator("#report-table", {
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
         printHeader:"<h2>Daily Summary Report - Daily Summary<h2>",
         // Columns
         columns:[
            {title:"Time Card Id", field:"timeCardId",      frozen:true, visible:false},
            {title:"Status",       field:"dataStatusLabel", hozAlign:"center", frozen:true,
               formatter:function(cell, formatterParams, onRendered){
                  let dataStatusClass = cell.getRow().getData().dataStatusClass;
                  cell.getElement().classList.add(dataStatusClass);
                  //let value = cell.getValue();
                  //cell.getElement().classList.add(dataStatusClass);
                  //return (`<div class="${dataStatusClass}">${value}</div>`);
                  return (cell.getValue());
               },
            },
            {title:"Mfg. Date", field:"manufactureDate",    frozen:true,
               formatter:"datetime", 
               formatterParams:{
                  outputFormat:"M/d/yyyy",
                  invalidPlaceholder:"---"
               }
            },
            {title:"Operator",     field:"operator",        headerFilter:true, frozen:true},
            {title:"Employee #",   field:"employeeNumber"},
            
            {title:"",             field:"panTicketCode",
               formatter:function(cell, formatterParams, onRendered){
                  let maintenanceLogEntry = cell.getRow().getData().maintenanceLogEntry;
                  let panTicketCode = cell.getRow().getData().panTicketCode;
                
                  let cellValue = "";
                  if (maintenanceLogEntry)
                  {
                     cellValue = "MAINT";
                  }
                  else
                  {
                     cellValue = "<i class=\"material-icons icon-button\">receipt</i>&nbsp" + panTicketCode;
                  }
                  
                  return (cellValue);
               },
               tooltip:function(e, cell, onRendered) {
                  let maintenanceLogEntry = cell.getRow().getData().maintenanceLogEntry;
                  return (maintenanceLogEntry ? "" : "Pan ticket");                  
               }
            },
            {title:"",       field:"timeCardLink",          print:false,
               formatter:function(cell, formatterParams, onRendered){
                  let maintenanceLogEntry = cell.getRow().getData().maintenanceLogEntry;
                  let panTicketCode = cell.getRow().getData().panTicketCode;
                  
                  let cellValue = "";
                  if (maintenanceLogEntry)
                  {
                     cellValue = "<i class=\"material-icons icon-button\">build</i>";
                  }
                  else
                  {
                     cellValue = "<i class=\"material-icons icon-button\">schedule</i>";
                  }
                  
                  return (cellValue);
               },
               tooltip:function(e, cell, onRendered) {
                  let maintenanceLogEntry = cell.getRow().getData().maintenanceLogEntry;
                  return (maintenanceLogEntry ? "Maintenance log" : "Time card");                  
               }
            },              
            {title:"",       field:"partWeightLogLink",     print:false,
               formatter:function(cell, formatterParams, onRendered){
                  let maintenanceLogEntry = cell.getRow().getData().maintenanceLogEntry;
                  return (maintenanceLogEntry ? "" : "<i class=\"material-icons icon-button\">fingerprint</i>");
               },
               tooltip:function(e, cell, onRendered) {
                  let maintenanceLogEntry = cell.getRow().getData().maintenanceLogEntry;
                  return (maintenanceLogEntry ? "" : "Part weight logs");                  
               }
            },            
            {title:"",       field:"partWasherLogLink",     print:false,
               formatter:function(cell, formatterParams, onRendered){
                  let maintenanceLogEntry = cell.getRow().getData().maintenanceLogEntry;
                  return (maintenanceLogEntry ? "" : "<i class=\"material-icons icon-button\">opacity</i>");
               },
               tooltip:function(e, cell, onRendered) {
                  let maintenanceLogEntry = cell.getRow().getData().maintenanceLogEntry;
                  return (maintenanceLogEntry ? "" : "Part washer logs");                  
               }
            },
            {title:"Job #",        field:"jobNumber",       headerFilter:true},
            {title:"WC #",         field:"wcLabel",         headerFilter:true},
            {title:"Shift Time",   field:"shiftTime",
               formatter:function(cell, formatterParams, onRendered){

                  var minutes = parseInt(cell.getValue());
                  
                  var cellValue = Math.floor(minutes / 60) + ":" + ("0" + (minutes % 60)).slice(-2);
                  
                  if (cell.getRow().getData().incompleteShiftTime)
                  {
                     cellValue += "&nbsp<span class=\"incomplete-indicator\">incomplete</span>";
                  }
                  
                  return (cellValue);
               },
               formatterPrint:function(cell, formatterParams, onRendered){

                  var minutes = parseInt(cell.getValue());
                  
                  var cellValue = Math.floor(minutes / 60) + ":" + ("0" + (minutes % 60)).slice(-2);
                  
                  return (cellValue);
               }              
            },
            {title:"Run Time",     field:"runTime",
               formatter:function(cell, formatterParams, onRendered){

                  var minutes = parseInt(cell.getValue());
                  
                  var cellValue = Math.floor(minutes / 60) + ":" + ("0" + (minutes % 60)).slice(-2);
                  
                  if (cell.getRow().getData().incompleteRunTime)
                  {
                     cellValue += "&nbsp<span class=\"incomplete-indicator\">incomplete</span>";
                  }
                  else if (cell.getRow().getData().unapprovedRunTime)
                  {
                     cellValue += "&nbsp<span class=\"unapproved-indicator\">unapproved</span>";                  
                  }
                  
                  return (cellValue);
               },
               formatterPrint:function(cell, formatterParams, onRendered){

                  var minutes = parseInt(cell.getValue());
                  
                  var cellValue = Math.floor(minutes / 60) + ":" + ("0" + (minutes % 60)).slice(-2);
                  
                  return (cellValue);
               }                
            },
            {title:"Setup Time",     field:"setupTime",
               formatter:function(cell, formatterParams, onRendered){

                  var minutes = parseInt(cell.getValue());
                  
                  var cellValue = Math.floor(minutes / 60) + ":" + ("0" + (minutes % 60)).slice(-2);
                  
                  if (cell.getRow().getData().unapprovedSetupTime)
                  {
                     cellValue += "&nbsp<span class=\"unapproved-indicator\">unapproved</span>";                  
                  }
                  
                  return (cellValue);
               },
               formatterPrint:function(cell, formatterParams, onRendered){

                  var minutes = parseInt(cell.getValue());
                  
                  var cellValue = Math.floor(minutes / 60) + ":" + ("0" + (minutes % 60)).slice(-2);
                  
                  return (cellValue);
               }                
            },
            {title:"Basket Count",            field:"panCount",
               formatter:function(cell, formatterParams, onRendered){
                  var cellValue = cell.getValue();
                  
                  if (cell.getRow().getData().incompletePanCount)
                  {
                     cellValue += "&nbsp<span class=\"incomplete-indicator\">incomplete</span>";
                  }
                  
                  return (cellValue);
               }
            },
            // Factory Stats
            {
               title:"Factory Stats",
               columns:[
                  {title:"Count",      field:"factoryStats.count"},
                  {title:"First Part", field:"factoryStats.firstEntry"},
                  {title:"Last Part",  field:"factoryStats.updateTime"},
               ]
            },             
            {title:"Sample Weight",           field:"sampleWeight"},
            {title:"Total Weight",            field:"partWeight",
               formatter:function(cell, formatterParams, onRendered){
                  var cellValue = cell.getValue();
               
                  if (cell.getRow().getData().unreasonablePartWeight)
                  {
                     cellValue += "&nbsp<span class=\"mismatch-indicator\">unreasonable</span>";                 
                  }
                  
                  return (cellValue);
               }
            },
            {title:"Avg. Basket Weight",      field:"averagePanWeight"},
            {title:"Part Count (time card)",  field:"partCountByTimeCard",
               formatter:function(cell, formatterParams, onRendered){
                  var cellValue = cell.getValue();
                  
                  if (cell.getRow().getData().incompletePartCount)
                  {
                     cellValue += "&nbsp<span class=\"incomplete-indicator\">incomplete</span>";
                  }
                  else if (cell.getRow().getData().unreasonablePartCountByTimeCard)
                  {
                     cellValue += "&nbsp<span class=\"mismatch-indicator\">unreasonable</span>";
                  }
                  
                  return (cellValue);
               }
            },
            {title:"Part Count (weight log)", field:"partCountByWeightLog",
               formatter:function(cell, formatterParams, onRendered){
                  var cellValue = cell.getValue();
                  
                  if (cell.getRow().getData().unreasonablePartCountByWeightLog)
                  {
                     cellValue += "&nbsp<span class=\"mismatch-indicator\">unreasonable</span>";
                  }
                  
                  return (cellValue);
               }
            },            
            {title:"Part Count (washer log)", field:"partCountByWasherLog",
               formatter:function(cell, formatterParams, onRendered){
                  var cellValue = cell.getValue();
                  
                  if (cell.getRow().getData().unreasonablePartCountByWasherLog)
                  {
                     cellValue += "&nbsp<span class=\"mismatch-indicator\">unreasonable</span>";
                  }
                  
                  return (cellValue);
               }
            },            
            {title:"Part Count",              field:"partCount"},
            {title:"Gross Hour",              field:"grossPartsPerHour"},
            {title:"Gross Shift",             field:"grossParts"},
            {title:"Efficiency",              field:"efficiency",
               formatter:function(cell, formatterParams, onRendered){
                  var cellValue = cell.getValue() + "%";
                  
                  if (cell.getRow().getData().unreasonableEfficiency)
                  {
                     cellValue += "&nbsp<span class=\"mismatch-indicator\">unreasonable</span>";
                  }
                  
                  return (cellValue);
               },
               formatterPrint:function(cell, formatterParams, onRendered){
                  return (cell.getValue() + "%");
               },  
            },
            {title:"Scrap Count",             field:"scrapCount"},
            {title:"Quoted Net",              field:"netPartsPerHour"},
            {title:"Machine Hours<br>Made",   field:"machineHoursMade"},
            {title:"In Process<br>Inspections", field:"inProcessInspectionCount",
               formatter:function(cell, formatterParams, onRendered){
                  var cellValue = parseInt(cell.getValue());
                  return ((cellValue > 0) ? cellValue : "");
               }
            }                 
         ],
         groupBy:"operator",
         initialSort:[
            {column: "efficiency", dir: "dec"}
         ]
      });
      
      tables[DAILY_SUMMARY_TABLE].on("cellClick", function(e, cell) {
         let timeCardId = cell.getRow().getData().timeCardId;
         let maintenanceEntryId = cell.getRow().getData().maintenanceEntryId;
         let maintenanceLogEntry = cell.getRow().getData().maintenanceLogEntry;
         
         if (maintenanceLogEntry)
         {
             if (cell.getColumn().getField() == "timeCardLink")
             {
                document.location = "<?php echo $ROOT?>/maintenanceLog/maintenanceLogEntry.php?entryId=" + maintenanceEntryId;
             }
         }
         else if (cell.getColumn().getField() == "panTicketCode")
         {
            document.location = "<?php echo $ROOT?>/panTicket/viewPanTicket.php?panTicketId=" + timeCardId;
         }
         else if ((cell.getColumn().getField() == "timeCardLink") ||
                  (cell.getColumn().getField() == "partCountByTimeCard"))
         {
            document.location = "<?php echo $ROOT?>/timecard/viewTimeCard.php?timeCardId=" + timeCardId;
         }
         else if ((cell.getColumn().getField() == "partWeightLogLink") ||
                  (cell.getColumn().getField() == "partWeight") ||
                  (cell.getColumn().getField() == "partCountByWeightLog"))
                  
         {
            document.location = "<?php echo $ROOT?>/partWeightLog/partWeightLog.php?timeCardId=" + timeCardId;
         }
         else if ((cell.getColumn().getField() == "partWasherLogLink") ||
                  (cell.getColumn().getField() == "partCountByWasherLog"))
         {
            document.location = "<?php echo $ROOT?>/partWasherLog/partWasherLog.php?timeCardId=" + timeCardId;
         }
      });
      
      params = getTableQueryParams(OPERATOR_SUMMARY_TABLE);
      
      tables[OPERATOR_SUMMARY_TABLE] = new Tabulator("#operator-summary-table", {
         // Data
         ajaxURL:url,
         ajaxParams:params,
         // Layout
         maxHeight:500,  // set height of table (in CSS or here), this enables the Virtual DOM and improves render speed dramatically (can be any valid css height value)
         layout:"fitData",
         columnDefaults:{
            hozAlign:"left", 
            vertAlign:"middle"
         },
         persistence:true,
         // Printing
         printAsHtml:true,
         printRowRange:"all", 
         printHeader:"<h2>Daily Summary Report - Operator Summary<h2>",
         // Columns
         columns:[
            {title:"Operator",                  field:"operator",       headerFilter:true, frozen:true},
            {title:"Employee #",                field:"employeeNumber", frozen:true},
            {title:"Run Time",                  field:"runTime"},
            {title:"Efficiency",                field:"efficiency",
               formatter:function(cell, formatterParams, onRendered){
                  return (cell.getValue() + "%");
               }
            },
            // Temp Start
            {title:"2 Machine Efficiency",      field:"topEfficiency",
               formatter:function(cell, formatterParams, onRendered){
                  return (cell.getValue() + "%");
               }
            },
            {title:"Borrowed Hours",            field:"adjustedHours"},
            {title:"Borrowed Parts",            field:"adjustedPartCount"},
            {title:"Adj. 2 Machine Efficiency", field:"adjustedTopEfficiency",
               formatter:function(cell, formatterParams, onRendered){
                  return (cell.getValue() + "%");
               }
            },
            {title:"PC/G",                      field:"adjustedBottomPCOverG"},
            // Temp End
            {title:"Paid Hours",                field:"shiftTime"},            
            {title:"Machine Hours Made",        field:"machineHoursMade"},
            {title:"Ratio",                     field:"ratio"}
         ],
      });
      
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
         printHeader:"<h2>Daily Summary Report - Shop Summary<h2>",
         // Columns
         columns:[
            {title:"Hours",              field:"runTime"},
            {title:"Efficiency",         field:"efficiency",
               formatter:function(cell, formatterParams, onRendered){
                  return (cell.getValue() + "%");
               }
            },
            {title:"Paid Hours",         field:"shiftTime"},                        
            {title:"Machine Hours Made", field:"machineHoursMade"},
            {title:"Ratio",              field:"ratio"}
         ],
      });

      function updateFilter(event)
      {
         if (document.readyState === "complete")
         {
            var filterId = event.srcElement.id;
   
            if ((filterId == "mfg-date-filter") || 
                (filterId == "maintenance-log-filter"))
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
               
               if (filterId == "maintenance-log-filter")
               {
                  setSession("dailySummaryReport.filter.useMaintenanceLogEntries", document.getElementById("maintenance-log-filter").checked);
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
      document.getElementById("maintenance-log-filter").addEventListener("change", updateFilter);      
      document.getElementById("today-button").onclick = filterToday;
      document.getElementById("yesterday-button").onclick = filterYesterday;
      
      document.getElementById("download-daily-summary-link").onclick = function(){tables[DAILY_SUMMARY_TABLE].download("csv", "<?php echo getReportFilename(DailySummaryReportTable::DAILY_SUMMARY) ?>", {delimiter:","})};
      document.getElementById("print-daily-summary-link").onclick = function(){tables[DAILY_SUMMARY_TABLE].print("active", true);};
      document.getElementById("download-operator-summary-link").onclick = function(){tables[OPERATOR_SUMMARY_TABLE].download("csv", "<?php echo getReportFilename(DailySummaryReportTable::OPERATOR_SUMMARY) ?>", {delimiter:","})};
      document.getElementById("print-operator-summary-link").onclick = function(){tables[OPERATOR_SUMMARY_TABLE].print("active", true);};
      document.getElementById("download-shop-summary-link").onclick = function(){tables[SHOP_SUMMARY_TABLE].download("csv", "<?php echo getReportFilename(DailySummaryReportTable::SHOP_SUMMARY) ?>", {delimiter:","})};
      document.getElementById("print-shop-summary-link").onclick = function(){tables[SHOP_SUMMARY_TABLE].print("active", true);};

      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
      document.getElementById("menu-button").onclick = function(){document.getElementById("menu").classList.toggle('shown');};
   </script>
   
</body>

</html>