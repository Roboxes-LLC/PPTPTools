<!-- 
Required PHP variables:
   $filterDateType
   $filterStartDate
   $filterEndDate
   $filterActiveOrders
 -->

<div class="flex-horizontal flex-top flex-left">
   <select id="date-type-input"><?php echo FilterDateType::getOptions([FilterDateType::ENTRY_DATE, FilterDateType::ORDERED_DATE, FilterDateType::DUE_DATE], $filterDateType) ?></select>
   &nbsp;&nbsp;&nbsp;
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
      <input id="active-orders-input" type="checkbox" <?php echo $filterActiveOrders ? "checked" : "" ?>>
      &nbsp;   
      <label>All open orders</label>
   </div>
   
</div>