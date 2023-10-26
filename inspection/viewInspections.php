<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once '../common/database.php';
require_once '../common/header.php';
require_once '../common/inspection.php';
require_once '../common/inspectionTemplate.php';
require_once '../common/newIndicator.php';
require_once '../common/permissions.php';

function getFilterStartDate()
{
   $startDate = Time::now("Y-m-d");
   
   if (isset($_SESSION["inspection.filter.startDate"]))
   {
      $startDate = $_SESSION["inspection.filter.startDate"];
   }
   
   // Convert to Javascript date format.
   $dateTime = new DateTime($startDate, new DateTimeZone('America/New_York'));  // TODO: Replace
   $startDate = $dateTime->format(Time::$javascriptDateFormat);
   
   return ($startDate);
}

function getFilterEndDate()
{
   $startDate = Time::now("Y-m-d");
   
   if (isset($_SESSION["inspection.filter.endDate"]))
   {
      $startDate = $_SESSION["inspection.filter.endDate"];
   }
   
   // Convert to Javascript date format.
   $dateTime = new DateTime($startDate, new DateTimeZone('America/New_York'));  // TODO: Replace
   $startDate = $dateTime->format(Time::$javascriptDateFormat);
   
   return ($startDate);
}

function getFilterInspectionType()
{
   $inspectionType = InspectionType::UNKNOWN;
   
   if (isset($_SESSION["inspection.filter.inspectionType"]))
   {
      $inspectionType = intval($_SESSION["inspection.filter.inspectionType"]);
   }
   
   return ($inspectionType);
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
   
   $filename = "Inspections_" . $dateString . ".csv";
   
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
   <link rel="stylesheet" type="text/css" href="inspection.css"/>
   
   <script src="../thirdParty/tabulator/js/tabulator.min.js"></script>
   <script src="../thirdParty/moment/moment.min.js"></script>
   
   <script src="/common/common.js"></script>
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script>   
   <script src="inspection.js"></script>
      
</head>

<body class="flex-vertical flex-top flex-left">

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading">Inspections</div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div id="description" class="description">Part inspections allow Pittsburgh Precision quality assurance experts the chance to catch productions problems before they result in signficant waste or delay.</div>

         <br>
         
         <div class="flex-horizontal flex-left flex-wrap">
            <div class="flex-horizontal">
               <div style="white-space: nowrap">Inspection type</div>
               &nbsp;
               <select id="inspection-type-filter"><?php echo getInspectionTypeOptions(getFilterInspectionType(), true, [InspectionType::OASIS]); ?></select>
            </div>
            &nbsp;&nbsp;
            <div class="flex-horizontal">
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
         </div>
         
         <br>
        
         <button id="new-inspection-button" class="accent-button">New Inspection</button>

         <br>
        
         <div id="inspection-table"></div>

         <br> 
        
         <div id="download-link" class="download-link">Download CSV file</div>
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::INSPECTION ?>); 
      
      preserveSession();

      function getTableQuery()
      {
         return ("<?php echo $ROOT ?>/api/inspectionData/");
      }

      function getTableQueryParams()
      {
         var params = new Object();

         params.inspectionType = document.getElementById("inspection-type-filter").value;
         params.startDate =  document.getElementById("start-date-filter").value;
         params.endDate =  document.getElementById("end-date-filter").value;

         return (params);
      }

      var url = getTableQuery();
      var params = getTableQueryParams();
      
      // Create Tabulator on DOM element time-card-table.
      var table = new Tabulator("#inspection-table", {
         //height:500, // set height of table (in CSS or here), this enables the Virtual DOM and improves render speed dramatically (can be any valid css height value)
         layout:"fitData",
         responsiveLayout:"hide", // enable responsive layouts
         ajaxURL:url,
         ajaxParams:params,
         //Define Table Columns
         columns:[
            {title:"Id",              field:"inspectionId",        hozAlign:"left", visible:false},
            {title:"",                field:"isPriority",          hozAlign:"left", responsive:1, width: 25,
               formatter:function(cell, formatterParams, onRendered){
                  let cellValue = "";
                  let isPriority = (cell.getValue() != 0);
                  
                  if (isPriority)
                  {
                     cellValue = "<i class=\"material-icons priority-icon\">priority_high</i>"
                  }

                  return (cellValue);
               }
            },
            {title:"Inspection Type", field:"inspectionTypeLabel", hozAlign:"left", responsive:1},
            {title:"Name",            field:"name",                hozAlign:"left", responsive:2, headerFilter:true},
            {title:"Inspection Date", field:"dateTime",            hozAlign:"left", responsive:0,
               formatter:"datetime",  // Requires moment.js 
               formatterParams:{
                  outputFormat:"MM/DD/YYYY",
                  invalidPlaceholder:"---"
               }
            },
            {title:"Time",            field:"dateTime",            hozAlign:"left", responsive:0,
               formatter:"datetime",  // Requires moment.js 
               formatterParams:{
                  outputFormat:"hh:mm A",
                  invalidPlaceholder:"---"
               }
            },
            {title:"Created By",      field:"authorName",          hozAlign:"left",   responsive:0, headerFilter:true},
            {title:"Inspector",       field:"inspectorName",       hozAlign:"left",   responsive:0, headerFilter:true},
            {title:"Mfg Date",        field:"mfgDate",             hozAlign:"left",   responsive:0,
               formatter:"datetime",  // Requires moment.js 
               formatterParams:{
                  outputFormat:"MM/DD/YYYY",
                  invalidPlaceholder:""
               }
            },         
            {title:"Operator",        field:"operatorName",        hozAlign:"left",   responsive:0, headerFilter:true},
            {title:"Job",             field:"jobNumber",           hozAlign:"left",   responsive:0, headerFilter:true},
            {title:"Work Center",     field:"wcLabel",             hozAlign:"left",   responsive:0, headerFilter:true},
            {title:"Success Rate",    field:"successRate",         hozAlign:"left",   responsive:3,
               formatter:function(cell, formatterParams, onRendered){
                  var count = cell.getRow().getData().samples;
                  var naCount = cell.getRow().getData().naCount;
                  var passCount = cell.getRow().getData().passCount;
                  return (passCount + "/" + (count - naCount));
               }
            },
            {title:"Status",       field:"inspectionStatus",    hozAlign:"center", responsive:0, headerFilter:true,
               formatter:function(cell, formatterParams, onRendered){
                  var label = cell.getRow().getData().inspectionStatusLabel;
                  var cssClass = cell.getRow().getData().inspectionStatusClass;
                  return ("<div class=\"inspection-status " + cssClass + "\">" + label + "</div>");
               }
            },
            {title:"",                field:"delete",                               responsive:0, <?php echo !Authentication::checkPermissions(Permission::DELETE_INSPECTION) ? "visible:false, " : "" ?>
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ],
         cellClick:function(e, cell){
            var inspectionId = parseInt(cell.getRow().getData().inspectionId);
            
            if (cell.getColumn().getField() == "delete")
            {
               onDeleteInspection(inspectionId);
            }
            else // Any other column
            {
               var inspectionType = cell.getRow().getData().inspectionType;

               // Open user for viewing/editing.
               if (inspectionType == <?php echo InspectionType::OASIS; ?>)
               {
                  document.location = "<?php echo $ROOT?>/inspection/viewOasisInspection.php?inspectionId=" + inspectionId;
               }
               else
               {
                  document.location = "<?php echo $ROOT?>/inspection/viewInspection.php?inspectionId=" + inspectionId;
               }
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
   
            if ((filterId == "inspection-type-filter") ||
                (filterId == "start-date-filter") ||
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

               if (filterId == "inspection-type-filter")
               {
                  setSession("inspection.filter.inspectionType", document.getElementById("inspection-type-filter").value);
               }
               else if (filterId == "start-date-filter")
               {
                  setSession("inspection.filter.startDate", document.getElementById("start-date-filter").value);
               }
               else if (filterId == "end-date-filter")
               {
                  setSession("inspection.filter.endDate", document.getElementById("end-date-filter").value);
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
      document.getElementById("inspection-type-filter").addEventListener("change", updateFilter);
      document.getElementById("start-date-filter").addEventListener("change", updateFilter);      
      document.getElementById("end-date-filter").addEventListener("change", updateFilter);
      document.getElementById("today-button").onclick = filterToday;
      document.getElementById("yesterday-button").onclick = filterYesterday;
      document.getElementById("new-inspection-button").onclick = function(){location.href = 'selectInspection.php';};
      document.getElementById("download-link").onclick = function(){table.download("csv", "<?php echo getReportFilename() ?>", {delimiter:"."})};
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
   </script>
   
</body>

</html>
