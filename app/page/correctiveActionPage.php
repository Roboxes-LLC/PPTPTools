<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/app/page/page.php';
require_once ROOT.'/core/manager/correctiveActionManager.php';

class CorrectiveActionPage extends Page
{
    public function handleRequest($params)
    {
       switch ($this->getRequest($params))
       {
          case "corrective_action_from_inspection":
          {
             if (Page::requireParams($params, ["inspectionId"]))
             {
                $inspectionId = $params->getInt("inspectionId");
                $inspection = Inspection::load($inspectionId, false);  // Don't load results.
                
                if ($inspection)
                {
                   $correctiveAction = new CorrectiveAction();
                   
                   $correctiveAction->occuranceDate = Time::now();
                   $correctiveAction->jobId = $inspection->getJobId();
                   $correctiveAction->inspectionId = $inspection->inspectionId;
                   $correctiveAction->description = "Defect found in QC";
                   $correctiveAction->employee = $inspection->getOperator();
                   $correctiveAction->initiator = CorrectiveActionInitiator::INTERNAL;
                   $correctiveAction->location = CorrectiveActionLocation::PPTP;
                   
                   if ($correctiveAction->jobId != JobInfo::UNKNOWN_JOB_ID)
                   {
                      if (CorrectiveAction::save($correctiveAction))
                      {
                         $this->result->success = true;
                         $this->result->correctiveActionId = $correctiveAction->correctiveActionId;
                         $this->result->correctiveAction = $correctiveAction;
                         
                         $correctiveAction->open(Time::now(), Authentication::getAuthenticatedUser()->employeeNumber, null);
                         
                         ActivityLog::logComponentActivity(
                               Authentication::getAuthenticatedUser()->employeeNumber,
                               ActivityType::ADD_CORRECTIVE_ACTION,
                               $correctiveAction->correctiveActionId,
                               $correctiveAction->getCorrectiveActionNumber());
                      }
                      else
                      {
                         $this->error("Database error");
                      }
                   }
                   else
                   {
                      $this->error("No associated job.");
                   }
                }
                else
                {
                   $this->error("Invalid inspection id [$inspectionId]");
                }
             }
             break;
          }
          
          case "save_corrective_action":
          {
             if (Page::requireParams($params, ["correctiveActionId", "employeeNumber", "occuranceDate", "description"]))
             {
                // CARs are created manually by either selecting a job (via pan ticket code), or a shipment (via shipment ticket code).
                if ($params->keyExists("jobId") || $params->keyExists("shipmentId"))
                {
                   $correctiveActionId = $params->getInt("correctiveActionId");
                   $newCorrectiveAction = ($correctiveActionId == CorrectiveAction::UNKNOWN_CA_ID);
   
                   $correctiveAction = null;
                   if ($newCorrectiveAction)
                   {
                      $correctiveAction = new CorrectiveAction();
                   }
                   else
                   {
                      $correctiveAction = CorrectiveAction::load($correctiveActionId);
                      
                      if (!$correctiveAction)
                      {
                         $correctiveAction = null;
                         $this->error("Invalid corrective action id [$correctiveActionId]");
                      }
                   }
                   
                   if ($correctiveAction)
                   {
                      CorrectiveActionPage::getCorrectiveActionParams($correctiveAction, $params);
                      
                      if (CorrectiveAction::save($correctiveAction))
                      {
                         $this->result->correctiveActionId = $correctiveAction->correctiveActionId;
                         $this->result->correctiveAction = $correctiveAction;
                         $this->result->success = true;
                         
                         if ($newCorrectiveAction)
                         {
                            $correctiveAction->open(Time::now(), Authentication::getAuthenticatedUser()->employeeNumber, null);
                         }
                         
                         ActivityLog::logComponentActivity(
                            Authentication::getAuthenticatedUser()->employeeNumber,
                            ($newCorrectiveAction ? ActivityType::ADD_CORRECTIVE_ACTION : ActivityType::EDIT_CORRECTIVE_ACTION),
                            $correctiveAction->correctiveActionId,
                            $correctiveAction->getCorrectiveActionNumber());
                      }
                      else
                      {
                         $this->error("Database error");
                      }
                   }
                   else
                   {
                      $this->error("Invalid corrective action id [$correctiveActionId]");
                   }
                }
                else
                {
                   $this->error("Missing parameters [jobId, shipmentId]");
                }
             }
             break;
          }
          
          case "save_correction":
          {
             if (Page::requireParams($params, ["correctiveActionId", "correctionType", "description", "dueDate", "employee", "responsibleDetails"]))
             {
                $correctiveActionId = $params->getInt("correctiveActionId");
                $correctionType = $params->getInt("correctionType");

                $correctiveAction = CorrectiveAction::load($correctiveActionId);
                
                if ($correctiveAction)
                {
                   if (($correctionType >= CorrectionType::FIRST) &&
                       ($correctionType < CorrectionType::LAST))
                   {
                      $correction = ($correctionType == CorrectionType::LONG_TERM) ?
                                       $correctiveAction->longTermCorrection :
                                       $correctiveAction->shortTermCorrection;
                   
                      CorrectiveActionPage::getCorrectionParams($correction, $correctionType, $params);
                      
                      if (CorrectiveAction::save($correctiveAction))
                      {
                         $this->result->correctiveActionId = $correctiveAction->correctiveActionId;
                         $this->result->correctiveAction = $correctiveAction;
                         $this->result->success = true;
                         
                         ActivityLog::logComponentActivity(
                            Authentication::getAuthenticatedUser()->employeeNumber,
                            ActivityType::EDIT_CORRECTIVE_ACTION,
                            $correctiveAction->correctiveActionId,
                            $correctiveAction->getCorrectiveActionNumber());
                      }
                      else
                      {
                         $this->error("Database error");
                      }
                   }
                   else
                   {
                      $this->error("Invalid correction type [$correctionType]");
                   }
                }
                else
                {
                   $this->error("Invalid corrective action id [$correctiveActionId]");
                }
             }
             break;
          }
          
          case "delete_corrective_action":
          {
             if ($this->authenticate([Permission::EDIT_CORRECTIVE_ACTION]))
             {
                if (Page::requireParams($params, ["correctiveActionId"]))
                {
                   $correctiveActionId = $params->getInt("correctiveActionId");
                   
                   $correctiveAction = CorrectiveAction::load($correctiveActionId);
                   
                   if ($correctiveAction)
                   {
                      CorrectiveAction::delete($correctiveActionId);
                      
                      $this->result->correctiveActionId = $correctiveActionId;
                      $this->result->success = true;
                      
                      ActivityLog::logComponentActivity(
                         Authentication::getAuthenticatedUser()->employeeNumber,
                         ActivityType::DELETE_CORRECTIVE_ACTION,
                         $correctiveAction->correctiveActionId,
                         $correctiveAction->getCorrectiveActionNumber());
                   }
                   else
                   {
                      $this->error("Invalid corrective action id [$correctiveActionId]");
                   }                
                }
             }
             break;
          }
          
          case "approve_corrective_action":
          {
             if (Page::authenticate([Permission::APPROVE_CORRECTIVE_ACTION]))
             {
                if (Page::requireParams($params, ["correctiveActionId", "approveNotes", "isApproved"]))
                {
                   $correctiveActionId = $params->getInt("correctiveActionId");
                   
                   $correctiveAction = CorrectiveAction::load($correctiveActionId);
                   
                   if ($correctiveAction)
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
                         if ($correctiveAction->approve(Time::now(), Authentication::getAuthenticatedUser()->employeeNumber, $notes))
                         {
                            $this->result->correctiveActionId = $correctiveAction->correctiveActionId;
                            $this->result->correctiveAction = $correctiveAction;
                            $this->result->success = true;
                            
                            ActivityLog::logApproveCorrectiveAction(
                                  Authentication::getAuthenticatedUser()->employeeNumber,
                                  $correctiveAction->correctiveActionId,
                                  $correctiveAction->getCorrectiveActionNumber(),
                                  $notes);
                         }
                         else
                         {
                            $this->error("Database error");
                         }
                      }
                      else
                      {
                         if ($correctiveAction->unapprove(Time::now(), Authentication::getAuthenticatedUser()->employeeNumber, $notes))
                         {
                            $this->result->correctiveActionId = $correctiveAction->correctiveActionId;
                            $this->result->correctiveAction = $correctiveAction;
                            $this->result->success = true;
                            
                            ActivityLog::logUnapproveCorrectiveAction(
                                  Authentication::getAuthenticatedUser()->employeeNumber,
                                  $correctiveAction->correctiveActionId,
                                  $correctiveAction->getCorrectiveActionNumber(),
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
                      $this->error("Invalid corrective action id [$correctiveActionId]");
                   }
                }
             }
             break;
          }
          
          case "review_corrective_action":
          {
             if (Page::authenticate([Permission::APPROVE_CORRECTIVE_ACTION]))
             {
                if (Page::requireParams($params, ["correctiveActionId", "reviewDate", "reviewer", "effectiveness", "comments"]))
                {
                   $correctiveActionId = $params->getInt("correctiveActionId");
                   
                   $correctiveAction = CorrectiveAction::load($correctiveActionId);
                   
                   if ($correctiveAction)
                   {
                      if ($correctiveAction->status != CorrectiveActionStatus::APPROVED)
                      {
                         $this->error("Incorrect state for review");
                      }
                      else
                      {
                         $review = $correctiveAction->review;
                         
                         CorrectiveActionPage::getReviewParams($review, $params);
                         
                         if (CorrectiveAction::save($correctiveAction))
                         {
                            $this->result->correctiveActionId = $correctiveAction->correctiveActionId;
                            $this->result->correctiveAction = $correctiveAction;
                            $this->result->success = true;
                            
                            $correctiveAction->review(Time::now(), Authentication::getAuthenticatedUser()->employeeNumber, null);
                               
                            ActivityLog::logReviewCorrectiveAction(
                                  Authentication::getAuthenticatedUser()->employeeNumber,
                                  $correctiveAction->correctiveActionId,
                                  $correctiveAction->getCorrectiveActionNumber(),
                                  null);
                         }
                         else
                         {
                            $this->error("Database error");
                         }
                      }
                   }
                   else
                   {
                      $this->error("Invalid corrective action id [$correctiveActionId]");
                   }
                }
             }
             break;
          }
          
          case "close_corrective_action":
          {
             if (Page::authenticate([Permission::APPROVE_CORRECTIVE_ACTION]))
             {
                if (Page::requireParams($params, ["correctiveActionId"]))
                {
                   $correctiveActionId = $params->getInt("correctiveActionId");
                   
                   $correctiveAction = CorrectiveAction::load($correctiveActionId);
                   
                   if ($correctiveAction)
                   {
                      if ($correctiveAction->status != CorrectiveActionStatus::REVIEWED)
                      {
                         $this->error("Incorrect state for closing");
                      }
                      else if ($correctiveAction->close(Time::now(), Authentication::getAuthenticatedUser()->employeeNumber, null))
                      {
                         $this->result->correctiveActionId = $correctiveAction->correctiveActionId;
                         $this->result->correctiveAction = $correctiveAction;
                         $this->result->success = true;
                         
                         ActivityLog::logCloseCorrectiveAction(
                               Authentication::getAuthenticatedUser()->employeeNumber,
                               $correctiveAction->correctiveActionId,
                               $correctiveAction->getCorrectiveActionNumber(),
                               null);
                      }
                      else
                      {
                         $this->error("Database error");
                      }
                   }
                   else
                   {
                      $this->error("Invalid corrective action id [$correctiveActionId]");
                   }
                }
             }
             break;
          }
          
          case "add_comment":
          {
             if (Page::requireParams($params, ["correctiveActionId", "comments"]))
             {
                $correctiveActionId = $params->getInt("correctiveActionId");
                $comments = $params->get("comments");
                
                $correctiveAction = CorrectiveAction::load($correctiveActionId);
                
                if ($correctiveAction)
                {
                   ActivityLog::logComponentActivity(
                      Authentication::getAuthenticatedUser()->employeeNumber,
                      ActivityType::ANNOTATE_CORRECTIVE_ACTION,
                      $correctiveAction->correctiveActionId,
                      $comments);
                   
                   $this->result->correctiveActionId = $correctiveAction->correctiveActionId;
                   $this->result->success = true;
                }
                else
                {
                   $this->error("Invalid corrective action id [$correctiveActionId]");
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
          {
             if ($this->authenticate([Permission::VIEW_CORRECTIVE_ACTION]))
             {
                // Fetch single component.
                if (isset($params["correctiveActionId"]))
                {
                   $correctiveActionId = $params->getInt("correctiveActionId");
                   
                   $correctiveAction = CorrectiveAction::load($correctiveActionId);
                   
                   if ($correctiveAction)
                   {
                      $this->result->success = true;
                      $this->result->correctiveAction = $correctiveAction;
                      
                      CorrectiveActionPage::augmentCorrectiveAction($correctiveAction);
                   }
                   else
                   {
                      $this->error("Invalid corrective action id [$correctiveActionId]");
                   }
                }
                // Fetch all components.
                else
                {
                   $dateTime = Time::dateTimeObject(null);
                   
                   $dateType = FilterDateType::OCCURANCE_DATE;
                   $endDate = Time::endOfDay($dateTime->format(Time::STANDARD_FORMAT));
                   $startDate = Time::startofDay($dateTime->modify("-1 month")->format(Time::STANDARD_FORMAT));
                   $allActive = false;
                   
                   if (isset($params["dateType"]))
                   {
                      $dateType = $params->getInt("dateType");
                   }                      
                   
                   if (isset($params["startDate"]))
                   {
                      $startDate = Time::startOfDay($params["startDate"]);
                   }
                   
                   if (isset($params["endDate"]))
                   {
                      $endDate = Time::endOfDay($params["endDate"]);
                   }
                   
                   if (isset($params["activeActions"]))
                   {
                      $allActive = $params->getBool("activeActions");
                   }
                   
                   $this->result->success = true;
                   $this->result->correctiveActions = CorrectiveActionManager::getCorrectiveActions($dateType, $startDate, $endDate, $allActive);
                   
                   // Augment data.
                   foreach ($this->result->correctiveActions as $correctiveAction)
                   {
                      CorrectiveActionPage::augmentCorrectiveAction($correctiveAction);
                   }
                }
             }
             break;             
          }
          
          case "attach_file":
          {
             if (Page::requireParams($params, ["correctiveActionId", "filename", "description"]))
             {
                if (isset($_FILES["attachment"]) &&
                          ($_FILES["attachment"]["name"] != ""))
                {
                   $correctiveActionId = $params->getInt("correctiveActionId");
                   $file = $_FILES["attachment"];
                   $filename = $params->get("filename");
                   $description = $params->get("description");
                   
                   // Use the actual filename if an alternate wasn't provided.
                   if (empty($filename))
                   {
                      $filename = $_FILES["attachment"]["name"];
                   }
                   
                   // Constrain the filename to an appropriate size.
                   $filename = Upload::shortenFilename($filename, Attachment::MAX_FILENAME_SIZE);
                   
                   $storedFilename = null;
                   $uploadStatus = Upload::uploadAttachment($file, $storedFilename);
                   
                   switch ($uploadStatus)
                   {
                      case UploadStatus::UPLOADED:
                      {
                         $attachment = new Attachment();
                         $attachment->componentId = $correctiveActionId;
                         $attachment->componentType = ComponentType::CORRECTIVE_ACTION;
                         $attachment->filename = $filename;
                         $attachment->storedFilename = $storedFilename;
                         $attachment->description = $description;
                         
                         if (Attachment::save($attachment))
                         {
                            $this->result->success = true;
                            $this->result->correctiveActionId = $correctiveActionId;
                            $this->result->attachment = $attachment;
                            
                            ActivityLog::logAddCorrectiveActionAttachment(
                               Authentication::getAuthenticatedUser()->employeeNumber,
                               $attachment->componentId,
                               $attachment->attachmentId,
                               $attachment->filename);
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
                
                $attachment = Attachment::load($attachmentId);
                
                if ($attachment)
                {
                   if (Attachment::delete($attachmentId))
                   {
                      // Delete file
                      Upload::deleteFile($attachment->storedFilename);
                      
                      $this->result->success = true;
                      $this->attachmentId = $attachmentId;
                      
                      ActivityLog::logDeleteCorrectiveActionAttachment(
                         Authentication::getAuthenticatedUser()->employeeNumber,
                         $attachment->componentId,
                         $attachmentId,
                         $attachment->filename);
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
          
          default:
          {
             $this->error("Unsupported command [{$this->getRequest($params)}]");
             break;
          }
       }
       
       echo json_encode($this->result);
    }
    
    private function getCorrectiveActionParams(&$correctiveAction, $params)
    {
       // Required parameters.
       $correctiveAction->employee = $params->getInt("employeeNumber");
       $correctiveAction->occuranceDate = $params->get("occuranceDate");
       $correctiveAction->description = $params->get("description");
       
       // Optional parameters.
       
       if ($params->keyExists("jobId"))
       {
          $correctiveAction->jobId = $params->getInt("jobId");
       }
       else if ($params->keyExists("shipmentId"))
       {
          $correctiveAction->shipmentId = $params->getInt("shipmentId");
       }
       
       if ($params->keyExists("batchSize"))
       {
          $correctiveAction->batchSize = $params->getInt("batchSize");
       }
       
       if ($params->keyExists("dimensionalDefectCount"))
       {
          $correctiveAction->dimensionalDefectCount = $params->getInt("dimensionalDefectCount");
       }
       
       if ($params->keyExists("platingDefectCount"))
       {
          $correctiveAction->platingDefectCount = $params->getInt("platingDefectCount");
       }
       
       if ($params->keyExists("otherDefectCount"))
       {
          $correctiveAction->otherDefectCount = $params->getInt("otherDefectCount");
       }
       
       // Disposition
       if ($params->keyExists("disposition"))
       {
          $correctiveAction->disposition = CorrectiveAction::NO_DISPOSITION;
          
          foreach ($params->get("disposition") as $disposition)
          {
             Disposition::setDisposition($disposition, $correctiveAction->disposition);
          }
       }
       
       if ($params->keyExists("rootCause"))
       {
          $correctiveAction->rootCause = $params->get("rootCause");
       }
       
       if ($params->keyExists("dmrNumber"))
       {
          $correctiveAction->dmrNumber = $params->get("dmrNumber");
       }
       
       if ($params->keyExists("initiator"))
       {
          $correctiveAction->initiator = $params->getInt("initiator");
       }
       
       if ($params->keyExists("location"))
       {
          $correctiveAction->location = $params->getInt("location");
       }
    }
    
    private static function getCorrectionParams(&$correction, $correctionType, $params)
    {
       $correction->description = $params->get("description");
       $correction->dueDate = $params->get("dueDate");
       $correction->employee = $params->getInt("employee");
       $correction->responsibleDetails = $params->get("responsibleDetails");
    }
    
    private static function getReviewParams(&$review, $params)
    {
       $review->reviewDate = $params->get("reviewDate");
       $review->reviewer = $params->getInt("reviewer");
       $review->effectiveness = $params->get("effectiveness");
       $review->comments = $params->get("comments");
    }
    
    private static function augmentCorrectiveAction(&$correctiveAction)
    {
       $correctiveAction->correctiveActionNumber = $correctiveAction->getCorrectiveActionNumber();
     
       $correctiveAction->statusLabel = CorrectiveActionStatus::getLabel($correctiveAction->status);
       $correctiveAction->statusClass = CorrectiveActionStatus::getClass($correctiveAction->status);
       
       $correctiveAction->formattedOccuranceDate = $correctiveAction->occuranceDate ? Time::dateTimeObject($correctiveAction->occuranceDate)->format("n/j/Y") : "";
       
       // customerName
       $customer = Customer::load($correctiveAction->getCustomerId());
       if ($customer)
       {
          $correctiveAction->customerName = $customer->customerName;
       }
       
       // pptpPartNumber, customerPartNumber
       $job = JobInfo::load($correctiveAction->jobId);
       if ($job)
       {
          $pptpPartNumber = JobInfo::getJobPrefix($job->jobNumber);
          $part = Part::load($pptpPartNumber, false);  // Use PPTP number.
          if ($part)
          {
             $correctiveAction->pptpPartNumber = $part->pptpNumber;
             $correctiveAction->customerPartNumber = $part->customerNumber;
          }
       }
       
       $correctiveAction->totalDefectCount = $correctiveAction->getTotalDefectCount();
       
       $correctiveAction->locationLabel = 
          ($correctiveAction->location != CorrectiveActionLocation::UNKNOWN) ? 
             CorrectiveActionLocation::getLabel($correctiveAction->location) : 
             null;
       
       $correctiveAction->initiatorLabel =
          ($correctiveAction->initiator != CorrectiveActionInitiator::UNKNOWN) ?
             CorrectiveActionInitiator::getLabel($correctiveAction->initiator) :
             null;
    }
 }
 
 ?>