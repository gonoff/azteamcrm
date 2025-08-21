<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        @session_start();
        
        // If already logged in, redirect to dashboard
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }
        
        $this->view('auth/login', [
            'csrf_token' => $this->csrf(),
            'error' => $_SESSION['login_error'] ?? null
        ]);
        
        unset($_SESSION['login_error']);
    }
    
    public function login()
    {
        @session_start();
        
        if (!$this->isPost()) {
            $this->redirect('/login');
        }
        
        $this->verifyCsrf();
        
        $username = $this->sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        $errors = $this->validate($_POST, [
            'username' => 'required',
            'password' => 'required'
        ]);
        
        if (!empty($errors)) {
            $_SESSION['login_error'] = 'Please enter both username and password.';
            $this->redirect('/login');
        }
        
        $user = new User();
        $authenticatedUser = $user->authenticate($username, $password);
        
        if ($authenticatedUser) {
            // Set session variables
            $_SESSION['user_id'] = $authenticatedUser->id;
            $_SESSION['username'] = $authenticatedUser->username;
            $_SESSION['user_role'] = $authenticatedUser->role;
            $_SESSION['full_name'] = $authenticatedUser->getFullName();
            $_SESSION['logged_in_at'] = time();
            $_SESSION['last_activity'] = time();
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Redirect to dashboard
            $this->redirect('/dashboard');
        } else {
            $_SESSION['login_error'] = 'Invalid username or password.';
            $this->redirect('/login');
        }
    }
    
    public function logout()
    {
        @session_start();
        
        // Clear all session variables
        $_SESSION = [];
        
        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy the session
        session_destroy();
        
        // Redirect to login page
        $this->redirect('/login');
    }
}