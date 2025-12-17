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
   
      <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons"/>
   <link rel="stylesheet" type="text/css" href="/thirdParty/tabulator/css/tabulator.min.css"/>
   
   <link rel="stylesheet" type="text/css" href="/common/theme.css<?php echo $versionQuery ?>"/>
   <link rel="stylesheet" type="text/css" href="/common/common.css<?php echo $versionQuery ?>"/>
   
   <script src="/thirdParty/tabulator/js/tabulator.min.js"></script>
   <script src="/thirdParty/luxon/luxon.min.js<?php echo versionQuery();?>"></script>
   
   <script src="/common/barcodeScanner.js<?php echo versionQuery();?>"></script>
   <script src="/common/common.js<?php echo $versionQuery ?>"></script>
   <script src="/common/validate.js<?php echo $versionQuery ?>"></script>
   <script src="/script/common/common.js<?php echo $versionQuery ?>"></script>
   <script src="/script/common/commonDefs.php<?php echo $versionQuery ?>"></script>
   <script src="/script/common/menu.js<?php echo $versionQuery ?>"></script>
   <script src="/script/common/validator.js<?php echo $versionQuery ?>"></script>   
   <script src="/script/page/<?php echo $javascriptFile ?>"></script>
   
   <style>
      .unexpected {
         backgroundColor: #FFD1D1;
      }
   </style>
      
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
        
         <?php echo $form ?>
         
         <div id="audit-line-table"></div>
         
         <br>
         
         <div class="flex-horizontal flex-h-center">
            <button id="cancel-button" style="margin-right:15px">Cancel</button>
            <button id="save-progress-button" class="accent-button" style="margin-right:15px"><?php echo isset($saveButtonLabel) ? $saveButtonLabel : "Save" ?></button>
            <button id="complete-button" class="accent-button" style="margin-right:15px">Complete</button>
            <button id="apply-button" class="accent-button">Apply</button>            
         </div>
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>
      preserveSession();
   
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo $appPageId ?>);  
         
      var PAGE = new Audit(Audit.PageMode.PERFORM_AUDIT, <?php echo $auditStatus ?>, <?php echo $isAdHoc ?>, <?php echo $hasCorrections ?>);
      PAGE.createAuditLineTable("audit-line-table");

      // Store the initial state of the form, for change detection.
      setInitialFormState("<?php echo $formId ?>");
      
      // Listen for barcodes.
      var barcodeScanner = new BarcodeScanner();
      barcodeScanner.onBarcode = PAGE.onBarcode.bind(PAGE);
   </script>
   
</body>

</html>
