<?php

require_once 'permissions.php';
require_once 'root.php';

class Activity
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const JOBS = Activity::FIRST;
   const USER = 2;
   const TIME_CARD = 3;
   const PART_WEIGHT = 4;
   const PART_WASH = 5;
   const INSPECTION_TEMPLATE = 6;
   const INSPECTION = 7;
   const PRINT_MANAGER = 8;
   const SIGNAGE = 9;
   const PAN_TICKET = 10;
   const REPORT = 11;
   const WEEKLY_REPORT = 12;     // TODO: Submenus
   const QUARTERLY_REPORT = 13;  // TODO: Submenus
   const MAINTENANCE_LOG = 14;
   const LAST = 15;
   
   public $id;
   public $label;
   public $icon;
   public $permissionMask;
   public $url;
   
   private function __construct($id, $label, $icon, $permissionMask, $url)
   {
      $this->id = $id;
      $this->label = $label;
      $this->icon = $icon;
      $this->permissionMask = $permissionMask;
      $this->url = $url;
   }
   
   public static $VALUES = array(
      Activity::JOBS,
      Activity::USER,
      Activity::TIME_CARD,
      Activity::PART_WEIGHT,
      Activity::PART_WASH,
      Activity::INSPECTION_TEMPLATE,
      Activity::INSPECTION,
      Activity::PRINT_MANAGER,
      Activity::SIGNAGE,
      Activity::PAN_TICKET,
      Activity::REPORT,
      Activity::WEEKLY_REPORT,
      Activity::QUARTERLY_REPORT,
      Activity::MAINTENANCE_LOG
   );
   
   private static $ACTIVITIES = null;
   
   public static function getActivities()
   {
      global $ROOT;
      
      if (Activity::$ACTIVITIES == null)
      {
         Activity::$ACTIVITIES = array(
            Activity::JOBS =>                new Activity(Activity::JOBS,                "Jobs",                 "assignment",           Permission::getPermission(Permission::VIEW_JOB)->bits,                 "$ROOT/jobs/viewJobs.php"),
            Activity::USER =>                new Activity(Activity::USER,                "Users",                "group",                Permission::getPermission(Permission::VIEW_USER)->bits,                "$ROOT/user/viewUsers.php"),
            Activity::TIME_CARD =>           new Activity(Activity::TIME_CARD,           "Time Cards",           "schedule",             Permission::getPermission(Permission::VIEW_TIME_CARD)->bits,           "$ROOT/timecard/viewTimeCards.php"),
            Activity::PART_WEIGHT =>         new Activity(Activity::PART_WEIGHT,         "Part Weight Log",      "fingerprint",          Permission::getPermission(Permission::VIEW_PART_WEIGHT_LOG)->bits,     "$ROOT/partWeightLog/partWeightLog.php"),
            Activity::PART_WASH =>           new Activity(Activity::PART_WASH,           "Parts Washer Log",     "opacity",              Permission::getPermission(Permission::VIEW_PART_WASHER_LOG)->bits,     "$ROOT/partWasherLog/partWasherLog.php"),
            Activity::INSPECTION_TEMPLATE => new Activity(Activity::INSPECTION_TEMPLATE, "Inspection Templates", "format_list_bulleted", Permission::getPermission(Permission::VIEW_INSPECTION_TEMPLATE)->bits, "$ROOT/inspectionTemplate/viewInspectionTemplates.php"),
            Activity::INSPECTION =>          new Activity(Activity::INSPECTION,          "Inspections",          "search",               Permission::getPermission(Permission::VIEW_INSPECTION)->bits,          "$ROOT/inspection/viewInspections.php"),
            Activity::PRINT_MANAGER =>       new Activity(Activity::PRINT_MANAGER,       "Print Manager",        "print",                Permission::getPermission(Permission::VIEW_PRINT_MANAGER)->bits,       "$ROOT/printer/viewPrinters.php"),
            Activity::SIGNAGE =>             new Activity(Activity::SIGNAGE,             "Digital Signage",      "tv",                   Permission::getPermission(Permission::VIEW_SIGN)->bits,                "$ROOT/signage/viewSigns.php"),
            Activity::PAN_TICKET =>          new Activity(Activity::PAN_TICKET,          "Pan Ticket Scanner",   "camera_alt",           Permission::getPermission(Permission::VIEW_TIME_CARD)->bits,           "$ROOT/panTicket/scanPanTicket.php"),
            Activity::REPORT =>              new Activity(Activity::REPORT,              "Reports",              "bar_chart",            Permission::getPermission(Permission::VIEW_REPORT)->bits,              "$ROOT/report/viewDailySummaryReport.php"),
            Activity::WEEKLY_REPORT =>       new Activity(Activity::WEEKLY_REPORT,       "Reports",              "bar_chart",            Permission::getPermission(Permission::VIEW_REPORT)->bits,              "$ROOT/report/viewWeeklySummaryReport.php"),
            Activity::QUARTERLY_REPORT =>    new Activity(Activity::QUARTERLY_REPORT,    "Reports",              "bar_chart",            Permission::getPermission(Permission::VIEW_REPORT)->bits,              "$ROOT/report/viewQuarterlySummaryReport.php"),
            Activity::MAINTENANCE_LOG =>     new Activity(Activity::MAINTENANCE_LOG,     "Maintenance Log",      "build",                Permission::getPermission(Permission::VIEW_MAINTENANCE_LOG)->bits,     "$ROOT/maintenanceLog/maintenanceLog.php")
         );
      }
      
      return (Activity::$ACTIVITIES);
   }
   
   public static function getActivity($activityId)
   {
      $activity = null;
      
      $activities = Activity::getActivities();
      
      if (isset($activities[$activityId]))
      {
         $activity = $activities[$activityId];
      }
      
      return ($activity);
   }
   
   public static function isAllowed($activityId, $permissions)
   {
      $isAllowed = false;
      
      $activity = Activity::getActivity($activityId);
      
      if ($activity)
      {
         $isAllowed = (($permissions & $activity->permissionMask) > 0);
      }
      
      return ($isAllowed);
   }
}
?>
