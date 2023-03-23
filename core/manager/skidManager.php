<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/core/component/skid.php';

class SkidManager
{
   public static function getSkids($startDate, $endDate)
   {
      $purchaseOrders = array();
      
      $result = PPTPDatabase::getInstance()->getSkids($startDate, $endDate);
      
      foreach ($result as $row)
      {
         $skid = new Skid();
         $skid->initialize($row);
         $skid->contents = Skid::getContents($skid->skidId);
         $skid->actions = Skid::getActions($skid->skidId);
         
         $skids[] = $skid;
      }
      
      return ($skids);
   }
   
   public static function getSkidsByState($siteId, $skidStates)
   {
      $skids = array();
      
      $result = PPTPDatabase::getInstance()->getSkidsByState($siteId, $skidStates);
      
      foreach ($result as $row)
      {
         $skid = new Skid();
         $skid->initialize($row);
         $skid->contents = Skid::getContents($skid->skidId);
         $skid->actions = Skid::getActions($skid->skidId);
         
         $skids[] = $skid;
      }
      
      return ($skids);
   }
}