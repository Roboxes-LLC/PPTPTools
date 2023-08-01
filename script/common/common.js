function formatCurrency(value)
{
   return value.toLocaleString('en-US', {style: 'currency', currency: 'USD', minimumFractionDigits: 2, maximumFractionDigits: 5});
}

function currencyFormatter(cell, formatterParams, onRendered)
{
   let currency = cell.getValue();
   if (currency != null)
   {
      currency = formatCurrency(currency);
   }
   return (currency);
}