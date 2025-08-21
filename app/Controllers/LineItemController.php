<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\LineItem;
use App\Models\Order;

class LineItemController extends Controller
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
        
        $lineItems = $orderData->getLineItems();
        
        $this->view('line-items/index', [
            'order' => $orderData,
            'lineItems' => $lineItems,
            'title' => 'Line Items - Order #' . $order_id,
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
        
        $this->view('line-items/form', [
            'order' => $orderData,
            'lineItem' => null,
            'title' => 'Add Line Item - Order #' . $order_id,
            'csrf_token' => $this->csrf()
        ]);
    }
    
    public function store($order_id)
    {
        $this->requireAuth();
        
        if (!$this->isPost()) {
            $this->redirect('/orders/' . $order_id);
        }
        
        $this->verifyCsrf();
        
        $order = new Order();
        $orderData = $order->find($order_id);
        
        if (!$orderData) {
            $this->redirect('/orders');
        }
        
        $data = $this->sanitize($_POST);
        
        $errors = $this->validate($data, [
            'product_description' => 'required|min:3',
            'product_type' => 'required',
            'size' => 'required',
            'quantity' => 'required',
            'customization_method' => 'required'
        ]);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $data;
            $this->redirect('/orders/' . $order_id . '/line-items/create');
        }
        
        // Handle customization areas (checkboxes to SET field)
        $customizationAreas = [];
        if (isset($data['customization_area_front'])) {
            $customizationAreas[] = 'front';
        }
        if (isset($data['customization_area_back'])) {
            $customizationAreas[] = 'back';
        }
        if (isset($data['customization_area_sleeve'])) {
            $customizationAreas[] = 'sleeve';
        }
        
        $lineItemData = [
            'order_id' => $order_id,
            'product_description' => $data['product_description'],
            'product_type' => $data['product_type'],
            'size' => $data['size'],
            'quantity' => intval($data['quantity']),
            'customization_method' => $data['customization_method'],
            'customization_areas' => implode(',', $customizationAreas),
            'color_specification' => $data['color_specification'] ?? '',
            'line_item_notes' => $data['line_item_notes'] ?? '',
            'supplier_status' => 'awaiting_to_order',
            'completion_status' => 'waiting_approval'
        ];
        
        $lineItem = new LineItem();
        $newLineItem = $lineItem->create($lineItemData);
        
        if ($newLineItem) {
            $_SESSION['success'] = 'Line item added successfully!';
            $this->redirect('/orders/' . $order_id);
        } else {
            $_SESSION['error'] = 'Failed to add line item.';
            $this->redirect('/orders/' . $order_id . '/line-items/create');
        }
    }
    
    public function edit($id)
    {
        $this->requireAuth();
        
        $lineItem = new LineItem();
        $lineItemData = $lineItem->find($id);
        
        if (!$lineItemData) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }
        
        $order = $lineItemData->getOrder();
        
        $this->view('line-items/form', [
            'order' => $order,
            'lineItem' => $lineItemData,
            'title' => 'Edit Line Item - Order #' . $order->id,
            'csrf_token' => $this->csrf()
        ]);
    }
    
    public function update($id)
    {
        $this->requireAuth();
        
        if (!$this->isPost()) {
            $this->redirect('/orders');
        }
        
        $this->verifyCsrf();
        
        $lineItem = new LineItem();
        $lineItemData = $lineItem->find($id);
        
        if (!$lineItemData) {
            $this->redirect('/orders');
        }
        
        $order = $lineItemData->getOrder();
        $data = $this->sanitize($_POST);
        
        $errors = $this->validate($data, [
            'product_description' => 'required|min:3',
            'product_type' => 'required',
            'size' => 'required',
            'quantity' => 'required',
            'customization_method' => 'required'
        ]);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $data;
            $this->redirect('/line-items/' . $id . '/edit');
        }
        
        // Handle customization areas
        $customizationAreas = [];
        if (isset($data['customization_area_front'])) {
            $customizationAreas[] = 'front';
        }
        if (isset($data['customization_area_back'])) {
            $customizationAreas[] = 'back';
        }
        if (isset($data['customization_area_sleeve'])) {
            $customizationAreas[] = 'sleeve';
        }
        
        $updateData = [
            'product_description' => $data['product_description'],
            'product_type' => $data['product_type'],
            'size' => $data['size'],
            'quantity' => intval($data['quantity']),
            'customization_method' => $data['customization_method'],
            'customization_areas' => implode(',', $customizationAreas),
            'color_specification' => $data['color_specification'] ?? '',
            'line_item_notes' => $data['line_item_notes'] ?? '',
            'supplier_status' => $data['supplier_status'] ?? $lineItemData->supplier_status,
            'completion_status' => $data['completion_status'] ?? $lineItemData->completion_status
        ];
        
        $lineItemData->fill($updateData);
        
        if ($lineItemData->update()) {
            $_SESSION['success'] = 'Line item updated successfully!';
            $this->redirect('/orders/' . $order->id);
        } else {
            $_SESSION['error'] = 'Failed to update line item.';
            $this->redirect('/line-items/' . $id . '/edit');
        }
    }
    
    public function delete($id)
    {
        $this->requireAuth();
        
        $lineItem = new LineItem();
        $lineItemData = $lineItem->find($id);
        
        if (!$lineItemData) {
            $this->redirect('/orders');
        }
        
        $order = $lineItemData->getOrder();
        
        if ($lineItemData->delete()) {
            $_SESSION['success'] = 'Line item deleted successfully!';
        } else {
            $_SESSION['error'] = 'Failed to delete line item.';
        }
        
        $this->redirect('/orders/' . $order->id);
    }
    
    public function updateStatus($id)
    {
        $this->requireAuth();
        
        if (!$this->isPost()) {
            $this->redirect('/orders');
        }
        
        $this->verifyCsrf();
        
        $lineItem = new LineItem();
        $lineItemData = $lineItem->find($id);
        
        if (!$lineItemData) {
            $this->redirect('/orders');
        }
        
        $order = $lineItemData->getOrder();
        $data = $this->sanitize($_POST);
        
        $success = false;
        $message = '';
        
        if (isset($data['supplier_status'])) {
            $success = $lineItemData->updateSupplierStatus($data['supplier_status']);
            $message = $success ? 'Supplier status updated!' : 'Failed to update supplier status.';
        } elseif (isset($data['completion_status'])) {
            $success = $lineItemData->updateCompletionStatus($data['completion_status']);
            $message = $success ? 'Completion status updated!' : 'Failed to update completion status.';
        }
        
        if ($this->isAjax()) {
            $this->json([
                'success' => $success,
                'message' => $message
            ]);
        } else {
            if ($success) {
                $_SESSION['success'] = $message;
            } else {
                $_SESSION['error'] = $message;
            }
            $this->redirect('/orders/' . $order->id);
        }
    }
    
    private function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}