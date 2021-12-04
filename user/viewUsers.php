<?php

require_once '../common/activity.php';
require_once '../common/database.php';
require_once '../common/header.php';
require_once '../common/menu.php';
require_once '../common/permissions.php';
require_once '../common/roles.php';

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
   <link rel="stylesheet" type="text/css" href="../thirdParty/tabulator/css/tabulator.min.css"/>
   
   <link rel="stylesheet" type="text/css" href="../common/theme.css"/>
   <link rel="stylesheet" type="text/css" href="../common/common.css"/>
   
   <script src="../thirdParty/tabulator/js/tabulator.min.js"></script>
   <script src="../thirdParty/moment/moment.min.js"></script>
   
   <script src="../common/common.js"></script>
   <script src="user.js"></script>
      
</head>

<body class="flex-vertical flex-top flex-left">

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(Activity::USER); ?>
      
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
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>
   
      preserveSession();

      function getTableQuery()
      {
         return ("<?php echo $ROOT ?>/api/userData/");
      }

      var url = getTableQuery();
      
      // Create Tabulator on DOM element user-table.
      var table = new Tabulator("#user-table", {
         maxHeight:500,  // set height of table (in CSS or here), this enables the Virtual DOM and improves render speed dramatically (can be any valid css height value)
         layout:"fitData",
         responsiveLayout:"hide", // enable responsive layouts
         ajaxURL:url,
         //Define Table Columns
         columns:[
            {title:"Employee #", field:"employeeNumber", hozAlign:"left", responsive:0},
            {title:"Name",       field:"name",           hozAlign:"left", responsive:1, headerFilter:true},
            {title:"Username",   field:"username",       hozAlign:"left", responsive:0, headerFilter:true},
            {title:"Email",      field:"email",          hozAlign:"left", responsive:2, width:150},
            {title:"Role",       field:"roleLabel",      hozAlign:"left", responsive:0, headerFilter:true},
            {title:"",           field:"delete",                          responsive:0,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ],
         cellClick:function(e, cell){
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
         },
         rowClick:function(e, row){
            // No row click function needed.
         },
      });

      // Setup event handling on all DOM elements.
      document.getElementById("add-user-button").onclick = function(){location.href = 'viewUser.php';};
      document.getElementById("download-link").onclick = function(){table.download("csv", "<?php echo getReportFilename() ?>", {delimiter:"."})};
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
      document.getElementById("menu-button").onclick = function(){document.getElementById("menu").classList.toggle('shown');};
   </script>
   
</body>

</html>
