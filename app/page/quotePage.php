<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/app/page/page.php';
require_once ROOT.'/common/upload.php';
require_once ROOT.'/core/manager/emailManager.php';
require_once ROOT.'/core/manager/quoteManager.php';

class QuotePage extends Page
{
    public function handleRequest($params)
    {
       switch ($this->getRequest($params))
       {
          case "save_quote":
          {
             if (Page::requireParams($params, ["quoteId", "customerId", "contactId", "customerPartNumber", "pptpPartNumber", "quantity"]))
             {
                $quoteId = $params->getInt("quoteId");
                $newQuote = ($quoteId == Quote::UNKNOWN_QUOTE_ID);
                
                $quote = null;
                if ($newQuote)
                {
                   $quote = new Quote();
                }
                else
                {
                   $quote = Quote::load($quoteId);
                   
                   if (!$quote)
                   {
                      $quote = null;
                      $this->error("Invalid quote id [$quoteId]");
                   }
                }
                
                if ($quote)
                {
                   QuotePage::getQuoteParams($quote, $params);
                   
                   if (Quote::save($quote))
                   {
                      $this->result->quoteId = $quote->quoteId;
                      $this->result->quote = $quote;
                      $this->result->success = true;
                      
                      if ($newQuote)
                      {
                         $quote->request(Time::now(), Authentication::getAuthenticatedUser()->employeeNumber, null);
                      }
                      
                      ActivityLog::logComponentActivity(
                         Authentication::getAuthenticatedUser()->employeeNumber,
                         ($newQuote ? ActivityType::ADD_QUOTE : ActivityType::EDIT_QUOTE),
                         $quote->quoteId,
                         $quote->getQuoteNumber());
                   }
                   else
                   {
                      $this->error("Database error");
                   }
                }
             }
             break;
          }
          
          case "delete_quote":
          {
             if (Page::requireParams($params, ["quoteId"]))
             {
                $quoteId = $params->getInt("quoteId");
                
                $quote = Quote::load($quoteId);
                
                if ($quote)
                {
                   Quote::delete($quoteId);
                   
                   $this->result->quoteId = $quote->quoteId;
                   $this->result->success = true;
                   
                   ActivityLog::logComponentActivity(
                      Authentication::getAuthenticatedUser()->employeeNumber,
                      ActivityType::DELETE_QUOTE,
                      $quote->quoteId,
                      $quote->getQuoteNumber());
                }
                else
                {
                   $this->error("Invalid quote id [$quoteId]");
                }
             }
             break;
          }
          
          case "attach_file":
          {
             if (Page::requireParams($params, ["quoteId", "filename", "description"]))
             {
                if (isset($_FILES["quoteAttachment"]) && 
                    ($_FILES["quoteAttachment"]["name"] != ""))
                {
                   $quoteId = $params->getInt("quoteId");
                   $file = $_FILES["quoteAttachment"];
                   $filename = $params->get("filename");
                   $description = $params->get("description");
                   
                   // Use the actual filename if an alternate wasn't provided.
                   if (empty($filename))
                   {
                      $filename = $_FILES["quoteAttachment"]["name"];
                   }
                   
                   // Constrain the filename to an appropriate size.
                   $filename = Upload::shortenFilename($filename, QuoteAttachment::MAX_FILENAME_SIZE);
                   
                   $storedFilename = null;
                   $uploadStatus = Upload::uploadQuoteAttachment($file, $storedFilename);
                   
                   switch ($uploadStatus)
                   {
                      case UploadStatus::UPLOADED:
                      {
                         $quoteAttachment = new QuoteAttachment();
                         $quoteAttachment->quoteId = $quoteId;
                         $quoteAttachment->filename = $filename;
                         $quoteAttachment->storedFilename = $storedFilename;
                         $quoteAttachment->description = $description;
                         
                         if (QuoteAttachment::save($quoteAttachment))
                         {
                            $this->result->success = true;
                            $this->result->quoteId = $quoteId;
                            $this->result->quoteAttachment = $quoteAttachment; 
                            
                            ActivityLog::logAddQuoteAttachment(
                               Authentication::getAuthenticatedUser()->employeeNumber,
                               $quoteAttachment->quoteId,
                               $quoteAttachment->attachmentId,
                               $quoteAttachment->filename);
                         }
                         else
                         {
                            $this->error("Database error");
                         }
                         break;  
                      }
                      
                      default:
                      {
                         $this->error("Upload error [" . UploadStatus::toString($uploadStatus) . "]");
                      }
                   }
                }
                else
                {
                   $this->error("Failed to upload file");
                }
             }
             break;
          }
          
          case "delete_attachment":
          {
             if (Page::requireParams($params, ["attachmentId"]))
             {
                $attachmentId = $params->getInt("attachmentId");
                
                $quoteAttachment = QuoteAttachment::load($attachmentId);
                
                if ($quoteAttachment)
                {
                   if (QuoteAttachment::delete($attachmentId))
                   {
                     // Delete file
                      Upload::deleteFile($quoteAttachment->storedFilename);
                      
                      $this->result->success = true;
                      $this->attachmentId = $attachmentId;
                      
                      ActivityLog::logDeleteQuoteAttachment(
                         Authentication::getAuthenticatedUser()->employeeNumber,
                         $quoteAttachment->quoteId,
                         $attachmentId,
                         $quoteAttachment->filename);
                   }
                   else
                   {
                      $this->error("Database error");
                   }
                }
                else
                {
                   $this->error("Invalid attachment [$attachmentId]");
                }
             }
             break;
          }
          
          case "estimate_quote":
          {
             if (Page::requireParams($params, ["quoteId"]))
             {
                $quoteId = $params->getInt("quoteId");
                $quote = Quote::load($quoteId);
                
                if ($quote)
                {
                   QuotePage::getEstimateParams($quote, $params);
                   
                   if (Quote::save($quote))
                   {
                      $this->result->quoteId = $quote->quoteId;
                      $this->result->quote = $quote;
                      $this->result->success = true;
                      
                      $isEstimated = $quote->isEstimated();
                      
                      if (!$isEstimated)
                      {
                         $quote->estimate(Time::now(), Authentication::getAuthenticatedUser()->employeeNumber, null);
                      }
                      else if (($quote->quoteStatus == QuoteStatus::UNAPPROVED) ||
                               ($quote->quoteStatus == QuoteStatus::REJECTED))
                      {
                         $quote->revise(Time::now(), Authentication::getAuthenticatedUser()->employeeNumber, null);
                      }
                      
                      ActivityLog::logComponentActivity(
                         Authentication::getAuthenticatedUser()->employeeNumber,
                         ($isEstimated ? ActivityType::REVISE_QUOTE : ActivityType::ESTIMATE_QUOTE),
                         $quote->quoteId,
                         $quote->getQuoteNumber());
                   }
                   else
                   {
                      $this->error("Database error");
                   }
                }
                else
                {
                   $this->error("Invalid quote id [$quoteId]");
                }
             }
             break;
          }
          
          case "approve_quote":
          {
             if (Page::authenticate([Permission::APPROVE_QUOTE]))
             {
                if (Page::requireParams($params, ["quoteId", "approveNotes", "isApproved"]))
                {
                   $quoteId = $params->getInt("quoteId");
                   
                   $quote = Quote::load($quoteId);
                   
                   if ($quote)
                   {
                      $isApproved = $params->getBool("isApproved");
                      
                      $notes = $params->get("approveNotes");
                      // Don't store empty notes.
                      if (empty($notes))
                      {
                         $notes = null;
                      }
                      
                      if ($isApproved)
                      {
                         if ($quote->approve(Time::now(), Authentication::getAuthenticatedUser()->employeeNumber, $notes))
                         {
                            $this->result->quoteId = $quote->quoteId;
                            $this->result->quote = $quote;
                            $this->result->success = true;
                     
                            ActivityLog::logApproveQuote(
                               Authentication::getAuthenticatedUser()->employeeNumber,
                               $quote->quoteId,
                               $quote->getQuoteNumber(),
                               $notes);
                         }
                         else
                         {
                            $this->error("Database error");
                         }
                      }
                      else
                      {
                         if ($quote->unapprove(Time::now(), Authentication::getAuthenticatedUser()->employeeNumber, $notes))
                         {
                            $this->result->quoteId = $quote->quoteId;
                            $this->result->quote = $quote;
                            $this->result->success = true;
                            
                            ActivityLog::logUnapproveQuote(
                                  Authentication::getAuthenticatedUser()->employeeNumber,
                                  $quote->quoteId,
                                  $quote->getQuoteNumber(),
                                  $notes);
                         }
                         else 
                         {
                            $this->error("Database error");
                         }
                      }
                   }
                   else 
                   {
                      $this->error("Invalid quote id [$quoteId]");
                   }
                }
             }
             break;
          }
          
          case "send_quote":
          {
             if (Page::requireParams($params, ["quoteId", "toEmail", "ccEmails", "fromEmail", "emailNotes"]))
             {
                $quoteId = $params->getInt("quoteId");
                $quote = Quote::load($quoteId);
                
                if ($quote)
                {
                   $toEmail = $params->get("toEmail");
                   $ccEmails = !empty($params->get("ccEmails")) ? explode(";", $params->get("ccEmails")) : array();
                   $fromEmail = $params->get("fromEmail");
                   $emailNotes = $params->get("emailNotes");
                   
                   $result = 
                      EmailManager::sendQuoteEmail(
                         $quoteId, 
                         Authentication::getAuthenticatedUser()->employeeNumber, 
                         $ccEmails,
                         $emailNotes);
                   
                   if ($result->status == true)
                   {
                      if ($quote->send(Time::now(), Authentication::getAuthenticatedUser()->employeeNumber, $emailNotes))
                      {
                         $this->result->quoteId = $quote->quoteId;
                         $this->result->quote = $quote;
                         $this->result->success = true;
                         
                         ActivityLog::logComponentActivity(
                            Authentication::getAuthenticatedUser()->employeeNumber,
                            ActivityType::SEND_QUOTE,
                            $quote->quoteId,
                            $quote->getQuoteNumber());
                      }
                      else
                      {
                         $this->error("Database error");
                      }
                   }
                   else
                   {
                      $this->error("Failed to send email: $result->message");
                   }
                }
                else
                {
                   $this->error("Invalid quote id [$quoteId]");
                }
             }
             break;
          }
          
          case "save_email_draft":
          {
             if (Page::requireParams($params, ["quoteId", "emailNotes"]))
             {
                $quoteId = $params->getInt("quoteId");
                $quote = Quote::load($quoteId);
                
                if ($quote)
                {
                   $emailNotes = $params->get("emailNotes");
                   
                   if ($quote->saveEmailDraft($emailNotes))
                   {
                      $this->result->quoteId = $quote->quoteId;
                      $this->result->quote = $quote;
                      $this->result->success = true;
                   }
                   else
                   {
                      $this->error("Database error");
                   }
                }
             }
             break;
          }
          
          case "accept_quote":
          {
             if (Page::requireParams($params, ["quoteId", "acceptNotes", "isAccepted"]))
             {
                $quoteId = $params->getInt("quoteId");
                $quote = Quote::load($quoteId);
                
                if ($quote)
                {
                   $isAccepted = $params->getBool("isAccepted");
                   
                   $notes = $params->get("acceptNotes");
                   // Don't store empty notes.
                   if (empty($notes))
                   {
                      $notes = null;
                   }
                   
                   if ($isAccepted)
                   {
                      if ($quote->accept(Time::now(), Authentication::getAuthenticatedUser()->employeeNumber, $notes))
                      {
                         $this->result->quoteId = $quote->quoteId;
                         $this->result->quote = $quote;
                         $this->result->success = true;
                         
                         ActivityLog::logAcceptQuote(
                            Authentication::getAuthenticatedUser()->employeeNumber,
                            $quote->quoteId,
                            $quote->getQuoteNumber(),
                            $notes);
                      }
                      else
                      {
                         $this->error("Database error");
                      }
                   }
                   else
                   {
                      if ($quote->reject(Time::now(), Authentication::getAuthenticatedUser()->employeeNumber, $notes))
                      {
                         $this->result->quoteId = $quote->quoteId;
                         $this->result->quote = $quote;
                         $this->result->success = true;
                         
                         ActivityLog::logRejectQuote(
                            Authentication::getAuthenticatedUser()->employeeNumber,
                            $quote->quoteId,
                            $quote->getQuoteNumber(),
                            $notes);
                      }
                      else
                      {
                         $this->error("Database error");
                      }
                   }
                }
                else
                {
                   $this->error("Invalid quote id [$quoteId]");
                }
             }
             break;
          }
          
          case "add_comment":
          {
             if (Page::requireParams($params, ["quoteId", "comments"]))
             {
                $quoteId = $params->getInt("quoteId");
                $comments = $params->get("comments");
                
                $quote = Quote::load($quoteId);
                
                if ($quote)
                {
                   ActivityLog::logComponentActivity(
                      Authentication::getAuthenticatedUser()->employeeNumber,
                      ActivityType::ANNOTATE_QUOTE,
                      $quote->quoteId,
                      $comments);
                   
                   $this->result->quoteId = $quote->quoteId;
                   $this->result->success = true;
                }
                else 
                {
                   $this->error("Invalid quote id [$quoteId]");
                }
             }
             break;
          }
          
          case "delete_comment":
          {  
             if (Page::requireParams($params, ["activityId"]))
             {
                $activityId = $params->getInt("activityId");
                
                $activity = Activity::load($activityId);
                
                if ($activity)
                {
                   if ($activity->author == Authentication::getAuthenticatedUser()->employeeNumber)
                   {
                      if (ActivityLog::deleteActivity($activityId))
                      {
                         $this->result->success = true;
                      }
                      else 
                      {
                         $this->error("Database error");
                      }
                   }
                   else
                   {
                      $this->error("Authentication error");
                   }
                }
                else
                {
                   $this->error("Invalid activity id [$activityId]");
                }
             }
             break;
          }
          
          case "fetch":
          default:
          {
             if ($this->authenticate([Permission::VIEW_QUOTE]))
             {
                // Fetch single component.
                if (isset($params["quoteId"]))
                {
                   $quoteId = $params->getInt("quoteId");
                   
                   $quote = Quote::load($quoteId);
                   
                   if ($quote)
                   {
                      $this->result->quote = $quote;
                      $this->result->success = true;
                   }
                   else
                   {
                      $this->error("Invalid quote id [$quoteId]");
                   }
                }
                // Fetch all components.
                else 
                {
                   $dateTime = Time::dateTimeObject(null);
                   
                   $endDate = Time::endOfDay($dateTime->format(Time::STANDARD_FORMAT));
                   $startDate = Time::startofDay($dateTime->modify("-1 month")->format(Time::STANDARD_FORMAT));
                   $activeQuotes = false;
                   
                   if (isset($params["startDate"]))
                   {
                      $startDate = Time::startOfDay($params["startDate"]);
                   }
                   
                   if (isset($params["endDate"]))
                   {
                      $endDate = Time::endOfDay($params["endDate"]);
                   }
                   
                   if (isset($params["activeQuotes"]))
                   {
                      $activeOrders = $params->getBool("activeQuotes");
                   }                   
                   
                   $this->result->success = true;
                   
                   if ($activeQuotes)
                   {
                      $this->result->quotes = QuoteManager::getQuotesByStatus(QuoteStatus::$activeStatuses);
                   }
                   else
                   {
                      $this->result->quotes = QuoteManager::getQuotes($startDate, $endDate);
                   }
                   
                   // Augment data.
                   foreach ($this->result->quotes as $quote)
                   {
                      QuotePage::augmentQuote($quote);
                   }
                }
             }
             break;
          }
       }
       
       echo json_encode($this->result);
    }
    
    private static function getQuoteParams(&$quote, $params)
    {
       $quote->customerId = $params->getInt("customerId");
       $quote->contactId = $params->getInt("contactId");
       $quote->customerPartNumber = $params->get("customerPartNumber");
       $quote->pptpPartNumber = $params->get("pptpPartNumber");
       $quote->partDescription = $params->get("partDescription");
       $quote->quantity = $params->getInt("quantity");
       $quote->emailNotes = $params->get("emailNotes");
    }
    
    private static function getEstimateParams(&$quote, $params)
    {
       for ($estimateIndex = 0; $estimateIndex < Quote::MAX_ESTIMATES; $estimateIndex++)
       {
          $estimate = null;
          
          if (!QuotePage::isEmptyEstimate($params, $estimateIndex))
          {
             $estimate = new Estimate();
             
             $estimate->quantity = $params->getInt(Estimate::getInputName("quantity", $estimateIndex));
             $estimate->grossPiecesPerHour = $params->getInt(Estimate::getInputName("grossPiecesPerHour", $estimateIndex));
             $estimate->netPiecesPerHour = $params->getInt(Estimate::getInputName("netPiecesPerHour", $estimateIndex));
             $estimate->unitPrice = $params->getFloat(Estimate::getInputName("unitPrice", $estimateIndex));
             $estimate->costPerHour = $params->getFloat(Estimate::getInputName("costPerHour", $estimateIndex));
             $estimate->markup = $params->getFloat(Estimate::getInputName("markup", $estimateIndex));
             $estimate->additionalCharge = $params->getFloat(Estimate::getInputName("additionalCharge", $estimateIndex));
             $estimate->chargeCode = $params->getInt(Estimate::getInputName("chargeCode", $estimateIndex));
             $estimate->totalCost = $params->getFloat(Estimate::getInputName("totalCost", $estimateIndex));
             $estimate->leadTime = $params->getInt(Estimate::getInputName("leadTime", $estimateIndex));
             $estimate->isSelected = $params->keyExists(Estimate::getInputName("isSelected", $estimateIndex));
          }
          
          $quote->setEstimate($estimate, $estimateIndex);
       }
    }
    
    private static function isEmptyEstimate($params, $estimateIndex)
    {
       $isEmpty = true;
       
       $properties = ["quantity", "unitPrice", "costPerHour", "markup", "additionalCharge", "chargeCode", "totalCost", "leadTime"];
 
       foreach ($properties as $property)
       {
          $inputName = Estimate::getInputName($property, $estimateIndex);
          if ($params->keyExists($inputName) && ($params->get($inputName) !== ""))
          {
             $isEmpty = false;
             break;
          }
       }

       return ($isEmpty);
    }
    
    private static function augmentQuote(&$quote)
    {
       // quoteNumber
       $quote->quoteNumber = $quote->getQuoteNumber();
       
       // customerName
       $customer = Customer::load($quote->customerId);
       if ($customer)
       {
          $quote->customerName = $customer->customerName;
       }
       
       // contactName
       $contact = Contact::load($quote->contactId);
       if ($contact)
       {
          $quote->contactName = $contact->getFullName();
       }       
       
       // estimateCount
       $quote->estimateCount = 0;
       for ($estimateIndex = 0; $estimateIndex < Quote::MAX_ESTIMATES; $estimateIndex++)
       {
          if ($quote->hasEstimate($estimateIndex))
          {
             $quote->estimateCount++;
          }
       }       
       
       // quoteStatusLabel
       $quote->quoteStatusLabel = QuoteStatus::getLabel($quote->quoteStatus);
   }
 }
 
 ?>