<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once '../common/authentication.php';
require_once '../common/database.php';
require_once '../common/filterDateType.php';
require_once '../common/header.php';
require_once '../common/maintenanceEntry.php';
require_once '../common/version.php';

function getFilterDateType()
{
   $filterDateType = FilterDateType::MAINTENANCE_DATE;
   
   if (isset($_SESSION["maintenance.filter.dateType"]))
   {
      $filterDateType = $_SESSION["maintenance.filter.dateType"];
   }
   
   return ($filterDateType);
}

function getFilterStartDate()
{
   $startDate = Time::now("Y-m-d");
   
   if (isset($_SESSION["maintenance.filter.startDate"]))
   {
      $startDate = $_SESSION["maintenance.filter.startDate"];
   }

   // Convert to Javascript date format.
   $dateTime = new DateTime($startDate, new DateTimeZone('America/New_York'));  // TODO: Replace
   $startDate = $dateTime->format(Time::$javascriptDateFormat);
   
   return ($startDate);
}

function getFilterEndDate()
{
   $startDate = Time::now("Y-m-d");
   
   if (isset($_SESSION["maintenance.filter.endDate"]))
   {
      $startDate = $_SESSION["maintenance.filter.endDate"];
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
   
   $filename = "MaintenanceLog_" . $dateString . ".csv";
   
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
   <link rel="stylesheet" type="text/css" href="../thirdParty/tabulator/css/tabulator.min.css<?php echo versionQuery();?>"/>
   
   <link rel="stylesheet" type="text/css" href="../common/theme.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="../common/common.css<?php echo versionQuery();?>"/>
   
   <script src="../thirdParty/tabulator/js/tabulator.min.js<?php echo versionQuery();?>"></script>
   <script src="../thirdParty/moment/moment.min.js<?php echo versionQuery();?>"></script>
   
   <script src="/common/common.js<?php echo versionQuery();?>"></script>
   <script src="/common/validate.js<?php echo versionQuery();?>"></script>
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script>
   <script src="maintenanceLog.js<?php echo versionQuery();?>"></script>
      
</head>

<body class="flex-vertical flex-top flex-left">

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading">Maintenance Log</div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div id="description" class="description">The Maintenace Log records upgrades and repairs to your work centers.</div>
         
         <br>
         
         <div class="flex-horizontal flex-v-center flex-left">
            <select id="date-type-filter"><?php echo FilterDateType::getOptions([FilterDateType::ENTRY_DATE, FilterDateType::MAINTENANCE_DATE], getFilterDateType()) ?></select>
            &nbsp;&nbsp;
            <div style="white-space: nowrap">Start</div>
            &nbsp;
            <input id="start-date-filter" type="date" value="<?php echo getFilterStartDate()?>">
            &nbsp;&nbsp;
            <div style="white-space: nowrap">End</div>
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
        
         <div id="maintenance-log-table"></div>

         <br> 
        
         <div id="download-link" class="download-link">Download CSV file</div>
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::MAINTENANCE_LOG ?>);  
   
      preserveSession();

      function getTableQuery()
      {
         return ("<?php echo $ROOT ?>/api/maintenanceLogData/");
      }

      function getTableQueryParams()
      {
         
         var params = new Object();
         params.dateType =  document.getElementById("date-type-filter").value;
         params.startDate =  document.getElementById("start-date-filter").value;
         params.endDate =  document.getElementById("end-date-filter").value;

         return (params);
      }
      
      var url = getTableQuery();
      var params = getTableQueryParams();
      
      // Create Tabulator on DOM element maintenance-log-table.
      var table = new Tabulator("#maintenance-log-table", {
         maxHeight:500,  // set height of table (in CSS or here), this enables the Virtual DOM and improves render speed dramatically (can be any valid css height value)
         layout:"fitData",
         responsiveLayout:"hide", // enable responsive layouts
         cellVertAlign:"middle",
         ajaxURL:url,
         ajaxParams:params,
         //Define Table Columns
         columns:[
            {title:"Id",          field:"maintenanceEntryId",       hozAlign:"left", visible:false},
            {title:"Entry Date",  field:"dateTime",                 hozAlign:"left",
               formatter:"datetime",  // Requires moment.js 
               formatterParams:{
                  outputFormat:"MM/DD/YYYY",
                  invalidPlaceholder:"---"
               }
            },
            {title:"Maint. Date", field:"maintenanceDateTime",      hozAlign:"left",
               formatter:"datetime",  // Requires moment.js 
               formatterParams:{
                  outputFormat:"MM/DD/YYYY",
                  invalidPlaceholder:"---"
               }
            },
            {title:"Techician",   field:"technicianName",            hozAlign:"left", headerFilter:true},            
            {title:"Job #",       field:"jobNumber",                 hozAlign:"left", headerFilter:true},
            {title:"Equipment",   field:"equipmentName",             hozAlign:"left", headerFilter:true},
            {title:"Maint. Type", field:"typeLabel",                 hozAlign:"left", headerFilter:true},
            {title:"Category",    field:"categoryLabel",             hozAlign:"left", headerFilter:true},
            {title:"Subcategory", field:"subcategoryLabel",          hozAlign:"left", headerFilter:true},
            {title:"Shift Time",  field:"shiftTime",                 hozAlign:"left",
               formatter:function(cell, formatterParams, onRendered){
                  var minutes = parseInt(cell.getValue());
                  var cellValue = Math.floor(minutes / 60) + ":" + ("0" + (minutes % 60)).slice(-2);
                  return (cellValue);
               }
            },
            {title:"Maint. Time", field:"maintenanceTime",           hozAlign:"left",
               formatter:function(cell, formatterParams, onRendered){
                  var minutes = parseInt(cell.getValue());
                  var cellValue = Math.floor(minutes / 60) + ":" + ("0" + (minutes % 60)).slice(-2);
                  return (cellValue);
               }
            },
            {title:"Part #",      field:"partNumber",                hozAlign:"left", headerFilter:true},
            {title:"Comments",    field:"comments",                  hozAlign:"left"},
            {title:"", field:"delete", responsive:0,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ],
         cellClick:function(e, cell){
            var entryId = parseInt(cell.getRow().getData().maintenanceEntryId);
         
            if (cell.getColumn().getField() == "delete")
            {
               onDeleteMaintenanceEntry(entryId);
            }
            else // Any other column
            {
               // Open maintenance log entry for viewing/editing.
               document.location = "<?php echo $ROOT?>/maintenanceLog/maintenanceLogEntry.php?entryId=" + entryId;               
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
                (filterId == "date-type-filter") ||
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

               if (filterId == "date-type-filter")
               {
                  setSession("maintenance.filter.dateType", document.getElementById("date-type-filter").value);
               }
               else if (filterId == "start-date-filter")
               {
                  setSession("maintenance.filter.startDate", document.getElementById("start-date-filter").value);
               }
               else if (filterId == "end-date-filter")
               {
                  setSession("maintenance.filter.endDate", document.getElementById("end-date-filter").value);
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
      document.getElementById("date-type-filter").addEventListener("change", updateFilter);
      document.getElementById("start-date-filter").addEventListener("change", updateFilter);      
      document.getElementById("end-date-filter").addEventListener("change", updateFilter);
      document.getElementById("today-button").onclick = filterToday;
      document.getElementById("yesterday-button").onclick = filterYesterday;
      document.getElementById("new-log-entry-button").onclick = function(){location.href = 'maintenanceLogEntry.php';};
      document.getElementById("download-link").onclick = function(){table.download("csv", "<?php echo getReportFilename() ?>", {delimiter:"."})};

      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};

   </script>
   
</body>

</html>
