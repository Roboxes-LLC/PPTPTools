// *****************************************************************************
// Imports

const readline = require('readline').createInterface({
  input: process.stdin,
  output: process.stdout
});

// Import PPTP PrintManage classr.
PrintManager = require("./printManager.js").PrintManager;

// *****************************************************************************

// Listener for updates from the PrintManager
class PrintManagerListener
{
   constructor()
   {
      this.localPrinters = [];
      this.cloudPrinters = [];
      this.printQueue = [];
   }
   
   onUpdate(localPrinters, cloudPrinters, printQueue)
   {
      this.processLocalPrinters(localPrinters);
      this.processCloudPrinters(cloudPrinters);
      this.processPrintQueue(printQueue);
   }
   
   isPrinterUpdated(oldPrinter, newPrinter)
   {
      return ((oldPrinter.isConnected != newPrinter.isConnected) ||
              (oldPrinter.status != newPrinter.status));
   }
   
   processLocalPrinters(printers)
   {
      for (const printer of printers)
      {
         var foundPrinter = this.localPrinters.find(function(element, index, array) {return element.printerName == this.printerName;}, printer);
         
         if (foundPrinter == undefined)
         {
            console.log("New local printer:");
            console.log(printer);
         }
         else if (this.isPrinterUpdated(foundPrinter, printer))
         {
            console.log("Local printer updated:");
            console.log(printer);
         }
      }
      
      this.localPrinters = printers;
   }
   
   processCloudPrinters(printers)
   {
      for (const printer of printers)
      {
         var foundPrinter = this.cloudPrinters.find(function(element, index, array) {return element.printerName == this.printerName;}, printer);
         
         if (foundPrinter == undefined)
         {
            console.log("New cloud printer:");
            console.log(printer);
         }
         else if (this.isPrinterUpdated(foundPrinter, printer))
         {
            console.log("Cloud printer updated:");
            console.log(printer);
         }
      }
      
      this.cloudPrinters = printers;
   }

   processPrintQueue(printQueue)
   {
      // New print jobs.
      for (const printJob of printQueue)
      {
         var foundJob = this.printQueue.find(function(element, index, array) {return element.printJobId == this.printJobId;}, printJob);
         
         if (foundJob == undefined)
         {
            console.log("Print job [" + printJob.printJobId + "] added");
         }
      }
      
      // Complete print jobs.
      for (const printJob of this.printQueue)
      {
         var foundJob = printQueue.find(function(element, index, array) {return element.printJobId == this.printJobId;}, printJob);
         
         if (foundJob == undefined)
         {
            console.log("Print job [" + printJob.printJobId + "] removed");
         }
      }
      
      this.printQueue = printQueue;
   }
}

// *****************************************************************************
// Application

// Print Manager initialization.
var printManager = new PrintManager(new PrintManagerListener(), 
                                    {
                                       // Local testing.
                                       //pptp: {http: "http", hostname:"localhost", apiUrl:"/pptp/api/"},
                                       // DYMO webservice version 8.6.2 or earlier.
                                       dymo: {hostname:"localhost"}
                                    });
printManager.start();
// Loop until user types "exit".
// TODO: CLI.
readline.question("", function(answer) {
   if (answer == "exit")
   {
      process.exit();
   }
});
