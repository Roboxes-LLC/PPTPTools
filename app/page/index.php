<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/router.php';
require_once ROOT.'/app/page/customerPage.php';
require_once ROOT.'/app/page/quotePage.php';

// *****************************************************************************
//                                   Begin

session_start();

$router = new Router();
$router->setLogging(true);

$router->add("customer", function($params) {
   (new CustomerPage())->handleRequest($params);
});

$router->add("quote", function($params) {
   (new QuotePage())->handleRequest($params);
});
   
$router->route();

?>