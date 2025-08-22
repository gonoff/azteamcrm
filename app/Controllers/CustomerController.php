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
            'title' => 'Customer Management'
        ]);
    }
    
    public function create()
    {
        $this->view('customers/form', [
            'title' => 'Add New Customer',
            'customer' => null,
            'action' => '/azteamcrm/customers/store'
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
            $this->redirect('/azteamcrm/customers/create');
            return;
        }
        
        // Format phone number
        if (!empty($data['phone_number'])) {
            $data['phone_number'] = preg_replace('/[^0-9]/', '', $data['phone_number']);
        }
        
        $customer = new Customer();
        if ($customer->create($data)) {
            $_SESSION['success'] = 'Customer created successfully.';
            $this->redirect('/azteamcrm/customers');
        } else {
            $_SESSION['error'] = 'Failed to create customer.';
            $_SESSION['old'] = $data;
            $this->redirect('/azteamcrm/customers/create');
        }
    }
    
    public function show($id)
    {
        $customer = new Customer();
        $customer = $customer->find($id);
        
        if (!$customer) {
            $_SESSION['error'] = 'Customer not found.';
            $this->redirect('/azteamcrm/customers');
            return;
        }
        
        $orders = $customer->getOrders();
        
        $this->view('customers/show', [
            'customer' => $customer,
            'orders' => $orders,
            'title' => 'Customer Details'
        ]);
    }
    
    public function edit($id)
    {
        $customer = new Customer();
        $customer = $customer->find($id);
        
        if (!$customer) {
            $_SESSION['error'] = 'Customer not found.';
            $this->redirect('/azteamcrm/customers');
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
            $this->redirect('/azteamcrm/customers');
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
            $this->redirect('/azteamcrm/customers/' . $id . '/edit');
            return;
        }
        
        // Format phone number
        if (!empty($data['phone_number'])) {
            $data['phone_number'] = preg_replace('/[^0-9]/', '', $data['phone_number']);
        }
        
        $customer->fill($data);
        if ($customer->update()) {
            $_SESSION['success'] = 'Customer updated successfully.';
            $this->redirect('/azteamcrm/customers/' . $id);
        } else {
            $_SESSION['error'] = 'Failed to update customer.';
            $_SESSION['old'] = $data;
            $this->redirect('/azteamcrm/customers/' . $id . '/edit');
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
            $this->redirect('/azteamcrm/customers');
            return;
        }
        
        // Check if customer has orders
        if ($customer->getTotalOrders() > 0) {
            $_SESSION['error'] = 'Cannot delete customer with existing orders.';
            $this->redirect('/azteamcrm/customers');
            return;
        }
        
        if ($customer->delete()) {
            $_SESSION['success'] = 'Customer deleted successfully.';
        } else {
            $_SESSION['error'] = 'Failed to delete customer.';
        }
        
        $this->redirect('/azteamcrm/customers');
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
}