<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/app/page/page.php';
require_once ROOT.'/core/manager/activityLog.php';
require_once ROOT.'/core/manager/salesOrderManager.php';

class SalesOrderPage extends Page
{
    public function handleRequest($params)
    {
       switch ($this->getRequest($params))
       {
          case "save_sales_order":
          {
             if ($this->authenticate([Permission::EDIT_SALES_ORDER]))
             {
                if (Page::requireParams($params, ["salesOrderId", "orderNumber", "pptpPartNumber", "poNumber", "orderDate", "quantity", "dueDate", "orderStatus", "comments"]))
                {
                   $salesOrderId = $params->getInt("salesOrderId");
                   $newOrder = ($salesOrderId == SalesOrder::UNKNOWN_SALES_ORDER_ID);
                   
                   $salesOrder = null;
                   if ($newOrder)
                   {
                      $salesOrder = new SalesOrder();
                      $salesOrder->author = Authentication::getAuthenticatedUser()->employeeNumber;
                      $salesOrder->dateTime = Time::now();
                   }
                   else
                   {
                      $salesOrder = SalesOrder::load($salesOrderId);
                      
                      if (!$salesOrder)
                      {
                         $salesOrder = null;
                         $this->error("Invalid sales order id [$salesOrderId]");
                      }
                   }
                   
                   if ($salesOrder)
                   {
                      SalesOrderPage::getSalesOrderParams($salesOrder, $params);
                      
                      if (SalesOrder::save($salesOrder))
                      {
                         $this->result->salesOrderId = $salesOrder->salesOrderId;
                         $this->result->salesOrder = $salesOrder;
                         $this->result->success = true;
                         
                         //
                         // Process uploaded packing list.
                         //
                         
                         if (isset($_FILES["packingList"]) && ($_FILES["packingList"]["name"] != ""))
                         {
                            $uploadStatus = Upload::uploadPackingList($_FILES["packingList"]);
                            
                            if ($uploadStatus == UploadStatus::UPLOADED)
                            {
                               $filename = basename($_FILES["packingList"]["name"]);
                               
                               $salesOrder->packingList = $filename;
                               
                               if (!SalesOrder::save($salesOrder))
                               {
                                  $this->error("Database error");
                               }
                            }
                            else
                            {
                               $this->error("File upload failed! " . UploadStatus::toString($uploadStatus));
                            }
                         }
                         
                         /*
                         ActivityLog::logComponentActivity(
                            Authentication::getAuthenticatedUser()->employeeNumber,
                            ($newOrder ? ActivityType::ADD_SALES_ORDER : ActivityType::EDIT_SAlES_ORDER),
                            $salesOrder->salesOrderId,
                            $salesOrder->orderNumber);
                         */
                      }
                      else
                      {
                         $this->error("Database error");
                      }
                   }
                }
             }
             break;
          }
          
          case "delete_sales_order":
          {
             if ($this->authenticate([Permission::DELETE_SALES_ORDER]))
             {
                if (Page::requireParams($params, ["salesOrderId"]))
                {
                   $salesOrderId = $params->getInt("salesOrderId");
                   
                   $saleOrder = SalesOrder::load($salesOrderId);
                   
                   if ($saleOrder)
                   {
                      SalesOrder::delete($salesOrderId);
                      
                      $this->result->salesOrderId = $salesOrderId;
                      $this->result->success = true;
                      
                      /*
                      ActivityLog::logComponentActivity(
                         Authentication::getAuthenticatedUser()->employeeNumber,
                         ActivityType::DELETE_SALES_ORDER,
                         $saleOrder->salesOrderId,
                         $saleOrder->orderNumber);
                      */
                   }
                   else
                   {
                      $this->error("Invalid sales order id [$jobId]");
                   }               
                }
             }
             break;
          }                   
          
          case "fetch":
          default:
          {
             if ($this->authenticate([Permission::VIEW_SALES_ORDER]))
             {
                // Fetch single component.
                if (isset($params["salesOrderId"]))
                {
                   $salesOrderId = $params->getInt("salesOrderId");
                   
                   $salesOrder = SalesOrder::load($salesOrderId);
                   
                   if ($salesOrder)
                   {
                      $this->result->salesOrder = $salesOrder;
                      $this->result->success = true;
                      
                      SalesOrderPage::augmentSalesOrder($this->result->salesOrder);
                   }
                   else
                   {
                      $this->error("Invalid sales order id [salesOrderId]");
                   }
                }
                // Fetch all components.
                else 
                {
                   $dateTime = Time::dateTimeObject(null);
                   
                   $endDate = Time::endOfDay($dateTime->format(Time::STANDARD_FORMAT));
                   $startDate = Time::startofDay($dateTime->modify("-1 month")->format(Time::STANDARD_FORMAT));
                   $activeOrders = false;
                   
                   if (isset($params["startDate"]))
                   {
                      $startDate = Time::startOfDay($params["startDate"]);
                   }
                   
                   if (isset($params["endDate"]))
                   {
                      $endDate = Time::endOfDay($params["endDate"]);
                   }
                   
                   if (isset($params["activeOrders"]))
                   {
                      $activeOrders = $params->getBool("activeOrders");
                   }
                   
                   $this->result->success = true;
                   $this->result->salesOrders = SalesOrderManager::getSalesOrders($startDate, $endDate, $activeOrders);
                   
                   // Augment data.
                   foreach ($this->result->salesOrders as $saleOrder)
                   {
                      SalesOrderPage::augmentSalesOrder($saleOrder);
                   }
                }
             }
             break;
          }
       }
       
       echo json_encode($this->result);
    }
    
    private function getSalesOrderParams($salesOrder, $params)
    {
       $salesOrder->orderNumber = $params->get("orderNumber");
       $salesOrder->poNumber = $params->get("poNumber");
       $salesOrder->orderDate = $params->get("orderDate");
       $salesOrder->quantity = $params->getInt("quantity");
       $salesOrder->unitPrice = $params->getFloat("unitPrice");
       $salesOrder->dueDate = $params->get("dueDate");
       $salesOrder->orderStatus = $params->getInt("orderStatus");
       $salesOrder->comments = $params->get("comments");
       
       $pptpNumber = $params->get("pptpPartNumber");
       $part = Part::load($pptpNumber, Part::USE_PPTP_NUMBER);
       if ($part)
       {
          $salesOrder->customerId = $part->customerId;
          $salesOrder->customerPartNumber = $part->customerNumber; 
       }
       
       // May not be set for users without VIEW_PRICES permissions.
       if ($params->keyExists("unitPrice"))
       {
          $salesOrder->unitPrice = $params->getFloat("unitPrice");
       }
    }
    
    private static function augmentSalesOrder(&$salesOrder)
    {
       global $PACKING_LISTS_DIR;
       
       $userInfo = UserInfo::load($salesOrder->author);
       $salesOrder->authorFullName = $salesOrder ? $userInfo->getFullName() : "";
       
       $customer = Customer::load($salesOrder->customerId);
       $salesOrder->customerName = $customer ? $customer->customerName : "";
       
       $salesOrder->formattedDateTime = Time::dateTimeObject($salesOrder->dateTime)->format("n/j/Y g:i a");
       
       $salesOrder->formattedOrderDate = Time::dateTimeObject($salesOrder->orderDate)->format("n/j/Y");
       
       $salesOrder->formattedDueDate = Time::dateTimeObject($salesOrder->dueDate)->format("n/j/Y");
       
       $salesOrder->orderStatusLabel = SalesOrderStatus::getLabel($salesOrder->orderStatus);
       
       $salesOrder->packingListUrl = $salesOrder->packingList ? $PACKING_LISTS_DIR . $salesOrder->packingList : null;
       
       if (Authentication::checkPermissions(Permission::VIEW_PRICES))
       {
          $salesOrder->formattedUnitPrice = "$".number_format($salesOrder->unitPrice, 4);
          $salesOrder->total = $salesOrder->getTotal();
          $salesOrder->formattedTotal = "$".number_format($salesOrder->getTotal(), 2);
       }
       else
       {
          // Redact pricing information.
          $salesOrder->unitPrice = null;
          $salesOrder->formattedUnitPrice = null;
          $salesOrder->total = null;
          $salesOrder->formattedTotal = null;
       }
    }
 }
 
 ?>