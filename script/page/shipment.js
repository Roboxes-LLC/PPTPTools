class Shipment
{
   // HTML elements
   static PageElements = {
      // Filters
      // Tables
      "DATA_TABLE": "data-table",
      // Buttons
      "ADD_BUTTON":    "add-button",
      // Input fields
   };

   constructor()
   {      
      this.table = null;
      
      this.setup();
   }
   
   setup()
   {
      this.createTable(Shipment.PageElements.DATA_TABLE);
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
            {title:"Date",              field:"formattedDateTime",           headerFilter:true},
            {title:"Job #",             field:"jobInfo.jobNumber",           headerFilter:true},
            {title:"Quantity",          field:"quantity",          headerFilter:true},
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
   
   
   // **************************************************************************
}