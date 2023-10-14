<?php 

   if (!defined('ROOT')) require_once '../../root.php';
   require_once ROOT.'/inspection/inspectionTable.php';

   $inspectionId = 46946;
   $templateId = 218;
   $quantity = 1000;
   $allowQuickInspection = true;
   $isEditable = true;

?>

<html>
   <header>
      <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons"/>
      
      <link rel="stylesheet" type="text/css" href="/common/theme.css"/>
      <link rel="stylesheet" type="text/css" href="/common/common.css"/>
      <link rel="stylesheet" type="text/css" href="/inspection/inspection.css"/>
      
      <script src="/inspection/inspection.js"></script>
      <script src="/script/common/commonDefs.php"></script>
   </header>
   <body>
      <?php echo InspectionTable::getHtml($inspectionId, $templateId, $quantity, $allowQuickInspection, $isEditable) ?>
   </body>
</html>