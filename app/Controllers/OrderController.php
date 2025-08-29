<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Models\User;
use Exception;

class OrderController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        
        $order = new Order();
        
        // Get pagination parameters
        $page = intval($_GET['page'] ?? 1);
        $perPage = 20;
        $search = trim($_GET['search'] ?? '');
        
        // Get paginated results with search
        if (!empty($search)) {
            $result = $order->searchAndPaginate(
                $search,
                ['order_notes'],
                $page,
                $perPage,
                [],
                'date_created DESC'
            );
        } else {
            $result = $order->paginate($page, $perPage, [], 'date_created DESC');
        }
        
        // Load customer data for each order
        foreach ($result['data'] as $ord) {
            $ord->customer = $ord->getCustomer();
        }
        
        $this->view('orders/index', [
            'orders' => $result['data'],
            'pagination' => $result['pagination'],
            'search_term' => $search,
            'pagination_html' => $this->renderPagination($result['pagination'], '/azteamcrm/orders', ['search' => $search]),
            'pagination_info' => $this->renderPaginationInfo($result['pagination']),
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
        
        // Validate and sanitize input
        $data = $this->validateAndSanitize($_POST, [
            'customer_id' => 'required',
            'date_due' => 'required'
        ]);
        
        if (!$data) {
            $this->redirect('/orders/create');
            return;
        }
        
        // New orders always start with these defaults
        $data['order_total'] = 0.00;
        $data['amount_paid'] = 0.00;
        $data['discount_amount'] = 0.00;
        $data['tax_amount'] = 0.00;
        $data['shipping_amount'] = 0.00;
        $data['user_id'] = $_SESSION['user_id'];
        $data['order_status'] = 'pending';
        $data['payment_status'] = 'unpaid';  // Always unpaid for new orders
        $data['date_created'] = date('Y-m-d H:i:s');
        
        // Use error handling wrapper for database operation
        $order = new Order();
        $newOrder = $this->handleDatabaseOperation(
            function() use ($order, $data) {
                return $order->create($data);
            },
            'Order created successfully!',
            'Failed to create order. Please check your information and try again.'
        );
        
        if ($newOrder) {
            $this->redirect('/orders/' . $newOrder->order_id);
        } else {
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
        
        // Validate and sanitize input
        $data = $this->validateAndSanitize($_POST, [
            'customer_id' => 'required',
            'date_due' => 'required'
        ]);
        
        if (!$data) {
            $this->redirect('/orders/' . $id . '/edit');
            return;
        }
        
        // Don't allow manual order_total updates - it's calculated from items
        unset($data['order_total']);
        
        // Don't allow manual order_status updates - it's synced from items
        unset($data['order_status']);
        
        // Keep existing payment status if not changed
        if (!isset($data['payment_status'])) {
            $data['payment_status'] = $orderData->payment_status;
        }
        
        // Handle Connecticut tax checkbox
        $data['apply_ct_tax'] = isset($data['apply_ct_tax']) ? 1 : 0;
        
        // Calculate tax amount based on checkbox
        if ($data['apply_ct_tax']) {
            // Calculate 6.35% of the order total
            $subtotal = floatval($orderData->order_total ?? 0);
            $data['tax_amount'] = round($subtotal * 0.0635, 2);
        } else {
            $data['tax_amount'] = 0.00;
        }
        
        // Use error handling wrapper for database operation
        $result = $this->handleDatabaseOperation(
            function() use ($orderData, $data) {
                $orderData->fill($data);
                return $orderData->update();
            },
            'Order updated successfully!',
            'Failed to update order. Please check your information and try again.'
        );
        
        if ($result) {
            $this->redirect('/orders/' . $id);
        } else {
            $this->redirect('/orders/' . $id . '/edit');
        }
    }
    
    public function delete($id)
    {
        $this->requireAuth();
        $this->requireRole('administrator');
        
        $order = new Order();
        $orderData = $order->find($id);
        
        if (!$orderData) {
            $this->setError('Order not found.');
            $this->redirect('/orders');
            return;
        }
        
        // Security check: Prevent deletion if order has items or payments
        $orderItems = $orderData->getOrderItems();
        $payments = $orderData->getPaymentHistory();
        
        if (!empty($orderItems)) {
            $this->setError('Cannot delete order. Please remove all order items first.');
            $this->redirect('/orders/' . $id);
            return;
        }
        
        if (!empty($payments)) {
            $this->setError('Cannot delete order. This order has payment history and cannot be deleted.');
            $this->redirect('/orders/' . $id);
            return;
        }
        
        // Use error handling wrapper for database operation
        $this->handleDatabaseOperation(
            function() use ($orderData) {
                return $orderData->delete();
            },
            'Order deleted successfully!',
            'Failed to delete order. It may be referenced by other records.'
        );
        
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
            $this->setError('Order not found.');
            $this->redirect('/orders');
            return;
        }
        
        $status = $this->sanitize($_POST['payment_status'] ?? '');
        $paidAmount = floatval($_POST['paid_amount'] ?? 0);
        
        // Use error handling wrapper for database operation
        $this->handleDatabaseOperation(
            function() use ($orderData, $status) {
                return $orderData->updatePaymentStatus($status);
            },
            'Payment status updated successfully!',
            'Failed to update payment status. Please try again.'
        );
        
        $this->redirect('/orders/' . $id);
    }
    
    public function updateShipping($id)
    {
        $this->requireAuth();
        $this->verifyCsrf();
        
        if (!$this->isPost()) {
            $this->redirect('/orders/' . $id);
            return;
        }
        
        $order = new Order();
        $orderData = $order->find($id);
        
        if (!$orderData) {
            $_SESSION['error'] = 'Order not found.';
            $this->redirect('/orders');
            return;
        }
        
        $shippingAmount = floatval($_POST['shipping_amount'] ?? 0);
        
        // Ensure shipping amount is not negative
        $shippingAmount = max(0, $shippingAmount);
        
        // Update shipping amount
        $orderData->attributes['shipping_amount'] = $shippingAmount;
        $orderData->shipping_amount = $shippingAmount;
        
        if ($orderData->update()) {
            $_SESSION['success'] = 'Shipping cost updated successfully.';
        } else {
            $_SESSION['error'] = 'Failed to update shipping cost.';
        }
        
        $this->redirect('/orders/' . $id);
    }
    
    public function updateDiscount($id)
    {
        $this->requireAuth();
        $this->verifyCsrf();
        
        if (!$this->isPost()) {
            $this->redirect('/orders/' . $id);
            return;
        }
        
        $order = new Order();
        $orderData = $order->find($id);
        
        if (!$orderData) {
            $_SESSION['error'] = 'Order not found.';
            $this->redirect('/orders');
            return;
        }
        
        $discountAmount = floatval($_POST['discount_amount'] ?? 0);
        
        // Ensure discount is not negative and not more than order total
        $discountAmount = max(0, min($discountAmount, $orderData->order_total));
        
        // Update discount amount
        $orderData->attributes['discount_amount'] = $discountAmount;
        $orderData->discount_amount = $discountAmount;
        
        if ($orderData->update()) {
            $_SESSION['success'] = 'Discount applied successfully.';
        } else {
            $_SESSION['error'] = 'Failed to apply discount.';
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
    
    public function processPayment($id)
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
        
        $paymentAmount = floatval($_POST['payment_amount'] ?? 0);
        $paymentMethod = $this->sanitize($_POST['payment_method'] ?? '');
        $paymentNotes = $this->sanitize($_POST['payment_notes'] ?? '');
        
        if ($paymentAmount <= 0) {
            $_SESSION['error'] = 'Invalid payment amount.';
            $this->redirect('/orders/' . $id);
        }
        
        // Add payment to history and update order
        if ($orderData->addPayment($paymentAmount, $paymentMethod, $paymentNotes)) {
            $_SESSION['success'] = 'Payment of $' . number_format($paymentAmount, 2) . ' recorded successfully.';
        } else {
            $_SESSION['error'] = 'Failed to process payment.';
        }
        
        $this->redirect('/orders/' . $id);
    }
    
    public function toggleTax($id)
    {
        // Ensure this is always treated as an AJAX request
        header('Content-Type: application/json');
        
        try {
            $this->requireAuth();
            $this->verifyCsrf();
            
            // Only accept POST requests
            if (!$this->isPost()) {
                $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
                return;
            }
            
            // Find the order
            $order = new Order();
            $orderData = $order->find($id);
            
            if (!$orderData) {
                $this->json(['success' => false, 'message' => 'Order not found'], 404);
                return;
            }
            
            // Get the new tax status from POST data
            $applyTax = isset($_POST['apply_tax']) && $_POST['apply_tax'] === '1';
            
            // Debug logging
            error_log("Tax toggle request - Order ID: $id, Apply Tax: " . ($applyTax ? 'true' : 'false'));
            error_log("Current order total: " . $orderData->order_total);
            
            // Initialize tax fields if they don't exist (for existing orders created before migration)
            if (!isset($orderData->apply_ct_tax)) {
                $orderData->apply_ct_tax = 0;
            }
            if (!isset($orderData->tax_amount)) {
                $orderData->tax_amount = 0.00;
            }
            
            error_log("Tax fields initialized - apply_ct_tax: " . $orderData->apply_ct_tax . ", tax_amount: " . $orderData->tax_amount);
            
            // Update tax settings using the fill method (safer than direct attributes manipulation)
            $updateData = [
                'apply_ct_tax' => $applyTax ? 1 : 0
            ];
            
            // Recalculate tax amount based on current order total
            if ($applyTax) {
                // Calculate 6.35% Connecticut tax on order subtotal
                $subtotal = floatval($orderData->order_total ?? 0);
                $taxAmount = round($subtotal * 0.0635, 2);
                error_log("Tax calculation: $subtotal * 0.0635 = $taxAmount");
            } else {
                $taxAmount = 0.00;
                error_log("Tax disabled, amount set to 0.00");
            }
            
            $updateData['tax_amount'] = $taxAmount;
            
            // Use the fill method to properly update the model
            $orderData->fill($updateData);
            
            // Save the changes
            if ($orderData->update()) {
                $message = $applyTax 
                    ? 'Connecticut tax (6.35%) applied successfully. Tax amount: $' . number_format($taxAmount, 2)
                    : 'Tax removed successfully';
                    
                error_log("Tax toggle successful - $message");
                    
                $this->json([
                    'success' => true, 
                    'message' => $message,
                    'apply_tax' => $applyTax,
                    'tax_amount' => $taxAmount,
                    'new_total' => $orderData->getTotalAmount()
                ]);
            } else {
                error_log("Database update failed for tax toggle");
                $this->json(['success' => false, 'message' => 'Failed to update tax settings - database operation failed'], 500);
            }
            
        } catch (Exception $e) {
            $errorMsg = "Tax toggle error - Order ID: $id, Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
            error_log($errorMsg);
            
            // Provide more specific error messages based on the exception
            $userMessage = 'An error occurred while updating tax settings';
            if (strpos($e->getMessage(), 'Unknown column') !== false) {
                $userMessage = 'Database schema error: Missing tax-related fields. Please run the database migration script.';
            } elseif (strpos($e->getMessage(), 'CSRF token') !== false) {
                $userMessage = 'Security token expired. Please refresh the page and try again.';
            }
            
            $this->json(['success' => false, 'message' => $userMessage], 500);
        }
    }
}
