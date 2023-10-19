<html>
   <head>
      <style>
         body {
            font-family: 'Roboto', sans-serif;
            font-size: 14px;
            background: #f5f5f5;
         }
         
         p {
           line-height: 1.5;
         }
         
         .banner {
            height: 50px;
            line-height: 50px;
            background: #fedc45;
            text-align: center;
         }
         
         .content {
            width: 75%;
            padding: 25px;
            color: #111111;
         }
         
         .header {
            font-size: 24px;
            padding-bottom: 10px;
         }

         .header .logo {
             margin-right: 10px;
             height: 40px;
         }
         
         .header .title {
            height: 40px;
            line-height: 40px;
         }
         
         .main {
            background: white;
            padding: 30px;
         }
         
         .notification-title {
            font-weight: bold;
            margin-bottom: 10px;
         }
         
         .fineprint {
            font-size: 12px;
            color: #a3a3a3;
         }
      </style>
   </head>
   <body>
      <div class="banner">An alert has been generated for <b><?php echo $templateParams->siteName ?></b></div>
      <div class="content">
         <div class="header">
            <img class="logo" style="float:left;" src="https://tools.pittsburghprecision.com/<?php echo $templateParams->logoSrc ?>"/>
            <div class="title" style="float:left">PPTP Tools</div>
            <div style="clear: both;"></div>
         </div>
         <div class="main">
            <div class="notification-title"><?php echo $templateParams->notificationTitle ?></div>
            <?php echo $templateParams->notificationText ?>
         </div>
         <div class="fineprint">
            <p>Please do not reply to this email. Emails sent to this address will not be answered.</p>
            <p>Don't want to see this notification? Update your settings <a href="https://tools.pittsburghprecision.com/user/viewUsers.php">here</a>.</p>
      </div>
   </body>
</html>