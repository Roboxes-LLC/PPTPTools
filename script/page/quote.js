class Quote
{
   // HTML elements
   static PageElements = {
   };

   constructor()
   {      
      this.table = null;
      
      this.setup();
   }
   
   setup()
   {
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

      return (params);
   }
}