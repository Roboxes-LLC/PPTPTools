<!-- 
Required PHP variables:
   $filterMfgDate
 -->

<div class="flex-horizontal flex-v-center flex-left">
   <button id="prev-week-button" class="small-button"><i class="material-icons icon-button">skip_previous</i></button>
   &nbsp;&nbsp;
   <button id="prev-day-button" class="small-button"><i class="material-icons icon-button" style="transform:rotate(180deg)">play_arrow</i></button>
   &nbsp;&nbsp;
   <div class="input-group">
      <label>Manufacture Date</label>
      <input id="mfg-date-input" type="date" value="<?php echo $filterMfgDate ?>">
   </div>
   &nbsp;&nbsp;
   <button id="next-day-button" class="small-button"><i class="material-icons icon-button">play_arrow</i></button>
   &nbsp;&nbsp;
   <button id="next-week-button" class="small-button"><i class="material-icons icon-button">skip_next</i></button>
</div>