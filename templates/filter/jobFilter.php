<!-- 
Required PHP variables:
 -->

<?php 

function isFilteredBy($jobStatus)
{
   $isFiltered = false;
   
   // Determine if any filter values have been set in the $_SESSION variable.
   $isInitialized = false;
   foreach (JobStatus::$VALUES as $evalJobStatus)
   {
      $name = strtolower(JobStatus::getName($evalJobStatus));
      
      $isInitialized |= isset($_SESSION["jobs.filter.$name"]);
   }
   
   // Initialize to showing just pending and active jobs.
   $activeName = strtolower(JobStatus::getName(JobStatus::ACTIVE));
   $pendingName = strtolower(JobStatus::getName(JobStatus::PENDING));
   
   if (!$isInitialized)
   {
      $_SESSION["jobs.filter.$activeName"] = true;
      $_SESSION["jobs.filter.$pendingName"] = true;
   }
   
   $name = strtolower(JobStatus::getName($jobStatus));
   
   if (isset($_SESSION["jobs.filter.$name"]))
   {
      $isFiltered = filter_var($_SESSION["jobs.filter.$name"], FILTER_VALIDATE_BOOLEAN);
   }
   
   return ($isFiltered);
}

function getJobStatusFilters()
{
   $html = "";
   
   for ($jobStatus = JobStatus::FIRST; $jobStatus < JobStatus::LAST; $jobStatus++)
   {
      if ($jobStatus != JobStatus::DELETED)
      {
         $checked = isFilteredBy($jobStatus) ? "checked" : "";
         
         $label = JobStatus::getName($jobStatus);
         
         $name = strtolower($label);
         
         $id = $name . "-filter";
         
         $html .=
<<<HEREDOC
         <div class="flex-horizontal flex-v-center">
            <input id="$id" class="job-status-filter" type="checkbox" name="$name" value="true" $checked/>
            &nbsp;
            <label for="$id">$label</label>
            &nbsp;&nbsp;
         </div>
HEREDOC;
      }
   }
   
   return ($html);
}

?>

<div class="flex-horizontal">
   <?php echo getJobStatusFilters(); ?>
</div>

