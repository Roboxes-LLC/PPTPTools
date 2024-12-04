class Part
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
      "CANCEL_BUTTON":     "cancel-button",
      "SAVE_BUTTON":       "save-button",
      // Input fields
      "PPTP_NUMBER_INPUT": "pptp-number-input",
      "IS_NEW_INPUT":      "is-new-input"
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
      if (document.getElementById(Part.PageElements.ADD_BUTTON) != null)
      {
         document.getElementById(Part.PageElements.ADD_BUTTON).addEventListener('click', function() {
            this.onAddButton();
         }.bind(this));
      }
      
      if (document.getElementById(Part.PageElements.CANCEL_BUTTON) != null)
      {
         document.getElementById(Part.PageElements.CANCEL_BUTTON).addEventListener('click', function() {
            history.back();
         }.bind(this));
      }
      
      if (document.getElementById(Part.PageElements.SAVE_BUTTON) != null)
      {
         document.getElementById(Part.PageElements.SAVE_BUTTON).addEventListener('click', function() {
            this.onSaveButton();
         }.bind(this));
      }
   }      
   
   createTable(tableElementId)
   {
      let url = "/app/page/part/";
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
               tableData = response.parts;
            }
            return (tableData);
         },
         //Define Table Columns
         columns:[
            {title:"PPTP #",            field:"pptpNumber",     hozAlign:"left", headerFilter:true},
            {title:"Customer #",        field:"customerNumber", hozAlign:"left", headerFilter:true},
            {title:"Customer",          field:"customerName",   hozAlign:"left", headerFilter:true},
            {title:"",                  field:"delete",
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ],
         initialSort:[
            {column:"pptpNumber", dir:"asc"}
         ],
         cellClick:function(e, cell){
            let partNumber = cell.getRow().getData().pptpNumber;
            
            if (cell.getColumn().getField() == "delete")
            {
               this.onDeleteButton(partNumber);
               e.stopPropagation();
            }
            else
            {
               document.location = `/part/part.php?partNumber=${partNumber}`;
            }
         }.bind(this),
      });
   }
   
   // **************************************************************************
            
   onAddButton()
   {
      document.location = `/part/part.php?partNumber=`;
   }
   
   onSaveButton()
   {
      let form = document.getElementById(Part.PageElements.INPUT_FORM);
      
      if (form.reportValidity() == true)
      {
         submitForm(Part.PageElements.INPUT_FORM, "/app/page/part", function (response) {
            if (response.success == true)
            {
               location.href = "/part/parts.php";
            }
            else
            {
               alert(response.error);
            }
         });
      }
      else
      {
         showInvalid(Part.PageElements.INPUT_FORM);
      }
   }
   
   onDeleteButton(partNumber)
   {
      if (confirm("Are you sure you want to delete this part?  All associated data (jobs, inspections, etc.) will also be deleted."))
      {
         // AJAX call to delete the component.
         let requestUrl = `/app/page/part/?request=delete_part&pptpNumber=${partNumber}`;
         
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
   
   getPartNumber()
   {
      let partNumber = null;
      
      if (document.getElementById(Part.PageElements.PPTP_NUMBER_INPUT) != null)
      {
         document.getElementById(Part.PageElements.PPTP_NUMBER_INPUT).value;
         console.log("PPTP Number: '" + partNumber + "'");
      }
      
      return (partNumber);
   }
   
   isNew()
   {
      return (document.getElementById(Part.PageElements.IS_NEW_INPUT).value === "true");
   }
   
   // **************************************************************************
}