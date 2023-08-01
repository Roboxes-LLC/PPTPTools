<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once '../common/database.php';
require_once '../common/header.php';
require_once '../common/maintenanceEntry.php';
require_once '../common/params.php';
require_once '../common/version.php';

abstract class InputField
{
   const FIRST = 0;
   const ENTRY_DATE = MaintenanceLogInputField::FIRST;
   const MAINTENANCE_DATE = 1;
   const MAINTENANCE_TIME = 2;
   const MAINTENANCE_TYPE = 3;
   const MAINTENANCE_CATEGORY = 4;
   const EMPLOYEE_NUMBER = 5;
   const JOB_NUMBER = 6;
   const WC_NUMBER = 7;
   const PART_NUMBER = 8;
   const COMMENTS = 9;
   const LAST = 10;
   const COUNT = MaintenanceLogInputField::LAST - MaintenanceLogInputField::FIRST;
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

function getHeading()
{
   $heading = "Configure Maintenance Log Settings";
      
   return ($heading);
}

function getDescription()
{
   $description = "Customize maintenance types and categories";
   
   return ($description);
}

// ********************************** BEGIN ************************************

Time::init();

session_start();

if (!Authentication::isAuthenticated())
{
   header('Location: ../login.php');
   exit;
}

?>

<!DOCTYPE html>
<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">

   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons"/>
   
   <link rel="stylesheet" type="text/css" href="../common/theme.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="../common/common.css<?php echo versionQuery();?>"/>
   
   <script src="/common/common.js<?php echo versionQuery();?>"></script>
   <script src="/common/validate.js<?php echo versionQuery();?>"></script>
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script>
   <script src="maintenanceLog.js<?php echo versionQuery();?>"></script>

</head>

<body class="flex-vertical flex-top flex-left">
        
   <form id="input-form" action="" method="POST">
      <!-- Hidden inputs make sure disabled fields below get posted. -->
   </form>

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="main flex-horizontal flex-top flex-left">
   
      <?php Menu::render(); ?>
      
      <div class="content flex-vertical flex-top flex-left">
      
         <div class="flex-horizontal flex-v-center flex-h-center">
            <div class="heading"><?php echo getHeading(); ?></div>&nbsp;&nbsp;
            <i id="help-icon" class="material-icons icon-button">help</i>
         </div>
         
         <div id="description" class="description"><?php echo getDescription(); ?></div>
         
         <br>
         
         <div class="flex-horizontal">
         
            <!--  Maintenance type -->
            <div class="flex-vertical" style="margin-right: 50px;">
               <div>Maintenance Type</div>
               <select id="maintenance-type-input" size="10" style="height: revert; margin-bottom: 10px;"><?php echo MaintenanceEntry::getTypeOptions(MaintenanceEntry::UNKNOWN_TYPE_ID) ?></select>
               <div class="flex-horizontal">
                  <button id="add-maintenance-type-button" style="min-width: revert; padding:revert; width:40px; margin-right:10px;"><i class="menu-icon material-icons">add</i></button>
                  <button id="edit-maintenance-type-button" style="min-width: revert; padding:revert; width:40px; margin-right:10px;"><i class="menu-icon material-icons">edit</i></button>
                  <button id="delete-maintenance-type-button" style="min-width: revert; padding:revert; width:40px; margin-right:10px;"><i class="menu-icon material-icons">delete</i></button>
               </div>
            </div>
            
            <!--  Maintenance Category -->
            <div class="flex-vertical" style="margin-right: 50px;">
               <div>Maintenance Category</div>
               <select id="maintenance-category-input" size="10" style="height: revert; margin-bottom: 10px;"></select>
               <div class="flex-horizontal">
                  <button id="add-maintenance-category-button" style="min-width: revert; padding:revert; width:40px; margin-right:10px;"><i class="menu-icon material-icons">add</i></button>
                  <button id="edit-maintenance-category-button" style="min-width: revert; padding:revert; width:40px; margin-right:10px;"><i class="menu-icon material-icons">edit</i></button>
                  <button id="delete-maintenance-category-button" style="min-width: revert; padding:revert; width:40px; margin-right:10px;"><i class="menu-icon material-icons">delete</i></button>
               </div>
            </div>
            
            <!--  Maintenance Subcategory -->
            <div class="flex-vertical">
               <div>Maintenance Subcategory</div>
               <select id="maintenance-subcategory-input" size="10" style="height: revert; margin-bottom: 10px;"></select>
               <div class="flex-horizontal">
                  <button id="add-maintenance-category-button" style="min-width: revert; padding:revert; width:40px; margin-right:10px;"><i class="menu-icon material-icons">add</i></button>
                  <button id="edit-maintenance-category-button" style="min-width: revert; padding:revert; width:40px; margin-right:10px;"><i class="menu-icon material-icons">edit</i></button>
                  <button id="delete-maintenance-category-button" style="min-width: revert; padding:revert; width:40px; margin-right:10px;"><i class="menu-icon material-icons">delete</i></button>
               </div>
            </div>
         
         </div>
      
      </div> <!-- content -->
     
   </div> <!-- main -->   
         
   <script>
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::MAINTENANCE_LOG ?>);  
      
      preserveSession();
      
      // Setup event handling on all DOM elements.

   </script>

</body>

</html>
