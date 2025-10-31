<?php

require_once 'root.php';

abstract class UploadStatus
{
   const FIRST         = 0;
   const UPLOADED      = UploadStatus::FIRST;
   const BAD_FILE_TYPE = 1;
   const BAD_FILE_SIZE = 2;
   const FILE_ERROR    = 3;
   const LAST          = 4;
   
   static function toString($uploadStatus)
   {
      $strings = array("UPLOADED", "BAD_FILE_TYPE", "BAD_FILE_SIZE", "FILE_ERROR");
      
      $stringVal = "UNKNOWN";
      
      if (($uploadStatus >= UploadStatus::FIRST) && ($uploadStatus < UploadStatus::LAST))
      {
         $stringVal = $strings[$uploadStatus];
      }
      
      return ($stringVal);
   }
}

class Upload
{
   static function uploadCustomerPrint($file)
   {
      global $UPLOADS;
      
      $returnStatus = UploadStatus::UPLOADED;
      
      $target = $UPLOADS . basename($file["name"]);
      
      if (!Upload::validateFileFormat($file, array("pdf")))
      {
         $returnStatus = UploadStatus::BAD_FILE_TYPE;
      }
      else if (!Upload::validateFileSize($file, 2000000))  // 2MB
      {
         $returnStatus = UploadStatus::BAD_FILE_SIZE;
      }
      else if (!move_uploaded_file($file["tmp_name"], $target))
      {
         $returnStatus = UploadStatus::FILE_ERROR;
      }
      
      return ($returnStatus);
   }
   
   static function uploadMaterialCert($file)
   {
      global $DOC_ROOT;
      global $MATERIAL_CERTS_DIR;
      
      $returnStatus = UploadStatus::UPLOADED;
      
      $target = $DOC_ROOT . $MATERIAL_CERTS_DIR . basename($file["name"]);
      
      if (!Upload::validateFileFormat($file, array("pdf")))
      {
         $returnStatus = UploadStatus::BAD_FILE_TYPE;
      }
      else if (!Upload::validateFileSize($file, 2000000))  // 2MB
      {
         $returnStatus = UploadStatus::BAD_FILE_SIZE;
      }
      else if (!move_uploaded_file($file["tmp_name"], $target))
      {
         $returnStatus = UploadStatus::FILE_ERROR;
      }
      
      return ($returnStatus);
   }
   
   static function uploadQuoteAttachment($file, &$storedFilename)
   {
      global $UPLOADS;
      
      $returnStatus = UploadStatus::UPLOADED;
      
      $storedFilename = basename($file["name"]);
      
      $target = $UPLOADS . $storedFilename;
      
      if (!Upload::validateFileFormat($file, array("pdf")))
      {
         $returnStatus = UploadStatus::BAD_FILE_TYPE;
      }
      else if (!Upload::validateFileSize($file, 2000000))  // 2MB
      {
         $returnStatus = UploadStatus::BAD_FILE_SIZE;
      }
      else if (!move_uploaded_file($file["tmp_name"], $target))
      {
         $returnStatus = UploadStatus::FILE_ERROR;
      }
      
      return ($returnStatus);
   }
   
   static function uploadAttachment($file, &$storedFilename)
   {
      global $UPLOADS;
      
      $returnStatus = UploadStatus::UPLOADED;
      
      $storedFilename = basename($file["name"]);

      $target = $UPLOADS . $storedFilename;
      
      if (!Upload::validateFileFormat($file, array("pdf", "png", "jpg")))
      {
         $returnStatus = UploadStatus::BAD_FILE_TYPE;
      }
      else if (!Upload::validateFileSize($file, 10000000))  // 10MB
      {
         $returnStatus = UploadStatus::BAD_FILE_SIZE;
      }
      else if (!move_uploaded_file($file["tmp_name"], $target))
      {
         $returnStatus = UploadStatus::FILE_ERROR;
      }      
      
      return ($returnStatus);
   }
   
   static function uploadPackingList($file)
   {
      global $DOC_ROOT;
      global $PACKING_LISTS_DIR;
      
      $returnStatus = UploadStatus::UPLOADED;
      
      $target = $DOC_ROOT . $PACKING_LISTS_DIR . basename($file["name"]);
      
      if (!Upload::validateFileFormat($file, array("pdf")))
      {
         $returnStatus = UploadStatus::BAD_FILE_TYPE;
      }
      else if (!Upload::validateFileSize($file, 2000000))  // 2MB
      {
         $returnStatus = UploadStatus::BAD_FILE_SIZE;
      }
      else if (!move_uploaded_file($file["tmp_name"], $target))
      {
         $returnStatus = UploadStatus::FILE_ERROR;
      }
      
      return ($returnStatus);
   }
   
   static function validateFileSize($file, $maxSize)
   {
      return ($file["size"] < $maxSize);
   }
   
   static function validateFileFormat($file, $extensions)
   {
      $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
      
      return (in_array($extension, $extensions));
   }
   
   static function generateFilename($filename, $prefix = "")
   {
      $extension = pathinfo($filename, PATHINFO_EXTENSION);
      
      $uniqueFilename = uniqid($prefix) . "." . $extension;

      return ($uniqueFilename);
   }
   
   static function deleteFile($filename)
   {
      global $UPLOADS;
      
      $success = true;
      
      $target = $UPLOADS . $filename;
      
      if (file_exists($target))
      {
         $success = unlink($target);
      }
      
      return ($success);
   }
   
   static function deletePackingList($filename)
   {
      global $DOC_ROOT;
      global $PACKING_LISTS_DIR;
      
      $success = true;
      
      $target = $DOC_ROOT . $PACKING_LISTS_DIR . $filename;
      
      if (file_exists($target))
      {
         $success = unlink($target);
      }
      
      return ($success);
   }
   
   static function shortenFilename($filename, $maxSize)
   {
      $shortenedFilename = $filename;
      
      if (strlen($shortenedFilename) > $maxSize)
      {
         $extension = pathinfo($filename, PATHINFO_EXTENSION);
         
         if (!empty($extension))
         {
            $availableLength = $maxSize - strlen($extension) - 1;
            $shortenedFilename = substr($shortenedFilename, 0, $availableLength) . "." . $extension;
         }
         else
         {
            $availableLength = $maxSize;
            substr($shortenedFilename, 0, $availableLength);
         }
      }

      return ($shortenedFilename);
   }
}