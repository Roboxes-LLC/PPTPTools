function ShipmentTicket(shipmentTicketId, previewImageId)
{   
   var shipmentTicketId = shipmentTicketId;
   
   var previewImageId = previewImageId;
   
   var labelXML = "";
   
   ShipmentTicket.prototype.updateLabelXML = function(udpatedLabelXML)
   {
      labelXML = udpatedLabelXML;
      
      this.render();
   }
   
   ShipmentTicket.prototype.fetchLabelXML = function()
   {
      var ShipmentTicket = this;
      
      // AJAX call to fetch print queue.
      requestUrl = "/app/page/shipment/?request=fetch_ticket&shipmentTicketId=" + shipmentTicketId;
      
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
                  ShipmentTicket.updateLabelXML(json.labelXML);               
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
   
   ShipmentTicket.prototype.render = function()
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