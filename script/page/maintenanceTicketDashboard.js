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
   }

   updateTable()
   {
      console.log("udpateTable()");

         // AJAX call to delete the component.
         let requestUrl = `/app/page/maintenanceTicket/?request=fetch&activeTickets=true`;
         
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
}