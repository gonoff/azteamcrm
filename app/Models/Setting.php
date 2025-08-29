<?php

namespace App\Models;

use App\Core\Model;

class Setting extends Model
{
    protected $table = 'settings';
    protected $primaryKey = 'setting_id';
    protected $fillable = [
        'setting_key', 'setting_value', 'setting_type', 'category',
        'display_name', 'description', 'validation_rules', 'default_value',
        'requires_restart', 'modified_by'
    ];

    /**
     * Get a setting value by key with automatic type casting
     */
    public static function getValue($key, $defaultValue = null)
    {
        $instance = new static();
        $results = $instance->where('setting_key', '=', $key);
        $setting = !empty($results) ? $results[0] : null;
        
        if (!$setting) {
            return $defaultValue;
        }
        
        return static::castValue($setting->setting_value, $setting->setting_type);
    }
    
    /**
     * Set a setting value with automatic type validation
     */
    public static function setValue($key, $value, $modifiedBy = null)
    {
        $instance = new static();
        $results = $instance->where('setting_key', '=', $key);
        $setting = !empty($results) ? $results[0] : null;
        
        if (!$setting) {
            return false; // Setting doesn't exist
        }
        
        // Validate the value
        if (!static::validateValue($value, $setting->setting_type, $setting->validation_rules)) {
            return false;
        }
        
        // Convert value to string for storage
        $stringValue = static::valueToString($value, $setting->setting_type);
        
        $setting->setting_value = $stringValue;
        if ($modifiedBy) {
            $setting->modified_by = $modifiedBy;
        }
        
        return $setting->update();
    }
    
    /**
     * Get all settings by category
     */
    public static function getByCategory($category)
    {
        $instance = new static();
        $settings = $instance->findAll(['category' => $category], 'display_name ASC');
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->setting_key] = [
                'value' => static::castValue($setting->setting_value, $setting->setting_type),
                'display_name' => $setting->display_name,
                'description' => $setting->description,
                'type' => $setting->setting_type,
                'validation_rules' => json_decode($setting->validation_rules, true),
                'default_value' => static::castValue($setting->default_value, $setting->setting_type),
                'requires_restart' => (bool)$setting->requires_restart
            ];
        }
        
        return $result;
    }
    
    /**
     * Get all categories
     */
    public static function getCategories()
    {
        $instance = new static();
        $sql = "SELECT DISTINCT category, COUNT(*) as setting_count 
                FROM {$instance->table} 
                GROUP BY category 
                ORDER BY category";
        
        $stmt = $instance->db->query($sql);
        
        if ($stmt) {
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        return [];
    }
    
    /**
     * Reset a setting to its default value
     */
    public static function resetToDefault($key, $modifiedBy = null)
    {
        $instance = new static();
        $results = $instance->where('setting_key', '=', $key);
        $setting = !empty($results) ? $results[0] : null;
        
        if (!$setting || !$setting->default_value) {
            return false;
        }
        
        $setting->setting_value = $setting->default_value;
        if ($modifiedBy) {
            $setting->modified_by = $modifiedBy;
        }
        
        return $setting->update();
    }
    
    /**
     * Cast a string value to the appropriate PHP type
     */
    protected static function castValue($value, $type)
    {
        switch ($type) {
            case 'integer':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'boolean':
                return $value === '1' || $value === 'true' || $value === true;
            case 'json':
                return json_decode($value, true);
            case 'string':
            default:
                return (string)$value;
        }
    }
    
    /**
     * Convert a PHP value to string for database storage
     */
    protected static function valueToString($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return $value ? '1' : '0';
            case 'json':
                return json_encode($value);
            case 'integer':
            case 'float':
            case 'string':
            default:
                return (string)$value;
        }
    }
    
    /**
     * Validate a value against its type and validation rules
     */
    protected static function validateValue($value, $type, $validationRulesJson)
    {
        $rules = json_decode($validationRulesJson, true) ?: [];
        
        // Type validation
        switch ($type) {
            case 'integer':
                if (!is_numeric($value) || (int)$value != $value) {
                    return false;
                }
                $value = (int)$value;
                break;
            case 'float':
                if (!is_numeric($value)) {
                    return false;
                }
                $value = (float)$value;
                break;
            case 'boolean':
                // Accept various boolean representations
                if (!in_array($value, [true, false, '1', '0', 'true', 'false', 1, 0], true)) {
                    return false;
                }
                break;
            case 'json':
                if (is_string($value)) {
                    json_decode($value);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return false;
                    }
                }
                break;
        }
        
        // Rule-based validation
        if (isset($rules['required']) && $rules['required'] && empty($value)) {
            return false;
        }
        
        if (isset($rules['min']) && is_numeric($value) && $value < $rules['min']) {
            return false;
        }
        
        if (isset($rules['max']) && is_numeric($value) && $value > $rules['max']) {
            return false;
        }
        
        if (isset($rules['in']) && is_array($rules['in']) && !in_array($value, $rules['in'])) {
            return false;
        }
        
        if (isset($rules['regex']) && is_string($value) && !preg_match($rules['regex'], $value)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get settings that require system restart
     */
    public static function getRestartRequired()
    {
        $instance = new static();
        return $instance->findAll(['requires_restart' => 1]);
    }
    
    /**
     * Bulk update multiple settings
     */
    public static function bulkUpdate($settings, $modifiedBy = null)
    {
        $instance = new static();
        $errors = [];
        $updated = 0;
        
        foreach ($settings as $key => $value) {
            if (static::setValue($key, $value, $modifiedBy)) {
                $updated++;
            } else {
                $errors[] = $key;
            }
        }
        
        return [
            'updated' => $updated,
            'errors' => $errors,
            'total' => count($settings)
        ];
    }
    
    /**
     * Export all settings to array format
     */
    public static function exportAll()
    {
        $instance = new static();
        $settings = $instance->findAll();
        
        $export = [];
        foreach ($settings as $setting) {
            $export[$setting->setting_key] = [
                'value' => static::castValue($setting->setting_value, $setting->setting_type),
                'type' => $setting->setting_type,
                'category' => $setting->category,
                'display_name' => $setting->display_name,
                'description' => $setting->description,
                'default_value' => static::castValue($setting->default_value, $setting->setting_type)
            ];
        }
        
        return $export;
    }
    
    /**
     * Get the user who last modified this setting
     */
    public function getModifiedByUser()
    {
        if (!$this->modified_by) {
            return null;
        }
        
        $user = new User();
        return $user->find($this->modified_by);
    }
}