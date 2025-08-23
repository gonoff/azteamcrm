<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Models\User;

class OrderController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        
        $order = new Order();
        $orders = $order->findAll([], 'date_created DESC');
        
        // Load customer data for each order
        foreach ($orders as $ord) {
            $ord->customer = $ord->getCustomer();
        }
        
        $this->view('orders/index', [
            'orders' => $orders,
            'title' => 'Orders',
            'csrf_token' => $this->csrf()
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
        
        $orderItems = $orderData->getOrderItems();
        $customer = $orderData->getCustomer();
        $user = $orderData->getUser();
        
        $this->view('orders/show', [
            'order' => $orderData,
            'orderItems' => $orderItems,
            'customer' => $customer,
            'user' => $user,
            'title' => 'Order #' . $orderData->order_id,
            'csrf_token' => $this->csrf()
        ]);
    }
    
    public function create()
    {
        $this->requireAuth();
        
        $customer = new Customer();
        $customers = $customer->findAll(['customer_status' => 'active'], 'full_name ASC');
        
        // Check if returning from customer creation with a new customer ID
        $selectedCustomer = null;
        
        // First priority: Check if customer ID passed in URL (from customer creation)
        if (isset($_GET['customer_id']) && $_GET['customer_id']) {
            $selectedCustomer = $customer->find($_GET['customer_id']);
            if ($selectedCustomer) {
                // Add to customers array if not already present
                $customerExists = false;
                foreach ($customers as $existingCustomer) {
                    if ($existingCustomer->customer_id == $selectedCustomer->customer_id) {
                        $customerExists = true;
                        break;
                    }
                }
                if (!$customerExists) {
                    array_unshift($customers, $selectedCustomer);
                }
            }
        } elseif (isset($_SESSION['old_input']['customer_id']) && $_SESSION['old_input']['customer_id']) {
            // If coming from failed validation, ensure the customer is selected
            $customerId = $_SESSION['old_input']['customer_id'];
            $customerExists = false;
            
            // Check if customer is already in the list and select it
            foreach ($customers as $existingCustomer) {
                if ($existingCustomer->customer_id == $customerId) {
                    $selectedCustomer = $existingCustomer;
                    $customerExists = true;
                    break;
                }
            }
            
            // If not in list, fetch and add it
            if (!$customerExists) {
                $selectedCustomer = $customer->find($customerId);
                if ($selectedCustomer) {
                    array_unshift($customers, $selectedCustomer);
                }
            }
        }
        
        // Restore form data if returning from customer creation
        if (isset($_SESSION['order_form_data'])) {
            $_SESSION['old_input'] = $_SESSION['order_form_data'];
            unset($_SESSION['order_form_data']);
        }
        
        $this->view('orders/form', [
            'title' => 'Create Order',
            'csrf_token' => $this->csrf(),
            'order' => null,
            'customers' => $customers,
            'selected_customer' => $selectedCustomer
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
            'customer_id' => 'required',
            'date_due' => 'required'
        ]);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $data;
            $this->redirect('/orders/create');
        }
        
        // Set order_total to 0.00 for new orders (will be calculated from items)
        $data['order_total'] = 0.00;
        $data['user_id'] = $_SESSION['user_id'];
        // Order status is always pending for new orders (will sync from items)
        $data['order_status'] = 'pending';
        $data['payment_status'] = $data['payment_status'] ?? 'unpaid';
        $data['date_created'] = date('Y-m-d H:i:s');
        
        $order = new Order();
        $newOrder = $order->create($data);
        
        if ($newOrder) {
            $_SESSION['success'] = 'Order created successfully!';
            $this->redirect('/orders/' . $newOrder->order_id);
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
        
        $customer = new Customer();
        $customers = $customer->findAll(['customer_status' => 'active'], 'full_name ASC');
        
        // Check if returning from customer creation with a new customer ID
        $selectedCustomer = null;
        if (isset($_SESSION['new_customer_id'])) {
            // Fetch the newly created customer directly
            $selectedCustomer = $customer->find($_SESSION['new_customer_id']);
            unset($_SESSION['new_customer_id']);
            
            // Add the new customer to the customers array if not already present
            if ($selectedCustomer) {
                $customerExists = false;
                foreach ($customers as $existingCustomer) {
                    if ($existingCustomer->customer_id == $selectedCustomer->customer_id) {
                        $customerExists = true;
                        break;
                    }
                }
                if (!$customerExists) {
                    // Add at the beginning so it's easy to find
                    array_unshift($customers, $selectedCustomer);
                }
            }
        }
        
        $this->view('orders/form', [
            'title' => 'Edit Order #' . $orderData->order_id,
            'csrf_token' => $this->csrf(),
            'order' => $orderData,
            'customers' => $customers,
            'selected_customer' => $selectedCustomer
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
            'customer_id' => 'required',
            'date_due' => 'required'
        ]);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $data;
            $this->redirect('/orders/' . $id . '/edit');
        }
        
        // Don't allow manual order_total updates - it's calculated from items
        unset($data['order_total']);
        
        // Don't allow manual order_status updates - it's synced from items
        unset($data['order_status']);
        
        // Keep existing payment status if not changed
        if (!isset($data['payment_status'])) {
            $data['payment_status'] = $orderData->payment_status;
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
        
        if ($orderData->updatePaymentStatus($status)) {
            $_SESSION['success'] = 'Payment status updated successfully!';
        } else {
            $_SESSION['error'] = 'Failed to update payment status.';
        }
        
        $this->redirect('/orders/' . $id);
    }
    
    public function cancelOrder($id)
    {
        $this->requireAuth();
        $this->verifyCsrf();
        
        if (!$this->isPost()) {
            $this->redirect('/orders/' . $id);
        }
        
        $order = new Order();
        $orderData = $order->find($id);
        
        if (!$orderData) {
            $this->redirect('/orders');
        }
        
        // Cancel the order (manual override)
        if ($orderData->updateOrderStatus('cancelled')) {
            $_SESSION['success'] = 'Order has been cancelled.';
        } else {
            $_SESSION['error'] = 'Failed to cancel order.';
        }
        
        $this->redirect('/orders/' . $id);
    }
}