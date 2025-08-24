<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class UserController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
        $this->requireRole('administrator');
    }
    
    public function index()
    {
        $user = new User();
        $users = $user->findAll();
        
        $this->view('users/index', [
            'title' => 'User Management',
            'users' => $users,
            'csrf_token' => $this->csrf()
        ]);
    }
    
    public function create()
    {
        $this->view('users/form', [
            'title' => 'Create User',
            'user' => null,
            'csrf_token' => $this->csrf(),
            'errors' => $_SESSION['errors'] ?? [],
            'old' => $_SESSION['old'] ?? []
        ]);
        
        unset($_SESSION['errors'], $_SESSION['old']);
    }
    
    public function store()
    {
        if (!$this->isPost()) {
            $this->redirect('/users');
        }
        
        $this->verifyCsrf();
        
        $data = [
            'username' => $this->sanitize($_POST['username'] ?? ''),
            'email' => $this->sanitize($_POST['email'] ?? ''),
            'full_name' => $this->sanitize($_POST['full_name'] ?? ''),
            'role' => $this->sanitize($_POST['role'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? ''
        ];
        
        // Apply title case to full name
        $data['full_name'] = $this->toTitleCase($data['full_name']);
        
        $errors = $this->validateUserData($data, true);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $data;
            $this->redirect('/users/create');
        }
        
        $user = new User();
        
        // Check if username or email already exists
        if ($user->existsExcept('username', $data['username'])) {
            $_SESSION['errors'] = ['username' => 'Username already exists'];
            $_SESSION['old'] = $data;
            $this->redirect('/users/create');
        }
        
        if ($user->existsExcept('email', $data['email'])) {
            $_SESSION['errors'] = ['email' => 'Email already exists'];
            $_SESSION['old'] = $data;
            $this->redirect('/users/create');
        }
        
        $user->username = $data['username'];
        $user->email = $data['email'];
        $user->full_name = $data['full_name'];
        $user->role = $data['role'];
        $user->setPassword($data['password']);
        $user->is_active = true;
        
        // Use error handling wrapper for database operation
        $result = $this->handleDatabaseOperation(
            function() use ($user) {
                return $user->save();
            },
            'User created successfully',
            'Failed to create user. Please check your information and try again.'
        );
        
        if ($result) {
            $this->redirect('/users');
        } else {
            $this->redirect('/users/create');
        }
    }
    
    public function edit($id)
    {
        $user = new User();
        $user = $user->find($id);
        
        if (!$user) {
            $this->view('errors/404');
            exit;
        }
        
        $this->view('users/form', [
            'title' => 'Edit User',
            'user' => $user,
            'csrf_token' => $this->csrf(),
            'errors' => $_SESSION['errors'] ?? [],
            'old' => $_SESSION['old'] ?? []
        ]);
        
        unset($_SESSION['errors'], $_SESSION['old']);
    }
    
    public function update($id)
    {
        if (!$this->isPost()) {
            $this->redirect('/users');
        }
        
        $this->verifyCsrf();
        
        $user = new User();
        $user = $user->find($id);
        
        if (!$user) {
            $this->view('errors/404');
            exit;
        }
        
        $data = [
            'username' => $this->sanitize($_POST['username'] ?? ''),
            'email' => $this->sanitize($_POST['email'] ?? ''),
            'full_name' => $this->sanitize($_POST['full_name'] ?? ''),
            'role' => $this->sanitize($_POST['role'] ?? ''),
            'password' => $_POST['password'] ?? ''
        ];
        
        // Apply title case to full name
        $data['full_name'] = $this->toTitleCase($data['full_name']);
        
        $errors = $this->validateUserData($data, false);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $data;
            $this->redirect("/users/{$id}/edit");
        }
        
        // Check if username or email already exists (excluding current user)
        $existingUser = new User();
        
        if ($existingUser->existsExcept('username', $data['username'], $id)) {
            $_SESSION['errors'] = ['username' => 'Username already exists'];
            $_SESSION['old'] = $data;
            $this->redirect("/users/{$id}/edit");
        }
        
        if ($existingUser->existsExcept('email', $data['email'], $id)) {
            $_SESSION['errors'] = ['email' => 'Email already exists'];
            $_SESSION['old'] = $data;
            $this->redirect("/users/{$id}/edit");
        }
        
        $user->username = $data['username'];
        $user->email = $data['email'];
        $user->full_name = $data['full_name'];
        $user->role = $data['role'];
        
        // Only update password if provided
        if (!empty($data['password'])) {
            $user->setPassword($data['password']);
        }
        
        // Use error handling wrapper for database operation
        $result = $this->handleDatabaseOperation(
            function() use ($user) {
                return $user->save();
            },
            'User updated successfully',
            'Failed to update user. Please check your information and try again.'
        );
        
        if ($result) {
            $this->redirect('/users');
        } else {
            $this->redirect("/users/{$id}/edit");
        }
    }
    
    public function delete($id)
    {
        if (!$this->isPost()) {
            $this->redirect('/users');
        }
        
        $this->verifyCsrf();
        
        // Prevent self-deletion
        if ($id == $_SESSION['user_id']) {
            $_SESSION['error'] = 'You cannot delete your own account';
            $this->redirect('/users');
        }
        
        $user = new User();
        $user = $user->find($id);
        
        if (!$user) {
            $this->view('errors/404');
            exit;
        }
        
        // Soft delete by deactivating the user
        $user->is_active = false;
        
        if ($user->save()) {
            $_SESSION['success'] = 'User deactivated successfully';
        } else {
            $_SESSION['error'] = 'Failed to deactivate user';
        }
        
        $this->redirect('/users');
    }
    
    public function toggleStatus($id)
    {
        if (!$this->isPost()) {
            $this->redirect('/users');
        }
        
        $this->verifyCsrf();
        
        // Prevent self-deactivation
        if ($id == $_SESSION['user_id']) {
            $_SESSION['error'] = 'You cannot change your own status';
            $this->redirect('/users');
        }
        
        $user = new User();
        $user = $user->find($id);
        
        if (!$user) {
            $this->json(['success' => false, 'message' => 'User not found'], 404);
        }
        
        // Toggle status and convert to integer for MySQL
        $user->is_active = $user->is_active ? 0 : 1;
        
        if ($user->save()) {
            $status = $user->is_active ? 'activated' : 'deactivated';
            $this->json(['success' => true, 'message' => "User {$status} successfully", 'is_active' => $user->is_active]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to update user status'], 500);
        }
    }
    
    public function profile()
    {
        $this->requireAuth();
        
        $user = new User();
        $user = $user->find($_SESSION['user_id']);
        
        $this->view('users/profile', [
            'title' => 'My Profile',
            'user' => $user,
            'csrf_token' => $this->csrf(),
            'success' => $_SESSION['success'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ]);
        
        unset($_SESSION['success'], $_SESSION['error']);
    }
    
    public function updatePassword()
    {
        $this->requireAuth();
        
        if (!$this->isPost()) {
            $this->redirect('/profile');
        }
        
        $this->verifyCsrf();
        
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        if (empty($currentPassword)) {
            $errors[] = 'Current password is required';
        }
        
        if (empty($newPassword)) {
            $errors[] = 'New password is required';
        } elseif (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match';
        }
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $this->redirect('/profile');
        }
        
        $user = new User();
        $user = $user->find($_SESSION['user_id']);
        
        // Verify current password
        if (!password_verify($currentPassword, $user->password_hash)) {
            $_SESSION['error'] = 'Current password is incorrect';
            $this->redirect('/profile');
        }
        
        // Update password
        $user->setPassword($newPassword);
        
        if ($user->save()) {
            $_SESSION['success'] = 'Password updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update password';
        }
        
        $this->redirect('/profile');
    }
    
    private function validateUserData($data, $isNew = true)
    {
        $errors = [];
        
        if (empty($data['username'])) {
            $errors['username'] = 'Username is required';
        } elseif (strlen($data['username']) < 3) {
            $errors['username'] = 'Username must be at least 3 characters';
        }
        
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
        
        if (empty($data['full_name'])) {
            $errors['full_name'] = 'Full name is required';
        }
        
        if (empty($data['role'])) {
            $errors['role'] = 'Role is required';
        } elseif (!in_array($data['role'], ['administrator', 'production_team'])) {
            $errors['role'] = 'Invalid role selected';
        }
        
        if ($isNew) {
            if (empty($data['password'])) {
                $errors['password'] = 'Password is required';
            } elseif (strlen($data['password']) < 6) {
                $errors['password'] = 'Password must be at least 6 characters';
            }
            
            if ($data['password'] !== $data['password_confirm']) {
                $errors['password_confirm'] = 'Passwords do not match';
            }
        } elseif (!empty($data['password']) && strlen($data['password']) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }
        
        return $errors;
    }
}