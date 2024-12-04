class Quote
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
      "ACTIVE_QUOTES_INPUT":    "active-quotes-input",
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
      "QUOTE_ID_INPUT":         "quote-id-input",
      "CUSTOMER_ID_INPUT":      "customer-id-input",
      "CONTACT_ID_INPUT":       "contact-id-input",
      "SUBMIT_ESTIMATE_INPUT":  "submit-estimate-input",
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
      if (document.getElementById(Quote.PageElements.START_DATE_INPUT) != null)
      {
         document.getElementById(Quote.PageElements.START_DATE_INPUT).addEventListener('change', function() {
            this.onStartDateChanged();
         }.bind(this));
      }

      if (document.getElementById(Quote.PageElements.END_DATE_INPUT) != null)
      {
         document.getElementById(Quote.PageElements.END_DATE_INPUT).addEventListener('change', function() {
            this.onEndDateChanged();
         }.bind(this));
      }
      
      if (document.getElementById(Quote.PageElements.ACTIVE_QUOTES_INPUT) != null)
      {
         document.getElementById(Quote.PageElements.ACTIVE_QUOTES_INPUT).addEventListener('change', function() {
            this.onActiveQuotesChanged();
         }.bind(this));
         
         if (document.getElementById(Quote.PageElements.ACTIVE_QUOTES_INPUT).checked)
         {
            disable(Quote.PageElements.START_DATE_INPUT);
            disable(Quote.PageElements.END_DATE_INPUT);
         }
         else
         {
            enable(Quote.PageElements.START_DATE_INPUT);
            enable(Quote.PageElements.END_DATE_INPUT);
         }
      }
      
      if (document.getElementById(Quote.PageElements.ADD_BUTTON) != null)
      {
         document.getElementById(Quote.PageElements.ADD_BUTTON).addEventListener('click', function() {
            this.onAddButton();
         }.bind(this));
      }
      
      if (document.getElementById(Quote.PageElements.SAVE_BUTTON) != null)
      {
         document.getElementById(Quote.PageElements.SAVE_BUTTON).addEventListener('click', function() {
            this.onSaveButton();
         }.bind(this));
      }
      
      if (document.getElementById(Quote.PageElements.CANCEL_BUTTON) != null)
      {
         document.getElementById(Quote.PageElements.CANCEL_BUTTON).addEventListener('click', function() {
            this.onCancelButton();
         }.bind(this));
      }
      
      if (document.getElementById(Quote.PageElements.UPDATE_BUTTON) != null)
      {
         document.getElementById(Quote.PageElements.UPDATE_BUTTON).addEventListener('click', function() {
            this.onUpdateButton();
         }.bind(this));
      }
      
      if (document.getElementById(Quote.PageElements.ATTACH_BUTTON) != null)
      {
         document.getElementById(Quote.PageElements.ATTACH_BUTTON).addEventListener('click', function() {
            this.onAttachButton();
         }.bind(this));
      }
      
      let deleteButtons = document.getElementsByClassName(Quote.PageElements.DELETE_ATTACH_BUTTON);
      for (let button of deleteButtons)
      {
         let attachmentId = button.dataset.attachmentid;
         
         button.addEventListener('click', function() {
            this.onDeleteAttachmentButton(attachmentId);
         }.bind(this));
      }  
          
      if (document.getElementById(Quote.PageElements.SAVE_ESTIMATE_BUTTON) != null)
      {
         document.getElementById(Quote.PageElements.SAVE_ESTIMATE_BUTTON).addEventListener('click', function() {
            this.onSaveEstimateButton(false);  // Save, but don't submit.
         }.bind(this));
      }
      
      if (document.getElementById(Quote.PageElements.SUBMIT_ESTIMATE_BUTTON) != null)
      {
         document.getElementById(Quote.PageElements.SUBMIT_ESTIMATE_BUTTON).addEventListener('click', function() {
            this.onSaveEstimateButton(true);  // Save and submit.
         }.bind(this));
      }
      
      if (document.getElementById(Quote.PageElements.REVISE_BUTTON) != null)
      {
         document.getElementById(Quote.PageElements.REVISE_BUTTON).addEventListener('click', function() {
            this.onSaveEstimateButton(true);  // Save and re-submit.
         }.bind(this));
      }
            
      if (document.getElementById(Quote.PageElements.APPROVE_BUTTON) != null)
      {
         document.getElementById(Quote.PageElements.APPROVE_BUTTON).addEventListener('click', function() {
            this.onApproveButton();
         }.bind(this));
      }
      
      if (document.getElementById(Quote.PageElements.UNAPPROVE_BUTTON) != null)
      {
         document.getElementById(Quote.PageElements.UNAPPROVE_BUTTON).addEventListener('click', function() {
            this.onUnapproveButton();
         }.bind(this));
      }
      
      if (document.getElementById(Quote.PageElements.PREVIEW_BUTTON) != null)
      {
         document.getElementById(Quote.PageElements.PREVIEW_BUTTON).addEventListener('click', function() {
            this.onPreviewButton();
         }.bind(this));
      }
      
      if (document.getElementById(Quote.PageElements.SAVE_DRAFT_BUTTON) != null)
      {
         document.getElementById(Quote.PageElements.SAVE_DRAFT_BUTTON).addEventListener('click', function() {
            this.onSaveDraftButton();
         }.bind(this));
      }
      
      if (document.getElementById(Quote.PageElements.SEND_BUTTON) != null)
      {
         document.getElementById(Quote.PageElements.SEND_BUTTON).addEventListener('click', function() {
            this.onSendButton();
         }.bind(this));
      }
      
      if (document.getElementById(Quote.PageElements.RESEND_BUTTON) != null)
      {
         document.getElementById(Quote.PageElements.RESEND_BUTTON).addEventListener('click', function() {
            this.onSendButton();
         }.bind(this));
      }
      
      if (document.getElementById(Quote.PageElements.ACCEPT_BUTTON) != null)
      {
         document.getElementById(Quote.PageElements.ACCEPT_BUTTON).addEventListener('click', function() {
            this.onAcceptButton();
         }.bind(this));
      }
      
      if (document.getElementById(Quote.PageElements.REJECT_BUTTON) != null)
      {
         document.getElementById(Quote.PageElements.REJECT_BUTTON).addEventListener('click', function() {
            this.onRejectButton();
         }.bind(this));
      }
      
      if (document.getElementById(Quote.PageElements.COMMENT_BUTTON) != null)
      {
         document.getElementById(Quote.PageElements.COMMENT_BUTTON).addEventListener('click', function() {
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
      
      if (document.getElementById(Quote.PageElements.CUSTOMER_ID_INPUT) != null)
      {
         document.getElementById(Quote.PageElements.CUSTOMER_ID_INPUT).addEventListener('change', function() {
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
            Quote.recalculateTotalCost(event.target);
         });
      }
      
      // Unit price inputs
      inputs = document.getElementsByClassName("unit-price-input");
      for (const element of inputs)
      {
         element.addEventListener('change', function(event) {
            Quote.recalculateTotalCost(event.target);
         });
      }
      
      // Unit price inputs
      inputs = document.getElementsByClassName("additional-charge-input");
      for (const element of inputs)
      {
         element.addEventListener('change', function(event) {
            Quote.recalculateTotalCost(event.target);
         });
      }
      
      // Total cost inputs
      inputs = document.getElementsByClassName("total-cost-input");
      for (const element of inputs)
      {
         Quote.recalculateTotalCost(element);
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
               tableData = response.quotes;
            }
            return (tableData);
         },
         //Define Table Columns
         columns:[
            {                         field:"quoteId",                 visible:false},
            {title:"Quote #",         field:"quoteNumber",             headerFilter:true},
            {title:"Customer",        field:"customerName",            headerFilter:true},
            {title:"Contact",         field:"contactName",             headerFilter:true},
            {title:"Customer Part #", field:"customerPartNumber",      headerFilter:true},
            {title:"PPTP Part #",     field:"pptpPartNumber",          headerFilter:true},
            {title:"Description",     field:"partDescription",         headerFilter:true},
            {title:"Quantity",        field:"quantity",                headerFilter:false},
            {title:"Estimates",       field:"estimateCount",           headerFilter:false},
            {title:"Status",          field:"quoteStatusLabel",        headerFilter:true},
            {title:"",                field:"delete",
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ],
         initialSort:[
            {column:"quoteNumber", dir:"asc"}
         ],
         cellClick:function(e, cell){
            let quoteId = parseInt(cell.getRow().getData().quoteId);
            
            if (cell.getColumn().getField() == "delete")
            {
               this.onDeleteButton(quoteId);
               e.stopPropagation();
            }
         }.bind(this),
         rowClick:function(e, row){
            var quoteId = row.getData().quoteId;
            document.location = `/quote/quote.php?quoteId=${quoteId}`;
         }.bind(this),
      });
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
         document.getElementById(Quote.PageElements.END_DATE_INPUT).value = 
            document.getElementById(Quote.PageElements.START_DATE_INPUT).value
      }
      
      this.onFilterUpdate();
      
      setSession("quote.startDate", document.getElementById(Quote.PageElements.START_DATE_INPUT).value);
   }
   
   onEndDateChanged()
   {
      if (!this.validateFilterDates())
      {
         document.getElementById(Quote.PageElements.START_DATE_INPUT).value = 
            document.getElementById(Quote.PageElements.END_DATE_INPUT).value
      }

      this.onFilterUpdate();
      
      setSession("quote.endDate", document.getElementById(Quote.PageElements.END_DATE_INPUT).value);
   }
   
   onActiveQuotesChanged()
   {
      var activeQuotes = document.getElementById(Quote.PageElements.ACTIVE_QUOTES_INPUT).checked;
      
      if (activeQuotes)
      {
         disable(Quote.PageElements.START_DATE_INPUT);
         disable(Quote.PageElements.END_DATE_INPUT);
      }
      else
      {
         enable(Quote.PageElements.START_DATE_INPUT);
         enable(Quote.PageElements.END_DATE_INPUT);
      }
      
      this.onFilterUpdate();
      
      setSession("quote.activeQuotes", (activeQuotes ? "true" : "false"));
   }
   
   onAddButton()
   {
      document.location = `/quote/newQuote.php`;
   }
   
   onDeleteButton(quoteId)
   {
      if (confirm("Are you sure you want to delete this quote?"))
      {
         // AJAX call to delete the component.
         let requestUrl = `/app/page/quote/?request=delete_quote&quoteId=${quoteId}`;
         
         ajaxRequest(requestUrl, function(response) {
            if (response.success == true)
            {
               location.href = "/quote/quotes.php";
            }
            else
            {
               console.log("Call to delete the quote failed.");
               alert(response.error);
            }
         });
      }
   }
   
   onPreviewButton()
   {
      if (this.validateForm())
      {
         let form = document.getElementById(Quote.PageElements.SEND_FORM);
         
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
         submitForm(Quote.PageElements.INPUT_FORM, "/app/page/quote", function (response) {
            if (response.success == true)
            {
               location.href = `/quote/quote.php?quoteId=${response.quoteId}`;
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
      document.location = `/quote/quotes.php`;
   }
   
   onUpdateButton()
   {
      if (this.validateRequestForm())
      {
         submitForm(Quote.PageElements.REQUEST_FORM, "/app/page/quote", function (response) {
            if (response.success == true)
            {
               location.href = `/quote/quote.php?quoteId=${response.quoteId}`;
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
         submitForm(Quote.PageElements.ATTACHMENTS_FORM, "/app/page/quote", function (response) {
            if (response.success == true)
            {
               location.href = `/quote/quote.php?quoteId=${response.quoteId}`;
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
      document.getElementById(Quote.PageElements.SUBMIT_ESTIMATE_INPUT).value = submitEstimate;
      
      if (this.validateQuoteForm())
      {
         submitForm(Quote.PageElements.QUOTE_FORM, "/app/page/quote", function (response) {
            if (response.success == true)
            {
               if (submitEstimate)
               {
                  location.href = `/quote/quote.php?quoteId=${response.quoteId}`;
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
      document.getElementById(Quote.PageElements.IS_APPROVED_INPUT).value = "true";
      
      submitForm(Quote.PageElements.APPROVE_FORM, "/app/page/quote", function (response) {
         if (response.success == true)
         {
            location.href = `/quote/quote.php?quoteId=${response.quoteId}`;
         }
         else
         {
            alert(response.error);
         }
      })
   }
   
   onUnapproveButton()
   {
      document.getElementById(Quote.PageElements.IS_APPROVED_INPUT).value = "false";
            
      submitForm(Quote.PageElements.APPROVE_FORM, "/app/page/quote", function (response) {
         if (response.success == true)
         {
            location.href = `/quote/quote.php?quoteId=${response.quoteId}`;
         }
         else
         {
            alert(response.error);
         }
      })
   }
   
   onSaveDraftButton()
   {
      let quoteId = document.getElementById(Quote.PageElements.QUOTE_ID_INPUT).value;
      let emailNotes = document.getElementById(Quote.PageElements.EMAIL_NOTES_INPUT).value;
      
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
      
      showModal(Quote.PageElements.PIN_CONFIRM_MODAL, "block");
   }
   
   onPinConfirmed()
   {
      hideModal(Quote.PageElements.PIN_CONFIRM_MODAL);
            
      submitForm(Quote.PageElements.SEND_FORM, "/app/page/quote", function (response) {
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
      document.getElementById(Quote.PageElements.IS_ACCEPTED_INPUT).value = "true";
      
      submitForm(Quote.PageElements.ACCEPT_FORM, "/app/page/quote", function (response) {
         if (response.success == true)
         {
            location.href = `/quote/quote.php?quoteId=${response.quoteId}`;
         }
         else
         {
            alert(response.error);
         }
      })
   }
   
   onRejectButton()
   {
      document.getElementById(Quote.PageElements.IS_ACCEPTED_INPUT).value = "false";
            
      submitForm(Quote.PageElements.ACCEPT_FORM, "/app/page/quote", function (response) {
         if (response.success == true)
         {
            location.href = `/quote/quote.php?quoteId=${response.quoteId}`;
         }
         else
         {
            alert(response.error);
         }
      })
   }
   
   onCommentButton()
   {
      submitForm(Quote.PageElements.HISTORY_FORM, "/app/page/quote", function (response) {
         if (response.success == true)
         {
            location.href = `/quote/quote.php?quoteId=${response.quoteId}`;
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
      let customerId = document.getElementById(Quote.PageElements.CUSTOMER_ID_INPUT).value;
      
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
      return ("/app/page/quote/");
   }

   getTableQueryParams()
   {
      let params = new Object();
      
      params.request = "fetch";
      
      params.startDate =  document.getElementById(Quote.PageElements.START_DATE_INPUT).value;
      
      params.endDate =  document.getElementById(Quote.PageElements.END_DATE_INPUT).value;
      
      if (document.getElementById(Quote.PageElements.ACTIVE_QUOTES_INPUT).checked)
      {
         params.activeQuotes = true;
      }

      return (params);
   }
   
   validateFilterDates()
   {
      let startDate = document.getElementById(Quote.PageElements.START_DATE_INPUT).value;
      let endDate = document.getElementById(Quote.PageElements.END_DATE_INPUT).value;
      
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
      let element = document.getElementById(Quote.PageElements.CONTACT_ID_INPUT);
      
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
            this.hideElement(Quote.PageElements.APPROVE_PANEL);
            this.hideElement(Quote.PageElements.SEND_PANEL);
            this.hideElement(Quote.PageElements.ACCEPT_PANEL);
            
            this.collapsePanel(Quote.PageElements.ATTACHMENTS_PANEL);
            
            this.hideElement(Quote.PageElements.REVISE_BUTTON);
            break;
         }
         
         case QuoteStatus.ESTIMATED:
         case QuoteStatus.REVISED:
         {
            this.hideElement(Quote.PageElements.SEND_PANEL);
            this.hideElement(Quote.PageElements.ACCEPT_PANEL);
            
            this.collapsePanel(Quote.PageElements.ATTACHMENTS_PANEL);
            this.collapsePanel(Quote.PageElements.ESTIMATES_PANEL);
            
            this.hideElement(Quote.PageElements.SAVE_ESTIMATE_BUTTON);
            this.hideElement(Quote.PageElements.SUBMIT_ESTIMATE_BUTTON);
            break;
         }
         
         case QuoteStatus.UNAPPROVED:
         case QuoteStatus.REJECTED:
         {
            this.hideElement(Quote.PageElements.APPROVE_PANEL);
            this.hideElement(Quote.PageElements.SEND_PANEL);
            this.hideElement(Quote.PageElements.ACCEPT_PANEL);
            
            this.collapsePanel(Quote.PageElements.ATTACHMENTS_PANEL);
            
            this.hideElement(Quote.PageElements.SUBMIT_ESTIMATE_BUTTON);
            
            this.accentButton(Quote.PageElements.REVISE_BUTTON);
            break;
         }
         
         case QuoteStatus.APPROVED:
         {
            this.hideElement(Quote.PageElements.ACCEPT_PANEL);
            
            this.collapsePanel(Quote.PageElements.ATTACHMENTS_PANEL);
            this.collapsePanel(Quote.PageElements.ESTIMATES_PANEL);
            this.collapsePanel(Quote.PageElements.APPROVE_PANEL);

            this.hideElement(Quote.PageElements.SAVE_ESTIMATE_BUTTON);
            this.hideElement(Quote.PageElements.SUBMIT_ESTIMATE_BUTTON);
            this.hideElement(Quote.PageElements.APPROVE_BUTTON);
            this.hideElement(Quote.PageElements.RESEND_BUTTON);
            break;
         }
         
         case QuoteStatus.SENT:
         {
            this.hideElement(Quote.PageElements.APPROVE_PANEL);
            
            this.collapsePanel(Quote.PageElements.ATTACHMENTS_PANEL);
            this.collapsePanel(Quote.PageElements.ESTIMATES_PANEL);
            this.collapsePanel(Quote.PageElements.APPROVE_PANEL);
            this.collapsePanel(Quote.PageElements.SEND_PANEL);
                        
            this.hideElement(Quote.PageElements.SAVE_ESTIMATE_BUTTON);
            this.hideElement(Quote.PageElements.SUBMIT_ESTIMATE_BUTTON);
            this.hideElement(Quote.PageElements.APPROVE_BUTTON);
            this.hideElement(Quote.PageElements.SEND_BUTTON);
            break;
         }
         
         case QuoteStatus.ACCEPTED:
         {
            this.collapsePanel(Quote.PageElements.ATTACHMENTS_PANEL);
            this.collapsePanel(Quote.PageElements.ESTIMATES_PANEL);
            this.collapsePanel(Quote.PageElements.APPROVE_PANEL);
            this.collapsePanel(Quote.PageElements.SEND_PANEL);
            this.collapsePanel(Quote.PageElements.ACCEPT_PANEL);
            
            this.hideElement(Quote.PageElements.SAVE_ESTIMATE_BUTTON);
            this.hideElement(Quote.PageElements.SUBMIT_ESTIMATE_BUTTON);
            this.hideElement(Quote.PageElements.APPROVE_BUTTON);
            this.hideElement(Quote.PageElements.SEND_BUTTON);
            this.hideElement(Quote.PageElements.ACCEPT_BUTTON);
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