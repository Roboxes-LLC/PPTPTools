/*
PPTP Tools web service API

https://tools.pittsburghprecision.com/api/printerData/
https://tools.pittsburghprecision.com/api/registerPrinter/
https://tools.pittsburghprecision.com/api/printQueueData/
https://tools.pittsburghprecision.com/api/setPrintJobStatus/
https://tools.pittsburghprecision.com/api/cancelPrintJob/
*/

// *****************************************************************************

// Define a fetch that works for both browser Javascript and Node.js.
fetch = (typeof fetch === 'undefined') ? require('node-fetch') : fetch;

// PPTP Tools web service interface. 
class PPTP
{
   constructor(options)
   {
      options = options || {};

      this.http = options.http || "https";
      this.hostname = options.hostname || "tools.pittsburghprecision.com";
      this.apiUrl = options.apiUrl || "/api/";    
   }
      
   async getPrinters()
   {
      // https://tools.pittsburghprecision.com/api/printerData/
      
      let printers = [];
      
      try
      {
         let response = await fetch(this.getApiUrl("printerData"));
         let responseText = await response.text();
         
         if (response.status == "200")
         {
           printers = JSON.parse(responseText);
         }
         else
         {
            console.log(`getPrinters: Bad response [${response.status}] from server: ${responseText}`);
         }
      }
      catch (exception)
      {
         if (exception.name == "SyntaxError")
         {
            console.log("getPrinters: JSON syntax error: " + responseText);
         }
         else
         {
            console.log(`getPrinters: Request error: ${exception}]`);
         }
      }
      
      return (printers);
   }
   
   async registerPrinters(printers)
   {
      // https://tools.pittsburghprecision.com/api/registerPrinter/
      
      let success = true;
      let responseText = "";
      
      for (const printer of printers)
      {
         try
         {
            let body = `printerName=${encodeURIComponent(printer.name)}&model=${printer.modelName}&isConnected=${printer.isConnected}`;
            
            let response = await fetch(this.getApiUrl("registerPrinter"), {
               method: 'POST',
               body: body,
               headers: {
                   'Content-Type': 'application/x-www-form-urlencoded'
               }
            });
            
            responseText = await response.text();
            
            if (response.status == "200")
            {            
               var json = JSON.parse(responseText);
      
               if (json.success == false)
               {
                  console.log("registerPrinters: Failed to register printer. " + json.error);
                  success = false;
               }
            }
            else
            {
               console.log(`registerPrinters: Bad response [${response.status}] from server: ${responseText}`);
               
               success = false;
               break;
            }
         }
         catch (exception)
         {
            if (exception.name == "SyntaxError")
            {
               console.log("registerPrinters: JSON syntax error: " + responseText);
            }
            else
            {
               console.log(`registerPrinters: Request error: ${exception}]`);
            }
            
            success = false;
            break;
         }
      }
      
      return (success);
   }
   
   async getPrintQueue()
   {
      // https://tools.pittsburghprecision.com/api/printQueueData/
      
      let printQueue = [];
      
      try
      {
         let response = await fetch(this.getApiUrl("printQueueData"));
         let responseText = await response.text();
         
         if (response.status == "200")
         {            
            printQueue = JSON.parse(responseText);
         }
         else
         {
            console.log(`getPrintQueue: Bad response [${response.status}] from server: ${responseText}`);
         }
      }
      catch (exception)
      {
         if (exception.name == "SyntaxError")
         {
            console.log("getPrintQueue: JSON syntax error: " + responseText);
         }
         else
         {
            console.log(`getPrintQueue: registerPrinters: Request error: ${exception}]`);
         }
      }
      
      return (printQueue);
   }
   
   async updatePrintJobStatus(printJobId, status)
   {
      // https://tools.pittsburghprecision.com/api/setPrintJobStatus/
      
      let success = false;
      
      try
      {
         let body = `printJobId=${encodeURIComponent(printJobId)}&status=${status}`;
         
         let response = await fetch(this.getApiUrl("setPrintJobStatus"), {
            method: 'POST',
            body: body,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
         });
         let responseText = await response.text();
         
         if (response.status == "200")
         {            
            var json = JSON.parse(responseText);
            
            if (json.success == true)
            {
               success = true;
            }
            else
            {
               console.log("updatePrintJobStatus: Failed to update print job status: " + json.error);
            }
         }
      }
      catch (exception)
      {
         if (exception.name == "SyntaxError")
         {
            console.log("updatePrintJobStatus: JSON syntax error: " + responseText);
         }
         else
         {
            console.log(`updatePrintJobStatus: registerPrinters: Request error: ${exception}]`);
         }
      }
      
      return (success);         
   }
   
   async cancelPrintJob(printJobId)
   {
      // https://tools.pittsburghprecision.com/api/cancelPrintJob/
      
      let success = false;
            
      try
      {
         let body = `printJobId=${encodeURIComponent(printJobId)}`;
         
         let response = await fetch(this.getApiUrl("cancelPrintJob"), {
            method: 'POST',
            body: body,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
         });
         let responseText = await response.text();
         
         if (response.status == "200")
         {            
            var json = JSON.parse(responseText);
            
            if (json.success == true)
            {
               success = true;
            }
            else
            {
               console.log("cancelPrintJob: Failed to cancel print job: " + json.error);
            }
         }
      }
      catch (exception)
      {
         if (exception.name == "SyntaxError")
         {
            console.log("cancelPrintJob: JSON syntax error: " + responseText);
         }
         else
         {
            console.log(`cancelPrintJob: registerPrinters: Request error: ${exception}]`);
         }
      }
      
      return (success);  
   }
   
   getApiUrl(apiCall)
   {
      return `${this.http}://${this.hostname}${this.apiUrl}${apiCall}`;
   }
}

module.exports = 
{
   PPTP: PPTP
}