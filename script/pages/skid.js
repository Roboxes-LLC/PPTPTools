class Skid
{
   // HTML elements
   static PageElements = {
      "INPUT_FORM":               "input-form",
      "PAN_TICKET_CODE_INPUT":    "pan-ticket-code-input",
      "JOB_NUMBER_INPUT":         "job-number-input",
      "WC_NUMBER_INPUT":          "wc-number-input",
      "SKID_INPUT":               "skid-input",
      // Filter
      "DATE_TYPE_INPUT":          "date-type-input",
      "START_DATE_INPUT":         "start-date-input",
      "END_DATE_INPUT":           "end-date-input",
      "TODAY_BUTTON":             "today-button",
      "YESTERDAY_BUTTON":         "yesterday-button",
      "ACTIVE_SKIDS_INPUT":       "active-skids-input",
   };

   constructor()
   {      
      this.table = null;
      
      this.setup();
   }
   
   setup()
   {
      if (document.getElementById(Skid.PageElements.START_DATE_INPUT))
      {
         document.getElementById(Skid.PageElements.START_DATE_INPUT).addEventListener('change', function() {
            this.onStartDateChanged();
         }.bind(this));
      }
      
      if (document.getElementById(Skid.PageElements.END_DATE_INPUT))
      {
         document.getElementById(Skid.PageElements.END_DATE_INPUT).addEventListener('change', function() {
            this.onEndDateChanged();
         }.bind(this));
      }
      
      if (document.getElementById(Skid.PageElements.TODAY_BUTTON))
      {
         document.getElementById(Skid.PageElements.TODAY_BUTTON).addEventListener('click', function() {
            this.onTodayButton();
         }.bind(this));
      }
      
      if (document.getElementById(Skid.PageElements.YESTERDAY_BUTTON))
      {
         document.getElementById(Skid.PageElements.YESTERDAY_BUTTON).addEventListener('click', function() {
            this.onYesterdayButton();
         }.bind(this));
      }
      
      if (document.getElementById(Skid.PageElements.ACTIVE_SKIDS_INPUT))
      {
         document.getElementById(Skid.PageElements.ACTIVE_SKIDS_INPUT).addEventListener('change', function() {
            this.onActiveSkidsChanged();
         }.bind(this));
      }
      
      if (document.getElementById(Skid.PageElements.JOB_NUMBER_INPUT))
      {
         document.getElementById(Skid.PageElements.JOB_NUMBER_INPUT).addEventListener('change', function() {
            this.onJobNumberChanged();
         }.bind(this));
      }
      
      if (document.getElementById(Skid.PageElements.ACTIVE_SKIDS_INPUT) &&
          document.getElementById(Skid.PageElements.ACTIVE_SKIDS_INPUT).checked)
      {
         disable(Skid.PageElements.DATE_TYPE_INPUT);
         disable(Skid.PageElements.START_DATE_INPUT);
         disable(Skid.PageElements.END_DATE_INPUT);
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
         cellVertAlign:"middle",
         ajaxURL:url,
         ajaxParams:params,
         ajaxResponse:function(url, params, response) {
            let tableData = [];
            if (response.success)
            {
               tableData = response.skids;
            }
            return (tableData);
         },
         //Define Table Columns
         columns:[
            {                     field:"skidId",            visible:false},
            {title:"Ticket",      field:"skidTicketCode",    headerFilter:true, 
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">receipt</i>&nbsp" + cell.getRow().getData().skidTicketCode);
               },
               formatterPrint:function(cell, formatterParams, onRendered){
                  return (cell.getValue());
               }  },
            {title:"Created",     field:"formattedDateTime", headerFilter:true},
            {title:"Author",      field:"authorName",        headerFilter:true},
            {title:"Job",         field:"jobNumber",         headerFilter:true},
            {title:"State",       field:"skidStateLabel",    headerFilter:true},
            {title:"", field:"delete", responsive:0, width:75, print:false,
               formatter:function(cell, formatterParams, onRendered){
                  return ("<i class=\"material-icons icon-button\">delete</i>");
               }
            }
         ],
         initialSort:[
            {column:"formattedDateTime", dir:"asc"}
         ],
         cellClick:function(e, cell){
            var skidId = parseInt(cell.getRow().getData().skidId);

            if (cell.getColumn().getField() == "skidTicketCode")
            {
               document.location = `/skid/printSkidTicket.php?skidId=${skidId}`;
            }
            else if (cell.getColumn().getField() == "delete")
            {
               this.onDeleteButton(skidId);
            }
            else // Any other column
            {
               // Open time card for viewing/editing.
               document.location = "/skid/skid.php?skidId=" + skidId;             
            }                         
         }.bind(this),
      });
   }
   
   onStartDateChanged(updateTable = true)
   {
      if (!this.validateFilterDates())
      {
         document.getElementById(Skid.PageElements.END_DATE_INPUT).value = 
            document.getElementById(Skid.PageElements.START_DATE_INPUT).value
      }
      
      if (updateTable)
      {
         this.onFilterUpdate();
      }
      
      setSession("skid.startDate", document.getElementById(Skid.PageElements.START_DATE_INPUT).value);
   }
   
   onEndDateChanged(updateTable = true)
   {
      if (!this.validateFilterDates())
      {
         document.getElementById(Skid.PageElements.START_DATE_INPUT).value = 
            document.getElementById(Skid.PageElements.END_DATE_INPUT).value
      }

      if (updateTable)
      {
         this.onFilterUpdate();
      }
      
      setSession("skid.endDate", document.getElementById(Skid.PageElements.END_DATE_INPUT).value);
   }
   
   onActiveSkidsChanged(updateTable = true)
   {
      var activeSkids = document.getElementById(Skid.PageElements.ACTIVE_SKIDS_INPUT).checked;
      
      if (activeSkids)
      {
         disable(Skid.PageElements.DATE_TYPE_INPUT);
         disable(Skid.PageElements.START_DATE_INPUT);
         disable(Skid.PageElements.END_DATE_INPUT);
      }
      else
      {
         enable(Skid.PageElements.DATE_TYPE_INPUT);
         enable(Skid.PageElements.START_DATE_INPUT);
         enable(Skid.PageElements.END_DATE_INPUT);
      }
      
      if (updateTable)
      {
         this.onFilterUpdate();
      }
      
      setSession("skid.activeSkids", (activeSkids ? "true" : "false"));
   }
   
   onTodayButton()
   {
      var today = new Date();
         
      document.getElementById(Skid.PageElements.START_DATE_INPUT).value = this.formattedDate(today); 
      document.getElementById(Skid.PageElements.END_DATE_INPUT).value = this.formattedDate(today);
      
      this.onStartDateChanged(false);
      this.onEndDateChanged();
   }

   onYesterdayButton()
   {
      var yesterday = new Date();
      yesterday.setDate(yesterday.getDate() - 1);
      
      document.getElementById(Skid.PageElements.START_DATE_INPUT).value = this.formattedDate(yesterday); 
      document.getElementById(Skid.PageElements.END_DATE_INPUT).value = this.formattedDate(yesterday);
      
      this.onStartDateChanged(false);
      this.onEndDateChanged();
   }
   
   onSaveButton()
   {
      var form = document.querySelector("#" + Skid.PageElements.INPUT_FORM);
      
      var xhttp = new XMLHttpRequest();
   
      // Bind the form data.
      var formData = new FormData(form);
   
      // Define what happens on successful data submission.
      xhttp.addEventListener("load", function(event) {
         try
         {
            var json = JSON.parse(event.target.responseText);
   
            if (json.success == true)
            {
               document.location = "/skid/skids.php";  
            }
            else
            {
               alert(json.error);
            }
         }
         catch (exception)
         {
            if (exception.name == "SyntaxError")
            {
               console.log("JSON syntax error");
               console.log(this.responseText);
            }
            else
            {
               throw(exception);
            }
         }
      });
   
      // Define what happens on successful data submission.
      xhttp.addEventListener("error", function(event) {
        alert('Oops! Something went wrong.');
      });
   
      // Set up our request
      var requestUrl = "/app/page/skid/";
      console.log(requestUrl);
      xhttp.open("POST", requestUrl, true);
   
      // The data sent is what the user provided in the form
      xhttp.send(formData);
   }
   
   onNewButton()
   {
      document.location = "/skid/skid.php?skidId=" + UNKNOWN_SKID_ID;
   }
   
   onDeleteButton(skidId)
   {
      if (confirm("Are you sure you want to delete this skid?"))
      {
         // AJAX call to delete skid.
         let requestUrl = "/app/page/skid/?request=delete_skid&skidId=" + skidId;
         
         var xhttp = new XMLHttpRequest();
         xhttp.onreadystatechange = function()
         {
            if (this.readyState == 4 && this.status == 200)
            {
               try
               {
                  var json = JSON.parse(this.responseText);
                  
                  if (json.success == true)
                  {
                     location.href = "/skid/skids.php";
                  }
                  else
                  {
                     alert(json.error);
                  }
               }
               catch (exception)
               {
                  if (exception.name == "SyntaxError")
                  {
                     console.log("JSON syntax error");
                     console.log(this.responseText);
                  }
                  else
                  {
                     throw(exception);
                  }
               }
            }
         };
         xhttp.open("GET", requestUrl, true);
         xhttp.send(); 
      }
   }
   
   onCancelButton()
   {
      if (!isFormChanged("input-form") ||
          confirm("Are you sure?  All data will be lost."))
      {
         window.history.back();
      }
   }
   
   onJobNumberChanged()
   {
      let jobNumber = document.getElementById(Skid.PageElements.JOB_NUMBER_INPUT).value;
      
      if (jobNumber == null)
      {
         disable(Skid.PageElements.WC_NUMBER_INPUT);
      }
      else
      {
         enable(Skid.PageElements.WC_NUMBER_INPUT);
         
         // Populate WC numbers based on selected job number.
         
         // AJAX call to populate WC numbers based on selected job number.
         let requestUrl = "/api/wcNumbers/?jobNumber=" + jobNumber;
         
         var xhttp = new XMLHttpRequest();
         xhttp.updateWcOptions = this.updateWcOptions;
         xhttp.onreadystatechange = function()
         {
            if (this.readyState == 4 && this.status == 200)
            {
               try
               {
                  let json = JSON.parse(this.responseText);
                  
                  if (json.success == true)
                  {
                     this.updateWcOptions(json.wcNumbers);               
                  }
                  else
                  {
                     console.log("API call to retrieve WC numbers failed.");
                  }
               }
               catch (exception)
               {
                  if (exception.name == "SyntaxError")
                  {
                     console.log("JSON syntax error");
                     console.log(this.responseText);
                  }
                  else
                  {
                     throw(exception);
                  }
               }
            }
         }
         xhttp.open("GET", requestUrl, true);
         xhttp.send();  
      }
   }
   
   onBarcode(barcode)
   {
      // AJAX call to retrieve skid from skid ticket code.
      let requestUrl = `/app/page/skid/?skidTicketCode=${barcode}`;
      
      var xhttp = new XMLHttpRequest();
      xhttp.updateWcOptions = this.updateWcOptions;
      xhttp.onreadystatechange = function()
      {
         if (this.readyState == 4 && this.status == 200)
         {
            try
            {
               let json = JSON.parse(this.responseText);
               
               if (json.success == true)
               {
                  document.location = `/skid/skid.php?skidId=${json.skid.skidId}`;            
               }
               else
               {
                  alert(`Failed to find skid for code \"${barcode}\"`);
               }
            }
            catch (exception)
            {
               if (exception.name == "SyntaxError")
               {
                  console.log("JSON syntax error");
                  console.log(this.responseText);
               }
               else
               {
                  throw(exception);
               }
            }
         }
      }
      xhttp.open("GET", requestUrl, true);
      xhttp.send();  
   }
   
   // **************************************************************************
   
   getSkidId()
   {
      return (document.querySelector("#" + Skid.PageElements.SKID_ID_INPUT).value);
   }
   
   getTableQuery()
   {
      return ("../app/page/skid/");
   }

   getTableQueryParams()
   {      
      let params = new Object();
      params.request = "fetch";
      params.startDate =  document.getElementById(Skid.PageElements.START_DATE_INPUT).value;
      params.endDate =  document.getElementById(Skid.PageElements.END_DATE_INPUT).value;
      params.activeSkids =  document.getElementById(Skid.PageElements.ACTIVE_SKIDS_INPUT).checked;

      return (params);
   }
   
   validateFilterDates()
   {
      let startDate = document.getElementById(Skid.PageElements.START_DATE_INPUT).value;
      let endDate = document.getElementById(Skid.PageElements.END_DATE_INPUT).value;
      
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
   
   updateWcOptions(wcNumbers)
   {
      let element = document.getElementById(Skid.PageElements.WC_NUMBER_INPUT);
      
      while (element.firstChild)
      {
         element.removeChild(element.firstChild);
      }
   
      for (var wcNumber of wcNumbers)
      {
         var option = document.createElement('option');
         option.innerHTML = wcNumber.label;
         option.value = wcNumber.wcNumber;
         element.appendChild(option);
      }
      
      element.value = null;
   }
   
   formattedDate(date)
   {
      // Convert to Y-M-D format, per HTML5 Date control.
      // https://stackoverflow.com/questions/12346381/set-date-in-input-type-date
      var day = ("0" + date.getDate()).slice(-2);
      var month = ("0" + (date.getMonth() + 1)).slice(-2);
      
      var formattedDate = date.getFullYear() + "-" + (month) + "-" + (day);
   
      return (formattedDate);
   }
}