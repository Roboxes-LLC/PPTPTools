<!-- 
Required PHP variables:
   $quoteNumber
   $quote
   $logo
   $company
   $author
   $quoteDate
   $contact
   $customer
   $preface
   $notes
 -->
 
 <?php  
 function getContactName($templateParams)
 {
    return (isset($templateParams->contact) ? $templateParams->contact->getFullName() : "");
 }
 
 function getContactAddressLine($templateParams, $addressLine)
 {
    $line = "";
    
    if (!$templateParams->customer->address->isEmpty())
    {
       switch ($addressLine)
       {
          case 1:
          {
             $line = $templateParams->customer->address->addressLine1;
             break;
          }
          
          case 2:
          {
             $line = $templateParams->customer->address->addressLine2;
             break;
          }
          
          case 3:
          {
             $line = "{$templateParams->customer->address->city}, {$templateParams->customer->address->stateAbbreviation} {$templateParams->customer->address->zipcode}";
             break;
          }
       }
    }
       
    return ($line);
 }
 
 function getSalutation($templateParams)
 {
    $salutation = "";
    
    if (isset($templateParams->contact))
    {
       $salutation = "Dear {$templateParams->contact->getFormalName()},";
    }
    else
    {
       $salutation = "To Whom it May Concern:";
    }
    
    return ($salutation);
 }
 ?>

<html>
   <head>
      <style>
         body {
            font-family: Helvetica, sans-serif;
            font-size: 12pt;
         }
         
         #header {
            text-align: right;
            margin-bottom: 25px;
         }
         
         #title {
            font-weight: bold;
            font-size: 24pt;
         }
         
         #quote-number {
            font-size: 18pt;
         }
         
         #company {
            margin-bottom: 10px;
         }
         
         #company-logo {
            float: left;
            width: 50px;
            margin-right: 10px;
         }
         
         #company-name {
            float: left;
            color: #cca300;
            font-weight: bold;
            font-size: 18pt;
            margin-top: 13px;
         }
         
         .clearfix:after {
            content:"";
            display:block;
            clear:both;
         }
         
         .bold {
            font-weight: bold;
         }
         
         .divider {
            width: 100%;
            border-top: 3px solid #bbb;
            margin-top: 25px;
            margin-bottom: 25px;
         }
         
         .bold {
            font-weight: bold;
         }
         
         .info-item {
            margin-bottom: 15px;
         }
         
         #info-table .label {
            font-weight: bold;
            width: 150px;
         }
         
         #info-table td {
            vertical-align: top;
            padding-bottom: 15px;
         }
         
         #quote-table {
           margin-left: auto;
           margin-right: auto;
        }
         
         #quote-table,         
         #quote-table td, 
         #quote-table th {
            border-collapse: collapse;
            border: 1px solid #bbb;
         }
         
         #quote-table td,
         #quote-table th {
            padding: 15px;
         }
         
         #iso-number {
            float: right;
         }
         
         #footer {
            margin-bottom: 25px;
         }
         
         #notes {
            border: 1px solid #bbb;
            padding: 20px;
            background: #eee;
            margin-bottom: 20px;
         }
      </style>
   </head>
   <body>
      <div id="header">
         <div id="title">Quotation</div>
         <div id="quote-number"><?php echo $templateParams->quoteNumber ?></div>
      </div>
      <div id="from">
         <div id="company" class="clearfix">
            <img id="company-logo" src="<?php echo $templateParams->logo ?>"/>
            <div id="company-name" class="bold"><?php echo $templateParams->company->companyName ?></div>
          </div>
          <div>
             <div><?php echo $templateParams->company->address->addressLine1 ?></div>
             <div><?php echo "{$templateParams->company->address->city}, {$templateParams->company->address->stateAbbreviation} {$templateParams->company->address->zipcode}" ?></div>
             <div>Phone: <?php echo $templateParams->company->phone ?></div>
             <div>Fax: <?php echo $templateParams->company->fax ?></div>
             <div><a href="mailto:<?php echo $templateParams->author->email ?>"><?php echo $templateParams->author->email ?></a></div>
          </div>
      </div>
      
      <div class="divider"></div>
      
      <table id="info-table">
         <tr>
            <td class="label">Quotation Date:</td>
            <td><?php echo $templateParams->quoteDate ?></td>
         </tr>
         <tr>
            <td class="label">Quotation For:</td>
            <td>
               <div>
                  <div class="bold"><?php echo $templateParams->customer->customerName ?></div>
                  <div><?php echo getContactName($templateParams) ?></div>
                  <div><?php echo getContactAddressLine($templateParams, 1) ?></div>
                  <div><?php echo getContactAddressLine($templateParams, 2) ?></div>
                  <div><?php echo getContactAddressLine($templateParams, 3) ?></div>
               </div>
            </td>
         </tr>         
      </table>

      <div id="preface">
         <p id="salutation"><?php echo getSalutation($templateParams) ?></p>
         <?php echo $templateParams->preface ?>
      </div>
      <table id="quote-table">
         <tr>
            <th>Part #</th>
            <th>Part description</th>
            <th>Quantity</th>
            <th>Unit price * </th>
            <th>Additional charge **</th>
            <th>Total price ***</th>
         </tr>
         <?php 
         foreach ($templateParams->quote->getSelectedEstimates() as $estimate)
         {
            $quantity = number_format($estimate->quantity, 0);
            $unitPrice = "$".number_format($estimate->unitPrice, 4);
            $additionalCharge = "$".number_format($estimate->additionalCharge, 2);
            $totalCost = "$".number_format($estimate->getTotalCost(), 2);
         
            echo
<<<HEREDOC
            <tr id="total-row">
               <td>{$templateParams->quote->customerPartNumber}</td>
               <td>{$templateParams->quote->partDescription}</td>
               <td>$quantity</td>
               <td>$unitPrice</td>
               <td>$additionalCharge</td>
               <td class="bold">$totalCost</td>
            </tr>
HEREDOC;
         }
         ?>
      </table>
      
      <br>
      
      <div id="caveats">
         <p>* Price in effect (P.I.E.) Price may change at the time of material purchase.</p>
         <p>** One time charge that applies to the first order only.</p>
         <p>*** Any P.O. referencing this quotation is subject to acknowledgement/conditions of sale by PPTP.</p>
      </div>
      <div id="notes">
         <div class="bold">Additional notes:</div>
         <br>
         <?php echo $templateParams->notes ?>
      </div>
      <div>
         <div>Thank you,</div>
         <br>
         <div><?php echo $templateParams->author->getFullName() ?></div>
         <div>Estimator</div>
      </div>
      <div id="footer">
         <div class="divider"></div>
         <div style="float:left"
            <div><a href="<?php echo $templateParams->company->website ?>"><?php echo $templateParams->company->website ?></a></div>
            <div id="iso-number">ISO <?php echo $templateParams->company->iso ?> CERTIFIED</div>
         </div>
      </div>
   </body>
</html>