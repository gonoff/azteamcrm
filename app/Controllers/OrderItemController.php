<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Customer;

class OrderItemController extends Controller
{
    public function index($order_id)
    {
        $this->requireAuth();
        
        $order = new Order();
        $orderData = $order->find($order_id);
        
        if (!$orderData) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }
        
        $orderItems = $orderData->getOrderItems();
        $customer = $orderData->getCustomer();
        
        $this->view('order-items/index', [
            'order' => $orderData,
            'customer' => $customer,
            'orderItems' => $orderItems,
            'title' => 'Order Items - Order #' . $orderData->order_id,
            'csrf_token' => $this->csrf()
        ]);
    }
    
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
            $this->redirect('/orders/' . $order_id . '/order-items');
        }
        
        $this->verifyCsrf();
        
        $order = new Order();
        $orderData = $order->find($order_id);
        
        if (!$orderData) {
            $this->redirect('/orders');
        }
        
        $data = $this->sanitize($_POST);
        
        // Validation
        $errors = $this->validate($data, [
            'product_description' => 'required',
            'quantity' => 'required|min:1',
            'unit_price' => 'required'
        ]);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $data;
            $this->redirect('/orders/' . $order_id . '/order-items/create');
        }
        
        // Set required fields
        $data['order_id'] = $order_id;
        $data['user_id'] = $_SESSION['user_id'];
        $data['order_item_status'] = $data['order_item_status'] ?? 'pending';
        
        // Handle optional fields
        $data['product_type'] = $data['product_type'] ?? null;
        $data['product_size'] = $data['product_size'] ?? null;
        $data['custom_method'] = $data['custom_method'] ?? null;
        $data['custom_area'] = $data['custom_area'] ?? null;
        $data['supplier_status'] = $data['supplier_status'] ?? null;
        $data['note_item'] = $data['note_item'] ?? null;
        
        $orderItem = new OrderItem();
        $newItem = $orderItem->create($data);
        
        if ($newItem) {
            // Update order total
            $orderData->calculateTotal();
            
            // Sync order status from items
            $orderData->syncStatusFromItems();
            
            $_SESSION['success'] = 'Order item added successfully!';
            $this->redirect('/orders/' . $order_id . '/order-items');
        } else {
            $_SESSION['error'] = 'Failed to add order item.';
            $this->redirect('/orders/' . $order_id . '/order-items/create');
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
            $this->redirect('/orders');
        }
        
        $this->verifyCsrf();
        
        $orderItem = new OrderItem();
        $itemData = $orderItem->find($id);
        
        if (!$itemData) {
            $this->redirect('/orders');
        }
        
        $data = $this->sanitize($_POST);
        
        // Validation
        $errors = $this->validate($data, [
            'product_description' => 'required',
            'quantity' => 'required|min:1',
            'unit_price' => 'required'
        ]);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $data;
            $this->redirect('/order-items/' . $id . '/edit');
        }
        
        // Handle optional fields
        $data['product_type'] = $data['product_type'] ?? null;
        $data['product_size'] = $data['product_size'] ?? null;
        $data['custom_method'] = $data['custom_method'] ?? null;
        $data['custom_area'] = $data['custom_area'] ?? null;
        $data['supplier_status'] = $data['supplier_status'] ?? null;
        $data['note_item'] = $data['note_item'] ?? null;
        
        $itemData->fill($data);
        
        if ($itemData->update()) {
            // Update order total
            $order = $itemData->getOrder();
            $order->calculateTotal();
            
            // Sync order status from items
            $order->syncStatusFromItems();
            
            $_SESSION['success'] = 'Order item updated successfully!';
            $this->redirect('/orders/' . $itemData->order_id . '/order-items');
        } else {
            $_SESSION['error'] = 'Failed to update order item.';
            $this->redirect('/order-items/' . $id . '/edit');
        }
    }
    
    public function delete($id)
    {
        $this->requireAuth();
        $this->verifyCsrf();
        
        $orderItem = new OrderItem();
        $itemData = $orderItem->find($id);
        
        if (!$itemData) {
            $this->redirect('/orders');
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
        
        $this->redirect('/orders/' . $order_id . '/order-items');
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
        $itemData->attributes[$field] = $value;
        
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
