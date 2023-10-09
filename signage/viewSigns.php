<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once '../common/database.php';
require_once '../common/header.php';
require_once '../common/permissions.php';

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
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script>  
   <script src="signage.js"></script>
      
</head>

<body class="flex-vertical flex-top flex-left">

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading">Digital Signage</div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div id="description" class="description">You can use PPTP Tools to set up and configure your Screenly digital signs.</div>

         <br>
        
         <button id="new-sign-button" class="accent-button">New Sign</button>

         <br>
        
         <div id="sign-table"></div>
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::SIGNAGE ?>); 
      
      preserveSession();

      function getTableQuery()
      {
         return ("<?php echo $ROOT ?>/api/signData/");
      }

      var url = getTableQuery();
      
      // Create Tabulator on DOM element sign-table.
      var table = new Tabulator("#sign-table", {
         //height:500, // set height of table (in CSS or here), this enables the Virtual DOM and improves render speed dramatically (can be any valid css height value)
         layout:"fitData",
         responsiveLayout:"hide", // enable responsive layouts
         ajaxURL:url,
         //Define Table Columns
         columns:[
            {title:"", field:"launch",
               formatter:function(cell, formatterParams, onRendered){
                  return ("<button class=\"small-button accent-button\" style=\"width:50px;\">Go</button>");
               }
            },
            {title:"Name",        field:"name",        hozAlign:"left"},
            {title:"Description", field:"description", hozAlign:"left"},
            {title:"URL",         field:"url",         hozAlign:"left"},
            {title:"",           field:"delete",
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ],
         cellClick:function(e, cell){
            var signId = parseInt(cell.getRow().getData().signId);
            var url = cell.getRow().getData().url;

            if (cell.getColumn().getField() == "launch")
            {
               window.open(url);
            }
            else if (cell.getColumn().getField() == "delete")
            {
               onDeleteSign(signId);
            }
            else // Any other column
            {
               // Open user for viewing/editing.
               document.location = "<?php echo $ROOT?>/signage/viewSign.php?signId=" + signId;               
            }
         },
         rowClick:function(e, row){
            // No row click function needed.
         },
      });

      // Setup event handling on all DOM elements.
      document.getElementById("new-sign-button").onclick = function(){location.href = 'viewSign.php';};
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
   </script>
   
</body>

</html>
