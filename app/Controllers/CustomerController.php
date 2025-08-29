<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Services\SettingsService;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }
    
    public function index()
    {
        $customer = new Customer();
        
        // Get pagination parameters
        $page = intval($_GET['page'] ?? 1);
        $perPage = SettingsService::getDefaultPageSize();
        $search = trim($_GET['search'] ?? '');
        $allowedSortColumns = ['customer_id', 'full_name', 'company_name', 'phone_number', 'email', 'date_created'];

        // Get paginated results with search
        if (!empty($search)) {
            $result = $customer->searchAndPaginate(
                $search,
                ['full_name', 'company_name', 'phone_number', 'email'],
                $page,
                $perPage,
                [],
                'full_name ASC',
                $allowedSortColumns
            );
        } else {
            $result = $customer->paginate($page, $perPage, [], 'full_name ASC', $allowedSortColumns);
        }
        
        $this->view('customers/index', [
            'customers' => $result['data'],
            'pagination' => $result['pagination'],
            'search_term' => $search,
            'pagination_html' => $this->renderPagination($result['pagination'], '/azteamcrm/customers', ['search' => $search]),
            'pagination_info' => $this->renderPaginationInfo($result['pagination']),
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
            'phone_number' => $this->sanitize($_POST['phone_number'] ?? ''),
            'email' => $this->sanitize($_POST['email'] ?? '')
        ];
        
        // Apply title case to names and city
        $data['full_name'] = $this->toTitleCase($data['full_name']);
        if (!empty($data['company_name'])) {
            $data['company_name'] = $this->toTitleCase($data['company_name']);
        }
        $data['city'] = $this->toTitleCase($data['city']);
        
        // Keep state uppercase (standard for US state codes) or set to NULL if empty
        if (!empty($data['state'])) {
            $data['state'] = strtoupper($data['state']);
        } else {
            $data['state'] = null; // Database requires exactly 2 chars or NULL
        }
        
        // Format phone number BEFORE validation
        if (!empty($data['phone_number'])) {
            $data['phone_number'] = preg_replace('/[^0-9]/', '', $data['phone_number']);
        }
        
        // Validation
        $phoneMinLength = SettingsService::getPhoneNumberLength();
        $validationRules = [
            'full_name' => 'required|min:2',
            'company_name' => 'required|min:2',
            'phone_number' => 'required|min:' . $phoneMinLength,
            'state' => 'max:2'  // Only validate max length if state is provided
        ];
        
        if (!empty($data['email'])) {
            $validationRules['email'] = 'email';
        }
        
        $errors = $this->validate($data, $validationRules);
        
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
        
        // Check for duplicate customer
        $customer = new Customer();
        if ($customer->isDuplicate($data['full_name'], $data['phone_number'])) {
            $duplicate = $customer->findDuplicate($data['full_name'], $data['phone_number']);
            
            $errorMsg = "A customer with this name already exists.";
            if ($duplicate) {
                $errorMsg .= " (Customer #" . $duplicate->customer_id . ": " . 
                             $duplicate->full_name;
                if ($duplicate->company_name) {
                    $errorMsg .= " - " . $duplicate->company_name;
                }
                $errorMsg .= ")";
            }
            
            $_SESSION['errors'] = ['duplicate' => $errorMsg];
            $_SESSION['old'] = $data;
            
            // Preserve return URL on duplicate error
            $returnUrl = $_POST['return_url'] ?? null;
            $redirectUrl = '/customers/create';
            if ($returnUrl) {
                $redirectUrl .= '?return_url=' . urlencode($returnUrl);
            }
            $this->redirect($redirectUrl);
            return;
        }
        
        // Create customer with detailed error handling (temporary for debugging)
        $customer = new Customer();
        try {
            $newCustomer = $customer->create($data);
            
            if ($newCustomer) {
                $_SESSION['success'] = 'Customer created successfully.';
            } else {
                // Show more details about why create() returned false
                $_SESSION['error'] = 'Database operation failed. The create method returned false. Check data format and database constraints.';
                error_log("Customer creation failed - create() returned false. Data: " . print_r($data, true));
                $newCustomer = false;
            }
        } catch (\Exception $e) {
            // Show actual database error (temporary for debugging)
            $_SESSION['error'] = 'Database Error: ' . $e->getMessage() . ' (File: ' . basename($e->getFile()) . ':' . $e->getLine() . ')';
            error_log("Customer creation exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
            error_log("Data that caused error: " . print_r($data, true));
            $newCustomer = false;
        }
        
        if ($newCustomer) {
            // Check if there's a return URL
            $returnUrl = $_POST['return_url'] ?? null;
            if ($returnUrl) {
                // If returning to order creation, add customer ID to URL
                if (strpos($returnUrl, '/orders/create') !== false) {
                    // Add customer ID as URL parameter
                    $separator = strpos($returnUrl, '?') !== false ? '&' : '?';
                    $returnUrl .= $separator . 'customer_id=' . $newCustomer->customer_id;
                }
                $this->redirect($returnUrl);
            } else {
                $this->redirect('/customers');
            }
        } else {
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
            'phone_number' => $this->sanitize($_POST['phone_number'] ?? ''),
            'email' => $this->sanitize($_POST['email'] ?? '')
        ];
        
        // Apply title case to names and city
        $data['full_name'] = $this->toTitleCase($data['full_name']);
        if (!empty($data['company_name'])) {
            $data['company_name'] = $this->toTitleCase($data['company_name']);
        }
        $data['city'] = $this->toTitleCase($data['city']);
        
        // Keep state uppercase (standard for US state codes) or set to NULL if empty
        if (!empty($data['state'])) {
            $data['state'] = strtoupper($data['state']);
        } else {
            $data['state'] = null; // Database requires exactly 2 chars or NULL
        }
        
        // Format phone number
        if (!empty($data['phone_number'])) {
            $data['phone_number'] = preg_replace('/[^0-9]/', '', $data['phone_number']);
        }
        
        // Validation
        $phoneMinLength = SettingsService::getPhoneNumberLength();
        $errors = $this->validate($data, [
            'full_name' => 'required|min:2',
            'company_name' => 'required|min:2',
            'phone_number' => 'required|min:' . $phoneMinLength,
            'state' => 'max:2'  // Only validate max length if state is provided
        ]);
        
        // Validate email if provided
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please provide a valid email address';
        }
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $data;
            $this->redirect('/customers/' . $id . '/edit');
            return;
        }
        
        // Check for duplicate customer (excluding current customer)
        $customerModel = new Customer();
        if ($customerModel->isDuplicate($data['full_name'], $data['phone_number'], $id)) {
            $duplicate = $customerModel->findDuplicate($data['full_name'], $data['phone_number']);
            
            $errorMsg = "Another customer with this name already exists.";
            if ($duplicate && $duplicate->customer_id != $id) {
                $errorMsg .= " (Customer #" . $duplicate->customer_id . ": " . 
                             $duplicate->full_name;
                if ($duplicate->company_name) {
                    $errorMsg .= " - " . $duplicate->company_name;
                }
                $errorMsg .= ")";
            }
            
            $_SESSION['errors'] = ['duplicate' => $errorMsg];
            $_SESSION['old'] = $data;
            $this->redirect('/customers/' . $id . '/edit');
            return;
        }
        
        // Format phone number
        if (!empty($data['phone_number'])) {
            $data['phone_number'] = preg_replace('/[^0-9]/', '', $data['phone_number']);
        }
        
        // Use error handling wrapper for database operation
        $result = $this->handleDatabaseOperation(
            function() use ($customer, $data) {
                $customer->fill($data);
                return $customer->update();
            },
            'Customer updated successfully.',
            'Failed to update customer. Please check your information and try again.'
        );
        
        if ($result) {
            $this->redirect('/customers/' . $id);
        } else {
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
        
        // Use error handling wrapper for database operation
        $this->handleDatabaseOperation(
            function() use ($customer) {
                return $customer->delete();
            },
            'Customer deleted successfully.',
            'Failed to delete customer. It may be referenced by other records.'
        );
        
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
