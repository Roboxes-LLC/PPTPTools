class BarcodeScanner
{
   // **************************************************************************
   //                            Public
   
   constructor()
   {
      this.buffer = "";
      
      // Callbacks.
      this.onBarcode = null;
      
      this.#listen();
   }

   // **************************************************************************
   //                            Private

   #listen()
   {
      console.log("Listening for barcodes");
      
      document.addEventListener('keypress', function(event){this.#onKeyPress(event.keyCode);}.bind(this));
   }
   
   #onKeyPress(keyCode)
   {
      if (keyCode == 13)
      {
         if (this.#validateCallback(this.onBarcode))
         {
            console.log("Barcode: " + this.buffer);
            
            this.onBarcode(this.buffer);
         }  

         this.buffer = "";
      }
      else
      {
         this.buffer = this.buffer + String.fromCharCode(keyCode);
      }  
   }
   
   #validateCallback(callback)
   {
      return ((callback != null) &&
              (typeof callback === 'function'));
   }
}