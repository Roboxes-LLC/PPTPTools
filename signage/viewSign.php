<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/menu.php';
require_once '../common/activity.php';
require_once '../common/header.php';
require_once '../common/params.php';
require_once '../common/signInfo.php';

abstract class UserInputField
{
   const FIRST = 0;
   const NAME = UserInputField::FIRST;
   const DESCRIPTION = 1;
   const URL = 2;
   const LAST = 3;
   const COUNT = UserInputField::LAST - UserInputField::FIRST;
}

abstract class View
{
   const NEW_SIGN = 0;
   const VIEW_SIGN = 1;
   const EDIT_SIGN = 2;
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

function getView()
{
   $view = View::VIEW_SIGN;
   
   if (getSignId() == SignInfo::UNKNOWN_SIGN_ID)
   {
      $view = View::NEW_SIGN;
   }
   else if (Authentication::checkPermissions(Permission::EDIT_SIGN))
   {
      $view = View::EDIT_SIGN;
   }
   
   return ($view);
}

function isEditable($field)
{
   $view = getView();
   
   // Start with the edit mode, as dictated by the view.
   $isEditable = (($view == View::NEW_SIGN) ||
                  ($view == View::EDIT_SIGN));
   
   return ($isEditable);
}

function getDisabled($field)
{
   return (isEditable($field) ? "" : "disabled");
}

function getSignId()
{
   $signId = SignInfo::UNKNOWN_SIGN_ID;
   
   $params = getParams();
   
   if ($params->keyExists("signId"))
   {
      $signId = $params->getInt("signId");
   }
   
   return ($signId);
}

function getSignInfo()
{
   static $signInfo = null;
   
   if ($signInfo == null)
   {
      $signId = getSignId();
      
      if ($signId != SignInfo::UNKNOWN_SIGN_ID)
      {
         $signInfo = SignInfo::load($signId);
      }
      else
      {
         $signInfo = new SignInfo();
      }
   }
   
   return ($signInfo);
}

function getHeading()
{
   $heading = "";
   
   switch (getView())
   {
      case View::NEW_SIGN:
      {
         $heading = "New Sign";
         break;
      }
      
      case View::EDIT_SIGN:
      {
         $heading = "Edit Sign";
         break;
      }
      
      case View::VIEW_SIGN:
      default:
      {
         $heading = "View Sign";
         break;
      }
   }

   return ($heading);
}

function getDescription()
{
   $description = "";
   
   switch (getView())
   {
      case View::NEW_SIGN:
      {
         $description = "Start by giving your new sign a name (ex. \"Entrance sign\") and meaningful description (ex. \"Main entrance by front doors\").<br><br>Then specify the URL of the Rapsberri Pi computer running the sign.";
         break;
      }
         
      case View::EDIT_SIGN:
      {
         $description = "You may revise any of the fields for sign and then select save when you're satisfied with the changes.";
         break;
      }
         
      case View::VIEW_SIGN:
      default:
      {
         $description = "View this sign's configuration.";
      }
   }
   
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
   
   <link rel="stylesheet" type="text/css" href="../common/theme.css"/>
   <link rel="stylesheet" type="text/css" href="../common/common.css"/>
   
   <script src="/common/common.js"></script>
   <script src="/common/validate.js"></script>
   <script src="/script/common/menu.js<?php echo versionQuery();?>"></script>  
   <script src="signage.js"></script>

</head>

<body class="flex-vertical flex-top flex-left">
        
   <form id="input-form" action="" method="POST">
         <input type="hidden" name="signId" value="<?php echo getSignInfo()->signId; ?>">   
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
         
         <div class="flex-column">

            <div class="form-item">
               <div class="form-label">Name</div>
               <input id="sign-name-input" type="text" name="name" form="input-form" maxlength="64" style="width:300px;" value="<?php echo getSignInfo()->name; ?>" <?php echo getDisabled(UserInputField::NAME); ?>/>
            </div>

            <div class="form-item">
               <div class="form-label">Description</div>
               <input id="sign-description-input" type="text" name="description" form="input-form" maxlength="64" style="width:300px;" value="<?php echo getSignInfo()->description; ?>" <?php echo getDisabled(UserInputField::DESCRIPTION); ?>/>
            </div>

            <div class="form-item">
               <div class="form-label">URL</div>
               <input id="sign-url-input" type="text" name="url" form="input-form" maxlength="64" style="width:300px;" value="<?php echo getSignInfo()->url; ?>" <?php echo getDisabled(UserInputField::URL); ?>/>
            </div>

         </div>         
         
         <div class="flex-horizontal flex-h-center">
            <button id="cancel-button">Cancel</button>&nbsp;&nbsp;&nbsp;
            <button id="save-button" class="accent-button">Save</button>            
         </div>
      
      </div> <!-- content -->
     
   </div> <!-- main -->   
         
   <script>
      var menu = new Menu("<?php echo Menu::MENU_ELEMENT_ID ?>");
      menu.setMenuItemSelected(<?php echo AppPage::SIGNAGE ?>); 
      
      preserveSession();
      
      var employeeNumberValidator = new IntValidator("employee-number-input", 4, 1, 9999, false);
      employeeNumberValidator.init();

      // Setup event handling on all DOM elements.
      document.getElementById("cancel-button").onclick = function(){window.history.back();};
      document.getElementById("save-button").onclick = function(){onSaveSign();};      
      document.getElementById("help-icon").onclick = function(){document.getElementById("description").classList.toggle('shown');};
            
   </script>

</body>

</html>
