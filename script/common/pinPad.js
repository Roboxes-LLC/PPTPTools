class PinPad
{
   // HTML elements
   static PageElements = {
      "PIN_PAD_CONTAINER": "pin-pad-container",
      "PIN_PAD":           "pin-pad",
      "PIN_PAD_DISPLAY":   "pin-pad-display",
      "PIN_PAD_ERROR":     "pin-pad-error-message"
   }
   
   static MAX_SEQUENCE_LENGTH = 3;

   constructor()
   {
      this.sequence = [];
      this.MAX_SEQUENCE_LENGTH = 3;
      this.inputIndex = 0;
      
      window.addEventListener("load", function(){
         this.#setup();
      }.bind(this));
   }
   
   setErrorMessage(message)
   {
      document.getElementById(PinPad.PageElements.PIN_PAD_ERROR).innerHTML = message;
   }
   
   onKeyPressed(keyValue)
   {
      if (keyValue == "bksp")
      {
         if (this.inputIndex > 0)
         {
            this.inputIndex--;
            this.sequence[this.inputIndex] = null;
            this.#fillDot(this.inputIndex, false);
         }
      }
      else if (keyValue == "enter")
      {
         if (this.sequence.length == PinPad.MAX_SEQUENCE_LENGTH)
         {
            this.#processSequence();
         }
      }
      else
      {
         if (this.inputIndex < PinPad.MAX_SEQUENCE_LENGTH)
         {
            this.sequence[this.inputIndex] = keyValue;
            this.#fillDot(this.inputIndex, true);

            this.inputIndex++;
         }
      }
   }
   
   reset()
   {
      this.sequence = [];
      this.inputIndex = 0;
      
      for (var index = 0; index < PinPad.MAX_SEQUENCE_LENGTH; index++)
      {
         this.#fillDot(index, false);
      }
   }
   
   // **************************************************************************
   
   #setup()
   {
      var container = document.getElementById(PinPad.PageElements.PIN_PAD_CONTAINER);
      if (container)
      {
         var keyElements = container.querySelectorAll("#pin-pad > div > div");
         
         for (const keyElement of keyElements)
         {
            var keyValue = keyElement.dataset.key;
            if (keyValue != "")
            {
               keyElement.addEventListener('click', function(event) {
                  this.onKeyPressed(event.target.dataset.key);
               }.bind(this));
            }
         }
      } 
   }
   
   #fillDot(index, fill)
   {
      var container = document.getElementById(PinPad.PageElements.PIN_PAD_CONTAINER);
      if (container != null)
      {      
         var dots = container.querySelectorAll("#pin-pad-display > div");
         
         var dot = dots[index];
         
         if (dot != null)
         {
            if (fill)
            {
               dot.classList.add("filled");
            }
            else
            {
               dot.classList.remove("filled");
            }
         }
      }
   }
   
   #processSequence()
   {
      var pin = "";
      for (var index = 0; index < PinPad.MAX_SEQUENCE_LENGTH; index++)
      {
         pin += this.sequence[index];
      }
      console.log("Pin: " + pin);
      
      if (this.#validateCallback(this.onPin))
      {
         this.onPin(pin);
      }
      
      this.reset();
   }
   
   #onValidUser(userId)
   {
      console.log("Validated user: " + userId);
   }
   
   #onInvalidUser()
   {
      console.log("Invalid pin: " + this.sequence);
   }
   
   #validateCallback(callback)
   {
      return ((callback != null) &&
              (typeof callback === 'function'));
   }
}

var PINPAD = new PinPad();