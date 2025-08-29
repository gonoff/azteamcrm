<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">
        <i class="bi bi-gear"></i> System Settings
    </h1>
    <div class="d-flex gap-2">
        <div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle" type="button" id="settingsActions" data-bs-toggle="dropdown">
                <i class="bi bi-three-dots"></i> Actions
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" onclick="exportSettings()">
                    <i class="bi bi-download"></i> Export Settings
                </a></li>
                <li><a class="dropdown-item" href="#" onclick="showImportModal()">
                    <i class="bi bi-upload"></i> Import Settings
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" onclick="resetCategory()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset Category
                </a></li>
                <li><a class="dropdown-item" href="#" onclick="clearCache()">
                    <i class="bi bi-trash3"></i> Clear Cache
                </a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Category Tabs -->
<ul class="nav nav-tabs mb-4" id="categoryTabs">
    <?php foreach ($categories as $cat): ?>
        <li class="nav-item">
            <a class="nav-link <?= $cat['category'] === $currentCategory ? 'active' : '' ?>" 
               href="/azteamcrm/settings?category=<?= $cat['category'] ?>">
                <i class="bi bi-<?= getCategoryIcon($cat['category']) ?>"></i>
                <?= ucwords(str_replace('_', ' ', $cat['category'])) ?>
                <span class="badge bg-secondary ms-1"><?= $cat['setting_count'] ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<!-- Settings Form -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-<?= getCategoryIcon($currentCategory) ?>"></i>
            <?= ucwords(str_replace('_', ' ', $currentCategory)) ?> Settings
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($settings)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                No settings found for this category.
            </div>
        <?php else: ?>
            <form id="settingsForm" onsubmit="saveSettings(event)">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="category" value="<?= $currentCategory ?>">
                
                <?php foreach ($settings as $key => $setting): ?>
                    <div class="mb-4 setting-item" data-setting-key="<?= $key ?>">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">
                                    <?= htmlspecialchars($setting['display_name']) ?>
                                    <?php if (in_array($key, $restartRequiredKeys)): ?>
                                        <span class="badge bg-warning text-dark ms-1" title="Requires system restart">
                                            <i class="bi bi-exclamation-triangle"></i> Restart Required
                                        </span>
                                    <?php endif; ?>
                                </label>
                                <?php if ($setting['description']): ?>
                                    <small class="text-muted d-block">
                                        <?= htmlspecialchars($setting['description']) ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <?= renderSettingInput($key, $setting) ?>
                            </div>
                            <div class="col-md-2 d-flex align-items-center">
                                <button type="button" class="btn btn-outline-secondary btn-sm me-1" 
                                        onclick="resetSetting('<?= $key ?>')"
                                        title="Reset to default">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                                <?php if ($setting['default_value'] !== null): ?>
                                    <small class="text-muted">
                                        Default: <code><?= htmlspecialchars(formatDefaultValue($setting['default_value'], $setting['type'])) ?></code>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($setting['validation_rules']): ?>
                            <div class="row mt-1">
                                <div class="col-md-4"></div>
                                <div class="col-md-8">
                                    <small class="text-info">
                                        <?= formatValidationRules($setting['validation_rules']) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <hr>
                <?php endforeach; ?>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Save Settings
                    </button>
                    <button type="button" class="btn btn-outline-danger" onclick="resetCategory()">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset All to Defaults
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="importForm" onsubmit="importSettings(event)">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <div class="mb-3">
                        <label for="settingsFile" class="form-label">Select Settings File</label>
                        <input type="file" class="form-control" id="settingsFile" name="settings_file" 
                               accept=".json" required>
                        <div class="form-text">Upload a JSON file containing settings data.</div>
                    </div>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Warning:</strong> Importing settings will overwrite existing values. 
                        Consider exporting current settings as backup first.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Import Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let isFormDirty = false;

document.addEventListener('DOMContentLoaded', function() {
    // Track form changes
    const form = document.getElementById('settingsForm');
    if (form) {
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                isFormDirty = true;
            });
        });
    }
    
    // Warn before leaving if form is dirty
    window.addEventListener('beforeunload', function(e) {
        if (isFormDirty) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
        }
    });
});

function saveSettings(event) {
    event.preventDefault();
    
    const form = document.getElementById('settingsForm');
    const formData = new FormData(form);
    
    // Convert form data to settings object
    const settings = {};
    const settingItems = document.querySelectorAll('.setting-item');
    
    settingItems.forEach(item => {
        const key = item.dataset.settingKey;
        const input = item.querySelector('input, select, textarea');
        
        if (input) {
            let value = input.value;
            
            // Handle different input types
            if (input.type === 'checkbox') {
                value = input.checked;
            } else if (input.type === 'number') {
                value = input.step && input.step.includes('.') ? parseFloat(value) : parseInt(value);
            }
            
            settings[key] = value;
        }
    });
    
    // Add settings to form data
    formData.append('settings', JSON.stringify(settings));
    
    fetch('/azteamcrm/settings/update', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            isFormDirty = false;
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Network error: Unable to save settings');
    });
}

function resetSetting(key) {
    if (!confirm('Are you sure you want to reset this setting to its default value?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('csrf_token', '<?= $csrf_token ?>');
    formData.append('setting_key', key);
    
    fetch('/azteamcrm/settings/reset-setting', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            
            // Update the input value in the UI
            const settingItem = document.querySelector(`[data-setting-key="${key}"]`);
            const input = settingItem.querySelector('input, select, textarea');
            
            if (input) {
                if (input.type === 'checkbox') {
                    input.checked = data.new_value;
                } else {
                    input.value = data.new_value;
                }
            }
            
            isFormDirty = false;
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Network error: Unable to reset setting');
    });
}

function resetCategory() {
    if (!confirm(`Are you sure you want to reset all ${document.querySelector('.nav-link.active').textContent.trim()} settings to their default values?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('csrf_token', '<?= $csrf_token ?>');
    formData.append('category', '<?= $currentCategory ?>');
    
    fetch('/azteamcrm/settings/reset-category', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            // Reload page to show reset values
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Network error: Unable to reset category');
    });
}

function exportSettings() {
    const category = '<?= $currentCategory ?>';
    const url = `/azteamcrm/settings/export?format=json&category=${category}`;
    window.open(url, '_blank');
}

function showImportModal() {
    const modal = new bootstrap.Modal(document.getElementById('importModal'));
    modal.show();
}

function importSettings(event) {
    event.preventDefault();
    
    const form = document.getElementById('importForm');
    const formData = new FormData(form);
    
    fetch('/azteamcrm/settings/import', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            
            // Close modal and reload page
            const modal = bootstrap.Modal.getInstance(document.getElementById('importModal'));
            modal.hide();
            
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Network error: Unable to import settings');
    });
}

function clearCache() {
    if (!confirm('Are you sure you want to clear the settings cache?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('csrf_token', '<?= $csrf_token ?>');
    
    fetch('/azteamcrm/settings/clear-cache', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Network error: Unable to clear cache');
    });
}

function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Remove existing alerts
    document.querySelectorAll('.alert:not(.alert-warning)').forEach(alert => {
        if (!alert.closest('.modal')) {
            alert.remove();
        }
    });
    
    // Add new alert after the header
    document.querySelector('h1').insertAdjacentHTML('afterend', alertHtml);
}
</script>

<?php
// Helper functions for rendering

function getCategoryIcon($category) {
    $icons = [
        'business' => 'briefcase',
        'ui' => 'layout-sidebar',
        'security' => 'shield-check',
        'performance' => 'speedometer2',
        'theme' => 'palette',
        'system' => 'cpu'
    ];
    
    return $icons[$category] ?? 'gear';
}

function renderSettingInput($key, $setting) {
    $value = $setting['value'];
    $type = $setting['type'];
    $rules = $setting['validation_rules'] ?? [];
    
    $inputId = 'setting_' . str_replace('.', '_', $key);
    $inputName = "settings[{$key}]";
    
    switch ($type) {
        case 'boolean':
            $checked = $value ? 'checked' : '';
            return "<div class=\"form-check form-switch\">
                        <input class=\"form-check-input\" type=\"checkbox\" id=\"{$inputId}\" name=\"{$inputName}\" {$checked}>
                        <label class=\"form-check-label\" for=\"{$inputId}\">
                            " . ($value ? 'Enabled' : 'Disabled') . "
                        </label>
                    </div>";
        
        case 'integer':
            $min = isset($rules['min']) ? "min=\"{$rules['min']}\"" : '';
            $max = isset($rules['max']) ? "max=\"{$rules['max']}\"" : '';
            return "<input type=\"number\" class=\"form-control\" id=\"{$inputId}\" name=\"{$inputName}\" value=\"{$value}\" {$min} {$max}>";
        
        case 'float':
            $min = isset($rules['min']) ? "min=\"{$rules['min']}\"" : '';
            $max = isset($rules['max']) ? "max=\"{$rules['max']}\"" : '';
            $step = isset($rules['step']) ? "step=\"{$rules['step']}\"" : 'step="0.0001"';
            return "<input type=\"number\" class=\"form-control\" id=\"{$inputId}\" name=\"{$inputName}\" value=\"{$value}\" {$min} {$max} {$step}>";
        
        case 'json':
            $jsonValue = is_string($value) ? $value : json_encode($value, JSON_PRETTY_PRINT);
            return "<textarea class=\"form-control font-monospace\" id=\"{$inputId}\" name=\"{$inputName}\" rows=\"4\">" . htmlspecialchars($jsonValue) . "</textarea>";
        
        case 'string':
        default:
            if (isset($rules['in']) && is_array($rules['in'])) {
                // Dropdown for predefined options
                $options = '';
                foreach ($rules['in'] as $option) {
                    $selected = $option === $value ? 'selected' : '';
                    $options .= "<option value=\"{$option}\" {$selected}>" . ucwords(str_replace('_', ' ', $option)) . "</option>";
                }
                return "<select class=\"form-select\" id=\"{$inputId}\" name=\"{$inputName}\">{$options}</select>";
            } else {
                // Regular text input
                return "<input type=\"text\" class=\"form-control\" id=\"{$inputId}\" name=\"{$inputName}\" value=\"" . htmlspecialchars($value) . "\">";
            }
    }
}

function formatDefaultValue($value, $type) {
    switch ($type) {
        case 'boolean':
            return $value ? 'true' : 'false';
        case 'json':
            return is_string($value) ? $value : json_encode($value);
        default:
            return $value;
    }
}

function formatValidationRules($rules) {
    if (!$rules) return '';
    
    $parts = [];
    
    if (isset($rules['min'])) $parts[] = "Min: {$rules['min']}";
    if (isset($rules['max'])) $parts[] = "Max: {$rules['max']}";
    if (isset($rules['required']) && $rules['required']) $parts[] = "Required";
    if (isset($rules['in']) && is_array($rules['in'])) $parts[] = "Options: " . implode(', ', $rules['in']);
    if (isset($rules['regex'])) $parts[] = "Pattern: {$rules['regex']}";
    
    return implode(' • ', $parts);
}
?>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>