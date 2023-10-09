<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once '../common/authentication.php';
require_once '../common/database.php';
require_once '../common/header.php';
require_once '../common/materialEntry.php';
require_once '../common/materialTicket.php';
require_once '../common/params.php';
require_once '../common/printerInfo.php';

function getParams()
{
   static $params = null;
   
   if (!$params)
   {
      $params = Params::parse();
   }
   
   return ($params);
}

function getMaterialTicketId()
{
   $materialTicketId = MaterialEntry::UNKNOWN_ENTRY_ID;
   
   $params = getParams();
   
   if ($params->keyExists("materialTicketId"))
   {
      $materialTicketId = $params->getInt("materialTicketId");
   }
   
   return ($materialTicketId);
}

function getMaterialTicket()
{
   $materialTicket = null;
   
   $materialTicketId = getMaterialTicketId();
   
   if ($materialTicketId != MaterialTicket::UNKNOWN_MATERIAL_TICKET_ID)
   {
      $materialTicket = new MaterialTicket($materialTicketId);
   }
   
   return ($materialTicket);
}

function getPrinterOptions()
{
   $options = "";
   
   $database = PPTPDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getPrinters();
      
      while ($result && ($row = $result->fetch_assoc()))
      {
         $printerInfo = PrinterInfo::load($row["printerName"]);
         
         if ($printerInfo && $printerInfo->isCurrent())
         {
            $displayName = $printerInfo->printerName;
            if (strrpos($printerInfo->printerName, "\\") !== false)
            {
               $displayName = substr($printerInfo->printerName, strrpos($printerInfo->printerName, "\\") + 1);
            }
            
            $disabled = "";
            $selected = "";
            if (!$printerInfo->isConnected)
            {
               $displayName .= " (offline)";
               $disabled = "disabled";
               
               if (isset($_SESSION["preferredPrinter"]) &&
                  ($printerInfo->printerName == $_SESSION["preferredPrinter"]))
               {
                  $selected = "selected";
               }
            }
            
            $options .= "<option value=\"$printerInfo->printerName\" $selected $disabled>$displayName</option>";
         }
      }
   }
   
   return ($options);
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
   
   <script src="/common/common.js"></script>   
   <script src="/common/materialTicket.js"></script>
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script>
   <!-- script src="https://www.labelwriter.com/software/dls/sdk/js/DYMO.Label.Framework.3.0.js" type="text/javascript" charset="UTF-8"></script-->
   <script src="/thirdParty/dymo/DYMO.Label.Framework.3.0.js" type="text/javascript" charset="UTF-8"></script>
      
</head>

<body class="flex-vertical flex-top flex-left">

   <form id="input-form" action="" method="POST">
      <input type="hidden" name="materialTicketId" value="<?php echo getMaterialTicketId(); ?>">
   </form>

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading">Print Material Tickets</div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div id="description" class="description">Blah blah blah.</div>
         
         <br>
         
         <div class="flex-horizontal flex-v-center">
         
           <div style="margin-right: 50px;">
              <img id="material-ticket-image" src="" alt="material ticket"/>
           </div>
           
            <div class="flex-vertical">
   
                  <div class="form-item">
                     <div class="form-label">Printer</div>
                     <select id="printer-input" type="text" name="printerName" form="input-form">
                        <?php echo getPrinterOptions(); ?>
                     </select>
                  </div>
   
                  <div class="form-item">
                     <div class="form-label">Copies</div>
                     <input id="copies-input" type="number" name="copies" form="input-form" style="width:50px;" value="1">
                  </div>
   
            </div>
            
         </div>
         
         <br>
         <br>
         
         <div class="flex-horizontal flex-h-center">
            <button id="cancel-button">Cancel</button>&nbsp;&nbsp;&nbsp;
            <button id="print-button" class="accent-button">Print</button>            
         </div>
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::MATERIAL ?>); 
      
      preserveSession();

      function printMaterialTicket()
      {
         var form = document.querySelector('#input-form');
         
         var xhttp = new XMLHttpRequest();
      
         // Bind the form data.
         var formData = new FormData(form);
      
         // Define what happens on successful data submission.
         xhttp.addEventListener("load", function(event) {
            try
            {
               var json = JSON.parse(event.target.responseText);
      
               if (json.success == true)
               {
                  alert("Print job was successfully queued.");
               }
               else
               {
                  alert(json.error);
               }
            }
            catch (expection)
            {
               console.log("JSON syntax error");
               console.log(this.responseText);
            }
         });
      
         // Define what happens on successful data submission.
         xhttp.addEventListener("error", function(event) {
           alert('Oops! Something went wrong.');
         });
      
         // Set up our request
         requestUrl = "../api/printMaterialTicket/"
         xhttp.open("POST", requestUrl, true);
      
         // The data sent is what the user provided in the form
         xhttp.send(formData);         
      }      
      
      // Setup event handling on all DOM elements.
      document.getElementById("cancel-button").onclick = function(){window.history.back();};
      document.getElementById("print-button").onclick = printMaterialTicket;
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
      
      dymo.label.framework.init(function() {
         var label = new MaterialTicket(<?php echo getMaterialTicketId() ?>, "material-ticket-image");
      });
   </script>
   
</body>

</html>
