<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\User;

class OrderController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        
        $order = new Order();
        $orders = $order->findAll([], 'created_at DESC');
        
        $this->view('orders/index', [
            'orders' => $orders,
            'title' => 'Orders'
        ]);
    }
    
    public function show($id)
    {
        $this->requireAuth();
        
        $order = new Order();
        $orderData = $order->find($id);
        
        if (!$orderData) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }
        
        $lineItems = $orderData->getLineItems();
        $capturedBy = $orderData->getCapturedByUser();
        
        $this->view('orders/show', [
            'order' => $orderData,
            'lineItems' => $lineItems,
            'capturedBy' => $capturedBy,
            'title' => 'Order #' . $id
        ]);
    }
    
    public function create()
    {
        $this->requireAuth();
        
        $this->view('orders/form', [
            'title' => 'Create Order',
            'csrf_token' => $this->csrf(),
            'order' => null
        ]);
    }
    
    public function store()
    {
        $this->requireAuth();
        
        if (!$this->isPost()) {
            $this->redirect('/orders');
        }
        
        $this->verifyCsrf();
        
        $data = $this->sanitize($_POST);
        
        $errors = $this->validate($data, [
            'client_name' => 'required|min:3',
            'client_phone' => 'required',
            'date_received' => 'required',
            'due_date' => 'required',
            'total_value' => 'required'
        ]);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $data;
            $this->redirect('/orders/create');
        }
        
        $data['captured_by_user_id'] = $_SESSION['user_id'];
        $data['is_rush_order'] = isset($data['is_rush_order']) ? 1 : 0;
        $data['outstanding_balance'] = $data['total_value'];
        $data['payment_status'] = 'unpaid';
        
        $order = new Order();
        $newOrder = $order->create($data);
        
        if ($newOrder) {
            $_SESSION['success'] = 'Order created successfully!';
            $this->redirect('/orders/' . $newOrder->id);
        } else {
            $_SESSION['error'] = 'Failed to create order.';
            $this->redirect('/orders/create');
        }
    }
    
    public function edit($id)
    {
        $this->requireAuth();
        
        $order = new Order();
        $orderData = $order->find($id);
        
        if (!$orderData) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }
        
        $this->view('orders/form', [
            'title' => 'Edit Order #' . $id,
            'csrf_token' => $this->csrf(),
            'order' => $orderData
        ]);
    }
    
    public function update($id)
    {
        $this->requireAuth();
        
        if (!$this->isPost()) {
            $this->redirect('/orders');
        }
        
        $this->verifyCsrf();
        
        $order = new Order();
        $orderData = $order->find($id);
        
        if (!$orderData) {
            $this->redirect('/orders');
        }
        
        $data = $this->sanitize($_POST);
        
        $errors = $this->validate($data, [
            'client_name' => 'required|min:3',
            'client_phone' => 'required',
            'date_received' => 'required',
            'due_date' => 'required',
            'total_value' => 'required'
        ]);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $data;
            $this->redirect('/orders/' . $id . '/edit');
        }
        
        $data['is_rush_order'] = isset($data['is_rush_order']) ? 1 : 0;
        
        // Update outstanding balance if total value changed
        if ($data['total_value'] != $orderData->total_value) {
            $paidAmount = $orderData->total_value - $orderData->outstanding_balance;
            $data['outstanding_balance'] = $data['total_value'] - $paidAmount;
            
            if ($data['outstanding_balance'] <= 0) {
                $data['payment_status'] = 'paid';
                $data['outstanding_balance'] = 0;
            } elseif ($data['outstanding_balance'] < $data['total_value']) {
                $data['payment_status'] = 'partial';
            } else {
                $data['payment_status'] = 'unpaid';
            }
        }
        
        $orderData->fill($data);
        
        if ($orderData->update()) {
            $_SESSION['success'] = 'Order updated successfully!';
            $this->redirect('/orders/' . $id);
        } else {
            $_SESSION['error'] = 'Failed to update order.';
            $this->redirect('/orders/' . $id . '/edit');
        }
    }
    
    public function delete($id)
    {
        $this->requireAuth();
        $this->requireRole('administrator');
        
        $order = new Order();
        $orderData = $order->find($id);
        
        if ($orderData && $orderData->delete()) {
            $_SESSION['success'] = 'Order deleted successfully!';
        } else {
            $_SESSION['error'] = 'Failed to delete order.';
        }
        
        $this->redirect('/orders');
    }
    
    public function updateStatus($id)
    {
        $this->requireAuth();
        
        if (!$this->isPost()) {
            $this->redirect('/orders/' . $id);
        }
        
        $this->verifyCsrf();
        
        $order = new Order();
        $orderData = $order->find($id);
        
        if (!$orderData) {
            $this->redirect('/orders');
        }
        
        $status = $this->sanitize($_POST['payment_status'] ?? '');
        $paidAmount = floatval($_POST['paid_amount'] ?? 0);
        
        if ($orderData->updatePaymentStatus($status, $paidAmount)) {
            $_SESSION['success'] = 'Payment status updated successfully!';
        } else {
            $_SESSION['error'] = 'Failed to update payment status.';
        }
        
        $this->redirect('/orders/' . $id);
    }
}