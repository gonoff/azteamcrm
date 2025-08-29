<?php

namespace App\Services;

use App\Models\Setting;

class SettingsService
{
    private static $cache = [];
    private static $cacheLoaded = false;
    
    /**
     * Get a setting value by key with optional default
     * Supports dot notation for nested access (e.g., 'business.ct_tax_rate')
     * 
     * @param string $key Setting key
     * @param mixed $default Default value if setting not found
     * @return mixed Setting value cast to appropriate type
     */
    public static function get($key, $default = null)
    {
        // Load cache if not already loaded
        if (!static::$cacheLoaded) {
            static::loadCache();
        }
        
        // Check if setting exists in cache
        if (array_key_exists($key, static::$cache)) {
            return static::$cache[$key];
        }
        
        // Try to get from database
        $value = Setting::getValue($key, $default);
        
        // Cache the result (even if it's the default value)
        static::$cache[$key] = $value;
        
        return $value;
    }
    
    /**
     * Set a setting value
     * 
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @param int|null $userId User making the change
     * @return bool Success status
     */
    public static function set($key, $value, $userId = null)
    {
        $success = Setting::setValue($key, $value, $userId);
        
        if ($success) {
            // Update cache
            static::$cache[$key] = $value;
        }
        
        return $success;
    }
    
    /**
     * Get all settings for a specific category
     * 
     * @param string $category Category name
     * @return array Settings array
     */
    public static function getCategory($category)
    {
        return Setting::getByCategory($category);
    }
    
    /**
     * Get all available categories
     * 
     * @return array Category list with counts
     */
    public static function getCategories()
    {
        return Setting::getCategories();
    }
    
    /**
     * Reset a setting to its default value
     * 
     * @param string $key Setting key
     * @param int|null $userId User making the change
     * @return bool Success status
     */
    public static function resetToDefault($key, $userId = null)
    {
        $success = Setting::resetToDefault($key, $userId);
        
        if ($success) {
            // Remove from cache so it gets reloaded
            unset(static::$cache[$key]);
        }
        
        return $success;
    }
    
    /**
     * Bulk update multiple settings
     * 
     * @param array $settings Key-value pairs of settings
     * @param int|null $userId User making the changes
     * @return array Update results
     */
    public static function bulkUpdate($settings, $userId = null)
    {
        $result = Setting::bulkUpdate($settings, $userId);
        
        // Update cache for successful updates
        foreach ($settings as $key => $value) {
            if (!in_array($key, $result['errors'])) {
                static::$cache[$key] = $value;
            }
        }
        
        return $result;
    }
    
    /**
     * Clear the settings cache
     */
    public static function clearCache()
    {
        static::$cache = [];
        static::$cacheLoaded = false;
    }
    
    /**
     * Load all settings into cache for performance
     */
    private static function loadCache()
    {
        $settings = Setting::exportAll();
        
        foreach ($settings as $key => $data) {
            static::$cache[$key] = $data['value'];
        }
        
        static::$cacheLoaded = true;
    }
    
    /**
     * Get multiple settings at once
     * 
     * @param array $keys Array of setting keys
     * @return array Key-value pairs of settings
     */
    public static function getMultiple($keys)
    {
        $result = [];
        
        foreach ($keys as $key) {
            $result[$key] = static::get($key);
        }
        
        return $result;
    }
    
    /**
     * Check if a setting exists
     * 
     * @param string $key Setting key
     * @return bool
     */
    public static function exists($key)
    {
        if (!static::$cacheLoaded) {
            static::loadCache();
        }
        
        return array_key_exists($key, static::$cache);
    }
    
    /**
     * Get settings that require restart
     * 
     * @return array Settings requiring restart
     */
    public static function getRestartRequired()
    {
        return Setting::getRestartRequired();
    }
    
    /**
     * Export all settings
     * 
     * @return array All settings data
     */
    public static function exportAll()
    {
        return Setting::exportAll();
    }
    
    // Convenience methods for common settings
    
    /**
     * Get Connecticut tax rate
     * 
     * @return float Tax rate as decimal (e.g., 0.0635 for 6.35%)
     */
    public static function getCtTaxRate()
    {
        return static::get('business.ct_tax_rate', 0.0635);
    }
    
    /**
     * Get rush order threshold in days
     * 
     * @return int Number of days
     */
    public static function getRushOrderThreshold()
    {
        return static::get('business.rush_order_threshold_days', 7);
    }
    
    /**
     * Get "due soon" threshold in days
     * 
     * @return int Number of days
     */
    public static function getDueSoonThreshold()
    {
        return static::get('business.due_soon_threshold_days', 3);
    }
    
    /**
     * Get session timeout in seconds
     * 
     * @return int Timeout in seconds
     */
    public static function getSessionTimeout()
    {
        return static::get('security.session_timeout_seconds', 1800);
    }
    
    /**
     * Get default pagination size
     * 
     * @return int Items per page
     */
    public static function getDefaultPageSize()
    {
        return static::get('ui.pagination.default_page_size', 20);
    }
    
    /**
     * Get production dashboard page size
     * 
     * @return int Items per page for production dashboard
     */
    public static function getProductionPageSize()
    {
        return static::get('ui.pagination.production_page_size', 50);
    }
    
    /**
     * Get maximum page size
     * 
     * @return int Maximum items per page
     */
    public static function getMaxPageSize()
    {
        return static::get('ui.pagination.max_page_size', 100);
    }
    
    /**
     * Get search debounce delay in milliseconds
     * 
     * @return int Delay in milliseconds
     */
    public static function getSearchDebounce()
    {
        return static::get('ui.timing.search_debounce_ms', 300);
    }
    
    /**
     * Get alert auto-dismiss timeout in milliseconds
     * 
     * @return int Timeout in milliseconds
     */
    public static function getAlertTimeout()
    {
        return static::get('ui.timing.alert_auto_dismiss_ms', 5000);
    }
    
    /**
     * Get dashboard recent orders limit
     * 
     * @return int Number of recent orders to show
     */
    public static function getDashboardRecentOrdersLimit()
    {
        return static::get('ui.dashboard.recent_orders_limit', 10);
    }
    
    /**
     * Get dashboard urgent orders limit
     * 
     * @return int Number of urgent orders to show
     */
    public static function getDashboardUrgentOrdersLimit()
    {
        return static::get('ui.dashboard.urgent_orders_limit', 10);
    }
    
    /**
     * Get maximum file upload size in bytes
     * 
     * @return int Size in bytes
     */
    public static function getMaxUploadSize()
    {
        return static::get('system.max_upload_size_bytes', 5242880); // 5MB
    }
    
    /**
     * Get auto-refresh interval for supplier tracking in milliseconds
     * 
     * @return int Interval in milliseconds
     */
    public static function getSupplierTrackingRefreshInterval()
    {
        return static::get('ui.timing.supplier_tracking_refresh_ms', 30000); // 30 seconds
    }
    
    /**
     * Get expected phone number length for formatting
     * 
     * @return int Phone number length (default 10 for US format)
     */
    public static function getPhoneNumberLength()
    {
        return static::get('business.phone_number_length', 10);
    }
    
    /**
     * Get default limit for customer search results
     * 
     * @return int Search results limit
     */
    public static function getCustomerSearchLimit()
    {
        return static::get('ui.limits.customer_search_limit', 20);
    }
}