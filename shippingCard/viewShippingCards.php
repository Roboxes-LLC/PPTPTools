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
require_once '../common/shippingCardInfo.php';
require_once '../common/userInfo.php';
require_once '../common/version.php';

function getFilterDateType()
{
   $filterDateType = FilterDateType::ENTRY_DATE;
   
   if (isset($_SESSION["shippingCard.filter.dateType"]))
   {
      $filterDateType = $_SESSION["shippingCard.filter.dateType"];
   }
   
   return ($filterDateType);
}

function getFilterStartDate()
{
   $startDate = Time::now("Y-m-d");
   
   if (isset($_SESSION["shippingCard.filter.startDate"]))
   {
      $startDate = $_SESSION["shippingCard.filter.startDate"];
   }
   
   // Convert to Javascript date format.
   $startDate = Time::toJavascriptDate($startDate);
   
   return ($startDate);
}

function getFilterEndDate()
{
   $endDate = Time::now("Y-m-d");
   
   if (isset($_SESSION["shippingCard.filter.endDate"]))
   {
      $endDate = $_SESSION["shippingCard.filter.endDate"];
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
   
   $filename = "ShippingCards_" . $dateString . ".csv";
   
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
   <link rel="stylesheet" type="text/css" href="../thirdParty/tabulator/css/tabulator.min.css<?php echo versionQuery();?>"/>
   
   <link rel="stylesheet" type="text/css" href="../common/theme.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="../common/common.css<?php echo versionQuery();?>"/>
   
   <script src="/thirdParty/tabulator/js/tabulator.min.js<?php echo versionQuery();?>"></script>
   <script src="/thirdParty/luxon/luxon.min.js<?php echo versionQuery();?>"></script>
   
   <script src="/common/barcodeScanner.js<?php echo versionQuery();?>"></script>   
   <script src="/common/common.js<?php echo versionQuery();?>"></script>
   <script src="/common/validate.js<?php echo versionQuery();?>"></script>
   <script src="/script/common/common.js<?php echo versionQuery();?>"></script>   
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script>   
   <script src="shippingCard.js<?php echo versionQuery();?>"></script>
      
</head>

<body class="flex-vertical flex-top flex-left">

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading">Shipping Cards</div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
                  
         <div id="description" class="description">Shipping cards record the time a shipper spends packaging parts for a job.</div>
         
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
        
         <button id="new-shipping-card-button" class="accent-button">New Shipping Card</button>

         <br>
        
         <div id="shipping-card-table"></div>
         
         <br> 
        
         <div id="download-link" class="download-link">Download CSV file</div>
         
         <div id="print-link" class="download-link">Print</div>         
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::SHIPPING_CARD ?>);  
   
      preserveSession();

      function getTableQuery()
      {
         return ("<?php echo $ROOT ?>/api/shippingCardData/");
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
      
      // Create Tabulator on DOM element shipping-card-table.
      var table = new Tabulator("#shipping-card-table", {
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
         printHeader:"<h1>Shipping Cards<h1>",
         // Columns
         index:"shippingCardId",
         columns:[
            {title:"Id",           field:"shippingCardId",  visible:false},
            {title:"Ticket",       field:"panTicketCode",   headerFilter:true,
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
            {title:"Date",         field:"dateTime",
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
            {title:"Shipper",      field:"shipper",         headerFilter:true},
            {title:"Job #",        field:"jobNumber",       headerFilter:true},
            {title:"Mfg. Date",    field:"manufactureDate", headerFilter:"input",
               formatter:"datetime",
               formatterParams:{
                  outputFormat:"M/d/yyyy",
                  invalidPlaceholder:"---"
               }
            },
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
            {title:"Shipping Time",     field:"shippingTime",
               formatter:function(cell, formatterParams, onRendered){

                  var minutes = parseInt(cell.getValue());
                  
                  var cellValue = Math.floor(minutes / 60) + ":" + ("0" + (minutes % 60)).slice(-2);
                  
                  if (cell.getRow().getData().incompleteShippingTime)
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
            {title:"Activity",     field:"activityLabel",   headerFilter:true},
            {title:"Part Count",   field:"partCount",
               formatter:function(cell, formatterParams, onRendered){
                  var cellValue = cell.getValue();
                  
                  if (cell.getRow().getData().incompletePartCount)
                  {
                     cellValue += "&nbsp<span class=\"incomplete-indicator\">incomplete</div>";
                  }
                  
                  return (cellValue);
                },
                formatterPrint:function(cell, formatterParams, onRendered){
                   return (cell.getValue());
                }                
            },
            {title:"Scrap Count",  field:"scrapCount"},
            {title:"Scrap Type",   field:"scrapTypeLabel",  headerFilter:true},
            {title:"", field:"delete",                      hozAlign:"center", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ]
      });
      
      table.on("cellClick", function(e, cell) {
         var shippingCardId = parseInt(cell.getRow().getData().shippingCardId);
   
         if (cell.getColumn().getField() == "delete")
         {
            onDeleteShippingCard(shippingCardId);
         }
         else // Any other column
         {
            // Open shipping card for viewing/editing.
            document.location = "<?php echo $ROOT?>/shippingCard/viewShippingCard.php?shippingCardId=" + shippingCardId;               
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
                  setSession("shippingCard.filter.dateType", document.getElementById("date-type-filter").value);
               }
               else if (filterId == "start-date-filter")
               {
                  setSession("shippingCard.filter.startDate", document.getElementById("start-date-filter").value);
               }
               else if (filterId == "end-date-filter")
               {
                  setSession("shippingCard.filter.endDate", document.getElementById("end-date-filter").value);
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
      document.getElementById("new-shipping-card-button").onclick = function(){location.href = 'viewShippingCard.php';};
      document.getElementById("download-link").onclick = function(){table.download("csv", "<?php echo getReportFilename() ?>", {delimiter:"."})};
      document.getElementById("print-link").onclick = function(){table.print("active", true);};

      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
      
   </script>
   
</body>

</html>