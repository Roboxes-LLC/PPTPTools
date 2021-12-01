// *****************************************************************************
// Imports

// Import Dymo interface classes.
Dymo = require("./dymo.js").Dymo;
PrintParams = require("./dymo.js").PrintParams;

// Import PPTP Tools interface classes.
PPTP = require("./pptp.js").PPTP;

// *****************************************************************************

// Be sure to keep in sync with PrintJobStatus in printDefs.php
var PrintJobStatus = {
   UNKNOWN:  0,
   QUEUED:   1,
   PENDING:  2,
   PRINTING: 3,
   COMPLETE: 4,
   DELETED:  6
};

var PrintJobStatusLabels = ["", "Queued", "Pending", "Printing", "Complete", "Deleted"];

class PrintManager
{
   constructor(listener, options)
   {
      // Dymo interface object.
      let dymoOptions = (options && options.dymo) ? options.dymo : {};
      this.dymo = new Dymo(dymoOptions);

      // PPTP Tools interface object.
      let pptpOptions = (options && options.pptp) ? options.pptp : {};
      this.pptp = new PPTP(pptpOptions);
            
      // Indicator that the DYMO web service is up and handling requests.
      this.isAvailable = false;
      
      // Indicator that the DYMO web service is up and handling requests.
      this.isPrintingEnabled = false;
      
      // Timer for refreshing the print queue from the server.
      this.interval = null;
      
      // Array of print jobs representing the current print queue.
      this.printQueue = [];
      
      // Array of dymo.label.framework.PrinterInfo objects representing the printers detected on the client PC.
      this.localPrinters = [];
      
      // Array of PrinterInfo objects representing the currently available cloud printers.
      this.cloudPrinters = [];
      
      // Callback routine when any data is updated.
      this.listener = listener;
      
      // Flag to avoid overlapping calls to update();
      this.updating = false;   
   }
   
   // Starts the print manager's periodic polling of printers and the print queue.
   start()
   {
      // Initial update.
      this.update();
      
      // Update periodically.
      this.interval = setInterval(function(){this.update();}.bind(this), 5000);
   }
   
   // Stops the print manager's periodic polling of printers and the print queue.
   stop()
   {
      clearInterval(this.interval);
   }
   
   // Polls local printers, cloud printers, and the print queue.
   // Attempts printing if a print job is specified for a local printer.
   async update()
   {
      if (this.updating == false)
      {
         // Lock this function to avoid overlapping calls.
         this.updating = true;
         
         await this.pollDymo();
         
         if (this.isAvailable)
         {      
            await this.refreshLocalPrinters();
   
            await this.registerPrinters();
         }
         
         await this.refreshCloudPrinters();
         
         await this.refreshPrintQueue();
         
         await this.onPrintQueueUpdate();
         
         // Update any listener (i.e. GUI).
         if (this.listener != null)
         {
            this.listener.onUpdate(this.localPrinters, this.cloudPrinters, this.printQueue);
         }
         
         // Unlock this function.
         this.updating = false;
      }
   }
   
   // Polls DYMO web service for availability.
   async pollDymo()
   {
      let pollResults = await this.dymo.poll();
      
      let wasAvailable = this.isAvailable;
      
      this.isAvailable = pollResults.isAvailable;
      this.isPrintingEnabled = pollResults.isPrintingEnabled;
      
      if (!wasAvailable && this.isAvailable)
      {
         console.log("Dymo web service is available.");
         console.log("Local printing is " + (this.isPrintingEnabled ? "enabled." : "not enabled."));
      }
      else if (wasAvailable && !this.isAvailable)
      {
         console.log("Dymo web service is unavailable.");
         this.localPrinters = [];
      }
   }
   
   // Queries DYMO web service for printers detected on the client PC
   async refreshLocalPrinters()
   {
      this.localPrinters = await this.dymo.getPrinters();
   }
   
   // Queries DYMO web service for printers detected on the client PC
   async registerPrinters()
   {
      await this.pptp.registerPrinters(this.localPrinters);
   }
   
   // Polls the PPTP Tools server for a list of available cloud printers.
   async refreshCloudPrinters()
   {
      this.cloudPrinters = await this.pptp.getPrinters();
   };
   
   // Polls the PPTP Tools server for a list of current print jobs.
   async refreshPrintQueue()
   {
      this.printQueue = await this.pptp.getPrintQueue();
   };
      
   // Returns true if the specified printer is local and online.
   isPrinterOnline(printerName)
   {
      let foundPrinter = this.localPrinters.find(function(element, index, array) {return element.name == this.printerName;}, {printerName: printerName});
      
      return ((foundPrinter != undefined) && foundPrinter.isConnected);
   }
   
   // Attempts printing of all queued print jobs.
   async onPrintQueueUpdate()
   {
      // Attempt to print any queued print jobs.
      if (this.isAvailable && this.isPrintingEnabled)
      {
         for (let printJob of this.printQueue)
         {
            if (printJob.status == PrintJobStatus.QUEUED)
            {
               if (await this.print(printJob) == true)
               {
                  printJob.status = PrintJobStatus.COMPLETE;
                  
                  await this.pptp.updatePrintJobStatus(printJob.printJobId, printJob.status);   
               }
               else
               {
                  // Leave in QUEUED state.                                 
               }
            }
         }
         
         // Clear the print queue.
         this.printQueue = [];
      }
   }
      
   // Attempts printing of a print job.
   async print(printJob)
   {
      var success = false;
      
      //  Is the target printer local and online?
      if (this.isPrinterOnline(printJob.printerName))
      {      
         console.log("print: Printing job " + printJob.printJobId);
         
         // Construct print params XML.
         var printParams = new PrintParams(printJob.copies, printJob.description);
         var printParamXML = this.dymo.createLabelWriterPrintParamsXml(printParams);

         // Print.
         success = await this.dymo.printLabel(printJob.printerName, printParamXML, printJob.xml);
         
         if (success)
         {
            console.log(`print: Sucessfully printed job [${printJob.printJobId}].`);
         }
         else
         {
            console.log(`print: Failed to print job [${printJob.printJobId}].`);
            
            // Temporary measure.  Eventually, show errors in printer queue.
            await this.pptp.cancelPrintJob(printJob.printJobId);                   
         }
      }
      
      return (success);
   }
 }
 
module.exports = 
{
   PrintManager: PrintManager
}