<!DOCTYPE html>
<html>
   <head>

   <title>Prospira data sheet - Part # <?php echo $partNumber ?></title>
   
   <link rel="stylesheet" type="text/css" href="../common/theme.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="../common/common.css<?php echo versionQuery();?>"/>

   <style>
     /* Styles for the screen view */
     body {
       font-family: sans-serif;
       margin: 20px;
     }
     
     .screen-only {
       background-color: lightgray;
       padding: 10px;
       text-align: center;
     }
     
     .content-container {
        width:1200px;
     }
   
     /* Styles for the print view */
     @media print {
       @page {
         size: letter; /* 8.5in x 11in */
         margin: 0.5in;
       }
       body {
         margin: 0; /* Remove body margin to let @page margin take over */
       }
       .screen-only {
         display: none; /* Hide elements not needed for printing */
       }
       /* Ensure content flows properly */
       .content-container {
         width: 100%;
         box-sizing: border-box; /* Include padding/border in the width */
       }
     }
     
     .content-box {
        border: 3px solid;
        padding: 0.1in;
        margin: -1px;
     }
          
     .section-label.large {
        font-size: 20pt;
     }
     
     .section-label.small {
        font-size: 16pt;
        font-weight: bold;
     }
     
     .section-label.center {
        text-align: center;
     }
     
     .section-label.bold {
        font-weight: bold;
     }
     
     .section-label.right {
        text-align: right;
     }
     
     .data {
        font-size: 30pt;
        font-weight: bold;
        text-align: center;
     }
     
     .data.medium {
        font-size: 20pt;
     }
     
     .data.empty {
        height: 0.4in;
     }
     
     .data.empty.large {
        height: 0.6in;
     }
     
     .stretch {
        width: 100%;
     }
     
     .seperator {
        padding: 0;
        height: 0.2in;
        background: #A0A0A0;
        margin: -1px;
        border: 3px solid;
     }
     
     .iso {
        font-size: 12pt;
        margin-top: 10px;
        margin-left: 50px;
     }
   </style>
</head>

   <body>
   
   <div class="content-container">
      <div id="section-1" class="flex-vertical section">
         <div class="flex-horizontal section-row">
            <div class="flex-vertical content-box" style="flex-grow: 75">
               <div class="section-label large" style="margin-bottom: 0.15in">RAW PART #</div>
               <div id="part-number" class="flex-h-center data"><?php echo $partNumber ?></div>
            </div>
            <div class="flex-vertical content-box" style="flex-grow: 10">
               <div class="section-label large center" style="margin-bottom: 0.15in">Date</div>
               <div id="date" class="flex-h-center data medium"><?php echo $formattedDate ?></div>
            </div>
            <div class="flex-vertical content-box" style="flex-grow: 15">
               <div class="section-label large center" style="margin-bottom: 0.15in">Quantity</div>
               <div id="date" class="flex-h-center data"><?php echo $quantity ?></div>
            </div>
         </div>
         <div class="flex-horizontal section-row">
            <div class="flex-horizontal content-box flex-v-center flex-right section-label large" style="flex-grow: 25">Raw Part Lot #</div>
            <div class="flex-h-center content-box data" style="flex-grow: 75"><?php echo $lotNumber ?></div>
         </div>
      </div>
      <div class="seperator"></div>
      <div id="section-2" class="flex-vertical section">
         <div class="flex-horizontal section-row">
            <div class="flex-vertical content-box" style="flex-grow: 50">
               <div class="flex-horizontal flex-v-center" style="margin-bottom: 0.15in">
                  <div class="section-label large" style="margin-right: 20px">PHOS PART #</div>
                  <div class="section-label tiny">(Chromate / Anodize if applic.)</div>
               </div>
               <div id="part-number" class="flex-h-center data empty"></div>
            </div>
            <div class="flex-vertical content-box" style="flex-grow: 25">
               <div class="section-label large center" style="margin-bottom: 0.15in">Date</div>
               <div id="date" class="flex-h-center data empty"></div>
            </div>
            <div class="flex-vertical content-box" style="flex-grow: 25">
               <div class="section-label large center" style="margin-bottom: 0.15in">Quantity</div>
               <div id="date" class="flex-h-center data empty"></div>
            </div>
         </div>
      </div>
      <div class="seperator"></div>
      <div id="section-3" class="flex-vertical section">
         <div class="flex-horizontal section-row">
            <div class="flex-vertical content-box" style="flex-grow: 50">
               <div class="section-label large" style="margin-bottom: 0.15in">BOND PART #</div>
               <div id="part-number" class="flex-h-center data empty"></div>
            </div>
            <div class="flex-vertical content-box" style="flex-grow: 25">
               <div class="section-label large center" style="margin-bottom: 0.15in">Date</div>
               <div id="date" class="flex-h-center data empty"></div>
            </div>
            <div class="flex-vertical content-box" style="flex-grow: 25">
               <div class="section-label large center" style="margin-bottom: 0.15in">Quantity</div>
               <div id="date" class="flex-h-center data empty"></div>
            </div>
         </div>
         <div class="flex-horizontal section-row">
            <div class="content-box section-label large right" style="flex-grow: 25">Sub contr. Lot #</div>
            <div class="flex-h-center content-box data empty" style="flex-grow: 75"></div>
         </div>
      </div>
      <div class="seperator"></div>
      <div id="section-4" class="flex-vertical section">
         <div class="flex-horizontal section-row">
            <div class="content-box section-label large center" style="flex-grow: 100">EXPIRATION DATE </div>
         </div>
         <div class="flex-horizontal section-row">
            <div class="flex-vertical content-box" style="flex-grow: 50">
               <div class="section-label small center" style="margin-bottom: 0.15in">PHOS</div>
               <div class="flex-h-center data empty"></div>
            </div>
            <div class="flex-vertical content-box" style="flex-grow: 50">
               <div class="section-label small center" style="margin-bottom: 0.15in">BOND</div>
               <div class="flex-h-center data empty"></div>
            </div>
         </div>         
      </div>
      <div class="seperator"></div>
      <div id="section-5" class="flex-horizontal section content-box">
         <div class="section-label large" style="flex-grow: 16">NEXT OP:</div>
         <div class="section-label large center bold" style="flex-grow: 16">PHOS</div>
         <div class="section-label large center bold" style="flex-grow: 16">BOND</div>
         <div class="section-label large center bold" style="flex-grow: 16">MOLD</div>
         <div class="section-label large center bold" style="flex-grow: 16">ASSY</div>
         <div class="section-label large center bold" style="flex-grow: 16">SHIP</div>
      </div>
      <div class="seperator"></div>
      <div id="section-6" class="flex-horizontal section">
         <div class="flex-vertical" style="flex-grow:33">
            <div class="content-box section-label large center bold" style="flex-grow: 100">QA "OK" (INCOMING)</div>
            <div class="content-box data empty large"></div>
         </div>
         <div class="flex-vertical" style="flex-grow:33">
            <div class="content-box section-label large center bold" style="flex-grow: 100">PHOS "OK"</div>
            <div class="content-box data empty large"></div>
         </div>
         <div class="flex-vertical" style="flex-grow:33">
            <div class="content-box section-label large center bold" style="flex-grow: 100">BOND "OK"</div>
            <div class="content-box data empty large"></div>
         </div>
      </div>
      <div class="iso">BAPM QF556 Rev. B</div>
   </div>
   
   </body>
</html>
