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

function onVendorHeatNumberChange()
{
   let heatNumber = document.getElementById("vendor-heat-number-input").value;
   
   clear("vendor-id-input");
   
   if (heatNumber != null)
   {
      // AJAX call to retrieve vendor heat info.
      requestUrl = "../api/materialHeat/?heatNumber=" + heatNumber;
      
      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function()
      {
         if (this.readyState == 4 && this.status == 200)
         {
            var json = JSON.parse(this.responseText);
            
            if (json.success == true)
            {
               updateVendorHeatInfo(json.materialHeatInfo);
            }
            else
            {
               console.log("API call to retrieve vendor heat info failed.");
               alert(json.error);
            }
         }
      };
      xhttp.open("GET", requestUrl, true);
      xhttp.send();
   }
}

function onWCNumberChange()
{
   clear("job-number-input");
   enable("job-number-input");
      
   // Populate job numbers based on selected WC number.
   reloadJobNumbers();
}

function onEditInternalHeatButton()
{
   if (document.getElementById("internal-heat-number-input").disabled)
   {
      disable("edit-internal-heat-number-button");
      hide("edit-internal-heat-number-button");
      enable("internal-heat-number-input");
   }
}

function recalculateQuantity()
{
   let materialId = parseInt(document.querySelector('#material-id-input').value);
   let pieces = parseInt(document.querySelector('#pieces-input').value);
   let quantity = 0;

   if (Number.isInteger(materialId) && Number.isInteger(pieces))
   {
      let length = materialLengths[materialId];
      quantity = length * pieces;
   }
   
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

function reloadJobNumbers()
{
   wcNumber = document.getElementById("wc-number-input").value;
   
   // AJAX call to populate WC numbers based on selected job number.
   requestUrl = "../api/jobNumbers/";
   if (wcNumber != 0)
   {
      requestUrl += "?wcNumber=" + wcNumber;
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
               updateJobOptions(json.jobNumbers);               
            }
            else
            {
               console.log("API call to retrieve job numbers failed.");
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

function updateVendorHeatInfo(vendorHeatInfo)
{
   if (vendorHeatInfo)
   {
      document.getElementById("vendor-id-input").value = vendorHeatInfo.vendorId;
      document.getElementById("material-id-input").value = vendorHeatInfo.materialId;
      document.getElementById("internal-heat-number-input").value = vendorHeatInfo.internalHeatNumber;
      document.getElementById("hidden-internal-heat-number-input").value = vendorHeatInfo.internalHeatNumber;
      
      disable("vendor-id-input");
      disable("material-id-input");
      disable("internal-heat-number-input");
      hide("edit-internal-heat-number-button");
   }
   else
   {
      clear("vendor-id-input");
      clear("material-id-input");      
      document.getElementById("internal-heat-number-input").value = nextInternalHeatNumber;
      document.getElementById("hidden-internal-heat-number-input").value = nextInternalHeatNumber;
            
      enable("vendor-id-input");
      enable("material-id-input");
      disable("internal-heat-number-input");
      show("edit-internal-heat-number-button", "block");
   }
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
   let valid = false;
   
   console.log(document.getElementById("wc-number-input").disabled);
   let issuingMaterial = !document.getElementById("wc-number-input").disabled;

   if (!(document.getElementById("vendor-heat-number-input").validator.validate()))
   {
      alert("Please enter a valid vendor heat number.");    
   }
   else if (isNaN(Date.parse(document.getElementById("received-date-input").value)))
   {
      alert("Please enter a valid received date.");    
   }
   else if (!(document.getElementById("vendor-id-input").validator.validate()))
   {
      alert("Please select a vendor.");    
   }
   else if (!(document.getElementById("internal-heat-number-input").validator.validate()))
   {
      alert("Internal heat number is invalid.");    
   }
   else if (!(document.getElementById("material-id-input").validator.validate()))
   {
      alert("Please select a material type.");    
   }
   else if (!(document.getElementById("tag-number-input").validator.validate()))
   {
      alert("Please enter a valid tag number.");    
   }
   else if (!(document.getElementById("pieces-input").validator.validate()))
   {
      alert("Please enter a valid pieces count.");    
   }
   else if (issuingMaterial && !(document.getElementById("wc-number-input").validator.validate()))
   {
      alert("Please select a work center.");  
   }
   else if (issuingMaterial && !(document.getElementById("job-number-input").validator.validate()))
   {
      alert("Please select a job number.");  
   }
   else
   {
      valid = true;
   }
   
   return (valid);     
}