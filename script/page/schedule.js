class Schedule
{
   // HTML elements
   static PageElements = {
      // Filters
      "START_DATE_INPUT":       "start-date-input",
      // Tables
      "SCHEDULED_TABLE":       "scheduled-table",
      "UNSCHEDULED_TABLE":     "unscheduled-table",
      // Buttons
      // Input fields
   };

   constructor(operatorOptions)
   {      
      this.scheduledTable = null;
      this.unscheduledTable = null;
      this.operatorOptions = operatorOptions;
      
      this.setup();
   }
   
   setup(operatorOptions)
   {
      this.createScheduledTable(Schedule.PageElements.SCHEDULED_TABLE);
      this.createUnscheduledTable(Schedule.PageElements.UNSCHEDULED_TABLE);
      
      if (document.getElementById(Schedule.PageElements.START_DATE_INPUT) != null)
      {
         document.getElementById(Schedule.PageElements.START_DATE_INPUT).addEventListener('change', function() {
            this.onStartDateChanged();
         }.bind(this));
      }
   }      
   
   createScheduledTable(tableElementId, operatorOptions)
   {
      let url = "/app/page/schedule/";
      let params = new Object();
      params.request = "fetch";
      params.startDate =  document.getElementById(Schedule.PageElements.START_DATE_INPUT).value;
      
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
               tableData = response.schedule;
            }
            return (tableData);
         },
         //Define Table Columns
         columns:[
            {                           field:"entryId",                 visible:false},
            {title:"WC #",              field:"jobInfo.wcNumber",        headerFilter:true},
            {title:"Job #",             field:"jobInfo.jobNumber",       headerFilter:true},
            {title:"Assigned Operator", field:"userInfo.employeeNumber", headerFilter:true,  editor:"select", cssClass:"editable",
               editorParams:{
                  values:this.operatorOptions
               },
               formatter:function(cell, formatterParams, onRendered) {
                  var employeeNumber = parseInt(cell.getValue());
                  var cellValue = this.operatorOptions[employeeNumber];

                  return (cellValue);
               }.bind(this)
            },
            {title:"",                  field:"delete",
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ],
         initialSort:[
            {column:"jobInfo.wcNumber", dir:"asc"}
         ],
         cellClick:function(e, cell){
            let entryId = parseInt(cell.getRow().getData().entryId);
            
            if (cell.getColumn().getField() == "delete")
            {
               this.onDeleteButton(entryId);
               e.stopPropagation();
            }
            else if (cell.getColumn().getField() == "userInfo.username")
            {
               e.stopPropagation();
            }
         }.bind(this),
         cellEdited:function(cell){
            if (cell.getColumn().getField() == "userInfo.employeeNumber")
            {
               let entryId = cell.getRow().getData().entryId;
               let employeeNumber = cell.getValue();
               
               this.onJobAssigned(entryId, employeeNumber);
            }
         }.bind(this),
      });
   }
   
   createUnscheduledTable(tableElementId)
   {
      let url = "/app/page/schedule/";
      
      let params = new Object();
      params.request = "fetchUnassigned";
      params.startDate =  document.getElementById(Schedule.PageElements.START_DATE_INPUT).value;

      let tableElementQuery = "#" + tableElementId;
   
      // Create Tabulator table
      this.unscheduledTable = new Tabulator(tableElementQuery, {
         layout:"fitData",
         cellVertAlign:"middle",
         ajaxURL:url,
         ajaxParams:params,
         ajaxResponse:function(url, params, response) {
            let tableData = [];
            if (response.success)
            {
               tableData = response.unscheduled;
            }
            return (tableData);
         },
         //Define Table Columns
         columns:[
            {                           field:"jobId",           visible:false},
            {title:"WC #",              field:"wcNumber",        headerFilter:true},
            {title:"Job #",             field:"jobNumber",       headerFilter:true},
            {title:"",                  field:"add",
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">add</i>");
               }
            }
         ],
         initialSort:[
            {column:"wcNumber", dir:"asc"}
         ],
         cellClick:function(e, cell){
            if (cell.getColumn().getField() == "add")
            {
               let jobId = cell.getRow().getData().jobId;
               
               this.onAddButton(jobId);
               e.stopPropagation();
            }
         }.bind(this),
      });
   }
   
   // **************************************************************************
         
   onStartDateChanged()
   {
      if (!this.validateFilterDates())
      {
         document.getElementById(Schedule.PageElements.END_DATE_INPUT).value = 
            document.getElementById(Schedule.PageElements.START_DATE_INPUT).value
      }
      
      this.onFilterUpdate();
      
      setSession("schedule.startDate", document.getElementById(Schedule.PageElements.START_DATE_INPUT).value);
   }
   
   onEndDateChanged()
   {
      if (!this.validateFilterDates())
      {
         document.getElementById(Schedule.PageElements.START_DATE_INPUT).value = 
            document.getElementById(Schedule.PageElements.END_DATE_INPUT).value
      }

      this.onFilterUpdate();
      
      setSession("quote.endDate", document.getElementById(Schedule.PageElements.END_DATE_INPUT).value);
   }
   
   onAddButton(jobId)
   {
      let mfgDate = document.getElementById(Schedule.PageElements.START_DATE_INPUT).value;
      
      // AJAX call to delete the component.
      let requestUrl = `/app/page/schedule/?request=save_entry&jobId=${jobId}&mfgDate=${mfgDate}&employeeNumber=0`;
      
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
   }
   
   onDeleteButton(entryId)
   {
      if (confirm("Are you sure you want to remove this job from the schedule?"))
      {
         // AJAX call to delete the component.
         let requestUrl = `/app/page/schedule/?request=delete_entry&entryId=${entryId}`;
         
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
      }
   }
   
   onJobAssigned(entryId, employeeNumber)
   {
      // AJAX call to delete the component.
      let requestUrl = `/app/page/schedule/?request=assign_operator&entryId=${entryId}&employeeNumber=${employeeNumber}`;
      
      ajaxRequest(requestUrl, function(response) {
         if (response.success == true)
         {
            console.log(`Assigned operator [${employeeNumber}] to job.`);
         }
         else
         {
            alert(response.error);
         }
      }.bind(this));

   }
      
   // **************************************************************************
   
   validateFilterDates()
   {
      /*
      let startDate = document.getElementById(Schedule.PageElements.START_DATE_INPUT).value;
      let endDate = document.getElementById(Schedule.PageElements.END_DATE_INPUT).value;
      */
      
      return (true);
   }
   
   onFilterUpdate()
   {
      if (document.readyState === "complete")
      {
         this.updateTables();
      }
   }
   
   updateTables()
   {
      let url = "/app/page/schedule/";
         
      let params = new Object();
      params.request = "fetch";
      params.startDate =  document.getElementById(Schedule.PageElements.START_DATE_INPUT).value;

      this.scheduledTable.setData(url, params)
      .then(function(){
         // Run code after table has been successfuly updated
      })
      .catch(function(error){
         // Handle error loading data
      });

      params.request = "fetchUnassigned";
      
      this.unscheduledTable.setData(url, params)
      .then(function(){
         // Run code after table has been successfuly updated
      })
      .catch(function(error){
         // Handle error loading data
      });
   }
   
   // **************************************************************************
}