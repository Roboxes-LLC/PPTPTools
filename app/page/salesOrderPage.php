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
                if (Page::requireParams($params, ["salesOrderId", "orderNumber", "customerId", "customerPartNumber", "poNumber", "orderDate", "quantity", "unitPrice", "dueDate", "orderStatus", "comments"]))
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
             if ($this->authenticate([Permission::EDIT_SALES_ORDER]))
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
                      $this->error("Invalid sales order id [salesOrderId]");
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
       $salesOrder->customerId = $params->getInt("customerId");
       $salesOrder->customerPartNumber = $params->get("customerPartNumber");
       $salesOrder->poNumber = $params->get("poNumber");
       $salesOrder->orderDate = $params->get("orderDate");
       $salesOrder->quantity = $params->getInt("quantity");
       $salesOrder->unitPrice = $params->getFloat("unitPrice");
       $salesOrder->dueDate = $params->get("dueDate");
       $salesOrder->orderStatus = $params->getInt("orderStatus");
       $salesOrder->comments = $params->get("comments");
    }
    
    private static function augmentSalesOrder(&$salesOrder)
    {
       $userInfo = UserInfo::load($salesOrder->author);
       $salesOrder->authorFullName = $salesOrder ? $userInfo->getFullName() : "";
       
       $customer = Customer::load($salesOrder->customerId);
       $salesOrder->customerName = $customer ? $customer->customerName : "";
       
       $salesOrder->formattedDateTime = Time::dateTimeObject($salesOrder->dateTime)->format("n/j/Y g:i a");
       
       $salesOrder->formattedOrderDate = Time::dateTimeObject($salesOrder->orderDate)->format("n/j/Y");
       
       $salesOrder->formattedUnitPrice = "$".number_format($salesOrder->unitPrice, 4);
       
       $salesOrder->formattedDueDate = Time::dateTimeObject($salesOrder->dueDate)->format("n/j/Y");
       
       $salesOrder->orderStatusLabel = SalesOrderStatus::getLabel($salesOrder->orderStatus);
    }
 }
 
 ?>