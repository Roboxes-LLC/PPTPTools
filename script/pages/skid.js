class Skid
{
   // HTML elements
   static PageElements = {
      "INPUT_FORM":               "input-form",
      "DEFECT_ID_INPUT":          "defect-id-input",
      "START_DATE_INPUT":         "start-date-input",
      "END_DATE_INPUT":           "end-date-input",
      "UNRESOLVED_DEFECTS_INPUT": "unresolved-defects-input",
      "CLOSE_BUTTON":             "close-button",
      "SAVE_BUTTON" :             "save-button",
      "DELETE_BUTTON" :           "delete-button",
      "CONFIRM_DELETE_MODAL":     "confirm-delete-modal",
      "CANCEL_DELETE_BUTTON":     "cancel-delete_button",
      "CONFIRM_DELETE_BUTTON":    "confirm-delete-button"
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
            {title:"Skid Ticket", field:"skidCode",          headerFilter:true},
            {title:"Created",     field:"formattedDateTime", headerFilter:true},
            {title:"Job",         field:"jobNumber",         headerFilter:true},
            {title:"State",       field:"skidStateLabel",    headerFilter:true},
         ],
         initialSort:[
            {column:"formattedDateTime", dir:"asc"}
         ],
         rowClick:function(e, row){
            var skidId = row.getData().skidId;
            document.location = "/skid.php?skidId=" + skidId;               
         }.bind(this),
      });
   }
   
   onStartDateChanged()
   {
      if (!this.validateFilterDates())
      {
         document.getElementById(Skid.PageElements.END_DATE_INPUT).value = 
            document.getElementById(Skid.PageElements.START_DATE_INPUT).value
      }
      
      this.onFilterUpdate();
      
      setSession("defect.startDate", document.getElementById(Skid.PageElements.START_DATE_INPUT).value);
   }
   
   onEndDateChanged()
   {
      if (!this.validateFilterDates())
      {
         document.getElementById(Skid.PageElements.START_DATE_INPUT).value = 
            document.getElementById(Skid.PageElements.END_DATE_INPUT).value
      }

      this.onFilterUpdate();
      
      setSession("defect.endDate", document.getElementById(Skid.PageElements.END_DATE_INPUT).value);
   }
   
   /*
   onCloseButton()
   {
      document.location = "/defects.php";   
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
               document.location = "/defects.php";  
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
      var requestUrl = "/app/page/defect/";
      console.log(requestUrl);
      xhttp.open("POST", requestUrl, true);
   
      // The data sent is what the user provided in the form
      xhttp.send(formData);
   }
   */
   
   onNewButton()
   {
      document.location = "/skid/skid.php?skidId=" + UNKNOWN_SKID_ID;
   }
   
   /*
   onDeleteButton()
   {
      showModal(Skid.PageElements.CONFIRM_DELETE_MODAL, "block");
   }
   
   onCancelDeleteButton()
   {
      hideModal(Skid.PageElements.CONFIRM_DELETE_MODAL);
   }
   
   onConfirmDeleteButton()
   {
      hideModal(Skid.PageElements.CONFIRM_DELETE_MODAL);
      
      var defectId = this.getSkidId();
      
      // AJAX call to delete the componen.
      var requestUrl = "/app/page/defect/?request=delete_defect&defectId=" + defectId;
      
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
                  location.href = "defects.php";
               }
               else
               {
                  console.log("Call to delete item failed.");
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
   */
   
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
      //params.startDate =  document.getElementById(Skid.PageElements.START_DATE_INPUT).value;
      //params.endDate =  document.getElementById(Skid.PageElements.END_DATE_INPUT).value;

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
}