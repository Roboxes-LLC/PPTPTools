<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/inspectionDefs.php';
require_once ROOT.'/core/common/correctiveActionDefs.php';
require_once ROOT.'/core/common/shipmentDefs.php';
require_once ROOT.'/core/component/audit.php';
require_once ROOT.'/core/component/contact.php';
require_once ROOT.'/core/component/customer.php';
require_once ROOT.'/core/component/quote.php';
require_once ROOT.'/core/component/salesOrder.php';
require_once ROOT.'/core/component/shipment.php';

header('Content-Type: text/javascript');

?>

UNKNOWN_CUSTOMER_ID = <?php echo Customer::UNKNOWN_CUSTOMER_ID ?>;

UNKNOWN_CONTACT_ID = <?php echo Contact::UNKNOWN_CONTACT_ID ?>;

UNKNOWN_QUOTE_ID = <?php echo Quote::UNKNOWN_QUOTE_ID ?>;

UNKNOWN_SALES_ORDER_ID = <?php echo SalesOrder::UNKNOWN_SALES_ORDER_ID ?>;

UNKNOWN_SHIPMENT_ID = <?php echo Shipment::UNKNOWN_SHIPMENT_ID ?>;

UNKNOWN_AUDIT_ID = <?php echo Audit::UNKNOWN_AUDIT_ID ?>;

UNKNOWN_SHIPMENT_LOCATION = <?php echo ShipmentLocation::UNKNOWN ?>;

<?php echo QuoteStatus::getJavascript("QuoteStatus") . "\n" ?>

<?php echo InspectionType::getJavascript("InspectionType") . "\n" ?>

<?php echo InspectionStatus::getJavascript("InspectionStatus") . "\n" ?>

<?php echo InspectionStatus::getJavascriptInspectionClasses("InspectionStatusClasses") . "\n" ?>

<?php echo ShipmentLocation::getJavascript("ShipmentLocation") . "\n" ?>

<?php echo CorrectiveActionStatus::getJavascript("CorrectiveActionStatus") . "\n" ?>


