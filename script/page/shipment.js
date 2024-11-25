class Shipment
{
   // HTML elements
   static PageElements = {
      // Filters
      // Tables
      "DATA_TABLE":        "data-table",
      "TIME_CARD_TABLE":   "time-card-table",
      "HEAT_TABLE":        "heat-table",
      // Buttons
      "ADD_BUTTON":        "add-button",
      "CANCEL_BUTTON":     "cancel-button",
      // Input fields
      "SHIPMENT_ID_INPUT": "shipment-id-input"
   };

   constructor()
   {      
      this.table = null;
      this.timeCardTable = null;
      this.heatTable = null;
      
      this.setup();
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
      
      if (document.getElementById(Shipment.PageElements.CANCEL_BUTTON) != null)
      {
         document.getElementById(Shipment.PageElements.CANCEL_BUTTON).addEventListener('click', function() {
            history.back();
         }.bind(this));
      }
   }      
   
   createTable(tableElementId)
   {
      let url = "/app/page/shipment/";
      let params = new Object();
      params.request = "fetch";
      
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
            {title:"Created",           field:"formattedDateTime", headerFilter:true},
            {title:"Job #",             field:"jobNumber",         headerFilter:true},
            {title:"Quantity",          field:"quantity",          headerFilter:true},
            {title:"Location",          field:"locationLabel",     headerFilter:true},
            {title:"Packing #",         field:"packingListNumber", headerFilter:true},
            {title:"",                  field:"delete",
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
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
            else if (cell.getColumn().getField() == "delete")
            {
               this.onDeleteButton(shipmentId);
               e.stopPropagation();
            }
            else
            {
               document.location = `/shipment/shipment.php?shipmentId=${shipmentId}`;
            }
         }.bind(this),
      });
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
            
   onAddButton(jobId)
   {
      /*
      let startDate = document.getElementById(Schedule.PageElements.MFG_DATE_INPUT).value;
      
      // AJAX call to delete the component.
      let requestUrl = `/app/page/schedule/?request=save_entry&jobId=${jobId}&startDate=${startDate}&employeeNumber=0`;
      
      ajaxRequest(requestUrl, function(response) {
         if (response.success == true)
         {
            this.updateTables();
         }
         else
         {
            alert(response.error);
         }
      }.bind(this));
      */
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
      
   // **************************************************************************
   
   getShipmentId()
   {
      return (parseInt(document.getElementById(Shipment.PageElements.SHIPMENT_ID_INPUT).value));
   }
   
   // **************************************************************************
}