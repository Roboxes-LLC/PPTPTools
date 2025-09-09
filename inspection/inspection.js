function onSelectInspectionTemplate()
{
   if (validateInspectionSelection())
   {
      document.getElementById("input-form").submit();
   }
}

function onSaveInspection()
{
   if (validateInspection())
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
               location.href = "viewInspections.php";
            }
            else
            {
               alert(json.error);
            }
         }
         catch (exception)
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
      requestUrl = "../api/saveInspection/"
      xhttp.open("POST", requestUrl, true);
   
      // The data sent is what the user provided in the form
      xhttp.send(formData);
   }
}

function onDeleteInspection(inspectionId)
{
   if (confirm("Are you sure you want to delete this inspection?"))
   {
      // AJAX call to delete an ispection.
      requestUrl = "../api/deleteInspection/?inspectionId=" + inspectionId;
      
      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function()
      {
         if (this.readyState == 4 && this.status == 200)
         {
            var json = JSON.parse(this.responseText);
            
            if (json.success == true)
            {
               location.href = "viewInspections.php";            
            }
            else
            {
               alert(json.error);
            }
         }
      };
      xhttp.open("GET", requestUrl, true);
      xhttp.send();  
   }
}

function onGenerateCorrectiveAction(inspectionId)
{
   requestUrl = `/app/page/correctiveAction/?request=corrective_action_from_inspection&inspectionId=${inspectionId}`;
   
   ajaxRequest(requestUrl, function(response) {
      console.log(response);
      if (response.success == true)
      {
         location.href = `/correctiveAction/correctiveAction/?correctiveActionId=${response.correctiveActionId}`;   
      }
      else
      {
         alert(response.error);
      }
   }.bind(this));
}

function isJobBasedInspection(inspectionType)
{
   return((inspectionType == InspectionType.OASIS) ||
          (inspectionType == InspectionType.FIRST_PART) ||
          (inspectionType == InspectionType.LINE) ||
          (inspectionType == InspectionType.QCP) || 
          (inspectionType == InspectionType.IN_PROCESS) ||
          (inspectionType == InspectionType.FINAL));
}

function onInspectionTypeChange()
{
   var inspectionType = document.getElementById("inspection-type-input").value;
   
   clear("job-number-input");
   clear("pan-ticket-code-input");
   clear("wc-number-input");
   clear("template-id-input");
   
   disable("job-number-input");
   disable("wc-number-input");
   disable("pan-ticket-code-input");
   disable("template-id-input");

   if (isJobBasedInspection(inspectionType))
   {
      show("job-selection-container", "flex");
      
      enable("job-number-input");
      
      if (inspectionType != InspectionType.FINAL)
      {
         enable("pan-ticket-code-input");
      }
   }
   else
   {
      hide("job-selection-container", "flex");
      
      enable("template-id-input");
   }
}

function onJobNumberChange()
{
   let inspectionType = parseInt(document.getElementById("inspection-type-input").value);
   
   let jobNumber = document.getElementById("job-number-input").value;
   
   if (jobNumber == null)
   {
      disable("wc-number-input");
   }
   else if (inspectionType == InspectionType.FINAL)
   {
      enable("template-id-input");
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

function onPanTicketCodeChange()
{
   var panTicketCode = document.getElementById("pan-ticket-code-input").value;
   
   // Clear fields.
   clear("job-number-input");
   clear("wc-number-input");
   clear("template-id-input");
   
   if (panTicketCode == "")
   {
      enable("job-number-input");
      
      // Disable fields that are dependent on the job number.
      disable("wc-number-input");
      disable("template-id-input");
      
      // AJAX call to retrieve active jobs.
      requestUrl = "../api/jobs/?onlyActive=true";
      
      ajaxRequest(requestUrl, function(response) {
         if (response.success == true)
         {
            updateJobOptions(response.jobs);
            
            updateTemplateId();
         }
         else
         {
            console.log("API call to retrieve jobs failed.");
         }
      });
   }
   else
   {
      disable("job-number-input");
      disable("wc-number-input");
      disable("template-id-input");
      
      // AJAX call to populate input fields based on pan ticket selection.
      requestUrl = `../api/timeCardInfo/?panTicketCode=${panTicketCode}&expandedProperties=true`;
      
      ajaxRequest(requestUrl, function(response) {
         if (response.success == true)
         {
            updateJobOptions(new Array(response.jobNumber));
            set("job-number-input", response.jobNumber);
            
            updateWcOptions(new Array({wcNumber: response.wcNumber, label:response.wcLabel}));
            set("wc-number-input", response.wcNumber);
            
            updateTemplateId();
            
            enable("template-id-input");
         }
         else
         {
            // Invalidate time card input.
            document.getElementById("pan-ticket-code-input").validator.color("#FF0000");
         }
      });
   }
}

function onWcNumberChange()
{
   enable("template-id-input");
}

function updateTemplateId()
{
   inspectionType = parseInt(document.getElementById("inspection-type-input").value);
   jobNumber = document.getElementById("job-number-input").value;
   wcNumber = parseInt(document.getElementById("wc-number-input").value);
   
   if (inspectionType != 0)
   {
      // AJAX call to populate template id based on selected inspection type, job number, and WC number.
      requestUrl = "../api/inspectionTemplates/?inspectionType=" + inspectionType + "&jobNumber=" + jobNumber + "&wcNumber=" + wcNumber;

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
                  updateTemplateIdOptions(json.templates);
               }
               else
               {
                  console.log("API call to retrieve inspection template id failed.");
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

function updateTemplateIdOptions(templates)
{
   element = document.getElementById("template-id-input");
   
   while (element.firstChild)
   {
      element.removeChild(element.firstChild);
   }
   
   var selectedTemplateId = 0;
   if (templates.length == 1)
   {
      var selectedTemplateId = templates[0].templateId;
   }

   for (var template of templates)
   {
      var option = document.createElement('option');
      option.innerHTML = template.name;
      option.value = template.templateId;
      element.appendChild(option);
   }

   if (templates.length == 1)
   {
      element.value = templates[0].templateId;
   }
   else
   {
      element.value = null;
   }
 
}

function validateInspectionSelection()
{
   valid = false;
   
   var inspectionType = document.getElementById("inspection-type-input").value;
   
   if (!validate("inspection-type-input"))
   {
      alert("Start by selecting an inspection type.");    
   }
   else if (isEnabled("job-number-input") && !validate("job-number-input"))
   {
      alert("Please select an active job.");    
   }
   else if (isEnabled("wc-number-input") && !validate("wc-number-input"))
   {
      alert("Please select a work center.");    
   }
   else
   {
      templateId = parseInt(document.getElementById("template-id-input").value);
      
      if (isNaN(templateId) || (templateId == 0))
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

function validateInspection()
{
   valid = false;
   
   var inspectionType = document.getElementById("inspection-type-input").value;
   
   if (isEnabled("inspection-number-input") && !validate("inspection-number-input"))
   {
      alert("Please enter an in-process inspection number.");    
   }
   else if (isEnabled("job-number-input") && !validate("job-number-input"))
   {
      alert("Please select an active job.");   
   }
   else if (isEnabled("wc-number-input") && !validate("wc-number-input"))
   {
      alert("Please select a work center.");   
   }
   else if (isEnabled("operator-input") && !validate("operator-input"))
   {
      alert("Please select an operator.");    
   }
   else if (isEnabled("mfg-date-input") && !validate("mfg-date-input"))
   {
      alert("Please enter a manufacture date.");
   }
   else if (isEnabled("start-mfg-date-input") && !validate("start-mfg-date-input"))
   {
      alert("Please enter a starting manufacture date.");
   }
   else if (isEnabled("quantity-input") && !validate("quantity-input"))
   {
      alert("Please enter a final inspection quantity.");
   }
   else
   {
      valid = true;
   }
   
   return (valid);  
}

function showData(button)
{
   var dataRow = button.closest("tr").nextSibling;
   
   // Show the data row.
   dataRow.style.display = "table-row";
   
   // Hide the "+" button.
   button.style.display = "none";
   
   // Show the "-" button.
   button.nextSibling.style.display = "block";  
}

function hideData(button)
{
   var dataRow = button.closest("tr").nextSibling;
   
   // Hide the data row.
   dataRow.style.display = "none";
   
   // Hide the "-" button.
   button.style.display = "none";
   
   // Show the "+" button.
   button.previousSibling.style.display = "block";
}

function approveAll()
{
   var inspectionInputs = document.getElementsByClassName('inspection-status-input');
   
   for (var input of inspectionInputs)
   {
      input.value = InspectionStatus.PASS;
      onInspectionStatusUpdate(input);
   }
}

function approveRow(propertyId)
{
   var inspectionInputs = 
      document.querySelectorAll(`.inspection-table tr[data-property-id="${propertyId}"] .inspection-status-input`);
   
   for (var input of inspectionInputs)
   {
      input.value = InspectionStatus.PASS;
      onInspectionStatusUpdate(input);
   }
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

function onTodayButton(button)
{
   let inputId = button.dataset["inputfield"];
   console.log(inputId);
   
   var mfgDateInput = document.querySelector(`#${inputId}`);
   
   if (mfgDateInput != null)
   {
      var today = new Date();
      
      mfgDateInput.value = formattedDate(today); 
   }         
}

function onYesterdayButton(button)
{
   let inputId = button.dataset["inputfield"];
   console.log(inputId);
      
   var mfgDateInput = document.querySelector(`#${inputId}`);
   
   if (mfgDateInput != null)
   {
      var yesterday = new Date();
      yesterday.setDate(yesterday.getDate() - 1);
      
      mfgDateInput.value = formattedDate(yesterday); 
   }      
}

function onInspectionTypeChanged()
{   
   let inspectionType = parseInt(document.querySelector('#inspection-type-input').value);
   
   if (inspectionType == InspectionType.FINAL)
   {
      disable("wc-number-input");
   }
   else if (inspectionType == InspectionType.GENERIC)
   {
      disable("job-number-input");
      disable("wc-number-input");
   }
   else
   {
      enable("job-number-input");
   }
}

function onQuantityChanged()
{   
   let inspectionId = parseInt(document.querySelector('#inspection-id-input').value);
   
   let templateId = parseInt(document.querySelector('#template-id-input').value);
   
   let quantity = parseInt(document.querySelector('#quantity-input').value);
   
   ajaxRequest(`/api/inspectionTable/?inspectionId=${inspectionId}&templateId=${templateId}&quantity=${quantity}`, function(response) {
      if (response.success)
      {
         updateInspectionTable(response.html);   
      }
      else
      {
         console.log("Failed to fetch inspection table: " + response.error);
      }
   });
}

function onInspectionStatusUpdate(element)
{
   // Clear classes
   for (const inspectionStatusClass of InspectionStatusClasses)
   {
      if (inspectionStatusClass != "")
      {
         element.classList.remove(inspectionStatusClass);
      }
   }

   // Add new class.
   var inspectionStatus = parseInt(element.value);
   element.classList.add(InspectionStatusClasses[inspectionStatus]);
}

function updateInspectionTable(html)
{
   let template = document.createElement('template');
   template.innerHTML = html;
   
   document.querySelector('.inspection-table').replaceWith(template.content.cloneNode(true));
}
