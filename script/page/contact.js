class Contact
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
      if (document.getElementById(Contact.PageElements.ADD_BUTTON) != null)
      {
         document.getElementById(Contact.PageElements.ADD_BUTTON).addEventListener('click', function() {
            this.onAddButton();
         }.bind(this));
      }
      
      if (document.getElementById(Contact.PageElements.SAVE_BUTTON) != null)
      {
         document.getElementById(Contact.PageElements.SAVE_BUTTON).addEventListener('click', function() {
            this.onSaveButton();
         }.bind(this));
      }
      
      if (document.getElementById(Contact.PageElements.CANCEL_BUTTON) != null)
      {
         document.getElementById(Contact.PageElements.CANCEL_BUTTON).addEventListener('click', function() {
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
         // Data
         ajaxURL:url,
         ajaxParams:params,
         ajaxResponse:function(url, params, response) {
            let tableData = [];
            if (response.success)
            {
               tableData = response.contacts;
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
            {                         field:"contactId",    visible:false},
            {title:"First Name",      field:"firstName",    headerFilter:true},
            {title:"Last Name",       field:"lastName",     headerFilter:true},                   
            {title:"Organization",    field:"customerName", headerFilter:true},
            {title:"Phone",           field:"phone",        headerFilter:true},
            {title:"Email",           field:"email",        headerFilter:true},
            {title:"",                field:"delete",       hozAlign:"center", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ],
         initialSort:[
            {column:"firstName", dir:"asc"}
         ]
      });
      
      this.table.on("cellClick", function(e, cell) {
         let contactId = parseInt(cell.getRow().getData().contactId);
         
         if (cell.getColumn().getField() == "delete")
         {
            this.onDeleteButton(contactId);
                     
            e.stopPropagation();
         }
      }.bind(this));
      
      this.table.on("rowClick", function(e, row) {
         var contactId = row.getData().contactId;
         document.location = `/customer/contact.php?contactId=${contactId}`;
      }.bind(this));
   }
   
   // **************************************************************************
   
   onAddButton()
   {
      document.location = `/customer/contact.php?contactId=${UNKNOWN_CONTACT_ID}`;
   }
   
   onDeleteButton(contactId)
   {
      if (confirm("Are you sure you want to delete this contact?"))
      {
         // AJAX call to delete the component.
         let requestUrl = `/app/page/customer/?request=delete_contact&contactId=${contactId}`;
         
         ajaxRequest(requestUrl, function(response) {
            if (response.success == true)
            {
               location.href = "/customer/customers.php";
            }
            else
            {
               console.log("Call to delete the contact failed.");
               alert(response.error);
            }
         });
      }
   }
   
   onSaveButton()
   {
      if (this.validateForm())
      {
         submitForm(Contact.PageElements.INPUT_FORM, "/app/page/customer", function (response) {
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
      params.request = "fetch_contact";

      return (params);
   }
   
   validateForm()
   {
      return (true);
   }
}