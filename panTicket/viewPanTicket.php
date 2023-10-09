<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once '../common/authentication.php';
require_once '../common/database.php';
require_once '../common/header.php';
require_once '../common/panTicket.php';
require_once '../common/params.php';
require_once '../common/timeCardInfo.php';

function getParams()
{
   static $params = null;
   
   if (!$params)
   {
      $params = Params::parse();
   }
   
   return ($params);
}

function getPanTicketId()
{
   $panTicketId = PanTicket::UNKNOWN_PAN_TICKET_ID;
   
   $params = getParams();
   
   if ($params->keyExists("panTicketId"))
   {
      $panTicketId = $params->getInt("panTicketId");
   }
   
   return ($panTicketId);
}

function getPanTicket()
{
   $panTicket = null;
   
   $panTicketId = getPanTicketId();
   
   if ($panTicketId != PanTicket::UNKNOWN_PAN_TICKET_ID)
   {
      $panTicket = new PanTicket($panTicketId);
   }
   
   return ($panTicket);
}

function renderPanTicket()
{
   $panTicket = getPanTicket();
   
   if ($panTicket)
   {
      $panTicket->render();
   }
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
   <link rel="stylesheet" type="text/css" href="../common/panTicket.css"/>
   
   <script src="/common/common.js"></script>
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script>
      
</head>

<body class="flex-vertical flex-top flex-left">

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading">Pan Ticket</div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div id="description" class="description">Select the action you want to take with this pan ticket.  All the relevant data will be automatically entered for you.</div>
         
         <br>
         
         <div class="flex-horizontal flex-v-center">
         
            <div style="margin-right: 50px;">
               <?php renderPanTicket(); ?>
            </div>
        
            <div class="flex-vertical">
               <button id="edit-time-card-button" class="accent-button">Edit Time Card</button>
               <br>
               <button id="weigh-parts-button" class="accent-button">Weigh Parts</button>
               <br>
               <button id="wash-parts-button" class="accent-button">Wash Parts</button>
               <br>
               <button id="print-copies-button" class="accent-button">Print Copies</button>
            </div>
            
         </div>
         
      </div> <!-- content -->
      
   </div> <!-- main -->
   
   <script>
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::TIME_CARD ?>); 
      
      preserveSession();
      
      // Setup event handling on all DOM elements.
      document.getElementById("edit-time-card-button").onclick = function(){location.href = "../timecard/viewTimeCard.php?timeCardId=<?php echo getPanTicketId(); ?>";};
      document.getElementById("weigh-parts-button").onclick = function(){location.href = "../partWeightLog/partWeightLogEntry.php?timeCardId=<?php echo getPanTicketId(); ?>";};
      document.getElementById("wash-parts-button").onclick = function(){location.href = "../partWasherLog/partWasherLogEntry.php?timeCardId=<?php echo getPanTicketId(); ?>";};
      document.getElementById("print-copies-button").onclick = function(){location.href = "printPanTicket.php?panTicketId=<?php echo getPanTicketId(); ?>";};
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
   </script>
   
</body>

</html>
