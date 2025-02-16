class Job
{
   // HTML elements
   static PageElements = {
      // Forms
      "INPUT_FORM": "input-form",
      // Filters
      "JOB_STATUS_FILTER": "job-status-filter",
      // Tables
      "DATA_TABLE":        "data-table",
      // Buttons
      "ADD_BUTTON":        "add-button",
      "CANCEL_BUTTON":     "cancel-button",
      "SAVE_BUTTON":       "save-button",
      // Input fields
      "PPTP_PART_NUMBER_INPUT": "pptp-part-number-input",
      "JOB_NUMBER_INPUT": "job-number-input",
      "JOB_NUMBER_PREFIX_INPUT": "job-number-prefix-input",
      "JOB_NUMBER_SUFFIX_INPUT": "job-number-suffix-input",
      "CUSTOMER_NAME_INPUT": "customer-name-input",
      "CUSTOMER_PART_NUMBER_INPUT": "customer-part-number-input",
      "GROSS_PARTS_PER_HOUR_INPUT": "gross-parts-per-hour-input",
      "NET_PARTS_PER_HOUR_INPUT": "net-parts-per-hour-input",
      "CYCLE_TIME_INPUT": "cycle-time-input",
      "NET_PERCENTAGE_INPUT": "net-percentage-input"
   };

   constructor()
   {      
      this.table = null;
      
      this.setup();
      
      if (document.getElementById(Job.PageElements.GROSS_PARTS_PER_HOUR_INPUT) != null)
      {
        this.updateCalculatedValues();
     }
   }
   
   setup()
   {
      var filters = document.getElementsByClassName(Job.PageElements.JOB_STATUS_FILTER);
      for (var filter of filters)
      {
         filter.addEventListener("change", function() {
            this.onFilterUpdate(); 
         }.bind(this));
      }      
      
      if (document.getElementById(Job.PageElements.ADD_BUTTON) != null)
      {
         document.getElementById(Job.PageElements.ADD_BUTTON).addEventListener('click', function() {
            document.location = `/jobs/viewJob.php?jobId=`;
         }.bind(this));
      }
      
      if (document.getElementById(Job.PageElements.CANCEL_BUTTON) != null)
      {
         document.getElementById(Job.PageElements.CANCEL_BUTTON).addEventListener('click', function() {
            history.back();
         }.bind(this));
      }
      
      if (document.getElementById(Job.PageElements.SAVE_BUTTON) != null)
      {
         document.getElementById(Job.PageElements.SAVE_BUTTON).addEventListener('click', function() {
            this.onSaveButton();
         }.bind(this));
      }
      
      if (document.getElementById(Job.PageElements.PPTP_PART_NUMBER_INPUT) != null)
      {
         document.getElementById(Job.PageElements.PPTP_PART_NUMBER_INPUT).addEventListener('change', function() {
            this.updateJobNumber();
            this.onPPTPPartNumberChanged();
         }.bind(this));
      }
      
      if (document.getElementById(Job.PageElements.JOB_NUMBER_SUFFIX_INPUT) != null)
      {
         document.getElementById(Job.PageElements.JOB_NUMBER_SUFFIX_INPUT).addEventListener('change', function() {
            this.updateJobNumber();
         }.bind(this));
      }
      
      if (document.getElementById(Job.PageElements.GROSS_PARTS_PER_HOUR_INPUT) != null)
      {
         document.getElementById(Job.PageElements.GROSS_PARTS_PER_HOUR_INPUT).addEventListener('change', function() {
            this.updateCalculatedValues();
         }.bind(this));
      }
      
      if (document.getElementById(Job.PageElements.NET_PARTS_PER_HOUR_INPUT) != null)
      {
         document.getElementById(Job.PageElements.NET_PARTS_PER_HOUR_INPUT).addEventListener('change', function() {
            this.updateCalculatedValues();
         }.bind(this));
      }
   }      
   
   createTable(tableElementId)
   {
      let url = "/app/page/job/";
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
               tableData = response.jobs;
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
            {title:"Id",             field:"jobId",           visible:false},
            {title:"Job #",          field:"jobNumber",       headerFilter:true},
            {title:"Date",           field:"dateTime",        headerFilter:"input",
               formatter:"datetime",
               formatterParams:{
                  outputFormat:"M/d/yyyy",
                  invalidPlaceholder:"---"
               }
            },
            {title:"Part #",         field:"part.pptpNumber", headerFilter:true},
            {title:"Customer",       field:"customerName",    headerFilter:true},
            {title:"Cust. Part #",   field:"part.pptpNumber", headerFilter:true},
            {title:"Work Center",    field:"wcLabel",         headerFilter:true},
            {title:"Cycle Time",        field:"cycleTime"},
            {title:"Gross Pieces/Hour", field:"grossPartsPerHour"},
            {title:"Net Pieces/Hour",   field:"netPartsPerHour"},                     
            {title:"Customer Print",    field:"part.customerPrint", 
               formatter:function(cell, formatterParams, onRendered){
                  let cellValue = null;
                  let filename = cell.getValue();
                  if (filename)
                  {
                     let truncatedFilename = (filename.length > 20) ? filename.substr(0, 20) + "..." : filename; 
                     cellValue = `<a href="/uploads/${filename}" target="_blank">${truncatedFilename}</a>`;
                  }
                  return (cellValue);
                }
            },
            {title:"Status",         field:"statusLabel",     headerFilter:true},
            {title:"",               field:"copy",            hozAlign:"center", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">content_copy</i>");
               }
            },
            {title:"",               field:"delete",          hozAlign:"center", print:false,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ],
         initialSort:[
            {column:"jobNumber", dir:"asc"}
         ]
      });
      
      this.table.on("cellClick", function(e, cell) {
         let jobId = parseInt(cell.getRow().getData().jobId);
         
         if (cell.getColumn().getField() == "copy")
         {
            document.location = `viewJob.php?copyFromJobId=${jobId}`;
         }
         else if (cell.getColumn().getField() == "delete")
         {
            this.onDeleteButton(jobId);
            e.stopPropagation();
         }
         else if (cell.getColumn().getField() == "customerPrint")
         {
            // No action.  Allow for clicking on link.
         }
         else // Any other column
         {
            document.location = `/jobs/viewJob.php?jobId=${jobId}`;          
         }
      }.bind(this));
   }
   
   // **************************************************************************

   onAddButton()
   {
      document.location = `/part/part.php?partNumber=`;
   }
   
   onSaveButton()
   {
      let form = document.getElementById(Job.PageElements.INPUT_FORM);
      
      if (form.reportValidity() == true)
      {
         submitForm(Job.PageElements.INPUT_FORM, "/app/page/job", function (response) {
            if (response.success == true)
            {
               location.href = "/jobs/viewJobs.php";
            }
            else
            {
               alert(response.error);
            }
         });
      }
      else
      {
         showInvalid(Job.PageElements.INPUT_FORM);
      }
   }
   
   onDeleteButton(jobId)
   {
      if (confirm("Are you sure you want to delete this job?"))
      {
         // AJAX call to delete the component.
         let requestUrl = `/app/page/job/?request=delete_job&jobId=${jobId}`;
         
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

   onPPTPPartNumberChanged()
   {
      let partNumber = this.getPartNumber();

      let requestUrl = `/app/page/part/?request=fetch&partNumber=${partNumber}`;
         
      ajaxRequest(requestUrl, function(response) {
         if (response.success == true)
         {
            this.updatePartInfo(response.part);
            this.updateJobNumber();
         }
         else
         {
            console.log(response.error);
         }
      }.bind(this));
   }
   
   onFilterUpdate()
   {
      if (document.readyState === "complete")
      {
         let url = "/app/page/job/";
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
   
   getTableQueryParams()
   {
      let params = new Object();
      params.request = "fetch";

      let filters = document.getElementsByClassName("job-status-filter");

      for (let filter of filters)
      {
         params[filter.name] = filter.checked;
      }

      return (params);
   }
   
   getPartNumber()
   {
      return (document.getElementById(Job.PageElements.PPTP_PART_NUMBER_INPUT).value);
   }
   
   updatePartInfo(part)
   {
      if (part === null)
      {
         document.getElementById(Job.PageElements.JOB_NUMBER_PREFIX_INPUT).value = null;
         document.getElementById(Job.PageElements.CUSTOMER_NAME_INPUT).value = null;
         document.getElementById(Job.PageElements.CUSTOMER_PART_NUMBER_INPUT).value = null;
      }
      else
      {
         document.getElementById(Job.PageElements.JOB_NUMBER_PREFIX_INPUT).value = part.pptpNumber;
         document.getElementById(Job.PageElements.CUSTOMER_NAME_INPUT).value = part.customerName;
         document.getElementById(Job.PageElements.CUSTOMER_PART_NUMBER_INPUT).value = part.customerNumber;
      }
   }
   
   updateJobNumber()
   {
      let jobNumberPrefix = document.getElementById(Job.PageElements.JOB_NUMBER_PREFIX_INPUT).value;
      let jobNumberSuffix = document.getElementById(Job.PageElements.JOB_NUMBER_SUFFIX_INPUT).value;
      
      if ((jobNumberPrefix != "") && (jobNumberSuffix != ""))
      {
         let jobNumber = (jobNumberPrefix + "-" + jobNumberSuffix);
         console.log(jobNumber);
         document.getElementById(Job.PageElements.JOB_NUMBER_INPUT).value = jobNumber;
      }
   }
   
   updateCalculatedValues()
   {
      var grossPartsPerHourInput = document.getElementById(Job.PageElements.GROSS_PARTS_PER_HOUR_INPUT);
      var netPartsPerHourInput = document.getElementById(Job.PageElements.NET_PARTS_PER_HOUR_INPUT);
      var cycleTimeInput = document.getElementById(Job.PageElements.CYCLE_TIME_INPUT);
      var netPercentageInput = document.getElementById(Job.PageElements.NET_PERCENTAGE_INPUT);

      if (grossPartsPerHourInput.checkValidity())
      {
         let grossPartsPerHour = parseInt(grossPartsPerHourInput.value);
      
         if (grossPartsPerHour > 0)
         {
            let cycleTime = (3600 / grossPartsPerHour);  // seconds
            
            cycleTimeInput.value = cycleTime.toFixed(2);
         }
         else
         {
            cycleTimeInput.value = null;
         }
      
         if (netPartsPerHourInput.checkValidity())
         {
            let netPartsPerHour = parseInt(netPartsPerHourInput.value);
            
            if (grossPartsPerHour > 0)
            {
               let netPercentage = ((netPartsPerHour / grossPartsPerHour) * 100.0);
               
               netPercentageInput.value = netPercentage.toFixed(2);
            }
         }
         else
         {
            netPercentageInput.value = null;
         }
      }
      else
      {
         cycleTimeInput.value = null;
         netPercentageInput.value = null;
      }
   }
}
