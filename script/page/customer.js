class Customer
{
   // HTML elements
   static PageElements = {
      "INPUT_FORM":    "input-form",
      "ADD_BUTTON":    "add-button",
      "SAVE_BUTTON":   "save-button",
      "CANCEL_BUTTON": "cancel-button"
   };

   constructor()
   {      
      this.table = null;
      
      this.setup();
   }
   
   setup()
   {
      if (document.getElementById(Customer.PageElements.ADD_BUTTON) != null)
      {
         document.getElementById(Customer.PageElements.ADD_BUTTON).addEventListener('click', function() {
            this.onAddButton();
         }.bind(this));
      }
      
      if (document.getElementById(Customer.PageElements.SAVE_BUTTON) != null)
      {
         document.getElementById(Customer.PageElements.SAVE_BUTTON).addEventListener('click', function() {
            this.onSaveButton();
         }.bind(this));
      }
      
      if (document.getElementById(Customer.PageElements.CANCEL_BUTTON) != null)
      {
         document.getElementById(Customer.PageElements.CANCEL_BUTTON).addEventListener('click', function() {
            this.onCancelButton();
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
            {title:"",                field:"delete",
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ],
         initialSort:[
            {column:"customerName", dir:"asc"}
         ],
         cellClick:function(e, cell){
            let customerId = parseInt(cell.getRow().getData().customerId);
            
            if (cell.getColumn().getField() == "delete")
            {
               this.onDeleteButton(customerId);
               e.stopPropagation();
            }
         }.bind(this),
         rowClick:function(e, row){
            var customerId = row.getData().customerId;
            document.location = `/customer/customer.php?customerId=${customerId}`;
         }.bind(this),
      });
   }
   
   onAddButton()
   {
      document.location = `/customer/customer.php?customerId=${UNKNOWN_CUSTOMER_ID}`;
   }
   
   onDeleteButton(customerId)
   {
      if (confirm("Are you sure you want to delete this customer?"))
      {
         // AJAX call to delete the component.
         let requestUrl = `/app/page/customer/?request=delete_customer&customerId=${customerId}`;
         
         ajaxRequest(requestUrl, function(response) {
            if (response.success == true)
            {
               location.href = "/customer/customers.php";
            }
            else
            {
               console.log("Call to delete the customer failed.");
               alert(response.error);
            }
         });
      }
   }
   
   onSaveButton()
   {
      if (this.validateForm())
      {
         submitForm(Customer.PageElements.INPUT_FORM, "/app/page/customer", function (response) {
            if (response.success == true)
            {
               location.href = "/customer/customers.php";
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
      document.location = `/customer/customers.php`;
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
   
   validateForm()
   {
      return (true);
   }
}