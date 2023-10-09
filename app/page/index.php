<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/router.php';
require_once ROOT.'/app/page/customerPage.php';
require_once ROOT.'/app/page/quotePage.php';
require_once ROOT.'/app/page/userPage.php';

// *****************************************************************************
//                                   Begin

session_start();

$router = new Router();
$router->setLogging(false);

$router->add("customer", function($params) {
   (new CustomerPage())->handleRequest($params);
});

$router->add("user", function($params) {
   (new UserPage())->handleRequest($params);
});

$router->add("quote", function($params) {
   (new QuotePage())->handleRequest($params);
});
   
$router->route();

?>