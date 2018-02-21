<?php

require_once '../common/filter.php';
require_once '../database.php';
require_once '../navigation.php';
require_once '../user.php';

// *****************************************************************************
//                                  FilterSpacer

class OnlyActiveFilterComponent extends FilterComponent
{
   public $onlyActive = false;
   
   public function getHtml()
   {
      $checked = $this->onlyActive ? "checked" : ""; 
      
      $html =
<<<HEREDOC
      <div class="filter-component">
         <input id="only-active-input" type="checkbox" name="onlyActive" value="true" $checked>
         <label for="only-active-input">Active jobs</label>
      </div>
HEREDOC;
      
      return ($html);
   }
   
   public function load()
   {
      if (isset($_POST['onlyActive']))
      {
         $this->onlyActive = boolval($_POST['onlyActive']);
      }
   }
}

// *****************************************************************************
//                                   ViewJobs

class ViewJobs
{
   private $filter;
   
   public function __construct()
   {
      $this->filter = new Filter();
      
      $this->filter->addByName('date', new DateFilterComponent());
      $this->filter->addByName('onlyActive', new OnlyActiveFilterComponent());
      $this->filter->add(new FilterButton());
      $this->filter->add(new FilterDivider());
      $this->filter->add(new ThisWeekButton());
      
      $this->filter->load();
   }

   public function getHtml()
   {
      $filterDiv = ViewJobs::filterDiv();
      
      $jobsDiv = ViewJobs::jobsDiv();
      
      $navBar = ViewJobs::navBar();
      
      $html = 
<<<HEREDOC
      <script src="jobs.js"></script>
   
      <div class="flex-vertical card-div">
         <div class="card-header-div">View Jobs</div>
         <div class="flex-vertical content-div" style="justify-content: flex-start; height:400px;">
   
               $filterDiv
   
               $jobsDiv
         
         </div>

         $navBar;

      </div>
HEREDOC;
      
      return ($html);
   }
   
   public function render()
   {
      echo (ViewJobs::getHtml());
   }
      
   private function filterDiv()
   {
      return ($this->filter->getHtml());
   }
   
   private function navBar()
   {
      $navBar = new Navigation();
      
      $navBar->start();
      $navBar->mainMenuButton();
      $navBar->highlightNavButton("New Job", "onNewJob()", true);
      $navBar->end();
      
      return ($navBar->getHtml());
   }
   
   private function jobsDiv()
   {
      $html = 
<<<HEREDOC
         <div class="jobs-div">
            <table class="job-table">
               <tr>
                  <th>Job Number</th>
                  <th>Author</th>                  
                  <th>Date</th>
                  <th>Part #</th>
                  <th>Work Center #</th>
                  <th>Status</th>
               </tr>
HEREDOC;
      
      $database = new PPTPDatabase();
      
      $database->connect();
      
      if ($database->isConnected())
      {
         // Start date.
         $startDate = new DateTime($this->filter->get('date')->startDate, new DateTimeZone('America/New_York'));  // TODO: Function in Time class
         $startDateString = $startDate->format("Y-m-d");
         
         // End date.
         // Increment the end date by a day to make it inclusive.
         $endDate = new DateTime($this->filter->get('date')->endDate, new DateTimeZone('America/New_York'));
         $endDate->modify('+1 day');
         $endDateString = $endDate->format("Y-m-d");
         
         $onlyActive = $this->filter->get("onlyActive")->onlyActive;
         
         $result = $database->getJobs($startDateString, $endDateString, $onlyActive);
         
         if ($result)
         {
            while ($row = $result->fetch_assoc())
            {
               $jobInfo = JobInfo::load($row["jobNumber"]);
               
               if ($jobInfo)
               {
                  $creatorName = "unknown";
                  $user = User::getUser($jobInfo->creator);
                  if ($user)
                  {
                     $creatorName= $user->getFullName();
                  }
                  
                  $dateTime = new DateTime($jobInfo->dateTime, new DateTimeZone('America/New_York'));  // TODO: Function in Time class
                  $date = $dateTime->format("m-d-Y");
                  
                  $status = $jobInfo->isActive ? "Active" : "Inactive";
                  
                  $html .=
<<<HEREDOC
                     <tr>
                        <td>$jobInfo->jobNumber</td>
                        <td>$creatorName</td>
                        <td>$date</td>
                        <td>$jobInfo->partNumber</td>
                        <td>$jobInfo->wcNumber</td>
                        <td>$status</td>
                     </tr>
HEREDOC;
               }
            }
         }
      }
      
      $html .=
<<<HEREDOC
            </table>
         </div>
HEREDOC;
      
      return ($html);
   }
}
?>