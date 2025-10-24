<?php

if (!defined('ROOT')) require_once 'root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/params.php';

function getParams()
{
   static $params = null;
   
   if (!$params)
   {
      $params = Params::parse();
   }
   
   return ($params);
}

function getAction()
{
   $params = getParams();
   
   return ($params->get("action"));
}

function getUsername()
{
   $params = getParams();
   
   return ($params->get("username"));
}

function getPassword()
{
   $params = getParams();
   
   return ($params->get("password"));
}

function redirect($url)
{
   unset($_SESSION["redirect"]);
   
   header("Location: $url");
   exit;
}

function getLoginFailureText($authenticationResult)
{
   $text = "";
   
   if ($authenticationResult == AuthenticationResult::INVALID_USERNAME)
   {
      $text = "A user by that name could not be found in PPTP Tools.  Contact your supervisor to be added to the system.";
   }
   else if ($authenticationResult == AuthenticationResult::INVALID_PASSWORD)
   {
      $text = "The supplied password is incorrect.  Contact your supervisor if you forgot or need to reset your password.";
   }
   
   return ($text);
}

// *****************************************************************************

session_start();

$params = Params::parse();

$authenticationResult = null;

if (getAction() == "logout")
{
   Authentication::deauthenticate();
   
   session_unset();
}
else if (getAction() == "login")
{
   $authenticationResult = Authentication::authenticate();
}

if (Authentication::isAuthenticated())
{
   if ($params->keyExists("redirect"))
   {
      redirect($params->keyExists("redirect"));
   }
   else
   {
      $roleIds = Role::getRolesFromBitset(Authentication::getAuthenticatedUser()->roles);
      
      if (!empty($roleIds))
      {
         $role = Role::getRole($roleIds[0]);
         
         $appPage = AppPage::getAppPage($role->defaultAppPage);
         
         if ($appPage)
         {
            redirect($appPage->URL);
         }
      }
   }
}

?>

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">

   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons"/>
   
   <link rel="stylesheet" type="text/css" href="common/theme.css"/>
   <link rel="stylesheet" type="text/css" href="common/common.css"/>
      
</head>

<body class="flex-vertical flex-top flex-left" style="background: url('images/PPTPFloor.jpg') center / cover; height: 100%;">

   <form id="input-form" action="" method="POST">
      <input type="hidden" name="action" value="login">
   </form>

   <?php Header::render("PPTP Tools", false); ?>
   
   <div class="main flex-horizontal flex-h-center flex-v-center" style="width: 100%">

      <div class="flex-vertical" style="width: 500px; background-color: white;">
      
         <div style="background: url('images/parts.jpg') center / cover; height: 200px; width: 100%"></div>
         
         <div style="padding: 20px;">
            
            <div>
               <b>Pittsburgh Precision Tools</b> is an online production monitoring toolkit that brings
               together a suite of data entry and analysis software, giving you a clear window into your shop's 
               daily operations. 
               <br>
               <br>
               Login and let's get started.
            </div>
            
            <br>
            
            <div class="flex-vertical flex-h-center">
            
               <div class="flex-horizontal flex-h-center" style="margin-top: 20px; width:100%;">
                  <label class="form-label" for="username_input">Username</label>
                  &nbsp
                  <input type="text" class="medium-text-input" name="username" form="input-form" value="<?php echo getUsername(); ?>">
               </div>
               
               <div class="flex-horizontal flex-h-center" style="margin-top: 20px; width:100%;">
                  <label class="form-label" for="password_input">Password</label>
                  &nbsp
                  <input type="password" class="medium-text-input" name="password" form="input-form" value="<?php echo getPassword(); ?>">
               </div>
               
               <div class="flex-horizontal flex-h-center" style="margin-top: 20px; width:100%; color: red;">
                 <?php echo getLoginFailureText($authenticationResult); ?>
              </div>
               
               <div class="flex-horizontal" style="margin-top: 20px; justify-content: center;">
                  <button id="login-button" type="submit" class="accent-button" form="input-form">Login</button>
               </div>

            </div>
            
         </div>
         
      </div>
      
   </div> <!-- main -->
   
   <script>
      // Setup event handling on all DOM elements.
      document.getElementById("login-button").onclick = function(){login();};
   </script>
   
</body>

</html>
