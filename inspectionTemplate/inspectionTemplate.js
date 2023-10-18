function onSaveInspectionTemplate()
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
               location.href = "viewInspectionTemplates.php";
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
                  location.href = "viewInspectionTemplates.php";            
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

function onCopyInspectionTemplate(templateId)
{
   location.href = "viewInspectionTemplate.php?copyFrom=" + templateId;
}

function onInspectionTypeChange()
{
   var inspectionType = parseInt(document.getElementById("inspection-type-input").value);
   
   show("sample-size-input-container", "flex");
   hide("optional-properties-input-container");
   
   switch (inspectionType)
   {
      case InspectionType.FINAL:
      {
         document.getElementById("sample-size-input").value = null;
         hide("sample-size-input-container", "flex");
         break;
      }
      
      case InspectionType.GENERIC:
      {
         show("optional-properties-input-container", "flex");
         break;
      }
      
      default:
      {
         break;
      }
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
   
   return (newName);
}

function onAddProperty()
{
   var table = document.getElementById("property-table");
   var newRow = table.insertRow(-1);
   newRow.innerHTML = getNewInspectionRow();
}

function onReorderProperty(propertyId, orderDelta)
{
   var rowId = "property" + propertyId + "_row";

   var row = document.getElementById(rowId);
   
   if (row != null)
   {
      var nextRow = row.nextElementSibling;
      var prevRow = row.previousElementSibling;
      var parent = row.parentNode;
      
      if ((orderDelta > 0) &&
          (nextRow != null))
      {
         // Swap ordering of row and next row.
         row.parentNode.removeChild(row);
         parent.insertBefore(row, nextRow.nextSibling);
         
         reorder();
      }
      else if ((orderDelta < 0) &&
               (prevRow != null))
      {
         // Swap ordering of row and previous row.         
         row.parentNode.removeChild(row);
         parent.insertBefore(row, prevRow);
         
         reorder();
      }
   }
}

function onDeleteProperty(propertyId)
{
   var rowId = "property" + propertyId + "_row";

   var row = document.getElementById(rowId);
   
   if (row != null)
   {
      row.remove();
   }
}

function reorder()
{
   var table = document.getElementById("property-table");
   
   for (var i = 0; i < table.rows.length; i++)
   {
      var inputElements = table.rows[i].getElementsByTagName("input");
      
      for (var input of inputElements)
      {
         if (input.name.includes("ordering"))
         {
            input.value = i;   
         }
      }
   }
}

function validateInspectionTemplate()
{
   valid = false;
   
   let templateName = document.getElementById("template-name-input").value;
   
   if (!(document.getElementById("inspection-type-input").validator.validate()))
   {
      alert("Please select an inspection type.");    
   }
   else if (!templateName || (templateName.length === 0))
   {
      alert("Please enter an inspection name."); 
   }
   else
   {
      valid = true;
   }
   
   return (valid);  
}
