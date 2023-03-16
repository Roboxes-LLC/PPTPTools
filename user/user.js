function onSaveUser()
{
   if (validateUser())
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
               location.href = "viewUsers.php";
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
      requestUrl = "../api/saveUser/"
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

function onDeleteUser(employeeNumber)
{
   if (confirm("Are you sure you want to delete this user?"))
   {
      // AJAX call to delete part weight entry.
      requestUrl = "../api/deleteUser/?employeeNumber=" + employeeNumber;
      
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
                  location.href = "viewUsers.php";
               }
               else
               {
                  console.log("API call to delete user failed.");
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

function onRoleChange()
{
   var roleId = parseInt(document.getElementById("role-input").value);
   var permissions = defaultPermissions[roleId - 1];
   
   var elements = document.getElementsByClassName("permission-checkbox");
   
   for (element of elements)
   {
      var name = element.name;
      var permissionId = parseInt(name.substring(name.indexOf("-") + 1));
      
      var mask = (1 << (permissionId - 1));
      var isSet = ((permissions & mask) != 0);
      
      element.checked = isSet;
   }
}

function validateUser()
{
   valid = false;

   if (!(document.getElementById("employee-number-input").validator.validate()))
   {
      alert("Please enter a valid employee number.");      
   }
   else if (!(document.getElementById("default-shift-hours-input").validator.validate()))
   {
      alert("Valid default shift hours range: 1 - 12.");      
   }
   /*
   else if (!(document.getElementById("username-input").validator.validate()))
   {
      alert("Please enter a valid username.");      
   }
   else if (!(document.getElementById("first-name-input").validator.validate()))
   {
      alert("Please enter a valid first name.");      
   }
   else if (!(document.getElementById("last-name-input").validator.validate()))
   {
      alert("Please enter a valid first name.");      
   }
   */
   else
   {
      valid = true;
   }
   
   return (valid);
}

function refreshAuthToken()
{
   const AUTH_TOKEN_LENGTH = 32;
   
   var newToken = "";

   var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

   for (var i = 0; i < AUTH_TOKEN_LENGTH; i++)
   {
      newToken += possible.charAt(Math.floor(Math.random() * possible.length));
   }
   
   document.getElementById("auth-token-input").value = newToken;
}