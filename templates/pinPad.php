<!-- 
Required PHP variables:
   $errorMessage
 -->

<div id="pin-pad-container" class="flex-vertical">
   <div id="pin-pad-display" class="flex-horizontal">
      <div></div><div></div><div></div>
   </div>
   <div id="pin-pad" class="pin-pad">
      <div class="flex-horizontal"><div data-key="1">1</div><div data-key="2">2</div><div data-key="3">3</div></div>
      <div class="flex-horizontal"><div data-key="4">4</div><div data-key="5">5</div><div data-key="6">6</div></div>
      <div class="flex-horizontal"><div data-key="7">7</div><div data-key="8">8</div><div data-key="9">9</div></div>
      <div class="flex-horizontal"><div class="function-key" data-key="bksp"><div class="material-icons">backspace</div></div><div data-key="0">0</div><div class="function-key" data-key="enter"><div class="material-icons">login</div></div></div>
   </div>
   <br>
   <div id="pin-pad-error-message" class="flex-horizontal flex-h-center login-error-message">
      <?php echo $errorMessage ?>
   </div> 
</div>
