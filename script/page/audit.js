class Audit
{
   // HTML elements
   static PageElements = {
      //  Forms
      "INPUT_FORM":      "input-form",
      // Tables
      "EXPECTED_TABLE" :   "expected-table",
      "UNEXPECTED_TABLE" : "unexpected-table",
      // Inputs,
      "AUDIT_ID_INPUT" :   "audit-id-input",
      "AD_HOC_INPUT":      "ad-hoc-input",
      "AUDIT_NAME_INPUT":  "audit-name-input",
      "LOCATION_INPUT":    "location-input",
      "PART_NUMBER_INPUT": "part-number-input",
      // Buttons
      "ADD_BUTTON":      "add-button",
      "SAVE_BUTTON":     "save-button",
      "SAVE_PROGRESS_BUTTON": "save-progress-button",
      "COMPLETE_BUTTON": "complete-button",
      "CANCEL_BUTTON":   "cancel-button",
      // Filters
      "START_DATE_INPUT":       "start-date-input",
      "END_DATE_INPUT":         "end-date-input",
      "ACTIVE_AUDITS_INPUT":    "active-audits-input",
   };
   
   static PageMode = {
      "EDIT_AUDIT": 0,
      "PERFORM_AUDIT": 1
   }
   
   static locationLabels =  ["", "WIP", "Vendor", "Finished Goods"];

   constructor(pageMode, isAdHoc)
   {      
      this.table = null;
      
      this.pageMode = pageMode;
      this.isAdHoc = isAdHoc;
      
      this.editedName = false;
      
      this.setup();
   }
   
   setup()
   {
      InputValidator.setupValidation();
      
      if (document.getElementById(Audit.PageElements.START_DATE_INPUT))
      {
         document.getElementById(Audit.PageElements.START_DATE_INPUT).addEventListener('change', function() {
            this.onStartDateChanged();
         }.bind(this));
      }
      
      if (document.getElementById(Audit.PageElements.END_DATE_INPUT))
      {
         document.getElementById(Audit.PageElements.END_DATE_INPUT).addEventListener('change', function() {
            this.onEndDateChanged();
         }.bind(this));
      }
      
      if (document.getElementById(Audit.PageElements.ACTIVE_AUDITS_INPUT) != null)
      {
         document.getElementById(Audit.PageElements.ACTIVE_AUDITS_INPUT).addEventListener('change', function() {
            this.onActiveAuditsChanged();
         }.bind(this));
         
         if (document.getElementById(Audit.PageElements.ACTIVE_AUDITS_INPUT).checked)
         {
            disable(Audit.PageElements.START_DATE_INPUT);
            disable(Audit.PageElements.END_DATE_INPUT);
         }
         else
         {
            enable(Audit.PageElements.START_DATE_INPUT);
            enable(Audit.PageElements.END_DATE_INPUT);
         }
      }
      
      if (document.getElementById(Audit.PageElements.ADD_BUTTON) != null)
      {
         document.getElementById(Audit.PageElements.ADD_BUTTON).addEventListener('click', function() {
            this.onAddButton();
         }.bind(this));
      }
      
      if (document.getElementById(Audit.PageElements.SAVE_BUTTON) != null)
      {
         document.getElementById(Audit.PageElements.SAVE_BUTTON).addEventListener('click', function() {
            this.onSaveButton();
         }.bind(this));
      }
      
      if (document.getElementById(Audit.PageElements.SAVE_PROGRESS_BUTTON) != null)
      {
         document.getElementById(Audit.PageElements.SAVE_PROGRESS_BUTTON).addEventListener('click', function() {
            this.onSaveProgressButton();
         }.bind(this));
      }
      
      if (document.getElementById(Audit.PageElements.COMPLETE_BUTTON) != null)
      {
         document.getElementById(Audit.PageElements.COMPLETE_BUTTON).addEventListener('click', function() {
            this.onCompleteButton();
         }.bind(this));
      }
      
      if (document.getElementById(Audit.PageElements.CANCEL_BUTTON) != null)
      {
         document.getElementById(Audit.PageElements.CANCEL_BUTTON).addEventListener('click', function() {
            this.onCancelButton();
         }.bind(this));
      }
      
      if (document.getElementById(Audit.PageElements.AUDIT_NAME_INPUT))
      {
         document.getElementById(Audit.PageElements.AUDIT_NAME_INPUT).addEventListener('input', function() {
            this.onAuditNameChanged();
         }.bind(this));
      }    
      
      if (document.getElementById(Audit.PageElements.AD_HOC_INPUT))
      {
         document.getElementById(Audit.PageElements.AD_HOC_INPUT).addEventListener('change', function() {
            this.onAdHocChanged();
         }.bind(this));
      }
      
      if (document.getElementById(Audit.PageElements.LOCATION_INPUT))
      {
         document.getElementById(Audit.PageElements.LOCATION_INPUT).addEventListener('change', function() {
            this.onLocationChanged();
         }.bind(this));
      }
      
      if (document.getElementById(Audit.PageElements.PART_NUMBER_INPUT))
      {
         document.getElementById(Audit.PageElements.PART_NUMBER_INPUT).addEventListener('change', function() {
            this.onPartNumberChanged();
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
         columnDefaults:{
            hozAlign:"left", 
            vertAlign:"middle"
         },
         ajaxURL:url,
         ajaxParams:params,
         ajaxResponse:function(url, params, response) {
            let tableData = [];
            if (response.success)
            {
               tableData = response.audits;
            }
            return (tableData);
         },
         //Define Table Columns
         columns:[
            {title:"ID",          field:"auditId",            headerFilter:true, visible:false,
               formatter:function(cell, formatterParams, onRendered){
                  var cellValue = ("0000" + cell.getValue()).slice (-4);
                  return (cellValue);
               }
            },
            {title:"Name",         field:"auditName",          headerFilter:true},
            {title:"Author",       field:"authorFullName",     headerFilter:true},                   
            {title:"Created",      field:"formattedCreated",
               sorter:"datetime", 
               sorterParams:{
                  format:"MM/DD/YYYY h:mm A",
                  alignEmptyValues:"top",
               }
            },
            /*
            {title:"Scheduled",    field:"formattedScheduled",
               sorter:"datetime", 
               sorterParams:{
                  format:"MM/DD/YYYY h:mm A",
                  alignEmptyValues:"top",
               }
            },
            */
            {title:"Assigned",     field:"assignedFullName",   headerFilter:true},                   
            {title:"Location",     field:"locationLabel",      headerFilter:true},
            {title:"Part #",       field:"partNumber",         headerFilter:true},
            //{title:"Progress",     field:"progress", formatter: "progress"},
            {title:"Accuracy",     field:"accuracy",
               formatter:function(cell, formatterParams, onRendered){
                  return (cell.getValue() + "%");
               }
            },
            {title:"Status",       field:"statusLabel",        headerFilter:true},
            {title:"",             field:"perform",            hozAlign:"center", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  let cellValue =`<button class=\"small-button accent-button\" style=\"width:50px;\">Perform</button>`;
                  return (cellValue);
               }
            },
            {title:"",             field:"delete",             hozAlign:"center", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ],
         initialSort:[
            {column:"formattedScheduled", dir:"asc"}
         ]
      });
      
      this.table.on("cellClick", function(e, cell) {
         let auditId = parseInt(cell.getRow().getData().auditId);
         
         if (cell.getColumn().getField() == "perform")
         {
            this.onPerformButton(auditId);
            e.stopPropagation();
         }
         else if (cell.getColumn().getField() == "delete")
         {
            this.onDeleteButton(auditId);
            e.stopPropagation();
         }
         else
         {
            document.location = "/audit/audit.php?auditId=" + auditId;   
         }
      }.bind(this));
   }
   
   createAuditLineTable(tableElementId)
   {
      let url = this.getTableQuery();
      let params = {
         request: "fetch_audit_lines",
         auditId: this.getAuditId()
      };
      
      let tableElementQuery = "#" + tableElementId;
   
      // Create Tabulator table
      this.table = new Tabulator(tableElementQuery, {
         layout:"fitData",
         columnDefaults:{
            hozAlign:"left", 
            vertAlign:"middle"
         },
         ajaxURL:url,
         ajaxParams:params,
         ajaxResponse:function(url, params, response) {
            let tableData = [];
            if (response.success)
            {
               tableData = response.auditLines;
            }
            return (tableData);
         },
         //Define Table Columns
         columns:[
            {                           field:"auditLineId",          visible:false},
            {                           field:"shipment.shipmentId",  visible:false},
            {title:"Ticket",            field:"shipment.shipmentTicketCode",
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">receipt</i>&nbsp" + cell.getRow().getData().shipment.shipmentTicketCode);
               },
               formatterPrint:function(cell, formatterParams, onRendered){
                  return (cell.getValue());
               }  
            },
            {title:"Created",           field:"shipment.dateTime",
               formatter:function(cell, formatterParams, onRendered) {
                  return (cell.getRow().getData().shipment.formattedDateTime);
               }
            },
            {title:"Customer",          field:"shipment.part.customerName"},
            {title:"Part #",            field:"shipment.part.customerNumber"},
            {title:"Job #",             field:"shipment.jobNumber"},
            {title:"Quantity",          field:"shipment.quantity", bottomCalc:"sum"},
            {title:"Corrected<br>Quantity", field:"adjustedCount", editor:"number", 
               editorParams:{
                  min:0,
                  max:9999,
                  step:1,
               },
            }, 
            {title:"Location",          field:"shipment.locationLabel"},
            {title:"Corrected<br>Location", field:"adjustedLocation", editor:"list", 
               editorParams:{
                  values:{
                     0:"&nbsp",
                     1:"WIP",
                     2:"Vendor",
                     3:"Finished Goods"
                  },
               },
               formatter:function(cell, formatterParams, onRendered) {
                  var location = parseInt(cell.getValue());
                  var cellValue = Audit.locationLabels[location];

                  return (cellValue);
               }.bind(this)
            }, 
            {title:"Confirmed",         field:"confirmed", formatter:"tickCross",
               formatterParams: {
                  allowEmpty: true,
                  tickElement: "<i class=\"material-icons icon-button\">check_box</i>",
                  crossElement: "<i class=\"material-icons icon-button\">check_box_outline_blank</i>"
               }
            },
            {title:"",             field:"delete",             hozAlign:"center", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ],
         rowFormatter: function(row){
            var isExpected = row.getData().isExpected;
            if (!isExpected)
            {
               //row.getElement().classList.add("unexpected");
               row.getElement().style.backgroundColor = "#FFD1D1";
            }
         },
         initialSort:[
            {column:"auditLineId", dir:"desc"}
         ]
      });
      
      this.table.on("cellClick", function(e, cell) {
         let auditLineId = parseInt(cell.getRow().getData().auditLineId);
         
         if (cell.getColumn().getField() == "delete")
         {
            this.onDeleteAuditLineButton(auditLineId);
            e.stopPropagation();
         }
         else if (cell.getColumn().getField() == "confirmed")
         {
            cell.setValue(!cell.getValue());
            e.stopPropagation();
         }
      }.bind(this));
   }
   
   // **************************************************************************
   
   onStartDateChanged()
   {
      if (!this.validateFilterDates())
      {
         document.getElementById(Audit.PageElements.END_DATE_INPUT).value = 
            document.getElementById(Audit.PageElements.START_DATE_INPUT).value
      }
      
      this.onFilterUpdate();
      
      setSession("audit.startDate", document.getElementById(Audit.PageElements.START_DATE_INPUT).value);
   }
   
   onEndDateChanged()
   {
      if (!this.validateFilterDates())
      {
         document.getElementById(Audit.PageElements.START_DATE_INPUT).value = 
            document.getElementById(Audit.PageElements.END_DATE_INPUT).value
      }

      this.onFilterUpdate();
      
      setSession("audit.endDate", document.getElementById(Audit.PageElements.END_DATE_INPUT).value);
   }
   
   onActiveAuditsChanged()
   {
      var activeAudits = document.getElementById(Audit.PageElements.ACTIVE_AUDITS_INPUT).checked;
      
      if (activeAudits)
      {
         disable(Audit.PageElements.START_DATE_INPUT);
         disable(Audit.PageElements.END_DATE_INPUT);
      }
      else
      {
         enable(Audit.PageElements.START_DATE_INPUT);
         enable(Audit.PageElements.END_DATE_INPUT);
      }
      
      this.onFilterUpdate();
      
      setSession("audit.activeAudits", (activeAudits ? "true" : "false"));
   }
   
   onAddButton()
   {
      document.location = `/audit/audit.php?customerId=${UNKNOWN_AUDIT_ID}`;
   }
   
   onDeleteButton(auditId)
   {
      if (confirm("Are you sure you want to delete this audit?"))
      {
         // AJAX call to delete the component.
         let requestUrl = `/app/page/audit/?request=delete_audit&auditId=${auditId}`;
         
         ajaxRequest(requestUrl, function(response) {
            if (response.success == true)
            {
               location.href = "/audit/audits.php";
            }
            else
            {
               console.log("Call to delete the audit failed.");
               alert(response.error);
            }
         });
      }
   }
   
   onDeleteAuditLineButton(auditLineId)
   {
      let rows = this.table.searchRows("auditLineId", "=", auditLineId);
      
      for (let row of rows)
      {
         row.delete();
      }
   }
   
   onSaveButton()
   {
      this.saveAudit();
   }
   
   onSaveProgressButton()
   {
      this.performAudit(false);
   }
   
   onCompleteButton()
   {
      this.performAudit(true);
   }
   
   onCancelButton()
   {
      document.location = `/audit/audits.php`;
   }
   
   onPerformButton(auditId)
   {
      document.location = `/audit/performAudit.php?auditId=${auditId}`;
   }
   
   onBarcode(value)
   {
      let shipmentId = parseInt(value);
      
      let row = null;
   
      // Search for the entry in the Expected table.
      if ((row = this.getRowInTable(this.table, shipmentId)) != null)
      {
         this.markConfirmed(row, true);
      }
      else
      {
         this.addAuditLine(shipmentId);
      }
   }
   
   onAuditNameChanged()
   {
      this.editedName = (get(Audit.PageElements.AUDIT_NAME_INPUT) != "");
   }
   
   onAdHocChanged()
   {
      let isAdHoc = document.getElementById(Audit.PageElements.AD_HOC_INPUT).checked;
      
      if (isAdHoc)
      {
         clear(Audit.PageElements.PART_NUMBER_INPUT);
         disable(Audit.PageElements.PART_NUMBER_INPUT);
      }
      else
      {
         enable(Audit.PageElements.PART_NUMBER_INPUT);
      }
      
      this.generateAuditName();
   }
   
   onLocationChanged()
   {
      this.generateAuditName();
   }
   
   onPartNumberChanged()
   {
      this.generateAuditName();
   }
   
   // **************************************************************************
   
   getAuditId()
   {
      return (parseInt(document.getElementById(Audit.PageElements.AUDIT_ID_INPUT).value));
   }
   
   isNew()
   {
      return (this.getAuditId() == UNKNOWN_AUDIT_ID);
   }
   
   getTableQuery()
   {
      return ("/app/page/audit/");
   }

   getTableQueryParams()
   {
      let params = new Object();
      
      params.request = "fetch";
      
      params.startDate =  document.getElementById(Audit.PageElements.START_DATE_INPUT).value;
      
      params.endDate =  document.getElementById(Audit.PageElements.END_DATE_INPUT).value;
      
      if (document.getElementById(Audit.PageElements.ACTIVE_AUDITS_INPUT).checked)
      {
         params.activeAudits = true;
      }

      return (params);
   }
   
   validateFilterDates()
   {
      let startDate = document.getElementById(Audit.PageElements.START_DATE_INPUT).value;
      let endDate = document.getElementById(Audit.PageElements.END_DATE_INPUT).value;
      
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
   
   createLineItemInputs()
   {
      if (this.table)  // Only for performAudit page.
      {
         let rows = this.table.getRows();
         
         for (const row of rows)
         {
            let shipmentId = row.getData().shipment.shipmentId;
            let confirmed = row.getData().confirmed;
            
            let name = "confirmed_" + shipmentId;
            let value = confirmed;
            
            let input = document.createElement("input");
            input.setAttribute("type", "hidden");
            input.setAttribute("name", name);
            input.setAttribute("value", value);
            document.getElementById(Audit.PageElements.INPUT_FORM).appendChild(input);
         }
      }
   }
   
   getRowInTable(table, shipmentId)
   {
      let row = null;
      
      let rows = table.searchRows("shipment.shipmentId", "=", shipmentId);
      
      if (rows.length > 0)
      {
         row = rows[0];
      }
      
      return (row);
   }
   
   markConfirmed(row, isConfirmed)
   {
      let cell = row.getCell("confirmed");
      cell.setValue(isConfirmed);
   }
   
   addAuditLine(shipmentId)
   {
      let auditId = this.getAuditId();
      
      ajaxRequest(`/app/page/audit/?request=fetch_audit_line&auditId=${auditId}&shipmentId=${shipmentId}`, function(response) {
         if (response.success == true)
         {
            // The fact that we found it makes it automatically confirmed. 
            response.auditLine.confirmed = true;
            
            this.table.addRow(response.auditLine, false)
            .then(function(row) {
               row.getTable().redraw(true);
               row.getTable().scrollToRow(row, "center", true);
            });
         }
      }.bind(this));
   }
   
   generateAuditName()
   {
      // <location>_<adhoc>_<mm_dd_yyyy>
      // <location>_<part>_<mm_dd_yyyy>
      
      const LOCATION_LABELS = ["Unknown", "WIP", "Vendor", "FinishedGoods", "Customer"];
      
      let auditName = get(Audit.PageElements.AUDIT_NAME_INPUT);
      let location = parseInt(get(Audit.PageElements.LOCATION_INPUT));
      let partNumberInput = document.getElementById(Audit.PageElements.PART_NUMBER_INPUT);
      let partNumberSelected = partNumberInput.selectedIndex;
      let isAdHoc = document.getElementById(Audit.PageElements.AD_HOC_INPUT).checked;
      
      if (this.isNew() &&
          (!isNaN(location)) &&
          (location != UNKNOWN_SHIPMENT_LOCATION) &&
          !this.editedName)
      {
         // Location label.
         let locationLabel = LOCATION_LABELS[location];
         
         // Part number label.
         let partNumberLabel = null;
         if (partNumberSelected > 0)
         {
            partNumberLabel = partNumberInput.options[partNumberSelected].text;
         }
         
         let name = locationLabel + "_";
         
         if (isAdHoc == true)
         {
            name += "AdHoc_";
         }
         else if (partNumberLabel != null)
         {
           name += partNumberLabel + "_";
         }
         
         name += this.getFormattedDate();
         
         set(Audit.PageElements.AUDIT_NAME_INPUT, name);
      }
   }
   
   getFormattedDate()
   {
      const today = new Date();
      
      const yyyy = today.getFullYear();
      let mm = today.getMonth() + 1; // Months start at 0!
      let dd = today.getDate();

      if (dd < 10) dd = '0' + dd;
      if (mm < 10) mm = '0' + mm;

      return (mm + "_" + dd + "_" + yyyy);
   }
   
   saveAudit()
   {
      if (InputValidator.validateForm(document.getElementById(Audit.PageElements.INPUT_FORM)))
      {
         submitForm(Audit.PageElements.INPUT_FORM, "/app/page/audit", function (response) {
            if (response.success == true)
            {
               location.href = "/audit/audits.php";
            }
            else
            {
               alert(response.error);
            }
         })
      }
   }
   
   performAudit(complete)
   {
      let params = new Object();
      params.request = "perform_audit";
      params.auditId = this.getAuditId();
      params.complete = complete;
      
      this.submitTable(this.table, this.getTableQuery(), params, function (response) {
         if (response.success == true)
         {
            location.href = "/audit/audits.php";
         }
         else
         {
            alert(response.error);
         }
      });
   }
   
   async submitTable(table, url, params, callback)
   {
      params.data = table.getData();
      
      try 
      {
         const response = await fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(params),
         });

         const reponseText = await response.text();
        
         if (response.ok)
         {
            try
            {
               const responseObj = JSON.parse(reponseText);
               
               if (callback != null)
               {
                  callback(responseObj);
               }
            } 
            catch (error)
            {
                console.log("JSON syntax error");
                console.log(reponseText);
                throw error;
            }
         }
         else
         {
            console.log("Server error");
         }
      }
      catch (error)
      {
          console.log("Server error");
          throw error;
      }
   }
}      