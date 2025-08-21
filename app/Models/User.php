<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected $table = 'users';
    protected $fillable = ['username', 'email', 'password_hash', 'role', 'full_name', 'is_active'];
    
    public function authenticate($username, $password)
    {
        $sql = "SELECT * FROM {$this->table} WHERE username = :username AND is_active = 1 LIMIT 1";
        $stmt = $this->db->query($sql, ['username' => $username]);
        
        if ($stmt) {
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($user && password_verify($password, $user['password_hash'])) {
                $this->attributes = $user;
                return $this;
            }
        }
        return false;
    }
    
    public function setPassword($password)
    {
        $this->password_hash = password_hash($password, PASSWORD_BCRYPT);
        return $this;
    }
    
    public function isAdmin()
    {
        return $this->role === 'administrator';
    }
    
    public function isProduction()
    {
        return $this->role === 'production_team';
    }
    
    public function getOrders()
    {
        $order = new Order();
        return $order->where('captured_by_user_id', '=', $this->id);
    }
    
    public function getFullName()
    {
        return $this->full_name ?: $this->username;
    }
    
    public function canAccessReports()
    {
        return $this->isAdmin();
    }
    
    public function canManageUsers()
    {
        return $this->isAdmin();
    }
    
    public function canUpdateProduction()
    {
        return $this->isAdmin() || $this->isProduction();
    }
}