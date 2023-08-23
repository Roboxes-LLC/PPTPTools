<!-- 
Required PHP variables:
   $versionQuery
   $javascriptFile
   $javascriptClass
   $appPageId
   $timeline
   $historyPanel
   $requestPanel
   $estimatesPanel
   $approvePanel
   $sendPanel
   $acceptPanel
   $quoteStatus
 -->

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">

   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons|Material+Icons+Outlined"/>
   
   <link rel="stylesheet" type="text/css" href="../common/theme.css"/>
   <link rel="stylesheet" type="text/css" href="../common/common.css"/>
   
   <script src="../thirdParty/tabulator/js/tabulator.min.js"></script>
   <script src="../thirdParty/moment/moment.min.js"></script>
   
   <script src="/common/common.js"></script>
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
            
         <div><?php echo $timeline ?></div>
         
         <div class="flex-horizontal">
            
            <div class="flex-vertical">
               <?php echo $requestPanel ?>
               
               <?php echo $estimatesPanel ?>
               
               <?php echo $approvePanel ?>
               
               <?php echo $sendPanel ?>
               
               <?php echo $acceptPanel ?>
            </div>
            
            <div class="flex-vertical flex-top flex-left">
               <?php echo $historyPanel ?>
            </div>

         </div>
                  
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>
      preserveSession();
   
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo $appPageId ?>);  
         
      var PAGE = new <?php echo $javascriptClass ?>();
      PAGE.setQuoteStatus(<?php echo $quoteStatus ?>);

      // Setup event handling on all DOM elements.
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};

      // Store the initial state of the form, for change detection.
      //setInitialFormState(TODO);
   </script>
   
</body>

</html>
