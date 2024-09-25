<?php 

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/materialEntry.php';
require_once ROOT.'/common/params.php';

$result = PPTPDatabase::getInstance()->query("SELECT * from materiallog;");
$count = PPTPDatabase::countResults($result);

$params = Params::parse();
$commit = $params->getBool("commit");
$commitString = $commit ? "true" : "false";

echo "Populating material inspections for $count entries (commit = $commitString)<br><br>";

foreach ($result as $row)
{
   $materialEntry = new MaterialEntry();
   $materialEntry->initialize($row);
   
   if ($materialEntry)
   {   
      echo "Processing entry $materialEntry->materialEntryId: ";
      
      $materialEntry->acceptedPieces = $materialEntry->pieces;
      $materialEntry->inspectedSize = $materialEntry->materialHeatInfo->materialInfo->size;
      $materialEntry->materialStamp = MaterialStamp::A9_SS;
      $materialEntry->poNumber = null;
      
      if (!$commit || MaterialEntry::save($materialEntry))
      {
         echo "inspection updated";   
      
         $certFile = $materialEntry->materialHeatInfo->internalHeatNumber . ".pdf";
         $certPath = $UPLOAD_PATH . $MATERIAL_CERTS_DIR . $certFile;
         
         if ($materialEntry->materialHeatInfo->certFile == $certFile)
         {
            echo ", cert already updated ($certFile)";
         }
         else if (file_exists($certPath))
         {
            $materialEntry->materialHeatInfo->certFile = $certFile;
            
            if (!$commit || MaterialHeatInfo::save($materialEntry->materialHeatInfo))
            {
               echo ", cert updated ($certFile)";
            }
            else
            {
               echo ", cert update failed ($certFile)";
            }
         }
         else
         {
            echo ", no cert ($certFile)";
         }
      }
      else
      {
         echo "inspection update failed";
      }
      
      echo ("<br>");
   }
}

?>