<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once '../common/activity.php';
require_once '../common/authentication.php';
require_once '../common/header.php';
require_once '../common/inspection.php';
require_once '../common/inspectionTemplate.php';
require_once '../common/jobInfo.php';
require_once '../common/oasisReport/oasisReport.php';
require_once '../common/params.php';
require_once '../common/root.php';
require_once '../common/userInfo.php';

function getParams()
{
   static $params = null;
   
   if (!$params)
   {
      $params = Params::parse();
   }
   
   return ($params);
}

function getInspectionId()
{
   $params = getParams();
   
   return ($params->keyExists("inspectionId") ? $params->get("inspectionId") : Inspection::UNKNOWN_INSPECTION_ID);
}

function getOasisReport()
{
   static $oasisReport = null;
   
   if ($oasisReport == null)
   {
      $inspectionId = getInspectionId();
      
      if ($inspectionId != Inspection::UNKNOWN_INSPECTION_ID)
      {
         $oasisReport = OasisReport::load($inspectionId);
      }
   }
   
   return ($oasisReport);
}

function getHeading()
{
   $heading = "Oasis Inspection Report";
   
   return ($heading);
}

function getDescription()
{
   $description = "";
   
   return ($description);
}

function getUserField($userField)
{
   $value = "";
   
   $oasisReport = getOasisReport();
   
   if ($oasisReport)
   {
      $value = $oasisReport->getUserField($userField)->getValue();
   }
   
   return ($value);
}

function getInspector()
{
   $inspector = "";
   
   $oasisReport = getOasisReport();
   
   if ($oasisReport)
   {
      $employeeNumber = intval($oasisReport->getUserField(UserFieldType::EMPLOYEE_NUMBER)->getValue());
      
      $userInfo = UserInfo::load($employeeNumber);
      
      if ($userInfo)
      {
         $inspector = $employeeNumber . " - " . $userInfo->getFullName();
      }
      else
      {
         $inspector = $employeeNumber;
      }
   }
   
   return ($inspector);
}

function getSamples()
{
   $html = "";
   
   $oasisReport = getOasisReport();
   
   if ($oasisReport)
   {
      /*
      for ($i = 0; $i < $oasisReport->getPartInspectionCount(); $i++)
      {
         $inspection = $oasisReport->getPartInspection($i);
         
         if ($inspection)
         {
            $html .= $inspection->toHtml();
         }
      }
      */
      
      $html .= $oasisReport->toHtml();
   }
   
   return ($html);
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
   
   <link rel="stylesheet" type="text/css" href="../common/theme.css"/>
   <link rel="stylesheet" type="text/css" href="../common/common.css"/>
   <link rel="stylesheet" type="text/css" href="../common/oasisReport/oasisReport.css"/>
   <link rel="stylesheet" type="text/css" href="inspection.css"/>
   
   <script src="/common/common.js"></script>
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script> 

</head>

<body class="flex-vertical flex-top flex-left">

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
         
         <!-- div>
            <div>
               <label>Insp. Type</label>
               <input type="text" value="<?php echo getUserField(UserFieldType::INSPECTION_TYPE) ?>" disabed></input>
            </div>
            
            <div>
               <label>Inspector</label>
               <input type="text" value="<?php echo getInspector() ?>" disabed></input>
            </div>
            
            <div>
               <label>Comments</label>
               <input type="text" value="<?php echo getUserField(UserFieldType::COMMENTS) ?>" disabed></input>
            </div>
            
            <div>
               <label>Parts Made</label>
               <input type="text" value="<?php echo getUserField(UserFieldType::PART_COUNT) ?>" disabed></input>
            </div>
            
            <div>
               <div>Sample Size</div>
               <input type="text" value="<?php echo getUserField(UserFieldType::SAMPLE_SIZE) ?>" disabed></input>
            </div>
            
            <div>
               <div>Machine Number</div>
               <input type="text" value="<?php echo getUserField(UserFieldType::MACHINE_NUMBER) ?>" disabed></input>
            </div>
            
            <div>
               <div>Date</div>
               <input type="text" value="<?php echo getUserField(UserFieldType::DATE) ?>" disabed></input>
            </div>
            
            <div>
               <div>Part Number</div>
               <input type="text" value="<?php echo getUserField(UserFieldType::PART_NUMBER) ?>" disabed></input>
            </div>     
            
            <div>
               <div>Efficiency</div>
               <input type="text" value="<?php echo getUserField(UserFieldType::EFFICIENCY) ?>" disabed></input>
            </div>          
         </div-->
         
         <?php echo getSamples() ?>
         
         <br>
         
         <div class="flex-horizontal flex-h-center">
            <button id="ok-button">Ok</button>
         </div>
      
      </div> <!-- content -->
     
   </div> <!-- main -->   
         
   <script>
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::INSPECTION ?>); 
      
      preserveSession();

      // Setup event handling on all DOM elements.
      document.getElementById("ok-button").onclick = function(){window.history.back();};
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
   </script>

</body>

</html>
