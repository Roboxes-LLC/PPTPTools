<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once '../common/database.php';
require_once '../common/header.php';
require_once '../common/jobInfo.php';
require_once '../common/permissions.php';
require_once '../common/roles.php';

function getReportFilename()
{
   $filename = "Jobs.csv";
   
   return ($filename);
}

function isFilteredBy($jobStatus)
{
   $isFiltered = false;
   
   // Determine if any filter values have been set in the $_SESSION variable.
   $isInitialized = false;
   foreach (JobStatus::$VALUES as $evalJobStatus)
   {
      $name = strtolower(JobStatus::getName($evalJobStatus));
      
      $isInitialized |= isset($_SESSION["jobs.filter.$name"]);
   }
   
   // Initialize to showing just pending and active jobs.
   $activeName = strtolower(JobStatus::getName(JobStatus::ACTIVE));
   $pendingName = strtolower(JobStatus::getName(JobStatus::PENDING));
   
   if (!$isInitialized)
   {
      $_SESSION["jobs.filter.$activeName"] = true;
      $_SESSION["jobs.filter.$pendingName"] = true;
   }   
   
   $name = strtolower(JobStatus::getName($jobStatus));
   
   if (isset($_SESSION["jobs.filter.$name"]))
   {
      $isFiltered = filter_var($_SESSION["jobs.filter.$name"], FILTER_VALIDATE_BOOLEAN);
   }
   
   return ($isFiltered);
}

function getJobStatusFilters()
{
   $html = "";
   
   for ($jobStatus = JobStatus::FIRST; $jobStatus < JobStatus::LAST; $jobStatus++)
   {
      if ($jobStatus != JobStatus::DELETED)
      {
         $checked = isFilteredBy($jobStatus) ? "checked" : "";
         
         $label = JobStatus::getName($jobStatus);
         
         $name = strtolower($label);
         
         $id = $name . "-filter";
         
         $html .=
<<<HEREDOC
         <div class="flex-horizontal flex-v-center">
            <input id="$id" class="job-status-filter" type="checkbox" name="$name" value="true" $checked/>
            &nbsp;
            <label for="$id">$label</label>
            &nbsp;&nbsp;
         </div>
HEREDOC;
      }
   }   
   
   return ($html);
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
   
   <script src="/common/common.js"></script>
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script>
   <script src="jobs.js"></script>
      
</head>

<body class="flex-vertical flex-top flex-left">

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading">Jobs</div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div id="description" class="description">Tracking production all starts with the creation of Job.  Your active jobs are the ones available to your operators for creating Time Sheets.</div>

         <br>
         
         <div class="flex-horizontal">         
            <?php echo getJobStatusFilters(); ?>
         </div>
         
         <br>
        
         <button id="add-job-button" class="accent-button">New Job</button>

         <br>
        
         <div id="job-table"></div>

         <br> 
        
         <div id="download-link" class="download-link">Download CSV file</div>
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>   
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::JOBS ?>);  
   
      preserveSession();

      function getTableQuery()
      {
         return ("<?php echo $ROOT ?>/api/jobData/");
      }

      function getTableQueryParams()
      {
         var params = new Object();

         var filters = document.getElementsByClassName("job-status-filter");

         for (var filter of filters)
         {
            params[filter.name] = filter.checked;
         }

         return (params);
      }

      var url = getTableQuery();
      var params = getTableQueryParams();
      
      // Create Tabulator on DOM element time-card-table.
      var table = new Tabulator("#job-table", {
         maxHeight:500,  // set height of table (in CSS or here), this enables the Virtual DOM and improves render speed dramatically (can be any valid css height value)
         layout:"fitData",
         responsiveLayout:"hide", // enable responsive layouts
         ajaxURL:url,
         ajaxParams:params,
         //Define Table Columns
         columns:[
            {title:"Id",             field:"jobId",         hozAlign:"left", visible:false},
            {title:"Job #",          field:"jobNumber",     hozAlign:"left", responsive:0, headerFilter:true},
            {title:"Date",           field:"dateTime",      hozAlign:"left", responsive:1, headerFilter:true,
               formatter:"datetime",  // Requires moment.js 
               formatterParams:{
                  outputFormat:"MM/DD/YYYY",
                  invalidPlaceholder:"---"
               }
            },
            {title:"Part #",         field:"partNumber",         hozAlign:"left", responsive:0, headerFilter:true},
            {title:"Cust. Part #",   field:"customerPartNumber", hozAlign:"left", responsive:2, headerFilter:true},
            {title:"Work Center",    field:"wcLabel",            hozAlign:"left", responsive:2, headerFilter:true},
            // Temporary
            {title:"Cycle Time",     field:"cycleTime",  hozAlign:"left", responsive:2 },
            {title:"Sample Weight",  field:"sampleWeight",  hozAlign:"left", responsive:2,
               formatter:function(cell, formatterParams, onRendered){
                  if (cell.getValue() <= 0.02)
                  {
                     cell.getElement().style.color = "red";
                  }
                  
                  return (cell.getValue());
               }
            },
            {title:"Gross Pieces/Hour", field:"grossPartsPerHour", hozAlign:"left", responsive:3},
            {title:"Net Pieces/Hour",   field:"netPartsPerHour", hozAlign:"left", responsive:3},                     
            {title:"Customer Print", field:"customerPrint", hozAlign:"left", responsive:4, 
               formatter:function(cell, formatterParams, onRendered){
                  var filename = cell.getValue();
                  var truncatedFilename = (filename.length > 20) ? filename.substr(0, 20) + "..." : filename; 
                  return ("<a href=\"<?php echo $ROOT ?>/uploads/" + filename + "\" target=\"_blank\">" + truncatedFilename + "</a>");
                }
            },
            {title:"Status",         field:"statusLabel",   hozAlign:"left", responsive:0, headerFilter:true},
            {title:"",               field:"copy",                           responsive:0,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">content_copy</i>");
               }
            },
            {title:"",               field:"delete",                          responsive:0,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ],
         cellClick:function(e, cell){
            var jobId = parseInt(cell.getRow().getData().jobId);
            
            if (cell.getColumn().getField() == "copy")
            {
               onCopyJob(jobId);
            }
            else if (cell.getColumn().getField() == "delete")
            {
               onDeleteJob(jobId);
            }
            else if (cell.getColumn().getField() == "customerPrint")
            {
               // No action.  Allow for clicking on link.
            }
            else // Any other column
            {
               // Open user for viewing/editing.
               document.location = "<?php echo $ROOT?>/jobs/viewJob.php?jobId=" + jobId;               
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
            var filter = event.srcElement;
            
            var url = getTableQuery();
            var params = getTableQueryParams();

            table.setData(url, params)
            .then(function(){
               // Run code after table has been successfuly updated
            })
            .catch(function(error){
               // Handle error loading data
            });

            setSession("jobs.filter." + filter.name, filter.checked);
         }
      }

      // Setup event handling on all DOM elements.
      var filters = document.getElementsByClassName("job-status-filter");
      for (var filter of filters)
      {
         filter.addEventListener("change", updateFilter); 
      }      
      document.getElementById("add-job-button").onclick = function(){location.href = 'viewJob.php';};
      document.getElementById("download-link").onclick = function(){table.download("csv", "<?php echo getReportFilename() ?>", {delimiter:"."})};
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
   </script>
   
</body>

</html>
