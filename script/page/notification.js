class Notification
{
   // HTML elements
   static PageElements = {
      // Forms
      "INPUT_FORM":                    "input-form",
      // Filters
      "START_DATE_INPUT":              "start-date-input",
      "END_DATE_INPUT":                "end-date-input",
      "SHOW_ALL_UNACKNOWLEDGED_INPUT": "show-all-unacknowledged-input",
      // Tables
      "DATA_TABLE":                    "data-table",
      // Buttons
      "ADD_BUTTON":                    "add-button",
      "SAVE_BUTTON":                   "save-button",
      "CANCEL_BUTTON":                 "cancel-button",
      // Input fields
      "PRIORITY_INPUT":                "priority-input",
      "SUBJECT_INPUT":                 "subject-input",
   };

   constructor()
   {      
      this.table = null;
      
      this.setup();
   }
   
   setup()
   {
      if (document.getElementById(Notification.PageElements.START_DATE_INPUT) != null)
      {
         document.getElementById(Notification.PageElements.START_DATE_INPUT).addEventListener('change', function() {
            this.onStartDateChanged();
         }.bind(this));
      }

      if (document.getElementById(Notification.PageElements.END_DATE_INPUT) != null)
      {
         document.getElementById(Notification.PageElements.END_DATE_INPUT).addEventListener('change', function() {
            this.onEndDateChanged();
         }.bind(this));
      }
      
      if (document.getElementById(Notification.PageElements.SHOW_ALL_UNACKNOWLEDGED_INPUT) != null)
      {
         document.getElementById(Notification.PageElements.SHOW_ALL_UNACKNOWLEDGED_INPUT).addEventListener('change', function() {
            this.onShowAllUnacknowledgedChanged();
         }.bind(this));
      
         if (document.getElementById(Notification.PageElements.SHOW_ALL_UNACKNOWLEDGED_INPUT).checked)
         {
            disable(Notification.PageElements.START_DATE_INPUT);
            disable(Notification.PageElements.END_DATE_INPUT);
         }      
      }
      
      if (document.getElementById(Notification.PageElements.ADD_BUTTON) != null)
      {
         document.getElementById(Notification.PageElements.ADD_BUTTON).addEventListener('click', function() {
            this.onSendMessageButton();
         }.bind(this));
      }
      
      if (document.getElementById(Notification.PageElements.SAVE_BUTTON) != null)
      {
         document.getElementById(Notification.PageElements.SAVE_BUTTON).addEventListener('click', function() {
            this.onSaveButton();
         }.bind(this));
      }
      
      if (document.getElementById(Notification.PageElements.CANCEL_BUTTON) != null)
      {
         document.getElementById(Notification.PageElements.CANCEL_BUTTON).addEventListener('click', function() {
            this.onCancelButton();
         }.bind(this));
      }
      
      if (document.getElementById(Notification.PageElements.PRIORITY_INPUT) != null)
      {
         let validator = new SelectValidator(Notification.PageElements.PRIORITY_INPUT);
         validator.init();
      }
   }      
   
   createTable(tableElementId)
   {
      let url = this.getTableQuery();
      let params = this.getTableQueryParams();
      
      let tableElementQuery = "#" + tableElementId;
   
      // Create Tabulator table
      this.table = new Tabulator(tableElementQuery, {
         layout:"fitData",
         cellVertAlign:"middle",
         ajaxURL:url,
         ajaxParams:params,
         ajaxResponse:function(url, params, response) {
            let tableData = [];
            if (response.success)
            {
               tableData = response.notifications;
            }
            return (tableData);
         },
         //Define Table Columns
         columns:[
            {                           field:"notificationId",          visible:false},
            {title:"Date/Time",         field:"formattedDateTime",       headerFilter:true},
            {title:"Priority",          field:"priorityLabel",           headerFilter:true, hozAlign:"center",
               formatter:function(cell, formatterParams, onRendered){
                  var label = cell.getRow().getData().priorityLabel.toUpperCase();
                  var cssClass = cell.getRow().getData().priorityClass;
                  return ("<div class=\"app-notification-priority " + cssClass + "\">" + label + "</div>");
               }
            }, 
            {title:"From",              field:"fromLabel",               headerFilter:true},
            {title:"Subject",           field:"subject",                 headerFilter:true},
            {title:"Message",           field:"message"},
            {title:"Acknowledged",      field:"acknowledged",
               formatter:function(cell, formatterParams, onRendered){
                  let acknowledged = cell.getRow().getData().acknowledged;                   
                  let disabled = "";
                  let checked = acknowledged ? "checked" : "";
               
                  return (`<input type=\"checkbox\" ${checked} ${disabled}>`);
               },
               formatterPrint:function(cell, formatterParams, onRendered){
                  let acknowledged = cell.getValue();   
                  return (acknowledged ? "YES" : "");
               }  
            }
         ],
         initialSort:[
            {column:"notificationId", dir:"asc"}
         ],
         cellClick:function(e, cell){
            if (cell.getColumn().getField() == "acknowledged")
            {
               let notificationId = cell.getRow().getData().notificationId;
               let acknowledged = cell.getRow().getData().acknowledged;
               
               this.onAcknowledged(notificationId, !acknowledged);
            }
         }.bind(this),
      });
   }
   
   // **************************************************************************
         
   onStartDateChanged()
   {
      if (!this.validateFilterDates())
      {
         document.getElementById(Notification.PageElements.END_DATE_INPUT).value = 
            document.getElementById(Notification.PageElements.START_DATE_INPUT).value
      }
      
      this.onFilterUpdate();
      
      setSession("notification.startDate", document.getElementById(Notification.PageElements.START_DATE_INPUT).value);
   }
   
   onEndDateChanged()
   {
      if (!this.validateFilterDates())
      {
         document.getElementById(Notification.PageElements.START_DATE_INPUT).value = 
            document.getElementById(Notification.PageElements.END_DATE_INPUT).value
      }

      this.onFilterUpdate();
      
      setSession("notification.endDate", document.getElementById(Notification.PageElements.END_DATE_INPUT).value);
   }
   
   onShowAllUnacknowledgedChanged()
   {
      if (document.getElementById(Notification.PageElements.SHOW_ALL_UNACKNOWLEDGED_INPUT).checked)
      {
         disable(Notification.PageElements.START_DATE_INPUT);
         disable(Notification.PageElements.END_DATE_INPUT);
      }
      else
      {
         enable(Notification.PageElements.START_DATE_INPUT);
         enable(Notification.PageElements.END_DATE_INPUT);
      }
      
      this.onFilterUpdate();
      
      setSession("notification.showAllUnacknowledged", document.getElementById(Notification.PageElements.SHOW_ALL_UNACKNOWLEDGED_INPUT).checked);
   }
   
   onDeleteButton(notificationId)
   {
      if (confirm("Are you sure you want to permanently remove this notification?"))
      {
         // AJAX call to delete the component.
         let requestUrl = `/app/page/notification/?request=delete_app_notification&notificationId=${notificationId}`;
         
         ajaxRequest(requestUrl, function(response) {
            if (response.success == true)
            {
               this.updateTables();
            }
            else
            {
               alert(response.error);
            }
         }.bind(this));
      }
   }
   
   onAcknowledged(notificationId, acknowledged)
   {
      // AJAX call to acknowledge an app notification.
      let requestUrl = `/app/page/notification/?request=acknowledge_notification&notificationId=${notificationId}&acknowledged=${acknowledged}`;

      ajaxRequest(requestUrl, function(response) {
         if (response.success == true)
         {
            this.updateTable();
         }
         else
         {
            console.log(error);
            alert(response.error);
         }
      }.bind(this));
   }
   
   onSendMessageButton()
   {
      location.href = "/notification/sendMessage.php";
   }
   
   onSaveButton()
   {
      if (this.validateForm())
      {
         submitForm(Notification.PageElements.INPUT_FORM, "/app/page/notification", function (response) {
            if (response.success == true)
            {
               alert("Message sent.");
               location.href = "/notification/notifications.php";
            }
            else
            {
               alert(response.error);
            }
         })
      }
   }
   
   onCancelButton()
   {
      window.history.back();
   }
      
   // **************************************************************************
   
   validateFilterDates()
   {
      /*
      let startDate = document.getElementById(Notification.PageElements.START_DATE_INPUT).value;
      let endDate = document.getElementById(Notification.PageElements.END_DATE_INPUT).value;
      */
      
      return (true);
   }
   
   onFilterUpdate()
   {
      if (document.readyState === "complete")
      {
         this.updateTable();
      }
   }
   
   updateTable()
   {
      let url = this.getTableQuery();
      let params = this.getTableQueryParams();
 
      this.table.setData(url, params)
      .then(function(){
         // Run code after table has been successfuly updated
      })
      .catch(function(error){
         // Handle error loading data
      });
   }
   
   getTableQuery()
   {
      return ("/app/page/notification/");
   }
   
   getTableQueryParams()
   {
      let params = new Object();
      params.request = "fetch_app_notfications";
      params.startDate =  document.getElementById(Notification.PageElements.START_DATE_INPUT).value;
      params.endDate =  document.getElementById(Notification.PageElements.END_DATE_INPUT).value;
      params.showAllUnacknowledged = document.getElementById(Notification.PageElements.SHOW_ALL_UNACKNOWLEDGED_INPUT).checked;
   
      return (params);
   }
   
   validateForm()
   {
      let valid = false;
   
      let targetCount = 0;
      for (let input of document.getElementsByClassName("target-input"))
      {
         if (input.checked)
         {
            targetCount++;
         }
      }
   
      if (targetCount == 0)
      {
         alert("Please specify at least one target user before sending.");    
      }
      else if (!(document.getElementById(Notification.PageElements.PRIORITY_INPUT).validator.validate()))
      {
         alert("Choose a message priority before sending.");    
      }
      else if (document.getElementById(Notification.PageElements.SUBJECT_INPUT).value == "")
      {
         alert("Messages require a subject.");    
      }
      else
      {
         valid = true;
      }
   
      return (valid);
   }
}