class Quote
{
   // HTML elements
   static PageElements = {
      "START_DATE_INPUT":    "start-date-input",
      "END_DATE_INPUT":      "end-date-input",
      "ACTIVE_QUOTES_INPUT": "active-quotes-input"
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
         ],
         initialSort:[
            {column:"quoteNumber", dir:"asc"}
         ],
         rowClick:function(e, row){
            var quoteId = row.getData().quoteId;
            document.location = `/quote/quote.php?quoteId=${defectId}`;
         }.bind(this),
      });
   }
   
   onNewButton()
   {
      document.location = `/quote/quote.php?quoteId=${UNKNOWN_QUOTE_ID}`;
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
}