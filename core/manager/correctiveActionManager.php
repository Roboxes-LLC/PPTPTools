<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/filterDateType.php';
require_once ROOT.'/core/common/pptpDatabase.php';
require_once ROOT.'/core/component/correctiveAction.php';

class CorrectiveActionManager
{
   public static function getCorrectiveActions($dateType, $startDate, $endDate, $allActive)
   {
      $correctiveActions = array();
      
      $result = PPTPDatabaseAlt::getInstance()->getCorrectiveActions($dateType, $startDate, $endDate, $allActive);
      
      foreach ($result as $row)
      {
         $correctiveActions[] = CorrectiveAction::load(intval($row["correctiveActionId"]));
      }
      
      return ($correctiveActions);
   }
}