<!--
Required PHP variables:
$versionQuery
$javascriptFile
$javascriptClass
$activity
$heading
$description
$newButtonLabel
$filterTemplate
-->

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
   <script src="../script/pages/<?php echo $javascriptFile ?><?php echo $versionQuery ?>"></script>
      
</head>

<body class="flex-vertical flex-top flex-left">

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render($activity); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading"><?php echo $heading ?></div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div id="description" class="description"><?php echo $description ?></div>

         <br>
         
         <div class="flex-horizontal">         
            <?php echo $filterTemplate ?>
         </div>
         
         <br>
        
         <button id="new-button" class="accent-button <?php echo ($newButtonLabel ? "" : "hidden") ?>" ><?php echo $newButtonLabel ?></button>

         <br>
        
         <div id="data-table"></div>

         <br> 
        
         <div id="download-link" class="download-link">Download CSV file</div>
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>
   
      preserveSession();
      
      var PAGE = new <?php echo $javascriptClass ?>();
      PAGE.createTable("data-table");
      
      document.getElementById("new-button").addEventListener('click', function() {
         PAGE.onNewButton();
      });
      document.getElementById("download-link").addEventListener('click', function() {
         PAGE.onDownloadButton();
      });
      document.getElementById("help-icon").addEventListener('click', function() {
         document.getElementById("description").classList.toggle('shown');
      });
      document.getElementById("menu-button").addEventListener('click', function() {
         document.getElementById("menu").classList.toggle('shown');
      });
      
   </script>
   
</body>

</html>
