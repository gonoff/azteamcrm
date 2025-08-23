<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Customer;
use App\Models\Order;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }
    
    public function index()
    {
        $customer = new Customer();
        $customers = $customer->findAll([], 'full_name ASC');
        
        $this->view('customers/index', [
            'customers' => $customers,
            'title' => 'Customer Management',
            'csrf_token' => $this->csrf()
        ]);
    }
    
    public function create()
    {
        // Check if there's a return URL passed
        $returnUrl = $_GET['return_url'] ?? null;
        
        // Store current order form data if coming from order creation
        if ($returnUrl && strpos($returnUrl, '/orders/create') !== false) {
            // Preserve any order form data in session
            if ($this->isPost()) {
                $_SESSION['order_form_data'] = $_POST;
            }
        }
        
        $this->view('customers/form', [
            'title' => 'Add New Customer',
            'customer' => null,
            'action' => '/azteamcrm/customers/store',
            'return_url' => $returnUrl
        ]);
    }
    
    public function store()
    {
        $this->verifyCsrf();
        
        $data = [
            'customer_status' => $this->sanitize($_POST['customer_status'] ?? 'active'),
            'full_name' => $this->sanitize($_POST['full_name']),
            'company_name' => $this->sanitize($_POST['company_name'] ?? ''),
            'address_line_1' => $this->sanitize($_POST['address_line_1']),
            'address_line_2' => $this->sanitize($_POST['address_line_2'] ?? ''),
            'city' => $this->sanitize($_POST['city']),
            'state' => $this->sanitize($_POST['state']),
            'zip_code' => $this->sanitize($_POST['zip_code']),
            'phone_number' => $this->sanitize($_POST['phone_number'] ?? '')
        ];
        
        // Validation
        $errors = $this->validate($data, [
            'full_name' => 'required|min:2',
            'address_line_1' => 'required',
            'city' => 'required',
            'state' => 'required|max:2',
            'zip_code' => 'required'
        ]);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $data;
            
            // Preserve return URL on validation error
            $returnUrl = $_POST['return_url'] ?? null;
            $redirectUrl = '/customers/create';
            if ($returnUrl) {
                $redirectUrl .= '?return_url=' . urlencode($returnUrl);
            }
            $this->redirect($redirectUrl);
            return;
        }
        
        // Format phone number
        if (!empty($data['phone_number'])) {
            $data['phone_number'] = preg_replace('/[^0-9]/', '', $data['phone_number']);
        }
        
        $customer = new Customer();
        $newCustomer = $customer->create($data);
        if ($newCustomer) {
            $_SESSION['success'] = 'Customer created successfully.';
            
            // Check if there's a return URL
            $returnUrl = $_POST['return_url'] ?? null;
            if ($returnUrl) {
                // If returning to order creation, store the new customer ID
                if (strpos($returnUrl, '/orders/create') !== false) {
                    $_SESSION['new_customer_id'] = $newCustomer->customer_id;
                }
                $this->redirect($returnUrl);
            } else {
                $this->redirect('/customers');
            }
        } else {
            $_SESSION['error'] = 'Failed to create customer.';
            $_SESSION['old'] = $data;
            
            // Preserve return URL on error
            $returnUrl = $_POST['return_url'] ?? null;
            $redirectUrl = '/customers/create';
            if ($returnUrl) {
                $redirectUrl .= '?return_url=' . urlencode($returnUrl);
            }
            $this->redirect($redirectUrl);
        }
    }
    
    public function show($id)
    {
        $customer = new Customer();
        $customer = $customer->find($id);
        
        if (!$customer) {
            $_SESSION['error'] = 'Customer not found.';
            $this->redirect('/customers');
            return;
        }
        
        $orders = $customer->getOrders();
        
        $this->view('customers/show', [
            'customer' => $customer,
            'orders' => $orders,
            'title' => 'Customer Details',
            'csrf_token' => $this->csrf()
        ]);
    }
    
    public function edit($id)
    {
        $customer = new Customer();
        $customer = $customer->find($id);
        
        if (!$customer) {
            $_SESSION['error'] = 'Customer not found.';
            $this->redirect('/customers');
            return;
        }
        
        $this->view('customers/form', [
            'title' => 'Edit Customer',
            'customer' => $customer,
            'action' => '/azteamcrm/customers/' . $id . '/update'
        ]);
    }
    
    public function update($id)
    {
        $this->verifyCsrf();
        
        $customer = new Customer();
        $customer = $customer->find($id);
        
        if (!$customer) {
            $_SESSION['error'] = 'Customer not found.';
            $this->redirect('/customers');
            return;
        }
        
        $data = [
            'customer_status' => $this->sanitize($_POST['customer_status'] ?? 'active'),
            'full_name' => $this->sanitize($_POST['full_name']),
            'company_name' => $this->sanitize($_POST['company_name'] ?? ''),
            'address_line_1' => $this->sanitize($_POST['address_line_1']),
            'address_line_2' => $this->sanitize($_POST['address_line_2'] ?? ''),
            'city' => $this->sanitize($_POST['city']),
            'state' => $this->sanitize($_POST['state']),
            'zip_code' => $this->sanitize($_POST['zip_code']),
            'phone_number' => $this->sanitize($_POST['phone_number'] ?? '')
        ];
        
        // Validation
        $errors = $this->validate($data, [
            'full_name' => 'required|min:2',
            'address_line_1' => 'required',
            'city' => 'required',
            'state' => 'required|max:2',
            'zip_code' => 'required'
        ]);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $data;
            $this->redirect('/customers/' . $id . '/edit');
            return;
        }
        
        // Format phone number
        if (!empty($data['phone_number'])) {
            $data['phone_number'] = preg_replace('/[^0-9]/', '', $data['phone_number']);
        }
        
        $customer->fill($data);
        if ($customer->update()) {
            $_SESSION['success'] = 'Customer updated successfully.';
            $this->redirect('/customers/' . $id);
        } else {
            $_SESSION['error'] = 'Failed to update customer.';
            $_SESSION['old'] = $data;
            $this->redirect('/customers/' . $id . '/edit');
        }
    }
    
    public function delete($id)
    {
        $this->requireRole('administrator');
        $this->verifyCsrf();
        
        $customer = new Customer();
        $customer = $customer->find($id);
        
        if (!$customer) {
            $_SESSION['error'] = 'Customer not found.';
            $this->redirect('/customers');
            return;
        }
        
        // Check if customer has orders
        if ($customer->getTotalOrders() > 0) {
            $_SESSION['error'] = 'Cannot delete customer with existing orders.';
            $this->redirect('/customers');
            return;
        }
        
        if ($customer->delete()) {
            $_SESSION['success'] = 'Customer deleted successfully.';
        } else {
            $_SESSION['error'] = 'Failed to delete customer.';
        }
        
        $this->redirect('/customers');
    }
    
    public function toggleStatus($id)
    {
        $this->verifyCsrf();
        
        $customer = new Customer();
        $customer = $customer->find($id);
        
        if (!$customer) {
            $this->json(['success' => false, 'message' => 'Customer not found']);
            return;
        }
        
        $newStatus = $customer->customer_status === 'active' ? 'inactive' : 'active';
        $customer->customer_status = $newStatus;
        
        if ($customer->update()) {
            $this->json([
                'success' => true,
                'status' => $newStatus,
                'message' => 'Customer status updated successfully'
            ]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to update status']);
        }
    }
    
    public function search()
    {
        // Verify CSRF token for security
        $this->verifyCsrf();
        
        // Get search query from request
        $query = $this->sanitize($_POST['query'] ?? '');
        
        // Validate query (minimum 2 characters)
        if (strlen($query) < 2) {
            $this->json([
                'success' => false,
                'message' => 'Please enter at least 2 characters to search',
                'results' => []
            ]);
            return;
        }
        
        // Search for customers
        $customer = new Customer();
        $results = $customer->searchCustomers($query);
        
        // Format results for response
        $formattedResults = [];
        foreach ($results as $result) {
            $displayName = $result->full_name;
            if ($result->company_name) {
                $displayName .= ' (' . $result->company_name . ')';
            }
            
            $formattedResults[] = [
                'customer_id' => $result->customer_id,
                'full_name' => $result->full_name,
                'company_name' => $result->company_name,
                'phone_number' => $result->formatPhoneNumber(),
                'display_name' => $displayName
            ];
        }
        
        // Return JSON response
        $this->json([
            'success' => true,
            'results' => $formattedResults,
            'count' => count($formattedResults)
        ]);
    }
}