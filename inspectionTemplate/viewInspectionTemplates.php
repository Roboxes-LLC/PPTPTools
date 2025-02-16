<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once '../common/database.php';
require_once '../common/header.php';
require_once '../common/permissions.php';

function getFilterInspectionType()
{
   $inspectionType = InspectionType::UNKNOWN;
   
   if (isset($_SESSION["inspectionTemplate.filter.inspectionType"]))
   {
      $inspectionType = intval($_SESSION["inspectionTemplate.filter.inspectionType"]);
   }
   
   return ($inspectionType);
}

function getReportFilename()
{
   $filename = "InspectionTemplates.csv";
   
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
   
   <script src="/common/common.js"></script>
   <script src="/script/common/common.js<?php echo versionQuery();?>"></script>
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script>
   <script src="inspectionTemplate.js"></script>
      
</head>

<body class="flex-vertical flex-top flex-left">

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading">Inspection Templates</div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div id="description" class="description">Inspection templates are the foundation of your inspection process.  Here you can customize the properties of a QCP, Line, or In-Proces inspection.  You can even create a custom "generic" inspection for validating processes outside of parts production.</div>

         <br>
         
         <select id="inspection-type-filter"><?php echo getInspectionTypeOptions(getFilterInspectionType(), true, [InspectionType::OASIS]); ?></select>

         <br>
        
         <button id="new-template-button" class="accent-button">New Template</button>

         <br>
        
         <div id="inspection-template-table"></div>

         <br> 
        
         <div id="download-link" class="download-link">Download CSV file</div>
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::INSPECTION_TEMPLATE ?>); 
      
      preserveSession();

      function getTableQuery()
      {
         return ("<?php echo $ROOT ?>/api/inspectionTemplateData/");
      }

      function getTableQueryParams()
      {
         var params = new Object();

         params.inspectionType = document.getElementById("inspection-type-filter").value;

         return (params);
      }

      var url = getTableQuery();
      var params = getTableQueryParams();

      // Create Tabulator on DOM element user-table.
      var table = new Tabulator("#inspection-template-table", {
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
         // Columns
         columns:[
            {title:"Id",             field:"templateId",          visible:false},
            {title:"Name",           field:"name",                headerFilter:true},
            {title:"Type",           field:"inspectionTypeLabel", headerFilter:true},
            {title:"Description",    field:"description",         headerFilter:true},
            {title:"",               field:"copy",                hozAlign:"center", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">content_copy</i>");
               }
            },            
            {title:"",           field:"delete",                  hozAlign:"center", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ]
      });
      
      this.table.on("cellClick", function(e, cell) {
         var templateId = parseInt(cell.getRow().getData().templateId);

         if (cell.getColumn().getField() == "copy")
         {
            onCopyInspectionTemplate(templateId);
         }
         else if (cell.getColumn().getField() == "delete")
         {
            onDeleteInspectionTemplate(templateId);
         }
         else // Any other column
         {
            // Open user for viewing/editing.
            document.location = "<?php echo $ROOT?>/inspectionTemplate/viewInspectionTemplate.php?templateId=" + templateId;               
         }
      }.bind(this));

      function updateFilter(event)
      {
         if (document.readyState === "complete")
         {
            var filterId = event.srcElement.id;
   
            if (filterId == "inspection-type-filter")
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

               setSession("inspectionTemplate.filter.inspectionType", document.getElementById("inspection-type-filter").value);
            }
         }
      }

      // Setup event handling on all DOM elements.
      document.getElementById("inspection-type-filter").addEventListener("change", updateFilter);      
      document.getElementById("new-template-button").onclick = function(){location.href = 'viewInspectionTemplate.php';};
      document.getElementById("download-link").onclick = function(){table.download("csv", "<?php echo getReportFilename() ?>", {delimiter:"."})};
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
   </script>
   
</body>

</html>
