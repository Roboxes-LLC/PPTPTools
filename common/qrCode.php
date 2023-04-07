<?php

if (!defined('ROOT')) require_once '../root.php';
require_once 'params.php';
require_once ROOT.'/thirdParty/phpqrcode/phpqrcode.php';

$params = Params::parse();

if ($params->keyExists("qrCodeContent"))
{
   QRCode::png($params->get("qrCodeContent"));
}
?>