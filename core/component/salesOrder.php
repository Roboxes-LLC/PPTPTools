<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/userInfo.php';
require_once ROOT.'/core/common/pptpDatabase.php';
require_once ROOT.'/core/component/customer.php';

abstract class SalesOrderStatus
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const OPEN = SalesOrderStatus::FIRST;
   const SHIPPED = 2;
   const LAST = 3;
   const COUNT = SalesOrderStatus::LAST - SalesOrderStatus::FIRST;
   
   public static $values = array(SalesOrderStatus::OPEN, SalesOrderStatus::SHIPPED);
   
   public static function getLabel($status)
   {
      $labels = array("", "Open", "Shipped");
      
      return ($labels[$status]);
   }
   
   public static function getOptions($selectedStatus)
   {
      $html = "<option style=\"display:none\">";
      
      foreach (SalesOrderStatus::$values as $status)
      {
         $label = SalesOrderStatus::getLabel($status);
         $value = $status;
         $selected = ($status == $selectedStatus) ? "selected" : "";
         
         $html .= "<option value=\"$value\" $selected>$label</option>";
      }
      
      return ($html);
   }
}

class SalesOrder
{
   const UNKNOWN_SALES_ORDER_ID = 0;
   
   const MAX_PACKING_LIST_FILENAME_SIZE = 128;
   
   public $salesOrderId;
   public $author;
   public $dateTime;
   public $orderNumber;
   public $customerId;
   public $customerPartNumber;
   public $poNumber;   
   public $orderDate;
   public $quantity;
   public $unitPrice;
   public $dueDate;
   public $orderStatus;
   public $comments;
   public $packingList;
   
   public function __construct()
   {
      $this->salesOrderId = SalesOrder::UNKNOWN_SALES_ORDER_ID;
      $this->author = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->dateTime = null;
      $this->orderNumber = null;
      $this->customerId = Customer::UNKNOWN_CUSTOMER_ID;
      $this->customerPartNumber;
      $this->poNumber = null;
      $this->orderDate = null;
      $this->quantity = 0;
      $this->unitPrice = 0.0;
      $this->dueDate = null;
      $this->orderStatus = SalesOrderStatus::UNKNOWN;
      $this->comments = null;
      $this->packingList = null;
   }
   
   public function initialize($row)
   {
      $this->salesOrderId = intval($row["salesOrderId"]);
      $this->author = intval($row["author"]);
      $this->dateTime = $row["dateTime"] ?
                           Time::fromMySqlDate($row["dateTime"]) :
                            null;
      $this->orderNumber = $row["orderNumber"];
      $this->customerId = intval($row["customerId"]);
      $this->customerPartNumber = $row["customerPartNumber"];
      $this->poNumber = $row["poNumber"];
      $this->orderDate = $row["orderDate"] ?
                            Time::fromMySqlDate($row["orderDate"]) :
                            null;
      $this->quantity = intval($row["quantity"]);
      $this->unitPrice = floatval($row["unitPrice"]);
      $this->dueDate = $row["dueDate"] ?
                          Time::fromMySqlDate($row["dueDate"]) :
                          null;
      $this->orderStatus = intval($row["orderStatus"]);
      $this->comments = $row["comments"];
      $this->packingList = $row["packingList"];
   }
   
   // **************************************************************************
   // Component interface
   
   public static function load($salesOrderId)
   {
      $salesOrder = null;
      
      $result = PPTPDatabaseAlt::getInstance()->getSalesOrder($salesOrderId);
      
      if ($result && ($row = $result[0]))
      {
         $salesOrder = new SalesOrder();
         
         $salesOrder->initialize($row);
      }
      
      return ($salesOrder);
   }
   
   public static function save($salesOrder)
   {
      $success = false;
      
      if ($salesOrder->salesOrderId == SalesOrder::UNKNOWN_SALES_ORDER_ID)
      {
         $success = PPTPDatabaseAlt::getInstance()->addSalesOrder($salesOrder);
      }
      else
      {
         $success = PPTPDatabaseAlt::getInstance()->updateSalesOrder($salesOrder);
      }
      
      return ($success);
   }
   
   public static function delete($salesOrderId)
   {
      return (PPTPDatabaseAlt::getInstance()->deleteSalesOrder($salesOrderId));
   }
   
   // **************************************************************************
   
   public function getTotal()
   {
      return ($this->unitPrice * $this->quantity);
   }
}