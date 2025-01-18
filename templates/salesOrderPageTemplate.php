<!-- 
Required PHP variables:
   $versionQuery
   $javascriptFile
   $javascriptClass
   $heading
   $description
   $formId
   $form
   $appPageId
   $saveButtonLabel
 -->

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">

   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons"/>
   <link rel="stylesheet" type="text/css" href="../thirdParty/tabulator/css/tabulator.min.css"/>
   
   <link rel="stylesheet" type="text/css" href="../common/theme.css<?php echo $versionQuery ?>"/>
   <link rel="stylesheet" type="text/css" href="../common/common.css<?php echo $versionQuery ?>"/>
   
   <script src="../thirdParty/tabulator/js/tabulator.min.js"></script>
   <script src="../thirdParty/moment/moment.min.js"></script>
   
   <script src="/common/common.js<?php echo $versionQuery ?>"></script>
   <script src="/common/validate.js<?php echo $versionQuery ?>"></script>
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
        
         <div class="flex-horizontal" style="width: 100%">
            <div style="margin-right: 50px">
               <?php echo $form ?>
            </div>
            <div>
               <div class="form-section-header">Parts Inventory</div>
               <div id="parts-inventory-table"></div>
            </div>
         </div>
         
         <br>
         
         <div class="flex-horizontal flex-h-center">
            <button id="cancel-button">Cancel</button>&nbsp;&nbsp;&nbsp;
            <button id="save-button" class="accent-button"><?php echo isset($saveButtonLabel) ? $saveButtonLabel : "Save" ?></button>            
         </div>
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>
      preserveSession();
   
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo $appPageId ?>);  
         
      var PAGE = new <?php echo $javascriptClass ?>();
      
      PAGE.createPartsInventoryTable("parts-inventory-table");

      // Setup event handling on all DOM elements.
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};

      // Store the initial state of the form, for change detection.
      setInitialFormState("<?php echo $formId ?>");
      
      <?php 
      if (isset($customScript))
      {
         echo $customScript;
      }
      ?>
   </script>
   
</body>

</html>
