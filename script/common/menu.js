class Menu
{
   constructor(menuElementId)
   {      
      this.menuElement = document.getElementById(menuElementId);
      
      let submenuItems = this.menuElement.getElementsByClassName("submenu-item");

      // Set onclick handlers for all submenu items.      
      for (const submenuItem of submenuItems)
      {
         let menu = this;
         submenuItem.onclick = function(){menu.onSubmenuClicked(this)};
      }      
   }
   
   setMenuItemSelected(menuItemId)
   {
      let menuItem = document.getElementById("menu-item-" + menuItemId);
      
      if (menuItem != null)
      {
         menuItem.classList.add('selected');
         
         // Mark parent submenu-item (if present) as selected as well.
         if (menuItem.parentElement.classList.contains("submenu"))
         {
            let submenuItem = menuItem.parentElement.previousElementSibling;
            
            if (submenuItem.classList.contains("submenu-item"))
            {
               submenuItem.classList.add('selected');
            }
         }
         
         while (menuItem.parentElement.classList.contains("submenu"))
         {
            let submenuItem = menuItem.parentElement.previousElementSibling;
            this.expand(submenuItem);
            
            menuItem = submenuItem;
         }
      }
   }
   
   onSubmenuClicked(submenuItem)
   {
      if (this.isExpanded(submenuItem))
      {
         this.unexpand(submenuItem);
      }
      else
      {
         // Expand menu.
         this.expand(this.menuElement);

         // Expand submenu.
         this.expand(submenuItem);
      }
   }
   
   isExpanded(submenuItem)
   {
      return (submenuItem.classList.contains("expanded"));
   }
   
   unexpand(submenuItem)
   {
      submenuItem.classList.remove("expanded");
      setSession(submenuItem.id + ".expanded", false);
      
      // If it is the menu that is being condensed, also condense all submenus.
      if (submenuItem.classList.contains("menu"))
      {
         let submenuItems = document.getElementsByClassName("submenu-item");
         
         for (let submenuItem of submenuItems)
         {
            this.unexpand(submenuItem);
         }
      }
   }
   
   expand(submenuItem)
   {
      submenuItem.classList.add("expanded");
      setSession(submenuItem.id + ".expanded", true);
   }
   
   toggle(submenuItem)
   {
      if (this.isExpanded(submenuItem))
      {
         this.unexpand(submenuItem);
      }
      else
      {
         this.expand(submenuItem);
      }
   }
   
   getUnexpandedIcon(submenuItem)
   {
      return (submenuItem.getElementsByClassName("menu-unexpanded-icon")[0]);
   }
   
   getExpandedIcon(submenuItem)
   {
      return (submenuItem.getElementsByClassName("menu-expanded-icon")[0]);
   }
   
   getSubmenuContainer(submenuItem)
   {
      return (submenuItem.nextElementSibling);
   }
   
   hide(element)
   {
      element.style.display = 'none';
   }
   
   show(element, display)
   {
      element.style.display = display;
   }
}