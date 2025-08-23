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
        'state', 'zip_code', 'phone_number'
    ];
    
    public function getOrders()
    {
        $order = new Order();
        return $order->where('customer_id', '=', $this->customer_id);
    }
    
    public function getFullAddress()
    {
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
            return '<span class="badge badge-success">Active</span>';
        } else {
            return '<span class="badge badge-secondary">Inactive</span>';
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
        if (strlen($phone) === 10) {
            return sprintf('(%s) %s-%s', 
                substr($phone, 0, 3), 
                substr($phone, 3, 3), 
                substr($phone, 6, 4)
            );
        }
        return $this->phone_number;
    }
    
    public function searchCustomers($query, $limit = 20)
    {
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
}