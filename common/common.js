function hide(elementId)
{
   document.getElementById(elementId).style.display = "none";
}

function show(elementId, display)
{
   document.getElementById(elementId).style.display = display;
}

function isVisible(elementId)
{
   return (document.getElementById(elementId).style.display != "none");
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

function isEnabled(elementId)
{
   return (document.getElementById(elementId).disabled == false);
}

function validate(elementId)
{
   return (document.getElementById(elementId).validator.validate());
}

function setSession(key, value)
{
   requestUrl = "../api/setSession/?key=" + key + "&value=" + value;
   
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
               console.log("$_SESSION[" + json.key + "] = " + json.value);
            }
            else
            {
               console.log("API call to update $_SESSION failed. " + json.error);
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

function preserveSession()
{
   setInterval(function(){ 
      // AJAX call to populate WC numbers based on selected job number.
      requestUrl = "../api/ping/";
   
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
                  console.log("Session preserved.");
               }
               else
               {
                  console.log("API call to preserve session failed.");
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
   }, 60000);
}

function copyToClipboard(elementId)
{
   var element = document.getElementById(elementId);
   
   element.focus();
   element.select();
   element.setSelectionRange(0, 99999);  // For mobile devices

   document.execCommand("copy");
   
   alert("Copied " + element.value);
}

/*
 * Serialize all form data into a query string
 * (c) 2018 Chris Ferdinandi, MIT License, https://gomakethings.com
 * @param  {Node}   form The form to serialize
 * @return {String}      The serialized form data
 */
function serializeForm(form)
{
   // Setup our serialized data
   var serialized = [];

   // Loop through each field in the form
   for (var i = 0; i < form.elements.length; i++)
   {
      var field = form.elements[i];

      // Don't serialize fields without a name, submits, buttons, file and reset inputs, and disabled fields
      if (!field.name || field.disabled || field.type === 'file' || field.type === 'reset' || field.type === 'submit' || field.type === 'button') continue;

      // If a multi-select, get all selections
      if (field.type === 'select-multiple')
      {
         for (var n = 0; n < field.options.length; n++)
         {
            if (!field.options[n].selected) continue;
            serialized.push(encodeURIComponent(field.name) + "=" + encodeURIComponent(field.options[n].value));
         }
      }

      // Convert field data to a query string
      else if ((field.type !== 'checkbox' && field.type !== 'radio') || field.checked)
      {
         serialized.push(encodeURIComponent(field.name) + "=" + encodeURIComponent(field.value));
      }
   }

   return serialized.join('&');
};

function setInitialFormState(formId)
{
   var form = document.getElementById(formId);
   
   if (form)
   {
      form.initState = serializeForm(form);
   }
   else
   {
      console.log("setInitialFormState: No form '" + formId + "' in document.");
   }
}

function isFormChanged(formId)
{
   var isChanged = false;
   
   var form = document.getElementById(formId);
   
   if (form)
   {
      if (form.initState != null)
      {
         isChanged = (form.initState != serializeForm(form));
      }
      else
      {
         console.log("setInitialFormState: Initial state could not be found for form '" + formId + "'.");
      }
   }
   else
   {
      console.log("setInitialFormState: No form '" + formId + "' in document.");
   }
   
   return (isChanged);
}

function formatDate(date)
{
   var formattedDate = "";

   if (date.getTime() === date.getTime())  // check for valid date
   {
      var month = (date.getMonth() + 1);
      var day = date.getDate();
      var year = date.getFullYear();
   
      month = (month.length < 2) ? ('0' + month) : month;
      day = (day.length < 2) ? ('0' + day) : day;
   
      var formattedDate = [month, day, year].join('/'); 
   }
   
   return (formattedDate);
}

function formatToTwoDigits(value)
{
   return (("0" + value).slice(-2));
}

function parseBool(value)
{
   return ((value === true) || (value.toLowerCase() === "true"));
}
