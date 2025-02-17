class SalesOrder
{
   // HTML elements
   static PageElements = {
      // Forms
      "INPUT_FORM":    "input-form",
      // Inputs
      "DATE_TYPE_INPUT": "date-type-input",
      "START_DATE_INPUT": "start-date-input",
      "END_DATE_INPUT": "end-date-input",
      "ACTIVE_ORDERS_INPUT": "active-orders-input",
      "CUSTOMER_NAME_INPUT": "customer-name-input",
      "CUSTOMER_PART_NUMBER_INPUT": "customer-part-number-input",
      "PPTP_PART_NUMBER_INPUT": "pptp-part-number-input",
      "UNIT_PRICE_INPUT": "unit-price-input",
      "QUANTITY_INPUT": "quantity-input",
      "TOTAL_INPUT": "total-input",
      // Buttons
      "ADD_BUTTON":    "add-button",
      "SAVE_BUTTON":   "save-button",
      "CANCEL_BUTTON": "cancel-button"
   };
   
   static NOTES_LENGTH_MAX = 16;

   constructor()
   {      
      this.table = null;
      
      this.setup();
      
      if (document.getElementById(SalesOrder.PageElements.UNIT_PRICE_INPUT) != null)
      {
         this.updateTotal();
      }
   }
   
   setup()
   {
      if (document.getElementById(SalesOrder.PageElements.DATE_TYPE_INPUT) != null)
      {
         document.getElementById(SalesOrder.PageElements.DATE_TYPE_INPUT).addEventListener('change', function() {
            this.onDateTypeChanged();
         }.bind(this));
      }
      
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
      
      if (document.getElementById(SalesOrder.PageElements.PPTP_PART_NUMBER_INPUT) != null)
      {
         document.getElementById(SalesOrder.PageElements.PPTP_PART_NUMBER_INPUT).addEventListener('change', function() {
            this.onPptpPartNumberChanged();
         }.bind(this));
      }
      
      if (document.getElementById(SalesOrder.PageElements.UNIT_PRICE_INPUT) != null)
      {
         document.getElementById(SalesOrder.PageElements.UNIT_PRICE_INPUT).addEventListener('input', function() {
            this.updateTotal();
         }.bind(this));
      }
      
      if (document.getElementById(SalesOrder.PageElements.QUANTITY_INPUT) != null)
      {
         document.getElementById(SalesOrder.PageElements.QUANTITY_INPUT).addEventListener('input', function() {
            this.updateTotal();
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
               tableData = response.salesOrders;
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
            {                         field:"salesOrderId",            visible:false},
            {title:"Order",           field:"orderNumber",             headerFilter:true},
            {title:"Author",          field:"authorFullName",          headerFilter:true},                   
            {title:"Entered",         field:"dateTime",                headerFilter:true,
               formatter:function(cell, formatterParams, onRendered) {
                  return (cell.getRow().getData().formattedDateTime);
               }
            },
            {title:"Customer",        field:"customerName",            headerFilter:true},
            {title:"Part #",          field:"customerPartNumber",      headerFilter:true},
            {title:"PO #",            field:"poNumber",                headerFilter:true},
            {title:"Ordered",         field:"formattedOrderDate",      headerFilter:true},
            {title:"Quantity",        field:"quantity",                headerFilter:false},
            {title:"Unit Price",      field:"formattedUnitPrice",      headerFilter:false,
               formatter:function(cell, formatterParams, onRendered) {
                  let cellValue = cell.getValue();
                  
                  return ((cellValue == null) ? "---" : cellValue);
               }
            },
            {title:"Total",           field:"formattedTotal",          headerFilter:false,
               formatter:function(cell, formatterParams, onRendered) {
                  let cellValue = cell.getValue();
                  
                  return ((cellValue == null) ? "---" : cellValue);
               }
            },            
            {title:"Due",             field:"dueDate",                 headerFilter:true,
               formatter:function(cell, formatterParams, onRendered) {
                  return (cell.getRow().getData().formattedDueDate);
               }
            },
            {title:"Status",          field:"orderStatus",             headerFilter:true,
               formatter:function(cell, formatterParams, onRendered){
                  return (cell.getRow().getData().orderStatusLabel);
               }
            },
            {title:"Packing List",   field:"packingList", hozAlign:"left",
               formatter:function(cell, formatterParams, onRendered){
                  let cellValue = "";
                  
                  let filename = cell.getValue();
                  let url = cell.getRow().getData().packingListUrl;
                  
                  if (filename != null)
                  {
                     var truncatedFilename = (filename.length > 20) ? filename.substr(0, 20) + "..." : filename; 
                     cellValue = `<a href="${url}" target="_blank">${truncatedFilename}</a>`;
                  }
                  
                  return (cellValue);
                }
            },
            {title:"Comments",        field:"comments",  tooltip:true,
               formatter:function(cell, formatterParams, onRendered){
                  let comments = cell.getValue();
                  
                  let abridgedComments = comments;
                  if (abridgedComments != null)
                  {
                     abridgedComments = 
                        (abridgedComments.length > SalesOrder.NOTES_LENGTH_MAX) ? 
                           abridgedComments.substring(0, (SalesOrder.NOTES_LENGTH_MAX - 3)) + "..." :
                           abridgedComments;
                  } 
                        
                  return (abridgedComments);
               }
            },
            {title:"",                field:"delete",                  hozAlign:"center", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ],
         initialSort:[
            {column:"dueDate", dir:"asc"}
         ]
      });
      
      this.table.on("cellClick", function(e, cell) {
         let salesOrderId = parseInt(cell.getRow().getData().salesOrderId);
         
         if (cell.getColumn().getField() == "packingList")
         {
            e.stopPropagation();
         }
         else if (cell.getColumn().getField() == "delete")
         {
            this.onDeleteButton(salesOrderId);
            e.stopPropagation();
         }
         else
         {
            document.location = `/salesOrder/salesOrder.php?salesOrderId=${salesOrderId}`;
         }
      }.bind(this));
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
    
   onDateTypeChanged()
   {
      this.onFilterUpdate();
      
      setSession("salesOrder.dateType", document.getElementById(SalesOrder.PageElements.DATE_TYPE_INPUT).value);
   } 
         
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
         disable(SalesOrder.PageElements.DATE_TYPE_INPUT);
         disable(SalesOrder.PageElements.START_DATE_INPUT);
         disable(SalesOrder.PageElements.END_DATE_INPUT);
      }
      else
      {
         enable(SalesOrder.PageElements.DATE_TYPE_INPUT);
         enable(SalesOrder.PageElements.START_DATE_INPUT);
         enable(SalesOrder.PageElements.END_DATE_INPUT);
      }
      
      this.onFilterUpdate();
      
      setSession("salesOrder.activeOrders", (activeQuotes ? "true" : "false"));
   }
   
   onPptpPartNumberChanged()
   {
      let pptpPartNumber = document.getElementById(SalesOrder.PageElements.PPTP_PART_NUMBER_INPUT).value;
      
      let requestUrl = `/app/page/part/?request=fetch&partNumber=${pptpPartNumber}`;
      
      ajaxRequest(requestUrl, function(response) {
         if (response.success == true)
         {
            this.updateCustomerPartNumber(response.part);
         }
         else
         {
            console.log("Call to fetch items failed.");
         }
      }.bind(this));
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
      let form = document.getElementById(SalesOrder.PageElements.INPUT_FORM);
      
      if (form.reportValidity() == true)
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
      else
      {
         showInvalid(SalesOrder.PageElements.INPUT_FORM);
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
      
      params.dateType = document.getElementById(SalesOrder.PageElements.DATE_TYPE_INPUT).value;
      
      params.startDate = document.getElementById(SalesOrder.PageElements.START_DATE_INPUT).value;
      
      params.endDate = document.getElementById(SalesOrder.PageElements.END_DATE_INPUT).value;
      
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
   
   updateCustomerPartNumber(part)
   {
      if (part != null)
      {
         document.getElementById(SalesOrder.PageElements.CUSTOMER_PART_NUMBER_INPUT).value = part.customerNumber;
         document.getElementById(SalesOrder.PageElements.CUSTOMER_NAME_INPUT).value = part.customerName;
         document.getElementById(SalesOrder.PageElements.UNIT_PRICE_INPUT).value = part.unitPrice.toFixed(4);
      }
      else
      {
         document.getElementById(SalesOrder.PageElements.CUSTOMER_PART_NUMBER_INPUT).value = null;
         document.getElementById(SalesOrder.PageElements.CUSTOMER_NAME_INPUT).value = null;
         document.getElementById(SalesOrder.PageElements.UNIT_PRICE_INPUT).value = null;
      }
      
      this.updateTotal();
   }
   
   updateTotal()
   {
      let unitPrice = parseFloat(document.getElementById(SalesOrder.PageElements.UNIT_PRICE_INPUT).value);
      let quantity = parseInt(document.getElementById(SalesOrder.PageElements.QUANTITY_INPUT).value);
      let total = 0.0;
      if ((unitPrice != NaN) && (quantity != NaN))
      {
         total = (unitPrice * quantity).toFixed(2);
      }
      
      document.getElementById(SalesOrder.PageElements.TOTAL_INPUT).value = total;
   }
}