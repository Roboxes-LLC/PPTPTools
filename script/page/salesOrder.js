class SalesOrder
{
   // HTML elements
   static PageElements = {
      // Forms
      "INPUT_FORM":    "input-form",
      // Inputs
      "START_DATE_INPUT": "start-date-input",
      "END_DATE_INPUT": "end-date-input",
      "ACTIVE_ORDERS_INPUT": "active-orders-input",
      "CUSTOMER_ID_INPUT": "customer-id-input",
      "CUSTOMER_PART_NUMBER_INPUT": "customer-part-number-input",
      "PPTP_PART_NUMBER_INPUT": "pptp-part-number-input",
      // Buttons
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
      if (document.getElementById(SalesOrder.PageElements.START_DATE_INPUT) != null)
      {
         document.getElementById(SalesOrder.PageElements.START_DATE_INPUT).addEventListener('change', function() {
            this.onStartDateChanged();
         }.bind(this));
      }

      if (document.getElementById(SalesOrder.PageElements.END_DATE_INPUT) != null)
      {
         document.getElementById(SalesOrder.PageElements.END_DATE_INPUT).addEventListener('change', function() {
            this.onEndDateChanged();
         }.bind(this));
      }
      
      if (document.getElementById(SalesOrder.PageElements.ACTIVE_ORDERS_INPUT) != null)
      {
         document.getElementById(SalesOrder.PageElements.ACTIVE_ORDERS_INPUT).addEventListener('change', function() {
            this.onActiveOrdersChanged();
         }.bind(this));
         
         if (document.getElementById(SalesOrder.PageElements.ACTIVE_ORDERS_INPUT).checked)
         {
            disable(SalesOrder.PageElements.START_DATE_INPUT);
            disable(SalesOrder.PageElements.END_DATE_INPUT);
         }
         else
         {
            enable(SalesOrder.PageElements.START_DATE_INPUT);
            enable(SalesOrder.PageElements.END_DATE_INPUT);
         }
      }

      if (document.getElementById(SalesOrder.PageElements.ADD_BUTTON) != null)
      {
         document.getElementById(SalesOrder.PageElements.ADD_BUTTON).addEventListener('click', function() {
            this.onAddButton();
         }.bind(this));
      }
      
      if (document.getElementById(SalesOrder.PageElements.SAVE_BUTTON) != null)
      {
         document.getElementById(SalesOrder.PageElements.SAVE_BUTTON).addEventListener('click', function() {
            this.onSaveButton();
         }.bind(this));
      }
      
      if (document.getElementById(SalesOrder.PageElements.CANCEL_BUTTON) != null)
      {
         document.getElementById(SalesOrder.PageElements.CANCEL_BUTTON).addEventListener('click', function() {
            this.onCancelButton();
         }.bind(this));
      }      
      
      if (document.getElementById(SalesOrder.PageElements.CUSTOMER_ID_INPUT) != null)
      {
         document.getElementById(SalesOrder.PageElements.CUSTOMER_ID_INPUT).addEventListener('change', function() {
            this.onCustomerIdChanged();
         }.bind(this));
      }
      
      if (document.getElementById(SalesOrder.PageElements.CUSTOMER_PART_NUMBER_INPUT) != null)
      {
         document.getElementById(SalesOrder.PageElements.CUSTOMER_PART_NUMBER_INPUT).addEventListener('change', function() {
            this.onCustomerPartNumberChanged();
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
               tableData = response.salesOrders;
            }
            return (tableData);
         },
         //Define Table Columns
         columns:[
            {                         field:"salesOrderId",            visible:false},
            {title:"Order",           field:"orderNumber",             headerFilter:true},
            {title:"Author",          field:"authorFullName",          headerFilter:true},                   
            {title:"Entered",         field:"formattedDateTime",       headerFilter:true},
            {title:"Customer",        field:"customerName",            headerFilter:true},
            {title:"Part #",          field:"customerPartNumber",      headerFilter:true},
            {title:"PO #",            field:"poNumber",                headerFilter:true},
            {title:"Ordered",         field:"formattedOrderDate",      headerFilter:true},
            {title:"Quantity",        field:"quantity",                headerFilter:false},
            {title:"Unit Price",      field:"formattedUnitPrice",      headerFilter:false},
            {title:"Due",             field:"formattedDueDate",        headerFilter:true},
            {title:"Status",          field:"orderStatus",             headerFilter:true,
               formatter:function(cell, formatterParams, onRendered){
                  return (cell.getRow().getData().orderStatusLabel);
               }
            },
            {title:"",                field:"delete",
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ],
         initialSort:[
            {column:"salesOrderId", dir:"desc"}
         ],
         cellClick:function(e, cell){
            let salesOrderId = parseInt(cell.getRow().getData().salesOrderId);
            
            if (cell.getColumn().getField() == "delete")
            {
               this.onDeleteButton(salesOrderId);
               e.stopPropagation();
            }
         }.bind(this),
         rowClick:function(e, row){
            let salesOrderId = parseInt(row.getData().salesOrderId);
            document.location = `/salesOrder/salesOrder.php?salesOrderId=${salesOrderId}`;
         }.bind(this),
      });
   }
   
   createPartsInventoryTable(tableElementId)
   {
      let url = "/app/page/shipment/";
      let params = new Object();
      params.request = "fetch";
      params.customerNumber = this.getCustomerPartNumber();
      
      let tableElementQuery = "#" + tableElementId;
   
      // Create Tabulator table
      this.scheduledTable = new Tabulator(tableElementQuery, {
         layout:"fitData",
         cellVertAlign:"middle",
         ajaxURL:url,
         ajaxParams:params,
         ajaxResponse:function(url, params, response) {
            let tableData = [];
            if (response.success)
            {
               tableData = response.shipments;
            }
            return (tableData);
         },
         //Define Table Columns
         columns:[
            {                           field:"shipmentId",         visible:false},
            {title:"Ticket",            field:"shipmentTicketCode", hozAlign:"left", headerFilter:true,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">receipt</i>&nbsp" + cell.getRow().getData().shipmentTicketCode);
               },
               formatterPrint:function(cell, formatterParams, onRendered){
                  return (cell.getValue());
               }  
            },
            {title:"Job #",             field:"jobNumber",         headerFilter:true},
            {title:"Quantity",          field:"quantity",          headerFilter:true},
            {title:"Location",          field:"locationLabel",     headerFilter:true},
         ],
         initialSort:[
            {column:"shipmentId", dir:"asc"}
         ],
         cellClick:function(e, cell){
            let shipmentId = parseInt(cell.getRow().getData().shipmentId);
            
            if (cell.getColumn().getField() == "shipmentTicketCode")
            {
               document.location = `/shipment/printShipmentTicket.php?shipmentTicketId=${shipmentId}`;
            }
            else
            {
               document.location = `/shipment/shipment.php?shipmentId=${shipmentId}`;
            }
         }.bind(this),
      });
   }
   
   // **************************************************************************
         
   onStartDateChanged()
   {
      if (!this.validateFilterDates())
      {
         document.getElementById(SalesOrder.PageElements.END_DATE_INPUT).value = 
            document.getElementById(SalesOrder.PageElements.START_DATE_INPUT).value
      }
      
      this.onFilterUpdate();
      
      setSession("salesOrder.startDate", document.getElementById(SalesOrder.PageElements.START_DATE_INPUT).value);
   }
   
   onEndDateChanged()
   {
      if (!this.validateFilterDates())
      {
         document.getElementById(SalesOrder.PageElements.START_DATE_INPUT).value = 
            document.getElementById(SalesOrder.PageElements.END_DATE_INPUT).value
      }

      this.onFilterUpdate();
      
      setSession("salesOrder.endDate", document.getElementById(SalesOrder.PageElements.END_DATE_INPUT).value);
   }
   
   onActiveOrdersChanged()
   {
      var activeQuotes = document.getElementById(SalesOrder.PageElements.ACTIVE_ORDERS_INPUT).checked;
      
      if (activeQuotes)
      {
         disable(SalesOrder.PageElements.START_DATE_INPUT);
         disable(SalesOrder.PageElements.END_DATE_INPUT);
      }
      else
      {
         enable(SalesOrder.PageElements.START_DATE_INPUT);
         enable(SalesOrder.PageElements.END_DATE_INPUT);
      }
      
      this.onFilterUpdate();
      
      setSession("salesOrder.activeOrders", (activeQuotes ? "true" : "false"));
   }
   
   onCustomerIdChanged()
   {
      var customerId = document.getElementById(SalesOrder.PageElements.CUSTOMER_ID_INPUT).value;
      
      var requestUrl = `/app/page/job/?request=fetch_parts&customerId=${customerId}`;
      console.log(requestUrl);

      ajaxRequest(requestUrl, function(response) {
         if (response.success == true)
         {
            this.updateCustomerPartNumberOptions(response.parts);
            document.getElementById(SalesOrder.PageElements.PPTP_PART_NUMBER_INPUT).value = null;
         }
         else
         {
            console.log("Call to fetch items failed.");
         }
      }.bind(this));
   }
   
   onCustomerPartNumberChanged()
   {
      let element = document.getElementById(SalesOrder.PageElements.CUSTOMER_PART_NUMBER_INPUT);
      
      for (let option of element.options)
      {
         if (option.selected)
         {
            let pptpNumber = option.dataset["pptpnumber"];
            
            document.getElementById(SalesOrder.PageElements.PPTP_PART_NUMBER_INPUT).value = pptpNumber;
            break;
         }
      }
   }
   
   validateFilterDates()
   {
      let startDate = document.getElementById(SalesOrder.PageElements.START_DATE_INPUT).value;
      let endDate = document.getElementById(SalesOrder.PageElements.END_DATE_INPUT).value;
      
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
   
   onAddButton()
   {
      document.location = `/salesOrder/salesOrder.php?salesOrderId=${UNKNOWN_SALES_ORDER_ID}`;
   }
   
   onDeleteButton(salesOrderId)
   {
      if (confirm("Are you sure you want to delete this order?"))
      {
         // AJAX call to delete the component.
         let requestUrl = `/app/page/salesOrder/?request=delete_sales_order&salesOrderId=${salesOrderId}`;
         
         ajaxRequest(requestUrl, function(response) {
            if (response.success == true)
            {
               location.href = "/salesOrder/salesOrders.php";
            }
            else
            {
               console.log("Call to delete the order failed.");
               alert(response.error);
            }
         });
      }
   }
   
   onSaveButton()
   {
      if (this.validateForm())
      {
         submitForm(SalesOrder.PageElements.INPUT_FORM, "/app/page/salesOrder", function (response) {
            if (response.success == true)
            {
               location.href = "/salesOrder/salesOrders.php";
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
      document.location = `/salesOrder/salesOrders.php`;
   }
   
   // **************************************************************************
      
   getTableQuery()
   {
      return ("/app/page/salesOrder/");
   }

   getTableQueryParams()
   {
      let params = new Object();
      
      params.request = "fetch";
      
      params.startDate =  document.getElementById(SalesOrder.PageElements.START_DATE_INPUT).value;
      
      params.endDate =  document.getElementById(SalesOrder.PageElements.END_DATE_INPUT).value;
      
      if (document.getElementById(SalesOrder.PageElements.ACTIVE_ORDERS_INPUT).checked)
      {
         params.activeOrders = true;
      }

      return (params);

   }
   
   validateForm()
   {
      return (true);
   }
   
   getCustomerPartNumber()
   {
      return (document.getElementById(SalesOrder.PageElements.CUSTOMER_PART_NUMBER_INPUT).value);
   }
   
   updateCustomerPartNumberOptions(parts)
   {
      var element = document.getElementById(SalesOrder.PageElements.CUSTOMER_PART_NUMBER_INPUT);
      
      while (element.firstChild)
      {
         element.removeChild(element.firstChild);
      }

      for (var part of parts)
      {
         var option = document.createElement('option');
         option.innerHTML = part.customerNumber;
         option.value = part.customerNumber;
         option.dataset["pptpnumber"] = part.pptpNumber;
         element.appendChild(option);
      }
   
      element.value = null;
   }
}