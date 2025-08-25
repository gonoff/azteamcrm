<?php

namespace App\Core;

use PDO;

abstract class Model
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $attributes = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->db->query($sql, ['id' => $id]);
        
        if ($stmt) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $this->attributes = $result;
                // Also set properties directly for compatibility
                foreach ($result as $key => $value) {
                    $this->$key = $value;
                }
                return $this;
            }
        }
        return null;
    }
    
    public function findAll($conditions = [], $orderBy = null, $limit = null)
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = :{$field}";
                $params[$field] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        $stmt = $this->db->query($sql, $params);
        
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_CLASS, static::class);
        }
        return [];
    }
    
    public function where($field, $operator = '=', $value = null)
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE {$field} {$operator} :value";
        $stmt = $this->db->query($sql, ['value' => $value]);
        
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_CLASS, static::class);
        }
        return [];
    }
    
    public function create($data)
    {
        $filteredData = $this->filterFillable($data);
        
        if (empty($filteredData)) {
            return false;
        }
        
        $fields = array_keys($filteredData);
        $placeholders = array_map(function($field) { return ":{$field}"; }, $fields);
        
        $sql = "INSERT INTO {$this->table} (" . implode(", ", $fields) . ") 
                VALUES (" . implode(", ", $placeholders) . ")";
        
        $stmt = $this->db->query($sql, $filteredData);
        
        if ($stmt) {
            $this->attributes[$this->primaryKey] = $this->db->lastInsertId();
            $this->attributes = array_merge($this->attributes, $filteredData);
            return $this;
        }
        return false;
    }
    
    public function update($data = [])
    {
        if (empty($data)) {
            $data = $this->attributes;
        }
        
        $filteredData = $this->filterFillable($data);
        
        // Debug logging for AJAX requests
        if (isset($_POST['ajax'])) {
            error_log('Model Update - Table: ' . $this->table);
            error_log('Model Update - Primary Key: ' . $this->primaryKey . ' = ' . ($this->attributes[$this->primaryKey] ?? 'NOT SET'));
            error_log('Model Update - Filtered Data: ' . json_encode($filteredData));
        }
        
        if (empty($filteredData) || !isset($this->attributes[$this->primaryKey])) {
            if (isset($_POST['ajax'])) {
                error_log('Model Update Failed - Empty filtered data or no primary key');
            }
            return false;
        }
        
        $setParts = [];
        foreach ($filteredData as $field => $value) {
            $setParts[] = "{$field} = :{$field}";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(", ", $setParts) . 
               " WHERE {$this->primaryKey} = :primary_key";
        
        if (isset($_POST['ajax'])) {
            error_log('Model Update - SQL: ' . $sql);
        }
        
        $filteredData['primary_key'] = $this->attributes[$this->primaryKey];
        
        $stmt = $this->db->query($sql, $filteredData);
        
        if ($stmt) {
            $this->attributes = array_merge($this->attributes, $filteredData);
            return true;
        }
        
        if (isset($_POST['ajax'])) {
            error_log('Model Update Failed - Database query returned false');
        }
        return false;
    }
    
    public function save($data = [])
    {
        if (!empty($data)) {
            $this->fill($data);
        }
        
        if (isset($this->attributes[$this->primaryKey])) {
            return $this->update();
        } else {
            return $this->create($this->attributes);
        }
    }
    
    public function delete()
    {
        if (!isset($this->attributes[$this->primaryKey])) {
            return false;
        }
        
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->query($sql, ['id' => $this->attributes[$this->primaryKey]]);
        
        return $stmt !== false;
    }
    
    public function count($conditions = [])
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = :{$field}";
                $params[$field] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }
        
        $stmt = $this->db->query($sql, $params);
        
        if ($stmt) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        }
        return 0;
    }
    
    protected function filterFillable($data)
    {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_filter($data, function($key) {
            return in_array($key, $this->fillable);
        }, ARRAY_FILTER_USE_KEY);
    }
    
    public function fill($data)
    {
        $filteredData = $this->filterFillable($data);
        $this->attributes = array_merge($this->attributes, $filteredData);
        return $this;
    }
    
    public function __get($name)
    {
        return $this->attributes[$name] ?? null;
    }
    
    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }
    
    public function __isset($name)
    {
        return isset($this->attributes[$name]);
    }
    
    public function toArray()
    {
        return $this->attributes;
    }
    
    /**
     * Pagination Support Methods
     */
    public function paginate($page = 1, $perPage = 20, $conditions = [], $orderBy = null)
    {
        $page = max(1, intval($page));
        $perPage = max(1, min(100, intval($perPage))); // Limit max per page to 100
        $offset = ($page - 1) * $perPage;
        
        // Build base query
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = :{$field}";
                $params[$field] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        
        $stmt = $this->db->query($sql, $params);
        
        $items = [];
        if ($stmt) {
            $items = $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
        }
        
        // Get total count for pagination info
        $totalCount = $this->count($conditions);
        $totalPages = ceil($totalCount / $perPage);
        
        return [
            'data' => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalCount,
                'total_pages' => $totalPages,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages,
                'previous_page' => $page > 1 ? $page - 1 : null,
                'next_page' => $page < $totalPages ? $page + 1 : null,
                'start_item' => $totalCount > 0 ? $offset + 1 : 0,
                'end_item' => min($offset + $perPage, $totalCount)
            ]
        ];
    }
    
    public function searchAndPaginate($searchTerm, $searchFields, $page = 1, $perPage = 20, $conditions = [], $orderBy = null)
    {
        $page = max(1, intval($page));
        $perPage = max(1, min(100, intval($perPage)));
        $offset = ($page - 1) * $perPage;
        
        // Build search query
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        $whereConditions = [];
        
        // Add regular conditions
        if (!empty($conditions)) {
            foreach ($conditions as $field => $value) {
                $whereConditions[] = "{$field} = :{$field}";
                $params[$field] = $value;
            }
        }
        
        // Add search conditions
        if (!empty($searchTerm) && !empty($searchFields)) {
            $searchConditions = [];
            foreach ($searchFields as $field) {
                $searchConditions[] = "{$field} LIKE :search_{$field}";
                $params["search_{$field}"] = '%' . $searchTerm . '%';
            }
            $whereConditions[] = '(' . implode(' OR ', $searchConditions) . ')';
        }
        
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        
        $stmt = $this->db->query($sql, $params);
        
        $items = [];
        if ($stmt) {
            $items = $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
        }
        
        // Get total count with search
        $countSql = "SELECT COUNT(*) as total FROM {$this->table}";
        if (!empty($whereConditions)) {
            $countSql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $countStmt = $this->db->query($countSql, $params);
        $totalCount = 0;
        if ($countStmt) {
            $result = $countStmt->fetch(\PDO::FETCH_ASSOC);
            $totalCount = $result['total'] ?? 0;
        }
        
        $totalPages = ceil($totalCount / $perPage);
        
        return [
            'data' => $items,
            'search_term' => $searchTerm,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalCount,
                'total_pages' => $totalPages,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages,
                'previous_page' => $page > 1 ? $page - 1 : null,
                'next_page' => $page < $totalPages ? $page + 1 : null,
                'start_item' => $totalCount > 0 ? $offset + 1 : 0,
                'end_item' => min($offset + $perPage, $totalCount)
            ]
        ];
    }
}