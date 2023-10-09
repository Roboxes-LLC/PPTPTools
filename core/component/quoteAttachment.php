<?php

if (!defined('ROOT')) require_once '../../root.php';

class QuoteAttachment
{
   const UNKNOWN_ATTACHMENT_ID = 0;
   
   const UNKNOWN_QUOTE_ID = 0;
   
   const MAX_FILENAME_SIZE = 32;

   public $attachmentId;
   public $quoteId;
   public $filename;
   public $storedFilename;
   public $description;
   
   public function __construct()
   {
      $this->attachmentId = QuoteAttachment::UNKNOWN_ATTACHMENT_ID;
      $this->quoteId = QuoteAttachment::UNKNOWN_QUOTE_ID;
      $this->filename = null;
      $this->storedFilename = null;
      $this->description = null;
   }
   
   public function initialize($row)
   {
      $this->attachmentId = intval($row["attachmentId"]);
      $this->quoteId = intval($row["quoteId"]);
      $this->filename = $row["filename"];
      $this->storedFilename = $row["storedFilename"];
      $this->description = $row["description"];
   }
   
   // **************************************************************************
   // Component interface
   
   public static function load($attachmentId)
   {
      $quoteAttachment = null;
      
      $result = PPTPDatabaseAlt::getInstance()->getQuoteAttachment($attachmentId);
      
      if ($result && ($row = $result[0]))
      {
         $quoteAttachment = new QuoteAttachment();
         
         $quoteAttachment->initialize($row);
      }
      
      return ($quoteAttachment);
   }
   
   public static function save($quoteAttachment)
   {
      $success = false;
      
      if ($quoteAttachment->attachmentId == QuoteAttachment::UNKNOWN_ATTACHMENT_ID)
      {
         $success = PPTPDatabaseAlt::getInstance()->addQuoteAttachment($quoteAttachment);
      }
      else
      {
         $success = PPTPDatabaseAlt::getInstance()->updateQuoteAttachment($quoteAttachment);
      }
     
      return ($success);
   }
   
   public static function delete($attachmentId)
   {
      return (PPTPDatabaseAlt::getInstance()->deleteQuoteAttachment($attachmentId));
   }
}