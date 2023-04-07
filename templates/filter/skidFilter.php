<!-- 
Required PHP variables:
   $filterDateType
   $filterStartDate
   $filterEndDate
   $filterUnresolvedDefects
   $filterActiveSkids
 -->

<div class="flex-horizontal flex-v-center flex-left">
   <select id="date-type-input"><?php echo FilterDateType::getOptions([FilterDateType::SKID_CREATION_DATE], $filterDateType) ?></select>
   &nbsp;&nbsp;
   <div style="white-space: nowrap">Start</div>
   &nbsp;
   <input id="start-date-input" type="date" value="<?php echo $filterStartDate ?>">
   &nbsp;&nbsp;
   <div style="white-space: nowrap">End</div>
   &nbsp;
   <input id="end-date-input" type="date" value="<?php echo $filterEndDate ?>">
   &nbsp;&nbsp;
   <button id="today-button" class="small-button">Today</button>
   &nbsp;&nbsp;
   <button id="yesterday-button" class="small-button">Yesterday</button>
   &nbsp;&nbsp;
   <input id="active-skids-input" type="checkbox" <?php echo $filterActiveSkids ? "checked" : "" ?>/>All active skids
</div>