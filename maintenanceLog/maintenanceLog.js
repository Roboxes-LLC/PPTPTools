function onSaveMaintenanceEntry()
{
   if (validateMaintenanceEntry())
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
               location.href = "maintenanceLog.php";
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
      requestUrl = "../api/saveMaintenanceEntry/"
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

function onDeleteMaintenanceEntry(maintenanceEntryId)
{
   if (confirm("Are you sure you want to delete this log entry?"))
   {
      // AJAX call to delete part weight entry.
      requestUrl = "../api/deleteMaintenanceEntry/?entryId=" + maintenanceEntryId;
      
      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function()
      {
         if (this.readyState == 4 && this.status == 200)
         {
            var json = JSON.parse(this.responseText);
            
            if (json.success == true)
            {
               location.href = "maintenanceLog.php";
            }
            else
            {
               console.log("API call to delete maintenance entry failed.");
               alert(json.error);
            }
         }
      };
      xhttp.open("GET", requestUrl, true);
      xhttp.send(); 
   }
}

function onShiftTimeChange()
{
   var hours = parseInt(document.getElementById("shift-time-hour-input").value);
   var minutes = parseInt(document.getElementById("shift-time-minute-input").value);
   
   var shiftTime = ((hours * 60) + minutes);
   
   document.getElementById("shift-time-input").value = shiftTime;
   
   document.getElementById("shift-time-minute-input").value = formatToTwoDigits(minutes);
}

function onMaintenanceTimeChange()
{
   var hours = parseInt(document.getElementById("maintenance-time-hour-input").value);
   var minutes = parseInt(document.getElementById("maintenance-time-minute-input").value);
   
   var maintenanceTime = ((hours * 60) + minutes);
   
   document.getElementById("maintenance-time-input").value = maintenanceTime;
   
   document.getElementById("maintenance-time-minute-input").value = formatToTwoDigits(minutes);
}

function onTodayButton()
{
   var today = new Date();
      
   document.querySelector('#maintenance-date-input').value = formattedDate(today); 
}

function onYesterdayButton()
{
   var yesterday = new Date();
   yesterday.setDate(yesterday.getDate() - 1);
   
   document.querySelector('#maintenance-date-input').value = formattedDate(yesterday); 
}

function onJobNumberChange()
{
   clear("wc-number-input");
   clear("equipment-input");
      
   // Populate WC numbers based on selected job number.
   reloadWCNumbers();
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
      option.innerHTML = wcNumber.label;
      option.value = wcNumber.wcNumber;
      element.appendChild(option);
   }
   
   element.value = null;
}

function onWCNumberChange()
{
   //document.getElementById("equipment-input").selectedIndex = -1;
   //document.getElementById("equipment-input").value = null;
   clear("equipment-input");
}

function onMaintenanceTypeChange()
{
   if (document.getElementById("maintenance-type-input").value > 0)
   {
      reloadMaintenanceCategories();
      
      onMaintenanceCategoryChange();
   }
   else
   {
      hide("maintenance-category-input");
      hide("maintenance-subcategory-input");
   }
}

function reloadMaintenanceCategories()
{
   let entryId = document.getElementById("entry-id-input").value;
   let maintenanceTypeId = document.getElementById("maintenance-type-input").value;
   
   // AJAX call to populate WC numbers based on selected job number.
   requestUrl = "../api/maintenanceCategories/";
   if (maintenanceTypeId != 0)
   {
      requestUrl += "?typeId=" + maintenanceTypeId;
   
      if (entryId != 0)
      {
         requestUrl += "&entryId=" + entryId;
      }
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
               updateMaintenanceCategoryOptions(json.maintenanceCategories, json.selectedCategoryId);               
            }
            else
            {
               console.log("API call to retrieve maintenance categories failed.");
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

function updateMaintenanceCategoryOptions(maintenanceCategories, selectedCategoryId)
{
   element = document.getElementById("maintenance-category-input");
   
   while (element.firstChild)
   {
      element.removeChild(element.firstChild);
   }
   
   if (maintenanceCategories.length == 0)
   {
      hide("maintenance-category-input");
      hide("maintenance-subcategory-input");
   }
   else
   {
      for (var maintenanceCategory of maintenanceCategories)
      {
         var option = document.createElement('option');
         option.innerHTML = maintenanceCategory.label;
         option.value = maintenanceCategory.categoryId;
         element.appendChild(option);
      }
      
      show("maintenance-category-input", "flex");
   }
   
   element.value = (selectedCategoryId != 0) ? selectedCategoryId : null;
}

function onMaintenanceCategoryChange()
{
   if (document.getElementById("maintenance-category-input").value > 0)
   {
      reloadMaintenanceSubcategories();
   }
   else
   {
      hide("maintenance-subcategory-input");
   }
}

function reloadMaintenanceSubcategories()
{
   let entryId = document.getElementById("entry-id-input").value;
   let maintenanceCategoryId = document.getElementById("maintenance-category-input").value;
   
   // AJAX call to populate WC numbers based on selected job number.
   requestUrl = "../api/maintenanceSubcategories/";
   if (maintenanceCategoryId != 0)
   {
      requestUrl += "?categoryId=" + maintenanceCategoryId;
      
      if (entryId != 0)
      {
         requestUrl += "&entryId=" + entryId;
      }
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
               updateMaintenanceSubcategoryOptions(json.maintenanceSubcategories, json.selectedSubcategoryId);               
            }
            else
            {
               console.log("API call to retrieve maintenance categories failed.");
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

function updateMaintenanceSubcategoryOptions(maintenanceSubcategories, selectedSubcategoryId)
{
   element = document.getElementById("maintenance-subcategory-input");
   
   while (element.firstChild)
   {
      element.removeChild(element.firstChild);
   }
   
   if (maintenanceSubcategories.length == 0)
   {
      hide("maintenance-subcategory-input");
   }
   else
   {
      for (var maintenanceSubcategory of maintenanceSubcategories)
      {
         var option = document.createElement('option');
         option.innerHTML = maintenanceSubcategory.label;
         option.value = maintenanceSubcategory.subcategoryId;
         element.appendChild(option);
      }
      
      show("maintenance-subcategory-input", "flex");
   }
   
   element.value = (selectedSubcategoryId != 0) ? selectedSubcategoryId : null;
}

function onEquipmentChange()
{
   clear("job-number-input");
   
   // Populate WC numbers based on cleared job number.
   reloadWCNumbers();
}

function onPartNumberChange()
{
   document.getElementById("new-part-number-input").value = null;
   document.getElementById("new-part-description-input").value = null;
}

function onNewPartNumberChange()
{
   document.getElementById("part-number-input").selectedIndex = -1;
   document.getElementById("part-number-input").value = null;
}

function onExistingPartButton()
{
   hide("new-part-number-block");
   show("part-number-block", "flex"); 
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

function validateMaintenanceEntry()
{
   valid = false;
   
   var maintenanceType = parseInt(document.getElementById("maintenance-type-input").value);
   
   if (isNaN(Date.parse(document.getElementById("maintenance-date-input").value)))
   {
      alert("Please enter a valid maintenance date.");    
   }
   else if ((document.getElementById("shift-time-hour-input").value == 0) &&
            (document.getElementById("shift-time-minute-input").value == 0))
   {
      alert("Please enter some valid shift time.")  
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
      alert("Please select a work center or support equipment.");    
   }
   else if (!(document.getElementById("maintenance-type-input").validator.validate()))
   {
      alert("Please select a maintenance type.");    
   }
   else if (isVisible("maintenance-category-input") &&
            !(document.getElementById("maintenance-category-input").validator.validate()))
   {
      alert("Please select a maintenance category.");    
   }
   else if (isVisible("maintenance-subcategory-input") &&
            !(document.getElementById("maintenance-subcategory-input").validator.validate()))
   {
      alert("Please select a maintenance subcategory.");    
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
   
   return (valid);     
}