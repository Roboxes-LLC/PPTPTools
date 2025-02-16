<!-- 
Required PHP variables:
   $versionQuery
   $javascriptFile
   $javascriptClass
   $appPageId
   $newButtonLabel
   $reportFileName
 -->

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">

   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons"/>
   <link rel="stylesheet" type="text/css" href="/thirdParty/tabulator/css/tabulator.min.css"/>
   
   <link rel="stylesheet" type="text/css" href="/common/theme.css<?php echo $versionQuery ?>"/>
   <link rel="stylesheet" type="text/css" href="/common/common.css<?php echo $versionQuery ?>"/>
   
   <script src="/thirdParty/tabulator/js/tabulator.min.js"></script>
   <script src="/thirdParty/luxon/luxon.min.js<?php echo versionQuery();?>"></script>
   
   <script src="/common/common.js<?php echo $versionQuery ?>"></script>
   <script src="/script/common/common.js<?php echo $versionQuery ?>"></script>
   <script src="/script/common/commonDefs.php<?php echo $versionQuery ?>"></script>
   <script src="/script/common/menu.js<?php echo $versionQuery ?>"></script>   
   <script src="/script/page/<?php echo $javascriptFile ?>"></script>
      
</head>

<body class="flex-vertical flex-top flex-left">

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading"><?php echo $heading ?></div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div id="description" class="description"><?php echo $description ?></div>

         <br>
         
         <?php if (isset($filterTemplate)) include $root."/templates/filter/$filterTemplate" ?>
        
         <br>
        
         <button id="add-button" class="accent-button"><?php echo $newButtonLabel ?></button>

         <br>
        
         <div id="data-table"></div>

         <br> 
        
         <div id="download-link" class="download-link">Download CSV file</div>
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>
      preserveSession();
   
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo $appPageId ?>);  
         
      var PAGE = new <?php echo $javascriptClass ?>();
      PAGE.createTable("data-table");

      // Setup event handling on all DOM elements.
      document.getElementById("download-link").onclick = function(){table.download("csv", "<?php echo $reportFileName ?>", {delimiter:"."})};
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
   </script>
   
</body>

</html>
