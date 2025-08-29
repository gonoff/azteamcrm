<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Customer;

class OrderItemController extends Controller
{
    // Index method removed - order items are now displayed in the order show page
    // public function index($order_id)
    // {
    //     $this->requireAuth();
    //     
    //     $order = new Order();
    //     $orderData = $order->find($order_id);
    //     
    //     if (!$orderData) {
    //         http_response_code(404);
    //         $this->view('errors/404');
    //         return;
    //     }
    //     
    //     $orderItems = $orderData->getOrderItems();
    //     $customer = $orderData->getCustomer();
    //     
    //     $this->view('order-items/index', [
    //         'order' => $orderData,
    //         'customer' => $customer,
    //         'orderItems' => $orderItems,
    //         'title' => 'Order Items - Order #' . $orderData->order_id,
    //         'csrf_token' => $this->csrf()
    //     ]);
    // }
    
    public function create($order_id)
    {
        $this->requireAuth();
        
        $order = new Order();
        $orderData = $order->find($order_id);
        
        if (!$orderData) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }
        
        $customer = $orderData->getCustomer();
        
        $this->view('order-items/form', [
            'order' => $orderData,
            'customer' => $customer,
            'orderItem' => null,
            'title' => 'Add Order Item - Order #' . $orderData->order_id,
            'csrf_token' => $this->csrf(),
            'action' => '/azteamcrm/orders/' . $order_id . '/order-items/store'
        ]);
    }
    
    public function store($order_id)
    {
        $this->requireAuth();
        
        if (!$this->isPost()) {
            if (isset($_POST['ajax'])) {
                $this->json(['success' => false, 'message' => 'Invalid request method']);
                return;
            }
            $this->redirect('/orders/' . $order_id);
        }
        
        $this->verifyCsrf();
        
        $order = new Order();
        $orderData = $order->find($order_id);
        
        if (!$orderData) {
            if (isset($_POST['ajax'])) {
                $this->json(['success' => false, 'message' => 'Order not found']);
                return;
            }
            $this->redirect('/orders');
        }
        
        $data = $this->sanitize($_POST);
        $isAjax = isset($data['ajax']);
        
        // Validation
        $errors = $this->validate($data, [
            'product_description' => 'required',
            'quantity' => 'required|min:1',
            'unit_price' => 'required'
        ]);
        
        if (!empty($errors)) {
            if ($isAjax) {
                $this->json(['success' => false, 'errors' => array_values($errors)]);
                return;
            }
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $data;
            $this->redirect('/orders/' . $order_id);
        }
        
        // Set required fields
        $data['order_id'] = $order_id;
        $data['user_id'] = $_SESSION['user_id'];
        $data['order_item_status'] = $data['order_item_status'] ?? 'pending';
        
        // Handle optional fields - convert empty strings to null
        $data['product_type'] = (!empty($data['product_type']) ? $data['product_type'] : null);
        $data['product_size'] = (!empty($data['product_size']) ? $data['product_size'] : null);
        $data['custom_method'] = (!empty($data['custom_method']) ? $data['custom_method'] : null);
        $data['custom_area'] = (!empty($data['custom_area']) ? $data['custom_area'] : null);
        $data['supplier_status'] = (!empty($data['supplier_status']) ? $data['supplier_status'] : null);
        $data['note_item'] = (!empty($data['note_item']) ? $data['note_item'] : null);
        
        $orderItem = new OrderItem();
        $newItem = $orderItem->create($data);
        
        if ($newItem) {
            // Update order total
            $orderData->calculateTotal();
            
            // Sync order status from items
            $orderData->syncStatusFromItems();
            
            if ($isAjax) {
                $this->json([
                    'success' => true, 
                    'message' => 'Order item added successfully!',
                    'item_id' => $newItem->order_item_id
                ]);
                return;
            }
            
            $_SESSION['success'] = 'Order item added successfully!';
            $this->redirect('/orders/' . $order_id);
        } else {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Failed to add order item.']);
                return;
            }
            $_SESSION['error'] = 'Failed to add order item.';
            $this->redirect('/orders/' . $order_id);
        }
    }
    
    public function edit($id)
    {
        $this->requireAuth();
        
        $orderItem = new OrderItem();
        $itemData = $orderItem->find($id);
        
        if (!$itemData) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }
        
        $order = $itemData->getOrder();
        $customer = $order->getCustomer();
        
        $this->view('order-items/form', [
            'order' => $order,
            'customer' => $customer,
            'orderItem' => $itemData,
            'title' => 'Edit Order Item #' . $itemData->order_item_id,
            'csrf_token' => $this->csrf(),
            'action' => '/azteamcrm/order-items/' . $id . '/update'
        ]);
    }
    
    public function update($id)
    {
        $this->requireAuth();
        
        if (!$this->isPost()) {
            if (isset($_POST['ajax'])) {
                $this->json(['success' => false, 'message' => 'Invalid request method']);
                return;
            }
            $this->redirect('/orders');
        }
        
        $this->verifyCsrf();
        
        $orderItem = new OrderItem();
        $itemData = $orderItem->find($id);
        
        if (!$itemData) {
            if (isset($_POST['ajax'])) {
                $this->json(['success' => false, 'message' => 'Order item not found for ID: ' . $id]);
                return;
            }
            $this->redirect('/orders');
        }
        
        $data = $this->sanitize($_POST);
        $isAjax = isset($data['ajax']);
        
        // Validation
        $errors = $this->validate($data, [
            'product_description' => 'required',
            'quantity' => 'required|min:1',
            'unit_price' => 'required'
        ]);
        
        if (!empty($errors)) {
            if ($isAjax) {
                $this->json(['success' => false, 'errors' => array_values($errors)]);
                return;
            }
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $data;
            $this->redirect('/orders/' . $itemData->order_id);
        }
        
        // Handle optional fields
        $data['product_type'] = $data['product_type'] ?? null;
        $data['product_size'] = $data['product_size'] ?? null;
        $data['custom_method'] = $data['custom_method'] ?? null;
        $data['custom_area'] = $data['custom_area'] ?? null;
        $data['supplier_status'] = $data['supplier_status'] ?? null;
        $data['note_item'] = $data['note_item'] ?? null;
        
        // Handle status fields specifically
        if (isset($data['order_item_status'])) {
            $data['order_item_status'] = $data['order_item_status'];
        }
        
        // Debug logging for AJAX requests
        if ($isAjax) {
            error_log('OrderItem Update - Data to be saved: ' . json_encode($data));
            error_log('OrderItem Update - Item ID: ' . $id);
        }
        
        try {
            // Direct database update - bypassing Model complexity
            $db = \App\Core\Database::getInstance();
            $connection = $db->getConnection();
            
            // Build the UPDATE query with all fields explicitly
            $sql = "UPDATE order_items SET 
                    product_description = :product_description,
                    product_type = :product_type,
                    product_size = :product_size,
                    quantity = :quantity,
                    unit_price = :unit_price,
                    custom_method = :custom_method,
                    custom_area = :custom_area,
                    order_item_status = :order_item_status,
                    supplier_status = :supplier_status,
                    note_item = :note_item
                    WHERE order_item_id = :order_item_id";
            
            // Prepare parameters - ensure proper types and handle empty strings
            $params = [
                'order_item_id' => (int)$id,
                'product_description' => (string)($data['product_description'] ?? ''),
                'product_type' => (!empty($data['product_type']) ? $data['product_type'] : null),
                'product_size' => (!empty($data['product_size']) ? $data['product_size'] : null),
                'quantity' => (int)($data['quantity'] ?? 1),
                'unit_price' => (float)($data['unit_price'] ?? 0),
                'custom_method' => (!empty($data['custom_method']) ? $data['custom_method'] : null),
                'custom_area' => (!empty($data['custom_area']) ? $data['custom_area'] : null),
                'order_item_status' => $data['order_item_status'] ?? 'pending',
                'supplier_status' => (!empty($data['supplier_status']) ? $data['supplier_status'] : null),
                'note_item' => (!empty($data['note_item']) ? $data['note_item'] : null)
            ];
            
            if ($isAjax) {
                error_log('Direct SQL Update - Item ID from URL: ' . $id);
                error_log('Direct SQL Update - Query: ' . $sql);
                error_log('Direct SQL Update - Params: ' . json_encode($params));
            }
            
            // Execute the update with direct PDO for better error handling
            try {
                $stmt = $connection->prepare($sql);
                $updateResult = $stmt->execute($params);
                
                if (!$updateResult && $isAjax) {
                    $errorInfo = $stmt->errorInfo();
                    error_log('PDO Error Info: ' . json_encode($errorInfo));
                    error_log('PDO Error Code: ' . $stmt->errorCode());
                }
            } catch (\PDOException $pdoEx) {
                if ($isAjax) {
                    error_log('PDO Exception during update: ' . $pdoEx->getMessage());
                    error_log('PDO Exception Code: ' . $pdoEx->getCode());
                }
                $updateResult = false;
            }
            
            if ($updateResult) {
                // Verify the update by querying the database
                $verifySQL = "SELECT * FROM order_items WHERE order_item_id = :id";
                $verifyStmt = $db->query($verifySQL, ['id' => $id]);
                $updatedItem = $verifyStmt ? $verifyStmt->fetch(\PDO::FETCH_ASSOC) : null;
                
                if ($isAjax) {
                    error_log('Update Verification - Item after update: ' . json_encode($updatedItem));
                }
                
                // Update order total using the existing order
                $order = $itemData->getOrder();
                if ($order) {
                    $order->calculateTotal();
                    // Sync order status from items
                    $order->syncStatusFromItems();
                }
                
                if ($isAjax) {
                    $this->json([
                        'success' => true, 
                        'message' => 'Order item updated successfully!',
                        'item' => [
                            'id' => $id,
                            'product_description' => $updatedItem['product_description'] ?? $params['product_description'],
                            'quantity' => $updatedItem['quantity'] ?? $params['quantity'],
                            'unit_price' => $updatedItem['unit_price'] ?? $params['unit_price'],
                            'total_price' => ($updatedItem['quantity'] ?? $params['quantity']) * ($updatedItem['unit_price'] ?? $params['unit_price'])
                        ]
                    ]);
                    return;
                }
                
                $_SESSION['success'] = 'Order item updated successfully!';
                $this->redirect('/orders/' . $itemData->order_id);
            } else {
                // Get last database error if available
                $errorMessage = 'Failed to update order item.';
                if ($isAjax) {
                    error_log('OrderItem Update Failed - Update returned false');
                    error_log('Check the PHP error log for PDO Error Info and Exception details');
                    
                    // Add diagnostic info to error message
                    $errorMessage .= ' Check server logs for details. Item ID: ' . $id;
                    
                    $this->json(['success' => false, 'message' => $errorMessage]);
                    return;
                }
                $_SESSION['error'] = $errorMessage;
                $this->redirect('/orders/' . $itemData->order_id);
            }
        } catch (\Exception $e) {
            error_log('OrderItem Update Exception: ' . $e->getMessage());
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                return;
            }
            $_SESSION['error'] = 'An error occurred while updating the item.';
            $this->redirect('/orders/' . $itemData->order_id);
        }
    }
    
    public function delete($id)
    {
        $this->requireAuth();
        $this->verifyCsrf();
        
        if (!$this->isPost()) {
            $this->redirect('/orders');
            return;
        }
        
        $orderItem = new OrderItem();
        $itemData = $orderItem->find($id);
        
        if (!$itemData) {
            $_SESSION['error'] = 'Order item not found.';
            $this->redirect('/orders');
            return;
        }
        
        $order_id = $itemData->order_id;
        
        if ($itemData->delete()) {
            // Update order total
            $order = new Order();
            $orderData = $order->find($order_id);
            if ($orderData) {
                $orderData->calculateTotal();
                // Sync order status from items
                $orderData->syncStatusFromItems();
            }
            
            $_SESSION['success'] = 'Order item deleted successfully!';
        } else {
            $_SESSION['error'] = 'Failed to delete order item.';
        }
        
        $this->redirect('/orders/' . $order_id);
    }
    
    public function updateStatus($id)
    {
        $this->requireAuth();
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        $this->verifyCsrf();
        
        $orderItem = new OrderItem();
        $itemData = $orderItem->find($id);
        
        if (!$itemData) {
            $this->json(['success' => false, 'message' => 'Item not found']);
            return;
        }
        
        $statusType = $this->sanitize($_POST['status_type'] ?? '');
        $newStatus = $this->sanitize($_POST['status'] ?? '');
        
        if (!$statusType || !$newStatus) {
            $this->json(['success' => false, 'message' => 'Invalid status data']);
            return;
        }
        
        $success = false;
        
        if ($statusType === 'order_item_status') {
            $success = $itemData->updateStatus($newStatus);
        } elseif ($statusType === 'supplier_status') {
            $success = $itemData->updateSupplierStatus($newStatus);
        }
        
        if ($success) {
            // Sync order status if item status was updated
            if ($statusType === 'order_item_status') {
                $order = new Order();
                $orderData = $order->find($itemData->order_id);
                if ($orderData) {
                    $orderData->syncStatusFromItems();
                }
            }
            
            $this->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'badge' => $statusType === 'order_item_status' 
                    ? $itemData->getStatusBadge() 
                    : $itemData->getSupplierStatusBadge()
            ]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to update status']);
        }
    }
    
    public function updateInline($id)
    {
        $this->requireAuth();
        $this->verifyCsrf();
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request']);
            return;
        }
        
        $orderItem = new OrderItem();
        $itemData = $orderItem->find($id);
        
        if (!$itemData) {
            $this->json(['success' => false, 'message' => 'Item not found']);
            return;
        }
        
        $field = $this->sanitize($_POST['field'] ?? '');
        $value = $this->sanitize($_POST['value'] ?? '');
        
        // Validate field is allowed for inline editing
        $allowedFields = ['quantity', 'unit_price', 'product_description', 'order_item_status'];
        if (!in_array($field, $allowedFields)) {
            $this->json(['success' => false, 'message' => 'Field not editable']);
            return;
        }
        
        // Update the field
        $itemData->$field = $value;
        
        if ($itemData->update()) {
            // Recalculate order total if price/quantity changed
            if (in_array($field, ['quantity', 'unit_price'])) {
                $order = $itemData->getOrder();
                $order->calculateTotal();
            }
            
            // Sync order status if item status changed
            if ($field === 'order_item_status') {
                $order = $itemData->getOrder();
                $order->syncStatusFromItems();
            }
            
            $this->json([
                'success' => true,
                'message' => 'Item updated successfully',
                'new_total' => $itemData->getOrder()->order_total
            ]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to update item']);
        }
    }
}
