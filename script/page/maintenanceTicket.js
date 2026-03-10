class MaintenanceTicket
{
   // HTML elements
   static PageElements = {
      // Forms
      "INPUT_FORM":           "input-form",
      "UPDATE_FORM":          "update-form",
      "HISTORY_FORM":         "history-form",
      "ATTACHMENTS_FORM":     "attachments-form",
      // Filters
      // Tables
      "DATA_TABLE":           "data-table",
      // Buttons
      "ADD_BUTTON":           "add-button",
      "SAVE_BUTTON":          "save-button",
      "CANCEL_BUTTON":        "cancel-button",
      "ACTION_CANCEL_BUTTON": "action-cancel-button",
      "ACTION_OK_BUTTON":     "action-ok-button",
      "UPDATE_BUTTON":        "update-button",
      "COMMENT_BUTTON":       "comment-button",
      "ATTACH_BUTTON":        "attach-button",
      // Input fields
      "START_DATE_INPUT":     "start-date-input",
      "END_DATE_INPUT":       "end-date-input",
      "ACTIVE_TICKETS_INPUT": "active-tickets-input",
      "DATE_TYPE_INPUT":      "date-type-input",
      "TICKET_ID_INPUT":      "doc-id-input",
      "DESCRIPTION_INPUT":    "description-input",
      "DESCRIPTION_OPTION_INPUT": "description-option-input",
      "SORT_BUTTON_UP":       "sort-button-up",
      "SORT_BUTTON_DOWN":     "sort-button-down",
      // Panels
      "ACTION_PANEL":         "action-panel",
      "ACTION_PANEL_TITLE":  "action-panel-title"
   };

   constructor()
   {      
      this.table = null;
      this.status = null;

      // Action commands.
      this.apiCommand = null;
      this.ticketId = 0;
      
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

      if (document.getElementById(MaintenanceTicket.PageElements.DATE_TYPE_INPUT) != null)
      {
         document.getElementById(MaintenanceTicket.PageElements.DATE_TYPE_INPUT).addEventListener('change', function() {
            this.onDateTypeChanged();
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

      if (document.getElementById(MaintenanceTicket.PageElements.ACTION_CANCEL_BUTTON) != null)
      {
         document.getElementById(MaintenanceTicket.PageElements.ACTION_CANCEL_BUTTON).addEventListener('click', function() {
            hideModal(MaintenanceTicket.PageElements.ACTION_PANEL);
         }.bind(this));
      }

      if (document.getElementById(MaintenanceTicket.PageElements.ACTION_OK_BUTTON) != null)
      {
         document.getElementById(MaintenanceTicket.PageElements.ACTION_OK_BUTTON).addEventListener('click', function() {
            hideModal(MaintenanceTicket.PageElements.ACTION_PANEL);
            this.onActionOkButton();
         }.bind(this));
      }

      if (document.getElementById(MaintenanceTicket.PageElements.UPDATE_BUTTON) != null)
      {
         document.getElementById(MaintenanceTicket.PageElements.UPDATE_BUTTON).addEventListener('click', function() {
            this.onUpdateButton();
         }.bind(this));
      }

      let elements = document.getElementsByClassName(MaintenanceTicket.PageElements.DESCRIPTION_OPTION_INPUT);
      for (let element of elements)
      {
         element.addEventListener('change', function(event) {
            this.onDescriptionOption(event.target, event.target.checked);
         }.bind(this));
      }

      if (document.getElementById(MaintenanceTicket.PageElements.COMMENT_BUTTON) != null)
      {
         document.getElementById(MaintenanceTicket.PageElements.COMMENT_BUTTON).addEventListener('click', function() {
            this.onCommentButton();
         }.bind(this));
      }

      if (document.getElementById(MaintenanceTicket.PageElements.ATTACH_BUTTON) != null)
      {
         document.getElementById(MaintenanceTicket.PageElements.ATTACH_BUTTON).addEventListener('click', function() {
            this.onAttachButton();
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
         // Movable rows
         movableRows: true,
         rowHeader:{headerSort:false, resizable: false, minWidth:30, width:30, rowHandle:true, formatter:"handle"},
         // Columns
         columns:[
            {                           field:"ticketId",           visible:false},
            {                           field:"priority",           visible:false},
            {title:"Ticket #",          field:"ticketNumber",       headerFilter:true},
            {title:"Occured",           field:"formattedOccured",   headerFilter:true},
            {title:"Posted",            field:"formattedDate",      headerFilter:true},
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
                  let cellValue = null;
                  let nextAction = cell.getRow().getData().nextAction;
                  if (nextAction)
                  {
                     let nextActionLabel = cell.getRow().getData().nextActionLabel;
                     let nextActionApiCommand = cell.getRow().getData().nextActionApiCommand;
                     cellValue =`<button class=\"small-button accent-button\" style=\"width:100px;\">${nextActionLabel}</button>`;
                  }
                  return (cellValue);
               }
            },
            {title:"Resolve Time",      field:"formattedResolveTime"},
            {title:"",                  field:"delete", tooltip:"Delete", hozAlign:"center", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ]
      });
      
      this.table.on("cellClick", function(e, cell) {
         let ticketId = parseInt(cell.getRow().getData().ticketId);
         
         if (cell.getColumn().getField() == "action")
         {
            let actionLabel = cell.getRow().getData().nextActionLabel;
            let apiCommand = cell.getRow().getData().nextActionApiCommand;

            this.showActionPanel(actionLabel, ticketId, apiCommand);
            e.stopPropagation();
         }
         else if (cell.getColumn().getField() == "delete")
         {
            this.onDeleteButton(ticketId);
            e.stopPropagation();
         }
         else
         {
            document.location = `/maintenanceTicket/maintenanceTicket.php?ticketId=${ticketId}`;
         }
      }.bind(this));

      this.table.on("rowMoved", function() {
         this.onRowMoved();
      }.bind(this));
   }

   setStatus(status)
   {
      this.status = status;
      
      this.updateControls();
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
         disable(MaintenanceTicket.PageElements.DATE_TYPE_INPUT);
      }
      else
      {
         enable(MaintenanceTicket.PageElements.START_DATE_INPUT);
         enable(MaintenanceTicket.PageElements.END_DATE_INPUT);
         enable(MaintenanceTicket.PageElements.DATE_TYPE_INPUT);
      }
      
      this.onFilterUpdate();

      this.sortTable();
      
      setSession("maintenanceTicket.activeTickets", (activeTickets ? "true" : "false"));
   }

   onDateTypeChanged()
   {
      this.onFilterUpdate();
      
      setSession("maintenanceTicket.dateType", document.getElementById(MaintenanceTicket.PageElements.DATE_TYPE_INPUT).value);
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

   onActionOkButton()
   {
      // AJAX call to delete the component.
      let requestUrl = `/app/page/maintenanceTicket/?request=${this.apiCommand}&ticketId=${this.ticketId}&notes=`;

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

   onUpdateButton()
   {
      if (this.validateRequestForm())
      {
         submitForm(MaintenanceTicket.PageElements.UPDATE_FORM, "/app/page/maintenanceTicket", function (response) {
            if (response.success == true)
            {
               location.reload();
            }
            else
            {
               alert(response.error);
            }
         })
      }      
   }

   onCommentButton()
   {
      submitForm(MaintenanceTicket.PageElements.HISTORY_FORM, "/app/page/maintenanceTicket", function (response) {
         if (response.success == true)
         {
            location.reload();
         }
         else
         {
            alert(response.error);
         }
      })
   }

   onAttachButton()
   {
      if (this.validateRequestForm())
      {
         submitForm(MaintenanceTicket.PageElements.ATTACHMENTS_FORM, "/app/page/maintenanceTicket", function (response) {
            if (response.success == true)
            {
               location.reload();
            }
            else
            {
               alert(response.error);
            }
         })
      }      
   }

   onDescriptionOption(element, isChecked)
   {
      let label = element.dataset.label;
      let descriptionInput = document.getElementById(MaintenanceTicket.PageElements.DESCRIPTION_INPUT);

      if (isChecked)
      {
         let seperator = (descriptionInput.value.length == 0) ? "" : "; ";
         descriptionInput.value += (seperator + label);
      }
      else
      {
         let regex = new RegExp(`; ${label}`, "g");
         descriptionInput.value = descriptionInput.value.replace(regex, "");

         regex = new RegExp(`${label}`, "g");
         descriptionInput.value = descriptionInput.value.replace(regex, "");
      }
   }

   onRowMoved()
   {
      let priority = 1;
      for (let row of this.table.getRows())
      {
         let ticketId = row.getData().ticketId;
         this.setPriority(ticketId, priority++);
      }
   }

   onRaisePriorityButton(ticketId)
   {
         // AJAX call to delete the component.
         let requestUrl = `/app/page/maintenanceTicket/?request=prioritize&ticketId=${ticketId}&raisePriority=true`;
         
         ajaxRequest(requestUrl, function(response) {
            if (response.success == true)
            {
               this.onFilterUpdate();
            }
            else
            {
               console.log(response.error);
            }
         }.bind(this));
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

      params.dateType =  document.getElementById(MaintenanceTicket.PageElements.DATE_TYPE_INPUT).value;

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

   validateRequestForm()
   {
      return (true);
   }

   setPriority(ticketId, priority)
   {
      // AJAX call to delete the component.
      let requestUrl = `/app/page/maintenanceTicket/?request=set_priority&ticketId=${ticketId}&priority=${priority}`;
      
      ajaxRequest(requestUrl, function(response) {
         if (response.success == true)
         {
            //  No action.
         }
         else
         {
            console.log(response.error);
         }
      }.bind(this));
   }

   sortTable()
   {
      if (document.getElementById(MaintenanceTicket.PageElements.ACTIVE_TICKETS_INPUT).checked)
      {
         this.table.setSort([{column:"priority", dir:"asc"}]);
      }
      else
      {
         this.table.setSort([{column:"ticketId", dir:"desc"}]);
      }
   }

   showActionPanel(actionLabel, ticketId, apiCommand)
   {
      document.getElementById(MaintenanceTicket.PageElements.ACTION_PANEL_TITLE).innerHTML = actionLabel;
      
      this.ticketId = ticketId;
      this.apiCommand = apiCommand;

      showModal(MaintenanceTicket.PageElements.ACTION_PANEL, "block");
   }
   
   // **************************************************************************
}