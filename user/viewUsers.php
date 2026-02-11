<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/permissions.php';
require_once ROOT.'/core/common/role.php';

function getReportFilename()
{
   $filename = "Users.csv";
   
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
   <link rel="stylesheet" type="text/css" href="/thirdParty/tabulator/dist/css/tabulator.min.css<?php echo versionQuery();?>"/>
   
   <link rel="stylesheet" type="text/css" href="/common/theme.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="/common/common.css<?php echo versionQuery();?>"/>
   
   <script src="/thirdParty/tabulator/dist/js/tabulator.min.js<?php echo versionQuery();?>"></script>
   <script src="/thirdParty/luxon/luxon.min.js<?php echo versionQuery();?>"></script>
   
   <script src="/common/common.js<?php echo versionQuery();?>"></script>
   <script src="/script/common/common.js<?php echo versionQuery();?>"></script>   
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script>   
   <script src="/user/user.js<?php echo versionQuery();?>"></script>
      
</head>

<body class="flex-vertical flex-top flex-left">

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading">Users</div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div id="description" class="description">Add, view, and delete users from the PPTP Tools system from here.</div>

         <br>
        
         <button id="add-user-button" class="accent-button">Add User</button>

         <br>
        
         <div id="user-table"></div>

         <br> 
        
         <div id="download-link" class="download-link">Download CSV file</div>
         
         <div id="print-link" class="download-link">Print</div>
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::USER ?>);  
   
      preserveSession();

      function getTableQuery()
      {
         return ("<?php echo $ROOT ?>/api/userData/");
      }

      var url = getTableQuery();
      
      // Create Tabulator on DOM element user-table.
      var table = new Tabulator("#user-table", {
         // Data
         ajaxURL:url,
         // Layout
         maxHeight:500,
         layout:"fitData",
         columnDefaults:{
            hozAlign:"left", 
            vertAlign:"middle"
         },
         persistence:true,
         // Columns
         columns:[
            {title:"Employee #", field:"employeeNumber"},
            {title:"Name",       field:"name",           headerFilter:true},
            {title:"Username",   field:"username",       headerFilter:true},
            {title:"Email",      field:"email",          width:250},
            {title:"Role",       field:"roleLabel",      headerFilter:true},
            {title:"",           field:"delete",         hozAlign:"center", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ]
      });
      
      table.on("cellClick", function(e, cell) {
         var employeeNumber = parseInt(cell.getRow().getData().employeeNumber);
         
         if (cell.getColumn().getField() == "delete")
         {
            onDeleteUser(employeeNumber);
         }
         else // Any other column
         {
            // Open user for viewing/editing.
            document.location = "<?php echo $ROOT?>/user/viewUser.php?employeeNumber=" + employeeNumber;               
         }
      });

      // Setup event handling on all DOM elements.
      document.getElementById("add-user-button").onclick = function(){location.href = 'viewUser.php';};
      document.getElementById("download-link").onclick = function(){table.download("csv", "<?php echo getReportFilename() ?>", {delimiter:"."})};
      document.getElementById("print-link").onclick = function(){table.print("active", true);};
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
   </script>
   
</body>

</html>
