<!-- 
Required PHP variables:
   $quoteNumber
   $quote
   $company
   $author
   $quoteDate
   $contact
   $customer
   $preface
   $notes
 -->

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
         <div>
            <img src=""/>
            <div id="company-name" class="bold"><?php echo $templateParams->company->companyName ?></div>
          </div>
          <div>
             <div><?php echo $templateParams->company->address->addressLine1 ?></div>
             <div><?php echo "{$templateParams->company->address->city}, {$templateParams->company->address->stateAbbreviation} {$templateParams->company->address->zipcode}" ?></div>
             <div>Phone: <?php echo $templateParams->company->phone ?></div>
             <div>Fax: <?php echo $templateParams->company->fax ?></div>
             <div><?php echo $templateParams->author->email ?></div>
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
                  <div class="bold"><?php echo $templateParams->contact->getFullName() ?></div>
                  <div><?php echo $templateParams->customer->customerName ?></div>
                  <div><?php echo $templateParams->customer->address->addressLine1 ?></div>
                  <div><?php echo $templateParams->customer->address->addressLine2?></div>
                  <div><?php echo "{$templateParams->customer->address->city}, {$templateParams->customer->address->stateAbbreviation} {$templateParams->customer->address->zipcode}" ?></div>
               </div>
            </td>
         </tr>         
      </table>

      <div id="preface">
         <p id="salutation">Dear <?php echo $templateParams->contact->getFormalName() ?>,</p>
         <?php echo $templateParams->preface ?>
      </div>
      <table id="quote-table">
         <tr>
            <th>Part #</th>
            <th>Part description</th>
            <th>Quantity</th>
            <th>Unit price</th>
            <th>Additional charge *</th>
            <th>Total price **</th>
         </tr>
         <tr id="total-row">
            <td><?php echo $templateParams->quote->customerPartNumber ?></td>
            <td></td>
            <td><?php echo number_format($templateParams->quote->quantity, 0) ?></td>
            <td>$<?php echo number_format($templateParams->quote->getSelectedEstimate()->unitPrice, 4) ?></td>
            <td>$<?php echo number_format($templateParams->quote->getSelectedEstimate()->additionalCharge, 2) ?></td>
            <td class="bold">$<?php echo number_format($templateParams->quote->getSelectedEstimate()->totalCost, 2) ?></td>
         </tr>
      </table>
      
      <br>
      
      <div id="caveats">
         <p>* One time charge that applies to the first order only.</p>
         <p>** Any P.O. referencing this quotation is subject to acknowledgement/conditions of sale by PPTP.</p>
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