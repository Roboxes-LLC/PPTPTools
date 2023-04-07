<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/app/page/skidPage.php';
require_once ROOT.'/core/common/router.php';

// *****************************************************************************
//                                   Begin

session_start();

$router = new Router();
$router->setLogging(true);

$router->add("skid", function($params) {
   (new SkidPage())->handleRequest($params);
});
   
$router->route();
?>