<?php

require_once '../common/authentication.php';
require_once '../common/database.php';
require_once '../common/header.php';
require_once '../common/jobInfo.php';
require_once '../common/menu.php';
require_once '../common/newIndicator.php';
require_once '../common/partWasherEntry.php';
require_once '../common/permissions.php';

function getFilterStartDate()
{
   $startDate = Time::now("Y-m-d");
   
   if (isset($_SESSION["partWasher.filter.startDate"]))
   {
      $startDate = $_SESSION["partWasher.filter.startDate"];
   }

   // Convert to Javascript date format.
   $dateTime = new DateTime($startDate, new DateTimeZone('America/New_York'));  // TODO: Replace
   $startDate = $dateTime->format(Time::$javascriptDateFormat);
   
   return ($startDate);
}

function getFilterEndDate()
{
   $startDate = Time::now("Y-m-d");
   
   if (isset($_SESSION["partWasher.filter.endDate"]))
   {
      $startDate = $_SESSION["partWasher.filter.endDate"];
   }
   
   // Convert to Javascript date format.
   $dateTime = new DateTime($startDate, new DateTimeZone('America/New_York'));  // TODO: Replace
   $startDate = $dateTime->format(Time::$javascriptDateFormat);
   
   return ($startDate);
}

function getReportFilename()
{
   $startDate = getFilterStartDate();
   $endDate = getFilterEndDate();
   
   $dateString = $startDate;
   if ($startDate != $endDate)
   {
      $dateString .= "_to_" . $endDate;
   }
   
   $filename = "PartWasherLog_" . $dateString . ".csv";
   
   return ($filename);
}

// ********************************** BEGIN ************************************

Time::init();

session_start();

if (!Authentication::isAuthenticated())
{
   header('Location: ../login.php');
   exit;
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
   <script src="partWasherLog.js"></script>
      
</head>

<body class="flex-vertical flex-top flex-left">

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(Activity::PART_WASH); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading">Part Washer Log</div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div id="description" class="description">The Part Washer Log provides an up-to-the-minute view into the part washing process.  Here you can track when your parts come through the wash line, and in what volume.</div>
         
         <br>
         
         <div class="flex-horizontal flex-v-center flex-left">
            <div style="white-space: nowrap">Start date</div>
            &nbsp;
            <input id="start-date-filter" type="date" value="<?php echo getFilterStartDate()?>">
            &nbsp;&nbsp;
            <div style="white-space: nowrap">End date</div>
            &nbsp;
            <input id="end-date-filter" type="date" value="<?php echo getFilterEndDate()?>">
            &nbsp;&nbsp;
            <button id="today-button" class="small-button">Today</button>
            &nbsp;&nbsp;
            <button id="yesterday-button" class="small-button">Yesterday</button>
         </div>
         
         <br>
        
         <button id="new-log-entry-button" class="accent-button">New Log Entry</button>

         <br>
        
         <div id="part-washer-log-table"></div>

         <br> 
        
         <div id="download-link" class="download-link">Download CSV file</div>
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>
   
      preserveSession();

      function getTableQuery()
      {
         return ("<?php echo $ROOT ?>/api/partWasherLogData/");
      }

      function getTableQueryParams()
      {
         
         var params = new Object();
         params.startDate =  document.getElementById("start-date-filter").value;
         params.endDate =  document.getElementById("end-date-filter").value;

         return (params);
      }
      
      var url = getTableQuery();
      var params = getTableQueryParams();
      
      // Create Tabulator on DOM element part-washer-log-table.
      var table = new Tabulator("#part-washer-log-table", {
         //height:500, // set height of table (in CSS or here), this enables the Virtual DOM and improves render speed dramatically (can be any valid css height value)
         layout:"fitData",
         responsiveLayout:"hide", // enable responsive layouts
         ajaxURL:url,
         ajaxParams:params,
         //Define Table Columns
         columns:[
            {title:"Id",           field:"partWasherEntryId", hozAlign:"left", visible:false},
            {title:"Job #",        field:"jobNumber",         hozAlign:"left", responsive:0, headerFilter:true},
            {title:"WC #",         field:"wcNumber",          hozAlign:"left", responsive:0, headerFilter:true},
            {title:"Operator",     field:"operatorName",      hozAlign:"left", responsive:0, headerFilter:true},
            {title:"Mfg. Date",    field:"manufactureDate",   hozAlign:"left", responsive:0, headerFilter:true,
               formatter:"datetime",  // Requires moment.js 
               formatterParams:{
                  outputFormat:"MM/DD/YYYY",
                  invalidPlaceholder:"---"
               }
            },
            {title:"Washer",       field:"washerName",        hozAlign:"left", responsive:0, headerFilter:true},
            {title:"Wash Date",    field:"dateTime",          hozAlign:"left", responsive:0,
               formatter:function(cell, formatterParams, onRendered){
                  var cellValue = "---";
                  
                  var date = new Date(cell.getValue());

                  if (date.getTime() === date.getTime())  // check for valid date
                  {
                     var cellValue = formatDate(date);
                     
                     if (cell.getRow().getData().isNew)
                     {
                        cellValue += "&nbsp<span class=\"new-indicator\">new</div>";
                     }
                  }

                  return (cellValue);
              }
            },
            {title:"Wash Time",    field:"dateTime",          hozAlign:"left", responsive:0,
               formatter:"datetime",  // Requires moment.js 
               formatterParams:{
                  outputFormat:"hh:mm A",
                  invalidPlaceholder:"---"
               }
            },            
            {title:"Basket Count", field:"panCount",          hozAlign:"left", responsive:2,
               formatter:function(cell, formatterParams, onRendered){
                  var cellValue = cell.getValue();
                  
                  if (cell.getRow().getData().panCountMismatch)
                  {
                     var totalPartWeightLogPanCount = cell.getRow().getData().totalPartWeightLogPanCount;
                     var totalPartWasherLogPanCount = cell.getRow().getData().totalPartWasherLogPanCount;
                     
                     var mismatch = "&nbsp<span class=\"mismatch-indicator\">mismatch</span>";
                     cellValue += mismatch;
                  }

                  return (cellValue);
               },
               tooltip:function(cell){
                  var toolTip = "";
                  
                  if (cell.getRow().getData().panCountMismatch)
                  {
                     var totalPartWeightLogPanCount = cell.getRow().getData().totalPartWeightLogPanCount;
                     var totalPartWasherLogPanCount = cell.getRow().getData().totalPartWasherLogPanCount;
                     
                     toolTip = "wash log = " + totalPartWasherLogPanCount + "; weight log = " + totalPartWeightLogPanCount;
                  }

                  return (toolTip);                  
               }
            },
            {title:"Part Count",   field:"partCount",         hozAlign:"left", responsive:1},
            {title:"", field:"delete", responsive:0,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ],
         cellClick:function(e, cell){
            var entryId = parseInt(cell.getRow().getData().partWasherEntryId);
            
            if (cell.getColumn().getField() == "delete")
            {
               onDeletePartWasherEntry(entryId);
            }
            else // Any other column
            {
               // Open time card for viewing/editing.
               document.location = "<?php echo $ROOT?>/partWasherLog/partWasherLogEntry.php?entryId=" + entryId;               
            }
         },
         rowClick:function(e, row){
            // No row click function needed.
         },
      });

      function updateFilter(event)
      {
         if (document.readyState === "complete")
         {
            var filterId = event.srcElement.id;
   
            if ((filterId == "start-date-filter") ||
                (filterId == "end-date-filter"))
            {
               var url = getTableQuery();
               var params = getTableQueryParams();

               table.setData(url, params)
               .then(function(){
                  // Run code after table has been successfuly updated
               })
               .catch(function(error){
                  // Handle error loading data
               });

               if (filterId == "start-date-filter")
               {
                  setSession("partWasher.filter.startDate", document.getElementById("start-date-filter").value);
               }
               else if (filterId == "end-date-filter")
               {
                  setSession("partWasher.filter.endDate", document.getElementById("end-date-filter").value);
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
         var startDateFilter = document.querySelector('#start-date-filter');
         var endDateFilter = document.querySelector('#end-date-filter');
         
         if ((startDateFilter != null) && (endDateFilter != null))
         {
            var today = new Date();
            
            startDateFilter.value = formattedDate(today); 
            endDateFilter.value = formattedDate(today);

            startDateFilter.dispatchEvent(new Event('change'));
            endDateFilter.dispatchEvent(new Event('change'));  // TODO: Avoid calling this!  "An active ajax request was blocked ..."
         }         
      }

      function filterYesterday()
      {
         var startDateFilter = document.querySelector('#start-date-filter');
         var endDateFilter = document.querySelector('#end-date-filter');
         
         if ((startDateFilter != null) && (endDateFilter != null))
         {
            var yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            
            startDateFilter.value = formattedDate(yesterday); 
            endDateFilter.value = formattedDate(yesterday);

            startDateFilter.dispatchEvent(new Event('change'));
            endDateFilter.dispatchEvent(new Event('change'));  // TODO: Avoid calling this!  "An active ajax request was blocked ..."
         }      
      }

      // Setup event handling on all DOM elements.
      document.getElementById("start-date-filter").addEventListener("change", updateFilter);      
      document.getElementById("end-date-filter").addEventListener("change", updateFilter);
      document.getElementById("today-button").onclick = filterToday;
      document.getElementById("yesterday-button").onclick = filterYesterday;
      document.getElementById("new-log-entry-button").onclick = function(){location.href = 'partWasherLogEntry.php';};
      document.getElementById("download-link").onclick = function(){table.download("csv", "<?php echo getReportFilename() ?>", {delimiter:"."})};

      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
      document.getElementById("menu-button").onclick = function(){document.getElementById("menu").classList.toggle('shown');};
   </script>
   
</body>

</html>
