class ProspiraLabel
{   
   constructor(docId, previewImageId)
   {      
      this.docId = docId;
      
      this.previewImageId = previewImageId;
      
      this.labelXML = "";
      
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
   
   updateLabelXML(updatedLabelXML)
   {
      this.labelXML = updatedLabelXML;
      
      this.render();
   }
   
   fetchLabelXML()
   {
      // AJAX call to fetch print queue.
      let requestUrl = `/app/page/prospiraDoc/?request=fetch_label&docId=${this.docId}`;
      
      ajaxRequest(requestUrl, function(response) {
         if (response.success == true)
         {
            this.updateLabelXML(response.labelXML);    
         }
         else
         {
            console.log("CAPI call to retrieve label failed.");
            alert(response.error);
         }
      }.bind(this));
   }
   
   render()
   {
      let previewImage = document.getElementById(this.previewImageId);

      if (this.labelXML != "")
      {
         try
         {
            var label = dymo.label.framework.openLabelXml(this.labelXML);

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
}
