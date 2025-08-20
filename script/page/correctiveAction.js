class CorrectiveAction
{
   // HTML elements
   static PageElements = {
      // Forms
      "INPUT_FORM":             "input-form",
      "REQUEST_FORM":           "request-form",
      "ATTACHMENTS_FORM":       "attachments-form",
      "QUOTE_FORM":             "quote-form",
      "APPROVE_FORM":           "approve-form",
      "ACCEPT_FORM":            "accept-form",
      "SEND_FORM":              "send-form",
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
      "ATTACH_BUTTON":          "attach-button",
      "SAVE_ESTIMATE_BUTTON":   "save-estimate-button",
      "SUBMIT_ESTIMATE_BUTTON": "submit-estimate-button",
      "REVISE_BUTTON":          "revise-button",
      "APPROVE_BUTTON":         "approve-button",
      "UNAPPROVE_BUTTON":       "unapprove-button",
      "PREVIEW_BUTTON":         "preview-button",
      "SAVE_DRAFT_BUTTON":      "save-draft-button",
      "SEND_BUTTON":            "send-button",
      "RESEND_BUTTON":          "resend-button",
      "ACCEPT_BUTTON":          "accept-button",
      "REJECT_BUTTON":          "reject-button",
      "COMMENT_BUTTON":         "comment-button",
      "DELETE_ATTACH_BUTTON":   "delete-quote-attachment-button",
      // Input fields
      "JOB_ID_INPUT":           "job-id-input",
      "JOB_NUMBER_INPUT":       "job-number-input",
      "WC_NUMBER_INPUT":        "wc-number-input",
      "PAN_TICKET_CODE_INPUT":  "pan-ticket-code-input",
      "EMPLOYEE_NUMBER_INPUT":  "employee-number-input",
      "OCCURANCE_DATE_INPUT":   "occurance-date-input",

      "IS_APPROVED_INPUT":      "is-approved-input",
      "IS_ACCEPTED_INPUT":      "is-accepted-input",
      "EMAIL_NOTES_INPUT":      "email-notes-input",
      // Panels
      "REQUEST_PANEL":          "request-panel",
      "ATTACHMENTS_PANEL":      "attachments-panel",
      "ESTIMATES_PANEL":        "estimates-panel",
      "APPROVE_PANEL":          "approve-panel",
      "SEND_PANEL":             "send-panel",
      "ACCEPT_PANEL":           "accept-panel",
      "HISTORY_PANEL":          "history-panel",
      "PIN_CONFIRM_MODAL":      "pin-confirm-modal"
   };

   constructor()
   {      
      this.table = null;
      this.quoteStatus = QuoteStatus.UNKNOWN;
      
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
      
      // Estimate selection inputs
      let inputs = document.getElementsByClassName("estimate-selection-input");
      for (const element of inputs)
      {
         element.addEventListener('change', function() {
            this.onEstimateSelected();
         }.bind(this));
      }
      this.onEstimateSelected();
      
      // Estimate quantity inputs
      inputs = document.getElementsByClassName("estimate-quantity-input");
      for (const element of inputs)
      {
         element.addEventListener('change', function(event) {
            CorrectiveAction.recalculateTotalCost(event.target);
         });
      }
      
      // Unit price inputs
      inputs = document.getElementsByClassName("unit-price-input");
      for (const element of inputs)
      {
         element.addEventListener('change', function(event) {
            CorrectiveAction.recalculateTotalCost(event.target);
         });
      }
      
      // Unit price inputs
      inputs = document.getElementsByClassName("additional-charge-input");
      for (const element of inputs)
      {
         element.addEventListener('change', function(event) {
            CorrectiveAction.recalculateTotalCost(event.target);
         });
      }
      
      // Total cost inputs
      inputs = document.getElementsByClassName("total-cost-input");
      for (const element of inputs)
      {
         CorrectiveAction.recalculateTotalCost(element);
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
         persistence:true,
         // Columns
         columns:[
            {                         field:"correctiveActionId",      visible:false},
            {title:"CA #",            field:"correctiveActionNumber",  headerFilter:true},
            {title:"Date",            field:"formattedOccuranceDate",  headerFilter:true},
            {title:"Customer",        field:"customerName",            headerFilter:true},
            {title:"Customer Part #", field:"customerPartNumber",      headerFilter:true},
            {title:"PPTP Part #",     field:"pptpPartNumber",          headerFilter:true},
            {title:"Location",        field:"locationLabel",           headerFilter:true},
            {title:"Initiated By",    field:"initiatorLabel",          headerFilter:true},
            {title:"Batch Size",      field:"batchSize",               headerFilter:false},
            {title:"Dim. Defects",    field:"dimensionalDefectCount",  headerFilter:false},
            {title:"Plating Defects", field:"platingDefectCount",      headerFilter:false},
            {title:"Other Defects",   field:"otherDefectCount",        headerFilter:false},
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
   
   setQuoteStatus(quoteStatus)
   {
      this.quoteStatus = quoteStatus;
      
      this.updateControls();
   }
   
   onEstimateSelected()
   {
      let inputs = document.getElementsByClassName("estimate-selection-input");
      
      for (const element of inputs)
      {
         if (element.checked)
         {
            element.closest(".estimate-table-column").classList.add("selected");
         }
         else
         {
            element.closest(".estimate-table-column").classList.remove("selected");
         }
      }
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
      var activeQuotes = document.getElementById(CorrectiveAction.PageElements.ACTIVE_ACTIONS_INPUT).checked;
      
      if (activeQuotes)
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
      
      setSession("correctiveAction.activeActions", (activeQuotes ? "true" : "false"));
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
   
   onPreviewButton()
   {
      if (this.validateForm())
      {
         let form = document.getElementById(CorrectiveAction.PageElements.SEND_FORM);
         
         // Rememeber form properties.
         let target = form.target;
         let action = form.action;
         let method = form.method;
         
         // Set target to open in new window.
         form.target = "_blank";
         form.action = "/quote/quotePreview.php";
         form.method = "post";
         
         form.submit();
         
         // Restore form properties.
         form.target = target;
         form.action = action;
         form.method = method;
      }  
   }

   onSaveButton()
   {
      if (this.validateForm())
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
         submitForm(CorrectiveAction.PageElements.REQUEST_FORM, "/app/page/quote", function (response) {
            if (response.success == true)
            {
               location.href = `/quote/CorrectiveAction.php?quoteId=${response.quoteId}`;
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
         submitForm(CorrectiveAction.PageElements.ATTACHMENTS_FORM, "/app/page/quote", function (response) {
            if (response.success == true)
            {
               location.href = `/quote/CorrectiveAction.php?quoteId=${response.quoteId}`;
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
      let requestUrl = `/app/page/quote/?request=delete_attachment&attachmentId=${attachmentId}`;
      
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
   
   onSaveEstimateButton(submitEstimate)
   {
      // Set a form variable to indicate if the estimate is being submitted for approval.
      document.getElementById(CorrectiveAction.PageElements.SUBMIT_ESTIMATE_INPUT).value = submitEstimate;
      
      if (this.validateQuoteForm())
      {
         submitForm(CorrectiveAction.PageElements.QUOTE_FORM, "/app/page/quote", function (response) {
            if (response.success == true)
            {
               if (submitEstimate)
               {
                  location.href = `/quote/CorrectiveAction.php?quoteId=${response.quoteId}`;
               }
               else
               {
                  alert("Estimate saved.");  
               }
            }
            else
            {
               alert(response.error);
            }
         })
      }      
   }
   
   onApproveButton()
   {
      document.getElementById(CorrectiveAction.PageElements.IS_APPROVED_INPUT).value = "true";
      
      submitForm(CorrectiveAction.PageElements.APPROVE_FORM, "/app/page/quote", function (response) {
         if (response.success == true)
         {
            location.href = `/quote/CorrectiveAction.php?quoteId=${response.quoteId}`;
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
            
      submitForm(CorrectiveAction.PageElements.APPROVE_FORM, "/app/page/quote", function (response) {
         if (response.success == true)
         {
            location.href = `/quote/CorrectiveAction.php?quoteId=${response.quoteId}`;
         }
         else
         {
            alert(response.error);
         }
      })
   }
   
   onSaveDraftButton()
   {
      let quoteId = document.getElementById(CorrectiveAction.PageElements.QUOTE_ID_INPUT).value;
      let emailNotes = document.getElementById(CorrectiveAction.PageElements.EMAIL_NOTES_INPUT).value;
      
      // AJAX call to delete the component.
      let requestUrl = `/app/page/quote/?request=save_email_draft&quoteId=${quoteId}&emailNotes=${emailNotes}`;
      
      ajaxRequest(requestUrl, function(response) {
         if (response.success == true)
         {
            alert("Draft saved.");
         }
         else
         {
            console.log("Call to save the email draft.");
            alert(response.error);
         }
      });
   }
   
   onSendButton()
   {
      PINPAD.reset();
      PINPAD.setErrorMessage("Enter employee # to send");
      
      showModal(CorrectiveAction.PageElements.PIN_CONFIRM_MODAL, "block");
   }
   
   onPinConfirmed()
   {
      hideModal(CorrectiveAction.PageElements.PIN_CONFIRM_MODAL);
            
      submitForm(CorrectiveAction.PageElements.SEND_FORM, "/app/page/quote", function (response) {
         if (response.success == true)
         {
            alert("Quote was sent."); 
            
            location.reload();
         }
         else
         {
            alert(response.error);
         }
      });
   }
   
   onAcceptButton()
   {
      document.getElementById(CorrectiveAction.PageElements.IS_ACCEPTED_INPUT).value = "true";
      
      submitForm(CorrectiveAction.PageElements.ACCEPT_FORM, "/app/page/quote", function (response) {
         if (response.success == true)
         {
            location.href = `/quote/CorrectiveAction.php?quoteId=${response.quoteId}`;
         }
         else
         {
            alert(response.error);
         }
      })
   }
   
   onRejectButton()
   {
      document.getElementById(CorrectiveAction.PageElements.IS_ACCEPTED_INPUT).value = "false";
            
      submitForm(CorrectiveAction.PageElements.ACCEPT_FORM, "/app/page/quote", function (response) {
         if (response.success == true)
         {
            location.href = `/quote/CorrectiveAction.php?quoteId=${response.quoteId}`;
         }
         else
         {
            alert(response.error);
         }
      })
   }
   
   onCommentButton()
   {
      submitForm(CorrectiveAction.PageElements.HISTORY_FORM, "/app/page/quote", function (response) {
         if (response.success == true)
         {
            location.href = `/quote/CorrectiveAction.php?quoteId=${response.quoteId}`;
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
   
   onCustomerChanged()
   {
      let customerId = document.getElementById(CorrectiveAction.PageElements.CUSTOMER_ID_INPUT).value;
      
      // AJAX call to delete the component.
      let requestUrl = `/app/page/customer/?request=fetch_contact&customerId=${customerId}`;
      
      ajaxRequest(requestUrl, function(response) {
         if (response.success == true)
         {
            console.log(response);
            this.updateContactOptions(response.contacts);
         }
         else
         {
            console.log("Call to fetch contacts failed.");
         }
      }.bind(this));      
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
      
      if (document.getElementById(CorrectiveAction.PageElements.ACTIVE_ACTIONS_INPUT).checked)
      {
         params.activeActions = true;
      }

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
   
   updateContactOptions(contacts)
   {
      let element = document.getElementById(CorrectiveAction.PageElements.CONTACT_ID_INPUT);
      
      while (element.firstChild)
      {
         element.removeChild(element.firstChild);
      }

      for (let contact of contacts)
      {
         let option = document.createElement('option');
         option.innerHTML = contact.firstName + " " + contact.lastName;
         option.value = contact.contactId;
         element.appendChild(option);
      }
   
      element.value = null;
   }
   
   validateForm()
   {
      return (true);
   }
   
   validateRequestForm()
   {
      return (true);
   }
   
   validateQuoteForm()
   {
      return (true);
   }
   
   // **************************************************************************
   
   updateControls()
   {
      switch (this.quoteStatus)
      {
         case QuoteStatus.REQUESTED:
         {
            this.hideElement(CorrectiveAction.PageElements.APPROVE_PANEL);
            this.hideElement(CorrectiveAction.PageElements.SEND_PANEL);
            this.hideElement(CorrectiveAction.PageElements.ACCEPT_PANEL);
            
            this.collapsePanel(CorrectiveAction.PageElements.ATTACHMENTS_PANEL);
            
            this.hideElement(CorrectiveAction.PageElements.REVISE_BUTTON);
            break;
         }
         
         case QuoteStatus.ESTIMATED:
         case QuoteStatus.REVISED:
         {
            this.hideElement(CorrectiveAction.PageElements.SEND_PANEL);
            this.hideElement(CorrectiveAction.PageElements.ACCEPT_PANEL);
            
            this.collapsePanel(CorrectiveAction.PageElements.ATTACHMENTS_PANEL);
            this.collapsePanel(CorrectiveAction.PageElements.ESTIMATES_PANEL);
            
            this.hideElement(CorrectiveAction.PageElements.SAVE_ESTIMATE_BUTTON);
            this.hideElement(CorrectiveAction.PageElements.SUBMIT_ESTIMATE_BUTTON);
            break;
         }
         
         case QuoteStatus.UNAPPROVED:
         case QuoteStatus.REJECTED:
         {
            this.hideElement(CorrectiveAction.PageElements.APPROVE_PANEL);
            this.hideElement(CorrectiveAction.PageElements.SEND_PANEL);
            this.hideElement(CorrectiveAction.PageElements.ACCEPT_PANEL);
            
            this.collapsePanel(CorrectiveAction.PageElements.ATTACHMENTS_PANEL);
            
            this.hideElement(CorrectiveAction.PageElements.SUBMIT_ESTIMATE_BUTTON);
            
            this.accentButton(CorrectiveAction.PageElements.REVISE_BUTTON);
            break;
         }
         
         case QuoteStatus.APPROVED:
         {
            this.hideElement(CorrectiveAction.PageElements.ACCEPT_PANEL);
            
            this.collapsePanel(CorrectiveAction.PageElements.ATTACHMENTS_PANEL);
            this.collapsePanel(CorrectiveAction.PageElements.ESTIMATES_PANEL);
            this.collapsePanel(CorrectiveAction.PageElements.APPROVE_PANEL);

            this.hideElement(CorrectiveAction.PageElements.SAVE_ESTIMATE_BUTTON);
            this.hideElement(CorrectiveAction.PageElements.SUBMIT_ESTIMATE_BUTTON);
            this.hideElement(CorrectiveAction.PageElements.APPROVE_BUTTON);
            this.hideElement(CorrectiveAction.PageElements.RESEND_BUTTON);
            break;
         }
         
         case QuoteStatus.SENT:
         {
            this.hideElement(CorrectiveAction.PageElements.APPROVE_PANEL);
            
            this.collapsePanel(CorrectiveAction.PageElements.ATTACHMENTS_PANEL);
            this.collapsePanel(CorrectiveAction.PageElements.ESTIMATES_PANEL);
            this.collapsePanel(CorrectiveAction.PageElements.APPROVE_PANEL);
            this.collapsePanel(CorrectiveAction.PageElements.SEND_PANEL);
                        
            this.hideElement(CorrectiveAction.PageElements.SAVE_ESTIMATE_BUTTON);
            this.hideElement(CorrectiveAction.PageElements.SUBMIT_ESTIMATE_BUTTON);
            this.hideElement(CorrectiveAction.PageElements.APPROVE_BUTTON);
            this.hideElement(CorrectiveAction.PageElements.SEND_BUTTON);
            break;
         }
         
         case QuoteStatus.ACCEPTED:
         {
            this.collapsePanel(CorrectiveAction.PageElements.ATTACHMENTS_PANEL);
            this.collapsePanel(CorrectiveAction.PageElements.ESTIMATES_PANEL);
            this.collapsePanel(CorrectiveAction.PageElements.APPROVE_PANEL);
            this.collapsePanel(CorrectiveAction.PageElements.SEND_PANEL);
            this.collapsePanel(CorrectiveAction.PageElements.ACCEPT_PANEL);
            
            this.hideElement(CorrectiveAction.PageElements.SAVE_ESTIMATE_BUTTON);
            this.hideElement(CorrectiveAction.PageElements.SUBMIT_ESTIMATE_BUTTON);
            this.hideElement(CorrectiveAction.PageElements.APPROVE_BUTTON);
            this.hideElement(CorrectiveAction.PageElements.SEND_BUTTON);
            this.hideElement(CorrectiveAction.PageElements.ACCEPT_BUTTON);
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
   
   static recalculateTotalCost(element)
   {
      // Get the parent table column.
      let parent = element;
      while ((parent = parent.parentElement) && !parent.classList.contains("estimate-table-column"));
      
      if (parent != null)
      {
         // Retrieve compoenents.
         let quantity = parseInt(parent.querySelector(".estimate-quantity-input").value);
         let unitPrice = parseFloat(parent.querySelector(".unit-price-input").value);
         let additionalCharge = parseFloat(parent.querySelector(".additional-charge-input").value);
      
         // Total cost calculation.
         let totalCost = ((quantity * unitPrice) + additionalCharge);
         totalCost = Math.round((totalCost + Number.EPSILON) * 100) / 100;
         totalCost = totalCost.toFixed(2);
         
         // Set new value in input field.
         parent.querySelector(".total-cost-input").value = totalCost;
      }
   }
}