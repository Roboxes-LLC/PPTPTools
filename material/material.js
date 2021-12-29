function onSaveMaterialEntry()
{
   if (validateMaterialEntry())
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
               location.href = "viewMaterials.php";
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
      requestUrl = "../api/saveMaterialEntry/"
      xhttp.open("POST", requestUrl, true);
   
      // The data sent is what the user provided in the form
      xhttp.send(formData);
   }
}

function onIssueMaterialEntry()
{
   if (validateMaterialEntry())
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
               location.href = "viewMaterials.php";
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
      requestUrl = "../api/issueMaterialEntry/"
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

function onDeleteMaterialEntry(materialEntryId)
{
   if (confirm("Are you sure you want to delete this material?"))
   {
      // AJAX call to delete part weight entry.
      requestUrl = "../api/deleteMaterialEntry/?entryId=" + materialEntryId;
      
      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function()
      {
         if (this.readyState == 4 && this.status == 200)
         {
            var json = JSON.parse(this.responseText);
            
            if (json.success == true)
            {
               location.href = "viewMaterials.php";
            }
            else
            {
               console.log("API call to delete material failed.");
               alert(json.error);
            }
         }
      };
      xhttp.open("GET", requestUrl, true);
      xhttp.send(); 
   }
}

function onJobNumberChange()
{
   clear("wc-number-input");
      
   // Populate WC numbers based on selected job number.
   reloadWCNumbers();
}

function recalculateQuantity()
{
   let materialId = document.querySelector('#material-id-input').value;
   let length = materialLengths[materialId];
   let pieces = document.querySelector('#pieces-input').value;
   let quantity = length * pieces;
   
   document.querySelector('#quantity-input').value = quantity;
}

function onRevokeButton(materialEntryId)
{
   if (confirm("Are you sure you want to revoke this material?"))
   {
      // AJAX call to delete part weight entry.
      requestUrl = "../api/revokeMaterialEntry/?entryId=" + materialEntryId;
      
      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function()
      {
         if (this.readyState == 4 && this.status == 200)
         {
            var json = JSON.parse(this.responseText);
            
            if (json.success == true)
            {
               location.href = "viewMaterials.php";
            }
            else
            {
               console.log("API call to delete material failed.");
               alert(json.error);
            }
         }
      };
      xhttp.open("GET", requestUrl, true);
      xhttp.send(); 
   }
}

function onAcknowledge(materialEntryId, employeeNumber)
{
   // AJAX call to delete part weight entry.
   requestUrl = `../api/acknowledgeMaterialEntry/?entryId=${materialEntryId}&acknowledgedUserId=${employeeNumber}`;
   
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         var json = JSON.parse(this.responseText);
         
         if (json.success == true)
         {
            // No action.
         }
         else
         {
            console.log("API call to acknowledge material failed.");
            alert(json.error);
         }
      }
   };
   xhttp.open("GET", requestUrl, true);
   xhttp.send(); 
}

function onUnacknowledge(materialEntryId)
{
   // AJAX call to delete part weight entry.
   requestUrl = `../api/unacknowledgeMaterialEntry/?entryId=${materialEntryId}`;
   
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         var json = JSON.parse(this.responseText);
         
         if (json.success == true)
         {
            // No action.
         }
         else
         {
            console.log("API call to unacknowledge material failed.");
            alert(json.error);
         }
      }
   };
   xhttp.open("GET", requestUrl, true);
   xhttp.send(); 
}

function reloadWCNumbers()
{
   jobNumber = document.getElementById("job-number-input").value;
   
   // AJAX call to populate WC numbers based on selected job number.
   requestUrl = "../api/wcNumbers/";
   if (jobNumber != 0)
   {
      requestUrl += "?jobNumber=" + jobNumber;
   }
   
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
      option.innerHTML = wcNumber;
      option.value = wcNumber;
      element.appendChild(option);
   }
   
   element.value = null;
}

function twoDigitNumber(value)
{
   return (("0" + value).slice(-2));
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

function validateMaterialEntry()
{
   valid = true;

   /*   
   var maintenanceType = parseInt(document.getElementById("maintenance-type-input").value);
   
   if (isNaN(Date.parse(document.getElementById("maintenance-date-input").value)))
   {
      alert("Please enter a valid maintenance date.");    
   }
   else if ((document.getElementById("maintenance-time-hour-input").value == 0) &&
            (document.getElementById("maintenance-time-minute-input").value == 0))
   {
      alert("Please enter some valid maintenance time.")  
   }
   else if (!(document.getElementById("employee-number-input").validator.validate()))
   {
      alert("Please select a technician.");    
   }
   else if (!(document.getElementById("wc-number-input").validator.validate()) &&
            !(document.getElementById("equipment-input").validator.validate()))
   {
      alert("Please select a work center.");    
   }
   else if (!(document.getElementById("maintenance-type-input").validator.validate()))
   {
      alert("Please select a maintenance type.");    
   }
   else if (((maintenanceType == 1) &&
             !(document.getElementById("repair-type-input").validator.validate())) ||
            ((maintenanceType == 2) &&
             !(document.getElementById("preventative-type-input").validator.validate())) ||            
            ((maintenanceType == 3) &&
             !(document.getElementById("cleaning-type-input").validator.validate())))             
   {
      alert("Please complete the maintenance type.");    
   }
   else if (((document.getElementById("new-part-number-input").value != "") &&
             (document.getElementById("new-part-description-input").value == "")) ||
            ((document.getElementById("new-part-description-input").value != "") &&
             (document.getElementById("new-part-number-input").value == "")))
   
   {
      alert("Please complete all new part info.");    
   }
   else
   {
      valid = true;
   }
   */
   
   return (valid);     
}