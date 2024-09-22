<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/router.php';
require_once ROOT.'/app/page/customerPage.php';
require_once ROOT.'/app/page/jobPage.php';
require_once ROOT.'/app/page/notificationPage.php';
require_once ROOT.'/app/page/quotePage.php';
require_once ROOT.'/app/page/schedulePage.php';
require_once ROOT.'/app/page/shipmentPage.php';
require_once ROOT.'/app/page/userPage.php';

// *****************************************************************************
//                                   Begin

session_start();

$router = new Router();
$router->setLogging(false);

$router->add("customer", function($params) {
   (new CustomerPage())->handleRequest($params);
});

$router->add("job", function($params) {
   (new JobPage())->handleRequest($params);
});

$router->add("notification", function($params) {
   (new NotificationPage())->handleRequest($params);
});

$router->add("user", function($params) {
   (new UserPage())->handleRequest($params);
});

$router->add("quote", function($params) {
   (new QuotePage())->handleRequest($params);
});

$router->add("schedule", function($params) {
   (new SchedulePage())->handleRequest($params);
});

$router->add("shipment", function($params) {
   (new ShipmentPage())->handleRequest($params);
});
   
$router->route();

?>