<!-- 
Required PHP variables:
   $versionQuery
   $javascriptFile
   $javascriptClass
   $appPageId
   $timeline
   $historyPanel
   $requestPanel
   $attachmentsPanel
   $status
 -->

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">

   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons|Material+Icons+Outlined"/>
   
   <link rel="stylesheet" type="text/css" href="/common/theme.css<?php echo $versionQuery ?>"/>
   <link rel="stylesheet" type="text/css" href="/common/common.css<?php echo $versionQuery ?>"/>
   <link rel="stylesheet" type="text/css" href="/css/correctiveAction.css<?php echo $versionQuery ?>"/>
   <link rel="stylesheet" type="text/css" href="/css/modal.css<?php echo $versionQuery ?>"/>
   <link rel="stylesheet" type="text/css" href="/css/pinPad.css<?php echo $versionQuery ?>"/>
   
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
            <div class="heading-with-iso"><?php echo $heading ?></div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div class="iso-number">ISO <?php echo $iso ?></div>
         
         <div id="description" class="description"><?php echo $description ?></div>
            
         <div><?php echo $timeline ?></div>
         
         <div class="flex-horizontal">
            
            <div class="flex-vertical">
               <?php echo $requestPanel ?>
               
               <?php echo $shortTermCorrectionPanel ?>
               
               <?php echo $longTermCorrectionPanel ?>
               
               <?php echo $approvePanel ?>
               
               <?php echo $reviewPanel ?>
            </div>
            
            <div class="flex-vertical flex-top flex-left">
               <?php echo $historyPanel ?>
               <br><br>
               <?php echo $attachmentsPanel ?>
            </div>

         </div>
                  
      </div> <!-- content -->
      
   </div> <!-- main -->
     
   <script>
      preserveSession();
   
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo $appPageId ?>);  
         
      var PAGE = new <?php echo $javascriptClass ?>();
      PAGE.setStatus(<?php echo $status ?>);

      // Setup event handling on all DOM elements.
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};

      // Store the initial state of the form, for change detection.
      //setInitialFormState(TODO);      
      
   </script>
   
</body>

</html>
