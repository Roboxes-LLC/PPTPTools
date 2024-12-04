function formatCurrency(value)
{
   return value.toLocaleString('en-US', {style: 'currency', currency: 'USD', minimumFractionDigits: 2, maximumFractionDigits: 5});
}

function currencyFormatter(cell, formatterParams, onRendered)
{
   let currency = cell.getValue();
   if (currency != null)
   {
      currency = formatCurrency(currency);
   }
   return (currency);
}

function ajaxRequest(requestUrl, callback)
{
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {        
         var response = {success: false};
          
         try
         {
            response = JSON.parse(this.responseText);
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
         
         if (callback != null)
         {
            callback(response);
         }
      }
   };
   xhttp.open("GET", requestUrl, true);
   xhttp.send(); 
}

function submitForm(formId, requestUrl, callback)
{
   var form = document.querySelector("#" + formId);
   
   var xhttp = new XMLHttpRequest();

   // Bind the form data.
   var formData = new FormData(form);

   // Define what happens on successful data submission.
   xhttp.addEventListener("load", function(event) {
      
      var response = {success: false};
      
      try
      {
         response = JSON.parse(event.target.responseText);
      }
      catch (exception)
      {
         if (exception.name == "SyntaxError")
         {
            response.error = "Bad server response";
            console.log("JSON parse error: \n" + this.responseText);
         }
         else
         {
            response.error = "Unknown server error";
            throw(exception);
         }
      }
      
      if (callback != null)
      {
         callback(response);
      }
   });

   // Define what happens on successful data submission.
   xhttp.addEventListener("error", function(event) {
     alert('Oops! Something went wrong.');
   });

   xhttp.open("POST", requestUrl, true);

   // The data sent is what the user provided in the form
   xhttp.send(formData);      
}

function showInvalid(formId)
{
   document.getElementById(formId).classList.add("show-invalid");
}

function hideInvalid(formId)
{
   document.getElementById(formId).classList.remove("show-invalid");
}