<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/inspection.php';

class InspectionManager
{
   public static function generateInProcessFromOasis($oasisInspection)
   {
      $inProcessInspection = null;
      
      $timeCardInfo = TimeCardInfo::load($oasisInspection->timeCardId);
      
      if ($timeCardInfo)
      {
         $jobInfo = JobInfo::load($timeCardInfo->jobId);
         
         if ($jobInfo)
         {
            $templateIds = InspectionTemplate::getInspectionTemplatesForJob(InspectionType::IN_PROCESS, $jobInfo->jobNumber, $jobInfo->jobId);

            // Only expect to have one template per job.
            if (count($templateIds) == 1)
            {
               $inProcessInspection = new Inspection();
               $inProcessInspection->dateTime = Time::now();
               $inProcessInspection->templateId = $templateIds[0];
               $inProcessInspection->author = UserInfo::SYSTEM_USER_ID;
               $inProcessInspection->inspector = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
               $inProcessInspection->comments = "Auto-generated from Oasis inspection";
               
               $inProcessInspection->timeCardId = $timeCardInfo->timeCardId;
               
               $inProcessInspectionCount = count(Inspection::getInspectionsForTimeCard($timeCardInfo->timeCardId, [InspectionType::IN_PROCESS]));
                  
               if ($inProcessInspectionCount == 0)
               {
                  $inProcessInspection->inspectionNumber = 1;   
               }
               else if ($inProcessInspectionCount == 1)
               {
                  $inProcessInspection->inspectionNumber = 2;
               }
               
               $inProcessInspection->updateSummary();
               
               Inspection::save($inProcessInspection);
            }
         }
      }
      
      return ($inProcessInspection);
   }
}

?>