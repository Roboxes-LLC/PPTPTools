<!-- 
Required PHP variables:
   $filterLocation
   $filterStartDate
   $filterEndDate
 -->

<div class="flex-horizontal flex-top flex-left">

   <div class="input-group flex-horizontal flex-v-center">
      <select id="shipment-location-input">
         <?php echo ShipmentLocation::getOptions($filterLocation, true) ?>
      </select>
   </div>
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
   
</div>