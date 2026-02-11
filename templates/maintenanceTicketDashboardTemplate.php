<!-- 
Required PHP variables:
   $versionQuery
 -->

<html>

   <head>
      <meta name="viewport" content="width=device-width, initial-scale=1">

      <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons"/>
      
      <link rel="stylesheet" type="text/css" href="/common/theme.css<?php echo $versionQuery ?>"/>
      <link rel="stylesheet" type="text/css" href="/common/common.css<?php echo $versionQuery ?>"/>
      
      <script src="/common/common.js<?php echo $versionQuery ?>"></script>
      <script src="/script/common/common.js<?php echo $versionQuery ?>"></script>
      <script src="/script/common/commonDefs.php<?php echo $versionQuery ?>"></script>
      <script src="/script/page/maintenanceTicketDashboard.js<?php echo $versionQuery ?>"></script>
   </head>

   <body class="flex-vertical flex-top flex-left">

      <div id="header" class="flex-horizontal">
         <div id="logo"></div>
         <div id="title">Maintenance Tickets</div>
      </div>
      
      <div class="main flex-vertical">
         <table id="ticket-table">
            <thead>
               <tr>
                  <th>Posted</th>
                  <th></th>
                  <th>WC #</th>
                  <th>Job</th>
                  <th>WC Status</th>
                  <th>Description</th>
                  <th>Assigned</th>
                  <th>Status</th>
                  <th>Elapsed Time</th>
               </tr>
            </thead>
            <tbody>
            </tbody>
            <tr id="ticket-template" class="template">
                  <td data-col="postedDate"></td>
                  <td data-col="postedTime"></td>
                  <td data-col="wcNumber"></td>
                  <td data-col="jobNumber"></td>
                  <td data-col="machineStatus"></td>
                  <td data-col="description"></td>
                  <td data-col="assigned"></td>
                  <td data-col="status"></td>
                  <td data-col="elapsedTime"></td>
            </tr>
         </table>
         <div id="page-indicator"></div>
      </div>

      <script>
         let dashboard = new Dashboard();
      </script>
   </body>

</html>