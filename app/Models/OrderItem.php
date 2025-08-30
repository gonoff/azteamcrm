<?php

namespace App\Models;

use App\Core\Model;

class OrderItem extends Model
{
    protected $table = 'order_items';
    protected $primaryKey = 'order_item_id';
    protected $fillable = [
        'order_item_status', 'order_id', 'user_id', 'quantity',
        'unit_price', 'product_type', 'product_description',
        'product_size', 'custom_method', 'custom_area',
        'supplier_status', 'material_prepared', 'note_item',
        'claimed_by', 'claimed_at', 'completed_by', 'completed_at'
    ];
    
    public function getOrder()
    {
        $order = new Order();
        return $order->find($this->order_id);
    }
    
    public function getUser()
    {
        $user = new User();
        return $user->find($this->user_id);
    }
    
    public function updateStatus($status)
    {
        $this->attributes['order_item_status'] = $status;
        $this->order_item_status = $status;
        
        // Set completion timestamp when item is completed
        if ($status === 'completed') {
            $this->attributes['completed_at'] = date('Y-m-d H:i:s');
            $this->completed_at = $this->attributes['completed_at'];
        }
        
        return $this->update();
    }
    
    public function updateSupplierStatus($status)
    {
        $this->attributes['supplier_status'] = $status;
        $this->supplier_status = $status;
        return $this->update();
    }
    
    public function calculateTotalPrice()
    {
        // Note: total_price is a generated column in the database
        // This method is for reference if needed in PHP
        return $this->quantity * $this->unit_price;
    }
    
    public function countInProduction()
    {
        // Only count items actually in production, not pending ones
        $sql = "SELECT COUNT(*) as count FROM {$this->table} oi
                JOIN orders o ON oi.order_id = o.order_id
                WHERE oi.order_item_status IN ('artwork_sent_for_approval', 'artwork_approved', 'nesting_digitalization_done')
                AND o.order_status != 'cancelled'";
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        }
        return 0;
    }
    
    public function getPendingItems()
    {
        $sql = "SELECT oi.*, o.date_due, c.full_name, c.company_name 
                FROM {$this->table} oi
                JOIN orders o ON oi.order_id = o.order_id
                JOIN customers c ON o.customer_id = c.customer_id
                WHERE oi.order_item_status != 'completed'
                ORDER BY o.date_due ASC";
        
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            return $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
        }
        return [];
    }
    
    /**
     * Status constants for 5-stage workflow
     */
    const STATUS_PENDING = 'pending';
    const STATUS_ARTWORK_SENT = 'artwork_sent_for_approval';
    const STATUS_ARTWORK_APPROVED = 'artwork_approved';
    const STATUS_NESTING_DONE = 'nesting_digitalization_done';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled'; // Legacy
    const STATUS_ON_HOLD = 'on_hold'; // Legacy
    
    /**
     * Get all valid status values
     */
    public static function getValidStatuses()
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_ARTWORK_SENT,
            self::STATUS_ARTWORK_APPROVED,
            self::STATUS_NESTING_DONE,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_ON_HOLD
        ];
    }
    
    /**
     * Get status labels for display
     */
    public static function getStatusLabels()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ARTWORK_SENT => 'Artwork Sent for Approval',
            self::STATUS_ARTWORK_APPROVED => 'Artwork Approved',
            self::STATUS_NESTING_DONE => 'Nesting/Digitalization Done',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_ON_HOLD => 'On Hold'
        ];
    }
    
    /**
     * Get status progression sequence
     */
    public static function getStatusProgression()
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_ARTWORK_SENT,
            self::STATUS_ARTWORK_APPROVED,
            self::STATUS_NESTING_DONE,
            self::STATUS_COMPLETED
        ];
    }
    
    /**
     * Check if status can be progressed to next stage
     */
    public function canAdvanceStatus()
    {
        $progression = self::getStatusProgression();
        $currentIndex = array_search($this->order_item_status, $progression);
        return $currentIndex !== false && $currentIndex < count($progression) - 1;
    }
    
    /**
     * Get next status in progression
     */
    public function getNextStatus()
    {
        $progression = self::getStatusProgression();
        $currentIndex = array_search($this->order_item_status, $progression);
        
        if ($currentIndex !== false && $currentIndex < count($progression) - 1) {
            return $progression[$currentIndex + 1];
        }
        
        return null;
    }
    
    public function getStatusBadge()
    {
        // Enhanced 5-stage workflow status badges
        $badges = [
            self::STATUS_PENDING => '<span class="badge bg-warning text-dark">Pending</span>',
            self::STATUS_ARTWORK_SENT => '<span class="badge bg-primary">Artwork Sent</span>',
            self::STATUS_ARTWORK_APPROVED => '<span class="badge bg-info">Artwork Approved</span>',
            self::STATUS_NESTING_DONE => '<span class="badge bg-purple">Nesting/Digitalization</span>',
            self::STATUS_COMPLETED => '<span class="badge bg-success">Completed</span>',
            self::STATUS_CANCELLED => '<span class="badge bg-secondary">Cancelled</span>',
            self::STATUS_ON_HOLD => '<span class="badge bg-secondary">On Hold</span>'
        ];
        
        return $badges[$this->order_item_status] ?? '<span class="badge bg-secondary">' . ucfirst(str_replace('_', ' ', $this->order_item_status)) . '</span>';
    }
    
    public function getSupplierStatusBadge()
    {
        if (!$this->supplier_status) {
            return '<span class="badge bg-light text-dark">N/A</span>';
        }
        
        $badges = [
            'awaiting_order' => '<span class="badge bg-secondary">Waiting</span>',
            'order_made' => '<span class="badge bg-success">Order Made</span>',
            'order_arrived' => '<span class="badge bg-danger">Order Arrived</span>',
            'order_delivered' => '<span class="badge bg-primary">Order Delivered</span>'
        ];
        
        return $badges[$this->supplier_status] ?? '<span class="badge bg-secondary">' . ucfirst(str_replace('_', ' ', $this->supplier_status)) . '</span>';
    }
    
    public function getSizeLabel()
    {
        if (!$this->product_size) {
            return 'N/A';
        }
        
        $sizes = [
            'child_xs' => 'Child XS',
            'child_s' => 'Child S',
            'child_m' => 'Child M',
            'child_l' => 'Child L',
            'child_xl' => 'Child XL',
            'xs' => 'XS',
            's' => 'S',
            'm' => 'M',
            'l' => 'L',
            'xl' => 'XL',
            'xxl' => 'XXL',
            'xxxl' => 'XXXL',
            'xxxxl' => 'XXXXL'
        ];
        
        return $sizes[strtolower($this->product_size)] ?? $this->product_size;
    }
    
    public function getCustomMethodLabel()
    {
        if (!$this->custom_method) {
            return 'N/A';
        }
        
        $methods = [
            'htv' => 'HTV',
            'dft' => 'DFT',
            'embroidery' => 'Embroidery',
            'sublimation' => 'Sublimation',
            'printing' => 'Printing Services'
        ];
        
        return $methods[strtolower($this->custom_method)] ?? ucwords(str_replace('_', ' ', $this->custom_method));
    }
    
    public function getProductTypeLabel()
    {
        if (!$this->product_type) {
            return 'N/A';
        }
        
        return ucwords(str_replace('_', ' ', $this->product_type));
    }
    
    public function getCustomAreaLabel()
    {
        if (!$this->custom_area) {
            return 'N/A';
        }
        
        // Handle comma-separated values
        $areas = explode(',', $this->custom_area);
        $formattedAreas = array_map(function($area) {
            return ucwords(str_replace('_', ' ', trim($area)));
        }, $areas);
        
        // Join with comma and space for better readability
        return implode(', ', $formattedAreas);
    }
    
    public function getUrgencyBadge()
    {
        if (!isset($this->urgency_level)) {
            return '';
        }
        
        $badges = [
            'overdue' => '<span class="badge bg-danger">OVERDUE</span>',
            'due_today' => '<span class="badge bg-warning text-dark">DUE TODAY</span>',
            'due_soon' => '<span class="badge bg-info text-dark">DUE SOON</span>',
            'rush' => '<span class="badge bg-danger">RUSH</span>',
            'normal' => ''
        ];
        
        return $badges[$this->urgency_level] ?? '';
    }
    
    public function getRowClass()
    {
        if (!isset($this->urgency_level)) {
            return '';
        }
        
        $classes = [
            'overdue' => 'table-danger',
            'due_today' => 'table-warning',
            'due_soon' => 'table-info',
            'rush' => 'table-warning',
            'normal' => ''
        ];
        
        return $classes[$this->urgency_level] ?? '';
    }
    
    public function getProductionItems()
    {
        $sql = "SELECT 
                    oi.*, 
                    o.order_id,
                    o.date_due,
                    o.order_status,
                    o.payment_status,
                    c.full_name as customer_name,
                    c.company_name,
                    c.phone_number,
                    CASE 
                        WHEN o.date_due < CURDATE() THEN 'overdue'
                        WHEN o.date_due = CURDATE() THEN 'due_today'
                        WHEN o.date_due <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 'due_soon'
                        WHEN o.date_due <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'rush'
                        ELSE 'normal'
                    END as urgency_level
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.order_id
                JOIN customers c ON o.customer_id = c.customer_id
                WHERE oi.order_item_status != 'completed'
                    AND o.order_status NOT IN ('cancelled', 'on_hold')
                ORDER BY 
                    FIELD(urgency_level, 'overdue', 'due_today', 'due_soon', 'rush', 'normal'),
                    o.date_due ASC";
        
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $results = $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
            // Add additional properties to each item
            foreach ($results as $item) {
                $item->customer_name = $item->customer_name ?? 'Unknown';
                $item->company_name = $item->company_name ?? '';
                $item->phone_number = $item->phone_number ?? '';
                $item->urgency_level = $item->urgency_level ?? 'normal';
            }
            return $results;
        }
        return [];
    }
    
    public function getCompletedTodayCount()
    {
        // Now uses the completed_at timestamp to track when items were completed
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->table} oi
                JOIN orders o ON oi.order_id = o.order_id
                WHERE oi.order_item_status = 'completed' 
                    AND DATE(oi.completed_at) = CURDATE()
                    AND o.order_status NOT IN ('cancelled', 'on_hold')";
        
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        }
        return 0;
    }
    
    public function getItemsDueToday()
    {
        $sql = "SELECT 
                    oi.*, 
                    o.order_id,
                    o.date_due,
                    o.order_status,
                    c.full_name as customer_name,
                    c.company_name,
                    CASE 
                        WHEN o.date_due = CURDATE() THEN 'due_today'
                        WHEN o.date_due = DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 'due_tomorrow'
                        ELSE 'start_today'
                    END as priority
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.order_id
                JOIN customers c ON o.customer_id = c.customer_id
                WHERE oi.order_item_status != 'completed'
                    AND o.order_status NOT IN ('cancelled', 'on_hold')
                    AND o.date_due <= DATE_ADD(CURDATE(), INTERVAL 2 DAY)
                ORDER BY o.date_due ASC, priority ASC";
        
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            return $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
        }
        return [];
    }
    
    public function getPendingProductionItems()
    {
        $sql = "SELECT 
                    oi.*, 
                    o.order_id,
                    o.date_due,
                    o.order_status,
                    c.full_name as customer_name,
                    c.company_name
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.order_id
                JOIN customers c ON o.customer_id = c.customer_id
                WHERE oi.order_item_status = 'pending'
                    AND o.order_status NOT IN ('cancelled', 'on_hold')
                ORDER BY o.date_due ASC";
        
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            return $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
        }
        return [];
    }
    
    public function getMaterialsSummary()
    {
        $sql = "SELECT 
                    product_type,
                    product_size,
                    custom_method,
                    COUNT(*) as item_count,
                    SUM(quantity) as total_quantity
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.order_id
                WHERE oi.order_item_status != 'completed'
                    AND o.order_status NOT IN ('cancelled', 'on_hold')
                GROUP BY product_type, product_size, custom_method
                ORDER BY product_type, product_size";
        
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        return [];
    }
    
    public function searchAndPaginate($searchTerm, $searchFields, $page = 1, $perPage = 50, $conditions = [], $orderBy = 'date_due ASC', array $allowedOrderColumns = ['date_due'])
    {
        $page = max(1, intval($page));
        $perPage = max(1, min(100, intval($perPage))); // Limit max per page
        $offset = ($page - 1) * $perPage;
        
        // Build the base query with joins
        $baseQuery = "FROM order_items oi
                     JOIN orders o ON oi.order_id = o.order_id
                     JOIN customers c ON o.customer_id = c.customer_id";
        
        // Build WHERE clause
        $whereClause = "WHERE o.order_status NOT IN ('cancelled', 'on_hold')";
        $params = [];
        
        // Add conditions
        if (!empty($conditions)) {
            foreach ($conditions as $field => $value) {
                if (is_array($value)) {
                    $placeholders = [];
                    foreach ($value as $i => $val) {
                        $placeholder = $field . '_' . $i;
                        $placeholders[] = ':' . $placeholder;
                        $params[$placeholder] = $val;
                    }
                    $whereClause .= " AND oi.{$field} IN (" . implode(',', $placeholders) . ")";
                } else {
                    $whereClause .= " AND oi.{$field} = :{$field}";
                    $params[$field] = $value;
                }
            }
        }
        
        // Add search conditions
        if (!empty($searchTerm) && !empty($searchFields)) {
            $searchConditions = [];
            foreach ($searchFields as $i => $field) {
                $searchParam = "search_" . $i;
                $searchConditions[] = "oi.{$field} LIKE :{$searchParam}";
                $params[$searchParam] = '%' . $searchTerm . '%';
            }
            $whereClause .= " AND (" . implode(' OR ', $searchConditions) . ")";
        }
        
        // Count total items
        $countSql = "SELECT COUNT(*) as total " . $baseQuery . " " . $whereClause;
        $stmt = $this->db->query($countSql, $params);
        $totalItems = $stmt ? ($stmt->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0) : 0;
        
        // Get paginated data with urgency calculation
        $dataSql = "SELECT 
                        oi.*, 
                        o.order_id,
                        o.date_due,
                        o.order_status,
                        o.payment_status,
                        c.full_name as customer_name,
                        c.company_name,
                        c.phone_number,
                        CASE 
                            WHEN o.date_due < CURDATE() THEN 'overdue'
                            WHEN o.date_due = CURDATE() THEN 'due_today'
                            WHEN o.date_due <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 'due_soon'
                            WHEN o.date_due <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'rush'
                            ELSE 'normal'
                        END as urgency_level
                    " . $baseQuery . " " . $whereClause;
        
        if ($orderBy) {
            $orderBy = $this->sanitizeOrderBy($orderBy, $allowedOrderColumns);
            if ($orderBy) {
                $dataSql .= " ORDER BY " . $orderBy;
            }
        }
        
        $dataSql .= " LIMIT {$perPage} OFFSET {$offset}";
        
        $stmt = $this->db->query($dataSql, $params);
        $data = [];
        
        if ($stmt) {
            $results = $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
            foreach ($results as $item) {
                // Ensure additional properties are set
                $item->customer_name = $item->customer_name ?? 'Unknown';
                $item->company_name = $item->company_name ?? '';
                $item->phone_number = $item->phone_number ?? '';
                $item->urgency_level = $item->urgency_level ?? 'normal';
                $data[] = $item;
            }
        }
        
        // Calculate pagination info
        $totalPages = ceil($totalItems / $perPage);
        
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages,
                'previous_page' => $page > 1 ? $page - 1 : null,
                'next_page' => $page < $totalPages ? $page + 1 : null,
                'start_item' => $totalItems > 0 ? $offset + 1 : 0,
                'end_item' => min($offset + $perPage, $totalItems)
            ]
        ];
    }
    
    public function getProductionStatistics()
    {
        // Get comprehensive production statistics
        // NOTE: For consistency with dashboard, pending count represents ORDERS with pending status
        $sql = "SELECT 
                    COUNT(*) as total_active_items,
                    SUM(CASE WHEN oi.order_item_status = 'pending' THEN 1 ELSE 0 END) as total_pending,
                    SUM(CASE WHEN oi.order_item_status = 'artwork_sent_for_approval' THEN 1 ELSE 0 END) as artwork_sent_count,
                    SUM(CASE WHEN oi.order_item_status = 'artwork_approved' THEN 1 ELSE 0 END) as artwork_approved_count,
                    SUM(CASE WHEN oi.order_item_status = 'nesting_digitalization_done' THEN 1 ELSE 0 END) as nesting_done_count,
                    SUM(CASE WHEN oi.order_item_status IN ('artwork_sent_for_approval', 'artwork_approved', 'nesting_digitalization_done') THEN 1 ELSE 0 END) as in_production,
                    SUM(CASE WHEN o.date_due <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
                                 AND o.date_due > DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                                 AND o.date_due > CURDATE()
                                 THEN 1 ELSE 0 END) as rush_items,
                    SUM(CASE WHEN o.date_due < CURDATE() THEN 1 ELSE 0 END) as overdue_items,
                    SUM(CASE WHEN oi.supplier_status IN ('awaiting_order', 'order_made') THEN 1 ELSE 0 END) as awaiting_supplier
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.order_id
                WHERE oi.order_item_status != 'completed'
                    AND o.order_status NOT IN ('cancelled', 'on_hold')";
        
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return [
                'total_pending' => (int)($result['total_pending'] ?? 0),
                'artwork_sent_count' => (int)($result['artwork_sent_count'] ?? 0),
                'artwork_approved_count' => (int)($result['artwork_approved_count'] ?? 0),
                'nesting_done_count' => (int)($result['nesting_done_count'] ?? 0),
                'in_production' => (int)($result['in_production'] ?? 0),
                'completed_today' => $this->getCompletedTodayCount(), // Now works with completion tracking
                'rush_items' => (int)($result['rush_items'] ?? 0),
                'overdue_items' => (int)($result['overdue_items'] ?? 0),
                'awaiting_supplier' => (int)($result['awaiting_supplier'] ?? 0)
            ];
        }
        
        return [
            'total_pending' => 0,
            'artwork_sent_count' => 0,
            'artwork_approved_count' => 0,
            'nesting_done_count' => 0,
            'in_production' => 0,
            'completed_today' => 0,
            'rush_items' => 0,
            'overdue_items' => 0,
            'awaiting_supplier' => 0
        ];
    }
    
    public function getProductionItemsPaginated($page = 1, $perPage = 50, $orderBy = 'date_due ASC')
    {
        $page = max(1, intval($page));
        $perPage = max(1, min(100, intval($perPage)));
        $offset = ($page - 1) * $perPage;
        
        // Count total items
        $countSql = "SELECT COUNT(*) as total 
                     FROM order_items oi
                     JOIN orders o ON oi.order_id = o.order_id
                     WHERE oi.order_item_status != 'completed'
                         AND o.order_status NOT IN ('cancelled', 'on_hold')";
        
        $stmt = $this->db->query($countSql);
        $totalItems = $stmt ? ($stmt->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0) : 0;
        
        // Get paginated production items with urgency calculation
        $dataSql = "SELECT 
                        oi.*, 
                        o.order_id,
                        o.date_due,
                        o.order_status,
                        o.payment_status,
                        c.full_name as customer_name,
                        c.company_name,
                        c.phone_number,
                        CASE 
                            WHEN o.date_due < CURDATE() THEN 'overdue'
                            WHEN o.date_due = CURDATE() THEN 'due_today'
                            WHEN o.date_due <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 'due_soon'
                            WHEN o.date_due <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'rush'
                            ELSE 'normal'
                        END as urgency_level
                    FROM order_items oi
                    JOIN orders o ON oi.order_id = o.order_id
                    JOIN customers c ON o.customer_id = c.customer_id
                    WHERE oi.order_item_status != 'completed'
                        AND o.order_status NOT IN ('cancelled', 'on_hold')
                    ORDER BY 
                        FIELD(urgency_level, 'overdue', 'due_today', 'due_soon', 'rush', 'normal'),
                        {$orderBy}
                    LIMIT {$perPage} OFFSET {$offset}";
        
        $stmt = $this->db->query($dataSql);
        $data = [];
        
        if ($stmt) {
            $results = $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
            foreach ($results as $item) {
                // Ensure additional properties are set
                $item->customer_name = $item->customer_name ?? 'Unknown';
                $item->company_name = $item->company_name ?? '';
                $item->phone_number = $item->phone_number ?? '';
                $item->urgency_level = $item->urgency_level ?? 'normal';
                $data[] = $item;
            }
        }
        
        // Calculate pagination info
        $totalPages = ceil($totalItems / $perPage);
        
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages,
                'previous_page' => $page > 1 ? $page - 1 : null,
                'next_page' => $page < $totalPages ? $page + 1 : null,
                'start_item' => $totalItems > 0 ? $offset + 1 : 0,
                'end_item' => min($offset + $perPage, $totalItems)
            ]
        ];
    }
    
    public function updateMaterialPrepared($prepared = true)
    {
        $this->attributes['material_prepared'] = $prepared ? 1 : 0;
        $this->material_prepared = $this->attributes['material_prepared'];
        return $this->update();
    }
    
    public function getSupplierTrackingData($sortBy = 'urgency')
    {
        // Build ORDER BY clause based on sort parameter
        $orderByClause = '';
        switch($sortBy) {
            case 'due_date_asc':
                $orderByClause = 'o.date_due ASC, o.order_id ASC, oi.order_item_id ASC';
                break;
            case 'due_date_desc':
                $orderByClause = 'o.date_due DESC, o.order_id ASC, oi.order_item_id ASC';
                break;
            case 'order_date_asc':
                $orderByClause = 'o.date_created ASC, o.order_id ASC, oi.order_item_id ASC';
                break;
            case 'order_date_desc':
                $orderByClause = 'o.date_created DESC, o.order_id ASC, oi.order_item_id ASC';
                break;
            case 'customer_name':
                $orderByClause = 'c.full_name ASC, o.order_id ASC, oi.order_item_id ASC';
                break;
            case 'urgency':
            default:
                $orderByClause = 'FIELD(urgency_level, \'overdue\', \'rush\', \'normal\'), o.date_due ASC, o.order_id ASC, oi.order_item_id ASC';
                break;
        }

        $sql = "SELECT 
                    o.order_id,
                    o.date_created as order_date,
                    o.date_due,
                    o.order_status,
                    c.full_name as customer_name,
                    c.company_name,
                    CASE 
                        WHEN o.date_due < CURDATE() THEN 'overdue'
                        WHEN o.date_due <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'rush'
                        ELSE 'normal'
                    END as urgency_level,
                    oi.*
                FROM orders o
                JOIN order_items oi ON o.order_id = oi.order_id
                JOIN customers c ON o.customer_id = c.customer_id
                WHERE o.order_status != 'completed'
                    AND o.order_status NOT IN ('cancelled', 'on_hold')
                ORDER BY {$orderByClause}";
        
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $results = $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
            // Group by order_id to organize data
            $orders = [];
            foreach ($results as $item) {
                $orderId = $item->order_id;
                
                if (!isset($orders[$orderId])) {
                    $orders[$orderId] = [
                        'order_id' => $item->order_id,
                        'order_date' => $item->order_date,
                        'date_due' => $item->date_due,
                        'customer_name' => $item->customer_name,
                        'company_name' => $item->company_name,
                        'urgency_level' => $item->urgency_level,
                        'items' => []
                    ];
                }
                
                $orders[$orderId]['items'][] = $item;
            }
            
            return array_values($orders); // Return indexed array
        }
        return [];
    }
    
    public function getCompletedOrdersData($sortBy = 'due_date_desc')
    {
        // Build ORDER BY clause based on sort parameter
        $orderByClause = '';
        switch($sortBy) {
            case 'due_date_asc':
                $orderByClause = 'o.date_due ASC, o.order_id ASC, oi.order_item_id ASC';
                break;
            case 'due_date_desc':
                $orderByClause = 'o.date_due DESC, o.order_id DESC, oi.order_item_id ASC';
                break;
            case 'order_date_asc':
                $orderByClause = 'o.date_created ASC, o.order_id ASC, oi.order_item_id ASC';
                break;
            case 'order_date_desc':
                $orderByClause = 'o.date_created DESC, o.order_id DESC, oi.order_item_id ASC';
                break;
            case 'customer_name':
                $orderByClause = 'c.full_name ASC, o.order_id ASC, oi.order_item_id ASC';
                break;
            case 'urgency':
            default:
                // For completed orders, default to most recent due date first
                $orderByClause = 'o.date_due DESC, o.order_id DESC, oi.order_item_id ASC';
                break;
        }

        $sql = "SELECT 
                    o.order_id,
                    o.date_created as order_date,
                    o.date_due,
                    o.order_status,
                    c.full_name as customer_name,
                    c.company_name,
                    'completed' as urgency_level,
                    oi.*
                FROM orders o
                JOIN order_items oi ON o.order_id = oi.order_id
                JOIN customers c ON o.customer_id = c.customer_id
                WHERE o.order_status = 'completed'
                    OR oi.order_item_status = 'completed'
                ORDER BY {$orderByClause}";
        
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $results = $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
            // Group by order_id to organize data
            $orders = [];
            foreach ($results as $item) {
                $orderId = $item->order_id;
                
                if (!isset($orders[$orderId])) {
                    $orders[$orderId] = [
                        'order_id' => $item->order_id,
                        'order_date' => $item->order_date,
                        'date_due' => $item->date_due,
                        'customer_name' => $item->customer_name,
                        'company_name' => $item->company_name,
                        'urgency_level' => $item->urgency_level,
                        'items' => []
                    ];
                }
                
                $orders[$orderId]['items'][] = $item;
            }
            
            return array_values($orders); // Return indexed array
        }
        return [];
    }
    
    // Personal Workspace System Methods
    
    
    /**
     * Advance an order item to the next status in the workflow
     */
    public function advanceStatus($userId)
    {
        // Check if status can be advanced
        if (!$this->canAdvanceStatus()) {
            return false;
        }
        
        $nextStatus = $this->getNextStatus();
        if (!$nextStatus) {
            return false;
        }
        
        // Update status
        $this->attributes['order_item_status'] = $nextStatus;
        $this->order_item_status = $nextStatus;
        
        // Set completion fields if reaching completed status
        if ($nextStatus === self::STATUS_COMPLETED) {
            $completedAt = date('Y-m-d H:i:s');
            $this->attributes['completed_by'] = $userId;
            $this->attributes['completed_at'] = $completedAt;
            $this->completed_by = $userId;
            $this->completed_at = $completedAt;
        }
        
        return $this->update();
    }
    
    
    /**
     * Get items by status for kanban workspace tabs
     */
    public function getItemsByStatus($status, $userId = null, $forCompleted = false)
    {
        $baseQuery = "SELECT oi.*, o.date_due, o.order_status, c.full_name as customer_name, c.company_name,
                          c.phone_number, o.payment_status,
                          CASE 
                              WHEN o.date_due < CURDATE() THEN 'overdue'
                              WHEN o.date_due = CURDATE() THEN 'due_today'
                              WHEN o.date_due <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 'due_soon'
                              WHEN o.date_due <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'rush'
                              ELSE 'normal'
                          END as urgency_level
                      FROM {$this->table} oi
                      JOIN orders o ON oi.order_id = o.order_id
                      JOIN customers c ON o.customer_id = c.customer_id
                      WHERE oi.order_item_status = :status
                          AND o.order_status NOT IN ('cancelled', 'on_hold')";
        
        $params = ['status' => $status];
        
        if ($userId && $forCompleted) {
            // For completed items - show items completed by specific user
            $baseQuery .= " AND oi.completed_by = :user_id";
            $params['user_id'] = $userId;
            $orderBy = " ORDER BY oi.completed_at DESC";
        } else {
            // For all other statuses - show ALL items (no user filtering)
            $orderBy = " ORDER BY FIELD(urgency_level, 'overdue', 'due_today', 'due_soon', 'rush', 'normal'), o.date_due ASC, oi.order_item_id ASC";
        }
        
        $sql = $baseQuery . $orderBy;
        $stmt = $this->db->query($sql, $params);
        
        if ($stmt) {
            $results = $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
            // Set additional properties from the JOIN
            foreach ($results as $item) {
                if (!isset($item->customer_name)) {
                    $item->customer_name = $item->full_name ?? 'Unknown Customer';
                }
            }
            return $results;
        }
        return [];
    }
    
    /**
     * Get all items at pending stage
     */
    public function getAllPendingItems()
    {
        return $this->getItemsByStatus(self::STATUS_PENDING);
    }
    
    /**
     * Get all items at artwork sent stage
     */
    public function getAllArtworkSent()
    {
        return $this->getItemsByStatus(self::STATUS_ARTWORK_SENT);
    }
    
    /**
     * Get all items at artwork approved stage
     */
    public function getAllArtworkApproved()
    {
        return $this->getItemsByStatus(self::STATUS_ARTWORK_APPROVED);
    }
    
    /**
     * Get all items at nesting/digitalization stage
     */
    public function getAllNestingDone()
    {
        return $this->getItemsByStatus(self::STATUS_NESTING_DONE);
    }
    
    /**
     * Get all order items completed by a specific user (for "My Completed" tab)
     */
    public function getUserCompleted($userId)
    {
        return $this->getItemsByStatus(self::STATUS_COMPLETED, $userId, true);
    }
    
}
