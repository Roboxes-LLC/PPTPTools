<?php

if (!defined('ROOT')) require_once '../../../root.php';
require_once ROOT.'/core/common/notificationEmail.php';

class NotificationEmailTest
{
   // Test constants.
   private const INSPECTION_ID = 46993;
   private const TO_USER_ID = 1975;
   
   public static function run()
   {
      echo "Running NotificationEmailTest ...<br>";
      
      $test = new NotificationEmailTest();
      
      $test->testGetHtml();
      
      //$test->testSend();
   }
   
   private function testGetHtml()
   {
      echo "NotificationEmail::getHtml()<br>";
      
      foreach (Notification::$values as $notificationType)
      {
         if (in_array($notificationType, NotificationEmail::$supportedNotificationTypes))
         {
            $notificationEmail = new NotificationEmail($notificationType, (object)array("inspectionId" => NotificationEmailTest::INSPECTION_ID));
            
            echo 
<<< HEREDOC
            <div style="width: 800px; margin-bottom:25px">
               {$notificationEmail->getHtml()}
            </div>
HEREDOC;
         }
      }
   }
   
   private function testSend()
   {
      echo "NotificationEmail::send()<br>";
      
      $notificationEmail = new NotificationEmail(Notification::FINAL_INSPECTION, (object)array("inspectionId" => NotificationEmailTest::INSPECTION_ID));
   
      $results = $notificationEmail->send(NotificationEmailTest::TO_USER_ID, []);
      
      var_dump($results);
   }
}

NotificationEmailTest::run();

?>