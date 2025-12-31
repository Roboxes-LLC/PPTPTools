class ProspiraDoc
{
   // HTML elements
   static PageElements = {
      // Forms
      "INPUT_FORM": "input-form",
      // Filters
      // Tables
      "DATA_TABLE":        "data-table",
      // Buttons
      "ADD_BUTTON":        "add-button",
      "SAVE_BUTTON":       "save-button",
      "CANCEL_BUTTON":     "cancel-button",
      // Input fields
      "SHIPMENT_LOCATION_INPUT": "shipment-location-input",
      "START_DATE_INPUT": "start-date-input",
      "END_DATE_INPUT": "end-date-input",
      "DOC_ID_INPUT": "doc-id-input",
   };

   constructor()
   {      
      this.table = null;
      
      this.setup();
   }
   
   setup()
   {
      if (document.getElementById(ProspiraDoc.PageElements.DATA_TABLE) != null)
      {
         this.createTable(ProspiraDoc.PageElements.DATA_TABLE);
      }
      
      if (document.getElementById(ProspiraDoc.PageElements.SHIPMENT_LOCATION_INPUT) != null)
      {
         document.getElementById(ProspiraDoc.PageElements.SHIPMENT_LOCATION_INPUT).addEventListener('change', function() {
            this.onShipmentLocationChanged();
         }.bind(this));
      }
      
      if (document.getElementById(ProspiraDoc.PageElements.START_DATE_INPUT) != null)
      {
         document.getElementById(ProspiraDoc.PageElements.START_DATE_INPUT).addEventListener('change', function() {
            this.onStartDateChanged();
         }.bind(this));
      }
      
      if (document.getElementById(ProspiraDoc.PageElements.END_DATE_INPUT) != null)
      {
         document.getElementById(ProspiraDoc.PageElements.END_DATE_INPUT).addEventListener('change', function() {
            this.onEndDateChanged();
         }.bind(this));
      }
      
      if (document.getElementById(ProspiraDoc.PageElements.ADD_BUTTON) != null)
      {
         document.getElementById(ProspiraDoc.PageElements.ADD_BUTTON).addEventListener('click', function() {
            this.onAddButton();
         }.bind(this));
      }
      
      if (document.getElementById(ProspiraDoc.PageElements.SAVE_BUTTON) != null)
      {
         document.getElementById(ProspiraDoc.PageElements.SAVE_BUTTON).addEventListener('click', function() {
            this.onSaveButton();
         }.bind(this));
      }
      
      if (document.getElementById(ProspiraDoc.PageElements.CANCEL_BUTTON) != null)
      {
         document.getElementById(ProspiraDoc.PageElements.CANCEL_BUTTON).addEventListener('click', function() {
            history.back();
         }.bind(this));
      } 
   }      
   
   createTable(tableElementId)
   {
      let url = "/app/page/prospiraDoc/";
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
               tableData = response.prospiraDocs;
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
            {                           field:"docId",           visible:false},
            {title:"Shipment",          field:"shipment.shipmentTicketCode",   headerFilter:true,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">receipt</i>&nbsp" + cell.getRow().getData().shipment.shipmentTicketCode);
               },
               formatterPrint:function(cell, formatterParams, onRendered){
                  return (cell.getValue());
               }  
            },
            {title:"Customer",          field:"shipment.part.customerName",   headerFilter:true},
            {title:"Part #",            field:"shipment.part.customerNumber", headerFilter:true},
            {title:"Job #",             field:"shipment.jobNumber",           headerFilter:true},
            {title:"Date",              field:"shipment.formattedDate",       headerFilter:true},
            {title:"Time",              field:"shipment.formattedTime",       headerFilter:true},
            {title:"Quantity",          field:"shipment.quantity",            headerFilter:true},
            {title:"Clock #",           field:"clockNumber",         headerFilter:true},
            {title:"Lot #",             field:"lotNumber",           headerFilter:true},
            {title:"Serial #",          field:"serialNumber",        headerFilter:true},
            {title:"",                  field:"printLabels",         hozAlign:"center", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  let cellValue =`<button class=\"small-button accent-button\" style=\"width:75px;\">Print Labels</button>`;
                  return (cellValue);
               }
            },
            {title:"",                  field:"printSheet",          hozAlign:"center", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  let cellValue =`<button class=\"small-button accent-button\" style=\"width:75px;\">Print Sheet</button>`;
                  return (cellValue);
               }
            },
            {title:"",                  field:"delete", tooltip:"Delete", hozAlign:"center", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ],
         initialSort:[
            {column:"docId", dir:"desc"}
         ]
      });
      
      this.table.on("cellClick", function(e, cell) {
         let docId = parseInt(cell.getRow().getData().docId);
         let shipmentId = parseInt(cell.getRow().getData().shipment.shipmentId);
         
         if (cell.getColumn().getField() == "shipment.shipmentTicketCode")
         {
            document.location = `/shipment/printShipmentTicket.php?shipmentTicketId=${shipmentId}`;
         }
         else if (cell.getColumn().getField() == "printLabels")
         {
            document.location = `/prospiraDoc/printProspiraLabel.php?docId=${docId}`;
         }
         else if (cell.getColumn().getField() == "printSheet")
         {
            let docId = parseInt(cell.getRow().getData().docId);
            this.printSheet(docId);
         }
         else if (cell.getColumn().getField() == "delete")
         {
            this.onDeleteButton(shipmentId);
            e.stopPropagation();
         }
         else
         {
            document.location = `/prospiraDoc/prospiraDoc.php?docId=${docId}`;
         }
      }.bind(this));
   }
   
   // **************************************************************************
   
   onStartDateChanged()
   {
      if (!this.validateFilterDates())
      {
         document.getElementById(ProspiraDoc.PageElements.END_DATE_INPUT).value = 
            document.getElementById(ProspiraDoc.PageElements.START_DATE_INPUT).value
      }
      
      this.onFilterUpdate();
      
      setSession("prospiraDoc.startDate", document.getElementById(ProspiraDoc.PageElements.START_DATE_INPUT).value);
   }
   
   onEndDateChanged()
   {
      if (!this.validateFilterDates())
      {
         document.getElementById(ProspiraDoc.PageElements.START_DATE_INPUT).value = 
            document.getElementById(ProspiraDoc.PageElements.END_DATE_INPUT).value
      }

      this.onFilterUpdate();
      
      setSession("prospiraDoc.endDate", document.getElementById(ProspiraDoc.PageElements.END_DATE_INPUT).value);
   }
   
   onShipmentLocationChanged()
   {
      let shipmentLocation = parseInt(document.getElementById(ProspiraDoc.PageElements.SHIPMENT_LOCATION_INPUT).value);
      
      if (shipmentLocation != ShipmentLocation.CUSTOMER)
      {
         disable(ProspiraDoc.PageElements.START_DATE_INPUT);
         disable(ProspiraDoc.PageElements.END_DATE_INPUT);
      }
      else
      {
         enable(ProspiraDoc.PageElements.START_DATE_INPUT);
         enable(ProspiraDoc.PageElements.END_DATE_INPUT);
      }
      
      this.onFilterUpdate();
      
      setSession("prospiraDoc.shipmentLocation", shipmentLocation);
   }
            
   onAddButton(jobId)
   {
      document.location = `/prospiraDoc/prospiraDoc.php?docId=${UNKNOWN_DOC_ID}`;
   }
   
   onDeleteButton(docId)
   {
      if (confirm("Are you sure you want to delete this documentation?"))
      {
         // AJAX call to delete the component.
         let requestUrl = `/app/page/prospiraDoc/?request=delete_doc&docId=${docId}`;
         
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
      let form = document.getElementById(ProspiraDoc.PageElements.INPUT_FORM);
      
      if (form.reportValidity() == true)
      {
         submitForm(ProspiraDoc.PageElements.INPUT_FORM, "/app/page/prospiraDoc", function (response) {
            if (response.success == true)
            {
               location.href = "/prospiraDoc/prospiraDocs.php";
            }
            else
            {
               alert(response.error);
            }
         })
      }
      else
      {
         showInvalid(ProspiraDoc.PageElements.INPUT_FORM);
      }
   }
      
   // **************************************************************************
      
   getDocId()
   {
      return (parseInt(document.getElementById(ProspiraDoc.PageElements.DOC_ID_INPUT).value));
   }
   
   getTableQueryParams()
   {
      let params = new Object();
      
      params.request = "fetch";
      
      params.shipmentLocation = document.getElementById(ProspiraDoc.PageElements.SHIPMENT_LOCATION_INPUT).value;
      
      params.startDate =  document.getElementById(ProspiraDoc.PageElements.START_DATE_INPUT).value;
      
      params.endDate =  document.getElementById(ProspiraDoc.PageElements.END_DATE_INPUT).value;

      return (params);
   }
   
   validateFilterDates()
   {
      let startDate = document.getElementById(ProspiraDoc.PageElements.START_DATE_INPUT).value;
      let endDate = document.getElementById(ProspiraDoc.PageElements.END_DATE_INPUT).value;
      
      return (new Date(endDate) >= new Date(startDate))
   }
   
   onFilterUpdate()
   {
      if (document.readyState === "complete")
      {
         let url = "/app/page/prospiraDoc/";
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
   
   printSheet(docId)
   {
      let url = `/prospiraDoc/prospiraSheet.php?docId=${docId}`;
      
      let printWindow = window.open(url, '_blank');
      
      printWindow.onload = function() {
         printWindow.focus();
         printWindow.print()
      }
   }
   
   // **************************************************************************
}