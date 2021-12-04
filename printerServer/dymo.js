/*
DYMO web service API

https://127.0.0.1:41951/DYMO/DLS/Printing/GetPrinters
https://127.0.0.1:41951/DYMO/DLS/Printing/StatusConnected
https://127.0.0.1:41951/DYMO/DLS/Printing/PrintLabel (XML label, print params in body of POST)
https://127.0.0.1:41951/DYMO/DLS/Printing/RenderLabel (XML label in body of POST)
*/

// *****************************************************************************
// Imports

// Import xml2js library.
var xml2js = require('xml2js');
var processors = require('xml2js/lib/processors');

// *****************************************************************************

// Insecure, but it's okay since it's just to the local Dymo server.
process.env['NODE_TLS_REJECT_UNAUTHORIZED'] = 0;

// Define a fetch that works for both browser Javascript and Node.js.
fetch = (typeof fetch === 'undefined') ? require('node-fetch') : fetch;

// Class representing the print params passed to the Dymo web service.
class PrintParams
{
   constructor(copies, jobTitle)
   {
      this.Copies = copies;
      this.JobTitle = jobTitle;
   }
}

// Dymo web service interface. 
class Dymo
{
   constructor(options)
   {
      options = options || {};

      this.hostname = options.hostname || '127.0.0.1';
      this.port = options.port || 41951;
      this.apiUrl = options.apiUrl || "/DYMO/DLS/Printing/";    
   }
   
   async poll()
   {
      // https://127.0.0.1:41951/DYMO/DLS/Printing/StatusConnected
      
      let pollResults = {
         isAvailable: false,
         isPrintingEnabled: false
      }
      
      try
      {
         let response = await fetch(this.getApiUrl("StatusConnected"));
         let responseText = await response.text();
         
         if (response.status == "200")
         {
            pollResults.isAvailable = true;
            pollResults.isPrintingEnabled = (responseText.toLowerCase() === 'true');
         }
         else
         {
            console.log(`poll: Bad response [${response.status}] from server: ${responseText}`);
         }
      }
      catch (exception)
      {
         console.log(`poll(): Request error: ${exception}]`);
      }
      
      return (pollResults);
   }
   
   async getPrinters()
   {
      // https://127.0.0.1:41951/DYMO/DLS/Printing/GetPrinters
      
      let printers = [];
      
      try
      {
         let response = await fetch(this.getApiUrl("GetPrinters"));
         let responseText = await response.text();
         
         if (response.status == "200")
         {
            // Strip off leading/trailing quotes.
            responseText = responseText.replace(/^"|"$/g, '');
            
            // Replace '\/' with '/'
            // Ex: <Printer><\/Printer> -> <Printer></Printer>
            // Note: Required because old DYMO software returns mangled XML.
            responseText = responseText.replace(/\\\//g, "/");
            
            // String off extra slashes.
            // Ex: ////MY_SERVER -> //MY_SERVER
            responseText = this.stripslashes(responseText);
                        
            var parser = new xml2js.Parser({explicitArray: false, tagNameProcessors: [processors.firstCharLowerCase]});
            
            parser.parseString(responseText, function(err, result) {
               if ((result.printers != null) &&
                   (result.printers.labelWriterPrinter != null))
               {
                  // Array of printers
                  if (Array.isArray(result.printers.labelWriterPrinter))
                  {
                     printers = result.printers.labelWriterPrinter;
                  }
                  // Single printer.
                  else
                  {
                    printers[0] = result.printers.labelWriterPrinter;
                  }
               }
            });
         }
         else
         {
            console.log(`getPrinters: Bad response [${response.status}] from server: ${responseText}`);
         }
      }
      catch (exception)
      {
         console.log(`getPrinters(): Request error: ${exception}]`);
      }
      
      return (printers);
   }
   
   createLabelWriterPrintParamsXml(printParams)
   {
      // XML format:
      /*
      <LabelWriterPrintParams>
         <Copies></Copies>
         <JobTitle></JobTitle>
         <FlowDirection></FlowDirection>
         <PrintQuality></PrintQuality>
         <TwinTurboRoll></TwinTurboRoll>
      </LabelWriterPrintParam>
      */
      
      var wrapped = {LabelWriterPrintParams: printParams};

      var builder = new xml2js.Builder();
      var xml = builder.buildObject(wrapped);
      
      return (xml);
   }
   
   async printLabel(printerName, printParamsXML, labelXML)
   {
      // https://127.0.0.1:41951/DYMO/DLS/Printing/PrintLabel (XML label, print params in body of POST)
      
      let success = false;
      
      try
      {
         let body = `printerName=${encodeURIComponent(printerName)}&printParamsXml=${encodeURIComponent(printParamsXML)}&labelXml=${encodeURIComponent(labelXML)}&labelSetXml=`;
         
         let response = await fetch(this.getApiUrl("PrintLabel"), {
            method: 'POST',
            body: body,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
         });
         let responseText = await response.text();
         
         if (response.status == "200")
         {
            success = true;
         }
         else
         {
            console.log(`printLabel: Bad response [${response.status}] from server: ${responseText}`);
         }
      }
      catch (exception)
      {
         console.log(`printLabel(): Request error: ${exception}]`);
      }
      
      return (success);
   }
   
   getApiUrl(apiCall)
   {
      return `https://${this.hostname}:${this.port}${this.apiUrl}${apiCall}`;
   }
   
   stripslashes(str) {
      str = str.replace(/\\'/g, '\'');
      str = str.replace(/\\"/g, '"');
      str = str.replace(/\\0/g, '\0');
      str = str.replace(/\\\\/g, '\\');
      
      return str;
   }
}

module.exports = 
{
   Dymo: Dymo,
   PrintParams: PrintParams
}
