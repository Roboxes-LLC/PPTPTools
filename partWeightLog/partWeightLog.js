function onSavePartWeightEntry()
{
   if (validatePartWeightEntry())
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
               location.href = "partWeightLog.php";
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
      requestUrl = "../api/savePartWeightEntry/"
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

function onDeletePartWeightEntry(partWeightEntryId)
{
   if (confirm("Are you sure you want to delete this log entry?"))
   {
      // AJAX call to delete part weight entry.
      requestUrl = "../api/deletePartWeightEntry/?entryId=" + partWeightEntryId;
      
      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function()
      {
         if (this.readyState == 4 && this.status == 200)
         {
            var json = JSON.parse(this.responseText);
            
            if (json.success == true)
            {
               location.href = "partWeightLog.php";
            }
            else
            {
               console.log("API call to delete part weight entry failed.");
               alert(json.error);
            }
         }
      };
      xhttp.open("GET", requestUrl, true);
      xhttp.send(); 
   }
}

function onPanTicketCodeChange()
{
   var panTicketCode = document.getElementById("pan-ticket-code-input").value;
   
   // Clear fields.
   clear("job-number-input");
   clear("wc-number-input");
   clear("manufacture-date-input");
   clear("operator-input");
   // Customer requested these fields be preserved.  10/23
   //clear("pan-count-input");
   //clear("part-weight-input");   
   sampleWeight = 0.0;  // global variable
   
   if (panTicketCode == "")
   {
      // Enable fields.
      enable("job-number-input");
      enable("manufacture-date-input");
      enable("today-button");
      enable("yesterday-button");
      enable("operator-input");
      
      // Disable WC number, as it's dependent on the job number.
      disable("wc-number-input");
      
      // AJAX call to retrieve active jobs.
      requestUrl = "../api/jobs/?onlyActive=true";
      
      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function()
      {
         if (this.readyState == 4 && this.status == 200)
         {
            var json = JSON.parse(this.responseText);
            
            if (json.success == true)
            {
               updateJobOptions(json.jobs);                   
            }
            else
            {
               console.log("API call to retrieve jobs failed.");
            }
         }
      };
      xhttp.open("GET", requestUrl, true);
      xhttp.send(); 
      
      // AJAX call to retrieve operators.
      requestUrl = "../api/users/?role=3";  // OPERATOR
      
      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function()
      {
         if (this.readyState == 4 && this.status == 200)
         {
            var json = JSON.parse(this.responseText);
            
            if (json.success == true)
            {
               updateOperatorOptions(json.operators);                   
            }
            else
            {
               console.log("API call to retrieve users failed.");
            }
         }
      };
      xhttp.open("GET", requestUrl, true);
      xhttp.send(); 
   }
   else
   {
      // Disable fields.
      disable("job-number-input");
      disable("wc-number-input");
      disable("manufacture-date-input");
      disable("today-button");
      disable("yesterday-button");
      disable("operator-input");
      
      // AJAX call to populate input fields based on pan ticket selection.
      requestUrl = "../api/timeCardInfo/?panTicketCode=" + panTicketCode + "&expandedProperties=true";
      
      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function()
      {
         if (this.readyState == 4 && this.status == 200)
         {
            var json = JSON.parse(this.responseText);
            
            if (json.success == true)
            {
               sampleWeight = json.sampleWeight;  // global variable
               
               updateTimeCardInfo(json.timeCardInfo, json.jobNumber, json.wcNumber, json.wcLabel, json.operatorName, json.sampleWeight);
            }
            else
            {
               console.log("API call to retrieve time card info failed.");
               
               // Invalidate time card input.
               document.getElementById("pan-ticket-code-input").validator.color("#FF0000");
            }
         }
      };
      xhttp.open("GET", requestUrl, true);
      xhttp.send();      
   }
}

function onJobNumberChange()
{
   jobNumber = document.getElementById("job-number-input").value;
   
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
      };
      xhttp.open("GET", requestUrl, true);
      xhttp.send();  
   }
}

function onWcNumberChange()
{
   var jobNumber = document.getElementById("job-number-input").value;
   var wcNumber = document.getElementById("wc-number-input").value;
      
   // Lookup sample weight based on selected job.
   
   // AJAX call to populate WC numbers based on selected job number.
   requestUrl = "../api/jobInfo/?jobNumber=" + jobNumber + "&wcNumber=" + wcNumber;
   
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
               sampleWeight = json.jobInfo.sampleWeight;          
            }
            else
            {
               console.log("API call to retrieve job info failed.");
               
               sampleWeight = 0.0;
            }
            
            updateCalculatedPartCount();
         }
         catch (exception)
         {
            console.log("JSON syntax error");
            console.log(this.responseText);
         }
      }
   };
   xhttp.open("GET", requestUrl, true);
   xhttp.send();  
}

function onTodayButton()
{
   var today = new Date();
      
   document.querySelector('#manufacture-date-input').value = formattedDate(today); 
}

function onYesterdayButton()
{
   var yesterday = new Date();
   yesterday.setDate(yesterday.getDate() - 1);
   
   document.querySelector('#manufacture-date-input').value = formattedDate(yesterday); 
}

function onLinkButton()
{
   if (document.getElementById("job-number-input").validator.validate() &&
       document.getElementById("wc-number-input").validator.validate() &&
       document.getElementById("operator-input").validator.validate() &&
       document.getElementById("manufacture-date-input").value != null)
   {
      var jobNumber = document.getElementById("job-number-input").value;
      var wcNumber = document.getElementById("wc-number-input").value;
      var operator = document.getElementById("operator-input").value;
      var manufactureDate = document.getElementById("manufacture-date-input").value;
      
      // AJAX call to populate WC numbers based on selected job number.
      requestUrl = "../api/timeCardInfo/?jobNumber=" + jobNumber + "&wcNumber=" + wcNumber + "&operator=" + operator + "&manufactureDate=" + manufactureDate + "&expandedProperties=true";
      
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
                  var timeCardId = json.timeCardInfo.timeCardId;
                  var panTicketCode = json.panTicketCode;     
                  
                  // Double check we have a valid time card.
                  if (timeCardId != 0)
                  {                  
                     alert("Linking entry to pan ticket " + panTicketCode + ".");
                     
                     set("pan-ticket-code-input", panTicketCode);
                     
                     onPanTicketCodeChange()
                  }
                  else
                  {
                     alert("No matching time card could be found.");
                  }
               }
               else
               {
                  alert("No matching time card could be found.");
               }
            }
            catch (exception)
            {
               console.log("JSON syntax error");
               console.log(this.responseText);
            }
         }
      };
      xhttp.open("GET", requestUrl, true);
      xhttp.send();
   }
   else
   {
      alert("Enter valid time card properties below before linking.")
   }    
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

function updateJobOptions(jobNumbers)
{
   element = document.getElementById("job-number-input");
   
   while (element.firstChild)
   {
      element.removeChild(element.firstChild);
   }

   for (var jobNumber of jobNumbers)
   {
      var option = document.createElement('option');
      option.innerHTML = jobNumber;
      option.value = jobNumber;
      element.appendChild(option);
   }
   
   element.value = null;
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

function updateOperatorOptions(operators)
{
   element = document.getElementById("operator-input");
   
   while (element.firstChild)
   {
      element.removeChild(element.firstChild);
   }

   for (var operator of operators)
   {
      var option = document.createElement('option');
      option.innerHTML = operator.employeeNumber + " - " + operator.name;
      option.value = operator.employeeNumber;
      element.appendChild(option);
   }
   
   element.value = null;
}

function twoDigitNumber(value)
{
   return (("0" + value).slice(-2));
}

function updateTimeCardInfo(timeCardInfo, jobNumber, wcNumber, wcLabel, operatorName)
{
   var operator = timeCardInfo.employeeNumber;
   var date = new Date(timeCardInfo.dateTime);
   var manufactureDate = formattedDate(date);
   var panCount = timeCardInfo.panCount;
   
   updateJobOptions(new Array(jobNumber));
   updateWcOptions(new Array({wcNumber: wcNumber, label:wcLabel}));
   updateOperatorOptions(new Array({employeeNumber: operator, name:operatorName}));
   
   set("job-number-input", jobNumber);
   set("wc-number-input", wcNumber);
   set("operator-input", operator);
   set("manufacture-date-input", manufactureDate);
   
   updateCalculatedPartCount();
}

function getBaseWeight()
{
   var panCount = parseInt(document.getElementById('pan-count-input').value);
   var panWeight = parseFloat(document.getElementById('pan-weight-input').value);
   var palletWeight = parseFloat(document.getElementById('pallet-weight-input').value);
   
   var baseWeight = ((panCount * panWeight) + palletWeight);

   return (baseWeight);
}

function updateCalculatedPartCount()
{
   var panCount = parseInt(document.getElementById('pan-count-input').value);
   var partWeight = parseFloat(document.getElementById('part-weight-input').value);
  
   var partCount = 0;
   
   if ((sampleWeight > 0) &&
       (panCount > 0) &&
       (partWeight > 0))       
   {
      partCount = ((partWeight - getBaseWeight()) / sampleWeight);
      
      partCount = Math.round(partCount);
   }
   
   document.getElementById('part-count-input').value = ((partCount > 0) ? partCount : "");
   
   return (partCount) 
}

function formattedDate(date)
{
   // Convert to Y-M-D format, per HTML5 Date control.
   // https://stackoverflow.com/questions/12346381/set-date-in-input-type-date
   var day = ("0" + date.getDate()).slice(-2);
   var month = ("0" + (date.getMonth() + 1)).slice(-2);
   
   var formattedDate = date.getFullYear() + "-" + (month) + "-" + (day);

   return (formattedDate);
}

function validatePartWeightEntry()
{
   valid = false;
   
   $validPanTicketCode = (document.getElementById("pan-ticket-code-input").validator.validate() &&
                          (document.getElementById("pan-ticket-code-input").style.color != "#FF0000"));

   if (!$validPanTicketCode)
   {
      alert("Please enter a valid pan ticket code.");    
   }
   else if (!(document.getElementById("job-number-input").validator.validate()))
   {
      alert("Start by selecting a valid pan ticket or active job.");    
   }
   else if (!(document.getElementById("wc-number-input").validator.validate()))
   {
      alert("Please select a work center.");    
   }
   else if (isNaN(Date.parse(document.getElementById("manufacture-date-input").value)))
   {
      alert("Please enter a valid manufacture date.");    
   }
   else if (!(document.getElementById("operator-input").validator.validate()))
   {
      alert("Please select an operator.");    
   }
   else if (!(document.getElementById("laborer-input").validator.validate()))
   {
      alert("Please select a laborer.");    
   }
   else if (!(document.getElementById("pan-count-input").validator.validate()))
   {
      alert("Please enter a valid basket count.");
   }
   else if (parseFloat(document.getElementById("part-weight-input").value) < getBaseWeight())
   {
      alert("Please enter a part weight greater than the base pallet/pan weight.");
   }
   else if (!(document.getElementById("part-weight-input").validator.validate()))
   {
      alert("Please enter a valid part weight.");
   }
   else
   {
      valid = true;
   }
   
   return (valid);   
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
            document.location = "partWeightLogEntry.php?timeCardId=" + json.timeCardInfo.timeCardId;
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