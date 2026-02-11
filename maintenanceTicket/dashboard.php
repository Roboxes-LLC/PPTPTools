<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/version.php';

// ********************************** BEGIN ************************************

Time::init();

session_start();

Authentication::authenticate();

if (!Authentication::isAuthenticated())
{
   header('Location: /login.php');
   exit;
}

include ROOT.'/templates/maintenanceTicketDashboardTemplate.php'

?>