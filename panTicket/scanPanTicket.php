<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once '../common/authentication.php';
require_once '../common/database.php';
require_once '../common/header.php';
require_once '../common/panTicket.php';
require_once '../common/params.php';

abstract class ScanToFunction
{
   const FIRST = 0;
   const UNKNOWN = ScanToFunction::FIRST;
   const PART_WEIGHT_LOG = 1;
   const PART_WASHER_LOG = 2;
   const LAST = 3;
   const COUNT = ScanToFunction::LAST - ScanToFunction::FIRST;
}

function getParams()
{
   static $params = null;
   
   if (!$params)
   {
      $params = Params::parse();
   }
   
   return ($params);
}

function getScanToFunction()
{
   $scanTo = ScanToFunction::UNKNOWN;
   
   $params = Params::parse();
   
   if ($params->keyExists("scanTo"))
   {
      $scanTo = $params->getInt("scanTo");
   }
   
   return ($scanTo);
}

// *********************************** BEGIN ***********************************

Time::init();

session_start();

if (!Authentication::isAuthenticated())
{
   header('Location: ../home.php');
   exit;
}
?>

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">

   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons"/>
   
   <link rel="stylesheet" type="text/css" href="../common/theme.css"/>
   <link rel="stylesheet" type="text/css" href="../common/common.css"/>
   
   <script src="../common/common.js"></script>
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script>
      
</head>

<body class="flex-vertical flex-top flex-left">

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading">Pan Ticket Scanner</div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div id="description" class="description">Scan a pan ticket as the first step in weighing, washing, or tracking parts.</div>
         
         <br>
         
         <video muted playsinline id="camera" width="500"></video>
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script type="module">
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::TIME_CARD ?>); 

      preserveSession();

      function onScanResult(result)
      {
         if (result != null)
         {
            console.log("onScanResult: " + result);

            // Extract the pan ticket ID from the URL.
            let panTicketId = 0;
            let startPos = (result.lastIndexOf("panTicketId=") + 1);
            if (startPos > 0) 
            {
               panTicketId = parseInt(result.substring(startPos));
            }

            // AJAX call to retrieve gross parts per hour by selected job.
            let requestUrl = "../api/timeCardInfo/?timeCardId=" + panTicketId;
      
            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function()
            {
               if (this.readyState == 4 && this.status == 200)
               {
                  try
                  {
                     var json = JSON.parse(this.responseText);
             
                     if (json.success == true)
                     {
                        if (scanTo == <?php echo (ScanToFunction::PART_WASHER_LOG); ?>)
                        {
                           location.href = "../partWeightLog/partWeightLogEntry.php?timeCardId=" + panTicketId;  
                        }
                        else if (scanTo == <?php echo (ScanToFunction::PART_WEIGHT_LOG); ?>)
                        {
                           location.href = "../partWasherLog/partWasherLogEntry.php?timeCardId=" + panTicketId;
                        }
                        else
                        {
                           location.href = result;
                        }
                     }
                     else
                     {
                        alert("Sorry. This pan ticket is no longer valid.");
                     }
                  }
                  catch (expection)
                  {
                     console.log("JSON syntax error");
                     console.log(this.responseText);
                  }
               }
            };
            xhttp.open("GET", requestUrl, true);
            xhttp.send();
         }
      }

      var scanTo = <?php echo (getScanToFunction()); ?>;

      import QrScanner from "../thirdParty/qrscanner/qr-scanner.min.js";
      QrScanner.WORKER_PATH = "../thirdParty/qrscanner/qr-scanner-worker.min.js";
   
      const video = document.getElementById('camera');
   
      const scanner = new QrScanner(video, onScanResult());
      scanner.start();
      
      // Setup event handling on all DOM elements.
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
   </script>
   
</body>

</html>
