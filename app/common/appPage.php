<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/permissions.php';

class AppPage
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const JOBS = AppPage::FIRST;
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
   const MATERIAL = 15;
   const SHIPPING_CARD = 16;
   const CUSTOMER = 17;
   const QUOTE = 18;
   const LAST = 19;
   
   public $pageId;
   public $label;
   public $icon;
   public $permissions;
   public $URL;
   
   private function __construct($id, $label, $icon, $permissions, $url)
   {
      $this->pageId = $id;
      $this->label = $label;
      $this->icon = $icon;
      $this->permissions = $permissions;
      $this->URL = $url;
   }
   
   public static $VALUES = array(
      AppPage::USER,
      AppPage::JOBS,
      AppPage::MATERIAL,
      AppPage::TIME_CARD,         
      AppPage::PART_WEIGHT,
      AppPage::PART_WASH,
      AppPage::INSPECTION_TEMPLATE,
      AppPage::INSPECTION,
      AppPage::PRINT_MANAGER,
      AppPage::SIGNAGE,
      AppPage::PAN_TICKET,
      AppPage::REPORT,
      AppPage::WEEKLY_REPORT,
      AppPage::QUARTERLY_REPORT,
      AppPage::MAINTENANCE_LOG,
      AppPage::SHIPPING_CARD,
      AppPage::CUSTOMER,
      AppPage::QUOTE
   );
   
   private static $pages = null;
   
   public static function getPages()
   {
      if (AppPage::$pages == null)
      {
         AppPage::$pages = array(
            AppPage::USER =>                new AppPage(AppPage::USER,                "Users",                "group",                Permission::VIEW_USER,                "/user/viewUsers.php"),
            AppPage::JOBS =>                new AppPage(AppPage::JOBS,                "Jobs",                 "assignment",           Permission::VIEW_JOB,                 "/jobs/viewJobs.php"),
            AppPage::MATERIAL =>            new AppPage(AppPage::MATERIAL,            "Material",             "widgets",              Permission::VIEW_MATERIAL,            "/material/viewMaterials.php"),
            AppPage::TIME_CARD =>           new AppPage(AppPage::TIME_CARD,           "Time Cards",           "schedule",             Permission::VIEW_TIME_CARD,           "/timecard/viewTimeCards.php"),
            AppPage::PART_WEIGHT =>         new AppPage(AppPage::PART_WEIGHT,         "Part Weight Log",      "balance",              Permission::VIEW_PART_WEIGHT_LOG,     "/partWeightLog/partWeightLog.php"),
            AppPage::PART_WASH =>           new AppPage(AppPage::PART_WASH,           "Parts Washer Log",     "opacity",              Permission::VIEW_PART_WASHER_LOG,     "/partWasherLog/partWasherLog.php"),
            AppPage::INSPECTION_TEMPLATE => new AppPage(AppPage::INSPECTION_TEMPLATE, "Templates",            null,                   Permission::VIEW_INSPECTION_TEMPLATE, "/inspectionTemplate/viewInspectionTemplates.php"),
            AppPage::INSPECTION =>          new AppPage(AppPage::INSPECTION,          "Inspections",          null,                   Permission::VIEW_INSPECTION,          "/inspection/viewInspections.php"),
            AppPage::PRINT_MANAGER =>       new AppPage(AppPage::PRINT_MANAGER,       "Print Manager",        "print",                Permission::VIEW_PRINT_MANAGER,       "/printer/viewPrinters.php"),
            AppPage::SIGNAGE =>             new AppPage(AppPage::SIGNAGE,             "Digital Signage",      "tv",                   Permission::VIEW_SIGN,                "/signage/viewSigns.php"),
            //AppPage::PAN_TICKET =>          new AppPage(AppPage::PAN_TICKET,          "Pan Ticket Scanner",   "camera_alt",         Permission::VIEW_TIME_CARD,           "/panTicket/scanPanTicket.php"),
            AppPage::REPORT =>              new AppPage(AppPage::REPORT,              "Daily",                null,                   Permission::VIEW_REPORT,              "/report/viewDailySummaryReport.php"),
            AppPage::WEEKLY_REPORT =>       new AppPage(AppPage::WEEKLY_REPORT,       "Weekly",               null,                   Permission::VIEW_REPORT,              "/report/viewWeeklySummaryReport.php"),
            AppPage::QUARTERLY_REPORT =>    new AppPage(AppPage::QUARTERLY_REPORT,    "Quarterly",            null,                   Permission::VIEW_REPORT,              "/report/viewQuarterlySummaryReport.php"),
            AppPage::MAINTENANCE_LOG =>     new AppPage(AppPage::MAINTENANCE_LOG,     "Maintenance Log",      "build",                Permission::VIEW_MAINTENANCE_LOG,     "/maintenanceLog/maintenanceLog.php"),
            AppPage::SHIPPING_CARD =>       new AppPage(AppPage::SHIPPING_CARD,       "Shipping Cards",       "local_shipping",       Permission::VIEW_SHIPPING_CARD,       "/shippingCard/viewShippingCards.php"),
            AppPage::CUSTOMER =>            new AppPage(AppPage::CUSTOMER,            "Customers",            null,                   Permission::VIEW_CUSTOMER,            "/customer/customers.php"),
            AppPage::QUOTE =>               new AppPage(AppPage::QUOTE,               "Quotes",               null,                   Permission::VIEW_QUOTE,               "/quote/quotes.php")
         );
      }
      
      return (AppPage::$pages);
   }
   
   public static function getAppPage($pageId)
   {
      $appPage = null;
      
      $pages = AppPage::getPages();
      
      if (isset($pages[$pageId]))
      {
         $appPage = $pages[$pageId];
      }
      
      return ($appPage);
   }
   
   public static function isAllowed($pageId, $permissions)
   {
      $isAllowed = false;
      
      $appPage = AppPage::getAppPage($pageId);
      
      if ($appPage)
      {
         $isAllowed = (($permissions & $appPage->permissions) > 0);
      }
      
      return ($isAllowed);
   }
}
?>
