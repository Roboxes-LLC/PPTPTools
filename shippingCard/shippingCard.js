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

function onShiftTimeChange()
{
   var hours = parseInt(document.getElementById("shift-time-hour-input").value);
   var minutes = parseInt(document.getElementById("shift-time-minute-input").value);
   
   var shiftTime = ((hours * 60) + minutes);
   
   document.getElementById("shift-time-input").value = shiftTime;
   
   document.getElementById("shift-time-minute-input").value = formatToTwoDigits(minutes);
}

function onShippingTimeChange()
{
   var hours = parseInt(document.getElementById("shipping-time-hour-input").value);
   var minutes = parseInt(document.getElementById("shipping-time-minute-input").value);
   
   var runTime = ((hours * 60) + minutes);
   
   document.getElementById("shipping-time-input").value = runTime;
   
   document.getElementById("shipping-time-minute-input").value = formatToTwoDigits(minutes);
}

function onSaveShippingCard()
{
   if (validateShippingCard())
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
               location.href = "viewShippingCards.php";
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
      requestUrl = "../api/saveShippingCard/"
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

function onDeleteShippingCard(shippingCardId)
{
   if (confirm("Are you sure you want to delete this shipping card?"))
   {
      // AJAX call to delete part weight entry.
      requestUrl = "../api/deleteShippingCard/?shippingCardId=" + shippingCardId;
      
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
                  location.href = "viewShippingCards.php";
               }
               else
               {
                  console.log("API call to delete shipping card failed.");
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

function validateShippingCard()
{
   valid = false;

   if (!(document.getElementById("shipper-input").validator.validate()))
   {
      alert("Please select a shipper.");    
   }
   else if (!(document.getElementById("job-number-input").validator.validate()))
   {
      alert("Please select an active job.");    
   }
   else if (!(document.getElementById("shift-time-hour-input").validator.validate() &&
              document.getElementById("shift-time-minute-input").validator.validate()))
   {
      alert("Please enter a valid shift time.")      
   }
   else if (!(document.getElementById("shipping-time-hour-input").validator.validate() &&
              document.getElementById("shipping-time-minute-input").validator.validate()))
   {
      alert("Please enter a valid run time.")      
   }
   else if (!(document.getElementById("part-count-input").validator.validate()))
   {
      alert("Please enter a valid part count.");    
   }
   else if (!(document.getElementById("scrap-count-input").validator.validate()))
   {
      alert("Please enter a valid scrap count.");    
   }
   else
   {
      valid = true;
   }
   
   return (valid);   
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
