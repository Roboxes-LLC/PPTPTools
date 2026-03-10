<!-- 
Required PHP variables:
   $versionQuery
 -->

<html>

   <head>
      <meta name="viewport" content="width=device-width, initial-scale=1">

      <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons"/>
      
      <link rel="stylesheet" type="text/css" href="/css/flex.css<?php echo $versionQuery ?>"/>

      <style>
         html, body {
            height: 100%;
            width: 100%;
            margin: 0;
            overflow: hidden;
         }

         body {
            color: white;
            background: black;
            font-family: "Avant Garde", Avantgarde, "Century Gothic", CenturyGothic, AppleGothic, sans-serif;
         }

         .header {
            width: 100%;
            border-bottom-style: solid;
            border-color: white;
            border-width: 5px;
            padding: 15px;
         }

         .header .title {
            font-size: 50px;
            font-weight: bold;
         }

         .header .logo {
            height: 100px;
            margin-right: 25px;
         }

         .main {
            width: 100%;
         }

         #ticket-table {
            margin: auto;
         }

         th, td {
            padding: 25px;
         }

         th {
            color: #14a3db;
            text-align: center;
            font-size: 32px;
         }

         td {
            text-align: center;
            font-size: 25px;
         }

         td.down {
            background: #700000;
         }

         tbody tr:nth-child(even) {
            background-color: #303030; /* Light gray background for even rows */
         }

         #page-indicator {
            color: yellow;
            font-size: 25px;
         }


      </style>
      
      <script src="/common/common.js<?php echo $versionQuery ?>"></script>
      <script src="/script/common/common.js<?php echo $versionQuery ?>"></script>
      <script src="/script/common/commonDefs.php<?php echo $versionQuery ?>"></script>
      <script src="/script/page/maintenanceTicketDashboard.js<?php echo $versionQuery ?>"></script>
   </head>

   <body class="flex-vertical flex-top flex-left">

      <div class="flex-horizontal flex-h-center header">
         <image src="/images/pptp-logo-256x256.png" class="logo">
         <div class="flex-horizontal flex-h-center flex-v-center title">Maintenance Tickets</div>
      </div>
      
      <div class="main flex-vertical flex-top flex-h-center">
         <table id="ticket-table">
            <thead>
               <tr>
                  <th colspan="2">Posted</th>
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
         <div id="page-indicator">Page 1/1</div>
      </div>

      <script>
         let dashboard = new Dashboard();
      </script>
   </body>

</html>