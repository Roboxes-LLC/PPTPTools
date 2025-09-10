class CorrectiveAction
{
   // HTML elements
   static PageElements = {
      // Forms
      "INPUT_FORM":             "input-form",
      "REQUEST_FORM":           "request-form",
      "ATTACHMENTS_FORM":       "attachments-form",
      "APPROVE_FORM":           "approve-form",
      "REVIEW_FORM":            "review-form",
      "HISTORY_FORM":           "history-form",
      // Filters
      "START_DATE_INPUT":       "start-date-input",
      "END_DATE_INPUT":         "end-date-input",
      "ACTIVE_ACTIONS_INPUT":   "active-actions-input",
      // Buttons
      "ADD_BUTTON":             "add-button",
      "SAVE_BUTTON":            "save-button",
      "CANCEL_BUTTON":          "cancel-button",
      "UPDATE_BUTTON":          "update-button",
      "UPDATE_CORRECTION_BUTTON": "update-correction-button",
      "ATTACH_BUTTON":          "attach-button",
      "COMMENT_BUTTON":         "comment-button",
      "APPROVE_BUTTON":         "approve-button",
      "UNAPPROVE_BUTTON":       "unapprove-button",
      "COMPLETE_BUTTON":        "complete-button",
      "CLOSE_BUTTON":           "close-button",
      "REOPEN_BUTTON":          "reopen-button",
      // Input fields
      "CORRECTIVE_ACTION_ID_INPUT": "corrective-action-id-input",
      "JOB_ID_INPUT":           "job-id-input",
      "JOB_NUMBER_INPUT":       "job-number-input",
      "WC_NUMBER_INPUT":        "wc-number-input",
      "PAN_TICKET_CODE_INPUT":  "pan-ticket-code-input",
      "EMPLOYEE_NUMBER_INPUT":  "employee-number-input",
      "OCCURANCE_DATE_INPUT":   "occurance-date-input",
      "DIMENSIONAL_DEFECTS_INPUT": "dimensional-defects-input",
      "PLATING_DEFECTS_INPUT":  "plating-defects-input",
      "OTHER_DEFECTS_INPUT":    "other-defects-input",
      "TOTAL_DEFECTS_INPUT":    "total-defects-input",
      "IS_APPROVED_INPUT":      "is-approved-input",
      // Panels
      "REQUEST_PANEL":          "request-panel",
      "ATTACHMENTS_PANEL":      "attachments-panel",
      "SHORT_TERM_CORRECTION_PANEL": "short-term-correction-panel",
      "LONG_TERM_CORRECTION_PANEL":  "long-term-correction-panel",
      "APPROVE_PANEL":          "approve-panel",
      "REVIEW_PANEL":           "review-panel",
      "HISTORY_PANEL":          "history-panel",
   };

   constructor()
   {      
      this.table = null;
      this.status = CorrectiveActionStatus.UNKNOWN;
      
      this.setup();
   }
   
   setup()
   {
      if (document.getElementById(CorrectiveAction.PageElements.START_DATE_INPUT) != null)
      {
         document.getElementById(CorrectiveAction.PageElements.START_DATE_INPUT).addEventListener('change', function() {
            this.onStartDateChanged();
         }.bind(this));
      }

      if (document.getElementById(CorrectiveAction.PageElements.END_DATE_INPUT) != null)
      {
         document.getElementById(CorrectiveAction.PageElements.END_DATE_INPUT).addEventListener('change', function() {
            this.onEndDateChanged();
         }.bind(this));
      }
      
      if (document.getElementById(CorrectiveAction.PageElements.ACTIVE_ACTIONS_INPUT) != null)
      {
         document.getElementById(CorrectiveAction.PageElements.ACTIVE_ACTIONS_INPUT).addEventListener('change', function() {
            this.onActiveActionsChanged();
         }.bind(this));
         
         if (document.getElementById(CorrectiveAction.PageElements.ACTIVE_ACTIONS_INPUT).checked)
         {
            disable(CorrectiveAction.PageElements.START_DATE_INPUT);
            disable(CorrectiveAction.PageElements.END_DATE_INPUT);
         }
         else
         {
            enable(CorrectiveAction.PageElements.START_DATE_INPUT);
            enable(CorrectiveAction.PageElements.END_DATE_INPUT);
         }
      }
      
      if (document.getElementById(CorrectiveAction.PageElements.ADD_BUTTON) != null)
      {
         document.getElementById(CorrectiveAction.PageElements.ADD_BUTTON).addEventListener('click', function() {
            this.onAddButton();
         }.bind(this));
      }
      
      if (document.getElementById(CorrectiveAction.PageElements.SAVE_BUTTON) != null)
      {
         document.getElementById(CorrectiveAction.PageElements.SAVE_BUTTON).addEventListener('click', function() {
            this.onSaveButton();
         }.bind(this));
      }
      
      if (document.getElementById(CorrectiveAction.PageElements.CANCEL_BUTTON) != null)
      {
         document.getElementById(CorrectiveAction.PageElements.CANCEL_BUTTON).addEventListener('click', function() {
            this.onCancelButton();
         }.bind(this));
      }
      
      if (document.getElementById(CorrectiveAction.PageElements.UPDATE_BUTTON) != null)
      {
         document.getElementById(CorrectiveAction.PageElements.UPDATE_BUTTON).addEventListener('click', function() {
            this.onUpdateButton();
         }.bind(this));
      }
      
      if (document.getElementById(CorrectiveAction.PageElements.APPROVE_BUTTON) != null)
      {
         document.getElementById(CorrectiveAction.PageElements.APPROVE_BUTTON).addEventListener('click', function() {
            this.onApproveButton();
         }.bind(this));
      }
      
      if (document.getElementById(CorrectiveAction.PageElements.UNAPPROVE_BUTTON) != null)
      {
         document.getElementById(CorrectiveAction.PageElements.UNAPPROVE_BUTTON).addEventListener('click', function() {
            this.onUnapproveButton();
         }.bind(this));
      }
      
      if (document.getElementById(CorrectiveAction.PageElements.COMPLETE_BUTTON) != null)
      {
         document.getElementById(CorrectiveAction.PageElements.COMPLETE_BUTTON).addEventListener('click', function() {
            this.onCompleteButton();
         }.bind(this));
      }
      
      if (document.getElementById(CorrectiveAction.PageElements.CLOSE_BUTTON) != null)
      {
         document.getElementById(CorrectiveAction.PageElements.CLOSE_BUTTON).addEventListener('click', function() {
            this.onCloseButton();
         }.bind(this));
      }
      
      let buttons = document.getElementsByClassName(CorrectiveAction.PageElements.UPDATE_CORRECTION_BUTTON);
      for (let button of buttons)
      {
         button.addEventListener('click', function(e) {
            this.onUpdateCorrectionButton(e.target);
         }.bind(this));
      }
      
      if (document.getElementById(CorrectiveAction.PageElements.ATTACH_BUTTON) != null)
      {
         document.getElementById(CorrectiveAction.PageElements.ATTACH_BUTTON).addEventListener('click', function() {
            this.onAttachButton();
         }.bind(this));
      }
      
      let deleteButtons = document.getElementsByClassName(CorrectiveAction.PageElements.DELETE_ATTACH_BUTTON);
      for (let button of deleteButtons)
      {
         let attachmentId = button.dataset.attachmentid;
         
         button.addEventListener('click', function() {
            this.onDeleteAttachmentButton(attachmentId);
         }.bind(this));
      }
      
      if (document.getElementById(CorrectiveAction.PageElements.PAN_TICKET_CODE_INPUT) != null)
      {
         document.getElementById(CorrectiveAction.PageElements.PAN_TICKET_CODE_INPUT).addEventListener('change', function() {
            this.onPanTicketCodeChanged();
         }.bind(this));
      }
      
      if (document.getElementById(CorrectiveAction.PageElements.JOB_NUMBER_INPUT) != null)
      {
         document.getElementById(CorrectiveAction.PageElements.JOB_NUMBER_INPUT).addEventListener('change', function() {
            this.onJobNumberChanged();
         }.bind(this));
      }
      
      if (document.getElementById(CorrectiveAction.PageElements.WC_NUMBER_INPUT) != null)
      {
         document.getElementById(CorrectiveAction.PageElements.WC_NUMBER_INPUT).addEventListener('change', function() {
            this.onWcNumberChanged();
         }.bind(this));
      }
      
      if (document.getElementById(CorrectiveAction.PageElements.DIMENSIONAL_DEFECTS_INPUT) != null)
      {
         document.getElementById(CorrectiveAction.PageElements.DIMENSIONAL_DEFECTS_INPUT).addEventListener('change', function() {
            this.onDefectCountChanged();
         }.bind(this));
      }
      
      if (document.getElementById(CorrectiveAction.PageElements.PLATING_DEFECTS_INPUT) != null)
      {
         document.getElementById(CorrectiveAction.PageElements.PLATING_DEFECTS_INPUT).addEventListener('change', function() {
            this.onDefectCountChanged();
         }.bind(this));
      }
      
      if (document.getElementById(CorrectiveAction.PageElements.OTHER_DEFECTS_INPUT) != null)
      {
         document.getElementById(CorrectiveAction.PageElements.OTHER_DEFECTS_INPUT).addEventListener('change', function() {
            this.onDefectCountChanged();
         }.bind(this));
      }
      
      if (document.getElementById(CorrectiveAction.PageElements.COMMENT_BUTTON) != null)
      {
         document.getElementById(CorrectiveAction.PageElements.COMMENT_BUTTON).addEventListener('click', function() {
            this.onCommentButton();
         }.bind(this));
      }
      
      let deleteCommentIcons = document.getElementsByClassName("delete-comment-icon");
      for (let icon of deleteCommentIcons)
      {
         let activityId = icon.dataset.activityId;
         
         icon.addEventListener('click', function() {
            this.onDeleteCommentButton(activityId);
         }.bind(this));
      }
      
      if (document.getElementById(CorrectiveAction.PageElements.CUSTOMER_ID_INPUT) != null)
      {
         document.getElementById(CorrectiveAction.PageElements.CUSTOMER_ID_INPUT).addEventListener('change', function() {
            this.onCustomerChanged();
         }.bind(this));
      }
      
      // Collapsible panels.
      let headers = document.querySelectorAll(".collapsible-panel-header");
      for (let header of headers)
      {
         header.addEventListener('click', function(event) {
            let panelId = event.target.closest(".collapsible-panel").id;
            this.togglePanel(panelId);
         }.bind(this));
      }
      
      // Forms.
      if (document.getElementsByTagName("form").length > 0)
      {               
         InputValidator.setupValidation();
      }
   }      
   
   createTable(tableElementId)
   {
      let url = this.getTableQuery();
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
               tableData = response.correctiveActions;
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
            {                         field:"correctiveActionId",      visible:false},
            {title:"CAR #",           field:"correctiveActionNumber",  headerFilter:true},
            {title:"Status",          field:"statusLabel",             headerFilter:true,
               formatter:function(cell, formatterParams, onRendered){
                  let label = cell.getRow().getData().statusLabel;
                  let cssClass = cell.getRow().getData().statusClass;
                  return ("<div class=\"corrective-action-status " + cssClass + "\">" + label + "</div>");
               }
            },
            {title:"Occurance Date",  field:"formattedOccuranceDate",  headerFilter:true},
            {title:"Customer",        field:"customerName",            headerFilter:true},
            {title:"Customer Part #", field:"customerPartNumber",      headerFilter:true},
            {title:"PPTP Part #",     field:"pptpPartNumber",          headerFilter:true},
            {title:"Location",        field:"locationLabel",           headerFilter:true},
            {title:"Initiated By",    field:"initiatorLabel",          headerFilter:true},
            {title:"Total Defects",   field:"totalDefectCount",        headerFilter:false},
            {title:"DMR Number",      field:"dmrNumber",               headerFilter:true},
            {title:"",                field:"delete",                  hozAlign:"center", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ],
         initialSort:[
            {column:"correctiveActionNumber", dir:"asc"}
         ]
      });
      
      this.table.on("cellClick", function(e, cell) {
         let correctiveActionId = parseInt(cell.getRow().getData().correctiveActionId);
         
         if (cell.getColumn().getField() == "delete")
         {
            this.onDeleteButton(correctiveActionId);
            e.stopPropagation();

         }
         else
         {
            document.location = `/correctiveAction/correctiveAction.php?correctiveActionId=${correctiveActionId}`;
         }
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
         document.getElementById(CorrectiveAction.PageElements.END_DATE_INPUT).value = 
            document.getElementById(CorrectiveAction.PageElements.START_DATE_INPUT).value
      }
      
      this.onFilterUpdate();
      
      setSession("correctiveAction.startDate", document.getElementById(CorrectiveAction.PageElements.START_DATE_INPUT).value);
   }
   
   onEndDateChanged()
   {
      if (!this.validateFilterDates())
      {
         document.getElementById(CorrectiveAction.PageElements.START_DATE_INPUT).value = 
            document.getElementById(CorrectiveAction.PageElements.END_DATE_INPUT).value
      }

      this.onFilterUpdate();
      
      setSession("correctiveAction.endDate", document.getElementById(CorrectiveAction.PageElements.END_DATE_INPUT).value);
   }
   
   onActiveActionsChanged()
   {
      var activeActions = document.getElementById(CorrectiveAction.PageElements.ACTIVE_ACTIONS_INPUT).checked;
      
      if (activeActions)
      {
         disable(CorrectiveAction.PageElements.START_DATE_INPUT);
         disable(CorrectiveAction.PageElements.END_DATE_INPUT);
      }
      else
      {
         enable(CorrectiveAction.PageElements.START_DATE_INPUT);
         enable(CorrectiveAction.PageElements.END_DATE_INPUT);
      }
      
      this.onFilterUpdate();
      
      setSession("correctiveAction.activeActions", (activeActions ? "true" : "false"));
   }
   
   onAddButton()
   {
      document.location = `/correctiveAction/newCorrectiveAction.php`;
   }
   
   onDeleteButton(correctiveActionId)
   {
      if (confirm("Are you sure you want to delete this CAR?"))
      {
         // AJAX call to delete the component.
         let requestUrl = `/app/page/correctiveAction/?request=delete_corrective_action&correctiveActionId=${correctiveActionId}`;
         
         ajaxRequest(requestUrl, function(response) {
            if (response.success == true)
            {
               location.href = "/correctiveAction/correctiveActions.php";
            }
            else
            {
               console.log("Call to delete the CAR failed.");
               alert(response.error);
            }
         });
      }
   }
   
   onPanTicketCodeChanged()
   {
      var panTicketCode = document.getElementById(CorrectiveAction.PageElements.PAN_TICKET_CODE_INPUT).value;
      
      // Clear fields.
      clear(CorrectiveAction.PageElements.JOB_NUMBER_INPUT);
      clear(CorrectiveAction.PageElements.WC_NUMBER_INPUT);
      clear(CorrectiveAction.PageElements.EMPLOYEE_NUMBER_INPUT);
      clear(CorrectiveAction.PageElements.OCCURANCE_DATE_INPUT);
      
      if (panTicketCode == "")
      {
         // Enable fields.
         enable(CorrectiveAction.PageElements.JOB_NUMBER_INPUT);
         enable(CorrectiveAction.PageElements.WC_NUMBER_INPUT);
         
         // Disable WC number, as it's dependent on the job number.
         disable(CorrectiveAction.PageElements.WC_NUMBER_INPUT);
         
         // AJAX call to retrieve active jobs.
         requestUrl = "../api/jobs/?onlyActive=true";
         
         ajaxRequest("../api/jobs/?onlyActive=true", function(response) {
            if (response.success == true)
            {
               this.updateJobOptions(response.jobs);  
            }
            else
            {
               console.log("API call to retrieve jobs failed.");
            }
         }.bind(this)); 
      }
      else
      {
         // Disable fields.
         disable(CorrectiveAction.PageElements.JOB_NUMBER_INPUT);
         disable(CorrectiveAction.PageElements.WC_NUMBER_INPUT);
         
         // AJAX call to populate input fields based on pan ticket selection.
         requestUrl = "../api/timeCardInfo/?panTicketCode=" + panTicketCode + "&expandedProperties=true";
         
         ajaxRequest(requestUrl, function(response) {
            if (response.success == true)
            {
               let manufactureDate = formatInputDate(new Date(response.timeCardInfo.dateTime));
               
               this.updateJobOptions(new Array(response.jobNumber));
               this.updateWcOptions(new Array({wcNumber:response.wcNumber, label:response.wcLabel}));
   
               set(CorrectiveAction.PageElements.JOB_ID_INPUT, response.timeCardInfo.jobId);
               console.log("Set job id: " + response.timeCardInfo.jobId);
               set(CorrectiveAction.PageElements.JOB_NUMBER_INPUT, response.jobNumber);
               set(CorrectiveAction.PageElements.WC_NUMBER_INPUT, response.wcNumber);
               set(CorrectiveAction.PageElements.WC_NUMBER_INPUT, response.wcNumber);
               set(CorrectiveAction.PageElements.EMPLOYEE_NUMBER_INPUT, response.timeCardInfo.employeeNumber);
               set(CorrectiveAction.PageElements.OCCURANCE_DATE_INPUT, manufactureDate);               
            }
            else
            {
               console.log("API call to retrieve time card info failed.");
            }
         }.bind(this));
      }
   }
   
   onJobNumberChanged()
   {
      let jobNumber = document.getElementById(CorrectiveAction.PageElements.JOB_NUMBER_INPUT).value;
      
      if (jobNumber == null)
      {
         disable("wc-number-input");
      }
      else
      {
         enable("wc-number-input");
         
         // Populate WC numbers based on selected job number.
         
         requestUrl = "../api/wcNumbers/?jobNumber=" + jobNumber;
         
         ajaxRequest(requestUrl, function(response) {
            if (response.success == true)
            {
               this.updateWcOptions(response.wcNumbers);   
            }
            else
            {
               console.log("API call to retrieve WC numbers failed.");
            }
         }.bind(this));
      }
   }
   
   onWcNumberChanged()
   {
      let jobNumber = document.getElementById(CorrectiveAction.PageElements.JOB_NUMBER_INPUT).value;
      let wcNumber = document.getElementById(CorrectiveAction.PageElements.WC_NUMBER_INPUT).value;
      
      if ((jobNumber != null) &&
          (wcNumber != null))
      {
         // Fetech job id.
         
         requestUrl = `/app/page/job/?request=fetch&jobNumber=${jobNumber}&wcNumber=${wcNumber}`;
         
         ajaxRequest(requestUrl, function(response) {
            if (response.success == true)
            {
               set(CorrectiveAction.PageElements.JOB_ID_INPUT, response.jobInfo.jobId);
               console.log("Set job id: " + response.jobInfo.jobId);
            }
            else
            {
               console.log("API call to retrieve job failed.");
            }
         }.bind(this));
      }
   }
   
   onDefectCountChanged()
   {
      const inputs = [CorrectiveAction.PageElements.DIMENSIONAL_DEFECTS_INPUT,
                      CorrectiveAction.PageElements.PLATING_DEFECTS_INPUT,
                      CorrectiveAction.PageElements.OTHER_DEFECTS_INPUT];

      let totalDefects = 0;
      
      for (const inputId of inputs)
      {
         let value = get(inputId);
         
         if (!isNaN(parseInt(value)))
         {
            totalDefects += parseInt(value);
         }
      }
      
      set(CorrectiveAction.PageElements.TOTAL_DEFECTS_INPUT, totalDefects);
   }
   
   // **************************************************************************
   
   updateJobOptions(jobNumbers)
   {
      let element = document.getElementById(CorrectiveAction.PageElements.JOB_NUMBER_INPUT);
      
      while (element.firstChild)
      {
         element.removeChild(element.firstChild);
      }
   
      for (var jobNumber of jobNumbers)
      {
         var option = document.createElement('option');
         option.innerHTML = jobNumber;
         option.value = jobNumber;
         element.appendChild(option);
      }
      
      element.value = null;
   }
   
   updateWcOptions(wcNumbers)
   {
      let element = document.getElementById("wc-number-input");
      
      while (element.firstChild)
      {
         element.removeChild(element.firstChild);
      }
   
      for (var wcNumber of wcNumbers)
      {
         let option = document.createElement('option');
         option.innerHTML = wcNumber.label;
         option.value = wcNumber.wcNumber;
         element.appendChild(option);
      }
      
      element.value = null;
   }
   
   // ********************************************************************
   
   onSaveButton()
   {
      if (InputValidator.validateForm(document.getElementById(CorrectiveAction.PageElements.INPUT_FORM)))
      {
         submitForm(CorrectiveAction.PageElements.INPUT_FORM, "/app/page/correctiveAction", function (response) {
            if (response.success == true)
            {
               location.href = `/correctiveAction/correctiveAction.php?correctiveActionId=${response.correctiveActionId}`;
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
      document.location = `/correctiveAction/correctiveActions.php`;
   }
   
   onUpdateButton()
   {
      if (this.validateRequestForm())
      {
         submitForm(CorrectiveAction.PageElements.REQUEST_FORM, "/app/page/correctiveAction", function (response) {
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
   
   onUpdateCorrectionButton(button)
   {
      if (this.validateRequestForm())
      {
         submitForm(button.form.id, "/app/page/correctiveAction", function (response) {
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
   
   onAttachButton()
   {
      if (this.validateRequestForm())
      {
         submitForm(CorrectiveAction.PageElements.ATTACHMENTS_FORM, "/app/page/correctiveAction", function (response) {
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
   
   onDeleteAttachmentButton(attachmentId)
   {
      // AJAX call to delete the attachment.
      let requestUrl = `/app/page/correctiveAction/?request=delete_attachment&attachmentId=${attachmentId}`;
      
      ajaxRequest(requestUrl, function(response) {
         if (response.success == true)
         {
            location.reload();
         }
         else
         {
            console.log("Call to delete the attachment failed.");
            alert(response.error);
         }
      });
   }
   
   onCommentButton()
   {
      submitForm(CorrectiveAction.PageElements.HISTORY_FORM, "/app/page/correctiveAction", function (response) {
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
   
   onDeleteCommentButton(activityId)
   {
      // AJAX call to delete the component.
      let requestUrl = `/app/page/correctiveAction/?request=delete_comment&activityId=${activityId}`;
      
      ajaxRequest(requestUrl, function(response) {
         if (response.success == true)
         {
            location.reload();
         }
         else
         {
            alert(response.error);
         }
      });
   }
     
   onApproveButton()
   {
      document.getElementById(CorrectiveAction.PageElements.IS_APPROVED_INPUT).value = "true";
      
      submitForm(CorrectiveAction.PageElements.APPROVE_FORM, "/app/page/correctiveAction", function (response) {
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
   
   onUnapproveButton()
   {
      document.getElementById(CorrectiveAction.PageElements.IS_APPROVED_INPUT).value = "false";
            
      submitForm(CorrectiveAction.PageElements.APPROVE_FORM, "/app/page/correctiveAction", function (response) {
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
   
   onCompleteButton()
   {
      submitForm(CorrectiveAction.PageElements.REVIEW_FORM, "/app/page/correctiveAction", function (response) {
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
   
   onCloseButton()
   {
      let correctiveActionId = parseInt(get(CorrectiveAction.PageElements.CORRECTIVE_ACTION_ID_INPUT));
      
      // AJAX call to compmlete the review.
      let requestUrl = `/app/page/correctiveAction/?request=close_corrective_action&correctiveActionId=${correctiveActionId}`;
      
      ajaxRequest(requestUrl, function(response) {
         if (response.success == true)
         {
            location.reload();
         }
         else
         {
            alert(response.error);
         }
      });
   }
   
   onCommentButton()
   {
      submitForm(CorrectiveAction.PageElements.HISTORY_FORM, "/app/page/correctiveAction", function (response) {
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
   
   onDeleteCommentButton(activityId)
   {
      // AJAX call to delete the component.
      let requestUrl = `/app/page/quote/?request=delete_comment&activityId=${activityId}`;
      
      ajaxRequest(requestUrl, function(response) {
         if (response.success == true)
         {
            location.reload();
         }
         else
         {
            console.log("Call to delete the comment failed.");
            alert(response.error);
         }
      });
   }
   
   // **************************************************************************
      
   getTableQuery()
   {
      return ("/app/page/correctiveAction/");
   }

   getTableQueryParams()
   {
      let params = new Object();
      
      params.request = "fetch";
      
      params.startDate =  document.getElementById(CorrectiveAction.PageElements.START_DATE_INPUT).value;
      
      params.endDate =  document.getElementById(CorrectiveAction.PageElements.END_DATE_INPUT).value;
      
      params.activeActions = document.getElementById(CorrectiveAction.PageElements.ACTIVE_ACTIONS_INPUT).checked;

      return (params);
   }
   
   validateFilterDates()
   {
      let startDate = document.getElementById(CorrectiveAction.PageElements.START_DATE_INPUT).value;
      let endDate = document.getElementById(CorrectiveAction.PageElements.END_DATE_INPUT).value;
      
      return (new Date(endDate) >= new Date(startDate))
   }
   
   onFilterUpdate()
   {
      if (document.readyState === "complete")
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
   }
   
   validateRequestForm()
   {
      return (true);
   }
   
   // **************************************************************************
   
   updateControls()
   {
      switch (this.status)
      {
         case CorrectiveActionStatus.OPEN:
         {
            this.hideElement(CorrectiveAction.PageElements.REVIEW_PANEL);
            break;
         }
         
         case CorrectiveActionStatus.APPROVED:
         {
            this.collapsePanel(CorrectiveAction.PageElements.REQUEST_PANEL);
            this.collapsePanel(CorrectiveAction.PageElements.SHORT_TERM_CORRECTION_PANEL);
            this.collapsePanel(CorrectiveAction.PageElements.LONG_TERM_CORRECTION_PANEL);
            this.collapsePanel(CorrectiveAction.PageElements.APPROVE_PANEL);
            
            this.hideElement(CorrectiveAction.PageElements.APPROVE_BUTTON);
            this.hideElement(CorrectiveAction.PageElements.CLOSE_BUTTON);
            this.hideElement(CorrectiveAction.PageElements.REOPEN_BUTTON);
            break;
         }
         
         case CorrectiveActionStatus.REVIEWED:
         {
            this.collapsePanel(CorrectiveAction.PageElements.REQUEST_PANEL);
            this.collapsePanel(CorrectiveAction.PageElements.SHORT_TERM_CORRECTION_PANEL);
            this.collapsePanel(CorrectiveAction.PageElements.LONG_TERM_CORRECTION_PANEL);
            this.collapsePanel(CorrectiveAction.PageElements.APPROVE_PANEL);
            
            this.hideElement(CorrectiveAction.PageElements.APPROVE_BUTTON);
            this.hideElement(CorrectiveAction.PageElements.COMPLETE_BUTTON);
            this.hideElement(CorrectiveAction.PageElements.REOPEN_BUTTON);
            break;
         }
         
         case CorrectiveActionStatus.CLOSED:
         {
            this.collapsePanel(CorrectiveAction.PageElements.REQUEST_PANEL);
            this.collapsePanel(CorrectiveAction.PageElements.SHORT_TERM_CORRECTION_PANEL);
            this.collapsePanel(CorrectiveAction.PageElements.LONG_TERM_CORRECTION_PANEL);
            this.collapsePanel(CorrectiveAction.PageElements.APPROVE_PANEL);

            this.hideElement(CorrectiveAction.PageElements.UPDATE_BUTTON);
            this.hideElement(CorrectiveAction.PageElements.APPROVE_BUTTON);
            this.hideElement(CorrectiveAction.PageElements.UNAPPROVE_BUTTON);
            this.hideElement(CorrectiveAction.PageElements.COMPLETE_BUTTON);
            this.hideElement(CorrectiveAction.PageElements.CLOSE_BUTTON);
            
            let buttons = document.getElementsByClassName(CorrectiveAction.PageElements.UPDATE_CORRECTION_BUTTON);
            for (let button of buttons)
            {
               this.hideElement(button.id);
            }
            break;
         }
         
         default:
         {
            break;
         }
      }
   }

   hideElement(panelId)
   {
      document.getElementById(panelId).classList.add("hidden");
   }
   
   togglePanel(panelId)
   {
      let panel = document.getElementById(panelId);
      
      if (panel.classList.contains("collapsed"))
      {
         this.expandPanel(panelId);   
      }
      else
      {
         this.collapsePanel(panelId);
      }
   }
   
   collapsePanel(panelId)
   {
      document.getElementById(panelId).classList.add("collapsed");
   }
   
   expandPanel(panelId)
   {
      document.getElementById(panelId).classList.remove("collapsed");
   }
   
   accentButton(buttonId)
   {
      document.getElementById(buttonId).classList.add("accent-button");
   }
}

// *****************************************************************************
//                                 Input validators

class JobIdValidator extends InputValidator
{
   constructor(input)
   {
      super(input);
   }
      
   validate()
   {
      // Clear any previous custom validity.
      this.input.setCustomValidity("");
      
      let jobId = parseInt(document.getElementById(CorrectiveAction.PageElements.JOB_ID_INPUT).value);
      
      // Verify a valid job has been determined.
      let isValid = (jobId > 0);
      
      if (!isValid)
      {
         alert("Start by selecting a valid pan ticket or active job.");
      }
      
      // Verify a valid job has been determined.
      return (isValid);
   }
}