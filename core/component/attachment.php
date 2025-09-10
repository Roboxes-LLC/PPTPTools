<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/componentType.php';

class Attachment
{
   const UNKNOWN_ATTACHMENT_ID = 0;
   
   const UNKNOWN_COMPONENT_ID = 0;
   
   const MAX_FILENAME_SIZE = 32;

   public $attachmentId;
   public $componentType;
   public $componentId;
   public $filename;
   public $storedFilename;
   public $description;
   
   public function __construct()
   {
      $this->attachmentId = Attachment::UNKNOWN_ATTACHMENT_ID;
      $this->componentType = ComponentType::UNKNOWN;
      $this->componentId = Attachment::UNKNOWN_COMPONENT_ID;
      $this->filename = null;
      $this->storedFilename = null;
      $this->description = null;
   }
   
   public function initialize($row)
   {
      $this->attachmentId = intval($row["attachmentId"]);
      $this->componentType = intval($row["componentType"]);
      $this->componentId = intval($row["componentId"]);
      $this->filename = $row["filename"];
      $this->storedFilename = $row["storedFilename"];
      $this->description = $row["description"];
   }
   
   // **************************************************************************
   // Component interface
   
   public static function load($attachmentId)
   {
      $attachment = null;
      
      $result = PPTPDatabaseAlt::getInstance()->getAttachment($attachmentId);
      
      if ($result && ($row = $result[0]))
      {
         $attachment = new Attachment();
         
         $attachment->initialize($row);
      }
      
      return ($attachment);
   }
   
   public static function save($attachment)
   {
      $success = false;
      
      if ($attachment->attachmentId == Attachment::UNKNOWN_ATTACHMENT_ID)
      {
         $success = PPTPDatabaseAlt::getInstance()->addAttachment($attachment);
      }
      else
      {
         $success = PPTPDatabaseAlt::getInstance()->updateAttachment($attachment);
      }
     
      return ($success);
   }
   
   public static function delete($attachmentId)
   {
      return (PPTPDatabaseAlt::getInstance()->deleteAttachment($attachmentId));
   }
}