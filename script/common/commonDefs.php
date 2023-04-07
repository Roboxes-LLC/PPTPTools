<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/component/skid.php';

header('Content-Type: text/javascript');

?>

UNKNOWN_SKID_ID = <?php echo Skid::UNKNOWN_SKID_ID ?>;
