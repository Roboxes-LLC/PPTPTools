<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/core/component/skid.php';

class SkidManager
{
   public static function getSkidBySkidTicketCode($skidTicketCode)
   {
      return (Skid::load(Skid::skidTicketCodeToSkidId($skidTicketCode)));
   }
   
   public static function getSkids($startDate, $endDate)
   {
      $skids = array();
      
      $result = PPTPDatabase::getInstance()->getSkids($startDate, $endDate);
      
      while ($result && ($row = $result->fetch_assoc()))
      {
         $skid = new Skid();
         $skid->initialize($row);
         $skid->contents = Skid::getContents($skid->skidId);
         $skid->actions = Skid::getActions($skid->skidId);
         
         $skids[] = $skid;
      }
      
      return ($skids);
   }
   
   public static function getSkidsByState($skidStates)
   {
      $skids = array();
      
      $result = PPTPDatabase::getInstance()->getSkidsByState($skidStates);
      
      while ($result && ($row = $result->fetch_assoc()))
      {
         $skid = new Skid();
         $skid->initialize($row);
         $skid->contents = Skid::getContents($skid->skidId);
         $skid->actions = Skid::getActions($skid->skidId);
         
         $skids[] = $skid;
      }
      
      return ($skids);
   }
   
   public static function getSkidsByJob($jobNumber)
   {
      $skids = array();
      
      $result = PPTPDatabase::getInstance()->getSkidsByJob($jobNumber);
      
      while ($result && ($row = $result->fetch_assoc()))
      {
         $skid = new Skid();
         $skid->initialize($row);         
         $skid->contents = Skid::getContents($skid->skidId);
         $skid->actions = Skid::getActions($skid->skidId);
         
         $skids[] = $skid;
      }
      
      return ($skids);
   }
   
   public static function skidExistsForJob($jobNumber)
   {
      return (count(SkidManager::getSkidsByJob($jobNumber)) > 0);
   }
}