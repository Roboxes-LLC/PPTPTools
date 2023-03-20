const SETUP_TIME = 0;
const RUN_TIME = 1;

function hide(elementId)
{
   document.getElementById(elementId).style.display = "none";
}

function show(elementId)
{
   document.getElementById(elementId).style.display = "block";
}

function set(elementId, value)
{
   document.getElementById(elementId).value = value;
}

function clear(elementId)
{
   document.getElementById(elementId).value = null;
}

function enable(elementId)
{
   document.getElementById(elementId).disabled = false;
}

function disable(elementId)
{
   document.getElementById(elementId).disabled = true;
}

function formatToTwoDigits(value)
{
   return (("0" + value).slice(-2));
}

function onOperatorChange()
{
   let employeeNumber = parseInt(document.getElementById("operator-input").value);
   
   // AJAX call to fetch user info.
   requestUrl = "../api/user/?employeeNumber=" + employeeNumber;

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
               updateShiftTime(json.user.defaultShiftHours);
               updateRunTime(json.user.defaultShiftHours);               
            }
            else
            {
               console.log("API call to retrieve user info failed.");
            }
         }
         catch (expection)
         {
            console.log("JSON syntax error");
            console.log(this.responseText);
         }               
      }
   };
   xhttp.open("GET", requestUrl, true);
   xhttp.send();  
}

function onJobNumberChange()
{
   jobNumber = document.getElementById("job-number-input").value;
   
   clear("wc-number-input");
   
   updateGrossPartsPerHour();
   
   if (jobNumber == null)
   {
      disable("wc-number-input");
   }
   else
   {
      enable("wc-number-input");
      
      // Populate WC numbers based on selected job number.
      
      // AJAX call to populate WC numbers based on selected job number.
      requestUrl = "../api/wcNumbers/?jobNumber=" + jobNumber;
      
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
                  updateWcOptions(json.wcNumbers);               
               }
               else
               {
                  console.log("API call to retrieve WC numbers failed.");
               }
            }
            catch (expection)
            {
               console.log("JSON syntax error");
               console.log(this.responseText);
            }               
         }
      };
      xhttp.open("GET", requestUrl, true);
      xhttp.send();  
   }
}

function updateWcOptions(wcNumbers)
{
   element = document.getElementById("wc-number-input");
   
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

function onWcNumberChange()
{
   updateGrossPartsPerHour();
   
   updateMaintenanceLogTable();
}

function onShiftTimeChange()
{
   var hours = parseInt(document.getElementById("shift-time-hour-input").value);
   var minutes = parseInt(document.getElementById("shift-time-minute-input").value);
   
   var shiftTime = ((hours * 60) + minutes);
   
   document.getElementById("shift-time-input").value = shiftTime;
   
   document.getElementById("shift-time-minute-input").value = formatToTwoDigits(minutes);
   
   updateApproval(RUN_TIME);
}

function onRunTimeChange()
{
   var hours = parseInt(document.getElementById("run-time-hour-input").value);
   var minutes = parseInt(document.getElementById("run-time-minute-input").value);
   
   var runTime = ((hours * 60) + minutes);
   
   document.getElementById("run-time-input").value = runTime;
   
   document.getElementById("run-time-minute-input").value = formatToTwoDigits(minutes);
   
   document.getElementById("run-time-approved-by-input").value = 0;
   
   updateEfficiency();
   
   updateApproval(RUN_TIME);
}

function onSetupTimeChange()
{
   var hours = parseInt(document.getElementById("setup-time-hour-input").value);
   var minutes = parseInt(document.getElementById("setup-time-minute-input").value);
   
   var setupTime = ((hours * 60) + minutes);
   
   document.getElementById("setup-time-input").value = setupTime;
   
   document.getElementById("setup-time-minute-input").value = formatToTwoDigits(minutes);
   
   document.getElementById("setup-time-approved-by-input").value = 0;
   
   updateApproval(SETUP_TIME);
}

function onPartCountChange()
{
   updateEfficiency();
}

function updateShiftTime(shiftHours)
{
   document.getElementById("shift-time-hour-input").value = shiftHours;
   document.getElementById("shift-time-minute-input").value = 0;
   
   onShiftTimeChange();
}

function updateRunTime(runHours)
{
   document.getElementById("run-time-hour-input").value = runHours;
   document.getElementById("run-time-minute-input").value = 0;
   
   onRunTimeChange();
}

function updateGrossPartsPerHour()
{
   var jobNumber = document.getElementById("job-number-input").value;
   var wcNumber = parseInt(document.getElementById("wc-number-input").value);
   
   var grossPartsPerHour = 0;
   
   if ((jobNumber != "") && !isNaN(wcNumber) && (wcNumber != 0))
   {
      // Populate gross parts per hour based on selected job.
      
      // AJAX call to retrieve gross parts per hour by selected job.
      requestUrl = "../api/grossPartsPerHour/?jobNumber=" + jobNumber + "&wcNumber=" + wcNumber;
      
      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function()
      {
         if (this.readyState == 4 && this.status == 200)
         {
            var grossPartsPerHour = 0;
            
            try
            {
               var json = JSON.parse(this.responseText);
               
               if (json.success == true)
               {
                  grossPartsPerHour = json.grossPartsPerHour;      
               }
               else
               {
                  console.log("API call to retrieve gross parts-per-hour failed.");
               }
            }
            catch (expection)
            {
               console.log("JSON syntax error");
               console.log(this.responseText);
            }
            
            document.getElementById("gross-parts-per-hour-input").value = grossPartsPerHour;

            updateEfficiency();
         }
      };
      xhttp.open("GET", requestUrl, true);
      xhttp.send();
   }
   else
   {
      document.getElementById("gross-parts-per-hour-input").value = 0;  

      updateEfficiency();
   }  
}

function updateEfficiency()
{
   var runTimeHourInput = document.getElementById("run-time-hour-input");
   var runTimeMinuteInput = document.getElementById("run-time-minute-input");
   var partCountInput = document.getElementById("part-count-input");
   var grossPartsPerHourInput = document.getElementById("gross-parts-per-hour-input");
   var efficiencyInput = document.getElementById("efficiency-input");
   
   if (runTimeHourInput.validator.isValid() && 
       runTimeMinuteInput.validator.isValid() &&
       partCountInput.validator.isValid())
   {
      var runTimeMinutes = ((parseInt(runTimeHourInput.value) * 60) + parseInt(runTimeMinuteInput.value));
      
      var partCount = partCountInput.value;
      
      var grossPartsPerHour = parseInt(grossPartsPerHourInput.value);
      
      if (grossPartsPerHour > 0)
      {
         var potentialParts = ((runTimeMinutes / 60) * grossPartsPerHour);
         
         if (potentialParts > 0)
         {
            var efficiency = ((partCount / potentialParts) * 100);
            
            efficiencyInput.value = efficiency.toFixed(2);
         }
      }
      else
      {
         clear("efficiency-input");
      }
   }
   else
   {
      clear("efficiency-input");
   }
}

function onSaveTimeCard()
{
   if (validateTimeCard())
   {
      var form = document.querySelector('#input-form');
      
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
               location.href = "viewTimeCards.php";
            }
            else
            {
               alert(json.error);
            }
         }
         catch (expection)
         {
            console.log("JSON syntax error");
            console.log(this.responseText);
         }
      });
   
      // Define what happens on successful data submission.
      xhttp.addEventListener("error", function(event) {
        alert('Oops! Something went wrong.');
      });
   
      // Set up our request
      requestUrl = "../api/saveTimeCard/"
      xhttp.open("POST", requestUrl, true);
   
      // The data sent is what the user provided in the form
      xhttp.send(formData);
   }
}

function onCancel()
{
   if (!isFormChanged("input-form") ||
       confirm("Are you sure?  All data will be lost."))
   {
      window.history.back();
   }
}

function onDeleteTimeCard(timeCardId)
{
   if (confirm("Are you sure you want to delete this time card?"))
   {
      // AJAX call to delete part weight entry.
      requestUrl = "../api/deleteTimeCard/?timeCardId=" + timeCardId;
      
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
                  location.href = "viewTimeCards.php";
               }
               else
               {
                  console.log("API call to delete part weight entry failed.");
                  alert(json.error);
               }
            }
            catch (expection)
            {
               console.log("JSON syntax error");
               console.log(this.responseText);
            }
         }
      };
      xhttp.open("GET", requestUrl, true);
      xhttp.send(); 
   }
}

function validateTimeCard()
{
   valid = false;

   if (!(document.getElementById("operator-input").validator.validate()))
   {
      alert("Please select an operator.");    
   }
   else if (!(document.getElementById("job-number-input").validator.validate()))
   {
      alert("Please select an active job.");    
   }
   else if (!(document.getElementById("wc-number-input").validator.validate()))
   {
      alert("Please select a work center.");    
   }
   else if (!(document.getElementById("material-number-input").validator.validate()))
   {
      alert("Please enter a valid heat number.");    
   }
   else if (!(document.getElementById("shift-time-hour-input").validator.validate() &&
              document.getElementById("shift-time-minute-input").validator.validate()))
   {
      alert("Please enter a valid shift time.")      
   }
   else if (!(document.getElementById("run-time-hour-input").validator.validate() &&
              document.getElementById("run-time-minute-input").validator.validate()))
   {
      alert("Please enter a valid run time.")      
   }
   else if (!(document.getElementById("setup-time-hour-input").validator.validate() &&
              document.getElementById("setup-time-minute-input").validator.validate()))
   {
      alert("Please enter a valid setup time.")
   }
   // J. Orbin requested that users be able enter incomplete time sheets.  (11/21/2019)
   /*
   else if ((document.getElementById("setup-time-hour-input").value == 0) &&
            (document.getElementById("shift-time-hour-input").value == 0) &&
            (document.getElementById("setup-time-minute-input").value == 0) &&
            (document.getElementById("run-time-hour-input").value == 0) &&
            (document.getElementById("run-time-minute-input").value == 0))
   {
      alert("Please enter some valid times.")  
   }
   */
   else if (!(document.getElementById("pan-count-input").validator.validate()))
   {
      alert("Please enter a valid basket count.");    
   }
   else if (!(document.getElementById("part-count-input").validator.validate()))
   {
      alert("Please enter a valid part count.");    
   }
   else if (!(document.getElementById("scrap-count-input").validator.validate()))
   {
      alert("Please enter a valid scrap count.");    
   }
   // J. Orbin requested that users be able enter incomplete time sheets.  (11/21/2019)
   /*
   else if ((document.getElementById("part-count-input").value == 0) &&
            (document.getElementById("scrap-count-input").value == 0))
   {
      alert("Please enter some part counts.");   
   }
   */
   // J. Orbin requested that users be forced to enter comments if a valid run time is less than shift time.  (5/27/2021)
   else if ((getMinutes(RUN_TIME) > 0) && requiresApproval(RUN_TIME) && !hasCodes() && !hasComments())
   {
      alert("Please provide an explanation for your reduced run time using the Codes or Comments section.");
   }
   else
   {
      valid = true;
   }
   
   return (valid);   
}

function getInputPrefix(approvalType)
{
   var prefix = "";
   
   if (approvalType == SETUP_TIME)
   {
      prefix = "setup-time";
   }
   else if (approvalType == RUN_TIME)
   {
      prefix = "run-time";
   }
   
   return (prefix);
}

function getMinutes(approvalType)
{
   var minutes = 0;
   
   var approvePrefix = getInputPrefix(approvalType);
   
   var hourInput = document.getElementById(approvePrefix + "-hour-input");
   var minuteInput = document.getElementById(approvePrefix + "-minute-input");
   
   if (hourInput.validator.isValid() && minuteInput.validator.isValid())
   {
      minutes = ((parseInt(hourInput.value) * 60) + parseInt(minuteInput.value));
   }
   
   return (minutes);
}

function hasCodes()
{
   var hasCodes = false;
   var elements = document.getElementsByClassName("comment-checkbox");
   
   for (element of elements)
   {
      if (element.checked)
      {
         hasCodes = true;
         break;
      }
   }
   
   return (hasCodes);
}

function hasComments()
{
   return ((document.getElementById("comments-input").value != null) &&
           (document.getElementById("comments-input").value != ""));
}

function requiresApproval(approvalType)
{
   var inputRequiresApproval = false;
   
   var approvePrefix = getInputPrefix(approvalType);
   
   var hourInput = document.getElementById(approvePrefix + "-hour-input");
   var minuteInput = document.getElementById(approvePrefix + "-minute-input");  
   
   if (hourInput.validator.isValid() && 
       minuteInput.validator.isValid())
   {
      var minutes = ((parseInt(hourInput.value) * 60) + parseInt(minuteInput.value));
      
      if (approvalType == SETUP_TIME)
      {
         inputRequiresApproval = (minutes > 0);         
      }
      else if (approvalType == RUN_TIME)
      {
         var shiftTimeMinutes =
            ((parseInt(document.getElementById('shift-time-hour-input').value) * 60) +
             parseInt(document.getElementById('shift-time-minute-input').value));
             
         inputRequiresApproval = (minutes < shiftTimeMinutes);         
      }
   }
   
   return (inputRequiresApproval);

}

function updateApproval(approvalType)
{
   var inputRequiresApproval = requiresApproval(approvalType);
   
   var approvePrefix = getInputPrefix(approvalType);

   var isApproved = document.getElementById(approvePrefix + "-approved-by-input").value != 0;
   
   // Start by hiding everything.
   hide(approvePrefix + "-approve-button");
   hide(approvePrefix + "-approved-text");
   hide(approvePrefix + "-unapprove-button");
   hide(approvePrefix + "-unapproved-text");
   
   if (inputRequiresApproval)
   {
      if (isApproved)
      {
         if (userCanApprove())
         {
            show(approvePrefix + "-unapprove-button");
         }

         show(approvePrefix + "-approved-text");
      }
      else
      {
         if (userCanApprove())
         {
            show(approvePrefix + "-approve-button");
         }

         show(approvePrefix + "-unapproved-text");
      }
   }
}

function approveRunTime(approvedBy)
{
   document.getElementById("run-time-approved-by-input").value = approvedBy;
   
   updateApproval(RUN_TIME);
}

function unapproveRunTime(approvedBy)
{
   document.getElementById("run-time-approved-by-input").value = 0;
   
   updateApproval(RUN_TIME);
}

function approveSetupTime(approvedBy)
{
   document.getElementById("setup-time-approved-by-input").value = approvedBy;
   
   updateApproval(SETUP_TIME);
}

function unapproveSetupTime(approvedBy)
{
   document.getElementById("setup-time-approved-by-input").value = 0;
   
   updateApproval(SETUP_TIME);
}

function updateRunTimeApproval(timeCardId, isApproved)
{
   // AJAX call to approve run time.
   var isApprovedString = (isApproved ? "true" : "false");
   requestUrl = "../api/approveRunTime/?timeCardId=" + timeCardId + "&isApproved=" + isApprovedString;
      
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
               table.updateData([{timeCardId:timeCardId, runTime:json.runTime, runTimeApprovedBy:json.runTimeApprovedBy, runTimeApprovedByName:json.runTimeApprovedByName}])
               .then(function(){
                  table.getRow(timeCardId).reformat();
               })
               .catch(function(error){
                  //handle error updating data
               });
            }
            else
            {
               console.log("API call to approve run time failed.");
               alert(json.error);
            }
         }
         catch (expection)
         {
            console.log("JSON syntax error");
            console.log(this.responseText);
         }
      }
   };
   xhttp.open("GET", requestUrl, true);
   xhttp.send(); 
}

function updateSetupTimeApproval(timeCardId, isApproved)
{
   // AJAX call to approve run time.
   var isApprovedString = (isApproved ? "true" : "false");
   requestUrl = "../api/approveSetupTime/?timeCardId=" + timeCardId + "&isApproved=" + isApprovedString;
      
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
               table.updateData([{timeCardId:timeCardId, setupTime:json.setupTime, setupTimeApprovedBy:json.setupTimeApprovedBy, setupTimeApprovedByName:json.setupTimeApprovedByName}])
               .then(function(){
                  table.getRow(timeCardId).reformat();
               })
               .catch(function(error){
                  //handle error updating data
               });
            }
            else
            {
               console.log("API call to approve run time failed.");
               alert(json.error);
            }
         }
         catch (expection)
         {
            console.log("JSON syntax error");
            console.log(this.responseText);
         }
      }
   };
   xhttp.open("GET", requestUrl, true);
   xhttp.send(); 
}

function onBarcode(barcode)
{
   // AJAX call to retrieve time card info from pan ticket.
   requestUrl = "../api/timeCardInfo/?panTicketCode=" + barcode + "&expandedProperties=true";
   
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         var json = JSON.parse(this.responseText);
         
         if (json.success == true)
         {
            document.location = "../panTicket/viewPanTicket.php?panTicketId=" + barcode;
         }
         else
         {
            alert("Invalid pan ticket code: " + barcode);
         }
      }
   };
   xhttp.open("GET", requestUrl, true);
   xhttp.send();
}
   