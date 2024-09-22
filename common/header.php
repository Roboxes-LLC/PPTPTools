<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/core/manager/notificationManager.php';

class Header
{
   public static function render($pageTitle, $showMenuButton = true)
   {
      global $ROOT;
         
      $menuButtonHidden = ($showMenuButton == true) ? "" : "hidden";
      
      echo 
<<<HEREDOC
      <div class="header">
         <div id="menu-button" class="menu-button $menuButtonHidden"><i class="menu-icon material-icons action-button-icon">menu</i></div>
         <div class="flex-horizontal" style="justify-content: space-between; width: 100%; padding-right:20px;">
            <div class="page-title">$pageTitle</div>
HEREDOC;
         
      if (Authentication::isAuthenticated())
      {
         $username = Authentication::getAuthenticatedUser()->username;
         
         $notificationCount = NotificationManager::getUnacknowledgedAppNotificationCount(Authentication::getAuthenticatedUser()->employeeNumber);
         
         echo
<<<HEREDOC
            <div class="flex-horizontal flex-v-center">
               <div id="notification-count-container" class="notification-indicator clickable" data-count="$notificationCount" style="position:relative" onclick="">
                  <i class="material-icons-outlined" style="margin-right:5px; color: #ffffff; font-size: 24px;">notifications</i>
                  <div id="notification-count-indicator" class="flex-horizontal flex-v-center flex-h-center notification-count-indicator">$notificationCount</div>
               </div>
               <i class="material-icons" style="margin-right:5px; color: #ffffff; font-size: 24px;">person</i>
               <div class="nav-username">$username&nbsp | &nbsp</div>
               <a class="nav-link" href="$ROOT/login.php?action=logout">Logout</a>
            </div>
HEREDOC;
      }
            
      echo "</div></div>";
      
      echo
<<<HEREDOC
      <script>
         document.getElementById("menu-button").addEventListener('click', function() {
            menu.toggle(menu.menuElement);
         });

         document.getElementById("notification-count-container").addEventListener('click', function() {
            document.location = "/notification/notifications.php";
         });

         setInterval(function() {
            ajaxRequest("/app/page/notification/?request=fetch_app_notifcations_count", function(response) {
               if (response.success == true)
               {
                  document.getElementById("notification-count-container").dataset.count = response.count;
                  document.getElementById("notification-count-indicator").innerHTML = response.count;
               }
               else
               {
                  console.log(response.error);
               }
            });
          }, 
          10000);
      </script>
HEREDOC;
   }
}
?>