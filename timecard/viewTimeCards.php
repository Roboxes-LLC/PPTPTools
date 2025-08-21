<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once '../common/authentication.php';
require_once '../common/database.php';
require_once '../common/filterDateType.php';
require_once '../common/header.php';
require_once '../common/isoInfo.php';
require_once '../common/jobInfo.php';
require_once '../common/newIndicator.php';
require_once '../common/permissions.php';
require_once '../common/roles.php';
require_once '../common/timeCardInfo.php';
require_once '../common/userInfo.php';
require_once '../common/version.php';

function getFilterDateType()
{
   $filterDateType = FilterDateType::ENTRY_DATE;
   
   if (isset($_SESSION["timeCard.filter.dateType"]))
   {
      $filterDateType = $_SESSION["timeCard.filter.dateType"];
   }
   
   return ($filterDateType);
}

function getFilterStartDate()
{
   $startDate = Time::now("Y-m-d");
   
   if (isset($_SESSION["timeCard.filter.startDate"]))
   {
      $startDate = $_SESSION["timeCard.filter.startDate"];
   }

   // Convert to Javascript date format.
   $startDate = Time::toJavascriptDate($startDate);
   
   return ($startDate);
}

function getFilterEndDate()
{
   $endDate = Time::now("Y-m-d");
   
   if (isset($_SESSION["timeCard.filter.endDate"]))
   {
      $endDate = $_SESSION["timeCard.filter.endDate"];
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
   
   $filename = "TimeCards_" . $dateString . ".csv";
   
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
   <link rel="stylesheet" type="text/css" href="/thirdParty/tabulator/css/tabulator.min.css<?php echo versionQuery();?>"/>
   
   <link rel="stylesheet" type="text/css" href="/common/theme.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="/common/common.css<?php echo versionQuery();?>"/>
   
   <script src="/thirdParty/tabulator/js/tabulator.min.js"></script>
   <script src="/thirdParty/luxon/luxon.min.js<?php echo versionQuery();?>"></script>
   
   <script src="/common/barcodeScanner.js<?php echo versionQuery();?>"></script>   
   <script src="/common/common.js<?php echo versionQuery();?>"></script>
   <script src="/common/validate.js<?php echo versionQuery();?>"></script>
   <script src="/script/common/common.js<?php echo versionQuery();?>"></script>
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script>  
   <script src="timeCard.js<?php echo versionQuery();?>"></script>
      
</head>

<body class="flex-vertical flex-top flex-left">

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading">Time Cards</div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
                  
         <div id="description" class="description">Time cards record the time a machine operator spends working on a job, as well as a part count for that run.</div>
         
         <br>
         
         <div class="flex-horizontal flex-v-center flex-left">
            <select id="date-type-filter"><?php echo FilterDateType::getOptions([FilterDateType::ENTRY_DATE, FilterDateType::MANUFACTURING_DATE], getFilterDateType()) ?></select>
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
        
         <button id="new-time-card-button" class="accent-button">New Time Card</button>

         <br>
        
         <div id="time-card-table"></div>
         
         <br> 
        
         <div id="download-link" class="download-link">Download CSV file</div>
         
         <div id="print-link" class="download-link">Print</div>         
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::TIME_CARD ?>); 
   
      preserveSession();

      function getTableQuery()
      {
         return ("<?php echo $ROOT ?>/api/timeCardData/");
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
      
      // Create Tabulator on DOM element time-card-table.
      var table = new Tabulator("#time-card-table", {
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
         // Printing
         printAsHtml:true,
         printRowRange:"all",
         printHeader:"<h1>Timecards<h1>",
         // Columns
         index:"timeCardId",
         columns:[
            {title:"Id",           field:"timeCardId",      visible:false},
            {title:"Ticket",       field:"panTicketCode",   headerFilter:true,
               formatter:function(cell, formatterParams, onRendered){
                  let panTicketCode = cell.getRow().getData().panTicketCode;
                  return (`<i class="material-icons icon-button">receipt</i>&nbsp<div>${panTicketCode}`);
               },
               formatterPrint:function(cell, formatterParams, onRendered){
                  return (cell.getValue());
               }  
            },                   
            {title:"Date",         field:"dateTime",        print:true,
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
              },
            },
            {title:"Mfg. Date",    field:"manufactureDate", headerFilter:"input",
               formatter:"datetime",  // Requires luxon.js 
               formatterParams:{
                  outputFormat:"M/d/yyyy",
                  invalidPlaceholder:"---"
               }
            },
            {title:"Operator",     field:"operator",       headerFilter:true},
            {title:"Job #",        field:"jobNumber",       headerFilter:true},
            {title:"WC #",         field:"wcLabel",         headerFilter:true},
            {title:"Heat #",       field:"materialNumber"},
            {title:"Shift Time",   field:"shiftTime",
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
            {title:"Run Time",     field:"runTime",
               contextMenu:function(component){
                  var menu = [];

                  if (component.getRow().getData().runTimeRequiresApproval)
                  {
                     menu.push({
                        label:"Approve",
                        action:function(e, cell){
                           updateRunTimeApproval(component.getRow().getData().timeCardId, true);
                        }
                     });
                     
                     menu.push({
                        label:"Disapprove",
                        action:function(e, cell){
                           updateRunTimeApproval(component.getRow().getData().timeCardId, false);
                        }
                     });
                  }
                  
                  return (menu);
               },
               formatter:function(cell, formatterParams, onRendered){

                  var minutes = parseInt(cell.getValue());
                  
                  var cellValue = Math.floor(minutes / 60) + ":" + ("0" + (minutes % 60)).slice(-2);
                  
                  if (cell.getRow().getData().incompleteRunTime)
                  {
                     cellValue += "&nbsp<span class=\"incomplete-indicator\">incomplete</div>";
                  }
                  else if (cell.getRow().getData().runTimeRequiresApproval)
                  {
                     if (cell.getRow().getData().runTimeApprovedByName)
                     {
                        cellValue += "&nbsp<span class=\"approved-indicator\">approved</div>";
                     }
                     else
                     {
                        cellValue += "&nbsp<span class=\"unapproved-indicator\">unapproved</div>";
                     }
                  }     
                  
                  return (cellValue);
               },
               formatterPrint:function(cell, formatterParams, onRendered){

                  var minutes = parseInt(cell.getValue());
                  
                  var cellValue = Math.floor(minutes / 60) + ":" + ("0" + (minutes % 60)).slice(-2);
                  
                  return (cellValue);
                },
                tooltip:function(cell){
                   var toolTip = "";

                   if (cell.getRow().getData().runTimeApprovedByName)
                   {
                      toolTip = "Approved by " + cell.getRow().getData().runTimeApprovedByName;
                   }

                   return (toolTip);                  
                }                
            },
            {title:"Setup Time",   field:"setupTime",
               contextMenu:function(component){
                  var menu = [];

                  if (component.getRow().getData().setupTimeRequiresApproval)
                  {
                     menu.push({
                        label:"Approve",
                        action:function(e, cell){
                           updateSetupTimeApproval(component.getRow().getData().timeCardId, true);
                        }
                     });
                     
                     menu.push({
                        label:"Disapprove",
                        action:function(e, cell){
                           updateSetupTimeApproval(component.getRow().getData().timeCardId, false);
                        }
                     });
                  }
                  
                  return (menu);
               },            
               formatter:function(cell, formatterParams, onRendered){

                  var minutes = parseInt(cell.getValue());
                  
                  var cellValue = Math.floor(minutes / 60) + ":" + ("0" + (minutes % 60)).slice(-2);

                  if (cell.getRow().getData().setupTimeRequiresApproval)
                  {
                     if (cell.getRow().getData().setupTimeApprovedByName)
                     {
                        cellValue += "&nbsp<span class=\"approved-indicator\">approved</div>";
                     }
                     else
                     {
                        cellValue += "&nbsp<span class=\"unapproved-indicator\">unapproved</div>";
                     }
                  }                  
                  
                  return (cellValue);
               },
               formatterPrint:function(cell, formatterParams, onRendered){

                  var minutes = parseInt(cell.getValue());
                  
                  var cellValue = Math.floor(minutes / 60) + ":" + ("0" + (minutes % 60)).slice(-2);
                  
                  return (cellValue);
                },                
                tooltip:function(cell){
                   var toolTip = "";

                   if (cell.getRow().getData().setupTimeApprovedByName)
                   {
                      toolTip = "Approved by " + cell.getRow().getData().setupTimeApprovedByName;
                   }

                   return (toolTip);                  
                }
            },
            {title:"Basket Count", field:"panCount",
               formatter:function(cell, formatterParams, onRendered){
                  var cellValue = cell.getValue();
                  
                  if (cell.getRow().getData().incompletePanCount)
                  {
                     cellValue += "&nbsp<span class=\"incomplete-indicator\">incomplete</div>";
                  }
                  
                  return (cellValue);
                },
                formatterPrint:function(cell, formatterParams, onRendered){
                   return (cell.getValue());
                }
            },
            {title:"Part Count",   field:"partCount",
               formatter:function(cell, formatterParams, onRendered){
                  var cellValue = cell.getValue();
                  
                  if (cell.getRow().getData().incompletePartCount)
                  {
                     cellValue += "&nbsp<span class=\"incomplete-indicator\">incomplete</div>";
                  }
                  
                  return (cellValue);
                },
                formatter:function(cell, formatterParams, onRendered){
                   return (cell.getValue());
                }                
            },
            {title:"Scrap Count",  field:"scrapCount"},
            {title:"Efficiency",   field:"efficiency", 
               formatter:function(cell, formatterParams, onRendered){
                  return (parseFloat(cell.getValue()).toFixed(2) + "%");
                }
            },
            {title:"Parts Taken<br>Early", field:"partsTakenEarly", hozAlign:"center", formatter:"tickCross", formatterParams:{crossElement:""}},
            {title:"", field:"delete", hozAlign:"center", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ]
      });
      
      table.on("cellClick", function(e, cell) {
         var timeCardId = parseInt(cell.getRow().getData().timeCardId);

         if (cell.getColumn().getField() == "panTicketCode")
         {
            document.location = "<?php echo $ROOT?>/panTicket/viewPanTicket.php?panTicketId=" + timeCardId;
         }            
         else if (cell.getColumn().getField() == "delete")
         {
            onDeleteTimeCard(timeCardId);
         }
         else // Any other column
         {
            // Open time card for viewing/editing.
            document.location = "<?php echo $ROOT?>/timecard/viewTimeCard.php?timeCardId=" + timeCardId;               
         }
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
                  setSession("timeCard.filter.dateType", document.getElementById("date-type-filter").value);
               }
               else if (filterId == "start-date-filter")
               {
                  setSession("timeCard.filter.startDate", document.getElementById("start-date-filter").value);
               }
               else if (filterId == "end-date-filter")
               {
                  setSession("timeCard.filter.endDate", document.getElementById("end-date-filter").value);
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
      window.addEventListener('resize', function() { table.redraw(); });
      document.getElementById("date-type-filter").addEventListener("change", updateFilter);
      document.getElementById("start-date-filter").addEventListener("change", updateFilter);      
      document.getElementById("end-date-filter").addEventListener("change", updateFilter);
      document.getElementById("today-button").onclick = filterToday;
      document.getElementById("yesterday-button").onclick = filterYesterday;
      document.getElementById("new-time-card-button").onclick = function(){location.href = 'viewTimeCard.php';};
      document.getElementById("download-link").onclick = function(){table.download("csv", "<?php echo getReportFilename() ?>", {delimiter:"."})};
      document.getElementById("print-link").onclick = function(){table.print("active", true);};

      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
      
      // Listen for barcodes.
      var barcodeScanner = new BarcodeScanner();
      barcodeScanner.onBarcode = onBarcode;
      
   </script>
   
</body>

</html>