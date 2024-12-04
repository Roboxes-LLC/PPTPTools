class InputValidator
{
   static setupValidation()
   {
      for (let input of document.querySelectorAll('[data-validator]'))
      {
         let validatorClass = input.dataset.validator;
         
         const ValidatorClass = new Function('return ' + validatorClass)();
         input.validator = new ValidatorClass(input);
      }
   }
   
   static validateForm(form)
   {
      let isValid = true;
      
      for (let input of form.elements)
      {
         isValid &= InputValidator.validateInput(input);
      }
      
      form.reportValidity();
      
      return (isValid);
   }
   
   static validateInput(input)
   {
      // Clear any previous custom validity.
      input.setCustomValidity("");
      
      // First, native Javascript validation.
      let isValid = input.checkValidity();
      
      // If that checks out, then execute custom validator.
      if (isValid && input.hasOwnProperty('validator'))
      {
         isValid = input.validator.validate();
      }
      
      return (isValid);
   }
   
   static resetForm(form)
   {
      for (let input of form.elements)
      {
         input.setCustomValidity("");
      }
   }

  // ***************************************************************************
   
   constructor(input)
   {
      this.input = input;
      
      input.addEventListener('input', function() {
         this.validate()
      }.bind(this));
   }
   
   validate()
   {
      throw new Error("Abstract classes can't be instantiated.");
   }
}
