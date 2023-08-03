<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/component/contact.php';
require_once ROOT.'/core/component/customer.php';
require_once ROOT.'/core/component/quote.php';

header('Content-Type: text/javascript');

?>

UNKNOWN_CUSTOMER_ID = <?php echo Customer::UNKNOWN_CUSTOMER_ID ?>;

UNKNOWN_CONTACT_ID = <?php echo Contact::UNKNOWN_CONTACT_ID ?>;

UNKNOWN_QUOTE_ID = <?php echo Quote::UNKNOWN_QUOTE_ID ?>;
