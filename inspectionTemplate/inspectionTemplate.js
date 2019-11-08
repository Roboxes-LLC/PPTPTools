function onSubmit()
{
   if (validateInspectionTemplate())
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
               location.href = "inspectionTemplates.php";
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
      requestUrl = "../api/saveInspectionTemplate/"
      xhttp.open("POST", requestUrl);
   
      // The data sent is what the user provided in the form
      xhttp.send(formData);
   }
}

function onDeleteInspectionTemplate(templateId)
{
   if (confirm("Are you sure you want to delete this template?"))
   {
      // AJAX call to delete an ispection.
      requestUrl = "../api/deleteInspectionTemplate/?templateId=" + templateId;
      
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
                  location.href = "inspectionTemplates.php";            
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
         }
      };
      xhttp.open("GET", requestUrl, true);
      xhttp.send();  
   }
}

function incrementPropertyName(name)
{
   var PROPERTY = "property";
   
   var startPos = name.indexOf(PROPERTY) + PROPERTY.length;
   var endPos = (startPos + 1);
   while ((endPos < length) && (Number.isInteger(name.charAt(endPos))))
   {
      endPos++;
   }
   
   var propertyIndex = parseInt(name.substring(startPos, endPos)) + 1;
   
   var newName = name.substring(0, startPos) + propertyIndex + name.substring(endPos);
   console.log(newName);
   
   return (newName);
}

function onAddProperty()
{
   var table = document.getElementById("property-table");
   
   var lastRow = table.rows[table.rows.length - 1];
   var newRow = lastRow.cloneNode(true);
   
   for (i = 0; i < newRow.cells.length; i++)
   {
      for (j = 0; j < newRow.cells[i].childNodes.length; j++)
      {
         var cell = newRow.cells[i].childNodes[j];
         
         var name = cell.name;
         
         if (name.includes("property"))
         {
            newName = incrementPropertyName(name);
            cell.name = newName;
            cell.value = null;
         }
      }
   }
   
   table.append(newRow);
}

/*
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

function updateTemplateId()
{
   inspectionType = document.getElementById("inspection-type-input").value;
   jobNumber = document.getElementById("job-number-input").value;
   wcNumber = document.getElementById("wc-number-input").value;
   
   if ((inspectionType != "") && (jobNumber != "") && (wcNumber != ""))
   {
      // AJAX call to populate template id based on selected inspection type, job number, and WC number.
      requestUrl = "../api/inspectionTemplate/?inspectionType=" + inspectionType + "&jobNumber=" + jobNumber + "&wcNumber=" + wcNumber;
      
      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function()
      {
         if (this.readyState == 4 && this.status == 200)
         {
            var json = JSON.parse(this.responseText);
            
            if (json.success == true)
            {
               console.log("Selecting template id: " + json.templateId);
               document.getElementById("template-id-input").value = json.templateId;
            }
            else
            {
               console.log("API call to retrieve inspection template id failed.");
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
      option.innerHTML = wcNumber;
      option.value = wcNumber;
      element.appendChild(option);
   }
   
   element.value = null;
}

function validateInspectionSelection()
{
   valid = false;
   
   if (!(document.getElementById("inspection-type-input").validator.validate()))
   {
      alert("Start by selecting an inspection type.");    
   }
   else if (!(document.getElementById("job-number-input").validator.validate()))
   {
      alert("Please select an active job.");    
   }
   else if (!(document.getElementById("wc-number-input").validator.validate()))
   {
      alert("Please select a work center.");    
   }
   else
   {
      templateId = parseInt(document.getElementById("template-id-input").value);
      
      if (templateId == 0)
      {
         alert("No inspection template could be found for the current selection."); 
      }
      else
      {
         valid = true;
      }
   }
   
   return (valid);
}
*/

function validateInspectionTemplate()
{
   /*
   valid = false;
   
   if (!(document.getElementById("operator-input").validator.validate()))
   {
      alert("Select an operator.");    
   }
   else
   {
      valid = true;
   }
   
   return (valid);  
   */
   
   return (true);
}
