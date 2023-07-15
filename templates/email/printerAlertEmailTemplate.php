<html>
   <head>
      <style>
         body {
            font-family: Helvetica, sans-serif; 
            font-size: 14pt; 
            width:800px;
         }
         
         .header {
            border-bottom: solid; 
            border-color: #2471A3; 
            border-width: 5px; 
            padding:10px;
            margin-bottom: 15px;
         }
         
         .header-left, .header-right {
            float: left;
            width: 50%;
         }
         
         .header-right {
            text-align: right;
         }
         
         .title {
            font-size: 18pt; 
            color: #2471A3;
         }
         
         .site-name {
            font-size: 18pt;
         }
         
         .content {
            padding:10px;
         }
         
         .copy {
            font-weight: bold;
            margin-bottom: 15px;
         }
         
         table {
            border-collapse: collapse;
            width: 800px;
            margin-bottom: 25px;
         }
         
         tr.table-header {
            background-color: #6a5acd; 
            color: white;
         }
         
         tr.customer-header {
            background-color: #2f2475; 
         }
         
         tr.customer-header .customer-name {
            float: left;
         }

        tr.customer-header .cart-count {
            float: right;
         }

         th, td {
            border: 1px solid #ddd; 
            padding: 8px;
         }
         
         td.overdue {
            color: red;
         }
         
         a {
            text-decoration: none;
            color: inherit;
         }
         
         .printer.removed {
            color: red;
         }
         
         .printer .new-indicator {
            background-color: yellow;
         }
      </style>
   </head>
   <body>
      <div class="header">
         <div class="header-left title">PPTP Tools - Printer Monitor</div>
         <div class="header-right">
            <img alt="PPTP Logo" src="https://tools.pittsburghprecision.com<?php echo $templateParams->logoSrc; ?>" width="150"/-->
         </div>
         <div style="clear: both;"></div>
      </div>
      <div class="content">
         <p>The status of the PPTP Tools label printers has changed.</p>
      
         <p><b>Current Label Printers</b></p>
         <?php echo $templateParams->printers ?>
         
         <p><b>Removed Label Printers</b></p>
         <?php echo $templateParams->removedPrinters ?>
      </div>
   </body>
</html>