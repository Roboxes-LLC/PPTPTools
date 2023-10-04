// https://www.w3schools.com/howto/howto_css_modals.asp 

// When the user clicks on <span> (x), close the modal
var closeButtons = document.getElementsByClassName("close");
for (let closeButton of closeButtons)
{
   closeButton.onclick = function() {
      hideModal(closeButton.parentElement.parentElement.id);
   }
}

// When the user clicks anywhere outside a modal, close it.
window.onclick = function(event) {
   if (event.target.classList.contains('modal') &&
       !event.target.classList.contains('noclose'))
   {
      hideModal(event.target.id);
   }
}

function isModalVisible()
{
   var isVisible = false;
   
   var modals = document.getElementsByClassName("modal");
   
   for (let modal of modals)
   {
      isVisible |= (modal.style.display == "block");
   }
   
   return (isVisible);
}

function showModal(id, display)
{
   element = document.getElementById(id);
   element.style.display = display;
   element.focus();
   
   document.querySelector("body").style.overflowY = "hidden";
}

function hideModal(id)
{
   document.getElementById(id).style.display = "none";
   document.querySelector("body").style.overflowY = "auto";
}
