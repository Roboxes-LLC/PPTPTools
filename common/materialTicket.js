function MaterialTicket(materialTicketId, previewImageId)
{   
   var materialTicketId = materialTicketId;
   
   var previewImageId = previewImageId;
   
   var labelXML = "";
   
   MaterialTicket.prototype.updateLabelXML = function(udpatedLabelXML)
   {
      labelXML = udpatedLabelXML;
      
      this.render();
   }
   
   MaterialTicket.prototype.fetchLabelXML = function()
   {
      var MaterialTicket = this;
      
      // AJAX call to fetch print queue.
      requestUrl = "../api/materialTicket/?materialTicketId=" + materialTicketId;
      
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
                  MaterialTicket.updateLabelXML(json.labelXML);               
               }
               else
               {
                  console.log("API call to retrieve pan ticket failed.");
               }
            }
            catch (exception)
            {
               console.log("JSON syntax error.");
               console.log(this.responseText);
            }
         }
      };
      xhttp.open("GET", requestUrl, true);
      xhttp.send();  
   }.bind(this);
   
   MaterialTicket.prototype.render = function()
   {
      var previewImage = document.getElementById(previewImageId);

      if (labelXML != "")
      {
         try
         {
            var label = dymo.label.framework.openLabelXml(labelXML);

            var pngData = label.render();
   
            previewImage.src = "data:image/png;base64," + pngData;
            
            previewImage.style.display  = "block";     
         }
         catch (exception)
         {
            console.log("Failed to create label preview image from XML.  Details: " + exception.message);
            
            previewImage.style.display  = "none";  
         }
      }
      else
      {
         previewImage.style.display  = "none";
      }
   }
   
   if (dymo.label.framework.checkEnvironment())
   {
      console.log("DYMO framwork initialized.");
   }
   else
   {
      console.log("DYMO framwork NOT initialized.");         
   }
   
   this.fetchLabelXML();
}