class Quote
{
   // HTML elements
   static PageElements = {
      "INPUT_FORM":          "input-form",
      "START_DATE_INPUT":    "start-date-input",
      "END_DATE_INPUT":      "end-date-input",
      "ACTIVE_QUOTES_INPUT": "active-quotes-input",
      "ADD_BUTTON":          "add-button",
      "SAVE_BUTTON":         "save-button",
      "CANCEL_BUTTON":       "cancel-button",
      "CUSTOMER_ID_INPUT":   "customer-id-input",
      "CONTACT_ID_INPUT":    "contact-id-input"
   };

   constructor()
   {      
      this.table = null;
      
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
      
      if (document.getElementById(Quote.PageElements.CUSTOMER_ID_INPUT) != null)
      {
         document.getElementById(Quote.PageElements.CUSTOMER_ID_INPUT).addEventListener('change', function() {
            this.onCustomerChanged();
         }.bind(this));
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
            {title:"Quantity",        field:"quantity",                headerFilter:false},
            {title:"Total Cost",      field:"totalCost",               headerFilter:false, formatter:currencyFormatter},
            {title:"Lead Time",       field:"leadTime",                headerFilter:false,
               formatter:function(cell, formatterParams, onRendered) {
                  return (cell.getValue() + " weeks");
               }
            },
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
}