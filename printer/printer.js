// Be sure to keep in sync with PrintJobStatus in printDefs.php
var PrintJobStatus = {
   UNKNOWN:  0,
   QUEUED:   1,
   PENDING:  2,
   PRINTING: 3,
   COMPLETE: 4,
   DELETED:  5
};

var PrintJobStatusLabels = ["", "Queued", "Pending", "Printing", "Complete", "Deleted"];

function PrintQueue(container)
{
   var container = container;
   
   var printJobs = null;
   
   PrintQueue.prototype.setPrintJobs = function(newPrintJobs)
   {
      printJobs = newPrintJobs;
   }
   
   PrintQueue.prototype.nextPrintJob = function()
   {
      var nextJob = null;
      
      if (printJobs != null)
      {
         for (printJob of printJobs)
         {
            if (printJob.status == PrintJobStatus.PENDING)
            {
               nextJob = printJob;
               break;
            }
         }
      }

      return (nextJob);
   }.bind(this)
   
   PrintQueue.prototype.render = function()
   {
      const HEADINGS = new Array("Date", "Owner", "Description", "Status");
      
      if (container != null)
      {
         // Clear table.
         while (container.firstChild)
         {
            container.removeChild(container.firstChild);
         }
         
         //
         // Build table heading
         //
         
         var table = document.createElement("table");
         var thead = table.createTHead();
         var row = thead.insertRow();
         
         for (heading of HEADINGS)
         {
            var th = document.createElement("th");
            var text = document.createTextNode(heading);
            th.appendChild(text);
            row.appendChild(th);
         }
         
         //
         // Build table rows
         //
         
         if (printJobs != null)
         {
            for (printJob of printJobs)
            {
               var row = table.insertRow();
               
               // Date
               var cell = row.insertCell();
               var text = document.createTextNode(printJob.dateTime);
               cell.appendChild(text);
               
               // Owner
               cell = row.insertCell();
               text = document.createTextNode(printJob.ownerName);
               cell.appendChild(text);
               
               // Description
               cell = row.insertCell();
               text = document.createTextNode(printJob.description);
               cell.appendChild(text);
               
               // Status
               cell = row.insertCell();
               text = document.createTextNode(PrintJobStatusLabels[printJob.status]);
               cell.appendChild(text);
            }
         }
         
         container.appendChild(table);
      }
   }.bind(this);
}


function Printer(preview)
{   
   var preview = preview;
   
   var printJob = null;
   
   Printer.prototype.updatePreview = function()
   {
      if (printJob != null)
      {
         var label = dymo.label.framework.openLabelXml(printJob.xml);
         
         var pngData = label.render();

         preview.src = "data:image/png;base64," + pngData;
         
         preview.style.display  = "block";         
      }
      else
      {
         preview.style.display  = "none";
      }
   }
   
   Printer.prototype.print = function(newPrintJob)
   {
      if (newPrintJob != null)
      {
         printJob = newPrintJob;
         
         console.log("Printing job " + printJob.printJobId);
         
         this.updatePreview();
      }
   }.bind(this);
   
   Printer.prototype.cancel = function()
   {
      printJob = null;
   }
   
   Printer.prototype.isPrinting = function()
   {
      return (printJob != null);
   }
}

function PrintManager(container, preview)
{
   var frameworkInitialized = false;
   
   var interval = null;
   
   var printQueue = new PrintQueue(container);
   
   this.printer = new Printer(preview);  // Why must this be a member of PrintManager?
   
   PrintManager.prototype.onFrameworkInitialized = function()
   {
      console.log("DYMO framework initialized");
      
      frameworkInitialized = true;
      
      if (dymo.label.framework.checkEnvironment())
      {
         console.log("Printing enabled.");
      }
      else
      {
         console.log("Printing not supported.");         
      }

      var printers = dymo.label.framework.getPrinters();
      if (printers.length == 0)
      {
         console.log("No printers available.");
      }
      else
      {
         var printerList = "";
         for (printer of printers)
         {
            printerList += printer.name;
            printerList += ", ";
         }
         console.log("Available printers: " + printerList);
      }
   }
   
   PrintManager.prototype.start = function()
   {
      // Initialize framework.
      dymo.label.framework.init(this.onFrameworkInitialized);
      
      // Initial update.
      this.update();
      
      // Update periodically.
      interval = setInterval(function(){this.update();}.bind(this), 5000);
   }.bind(this)
   
   PrintManager.prototype.stop = function()
   {
      clearInterval(interval);
   }
   
   PrintManager.prototype.update = function()
   {
      console.log("PrintManager::update()");
      
      this.fetchPrintQueue();
      
      this.printer.updatePreview();
      
      if (!this.printer.isPrinting())
      {
         var printJob = printQueue.nextPrintJob();
         
         if (printJob != null)
         {
            this.printer.print(printJob);
            
            this.setPrintJobStatus(printJob.printJobId, PrintJobStatus.PRINTING);
            
            // TODO: Find way to make status in print queue update immediately.
         }
      }
   }.bind(this)
   
   PrintManager.prototype.fetchPrintQueue = function()
   {
      // AJAX call to fetch print queue.
      requestUrl = "../api/printQueue/?printerId=" + 101;
      
      var manager = this;
      
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
                  manager.updatePrintQueue(json.queue);               
               }
               else
               {
                  console.log("API call to retrieve print queue failed.");
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
   };
   
   PrintManager.prototype.setPrintJobStatus = function(printJobId, status)
   {
      // AJAX call to fetch print queue.
      requestUrl = "../api/setPrintJobStatus/?printJobId=" + printJobId + "&status=" + status;
      
      var manager = this;
      
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
                  console.log("Print job " + json.printJobId + " status updated: " + PrintJobStatusLabels[json.status]);            
               }
               else
               {
                  console.log("API call to update print job status failed.");
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
   }.bind(this);
   
   PrintManager.prototype.updatePrintQueue = function(printJobs)
   {
      // Mark all QUEUED print jobs as PENDING.
      if (frameworkInitialized)
      {
         for (printJob of printJobs)
         {
            if (printJob.status == PrintJobStatus.QUEUED)
            {
               printJob.status = PrintJobStatus.PENDING;
               this.setPrintJobStatus(printJob.printJobId, PrintJobStatus.PENDING);
            }
         }
      }
         
      printQueue.setPrintJobs(printJobs);
      printQueue.render();
   }.bind(this);
}