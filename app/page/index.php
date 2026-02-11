<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/router.php';
require_once ROOT.'/app/page/auditPage.php';
require_once ROOT.'/app/page/correctiveActionPage.php';
require_once ROOT.'/app/page/customerPage.php';
require_once ROOT.'/app/page/jobPage.php';
require_once ROOT.'/app/page/maintenanceTicketPage.php';
require_once ROOT.'/app/page/notificationPage.php';
require_once ROOT.'/app/page/partPage.php';
require_once ROOT.'/app/page/prospiraDocPage.php';
require_once ROOT.'/app/page/quotePage.php';
require_once ROOT.'/app/page/salesOrderPage.php';
require_once ROOT.'/app/page/schedulePage.php';
require_once ROOT.'/app/page/shipmentPage.php';
require_once ROOT.'/app/page/userPage.php';

// *****************************************************************************
//                                   Begin

session_start();

$router = new Router();
$router->setLogging(true);

$router->add("audit", function($params) {
   (new AuditPage())->handleRequest($params);
});

$router->add("correctiveAction", function($params) {
   (new CorrectiveActionPage())->handleRequest($params);
});

$router->add("customer", function($params) {
   (new CustomerPage())->handleRequest($params);
});

$router->add("job", function($params) {
   (new JobPage())->handleRequest($params);
});

$router->add("maintenanceTicket", function($params) {
   (new MaintenanceTicketPage())->handleRequest($params);
});

$router->add("notification", function($params) {
   (new NotificationPage())->handleRequest($params);
});

$router->add("part", function($params) {
   (new PartPage())->handleRequest($params);
});

$router->add("prospiraDoc", function($params) {
   (new ProspiraDocPage())->handleRequest($params);
});

$router->add("user", function($params) {
   (new UserPage())->handleRequest($params);
});

$router->add("quote", function($params) {
   (new QuotePage())->handleRequest($params);
});

$router->add("salesOrder", function($params) {
   (new SalesOrderPage())->handleRequest($params);
});

$router->add("schedule", function($params) {
   (new SchedulePage())->handleRequest($params);
});

$router->add("shipment", function($params) {
   (new ShipmentPage())->handleRequest($params);
});
   
$router->route();

?>