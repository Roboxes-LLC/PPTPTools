class Plating
{
   // HTML elements
   static PageElements = {
      // Forms
      "INPUT_FORM": "input-form",
      // Filters
      // Tables
      "DATA_TABLE":        "data-table",
      // Buttons
      "SAVE_BUTTON":       "save-button",
      "CANCEL_BUTTON":     "cancel-button",
      // Input fields
      "SHIPMENT_ID_INPUT": "shipment-id-input",
   };

   constructor()
   {      
      this.table = null;
      
      this.setup();
   }
   
   setup()
   {
      if (document.getElementById(Plating.PageElements.SAVE_BUTTON) != null)
      {
         document.getElementById(Plating.PageElements.SAVE_BUTTON).addEventListener('click', function() {
            this.onSaveButton();
         }.bind(this));
      }
      
      if (document.getElementById(Plating.PageElements.CANCEL_BUTTON) != null)
      {
         document.getElementById(Plating.PageElements.CANCEL_BUTTON).addEventListener('click', function() {
            history.back();
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
            {                           field:"priorityPlating",      hozAlign:"center",
               formatter:function(cell, formatterParams, onRendered){
                  let cellValue = "";
                  let isPriority = (cell.getValue() != 0);
                  
                  if (isPriority)
                  {
                     cellValue = "<i class=\"material-icons priority-icon\">priority_high</i>"
                  }

                  return (cellValue);
               }
            },
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
            {title:"Part #",            field:"part.customerNumber", headerFilter:true},
            {title:"Job #",             field:"jobNumber",           headerFilter:true},
            {title:"Quantity",          field:"quantity",            headerFilter:true},
            {title:"Packing #",         field:"packingListNumber",   headerFilter:true},
            {title:"Packing List",      field:"packingList",
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
            {title:"Plating Status",    field:"platingStatusLabel", editor:"list", 
              editorParams:{
                 values:["Raw", "Plated", "Strip and Replate"],
              }
           }
         ],
         initialSort:[
            {column:"dateTime", dir:"desc"}
         ]
      });
      
      this.table.on("cellClick", function(e, cell) {
         let shipmentId = parseInt(cell.getRow().getData().shipmentId);
         
         if (cell.getColumn().getField() == "packingList")
         {
            e.stopPropagation();
         }
         else
         {
            document.location = `/plating/shipment.php?shipmentId=${shipmentId}`;
         }
      });
   }
   
   // **************************************************************************
         
   onSaveButton()
   {
      let form = document.getElementById(Plating.PageElements.INPUT_FORM);
      
      if (form.reportValidity() == true)
      {
         submitForm(Plating.PageElements.INPUT_FORM, "/app/page/shipment", function (response) {
            if (response.success == true)
            {
               location.href = "/plating/shipments.php";
            }
            else
            {
               alert(response.error);
            }
         })
      }
      else
      {
         showInvalid(Plating.PageElements.INPUT_FORM);
      }
   }
      
   // **************************************************************************
      
   getShipmentId()
   {
      return (parseInt(document.getElementById(Plating.PageElements.SHIPMENT_ID_INPUT).value));
   }
   
   getTableQueryParams()
   {
      let params = new Object();
      
      params.request = "fetch";
      
      params.shipmentLocation = ShipmentLocation.PLATER;
      
      return (params);
   }
   
   // **************************************************************************
}