function IntValidator(inputId, maxLength, minValue, maxValue, allowNull)
{
   this.inputId = inputId;
   this.maxLength = maxLength;
   this.minValue = minValue;
   this.maxValue = maxValue;
   this.allowNull = allowNull;
   
   IntValidator.prototype.init = function()
   {
      var element = document.getElementById(this.inputId);
      
      if (element)
      {
         element.maxLength = this.maxLength;
         element.min = this.minValue;
         element.max = this.maxValue;
         
         element.validator = this;
      }
   }
   
   IntValidator.prototype.isValid = function()
   {
      var valid = false;
   
      var element = document.getElementById(this.inputId);
      
      if (element)
      {
         var value = element.value;
         
         if ((value == null) || (value == "")) 
         {
            valid = this.allowNull;
         }
         else
         {
            valid = !(isNaN(value) || 
                      (parseInt(value) < this.minValue) || 
                      (parseInt(value) > this.maxValue));
         }
      }
      
      return (valid);
   }
   
   IntValidator.prototype.color = function(color)
   {
      var element = document.getElementById(this.inputId);
      
      if (element)
      {
         element.style.color = color;
      }
   }
   
   IntValidator.prototype.validate = function()
   {
      var valid = this.isValid();
      
      if (valid)
      {
         this.color("#000000");
      }
      else
      {
         this.color("#FF0000");
      }

      return (valid);
   }
}

function DecimalValidator(inputId, maxLength, minValue, maxValue, maxDecimalPlaces, allowNull)
{
   this.inputId = inputId;
   this.maxLength = maxLength;
   this.minValue = minValue;
   this.maxValue = maxValue;
   this.maxDecimalPlaces = maxDecimalPlaces;
   this.allowNull = allowNull;
   
   DecimalValidator.prototype.init = function()
   {
      var element = document.getElementById(this.inputId);
      
      if (element)
      {
         element.maxLength = this.maxLength;
         element.min = this.minValue;
         element.max = this.maxValue;
         
         element.validator = this;
      }
   }
   
   // https://stackoverflow.com/questions/17369098/simplest-way-of-getting-the-number-of-decimals-in-a-number-in-javascript
   DecimalValidator.prototype.countDecimals = function(value)
   {
      var count = 0;
      
      if (Math.floor(value) !== value)
      {
         count = value.toString().split(".")[1].length;
      }
      
      return (count);
   }
   
   DecimalValidator.prototype.isValid = function()
   {
      var valid = false;
   
      var element = document.getElementById(this.inputId);
      
      if (element)
      {
         var value = element.value;
         
         if ((value == null) || (value == "")) 
         {
            valid = this.allowNull;
         }
         else
         {
            valid = !(isNaN(value) || 
                      (parseFloat(value) < this.minValue) || 
                      (parseFloat(value) > this.maxValue) ||
                      (this.countDecimals(parseFloat(value)) > this.maxDecimalPlaces));
         }
      }
      
      return (valid);
   }
   
   DecimalValidator.prototype.color = function(color)
   {
      var element = document.getElementById(this.inputId);
      
      if (element)
      {
         element.style.color = color;
      }
   }
   
   DecimalValidator.prototype.validate = function()
   {
      var valid = this.isValid();
      
      if (valid)
      {
         this.color("#000000");
      }
      else
      {
         this.color("#FF0000");
      }

      return (valid);
   }
}

function HexValidator(inputId, maxLength, minValue, maxValue, allowNull)
{
   this.inputId = inputId;
   this.maxLength = maxLength;
   this.minValue = minValue;
   this.maxValue = maxValue;
   this.allowNull = allowNull;
   
   HexValidator.prototype.init = function()
   {
      var element = document.getElementById(this.inputId);
      
      if (element)
      {
         element.maxLength = this.maxLength;
         
         element.validator = this;
      }
   }
   
   HexValidator.prototype.isValid = function()
   {
      var valid = false;
   
      var element = document.getElementById(this.inputId);
      
      if (element)
      {
         var hexValue = element.value;
         
         // Validate null value.
         if ((hexValue == null) || (hexValue == "")) 
         {
            valid = this.allowNull;
         }
         // Validate valid hex within limits.
         else
         {
            var regexp = /^[0-9a-fA-F]+$/;
            var isHex = regexp.test(hexValue);
               
            var intVal = parseInt(hexValue, 16);  // radix = 16 for hexadecimal
            
            valid = (isHex && !isNaN(intVal) && (intVal >= minValue) && (intVal <= maxValue));
         }
      }
      
      return (valid);
   }
   
   HexValidator.prototype.color = function(color)
   {
      var element = document.getElementById(this.inputId);
      
      if (element)
      {
         element.style.color = color;
      }
   }
   
   HexValidator.prototype.validate = function()
   {
      var valid = this.isValid();
      
      if (valid)
      {
         this.color("#000000");
      }
      else
      {
         this.color("#FF0000");
      }

      return (valid);
   }
}

function SelectValidator(inputId)
{
   this.inputId = inputId;
   
   SelectValidator.prototype.init = function()
   {
      var element = document.getElementById(this.inputId);
      
      if (element)
      {
         element.validator = this;
      }
   }
   
   SelectValidator.prototype.isValid = function()
   {   
      var element = document.getElementById(this.inputId);
      
      return (element.value != "");
   }
   
   SelectValidator.prototype.color = function(color)
   {
      var element = document.getElementById(this.inputId);
      
      if (element)
      {
         element.style.color = color;
      }
   }
   
   SelectValidator.prototype.validate = function()
   {
      var valid = this.isValid();
      
      if (valid)
      {
         this.color("#000000");
      }
      else
      {
         this.color("#FF0000");
      }

      return (valid);
   }
}

class RegExpressionValidator
{
   constructor(inputId, regExpression, validOnMatch, maxLength)
   {
      this.inputId = inputId;
      this.regExpression = regExpression;
      this.validOnMatch = validOnMatch;
      this.maxLength = maxLength;
   }
   
   init()
   {
      var element = document.getElementById(this.inputId);
      
      if (element)
      {         
         element.validator = this;
         element.maxLength = this.maxLength;
      }
   }
   
   isValid()
   {   
      let element = document.getElementById(this.inputId);
      
      let isMatch = this.regExpression.test(element.value);
      let isValid = (isMatch == this.validOnMatch);
      
      return (isValid);
   }
   
   color(color)
   {
      var element = document.getElementById(this.inputId);
      
      if (element)
      {
         element.style.color = color;
      }
   }
   
   validate()
   {
      var valid = this.isValid();
      
      if (valid)
      {
         this.color("#000000");
      }
      else
      {
         this.color("#FF0000");
      }

      return (valid);
   }   
}

function DateValidator(inputId)
{
   this.inputId = inputId;
   
   DateValidator.prototype.init = function()
   {
      var element = document.getElementById(this.inputId);
      
      if (element)
      {
         element.validator = this;
      }
   }
   
   DateValidator.prototype.isValid = function()
   {   
      var element = document.getElementById(this.inputId);
      
      return ((element.value != "") && (element.value != null));
   }
   
   DateValidator.prototype.color = function(color)
   {
      var element = document.getElementById(this.inputId);
      
      if (element)
      {
         element.style.color = color;
      }
   }
   
   DateValidator.prototype.validate = function()
   {
      var valid = this.isValid();
      
      if (valid)
      {
         this.color("#000000");
      }
      else
      {
         this.color("#FF0000");
      }

      return (valid);
   }
}
   