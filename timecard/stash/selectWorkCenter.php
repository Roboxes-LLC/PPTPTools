<?php
require_once '../common/database.php';

class SelectWorkCenter
{
   public static function getHtml()
   {
      $html = "";
      
      $workCenters = SelectWorkCenter::workCenters();
      
      $navBar = SelectWorkCenter::navBar();
      
      $html =
<<<HEREDOC
      <form id="input-form" action="#" method="POST"></form>
      
      <div class="flex-vertical content">

         <div class="heading">Select a Work Center</div>

         <div class="description">Select one of the following work centers.  You can find the work center number for your assigned station on your Job Sheet.<br/><br/>  If you don't see your work center listed, contact your supervisor.</div>

         <div class="flex-vertical inner-content">
            
            $workCenters
            
         </div>   
         
         $navBar
         
      </div>
HEREDOC;
      
      return ($html);
   }
   
   public static function render()
   {
      echo (SelectWorkCenter::getHtml());
   }
   
   private static function workCenters()
   {
      $html = 
<<<HEREDOC
      <div class="flex-horizontal selection-container">
HEREDOC;

      $selectedWorkCenter = SelectWorkCenter::getWorkCenter();
      
      $database = new PPTPDatabase();
      
      $database->connect();
      
      if ($database->isConnected())
      {
         $result = $database->getActiveWorkCenters();
         
         // output data of each row
         while ($result && ($row = $result->fetch_assoc()))
         {
            $wcNumber = $row["wcNumber"];
            
            $isChecked = ($selectedWorkCenter == $wcNumber);
            
            $html .= SelectWorkCenter::workCenter($wcNumber, $isChecked);
         }
      }
      
      $html .=
<<<HEREDOC
      </div>
HEREDOC;
      
      return ($html);
   }
   
   private static function workCenter($wcNumber, $isChecked)
   {
      $html = "";
      
      $checked = $isChecked ? "checked" : "";
      
      $id = "list-option-" + $wcNumber;
      
      $html =
<<<HEREDOC
         <input type="radio" form="input-form" id="$id" class="invisible-radio-button" name="wcNumber" value="$wcNumber" $checked/>
         <label for="$id">
            <div type="button" class="select-button wc-select-button">
               <i class="material-icons button-icon">build</i>
               <div>$wcNumber</div>
            </div>
         </label>
HEREDOC;
      
      return ($html);
   }
   
   private static function navBar()
   {
      $navBar = new Navigation();
      
      $navBar->start();
      $navBar->cancelButton("submitForm('input-form', 'timeCard.php', 'view_time_cards', 'cancel_time_card')");
      $navBar->nextButton("if (validateWorkCenter()) {submitForm('input-form', 'timeCard.php', 'select_job', 'update_time_card_info');};");
      $navBar->end();
      
      return ($navBar->getHtml());
   }
   
   private static function getWorkCenter()
   {
      $wcNumber = null;

      if (isset($_SESSION['timeCardInfo']))
      {
         $jobInfo = JobInfo::load($_SESSION['timeCardInfo']->jobId);

         if ($jobInfo)
         {
            $wcNumber = $jobInfo->wcNumber;
         }
      }

      return ($wcNumber);
   }
}
?>