<?php

namespace App\Models;

use App\Core\Model;

class Customer extends Model
{
    protected $table = 'customers';
    protected $primaryKey = 'customer_id';
    protected $fillable = [
        'customer_status', 'full_name', 'company_name', 
        'address_line_1', 'address_line_2', 'city', 
        'state', 'zip_code', 'phone_number', 'email'
    ];
    
    public function getOrders()
    {
        $order = new Order();
        return $order->where('customer_id', '=', $this->customer_id);
    }
    
    public function getFullAddress()
    {
        // Check if primary address information is available
        if (empty($this->address_line_1) || empty($this->city) || empty($this->state) || empty($this->zip_code)) {
            return 'Address not provided';
        }
        
        $address = $this->address_line_1;
        if ($this->address_line_2) {
            $address .= ', ' . $this->address_line_2;
        }
        $address .= ', ' . $this->city . ', ' . $this->state . ' ' . $this->zip_code;
        return $address;
    }
    
    public function getDisplayName()
    {
        if ($this->company_name) {
            return $this->company_name . ' (' . $this->full_name . ')';
        }
        return $this->full_name;
    }
    
    public function getStatusBadge()
    {
        if ($this->customer_status === 'active') {
            return '<span class="badge bg-success">Active</span>';
        } else {
            return '<span class="badge bg-secondary">Inactive</span>';
        }
    }
    
    public function getTotalOrders()
    {
        $sql = "SELECT COUNT(*) as count FROM orders WHERE customer_id = :customer_id";
        $stmt = $this->db->query($sql, ['customer_id' => $this->customer_id]);
        
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        }
        return 0;
    }
    
    public function getTotalRevenue()
    {
        $sql = "SELECT SUM(order_total) as total FROM orders WHERE customer_id = :customer_id";
        $stmt = $this->db->query($sql, ['customer_id' => $this->customer_id]);
        
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        }
        return 0;
    }
    
    public function formatPhoneNumber()
    {
        $phone = preg_replace('/[^0-9]/', '', $this->phone_number);
        $expectedLength = \App\Services\SettingsService::getPhoneNumberLength();
        if (strlen($phone) === $expectedLength) {
            return sprintf('(%s) %s-%s', 
                substr($phone, 0, 3), 
                substr($phone, 3, 3), 
                substr($phone, 6, 4)
            );
        }
        return $this->phone_number;
    }
    
    public function searchCustomers($query, $limit = null)
    {
        // Use default customer search limit if not specified
        if ($limit === null) {
            $limit = \App\Services\SettingsService::getCustomerSearchLimit();
        }
        
        // Sanitize the query for use in LIKE statements
        $searchTerm = '%' . $query . '%';
        
        // Remove non-numeric characters for phone search
        $phoneSearch = preg_replace('/[^0-9]/', '', $query);
        $phoneSearchTerm = '%' . $phoneSearch . '%';
        
        // Build the SQL query to search across multiple fields
        $sql = "SELECT customer_id, full_name, company_name, phone_number, customer_status 
                FROM {$this->table} 
                WHERE customer_status = 'active' 
                AND (
                    full_name LIKE :name_search 
                    OR company_name LIKE :company_search 
                    OR phone_number LIKE :phone_search
                )
                ORDER BY 
                    CASE 
                        WHEN full_name LIKE :exact_match THEN 1
                        WHEN company_name LIKE :exact_match_company THEN 2
                        ELSE 3
                    END,
                    full_name ASC
                LIMIT :limit";
        
        // Use the Database connection directly for prepared statements
        $connection = $this->db->getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->bindValue(':name_search', $searchTerm, \PDO::PARAM_STR);
        $stmt->bindValue(':company_search', $searchTerm, \PDO::PARAM_STR);
        $stmt->bindValue(':phone_search', $phoneSearchTerm, \PDO::PARAM_STR);
        $stmt->bindValue(':exact_match', $query . '%', \PDO::PARAM_STR);
        $stmt->bindValue(':exact_match_company', $query . '%', \PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
        }
        
        return [];
    }
    
    public function isDuplicate($fullName, $phoneNumber = null, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE LOWER(REPLACE(full_name, ' ', '')) = LOWER(REPLACE(:full_name, ' ', ''))";
        
        $params = ['full_name' => $fullName];
        
        // Include phone number in duplicate check if provided
        if ($phoneNumber) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
            if ($cleanPhone) {
                $sql .= " OR (phone_number IS NOT NULL AND REPLACE(phone_number, ' ', '') = :phone)";
                $params['phone'] = $cleanPhone;
            }
        }
        
        // Exclude current record when updating
        if ($excludeId) {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                    WHERE (LOWER(REPLACE(full_name, ' ', '')) = LOWER(REPLACE(:full_name, ' ', ''))";
            if ($phoneNumber) {
                $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
                if ($cleanPhone) {
                    $sql .= " OR (phone_number IS NOT NULL AND REPLACE(phone_number, ' ', '') = :phone)";
                    $params['phone'] = $cleanPhone;
                }
            }
            $sql .= ") AND customer_id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt = $this->db->query($sql, $params);
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        }
        return false;
    }
    
    public function findDuplicate($fullName, $phoneNumber = null)
    {
        $sql = "SELECT customer_id, full_name, company_name, phone_number 
                FROM {$this->table} 
                WHERE LOWER(REPLACE(full_name, ' ', '')) = LOWER(REPLACE(:full_name, ' ', ''))";
        
        $params = ['full_name' => $fullName];
        
        if ($phoneNumber) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
            if ($cleanPhone) {
                $sql .= " OR (phone_number IS NOT NULL AND REPLACE(phone_number, ' ', '') = :phone)";
                $params['phone'] = $cleanPhone;
            }
        }
        
        $sql .= " LIMIT 1";
        
        $stmt = $this->db->query($sql, $params);
        if ($stmt) {
            return $stmt->fetch(\PDO::FETCH_OBJ);
        }
        return null;
    }
}