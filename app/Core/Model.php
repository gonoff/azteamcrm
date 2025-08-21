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
        
        if (empty($filteredData) || !isset($this->attributes[$this->primaryKey])) {
            return false;
        }
        
        $setParts = [];
        foreach ($filteredData as $field => $value) {
            $setParts[] = "{$field} = :{$field}";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(", ", $setParts) . 
               " WHERE {$this->primaryKey} = :primary_key";
        
        $filteredData['primary_key'] = $this->attributes[$this->primaryKey];
        
        $stmt = $this->db->query($sql, $filteredData);
        
        if ($stmt) {
            $this->attributes = array_merge($this->attributes, $filteredData);
            return true;
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
    
    public function toArray()
    {
        return $this->attributes;
    }
}