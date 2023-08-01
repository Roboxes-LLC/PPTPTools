class Customer
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
               tableData = response.customers;
            }
            return (tableData);
         },
         //Define Table Columns
         columns:[
            {                         field:"contactId",               visible:false},
            {title:"Customer Name",   field:"customerName",            headerFilter:true},
            {title:"City",            field:"address.city",            headerFilter:true},                   
            {title:"State",           field:"address.stateLabel",      headerFilter:true},
            {title:"Primary Contact", field:"primaryContact.fullName", headerFilter:true},
            {title:"Phone",           field:"primaryContact.phone",    headerFilter:true},
            {title:"Email",           field:"primaryContact.email",    headerFilter:true},
         ],
         initialSort:[
            {column:"customerName", dir:"asc"}
         ],
         rowClick:function(e, row){
            var defectId = row.getData().customerId;
            document.location = `/customer/customer.php?customerId=defectId=${defectId}`;
         }.bind(this),
      });
   }
   
   onNewButton()
   {
      document.location = `/customer/customer.php?customerId=${UNKNOWN_CUSTOMER_ID}`;
   }
   
   // **************************************************************************
      
   getTableQuery()
   {
      return ("/app/page/customer/");
   }

   getTableQueryParams()
   {
      
      let params = new Object();
      params.request = "fetch";

      return (params);
   }
}