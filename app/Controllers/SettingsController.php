<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\SettingsService;
use App\Models\Setting;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
        $this->requireRole('administrator'); // Only administrators can access settings
    }
    
    /**
     * Display settings management interface
     */
    public function index()
    {
        // Ensure access control settings exist so the category appears
        $this->ensureAccessSettingsSeed();
        $category = $_GET['category'] ?? 'business'; // Default to business category
        
        // Validate category
        $categories = SettingsService::getCategories();
        $validCategories = array_column($categories, 'category');
        
        if (!in_array($category, $validCategories)) {
            $category = 'business';
        }
        
        // Get settings for the selected category
        $settings = SettingsService::getCategory($category);
        
        // Get settings that require restart
        $restartRequired = SettingsService::getRestartRequired();
        $restartRequiredKeys = array_column($restartRequired, 'setting_key');
        
        $this->view('settings/index', [
            'categories' => $categories,
            'currentCategory' => $category,
            'settings' => $settings,
            'restartRequiredKeys' => $restartRequiredKeys,
            'csrf_token' => $this->csrf()
        ]);
    }

    private function ensureAccessSettingsSeed(): void
    {
        // Only seed when logged in as admin and settings are missing
        try {
            $adminId = $_SESSION['user_id'] ?? null;

            // Seed production_team allowed features
            if (!\App\Services\SettingsService::exists('access.roles.production_team.allowed_features')) {
                $s = new \App\Models\Setting();
                $s->create([
                    'setting_key' => 'access.roles.production_team.allowed_features',
                    'setting_value' => json_encode(['workspace', 'supplier_tracking', 'profile']),
                    'setting_type' => 'json',
                    'category' => 'access',
                    'display_name' => 'Production Team Allowed Features',
                    'description' => 'Controls which features the production team can access.',
                    'validation_rules' => json_encode([]),
                    'default_value' => json_encode(['workspace', 'supplier_tracking', 'profile']),
                    'requires_restart' => 0,
                    'modified_by' => $adminId
                ]);
            }

            // Seed administrator allowed features (mostly informational; admins bypass checks)
            if (!\App\Services\SettingsService::exists('access.roles.administrator.allowed_features')) {
                $s = new \App\Models\Setting();
                $s->create([
                    'setting_key' => 'access.roles.administrator.allowed_features',
                    'setting_value' => json_encode(['dashboard','customers','orders','production','supplier_tracking','workspace','users','settings','profile']),
                    'setting_type' => 'json',
                    'category' => 'access',
                    'display_name' => 'Administrator Allowed Features',
                    'description' => 'Features available to administrators (admins have full access).',
                    'validation_rules' => json_encode([]),
                    'default_value' => json_encode(['dashboard','customers','orders','production','supplier_tracking','workspace','users','settings','profile']),
                    'requires_restart' => 0,
                    'modified_by' => $adminId
                ]);
            }

            // Optional default role template
            if (!\App\Services\SettingsService::exists('access.roles.default.allowed_features')) {
                $s = new \App\Models\Setting();
                $s->create([
                    'setting_key' => 'access.roles.default.allowed_features',
                    'setting_value' => json_encode(['profile']),
                    'setting_type' => 'json',
                    'category' => 'access',
                    'display_name' => 'Default Role Allowed Features',
                    'description' => 'Fallback features for any unspecified roles.',
                    'validation_rules' => json_encode([]),
                    'default_value' => json_encode(['profile']),
                    'requires_restart' => 0,
                    'modified_by' => $adminId
                ]);
            }
        } catch (\Throwable $e) {
            // Non-fatal: if DB not writable or table missing, just skip seeding
            error_log('Settings access seed skipped: ' . $e->getMessage());
        }
    }
    
    /**
     * Update settings for a category
     */
    public function update()
    {
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        $this->verifyCsrf();
        
        $category = $_POST['category'] ?? '';
        $settings = $_POST['settings'] ?? [];

        // Support JSON payload from client (backward compatibility)
        if (is_string($settings)) {
            $decoded = json_decode($settings, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $settings = $decoded;
            }
        }
        
        if (empty($category) || empty($settings)) {
            $this->json(['success' => false, 'message' => 'Missing category or settings data']);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $result = SettingsService::bulkUpdate($settings, $userId);
        
        if ($result['errors']) {
            $this->json([
                'success' => false,
                'message' => "Updated {$result['updated']} of {$result['total']} settings. Errors: " . implode(', ', $result['errors'])
            ]);
        } else {
            $this->json([
                'success' => true,
                'message' => "Successfully updated {$result['updated']} settings"
            ]);
        }
    }
    
    /**
     * Reset a single setting to default
     */
    public function resetSetting()
    {
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        $this->verifyCsrf();
        
        $settingKey = $_POST['setting_key'] ?? '';
        
        if (empty($settingKey)) {
            $this->json(['success' => false, 'message' => 'Setting key is required']);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $success = SettingsService::resetToDefault($settingKey, $userId);
        
        if ($success) {
            $newValue = SettingsService::get($settingKey);
            $this->json([
                'success' => true,
                'message' => 'Setting reset to default value',
                'new_value' => $newValue
            ]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to reset setting']);
        }
    }
    
    /**
     * Reset all settings in a category to defaults
     */
    public function resetCategory()
    {
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        $this->verifyCsrf();
        
        $category = $_POST['category'] ?? '';
        
        if (empty($category)) {
            $this->json(['success' => false, 'message' => 'Category is required']);
            return;
        }
        
        $categorySettings = SettingsService::getCategory($category);
        $userId = $_SESSION['user_id'];
        $resetCount = 0;
        $errors = [];
        
        foreach ($categorySettings as $key => $data) {
            if (SettingsService::resetToDefault($key, $userId)) {
                $resetCount++;
            } else {
                $errors[] = $key;
            }
        }
        
        if ($errors) {
            $this->json([
                'success' => false,
                'message' => "Reset {$resetCount} settings. Errors: " . implode(', ', $errors)
            ]);
        } else {
            $this->json([
                'success' => true,
                'message' => "Successfully reset {$resetCount} settings to defaults"
            ]);
        }
    }
    
    /**
     * Export settings to JSON file
     */
    public function export()
    {
        $format = $_GET['format'] ?? 'json';
        $category = $_GET['category'] ?? null;
        
        if ($category) {
            // Export specific category
            $data = SettingsService::getCategory($category);
            $filename = "settings_{$category}_" . date('Y-m-d_H-i-s');
        } else {
            // Export all settings
            $data = SettingsService::exportAll();
            $filename = "settings_all_" . date('Y-m-d_H-i-s');
        }
        
        if ($format === 'json') {
            header('Content-Type: application/json');
            header("Content-Disposition: attachment; filename=\"{$filename}.json\"");
            echo json_encode($data, JSON_PRETTY_PRINT);
        } else {
            // PHP array format
            header('Content-Type: text/plain');
            header("Content-Disposition: attachment; filename=\"{$filename}.php\"");
            echo "<?php\n\nreturn " . var_export($data, true) . ";\n";
        }
        
        exit;
    }
    
    /**
     * Import settings from uploaded file
     */
    public function import()
    {
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        $this->verifyCsrf();
        
        if (!isset($_FILES['settings_file']) || $_FILES['settings_file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'No file uploaded or upload error']);
            return;
        }
        
        $filePath = $_FILES['settings_file']['tmp_name'];
        $fileContent = file_get_contents($filePath);
        
        // Try to decode as JSON
        $data = json_decode($fileContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->json(['success' => false, 'message' => 'Invalid JSON file']);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $importCount = 0;
        $errors = [];
        
        foreach ($data as $key => $settingData) {
            // Handle both formats: direct key-value and structured data
            $value = isset($settingData['value']) ? $settingData['value'] : $settingData;
            
            if (SettingsService::set($key, $value, $userId)) {
                $importCount++;
            } else {
                $errors[] = $key;
            }
        }
        
        if ($errors) {
            $this->json([
                'success' => false,
                'message' => "Imported {$importCount} settings. Errors: " . implode(', ', $errors)
            ]);
        } else {
            $this->json([
                'success' => true,
                'message' => "Successfully imported {$importCount} settings"
            ]);
        }
    }
    
    /**
     * Get setting value via AJAX
     */
    public function getValue()
    {
        $key = $_GET['key'] ?? '';
        
        if (empty($key)) {
            $this->json(['success' => false, 'message' => 'Setting key is required']);
            return;
        }
        
        if (!SettingsService::exists($key)) {
            $this->json(['success' => false, 'message' => 'Setting not found']);
            return;
        }
        
        $value = SettingsService::get($key);
        
        $this->json([
            'success' => true,
            'key' => $key,
            'value' => $value
        ]);
    }
    
    /**
     * Set single setting value via AJAX
     */
    public function setValue()
    {
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        $this->verifyCsrf();
        
        $key = $_POST['key'] ?? '';
        $value = $_POST['value'] ?? '';
        
        if (empty($key)) {
            $this->json(['success' => false, 'message' => 'Setting key is required']);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $success = SettingsService::set($key, $value, $userId);
        
        if ($success) {
            $this->json([
                'success' => true,
                'message' => 'Setting updated successfully',
                'key' => $key,
                'value' => SettingsService::get($key) // Return the properly cast value
            ]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to update setting']);
        }
    }
    
    /**
     * Clear settings cache
     */
    public function clearCache()
    {
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        $this->verifyCsrf();
        
        SettingsService::clearCache();
        
        $this->json([
            'success' => true,
            'message' => 'Settings cache cleared successfully'
        ]);
    }
    
    /**
     * Check which settings require system restart
     */
    public function checkRestartRequired()
    {
        $restartRequired = SettingsService::getRestartRequired();
        
        $this->json([
            'success' => true,
            'restart_required' => !empty($restartRequired),
            'settings' => array_column($restartRequired, 'setting_key')
        ]);
    }
}
