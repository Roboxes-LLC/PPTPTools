<?php
/*
if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/userInfo.php';
$templateParams = new stdClass();
$templateParams->date = "9/6/2023";
$templateParams->coverText = "Blah blah blah";
$templateParams->company = new stdClass();
$templateParams->company->companyName = "Pittsburgh Precision Turned Products";
$templateParams->author = UserInfo::load(1975);
*/
?>

<html>
   <head>
      <style>
      </style>
   </head>
   <body style="font-family: Helvetica, sans-serif; font-size: 14pt;">
      <div style="border-bottom: solid; padding:10px; height: 50px;">
         <img style="float: left; margin-right: 20px;" src="https://tools.pittsburghprecision.com/images/pptp-logo-256x256.png" width="50"/>
         <div style="float: left; height:50px; line-height: 50px;"><?php echo $templateParams->company->companyName ?></div>
         <div style="float: right; height:50px; line-height: 50px;"><?php echo $templateParams->date ?></div>
      </div>
      <div style="clear:both"></div>
      <div style="padding:10px;">
         <p><?php echo $templateParams->coverText ?></p>
      </div>
   </body>
</html>