<!-- 
Required PHP variables:
   $filterStartDate
   $filterEndDate
   $filterActiveActions
 -->

<div class="flex-horizontal flex-top flex-left">
   <div class="input-group">
      <label>Start</label>
      <input id="start-date-input" type="date" value="<?php echo $filterStartDate ?>">
   </div>
   &nbsp;&nbsp;&nbsp;
   <div class="input-group">
      <label>End</label>
      <input id="end-date-input" type="date" value="<?php echo $filterEndDate ?>">
   </div>
   &nbsp;&nbsp;&nbsp;
   <div class="input-group flex-horizontal flex-v-center">
      <input id="active-actions-input" type="checkbox" <?php echo $filterActiveActions ? "checked" : "" ?>>
      &nbsp;   
      <label>All open actions</label>
   </div>
   
</div>