<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/params.php';
require_once ROOT.'/common/permissions.php';
require_once ROOT.'/common/version.php';
require_once ROOT.'/app/common/appPage.php';
require_once ROOT.'/app/common/session.php';
//require_once ROOT.'/core/common/authentication.php';
//require_once ROOT.'/core/common/params.php';
//require_once ROOT.'/core/common/permission.php';

$jsonMenu = 
<<<HEREDOC
{
   "menuItems": [
      {
         "type":"menu-item",
         "pageId": 2
      },
      {
         "type":"submenu",
         "menuId":"inspection",
         "label":"Jobs",
         "icon":"assignment",
         "permissions":18,
         "menuItems": [
            {
               "type":"menu-item",
               "pageId": 24
            },
            {
               "type":"menu-item",
               "pageId": 1
            },
            {
               "type":"menu-item",
               "pageId": 20
            }
         ]
      },
      {
         "type":"menu-item",
         "pageId": 15
      },
      {
         "type":"submenu",
         "menuId":"inspection",
         "label":"Time Cards",
         "icon":"schedule",
         "permissions":18,
         "menuItems": [
            {
               "type":"menu-item",
               "pageId": 3
            },
            {
               "type":"menu-item",
               "pageId": 16
            }
         ]
      },
      {
         "type":"menu-item",
         "pageId": 4
      },
      {
         "type":"menu-item",
         "pageId": 5
      },
      {
         "type":"submenu",
         "menuId":"inspection",
         "label":"Inspection",
         "icon":"search",
         "permissions":18,
         "menuItems": [
            {
               "type":"menu-item",
               "pageId": 6
            },
            {
               "type":"menu-item",
               "pageId": 7
            }
         ]
      },
      {
         "type":"submenu",
         "menuId":"reports",
         "label":"Reports",
         "icon":"bar_chart",
         "permissions":24,
         "menuItems": [
            {
               "type":"menu-item",
               "pageId": 11
            },
            {
               "type":"menu-item",
               "pageId": 12
            },
            {
               "type":"menu-item",
               "pageId": 13
            }
         ]
      },
      {
         "type":"menu-item",
         "pageId": 14
      },
      {
         "type":"menu-item",
         "pageId": 22
      },
      {
         "type":"submenu",
         "menuId":"sales",
         "label":"Sales",
         "icon":"store",
         "permissions":33,
         "menuItems": [
            {
               "type":"menu-item",
               "pageId": 17
            },
            {
               "type":"menu-item",
               "pageId": 18
            },
            {
               "type":"menu-item",
               "pageId": 19
            },
            {
               "type":"menu-item",
               "pageId": 23
            }
         ]
      },
      {
         "type":"submenu",
         "menuId":"iso",
         "label":"ISO",
         "icon":"diamond",
         "permissions":50,
         "menuItems": [
            {
               "type":"menu-item",
               "pageId": 25
            }
         ]
      },
      {
         "type":"menu-item",
         "pageId": 21
      }
   ]
}
HEREDOC;

class Menu
{ 
   const MENU_ELEMENT_ID = "menu";
   
   const INITIAL_MENU_EXPANDED = false;
   
   public static function render()
   {
      echo Menu::getHtml();
   }
   
   public static function getHtml()
   {
      $elementId = Menu::MENU_ELEMENT_ID;
      
      // Initialize the menu to unexpanded, if not previously set.
      if (!Session::isset($elementId . ".expanded"))
      {
         Menu::setExpanded($elementId, Menu::INITIAL_MENU_EXPANDED);
      }
      
      $expanded = Menu::isExpanded($elementId) ? "expanded" : "";
      
      $html = "<div id=\"$elementId\" class=\"menu flex-vertical $expanded\">";
      
      $obj = Menu::getMenuObj();

      if ($obj)
      {
         $html .= Menu::renderInternal($obj->menuItems, 0);
      }

      $html .= "</div>";
      
      return ($html);
   }
   
   public static function setExpanded($elementId, $isExpanded)
   {
      if ($isExpanded)
      {
         Session::setVar($elementId . ".expanded", true);
      }
      else
      {
         Session::clearVar($elementId . ".expanded");
      }
   }
      
   private static function renderInternal($obj, $depth)
   {
      $html = "";
            
      if (Menu::checkPermissions($obj))
      {
         $indent = ($depth > 0) ? "indent-$depth" : "";
         
         if (is_array($obj))
         {
            foreach ($obj as $subobj)
            {
               $html .= Menu::renderInternal($subobj, $depth);
            }
         }
         else if ($obj->type == "submenu")
         {
            $elementId = Menu::getElementId($obj->menuId);
            $expanded = Menu::isExpanded($elementId) ? "expanded" : "";
            $customClass = isset($obj->class) ? $obj->class : "";
            
            $html =
<<<HEREDOC
            <div id="$elementId" class="menu-item submenu-item flex-horizontal flex-left flex-v-center clickable $indent $expanded $customClass" onclick="">
               <div class="material-icons menu-gutter menu-unexpanded-icon">arrow_right</div>
               <div class="material-icons menu-gutter menu-expanded-icon">arrow_drop_down</div>
               <div class="material-icons menu-icon">$obj->icon</div>
               <div class="menu-label noselect">$obj->label</div>
            </div>
            <div class="submenu flex-vertical">
HEREDOC;
            
            $html .=  Menu::renderInternal($obj->menuItems, ++$depth);
            
            $html .=
<<<HEREDOC
            </div>
HEREDOC;
         }
         else if ($obj->type == "menu-item")
         {
            $elementId = "";
            $label = "";
            $icon = "";
            $url = "";
            $permissions = Permission::NO_PERMISSIONS;
            $customClass = isset($obj->class) ? $obj->class : "";
            
            if (isset($obj->pageId))
            {
               $appPage = AppPage::getAppPage(intval($obj->pageId));
               if ($appPage)
               {
                  $elementId = Menu::getElementId($appPage->pageId);
                  $label = $appPage->label;
                  $icon = $appPage->icon;
                  $url = $appPage->URL;
                  $permissions = $appPage->permissions;
               }
            }
            else 
            {
               $elementId = Menu::getElementId($obj->menuId);
               $label = $obj->label;
               $icon = $obj->icon;
               $url = $obj->URL;
               $permissions = $obj->permissions;
            }
            
            if (Authentication::checkPermissions($permissions))
            {
               $html .= 
<<<HEREDOC
               <div id="$elementId" class="menu-item flex-horizontal flex-left flex-v-center clickable $indent $customClass" onclick="location.href='$url'">
                  <div class="menu-gutter"></div>
                  <div class="material-icons menu-icon">$icon</div>
                  <div class="menu-label noselect">$label</div>
               </div>
HEREDOC;
            }
         }
      
      }
      
      return ($html);
   }
   
   private static function getMenuObj()
   {
      global $jsonMenu;
      
      $menuObj = null;
      
      /*
      if (Session::isset(Session::MENU))
      {
         $menuObj = Session::getVar(Session::MENU);
      }
      else
      */
      {
         $menuObj = json_decode($jsonMenu);
         
         //Session::setVar(Session::MENU, $menuObj);
      }
      
      return ($menuObj);
   }
   
   public static function isExpanded($elementId)
   {
      $key = $elementId . ".expanded";
      
      return (Session::isset($key) && 
              filter_var(Session::getVar($key), FILTER_VALIDATE_BOOLEAN));
   }
   
   /*
   private static function setExpandedInternal($elementId, $isExpanded, $menuNode)
   {
      if ($menuNode)
      {
         if (isset($menuNode->menuType) &&
             ($menuNode->menuType == "submenu"))
         {
            if (isset($menuNode->menuId) &&
                (Menu::getElementId($menuNode->menuId) == $elementId))
            {
               $menuNode->expanded = $isExpanded;
               return;
            }
         }
         
         if (isset($menuNode->menuItems))
         {
            foreach ($menuNode->menuItems as $menuItem)
            {
               Menu::setExpandedInternal($elementId, $isExpanded, $menuItem);
            }
         }
      }
   }
   */
          
   private static function getElementId($menuId)
   {
      return ("menu-item-" . $menuId);
   }
   
   private static function checkPermissions($obj)
   {
      $isPermitted = false;
      
      if (is_array($obj))
      {
         foreach ($obj as $subobj)
         {
            $isPermitted |= Menu::checkPermissions($subobj);
         }
      }
      else if ($obj->type == "submenu")
      {
         $isPermitted = Menu::checkPermissions($obj->menuItems);
      }
      else if ($obj->type == "menu-item")
      {
         $permissionId = Permission::UNKNOWN;
         
         if (isset($obj->pageId))
         {
            $appPage = AppPage::getAppPage(intval($obj->pageId));
            if ($appPage)
            {
               $permissionId = $appPage->permissions;
            }
         }
         else
         {
            $permissionId = $obj->permissions;
         }
         
         $isPermitted = Authentication::checkPermissions($permissionId);         
      }
      
      return ($isPermitted);
   }
}

if (Params::parse()->keyExists("show"))
{
   echo Menu::render();
}

Menu::setExpanded("menu-item-setup", true);

?>
