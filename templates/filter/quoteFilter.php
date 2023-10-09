<!-- 
Required PHP variables:
   $filterStartDate
   $filterEndDate
   $filterActiveQuotes
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
      <input id="active-quotes-input" type="checkbox" <?php echo $filterActiveQuotes ? "checked" : "" ?>>
      &nbsp;   
      <label>All active quotes</label>
   </div>
   
</div>