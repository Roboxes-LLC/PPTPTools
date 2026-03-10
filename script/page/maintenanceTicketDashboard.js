class Dashboard
{
      // HTML elements
   static PageElements = {
      // Tables (data-field)
      "TICKET_TABLE":    "ticket-table",
      // Cells
      "COL":            "col",
      "POSTED_DATE":    "postedDate",
      "POSTED_TIME":    "postedTime",
      "WC_NUMBER":      "wcNumber",
      "JOB_NUMBER":     "jobNumber",
      "MACHINE_STATUS": "machineStatus",
      "DESCRIPTION":    "description",
      "ASSIGNED":       "assigned",
      "STATUS":         "status",
      "ELAPSED_TIME":   "elapsedTime",
      // Template
      "TICKET_TEMPLATE":     "ticket-template",
   };

   constructor()
   {      
      this.tableElement = null;
      this.templateElement = null;
      this.maintenanceTickets = null;

      this.setup();
   }

   setup()
   {
      console.log("setup()");
      this.tableElement = document.getElementById(Dashboard.PageElements.TICKET_TABLE);
      this.tableBodyElement = this.tableElement.getElementsByTagName('tbody')[0];
      this.templateElement = document.getElementById(Dashboard.PageElements.TICKET_TEMPLATE);

      if ((this.tableElement == null) ||
          (this.tableBodyElement == null) ||
          (this.templateElement == null))
      {
         console.log("Dashboard::setup: Template error.  Missing elements.");
      }

      this.updateTable();

      setInterval(function () {this.updateTable()}.bind(this), 10000);  // 10 second refresh

      setInterval(function() {this.updateElapsedTime()}.bind(this), 1000);  // 1 second refresh
   }

   updateTable()
   {
      console.log("udpateTable()");

         // AJAX call to delete the component.
         let requestUrl = `/app/page/maintenanceTicket/?request=fetch&activeTickets=true&prioritySort=true`;
         
         ajaxRequest(requestUrl, function(response) {
            if (response.success == true)
            {
               this.maintenanceTickets = response.maintenanceTickets;
               this.clearTable();
               this.createRows(this.maintenanceTickets);
            }
            else
            {
               this.clearTable();
               alert(response.error);
            }
         }.bind(this));
   }

   updateElapsedTime()
   {
      let elements = document.querySelectorAll(`[data-col="${Dashboard.PageElements.ELAPSED_TIME}"]`);

      for (let element of elements)
      {
         if (!element.parentNode.classList.contains("template"))
         {
            element.innerHTML = this.getUpdateTimeString(element.dataset.updateTime);
         }
      }
   }

   getUpdateTimeString(updateTime)
   {
      var updateTimeString = "----";

      if (updateTime)
      {
         var now = new Date(Date.now());
         var lastUpdate = new Date(Date.parse(updateTime));
         
         // Verify lastCountTime is for this work day.
         if (lastUpdate &&
             ((lastUpdate.getYear() == now.getYear()) &&
              (lastUpdate.getMonth() == now.getMonth()) &&
              (lastUpdate.getDay() == now.getDay())))
         {
            var diff = new Date(now - lastUpdate);
            
            var millisInHour = (1000 * 60 * 60);
            var millisInMinute = (1000 * 60);
            var millisInSecond = 1000;
            
            var hours = Math.floor(diff / millisInHour);
            var minutes = Math.floor((diff % millisInHour) / millisInMinute);
            var seconds = Math.round((diff % millisInMinute) / millisInSecond);
            var tenths = Math.round((diff % millisInSecond) / 10);
            
            if (hours > 0)
            {
               updateTimeString = this.padNumber(hours) + ":" + this.padNumber(minutes) + ":" + this.padNumber(seconds);
            }
            else
            {
               updateTimeString = this.padNumber(minutes) + ":" + this.padNumber(seconds);
            }
         }
      }

      return (updateTimeString);
   }

   padNumber(number)
   {
      return ((number < 10 ? '0' : '') + number);
   }

   clearTable()
   {
      this.tableBodyElement.innerHTML = '';
   }

   createRows(maintenanceTickets)
   {
      for (let maintenanceTicket of maintenanceTickets)
      {
         let rowElement = this.templateElement.cloneNode(true);

         var truncatedDescription = (maintenanceTicket.description.length > 20) ? maintenanceTicket.description.substr(0, 20) + "..." : maintenanceTicket.description; 

         this.setCell(rowElement, Dashboard.PageElements.POSTED_DATE, maintenanceTicket.formattedDate);
         this.setCell(rowElement, Dashboard.PageElements.POSTED_TIME, maintenanceTicket.formattedTime);
         this.setCell(rowElement, Dashboard.PageElements.WC_NUMBER, maintenanceTicket.wcLabel);
         this.setCell(rowElement, Dashboard.PageElements.JOB_NUMBER, maintenanceTicket.jobNumber);
         this.setCell(rowElement, Dashboard.PageElements.MACHINE_STATUS, maintenanceTicket.machineStateLabel);
         this.setCellClass(rowElement, Dashboard.PageElements.MACHINE_STATUS, maintenanceTicket.machineStateClass);         
         this.setCell(rowElement, Dashboard.PageElements.DESCRIPTION, truncatedDescription);
         this.setCell(rowElement, Dashboard.PageElements.ASSIGNED, maintenanceTicket.assignedName);
         this.setCell(rowElement, Dashboard.PageElements.STATUS, maintenanceTicket.statusLabel);
         this.setCell(rowElement, Dashboard.PageElements.ELAPSED_TIME, this.getUpdateTimeString(maintenanceTicket.updateTime));
         this.setCellData(rowElement, Dashboard.PageElements.ELAPSED_TIME, "updateTime", maintenanceTicket.updateTime);

         rowElement.classList.remove("template");

         this.tableBodyElement.appendChild(rowElement);
      }
   }

   setCell(rowElement, dataField, value)
   {
      let cellElement = rowElement.querySelector(`[data-${Dashboard.PageElements.COL}="${dataField}"]`);

      if (cellElement != null)
      {
         cellElement.innerHTML = value;
      }
   }

   setCellClass(rowElement, dataField, cssClass)
   {
      let cellElement = rowElement.querySelector(`[data-${Dashboard.PageElements.COL}="${dataField}"]`);

      if (cellElement != null)
      {
         cellElement.classList.add(cssClass);
      }
   }

   setCellData(rowElement, dataField, dataLabel, dataValue)
   {
      let cellElement = rowElement.querySelector(`[data-${Dashboard.PageElements.COL}="${dataField}"]`);

      if (cellElement != null)
      {
         cellElement.dataset[dataLabel] = dataValue;
      }
   }
}