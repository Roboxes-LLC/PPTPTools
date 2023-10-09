<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once '../common/authentication.php';
require_once '../common/database.php';
require_once '../common/filterDateType.php';
require_once '../common/header.php';
require_once '../common/materialEntry.php';
require_once '../common/version.php';

function getFilterStatus()
{
   $materialEntryStatus = MaterialEntryStatus::UNKNOWN;
   
   if (isset($_SESSION["material.filter.status"]))
   {
      $materialEntryStatus = intval($_SESSION["material.filter.status"]);
   }
   
   return ($materialEntryStatus);
}

function getFilterDateType()
{
   $filterDateType = FilterDateType::ENTRY_DATE;
   
   if (isset($_SESSION["material.filter.dateType"]))
   {
      $filterDateType = $_SESSION["material.filter.dateType"];
   }
   
   return ($filterDateType);
}

function getFilterStartDate()
{
   $startDate = Time::now("Y-m-d");
   
   if (isset($_SESSION["material.filter.startDate"]))
   {
      $startDate = $_SESSION["material.filter.startDate"];
   }

   // Convert to Javascript date format.
   $dateTime = new DateTime($startDate, new DateTimeZone('America/New_York'));  // TODO: Replace
   $startDate = $dateTime->format(Time::$javascriptDateFormat);
   
   return ($startDate);
}

function getFilterEndDate()
{
   $startDate = Time::now("Y-m-d");
   
   if (isset($_SESSION["material.filter.endDate"]))
   {
      $startDate = $_SESSION["material.filter.endDate"];
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
   
   $filename = "Materials_" . $dateString . ".csv";
   
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
   <script src="material.js<?php echo versionQuery();?>"></script>
      
</head>

<body class="flex-vertical flex-top flex-left">

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading">Materials</div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div id="description" class="description">Something something something.</div>
         
         <br>
         
         <div class="flex-horizontal flex-v-center flex-left">
            <div style="white-space: nowrap">Status</div>
            &nbsp;
            <select id="status-filter"><?php echo MaterialEntryStatus::getOptions(getFilterStatus(), true)?></select>
            &nbsp;&nbsp;                        
            <select id="date-type-filter"><?php echo FilterDateType::getOptions([FilterDateType::ENTRY_DATE, FilterDateType::RECEIVE_DATE], getFilterDateType()) ?></select>
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
            &nbsp;&nbsp;
         </div>
         
         <br>
        
         <button id="new-material-button" class="accent-button">Receive Material</button>

         <br>
        
         <div id="material-table"></div>

         <br> 
        
         <div id="download-link" class="download-link">Download CSV file</div>
         
         <div id="print-link" class="download-link">Print</div>  
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::MATERIAL ?>);  
      
      preserveSession();

      function getTableQuery()
      {
         return ("<?php echo $ROOT ?>/api/materialData/");
      }

      function getTableQueryParams()
      {
         
         var params = new Object();
         params.status =  document.getElementById("status-filter").value;
         params.dateType =  document.getElementById("date-type-filter").value;
         params.startDate =  document.getElementById("start-date-filter").value;
         params.endDate =  document.getElementById("end-date-filter").value;

         return (params);
      }
      
      // Set the acknowledgedUserId to the authenticated user.
      var hasIssuePermission = <?php echo Authentication::checkPermissions(Permission::ISSUE_MATERIAL) ? "true" : "false" ?>;
      var hasAcknowledgePermission = <?php echo Authentication::checkPermissions(Permission::ACKNOWLEDGE_MATERIAL) ? "true" : "false" ?>;
      var acknowledgedUserId = <?php echo Authentication::getAuthenticatedUser()->employeeNumber ?>;
      
      var url = getTableQuery();
      var params = getTableQueryParams();
      
      // Create Tabulator on DOM element.
      var table = new Tabulator("#material-table", {
         maxHeight:500,  // set height of table (in CSS or here), this enables the Virtual DOM and improves render speed dramatically (can be any valid css height value)
         layout:"fitData",
         cellVertAlign:"middle",
         ajaxURL:url,
         ajaxParams:params,
         //printAsHtml:true,
         //Define Table Columns
         columns:[
            {title:"Id",          field:"materialEntryId",       hozAlign:"left", visible:false},
            {title:"Ticket",      field:"materialTicketCode",    hozAlign:"left", headerFilter:true,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">receipt</i>&nbsp" + cell.getRow().getData().materialTicketCode);
               },
               formatterPrint:function(cell, formatterParams, onRendered){
                  return (cell.getValue());
               }  
            },                   
            {title:"Received",    field:"receivedDateTime",       hozAlign:"left",
               formatter:"datetime",  // Requires moment.js 
               formatterParams:{
                  outputFormat:"MM/DD/YYYY",
                  invalidPlaceholder:"---"
               }
            },
            {title:"Material",    field:"materialPartNumber",                  hozAlign:"left", headerFilter:true, visible:true},
            {title:"Vendor",      field:"vendorName",                          hozAlign:"left", headerFilter:true, visible:true},
            {title:"Vendor Heat", field:"vendorHeatNumber",                    hozAlign:"left", headerFilter:true, visible:true},            
            {title:"PPTP Heat",   field:"materialHeatInfo.internalHeatNumber", hozAlign:"left", headerFilter:true, visible:true},
            {title:"Tag",         field:"tagNumber",                           hozAlign:"left", headerFilter:true, visible:true},
            {title:"Location",    field:"locationLabel",                       hozAlign:"left", headerFilter:true, visible:true},
            {title:"Type",        field:"materialTypeLabel",                   hozAlign:"left", headerFilter:true, visible:true},
            {title:"Size",        field:"size",                                hozAlign:"left", headerFilter:true, visible:true},
            {title:"Length",      field:"length",                              hozAlign:"left", headerFilter:true, visible:true},
            {title:"Pieces",      field:"pieces",                              hozAlign:"left", visible:true},            
            {title:"Quantity",    field:"quantity",                            hozAlign:"left", visible:true},
            {title:"",            field:"issue",                                                visible:hasIssuePermission, print:false,
               formatter:function(cell, formatterParams, onRendered){
                  let isIssued = cell.getRow().getData().isIssued;                  
                  let buttonText = isIssued ? "Revoke" : "Issue";
            
                  let disabled = hasIssuePermission ? "" : "disabled";
         
                  return (`<button class=\"small-button accent-button\" style=\"width:50px;\" ${disabled}>${buttonText}</button>`);
               }
            },            
            {
               title:"Issued",
               columns:[
                  {title:"Issued Date", field:"issuedDateTime",                  hozAlign:"left",
                     formatter:"datetime",  // Requires moment.js 
                     formatterParams:{
                        outputFormat:"MM/DD/YYYY",
                        invalidPlaceholder:""
                     }
                  },
                  {title:"Job #",       field:"issuedJobNumber",                 hozAlign:"left", headerFilter:true, visible:true},
                  {title:"WC #",        field:"issuedWCNumber",                  hozAlign:"left", headerFilter:true, visible:true}
               ]
            },
            {title:"Ack.",        field:"isAcknowledged",                  hozAlign:"left", visible:true,
               formatter:function(cell, formatterParams, onRendered){
                  let isIssued = cell.getRow().getData().isIssued;
                  let isAcknowledged = cell.getRow().getData().isAcknowledged;                   
                  
                  let disabled = (isIssued && hasAcknowledgePermission) ? "" : "disabled";
                  let checked = isAcknowledged ? "checked" : "";
               
                  return (`<input type=\"checkbox\" ${checked} ${disabled}>`);
               },
               formatterPrint:function(cell, formatterParams, onRendered){
                  let isAcknowledged = cell.getValue();   
                  return (isAcknowledged ? "YES" : "");
               }  
            },
            {title:"", field:"delete", responsive:0, print:false,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ],
         cellClick:function(e, cell){
            var entryId = parseInt(cell.getRow().getData().materialEntryId);            
                     
            if (cell.getColumn().getField() == "materialTicketCode")
            {
               document.location = `printMaterialTicket.php?materialTicketId=${entryId}`;
            }
            else if (cell.getColumn().getField() == "issue")
            {
               let isIssued = cell.getRow().getData().isIssued;      
                              
               if (!isIssued)
               {
                  document.location = `viewMaterial.php?entryId=${entryId}&issue=1`;
               }
               else
               {
                  onRevokeButton(entryId);
               }
            }
            else if (cell.getColumn().getField() == "isAcknowledged")
            {
               let isAcknowledged = cell.getRow().getData().isAcknowledged;   

               if (!isAcknowledged)
               {                              
                  onAcknowledge(entryId, acknowledgedUserId);  // Note: acknowledgedUserId set below.
               }
               else
               {
                  onUnacknowledge(entryId);               
               }
            }
            else if (cell.getColumn().getField() == "delete")
            {
               onDeleteMaterialEntry(entryId);
            }
            else // Any other column
            {
               // Open material entry for viewing/editing.
               document.location = `viewMaterial.php?entryId=${entryId}`;               
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
   
            if ((filterId == "status-filter") ||
                (filterId == "date-type-filter") ||
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

               if (filterId == "status-filter")
               {
                  setSession("material.filter.status", document.getElementById("status-filter").value);
               }
               else if (filterId == "date-type-filter")
               {
                  setSession("material.filter.dateType", document.getElementById("date-type-filter").value);
               }
               else if (filterId == "start-date-filter")
               {
                  setSession("material.filter.startDate", document.getElementById("start-date-filter").value);
               }
               else if (filterId == "end-date-filter")
               {
                  setSession("material.filter.endDate", document.getElementById("end-date-filter").value);
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
      document.getElementById("status-filter").addEventListener("change", updateFilter);
      document.getElementById("date-type-filter").addEventListener("change", updateFilter);  
      document.getElementById("start-date-filter").addEventListener("change", updateFilter);      
      document.getElementById("end-date-filter").addEventListener("change", updateFilter);
      document.getElementById("today-button").onclick = filterToday;
      document.getElementById("yesterday-button").onclick = filterYesterday;
      document.getElementById("new-material-button").onclick = function(){location.href = 'viewMaterial.php';};
      document.getElementById("download-link").onclick = function(){table.download("csv", "<?php echo getReportFilename() ?>", {delimiter:"."})};
      document.getElementById("print-link").onclick = function(){table.print(false, true);};

      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};

   </script>
   
</body>

</html>
