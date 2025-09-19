<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once '../common/authentication.php';
require_once '../common/database.php';
require_once '../common/filterDateType.php';
require_once '../common/header.php';
require_once '../common/isoInfo.php';
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
   $startDate = Time::now(Time::JAVASCRIPT_DATE_FORMAT);
   
   if (isset($_SESSION["material.filter.startDate"]))
   {
      $startDate = $_SESSION["material.filter.startDate"];
   }
   
   // Convert to Javascript date format.
   $startDate = Time::dateTimeObject($startDate)->format(Time::JAVASCRIPT_DATE_FORMAT);
   
   return ($startDate);
}

function getFilterEndDate()
{
   $endDate = Time::now(Time::JAVASCRIPT_DATE_FORMAT);
   
   if (isset($_SESSION["material.filter.endDate"]))
   {
      $endDate = $_SESSION["material.filter.endDate"];
   }
   
   // Convert to Javascript date format.
   $endDate = Time::dateTimeObject($endDate)->format(Time::JAVASCRIPT_DATE_FORMAT);
   
   return ($endDate);
}

function getFilterAllUnissued()
{
   $allUnissued = false;
   
   if (isset($_SESSION["material.filter.allUnissued"]))
   {
      $allUnissued = filter_var($_SESSION["material.filter.allUnissued"], FILTER_VALIDATE_BOOLEAN);
   }
   
   return ($allUnissued);
   
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
   <script src="/thirdParty/luxon/luxon.min.js<?php echo versionQuery();?>"></script>
   
   <script src="/common/common.js<?php echo versionQuery();?>"></script>
   <script src="/common/validate.js<?php echo versionQuery();?>"></script>
   <script src="/script/common/common.js<?php echo versionQuery();?>"></script>  
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script>  
   <script src="material.js<?php echo versionQuery();?>"></script>
      
</head>

<body class="flex-vertical flex-top flex-left">

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading-with-iso">Materials</div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div id="description" class="description">Something something something.</div>
         
         <div class="iso-number">ISO <?php echo IsoInfo::getIsoNumber(IsoDoc::MATERIAL_LOG); ?></div>
         
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
            <input id="all-unissued-filter" type="checkbox" <?php echo getFilterAllUnissued() ? "checked" : "" ?>>&nbsp;All Unissued
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
         if (document.getElementById("all-unissued-filter").checked)
         {
            params.allUnissued = true;
         }
         else
         {
            params.status =  document.getElementById("status-filter").value;
            params.dateType =  document.getElementById("date-type-filter").value;
            params.startDate =  document.getElementById("start-date-filter").value;
            params.endDate =  document.getElementById("end-date-filter").value;
         }

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
         // Data
         ajaxURL:url,
         ajaxParams:params,
         // Layout
         maxHeight: 500,
         layout:"fitData",
         columnDefaults:{
            hozAlign:"left", 
            vertAlign:"middle"
         },
         persistence:true,
         // Printing
         printAsHtml:true,          //enable HTML table printing
         printRowRange:"all",       // print all rows 
         printHeader:"<h1>Material Log<h1>",
         // Columns
         columns:[
            {title:"Id",          field:"materialEntryId",                          visible:false},
            {title:"Ticket",      field:"materialTicketCode",                       headerFilter:true,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">receipt</i>&nbsp" + cell.getRow().getData().materialTicketCode);
               },
               formatterPrint:function(cell, formatterParams, onRendered){
                  return (cell.getValue());
               }  
            },                   
            {title:"Received",    field:"receivedDateTime",
               formatter:"datetime",
               formatterParams:{
                  outputFormat:"M/d/yyyy",
                  invalidPlaceholder:"---"
               }
            },
            {title:"Material",    field:"materialHeatInfo.materialInfo.partNumber", headerFilter:true},
            {title:"Vendor",      field:"vendorName",                               headerFilter:true},
            {title:"Vendor Heat", field:"vendorHeatNumber",                         headerFilter:true},            
            {title:"PPTP Heat",   field:"materialHeatInfo.internalHeatNumber",      headerFilter:true},
            {title:"Tag",         field:"tagNumber",                                headerFilter:true},
            {title:"Location",    field:"locationLabel",                            headerFilter:true},
            {title:"Type",        field:"materialHeatInfo.materialInfo.typeLabel",  headerFilter:true},
            {title:"Size",        field:"materialHeatInfo.materialInfo.size",       headerFilter:true},
            {title:"Shape",       field:"materialHeatInfo.materialInfo.shapeLabel", headerFilter:true},
            {title:"Length",      field:"materialHeatInfo.materialInfo.length",     headerFilter:true},
            {title:"Pieces",      field:"pieces"},            
            {title:"Quantity",    field:"quantity"},
            {
               title:"Inspection",
               columns:[
                  {title:"Accepted", field:"acceptedPieces",
                     formatter:function(cell, formatterParams, onRendered){
                        let received = parseInt(cell.getRow().getData().pieces);
                        let accepted = parseInt(cell.getRow().getData().acceptedPieces);
                        
                        cellValue = "";
                        if (accepted <= received)
                        {
                           cellValue = `${accepted}/${received}`;
                        }
                        
                        return (cellValue);
                     },
                     formatterPrint:function(cell, formatterParams, onRendered){  
                        return (cell.getValue());
                     }
                  },
                  {title:"Stamp",       field:"materialStampLabel",                 headerFilter:true},
                  {title:"PO #",        field:"poNumber",                           headerFilter:true}
               ]
            },
            {title:"",            field:"issue",                                    visible:hasIssuePermission, print:false,
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
                  {title:"Issued Date", field:"issuedDateTime",
                     formatter:"datetime",  // Requires luxon.js 
                     formatterParams:{
                        outputFormat:"M/d/yyyy",
                        invalidPlaceholder:""
                     }
                  },
                  {title:"Job #",       field:"issuedJobNumber",                   headerFilter:true},
                  {title:"WC #",        field:"issuedWCNumber",                    headerFilter:true}
               ]
            },
            {title:"Ack.",        field:"isAcknowledged",
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
            {title:"", field:"delete",                                              hozAlign:"center", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ]
      });
      
      this.table.on("cellClick", function(e, cell) {
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
      }.bind(this));

      function updateFilter(event)
      {
         if (document.readyState === "complete")
         {
            var filterId = event.srcElement.id;
   
            if ((filterId == "status-filter") ||
                (filterId == "date-type-filter") ||
                (filterId == "start-date-filter") ||
                (filterId == "end-date-filter") ||
                (filterId == "all-unissued-filter"))
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
               else if (filterId == "all-unissued-filter")
               {
                  setSession("material.filter.allUnissued", document.getElementById("all-unissued-filter").checked);
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
      
      function onAllUnissuedFilterChanged()
      {
         let allUnissued = document.getElementById("all-unissued-filter").checked;
         
         if (allUnissued)
         {
            disable("status-filter");
            disable("date-type-filter");
            disable("start-date-filter");
            disable("end-date-filter");
         }
         else
         {
            enable("status-filter");
            enable("date-type-filter");
            enable("start-date-filter");
            enable("end-date-filter");
         }
      }

      // Setup event handling on all DOM elements.
      document.getElementById("status-filter").addEventListener("change", updateFilter);
      document.getElementById("date-type-filter").addEventListener("change", updateFilter);  
      document.getElementById("start-date-filter").addEventListener("change", updateFilter);      
      document.getElementById("end-date-filter").addEventListener("change", updateFilter);
      document.getElementById("all-unissued-filter").addEventListener("change", function() {
         onAllUnissuedFilterChanged();
         updateFilter(event);
      });
      document.getElementById("today-button").onclick = filterToday;
      document.getElementById("yesterday-button").onclick = filterYesterday;
      document.getElementById("new-material-button").onclick = function(){location.href = 'viewMaterial.php';};
      document.getElementById("download-link").onclick = function(){table.download("csv", "<?php echo getReportFilename() ?>", {delimiter:"."})};
      document.getElementById("print-link").onclick = function(){table.print("active", true);};

      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
      
      onAllUnissuedFilterChanged()
   </script>
   
</body>

</html>
