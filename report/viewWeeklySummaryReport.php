<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once '../common/authentication.php';
require_once '../common/database.php';
require_once '../common/weeklySummaryReport.php';
require_once '../common/header.php';
require_once '../common/jobInfo.php';
require_once '../common/newIndicator.php';
require_once '../common/permissions.php';
require_once '../common/roles.php';
require_once '../common/timeCardInfo.php';
require_once '../common/userInfo.php';
require_once '../common/version.php';

function getMfgDate()
{
   $mfgDate = Time::now("Y-m-d");
   
   if (isset($_SESSION["weeklySummaryReport.filter.mfgDate"]))
   {
      $mfgDate = $_SESSION["weeklySummaryReport.filter.mfgDate"];
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
   
   if (isset($_SESSION["weeklySummaryReport.filter.useMaintenanceLogEntries"]))
   {
      $useMaintenanceLogEntries = filter_var($_SESSION["weeklySummaryReport.filter.useMaintenanceLogEntries"], FILTER_VALIDATE_BOOLEAN);
   }
   
   return ($useMaintenanceLogEntries);
}

function getReportStartDate()
{
   $dates = WorkDay::getDates(getMfgDate());
   
   $dateTime = new DateTime($dates[WorkDay::SUNDAY], new DateTimeZone('America/New_York'));  // TODO: Replace
   $formattedDatetime = $dateTime->format("D n/j");
   
   return ($formattedDatetime);
}

function getReportEndDate()
{
   $dates = WorkDay::getDates(getMfgDate());
   
   $dateTime = new DateTime($dates[WorkDay::SATURDAY], new DateTimeZone('America/New_York'));  // TODO: Replace
   $formattedDatetime = $dateTime->format("D n/j");
   
   return ($formattedDatetime);
}

function getReportFilename()
{
   $mfgDate = getFilterMfgDate();
   
   $filename = "WeeklySummaryReport_" . $mfgDate . ".csv";
   
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
            <div class="heading">Weekly Summary Report</div>&nbsp;&nbsp;
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
         
         <div id="report-table-header" class="table-header">Operator Summary</div>
         
         <br>

         <div id="week-number-div" class="date-range-header"></div>
       
         <div id="operator-summary-table"></div>
         
         <br>
                  
         <div class="table-header">Shop Summary</div>
         
         <br>
         
         <div id="week-date-range-div" class="date-range-header"></div>                  
         
         <div id="shop-summary-table"></div>
         
         <br>
         
         <div class="table-header">Weekly Bonus</div>
         
         <br>
        
         <div id="week-date-range-div" class="date-range-header"></div>
         
         <div id="bonus-table"></div>
         
         <br>
        
         <div id="download-link" class="download-link">Download CSV file</div>
         
         <div id="print-link" class="download-link">Print</div>         
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::WEEKLY_REPORT ?>);   
      
      const OPERATOR_SUMMARY_TABLE = <?php echo WeeklySummaryReportTable::OPERATOR_SUMMARY; ?>;
      const SHOP_SUMMARY_TABLE = <?php echo WeeklySummaryReportTable::SHOP_SUMMARY; ?>;
      const BONUS_TABLE = <?php echo WeeklySummaryReportTable::BONUS; ?>;
   
      preserveSession();

      function getTableQuery()
      {
         return ("<?php echo $ROOT ?>/api/weeklySummaryReportData/");
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
            // Sunday
            {
               title:"Sunday",
               columns:[
                  {title:"Hours",         field:"sunday.runTime"},
                  {title:"Efficiency",    field:"sunday.efficiency",
                     formatter:function(cell, formatterParams, onRendered){
                        return (cell.getValue() + "%");
                     }
                  },
                  {title:"Paid Hours",    field:"sunday.shiftTime"},
                  {title:"Machine Hours", field:"sunday.machineHoursMade"},
                  {title:"Ratio",         field:"sunday.ratio",}
               ],
            },
            // Monday
            {
               title:"Monday",
               columns:[
                  {title:"Hours",         field:"monday.runTime"},
                  {title:"Efficiency",    field:"monday.efficiency",
                     formatter:function(cell, formatterParams, onRendered){
                        return (cell.getValue() + "%");
                     }
                  },
                  {title:"Paid Hours",    field:"monday.shiftTime"},
                  {title:"Machine Hours", field:"monday.machineHoursMade"},
                  {title:"Ratio",         field:"monday.ratio"}
               ],
            },
            // Tuesday
            {
               title:"Tuesday",
               columns:[
                  {title:"Hours",         field:"tuesday.runTime"},
                  {title:"Efficiency",    field:"tuesday.efficiency",
                     formatter:function(cell, formatterParams, onRendered){
                        return (cell.getValue() + "%");
                     }
                  },
                  {title:"Paid Hours",    field:"tuesday.shiftTime",},                  
                  {title:"Machine Hours", field:"tuesday.machineHoursMade"},
                  {title:"Ratio",         field:"tuesday.ratio"}
               ],
            },
            // Wednesday
            {
               title:"Wednesday",
               columns:[
                  {title:"Hours",         field:"wednesday.runTime"},
                  {title:"Efficiency",    field:"wednesday.efficiency",
                     formatter:function(cell, formatterParams, onRendered){
                        return (cell.getValue() + "%");
                     }
                  },
                  {title:"Paid Hours",    field:"wednesday.shiftTime"},                                    
                  {title:"Machine Hours", field:"wednesday.machineHoursMade"},
                  {title:"Ratio",         field:"wednesday.ratio"}
               ],
            },
            // Thursday
            {
               title:"Thursday",
               columns:[
                  {title:"Hours",         field:"thursday.runTime"},
                  {title:"Efficiency",    field:"thursday.efficiency",
                     formatter:function(cell, formatterParams, onRendered){
                        return (cell.getValue() + "%");
                     }
                  },
                  {title:"Paid Hours",    field:"thursday.shiftTime"},                                                      
                  {title:"Machine Hours", field:"thursday.machineHoursMade"},
                  {title:"Ratio",         field:"thursday.ratio"}
               ],
            },
            // Friday
            {
               title:"Friday",
               columns:[
                  {title:"Hours",         field:"friday.runTime"},
                  {title:"Efficiency",    field:"friday.efficiency",
                     formatter:function(cell, formatterParams, onRendered){
                        return (cell.getValue() + "%");
                     }
                  },
                  {title:"Paid Hours",    field:"friday.shiftTime"},                  
                  {title:"Machine Hours", field:"friday.machineHoursMade"},
                  {title:"Ratio",         field:"friday.ratio"}
               ],
            },
            // Saturday
            {
               title:"Saturday",
               columns:[
                  {title:"Hours",         field:"saturday.runTime"},
                  {title:"Efficiency",    field:"saturday.efficiency",
                     formatter:function(cell, formatterParams, onRendered){
                        return (cell.getValue() + "%");
                     }
                  },
                  {title:"Paid Hours",    field:"saturday.shiftTime"},                  
                  {title:"Machine Hours", field:"saturday.machineHoursMade"},
                  {title:"Ratio",         field:"saturday.ratio"}
               ],
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
         layout:"fitData",
         cellVertAlign:"middle",
         // Printing
         printAsHtml:true,
         printRowRange:"all", 
         printHeader:"<h1>Totals<h1>",
         // Columns
         columns:[
            {title:"Day",                field:"day"},
            {title:"Hours",              field:"runTime"},         
            {title:"Efficiency",         field:"efficiency",
               formatter:function(cell, formatterParams, onRendered){
                  return (cell.getValue() + "%");
               }
            },
            {title:"Paid Hours",         field:"shiftTime"},
            {title:"Machine Hours Made", field:"machineHoursMade"},
            {title:"Ratio",              field:"ratio"},
         ]
      });      
      
      // ***********************************************************************
      //                                Bonus
      

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
         //  Printing
         printAsHtml:true,
         printRowRange:"all", 
         printHeader:"<h1>Totals<h1>",
         // Columns
         columns:[
            {title:"Operator",   field:"operator",       headerFilter:true, frozen:true},
            {title:"Employee #", field:"employeeNumber", frozen:true},         
            {title:"Hours",      field:"runTime"},
            {title:"Efficiency", field:"efficiency",
               formatter:function(cell, formatterParams, onRendered){
                  return (cell.getValue() + "%");
               }
            },
            {title:"Paid Hours",    field:"shiftTime"},
            {title:"Machine Hours", field:"machineHoursMade"},
            {title:"PC/G",          field:"pcOverG"},
            {
               title:"75%",
               columns:[
                  {title:"$0.25", field:"tier1", formatter:bonusFormatter, formatterParams:{tier:1}}
               ]
            },
            {
               title:"80%",
               columns:[
                  {title:"$0.50", field:"tier2", formatter:bonusFormatter, formatterParams:{tier:2}}
               ]
            },
            {
               title:"85%",
               columns:[
                  {title:"$1.00", field:"tier3", formatter:bonusFormatter, formatterParams:{tier:3}}
               ]
            },
            {
               title:"90%",
               columns:[
                  {title:"$1.50", field:"tier4", formatter:bonusFormatter, formatterParams:{tier:4}}
               ]
            },
            {
               title:"95%",
               columns:[
                  {title:"$2.00", field:"tier5", formatter:bonusFormatter, formatterParams:{tier:5}}
               ]
            },
            {
               title:"100%",
               columns:[
                  {title:"$3.00", field:"tier6", formatter:bonusFormatter, formatterParams:{tier:6}}
               ]
            },
            {
               title:"3+ Machine",
               columns:[
                  {title:"$4.00", field:"additionalMachineBonus", 
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

      function updateFilter(event)
      {
         if (document.readyState === "complete")
         {
            var filterId = event.srcElement.id;
   
            if ((filterId == "mfg-date-filter") || 
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
               
               tables[BONUS_TABLE].setData(getTableQuery(), getTableQueryParams(BONUS_TABLE))
               .then(function(){
                  // Run code after table has been successfuly updated
               })
               .catch(function(error){
                  // Handle error loading data
               });
               
               updateReportDates();

               if (filterId == "mfg-date-filter")
               {
                  setSession("weeklySummaryReport.filter.mfgDate", document.getElementById("mfg-date-filter").value);
               }
               
               if (filterId == "maintenance-log-filter")
               {
                  setSession("weeklySummaryReport.filter.useMaintenanceLogEntries", document.getElementById("maintenance-log-filter").checked);
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
      
      function updateReportDates()
      {
         var mfgDate = document.getElementById("mfg-date-filter").value;
      
         // AJAX call to retrieve report dates.
         requestUrl = "../api/weeklySummaryReportDates/?mfgDate=" + mfgDate;
         
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
                     var headers = document.getElementsByClassName("date-range-header");
                     for (var header of headers)
                     {
                        header.innerHTML = "Week " + json.weekNumber + ", " + json.weekStartDate + " - " + json.weekEndDate;
                     }
                  }
                  else
                  {
                     console.log("API call to retrieve report dates.");
                  }
               }
               catch (expection)
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
      window.addEventListener('resize', function() { tables[OPERATOR_SUMMARY_TABLE].redraw(); tables[BONUS_TABLE].redraw(); });
      document.getElementById("mfg-date-filter").addEventListener("change", updateFilter);
      document.getElementById("maintenance-log-filter").addEventListener("change", updateFilter);      
      document.getElementById("today-button").onclick = filterToday;
      document.getElementById("yesterday-button").onclick = filterYesterday;
      document.getElementById("download-link").onclick = function(){table.download("csv", "<?php echo getReportFilename() ?>", {delimiter:"."})};
      document.getElementById("print-link").onclick = function(){tables[OPERATOR_SUMMARY_TABLE].print("active", true);};

      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
      
      updateReportDates();
   </script>
   
</body>

</html>