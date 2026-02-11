class MaintenanceTicket
{
   // HTML elements
   static PageElements = {
      // Forms
      "INPUT_FORM":           "input-form",
      // Filters
      // Tables
      "DATA_TABLE":           "data-table",
      // Buttons
      "ADD_BUTTON":           "add-button",
      "SAVE_BUTTON":          "save-button",
      "CANCEL_BUTTON":        "cancel-button",
      // Input fields
      "START_DATE_INPUT":     "start-date-input",
      "END_DATE_INPUT":       "end-date-input",
      "ACTIVE_TICKETS_INPUT": "active-tickets-input",
      "TICKET_ID_INPUT":      "doc-id-input",
   };

   constructor()
   {      
      this.table = null;
      
      this.setup();
   }
   
   setup()
   {
      if (document.getElementById(MaintenanceTicket.PageElements.DATA_TABLE) != null)
      {
         this.createTable(MaintenanceTicket.PageElements.DATA_TABLE);
      }
            
      if (document.getElementById(MaintenanceTicket.PageElements.START_DATE_INPUT) != null)
      {
         document.getElementById(MaintenanceTicket.PageElements.START_DATE_INPUT).addEventListener('change', function() {
            this.onStartDateChanged();
         }.bind(this));
      }
      
      if (document.getElementById(MaintenanceTicket.PageElements.END_DATE_INPUT) != null)
      {
         document.getElementById(MaintenanceTicket.PageElements.END_DATE_INPUT).addEventListener('change', function() {
            this.onEndDateChanged();
         }.bind(this));
      }

      if (document.getElementById(MaintenanceTicket.PageElements.ACTIVE_TICKETS_INPUT) != null)
      {
         document.getElementById(MaintenanceTicket.PageElements.ACTIVE_TICKETS_INPUT).addEventListener('change', function() {
            this.onActiveTicketsChanged();
         }.bind(this));
      }
      
      if (document.getElementById(MaintenanceTicket.PageElements.ADD_BUTTON) != null)
      {
         document.getElementById(MaintenanceTicket.PageElements.ADD_BUTTON).addEventListener('click', function() {
            this.onAddButton();
         }.bind(this));
      }
      
      if (document.getElementById(MaintenanceTicket.PageElements.SAVE_BUTTON) != null)
      {
         document.getElementById(MaintenanceTicket.PageElements.SAVE_BUTTON).addEventListener('click', function() {
            this.onSaveButton();
         }.bind(this));
      }
      
      if (document.getElementById(MaintenanceTicket.PageElements.CANCEL_BUTTON) != null)
      {
         document.getElementById(MaintenanceTicket.PageElements.CANCEL_BUTTON).addEventListener('click', function() {
            history.back();
         }.bind(this));
      }
   }      
   
   createTable(tableElementId)
   {
      let url = "/app/page/maintenanceTicket/";
      let params = this.getTableQueryParams();
      
      let tableElementQuery = "#" + tableElementId;
   
      // Create Tabulator table
      this.table = new Tabulator(tableElementQuery, {
         // Data
         ajaxURL:url,
         ajaxParams:params,
         ajaxResponse:function(url, params, response) {
            let tableData = [];
            if (response.success)
            {
               tableData = response.maintenanceTickets;
            }
            return (tableData);
         },
         // Layout
         layout:"fitData",
         columnDefaults:{
            hozAlign:"left", 
            vertAlign:"middle"
         },
         persistence:false,
         // Columns
         columns:[
            {                           field:"ticketId",           visible:false},
            {title:"Posted Date",       field:"formattedDate",      headerFilter:true},
            {title:"Posted Time",       field:"formattedTime",      headerFilter:true},
            {title:"Posted By",         field:"authorName",         headerFilter:true},
            {title:"Status",            field:"statusLabel",        headerFilter:true}, 
            {title:"WC #",              field:"wcLabel",            headerFilter:true},
            {title:"Job #",             field:"jobNumber",          headerFilter:true},
            {title:"Description",       field:"description"},
            {title:"Machine State",     field:"machineState",       headerFilter:true, hozAlign:"center",
               formatter:function(cell, formatterParams, onRendered){
                  var label = cell.getRow().getData().machineStateLabel;
                  var cssClass = cell.getRow().getData().machineStateClass;
                  return ("<div class=\"machine-state " + cssClass + "\">" + label + "</div>");
               }
            }, 
            {title:"Assigned",          field:"assignedName",       headerFilter:true},
            {title:"",                  field:"action",         hozAlign:"center",    print:false,
               formatter:function(cell, formatterParams, onRendered){
                  let cellValue =`<button class=\"small-button accent-button\" style=\"width:75px;\">Action</button>`;
                  return (cellValue);
               }
            },
            {title:"Resolve Time",      field:"formattedResolveTime", headerFilter:true},
            {title:"",                  field:"delete", tooltip:"Delete", hozAlign:"center", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ],
         initialSort:[
            {column:"ticketId", dir:"desc"}
         ]
      });
      
      this.table.on("cellClick", function(e, cell) {
         let ticketId = parseInt(cell.getRow().getData().ticketId);
         
         if (cell.getColumn().getField() == "delete")
         {
            this.onDeleteButton(ticketId);
            e.stopPropagation();
         }
         else
         {
            document.location = `/maintenanceTicket/maintenanceTicket.php?ticketId=${ticketId}`;
         }
      }.bind(this));
   }
   
   // **************************************************************************
   
   onStartDateChanged()
   {
      if (!this.validateFilterDates())
      {
         document.getElementById(MaintenanceTicket.PageElements.END_DATE_INPUT).value = 
            document.getElementById(MaintenanceTicket.PageElements.START_DATE_INPUT).value
      }
      
      this.onFilterUpdate();
      
      setSession("maintenanceTicket.startDate", document.getElementById(MaintenanceTicket.PageElements.START_DATE_INPUT).value);
   }
   
   onEndDateChanged()
   {
      if (!this.validateFilterDates())
      {
         document.getElementById(MaintenanceTicket.PageElements.START_DATE_INPUT).value = 
            document.getElementById(MaintenanceTicket.PageElements.END_DATE_INPUT).value
      }

      this.onFilterUpdate();
      
      setSession("maintenanceTicket.endDate", document.getElementById(MaintenanceTicket.PageElements.END_DATE_INPUT).value);
   }

   onActiveTicketsChanged()
   {
      var activeTickets = document.getElementById(MaintenanceTicket.PageElements.ACTIVE_TICKETS_INPUT).checked;
      
      if (activeTickets)
      {
         disable(MaintenanceTicket.PageElements.START_DATE_INPUT);
         disable(MaintenanceTicket.PageElements.END_DATE_INPUT);
      }
      else
      {
         enable(MaintenanceTicket.PageElements.START_DATE_INPUT);
         enable(MaintenanceTicket.PageElements.END_DATE_INPUT);
      }
      
      this.onFilterUpdate();
      
      setSession("maintenanceTicket.activeTickets", (activeTickets ? "true" : "false"));
   }
            
   onAddButton()
   {
      document.location = `/maintenanceTicket/newMaintenanceTicket.php`;
   }
   
   onDeleteButton(ticketId)
   {
      if (confirm("Are you sure you want to delete this ticket?"))
      {
         // AJAX call to delete the component.
         let requestUrl = `/app/page/maintenanceTicket/?request=delete_ticket&ticketId=${ticketId}`;
         
         ajaxRequest(requestUrl, function(response) {
            if (response.success == true)
            {
               location.reload();
            }
            else
            {
               alert(response.error);
            }
         }.bind(this));
      }
   }
   
   onSaveButton()
   {
      let form = document.getElementById(MaintenanceTicket.PageElements.INPUT_FORM);
      
      if (form.reportValidity() == true)
      {
         submitForm(MaintenanceTicket.PageElements.INPUT_FORM, "/app/page/maintenanceTicket", function (response) {
            if (response.success == true)
            {
               location.href = "/maintenanceTicket/maintenanceTickets.php";
            }
            else
            {
               alert(response.error);
            }
         })
      }
      else
      {
         showInvalid(MaintenanceTicket.PageElements.INPUT_FORM);
      }
   }
      
   // **************************************************************************
      
   getTicketIdId()
   {
      return (parseInt(document.getElementById(MaintenanceTicket.PageElements.TICKET_ID_INPUT).value));
   }
   
   getTableQueryParams()
   {
      let params = new Object();
      params.request = "fetch";
      
      params.startDate =  document.getElementById(MaintenanceTicket.PageElements.START_DATE_INPUT).value;   

      params.endDate =  document.getElementById(MaintenanceTicket.PageElements.END_DATE_INPUT).value;

      if (document.getElementById(MaintenanceTicket.PageElements.ACTIVE_TICKETS_INPUT).checked)
      {
         params.activeTickets = true;
      }

      return (params);
   }
   
   validateFilterDates()
   {
      let startDate = document.getElementById(MaintenanceTicket.PageElements.START_DATE_INPUT).value;
      let endDate = document.getElementById(MaintenanceTicket.PageElements.END_DATE_INPUT).value;
      
      return (new Date(endDate) >= new Date(startDate))
   }
   
   onFilterUpdate()
   {
      if (document.readyState === "complete")
      {
         let url = "/app/page/maintenanceTicket/";
         let params = this.getTableQueryParams();

         this.table.setData(url, params)
         .then(function(){
            // Run code after table has been successfuly updated
         })
         .catch(function(error){
            // Handle error loading data
         });
      }
   }
   
   // **************************************************************************
}