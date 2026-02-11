class Shipment
{
   // HTML elements
   static PageElements = {
      // Forms
      "INPUT_FORM": "input-form",
      // Filters
      // Tables
      "DATA_TABLE":        "data-table",
      "TIME_CARD_TABLE":   "time-card-table",
      "HEAT_TABLE":        "heat-table",
      // Buttons
      "ADD_BUTTON":        "add-button",
      "SAVE_BUTTON":       "save-button",
      "CANCEL_BUTTON":     "cancel-button",
      "DELETE_PACKING_LIST_BUTTON": "delete-packing-list-button",
      // Input fields
      "SHIPMENT_LOCATION_INPUT": "shipment-location-input",
      "START_DATE_INPUT": "start-date-input",
      "END_DATE_INPUT": "end-date-input",
      "SHIPMENT_ID_INPUT": "shipment-id-input",
      "JOB_NUMBER_INPUT": "job-number-input",
      "PPTP_PART_NUMBER_INPUT": "pptp-part-number-input",
      "CUSTOMER_NAME_INPUT": "customer-name-input",
      "CUSTOMER_PART_NUMBER_INPUT": "customer-part-number-input",
      "QUANTITY_INPUT": "quantity-input",
      "PARENT_QUANTITY_INPUT": "parent-quantity-input",
      "CHILD_QUANTITY_INPUT": "child-quantity-input",
   };

   constructor()
   {      
      this.table = null;
      this.timeCardTable = null;
      this.heatTable = null;
      
      this.setup();
      
      if (document.getElementById(Shipment.PageElements.SHIPMENT_LOCATION_INPUT) != null)
      {
         this.onShipmentLocationChanged();
      }
   }
   
   setup()
   {
      if (document.getElementById(Shipment.PageElements.TIME_CARD_TABLE) != null)
      {
         this.createTimeCardTable(Shipment.PageElements.TIME_CARD_TABLE);
      }
      
      if (document.getElementById(Shipment.PageElements.HEAT_TABLE) != null)
      {
         this.createHeatTable(Shipment.PageElements.HEAT_TABLE);
      }
      
      if (document.getElementById(Shipment.PageElements.SHIPMENT_LOCATION_INPUT) != null)
      {
         document.getElementById(Shipment.PageElements.SHIPMENT_LOCATION_INPUT).addEventListener('change', function() {
            this.onShipmentLocationChanged();
         }.bind(this));
      }
      
      if (document.getElementById(Shipment.PageElements.START_DATE_INPUT) != null)
      {
         document.getElementById(Shipment.PageElements.START_DATE_INPUT).addEventListener('change', function() {
            this.onStartDateChanged();
         }.bind(this));
      }
      
      if (document.getElementById(Shipment.PageElements.END_DATE_INPUT) != null)
      {
         document.getElementById(Shipment.PageElements.END_DATE_INPUT).addEventListener('change', function() {
            this.onEndDateChanged();
         }.bind(this));
      }
      
      if (document.getElementById(Shipment.PageElements.ADD_BUTTON) != null)
      {
         document.getElementById(Shipment.PageElements.ADD_BUTTON).addEventListener('click', function() {
            this.onAddButton();
         }.bind(this));
      }
      
      if (document.getElementById(Shipment.PageElements.SAVE_BUTTON) != null)
      {
         document.getElementById(Shipment.PageElements.SAVE_BUTTON).addEventListener('click', function() {
            this.onSaveButton();
         }.bind(this));
      }
      
      if (document.getElementById(Shipment.PageElements.CANCEL_BUTTON) != null)
      {
         document.getElementById(Shipment.PageElements.CANCEL_BUTTON).addEventListener('click', function() {
            history.back();
         }.bind(this));
      }
      
      if (document.getElementById(Shipment.PageElements.JOB_NUMBER_INPUT) != null)
      {
         document.getElementById(Shipment.PageElements.JOB_NUMBER_INPUT).addEventListener('change', function() {
            this.onJobNumberChanged();
         }.bind(this));
      }
      
      if (document.getElementById(Shipment.PageElements.CHILD_QUANTITY_INPUT) != null)
      {
         document.getElementById(Shipment.PageElements.CHILD_QUANTITY_INPUT).addEventListener('input', function() {
            this.onChildQuantityChanged();
         }.bind(this));
      }
      
      let deleteButtons = document.getElementsByClassName(Shipment.PageElements.DELETE_PACKING_LIST_BUTTON);
      for (let button of deleteButtons)
      {
         button.addEventListener('click', function(event) {
            this.onDeletePackingListButton(event.target);
         }.bind(this));
      } 
   }      
   
   createTable(tableElementId)
   {
      let url = "/app/page/shipment/";
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
               tableData = response.shipments;
            }
            return (tableData);
         },
         // Layout
         layout:"fitData",
         columnDefaults:{
            hozAlign:"left", 
            vertAlign:"middle"
         },
         persistence:false,
         // Columns
         columns:[
            {                           field:"shipmentId",           visible:false},
            {title:"Ticket",            field:"shipmentTicketCode",   headerFilter:true,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">receipt</i>&nbsp" + cell.getRow().getData().shipmentTicketCode);
               },
               formatterPrint:function(cell, formatterParams, onRendered){
                  return (cell.getValue());
               }  
            },
            {title:"Created",           field:"dateTime",            headerFilter:true,
               formatter:function(cell, formatterParams, onRendered) {
                  return (cell.getRow().getData().formattedDateTime);
               }
            },
            {title:"Customer",          field:"part.customerName",   headerFilter:true},
            {title:"Part #",            field:"part.customerNumber", headerFilter:true},
            {title:"Job #",             field:"jobNumber",           headerFilter:true},
            {title:"Quantity",          field:"quantity",            headerFilter:true, bottomCalc:"sum"},
            {title:"Inspection",        field:"inspectionStatus",    headerFilter:true, hozAlign:"center",
               formatter:function(cell, formatterParams, onRendered) {
                  let cellValue = "";
                  let label = cell.getRow().getData().inspectionStatusLabel;
                  if (label != "")
                  {
                     let cssClass = cell.getRow().getData().inspectionStatusClass;
                     cellValue = `<div class="inspection-status ${cssClass}">${label}</div>`;
                  }
                  return (cellValue);
               }
            }, 
            {title:"Location",          field:"locationLabel",       headerFilter:true},
            {title:"Vendor<br>Packing List", field:"vendorPackingList",         hozAlign:"left",
               formatter:function(cell, formatterParams, onRendered){
                  let cellValue = "";
                  
                  let filename = cell.getValue();
                  let url = cell.getRow().getData().vendorPackingListUrl;
                  
                  if (filename != null)
                  {
                     cellValue = `<a href="${url}" target="_blank">${filename}</a>`;
                  }
                  
                  return (cellValue);
                }
            },
            {title:"Customer<br>Packing List", field:"customerPackingList",         hozAlign:"left",
               formatter:function(cell, formatterParams, onRendered){
                  let cellValue = "";
                  
                  let filename = cell.getValue();
                  let url = cell.getRow().getData().customerPackingListUrl;
                  
                  if (filename != null)
                  {
                     cellValue = `<a href="${url}" target="_blank">${filename}</a>`;
                  }
                  
                  return (cellValue);
                }
            },
            {title:"Vendor<br>Shipped",    field:"dateTime",            headerFilter:true,
               formatter:function(cell, formatterParams, onRendered) {
                  return (cell.getRow().getData().formattedVendorShippedDate);
               }
            },
            {title:"Customer<br>Shipped",  field:"dateTime",            headerFilter:true,
               formatter:function(cell, formatterParams, onRendered) {
                  return (cell.getRow().getData().formattedCustomerShippedDate);
               }
            },
            {title:"",                  field:"split", tooltip:"Split",   hozAlign:"center", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">call_split</i>");
               }
            },
            {title:"",                  field:"delete", tooltip:"Delete", hozAlign:"center", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ],
         initialSort:[
            {column:"dateTime", dir:"desc"}
         ]
      });
      
      this.table.on("cellClick", function(e, cell) {
         let shipmentId = parseInt(cell.getRow().getData().shipmentId);
         
         if (cell.getColumn().getField() == "shipmentTicketCode")
         {
            document.location = `/shipment/printShipmentTicket.php?shipmentTicketId=${shipmentId}`;
         }
         else if (cell.getColumn().getField() == "inspectionStatus")
         {
            let inspectionId = parseInt(cell.getRow().getData().inspectionId);
            if (inspectionId != 0)
            {
               document.location = `/inspection/viewInspection.php?inspectionId=${inspectionId}`;
            }
            e.stopPropagation();
         }
         else if ((cell.getColumn().getField() == "vendorPackingList") ||
                  (cell.getColumn().getField() == "customerPackingList"))
         {
            e.stopPropagation();
         }
         else if (cell.getColumn().getField() == "split")
         {
            let shipmentId = parseInt(cell.getRow().getData().shipmentId);
            if (shipmentId != 0)
            {
               document.location = `/shipment/splitShipment.php?shipmentId=${shipmentId}`;
            }
            e.stopPropagation();
         }
         else if (cell.getColumn().getField() == "delete")
         {
            this.onDeleteButton(shipmentId);
            e.stopPropagation();
         }
         else
         {
            document.location = `/shipment/shipment.php?shipmentId=${shipmentId}`;
         }
      }.bind(this));
   }
   
   createTimeCardTable(tableElementId)
   {
      let url = "/app/page/shipment/";
      let params = new Object();
      params.request = "fetch_time_cards";
      params.shipmentId = this.getShipmentId();
      
      let tableElementQuery = "#" + tableElementId;
   
      // Create Tabulator table
      this.timeCardTable = new Tabulator(tableElementQuery, {
         layout:"fitData",
         cellVertAlign:"middle",
         ajaxURL:url,
         ajaxParams:params,
         ajaxResponse:function(url, params, response) {
            let tableData = [];
            if (response.success)
            {
               tableData = response.timeCards;
            }
            return (tableData);
         },
         //Define Table Columns
         columns:[
            {                           field:"timeCardId", visible:false},
            {title:"Ticket",            field:"panTicketCode", hozAlign:"left", headerFilter:true,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">receipt</i>&nbsp" + cell.getRow().getData().panTicketCode);
               },
               formatterPrint:function(cell, formatterParams, onRendered){
                  return (cell.getValue());
               }  
            },
            {title:"Mfg Date",          field:"formattedMfgDate",  headerFilter:true},
            {title:"Job #",             field:"jobNumber",         headerFilter:true},
            {title:"WC #",              field:"wcLabel",           headerFilter:true},
            {title:"Operator",          field:"operator",          headerFilter:true},
            {title:"Part Count",        field:"partCount", headerFilter:true},
         ],
         initialSort:[
            {column:"formattedMfgDate", dir:"asc"}
         ],
         cellClick:function(e, cell){
            let timeCardId = parseInt(cell.getRow().getData().timeCardId);
            
            if (cell.getColumn().getField() == "panTicketCode")
            {
               document.location = `/panTicket/viewPanTicket.php?panTicketId=${timeCardId}`;
            }
            else
            {
               document.location = `/timecard/viewTimeCard.php?timeCardId=${timeCardId}`;
            }
         }.bind(this),
      });
   }
   
   createHeatTable(tableElementId)
   {
      let url = "/app/page/shipment/";
      let params = new Object();
      params.request = "fetch_heats";
      params.shipmentId = this.getShipmentId();
      
      let tableElementQuery = "#" + tableElementId;
   
      // Create Tabulator table
      this.timeCardTable = new Tabulator(tableElementQuery, {
         layout:"fitData",
         cellVertAlign:"middle",
         ajaxURL:url,
         ajaxParams:params,
         ajaxResponse:function(url, params, response) {
            let tableData = [];
            if (response.success)
            {
               tableData = response.heats;
            }
            return (tableData);
         },
         //Define Table Columns
         columns:[
            {                     field:"materialEntryId", visible:false},
            /*
            {title:"Ticket",      field:"materialTicketCode",    hozAlign:"left", headerFilter:true,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">receipt</i>&nbsp" + cell.getRow().getData().materialTicketCode);
               },
               formatterPrint:function(cell, formatterParams, onRendered){
                  return (cell.getValue());
               }  
            },
            */
            {title:"PPTP Heat",   field:"internalHeatNumber",      hozAlign:"left", headerFilter:true, visible:true},                   
            {title:"Vendor Heat", field:"vendorHeatNumber",                         hozAlign:"left", headerFilter:true, visible:true},
            {title:"Material",    field:"materialInfo.partNumber", hozAlign:"left", headerFilter:true, visible:true},
            {title:"Vendor",      field:"vendorName",                               hozAlign:"left", headerFilter:true, visible:true},
         ],
         initialSort:[
            {column:"internalHeatNumber", dir:"asc"}
         ],
         cellClick:function(e, cell){
            var entryId = parseInt(cell.getRow().getData().materialEntryId);            
                     
            if (cell.getColumn().getField() == "materialTicketCode")
            {
               document.location = `printMaterialTicket.php?materialTicketId=${entryId}`;
            }

         }.bind(this),
      });      
   }
   
   // **************************************************************************
   
   onStartDateChanged()
   {
      if (!this.validateFilterDates())
      {
         document.getElementById(Shipment.PageElements.END_DATE_INPUT).value = 
            document.getElementById(Shipment.PageElements.START_DATE_INPUT).value
      }
      
      this.onFilterUpdate();
      
      setSession("shipment.startDate", document.getElementById(Shipment.PageElements.START_DATE_INPUT).value);
   }
   
   onEndDateChanged()
   {
      if (!this.validateFilterDates())
      {
         document.getElementById(Shipment.PageElements.START_DATE_INPUT).value = 
            document.getElementById(Shipment.PageElements.END_DATE_INPUT).value
      }

      this.onFilterUpdate();
      
      setSession("shipment.endDate", document.getElementById(Shipment.PageElements.END_DATE_INPUT).value);
   }
   
   onShipmentLocationChanged()
   {
      let shipmentLocation = parseInt(document.getElementById(Shipment.PageElements.SHIPMENT_LOCATION_INPUT).value);
      
      if (shipmentLocation != ShipmentLocation.CUSTOMER)
      {
         disable(Shipment.PageElements.START_DATE_INPUT);
         disable(Shipment.PageElements.END_DATE_INPUT);
      }
      else
      {
         enable(Shipment.PageElements.START_DATE_INPUT);
         enable(Shipment.PageElements.END_DATE_INPUT);
      }
      
      this.onFilterUpdate();
      
      setSession("shipment.shipmentLocation", shipmentLocation);
   }
            
   onAddButton(jobId)
   {
      document.location = `/shipment/shipment.php?shipmentId=${UNKNOWN_SHIPMENT_ID}`;
   }
   
   onDeleteButton(shipmentId)
   {
      if (confirm("Are you sure you want to remove shipment?"))
      {
         // AJAX call to delete the component.
         let requestUrl = `/app/page/shipment/?request=delete_shipment&shipmentId=${shipmentId}`;
         
         ajaxRequest(requestUrl, function(response) {
            if (response.success == true)
            {
               location.reload();
            }
            else
            {
               alert(response.error);
            }
         }.bind(this));
      }
   }
   
   onSaveButton()
   {
      let form = document.getElementById(Shipment.PageElements.INPUT_FORM);
      
      if (form.reportValidity() == true)
      {
         submitForm(Shipment.PageElements.INPUT_FORM, "/app/page/shipment", function (response) {
            if (response.success == true)
            {
               location.href = "/shipment/shipments.php";
            }
            else
            {
               alert(response.error);
            }
         })
      }
      else
      {
         showInvalid(Shipment.PageElements.INPUT_FORM);
      }
   }
   
   onJobNumberChanged()
   {
      let jobNumber = document.getElementById(Shipment.PageElements.JOB_NUMBER_INPUT).value;
      
      if ((jobNumber != "") && (jobNumber != null))
      {
         let pptpPartNumber = jobNumber.substring(0, jobNumber.indexOf("-"));
      
         let requestUrl = `/app/page/part/?request=fetch&partNumber=${pptpPartNumber}`;
         
         ajaxRequest(requestUrl, function(response) {
            if (response.success == true)
            {
               this.updatePartNumber(response.part);
            }
            else
            {
               console.log("Call to fetch items failed.");
            }
         }.bind(this));
      }
   }
   
   onChildQuantityChanged()
   {
      let quantity = parseInt(get(Shipment.PageElements.QUANTITY_INPUT));
      let childQuantity = parseInt(get(Shipment.PageElements.CHILD_QUANTITY_INPUT));
      
      if (childQuantity > quantity)
      {
         childQuantity = quantity;
      }
      
      set(Shipment.PageElements.CHILD_QUANTITY_INPUT, childQuantity);
      set(Shipment.PageElements.PARENT_QUANTITY_INPUT, (quantity - childQuantity));
   }
   
   onDeletePackingListButton(button)
   {
      let packingList = button.dataset.packinglist;
      console.log(packingList);

      let inputElementId = `${packingList}-input`;
      let valueElementId = `${packingList}-value`;

      hide(valueElementId);
      show(inputElementId, "flex");
   }
      
   // **************************************************************************
   
   updatePartNumber(part)
   {
      if (part != null)
      {
         document.getElementById(Shipment.PageElements.PPTP_PART_NUMBER_INPUT).value = part.pptpNumber;
         document.getElementById(Shipment.PageElements.CUSTOMER_PART_NUMBER_INPUT).value = part.customerNumber;
         document.getElementById(Shipment.PageElements.CUSTOMER_NAME_INPUT).value = part.customerName;
      }
      else
      {
         document.getElementById(Shipment.PageElements.PPTP_PART_NUMBER_INPUT).value = null;
         document.getElementById(Shipment.PageElements.CUSTOMER_PART_NUMBER_INPUT).value = null;
         document.getElementById(Shipment.PageElements.CUSTOMER_NAME_INPUT).value = null;
      }
   }
   
   getShipmentId()
   {
      return (parseInt(document.getElementById(Shipment.PageElements.SHIPMENT_ID_INPUT).value));
   }
   
   getTableQueryParams()
   {
      let params = new Object();
      
      params.request = "fetch";
      
      params.shipmentLocation = document.getElementById(Shipment.PageElements.SHIPMENT_LOCATION_INPUT).value;
      
      params.startDate =  document.getElementById(Shipment.PageElements.START_DATE_INPUT).value;
      
      params.endDate =  document.getElementById(Shipment.PageElements.END_DATE_INPUT).value;

      return (params);
   }
   
   validateFilterDates()
   {
      let startDate = document.getElementById(Shipment.PageElements.START_DATE_INPUT).value;
      let endDate = document.getElementById(Shipment.PageElements.END_DATE_INPUT).value;
      
      return (new Date(endDate) >= new Date(startDate))
   }
   
   onFilterUpdate()
   {
      if (document.readyState === "complete")
      {
         let url = "/app/page/shipment/";
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
   
   // **************************************************************************
}