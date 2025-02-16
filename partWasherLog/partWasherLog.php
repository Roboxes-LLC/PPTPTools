<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once '../common/authentication.php';
require_once '../common/database.php';
require_once '../common/filterDateType.php';
require_once '../common/header.php';
require_once '../common/jobInfo.php';
require_once '../common/newIndicator.php';
require_once '../common/partWasherEntry.php';
require_once '../common/permissions.php';
require_once '../common/timeCardInfo.php';
require_once '../common/version.php';

function getFilterDateType()
{
   $filterDateType = FilterDateType::WASH_DATE;
   
   if (isset($_SESSION["partWasher.filter.dateType"]))
   {
      $filterDateType = $_SESSION["partWasher.filter.dateType"];
   }
   
   return ($filterDateType);
}

function getFilterStartDate()
{
   $startDate = Time::now("Y-m-d");
   
   $timeCardId = getTimeCardId();
   
   // If a time card is specified, use the manufacture date.
   if ($timeCardId != TimeCardInfo::UNKNOWN_TIME_CARD_ID)
   {
      $timeCardInfo = TimeCardInfo::load($timeCardId);
      if ($timeCardInfo)
      {
         $startDate = $timeCardInfo->manufactureDate;
      }
   }
   // Otherwise, pull from the value stored in the $_SESSION variable.
   else
   {
      if (isset($_SESSION["partWasher.filter.startDate"]))
      {
         $startDate = $_SESSION["partWasher.filter.startDate"];
      }
   }

   // Convert to Javascript date format.
   $startDate = Time::toJavascriptDate($startDate);
   
   return ($startDate);
}

function getFilterEndDate()
{
   $endDate = Time::now("Y-m-d");
   
   $timeCardId = getTimeCardId();
   
   // If a time card is specified, use the manufacture date.
   if ($timeCardId != TimeCardInfo::UNKNOWN_TIME_CARD_ID)
   {
      $timeCardInfo = TimeCardInfo::load($timeCardId);
      if ($timeCardInfo)
      {
         $endDate = $timeCardInfo->manufactureDate;
      }
   }
   // Otherwise, pull from the value stored in the $_SESSION variable.
   else
   {
      if (isset($_SESSION["partWasher.filter.endDate"]))
      {
         $endDate = $_SESSION["partWasher.filter.endDate"];
      }
   }
   
   // Convert to Javascript date format.
   $endDate = Time::toJavascriptDate($endDate);
   
   return ($endDate);
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

function getTimeCardId()
{
   $timeCardId = TimeCardInfo::UNKNOWN_TIME_CARD_ID;
   
   $params = Params::parse();
   
   if ($params->keyExists("timeCardId"))
   {
      $timeCardId = $params->getInt("timeCardId");
   }
   
   return ($timeCardId);
}

function getDateSelectionDisabled()
{
   return ((getTimeCardId() != TimeCardInfo::UNKNOWN_TIME_CARD_ID) ? "disabled" : "");
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
   <script src="/thirdParty/luxon/luxon.min.js<?php echo versionQuery();?>"></script>
   
   <script src="/common/barcodeScanner.js<?php echo versionQuery();?>"></script>
   <script src="/common/common.js<?php echo versionQuery();?>"></script>
   <script src="/common/validate.js<?php echo versionQuery();?>"></script>
   <script src="/script/common/common.js<?php echo versionQuery() ?>"></script>
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script>   
   <script src="partWasherLog.js<?php echo versionQuery();?>"></script>
      
</head>

<body class="flex-vertical flex-top flex-left">

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading">Part Washer Log</div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
                  
         <div id="description" class="description">The Part Washer Log provides an up-to-the-minute view into the part washing process.  Here you can track when your parts come through the wash line, and in what volume.</div>
         
         <br>
         
         <div class="flex-horizontal flex-v-center flex-left">
            <input type="hidden" id="time-card-id-filter" value="<?php echo getTimeCardId()?>">
            <select id="date-type-filter"><?php echo FilterDateType::getOptions([FilterDateType::WASH_DATE, FilterDateType::MANUFACTURING_DATE], getFilterDateType()) ?></select>
            &nbsp;&nbsp;            
            <div style="white-space: nowrap">Start</div>
            &nbsp;
            <input id="start-date-filter" type="date" value="<?php echo getFilterStartDate()?>" <?php echo getDateSelectionDisabled();?>>
            &nbsp;&nbsp;
            <div style="white-space: nowrap">End</div>
            &nbsp;
            <input id="end-date-filter" type="date" value="<?php echo getFilterEndDate()?>" <?php echo getDateSelectionDisabled();?>>
            &nbsp;&nbsp;
            <button id="today-button" class="small-button" <?php echo getDateSelectionDisabled();?>>Today</button>
            &nbsp;&nbsp;
            <button id="yesterday-button" class="small-button" <?php echo getDateSelectionDisabled();?>>Yesterday</button>
         </div>
         
         <br>
        
         <button id="new-log-entry-button" class="accent-button">New Log Entry</button>

         <br>
        
         <div id="part-washer-log-table"></div>

         <br> 
        
         <div id="download-link" class="download-link">Download CSV file</div>
         
         <div id="print-link" class="download-link">Print</div>
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::PART_WASH ?>); 
   
      preserveSession();

      function getTableQuery()
      {
         return ("<?php echo $ROOT ?>/api/partWasherLogData/");
      }

      function getTableQueryParams()
      {
         
         var params = new Object();
         params.dateType =  document.getElementById("date-type-filter").value;
         params.startDate =  document.getElementById("start-date-filter").value;
         params.endDate =  document.getElementById("end-date-filter").value;
         params.timeCardId = document.getElementById("time-card-id-filter").value;

         return (params);
      }
      
      var url = getTableQuery();
      var params = getTableQueryParams();
      
      // Create Tabulator on DOM element part-washer-log-table.
      var table = new Tabulator("#part-washer-log-table", {
         // Data
         ajaxURL:url,
         ajaxParams:params,
         // Layout
         layout:"fitData",
         columnDefaults:{
            hozAlign:"left", 
            vertAlign:"middle"
         },
         persistence:true,
         // Print
         printAsHtml:true,
         printRowRange:"all",
         printHeader:"<h1>Part Washer Log<h1>",
         // Columns
         columns:[
            {title:"Id",           field:"partWasherEntryId", visible:false},
            {title:"Ticket",       field:"panTicketCode",     headerFilter:true,
               formatter:function(cell, formatterParams, onRendered){
                  var cellValue = "";
                  
                  var timeCardId = cell.getRow().getData().timeCardId;
                  
                  if (timeCardId != 0)
                  {
                     cellValue = "<i class=\"material-icons icon-button\">receipt</i>&nbsp" + cell.getRow().getData().panTicketCode;
                  }
                  
                  return (cellValue);
               },
               formatterPrint:function(cell, formatterParams, onRendered){
                  return (cell.getValue());
              }                 
            },  
            {title:"Job #",        field:"jobNumber",         headerFilter:true},
            {title:"WC #",         field:"wcLabel",           headerFilter:true},
            {title:"Operator",     field:"operatorName",      headerFilter:true},
            {title:"Mfg. Date",    field:"manufactureDate",   headerFilter:"input",
               formatter:"datetime",  // Requires luxon.js 
               formatterParams:{
                  outputFormat:"M/d/yyyy",
                  invalidPlaceholder:"---"
               }
            },
            {title:"Washer",       field:"washerName",        headerFilter:true},
            {title:"Wash Date",    field:"washDate",
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
              },
              formatterPrint:function(cell, formatterParams, onRendered){
                 var cellValue = "---";
                  
                 var date = new Date(cell.getValue());

                 if (date.getTime() === date.getTime())  // check for valid date
                 {
                    var cellValue = formatDate(date);
                 }

                 return (cellValue);
              }
            },
            {title:"Wash Time",    field:"washTime",
               formatter:"datetime",
               formatterParams:{
                  outputFormat:"h:mm a",
                  invalidPlaceholder:"---"
               }
            },            
            {title:"Basket Count", field:"panCount",
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
               formatterPrint:function(cell, formatterParams, onRendered){
                  return (cell.getValue());
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
            {title:"Part Count",   field:"partCount"},
            {title:"", field:"delete",                        hozAlign:"center", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ]
      });
      
      this.table.on("cellClick", function(e, cell) {
         var entryId = parseInt(cell.getRow().getData().partWasherEntryId);
         
         var timeCardId = cell.getRow().getData().timeCardId;
         
         if ((cell.getColumn().getField() == "panTicketCode") &&
             (cell.getRow().getData().timeCardId != 0))
         {               
            document.location = "<?php echo $ROOT?>/panTicket/viewPanTicket.php?panTicketId=" + timeCardId;
         }  
         else if (cell.getColumn().getField() == "delete")
         {
            onDeletePartWasherEntry(entryId);
         }
         else // Any other column
         {
            // Open time card for viewing/editing.
            document.location = "<?php echo $ROOT?>/partWasherLog/partWasherLogEntry.php?entryId=" + entryId;               
         }
      }.bind(this));

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
                  setSession("partWasher.filter.dateType", document.getElementById("date-type-filter").value);
               }
               else if (filterId == "start-date-filter")
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
      document.getElementById("date-type-filter").addEventListener("change", updateFilter);
      document.getElementById("start-date-filter").addEventListener("change", updateFilter);      
      document.getElementById("end-date-filter").addEventListener("change", updateFilter);
      document.getElementById("today-button").onclick = filterToday;
      document.getElementById("yesterday-button").onclick = filterYesterday;
      document.getElementById("new-log-entry-button").onclick = function(){location.href = 'partWasherLogEntry.php';};
      document.getElementById("download-link").onclick = function(){table.download("csv", "<?php echo getReportFilename() ?>", {delimiter:","})};
      document.getElementById("print-link").onclick = function(){table.print(false, true);};

      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
      
      // Listen for barcodes.
      var barcodeScanner = new BarcodeScanner();
      barcodeScanner.onBarcode = onBarcode;
      
   </script>
   
</body>

</html>
