<?php 

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/quote/quoteEmail.php';

// ********************************** BEGIN ************************************

Time::init();

session_start();

if (!Authentication::isAuthenticated())
{
   header('Location: /login.php');
   exit;
}

$params = Params::parse();

$quoteId = $params->get("quoteId");
$notes = $params->get("notes");

$quoteEmail = new QuoteEmail($quoteId);
$quoteEmail->setNotes($notes);

?>

<div style="width: 900px; margin: auto;">
   <?php echo $quoteEmail->getHtml() ?>
</div>